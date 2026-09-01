@extends('backend.layouts.app')

@section('title')
    {{ __('mediaradar::mediaradar.title') }}
@endsection

@section('content')
    <div class="row g-3 mb-4">
        @php
            $tiles = [
                ['label' => __('mediaradar::mediaradar.new_candidates'), 'value' => $counters['new'], 'icon' => 'ph-tray', 'class' => 'text-primary'],
                ['label' => __('mediaradar::mediaradar.priority_candidates'), 'value' => $counters['priority'], 'icon' => 'ph-star', 'class' => 'text-success'],
                ['label' => __('mediaradar::mediaradar.reviewed_today'), 'value' => $counters['reviewed_today'], 'icon' => 'ph-check-circle', 'class' => 'text-info'],
                ['label' => __('mediaradar::mediaradar.scheduled'), 'value' => $counters['scheduled'], 'icon' => 'ph-calendar', 'class' => 'text-warning'],
                ['label' => __('mediaradar::mediaradar.published'), 'value' => $counters['published'], 'icon' => 'ph-broadcast', 'class' => 'text-success'],
                ['label' => __('mediaradar::mediaradar.errors'), 'value' => $counters['errors'], 'icon' => 'ph-warning-circle', 'class' => 'text-danger'],
            ];
        @endphp

        @foreach ($tiles as $tile)
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="ph {{ $tile['icon'] }} fs-2 {{ $tile['class'] }}"></i>
                        <div>
                            <div class="fs-3 fw-semibold">{{ $tile['value'] }}</div>
                            <div class="text-muted small">{{ $tile['label'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('mediaradar::mediaradar.priority_queue') }}</h5>
                    <a href="{{ route('backend.media-radar-candidates.index') }}" class="btn btn-sm btn-primary">
                        {{ __('mediaradar::mediaradar.candidates') }}
                    </a>
                </div>
                <div class="card-body">
                    @forelse ($priorityCandidates as $candidate)
                        <div class="d-flex gap-3 align-items-start border-bottom py-3">
                            <img src="{{ \Modules\MediaRadar\Services\CoverArtService::displayUrl($candidate->thumbnail_url ?: $candidate->poster_url) }}"
                                alt="" class="rounded" style="width:132px;height:74px;object-fit:cover;">
                            <div class="flex-grow-1">
                                <a href="{{ route('backend.media-radar-candidates.show', $candidate->id) }}" class="fw-semibold text-decoration-none">
                                    {{ $candidate->displayTitle() }}
                                </a>
                                <div class="text-muted small mt-1">
                                    <span class="text-uppercase">{{ $candidate->provider }}</span>
                                    &middot; {{ $candidate->durationLabel() }}
                                    &middot; {{ $candidate->creator_name ?: '-' }}
                                    @if ($candidate->published_at)
                                        &middot; {{ $candidate->published_at->diffForHumans() }}
                                    @endif
                                </div>
                                <div class="small mt-1">
                                    {{ __('mediaradar::mediaradar.suggested') }}:
                                    <strong>{{ optional($candidate->genre)->name ?: '-' }}</strong>
                                </div>
                            </div>
                            <span class="badge bg-success-subtle fs-6">{{ $candidate->editorial_score ?? '-' }}</span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">{{ __('mediaradar::mediaradar.no_candidates') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('mediaradar::mediaradar.recent_runs') }}</h5>
                    <a href="{{ route('backend.media-radar-runs.index') }}" class="btn btn-sm btn-outline-secondary">
                        {{ __('mediaradar::mediaradar.runs') }}
                    </a>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('mediaradar::mediaradar.rule') }}</th>
                                <th>{{ __('mediaradar::mediaradar.lbl_providers') }}</th>
                                <th>{{ __('messages.lbl_status') }}</th>
                                <th class="text-end">{{ __('mediaradar::mediaradar.new_candidates_col') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentRuns as $run)
                                <tr>
                                    <td><a href="{{ route('backend.media-radar-runs.show', $run->id) }}">{{ $run->id }}</a></td>
                                    <td>{{ optional($run->rule)->name ?: __('mediaradar::mediaradar.trusted_source_watch') }}</td>
                                    <td class="text-capitalize">{{ $run->provider }}</td>
                                    <td>{{ ucfirst($run->status) }}</td>
                                    <td class="text-end">{{ $run->new_candidates }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">-</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">{{ __('mediaradar::mediaradar.approval_mode') }}</h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-2">
                        <input type="checkbox" class="form-check-input" id="auto_approve_toggle"
                            data-url="{{ route('backend.media-radar-settings.toggle_auto_approve') }}"
                            {{ $settings->auto_approve ? 'checked' : '' }}
                            @cannot('manage_media_radar_settings') disabled @endcannot>
                        <label class="form-check-label" for="auto_approve_toggle" id="auto_approve_label">
                            {{ $settings->auto_approve ? __('mediaradar::mediaradar.auto_mode') : __('mediaradar::mediaradar.manual_mode') }}
                        </label>
                    </div>
                    <small class="text-muted d-block">{{ __('mediaradar::mediaradar.auto_approval_help') }}</small>
                    <hr>
                    <div class="d-flex justify-content-between small">
                        <span>{{ __('mediaradar::mediaradar.active_rules') }}</span><strong>{{ $activeRules }}</strong>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span>{{ __('mediaradar::mediaradar.trusted_sources') }}</span><strong>{{ $trustedSources }}</strong>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span>{{ __('mediaradar::mediaradar.pipeline') }}</span><strong>{{ $pipeline['analysis_queue'] }}</strong>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('mediaradar::mediaradar.provider_health') }}</h5></div>
                <div class="card-body">
                    @foreach ($providerHealth as $provider)
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>{{ $provider['label'] }}</strong>
                                <span class="badge {{ $provider['status'] === 'healthy' ? 'bg-success-subtle' : ($provider['status'] === 'not_configured' ? 'bg-secondary-subtle' : 'bg-danger-subtle') }}">
                                    {{ __('mediaradar::mediaradar.'.$provider['status']) }}
                                </span>
                            </div>
                            <div class="small text-muted">
                                {{ __('mediaradar::mediaradar.last_success') }}:
                                {{ $provider['last_success_at'] ?? '-' }}
                            </div>
                            @if (isset($provider['detail']['searches_today']))
                                <div class="small text-muted">
                                    {{ __('mediaradar::mediaradar.searches_today') }}:
                                    {{ $provider['detail']['searches_today'] }} / {{ $provider['detail']['daily_search_cap'] }}
                                </div>
                            @endif
                            @if (isset($provider['detail']['rate_remaining']))
                                <div class="small text-muted">
                                    {{ __('mediaradar::mediaradar.rate_remaining') }}: {{ $provider['detail']['rate_remaining'] ?? '-' }}
                                </div>
                            @endif
                            @if (! empty($provider['last_error']))
                                <div class="small text-danger">{{ \Illuminate\Support\Str::limit($provider['last_error'], 120) }}</div>
                            @endif
                        </div>
                    @endforeach
                    <p class="small text-muted mb-0">{{ __('mediaradar::mediaradar.credentials_note') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('auto_approve_toggle');

            if (!toggle) {
                return;
            }

            toggle.addEventListener('change', function () {
                fetch(toggle.dataset.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: toggle.checked ? 1 : 0 })
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('auto_approve_label').textContent = data.auto_approve
                        ? @json(__('mediaradar::mediaradar.auto_mode'))
                        : @json(__('mediaradar::mediaradar.manual_mode'));
                })
                .catch(() => { toggle.checked = !toggle.checked; });
            });
        });
    </script>
@endpush
