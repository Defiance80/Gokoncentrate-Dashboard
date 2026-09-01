<?php

namespace Modules\MediaRadar\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\MediaRadar\Jobs\Concerns\MediaRadarJob;
use Modules\MediaRadar\Models\MediaTrustedSource;
use Modules\MediaRadar\Services\TrustedSourceService;

class RefreshTrustedSourceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use MediaRadarJob;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public ?int $trustedSourceId = null,
        public int $limit = 10,
    ) {
        $this->onQueue($this->queueName());
    }

    public function handle(TrustedSourceService $sources): void
    {
        if ($this->trustedSourceId === null) {
            $sources->refreshAll($this->limit);

            return;
        }

        $source = MediaTrustedSource::find($this->trustedSourceId);

        if ($source !== null) {
            $sources->refresh($source, $this->limit);
        }
    }
}
