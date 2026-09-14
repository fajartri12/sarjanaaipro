<?php

namespace App\Console\Commands;

use App\Models\AppNotification;
use App\Models\Project;
use Illuminate\Console\Command;

class SendDeadlineReminders extends Command
{
    protected $signature = 'notifications:deadlines';
    protected $description = 'Kirim notifikasi pengingat deadline project yang mendekat.';

    public function handle(): int
    {
        $days = [7, 3, 1]; // hari sebelum deadline yang memicu notifikasi.

        foreach ($days as $day) {
            $projects = Project::whereNotNull('deadline')
                ->where('status', 'aktif')
                ->whereDate('deadline', now()->addDays($day)->toDateString())
                ->where(function ($q) use ($day) {
                    // Belum pernah dikirimi notifikasi, atau sudah lebih dari 3 hari sejak notifikasi terakhir.
                    $q->whereNull('deadline_notified_at')
                        ->orWhere('deadline_notified_at', '<', now()->subDays(3));
                })
                ->with('user')
                ->get();

            foreach ($projects as $project) {
                $user = $project->user;
                if (! $user) continue;

                $daysLeft = $day;
                $title = $daysLeft === 1
                    ? 'Deadline besok!'
                    : "Deadline {$daysLeft} hari lagi";

                $body = "Project \"{$project->name}\" target selesai {$project->deadline->translatedFormat('j M Y')}.";

                AppNotification::create([
                    'user_id' => $user->id,
                    'type' => 'warning',
                    'title' => $title,
                    'body' => $body,
                    'data' => ['project_id' => $project->id],
                ]);

                $project->update(['deadline_notified_at' => now()]);

                $this->info("Notifikasi deadline untuk {$user->email}: {$title}");
            }
        }

        return self::SUCCESS;
    }
}