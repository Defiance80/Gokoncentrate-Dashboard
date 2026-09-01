<?php

namespace Modules\MediaRadar\Console;

use Illuminate\Console\Command;
use Modules\MediaRadar\Jobs\PublishCandidateJob;
use Modules\MediaRadar\Models\MediaCandidate;
use Modules\MediaRadar\Models\MediaRadarSetting;
use Modules\MediaRadar\Support\CandidateStatus;

/**
 * Housekeeping: publish anything whose scheduled time has arrived, then archive
 * stale unreviewed candidates.
 *
 * Archiving keeps the row (and therefore the duplicate history and the audit
 * trail); nothing is hard deleted.
 */
class ExpireCandidatesCommand extends Command
{
    protected $signature = 'media-radar:expire-candidates {--dry-run : Report what would change without writing}';

    protected $description = 'Publish due scheduled candidates and archive stale unreviewed ones';

    public function handle(): int
    {
        $settings = MediaRadarSetting::getInstance();

        $published = $this->publishDue($settings);
        $archived = $this->archiveStale($settings);

        $this->info(sprintf(
            '%s%d scheduled candidate(s) queued for publishing, %d stale candidate(s) archived.',
            $this->option('dry-run') ? '[dry run] ' : '',
            $published,
            $archived
        ));

        return self::SUCCESS;
    }

    private function publishDue(MediaRadarSetting $settings): int
    {
        $due = MediaCandidate::query()
            ->where('publication_id', $settings->publication_id)
            ->where('status', CandidateStatus::SCHEDULED)
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->whereNull('published_entertainment_id')
            ->get();

        foreach ($due as $candidate) {
            if (! $this->option('dry-run')) {
                PublishCandidateJob::dispatch($candidate->id, $candidate->approved_by);
            }
        }

        return $due->count();
    }

    private function archiveStale(MediaRadarSetting $settings): int
    {
        $days = (int) $settings->candidate_expiration_days;

        if ($days <= 0) {
            return 0;
        }

        $query = MediaCandidate::query()
            ->where('publication_id', $settings->publication_id)
            ->where('status', CandidateStatus::READY_FOR_REVIEW)
            ->where('discovered_at', '<=', now()->subDays($days))
            // Priority candidates are kept longer; only low scorers expire on
            // the short window.
            ->where(function ($q) use ($days) {
                $q->where('editorial_score', '<', 75)
                    ->orWhere('discovered_at', '<=', now()->subDays($days + 30));
            });

        if ($this->option('dry-run')) {
            return $query->count();
        }

        $count = 0;

        foreach ($query->cursor() as $candidate) {
            $candidate->forceFill(['status' => CandidateStatus::ARCHIVED])->save();
            $count++;
        }

        return $count;
    }
}
