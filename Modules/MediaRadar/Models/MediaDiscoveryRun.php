<?php

namespace Modules\MediaRadar\Models;

use Illuminate\Database\Eloquent\Model;

class MediaDiscoveryRun extends Model
{
    protected $table = 'media_discovery_runs';

    protected $fillable = [
        'rule_id',
        'provider',
        'status',
        'trigger',
        'started_at',
        'completed_at',
        'results_received',
        'new_candidates',
        'existing_candidates',
        'filtered_out',
        'errors_count',
        'provider_request_count',
        'error_summary',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_FAILED = 'failed';

    public function rule()
    {
        return $this->belongsTo(MediaDiscoveryRule::class, 'rule_id')->withTrashed();
    }

    public function markRunning(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    public function markCompleted(array $counters = []): void
    {
        $this->update(array_merge($counters, [
            'status' => ($counters['errors_count'] ?? $this->errors_count) > 0
                ? self::STATUS_PARTIAL
                : self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]));
    }

    public function markFailed(string $message, array $counters = []): void
    {
        $this->update(array_merge($counters, [
            'status' => self::STATUS_FAILED,
            'errors_count' => ($this->errors_count ?? 0) + 1,
            'error_summary' => mb_substr($message, 0, 2000),
            'completed_at' => now(),
        ]));
    }

    public function durationLabel(): string
    {
        if ($this->started_at === null || $this->completed_at === null) {
            return '-';
        }

        return $this->started_at->diffForHumans($this->completed_at, true);
    }
}
