<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\SemproQuestion;
use App\Models\SemproSession;
use App\Services\AI\QuestionBankService;
use App\Services\AI\SemproService;
use Illuminate\Http\Request;

class SemproController extends Controller
{
    public function __construct(
        private SemproService $sempro,
        private QuestionBankService $bank,
    ) {}

    public function index(Request $request): \Illuminate\View\View
    {
        $user = $request->user();

        return view('sempro.index', [
            'sessions' => SemproSession::with('project:id,name,title')
                ->ownedBy($user->id)
                ->latest()
                ->get(),
            'projects' => $user->projects()->get(['id', 'name', 'title']),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'question_count' => ['nullable', 'integer', 'min:3', 'max:10'],
            'difficulty' => ['nullable', 'in:dasar,menengah,sulit'],
            'category' => ['nullable', 'string'],
        ]);

        $project = Project::findOrFail($data['project_id']);
        abort_unless($project->user_id === $request->user()->id, 403);

        $session = $this->sempro->startSession($project);

        // Coba ambil dari bank dulu (gratis & instan).
        $fromBank = $this->bank->pick(
            $project,
            $data['category'] ?? null,
            $data['difficulty'] ?? null,
            $data['question_count'] ?? 5,
        );

        if ($fromBank->isNotEmpty()) {
            // Bank cukup — simpan pertanyaan dari bank.
            $position = -1;
            foreach ($fromBank as $row) {
                SemproQuestion::create([
                    'sempro_session_id' => $session->id,
                    'project_id' => $project->id,
                    'category' => $row->category,
                    'difficulty' => $row->difficulty,
                    'question' => $row->question,
                    'expected_points' => $row->expected_points,
                    'position' => ++$position,
                    'source' => 'bank',
                ]);
            }
            $session->update(['question_count' => $fromBank->count()]);
        } else {
            // Bank kosong — minta AI.
            $this->sempro->generateQuestions(
                $session,
                $data['question_count'] ?? 5,
                $data['category'] ?? null,
            );
        }

        return redirect()->route('sempro.show', $session)
            ->with('success', 'Sesi sempro siap. Jawab pertanyaan satu per satu.');
    }

    public function show(Request $request, SemproSession $session): \Illuminate\View\View
    {
        abort_unless($session->user_id === $request->user()->id, 403);

        return view('sempro.show', [
            'session' => $session->load([
                'project:id,name,title,method',
                'questions',
                'answers',
                'evaluations',
            ]),
        ]);
    }

    public function answer(Request $request, SemproSession $session): \Illuminate\Http\RedirectResponse
    {
        abort_unless($session->user_id === $request->user()->id, 403);
        abort_if($session->status === 'finished', 422, 'Sesi sudah selesai.');

        $data = $request->validate([
            'question_id' => ['required', 'exists:sempro_questions,id'],
            'answer' => ['required', 'string', 'min:20', 'max:5000'],
        ]);

        $question = SemproQuestion::where('sempro_session_id', $session->id)
            ->findOrFail($data['question_id']);

        $evaluation = $this->sempro->submitAnswer($session, $question, $data['answer']);

        return back()
            ->with('success', 'Jawaban dinilai.')
            ->with('evaluation', $evaluation);
    }

    public function followUp(Request $request, SemproSession $session): \Illuminate\Http\RedirectResponse
    {
        abort_unless($session->user_id === $request->user()->id, 403);
        abort_if($session->status === 'finished', 422, 'Sesi sudah selesai.');

        $data = $request->validate([
            'category' => ['required', 'string', 'max:60'],
            'count' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $questions = $this->bank->followUp(
            $this->sempro,
            $session,
            $data['category'],
            $data['count'] ?? 3,
        );

        return back()->with(
            'success',
            $questions
                ? 'Pertanyaan lanjutan ditambahkan untuk memperkuat '.$data['category'].'.'
                : 'Belum ada pertanyaan lanjutan yang tersedia.',
        );
    }

    public function readiness(Request $request, Project $project): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $project);

        return response()->json($this->bank->readiness($project));
    }

    public function finish(Request $request, SemproSession $session): \Illuminate\Http\RedirectResponse
    {
        abort_unless($session->user_id === $request->user()->id, 403);

        $this->sempro->finish($session);

        return redirect()->route('sempro.result', $session)
            ->with('success', 'Sesi sempro selesai. Lihat hasilnya.');
    }

    public function result(Request $request, SemproSession $session): \Illuminate\View\View
    {
        abort_unless($session->user_id === $request->user()->id, 403);

        return view('sempro.result', [
            'session' => $session->load([
                'project:id,name,title',
                'questions',
                'answers',
                'evaluations',
            ]),
        ]);
    }

    public function destroy(Request $request, SemproSession $session): \Illuminate\Http\RedirectResponse
    {
        abort_unless($session->user_id === $request->user()->id, 403);

        $session->delete();

        return redirect()->route('sempro.index')->with('success', 'Sesi dihapus.');
    }
}
