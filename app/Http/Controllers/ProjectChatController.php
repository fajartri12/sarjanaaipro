<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\Project;
use App\Services\AI\AiService;
use App\Support\AiText;
use App\Services\AI\PromptLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectChatController extends Controller
{
    /** Berapa pesan lama yang dikirim ulang ke AI sebagai konteks. */
    private const CONTEXT_MESSAGES = 20;

    public function __construct(private AiService $ai) {}

    /**
     * Chat AI yang terikat pada satu project.
     * Riwayat disimpan di ai_conversations/ai_messages supaya tidak hilang saat refresh.
     */
    public function index(Request $request, Project $project): \Illuminate\View\View
    {
        $this->authorize('view', $project);

        $conversations = $project->conversations()
            ->where('user_id', $request->user()->id)
            ->latest('updated_at')
            ->get();

        $conversation = $request->filled('c')
            ? $conversations->firstWhere('id', (int) $request->query('c'))
            : $conversations->first();

        $conversation?->load('messages');

        return view('projects.chat', [
            'project' => $project,
            'conversations' => $conversations,
            'conversation' => $conversation,
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
        ]);

        $conversation = $project->conversations()->create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'feature' => 'chat',
        ]);

        return redirect()->route('projects.chat.index', ['project' => $project->id, 'c' => $conversation->id]);
    }

    /**
     * Kirim satu pesan, simpan balasan AI, kembalikan keduanya sebagai JSON.
     */
    public function send(Request $request, Project $project, AiConversation $conversation): JsonResponse
    {
        $this->authorize('view', $project);
        abort_unless($conversation->project_id === $project->id && $conversation->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:4000'],
        ]);

        // reorder() membuang orderBy default dari relasi messages(); tanpa itu
        // latest('id') cuma menempel di belakang order asc dan malah ambil
        // pesan paling awal.
        $history = $conversation->messages()
            ->reorder()
            ->latest('id')
            ->limit(self::CONTEXT_MESSAGES)
            ->get()
            ->reverse()
            ->values();

        $messages = [['role' => 'system', 'content' => $this->systemPrompt($project)]];

        foreach ($history as $past) {
            $messages[] = ['role' => $past->role, 'content' => $past->content];
        }

        $messages[] = ['role' => 'user', 'content' => $data['message']];

        $result = $this->ai->chat($messages, 'chat', $request->user(), $project);
        $answer = AiText::plain($result->text);

        $asked = $conversation->messages()->create(['role' => 'user', 'content' => $data['message']]);
        $replied = $conversation->messages()->create(['role' => 'assistant', 'content' => $answer]);

        $conversation->touch();

        return response()->json([
            'messages' => [
                ['id' => $asked->id, 'role' => 'user', 'content' => $asked->content],
                ['id' => $replied->id, 'role' => 'assistant', 'content' => $replied->content],
            ],
            'quota_left' => $this->quotaLeft($request),
        ]);
    }

    public function destroy(Request $request, Project $project, AiConversation $conversation): RedirectResponse
    {
        $this->authorize('view', $project);
        abort_unless($conversation->project_id === $project->id && $conversation->user_id === $request->user()->id, 404);

        $conversation->delete();

        return redirect()->route('projects.chat.index', $project->id)
            ->with('success', 'Percakapan dihapus.');
    }

    private function systemPrompt(Project $project): string
    {
        $parts = [
            PromptLibrary::base($project->degree_level),
            'Anda mendampingi satu project skripsi tertentu. Jawab spesifik untuk konteks project ini, bukan nasihat umum.',
        ];

        $context = array_filter([
            $project->title ? 'Judul: '.$project->title : null,
            $project->study_program ? 'Program studi: '.$project->study_program : null,
            $project->method ? 'Metode: '.$project->method : null,
        ]);

        if ($context) {
            $parts[] = "Konteks project:\n- ".implode("\n- ", $context);
        }

        return implode("\n\n", $parts);
    }

    private function quotaLeft(Request $request): ?int
    {
        $plan = $request->user()->currentPlan();
        $limit = $plan?->limit('ai_chat');

        return $limit === null || $limit < 0 ? null : $limit;
    }
}