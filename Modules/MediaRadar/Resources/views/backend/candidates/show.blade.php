@extends('backend.layouts.app')

@section('title')
    {{ $candidate->displayTitle() }}
@endsection

@section('content')
    <x-back-button-component route="backend.media-radar-candidates.index" />

    @php
        $analysis = $candidate->analysis;
        $canApprove = auth()->user()->can('approve_media_radar');
        $canEdit = auth()->user()->can('edit_media_radar');
        $canSources = auth()->user()->can('manage_media_radar_sources');
        $reviewable = $candidate->isReviewable();
        $approved = in_array($candidate->status, [\Modules\MediaRadar\Support\CandidateStatus::APPROVED, \Modules\MediaRadar\Support\CandidateStatus::SCHEDULED], true);
        // An editor can accept from any non-terminal, not-yet-approved status.
        $canAccept = $canApprove
            && ! \Modules\MediaRadar\Support\CandidateStatus::isTerminal((string) $candidate->status)
            && ! $approved;
    @endphp

    <div class="row g-3">
        <div class="col-lg-7">
            {{-- Preview --}}
            <div class="card mb-3">
                <div class="card-body">
                    @if ($candidate->embeddable)
                        <div class="ratio ratio-16x9 mb-3">
                            <iframe src="{{ $candidate->embedUrl() }}" title="{{ $candidate->displayTitle() }}"
                                allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            {{ __('mediaradar::mediaradar.risk_flags') }}:
                            {{ $candidate->error_message ?: 'Embedding is disabled for this video.' }}
                        </div>
                    @endif

                    <a href="{{ $candidate->playbackUrl() }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary">
                        <i class="ph ph-arrow-square-out"></i> {{ __('mediaradar::mediaradar.open_on_platform') }}
                    </a>
                </div>
            </div>

            {{-- Editable metadata --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">{{ __('mediaradar::mediaradar.edit_metadata') }}</h5></div>
                {{ html()->form('PUT', route('backend.media-radar-candidates.update', $candidate->id))->class('requires-validation')->open() }}
                @csrf
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-12">
                            {{ html()->label(__('video.lbl_title'), 'editorial_title')->class('form-label') }}
                            {{ html()->text('editorial_title', old('editorial_title', $candidate->displayTitle()))->class('form-control')->required()->disabled(! $canEdit) }}
                        </div>
                        <div class="col-12">
                            {{ html()->label(__('messages.description'), 'editorial_description')->class('form-label') }}
                            {{ html()->textarea('editorial_description', old('editorial_description', $candidate->displayDescription()))->class('form-control')->rows(5)->disabled(! $canEdit) }}
                        </div>
                        <div class="col-12">
                            {{ html()->label(__('mediaradar::mediaradar.suggested').' '.__('messages.description'), 'editorial_summary')->class('form-label') }}
                            {{ html()->textarea('editorial_summary', old('editorial_summary', $candidate->editorial_summary))->class('form-control')->rows(2)->disabled(! $canEdit) }}
                        </div>
                        <div class="col-md-6">
                            {{ html()->label(__('mediaradar::mediaradar.lbl_genre'), 'genre_id')->class('form-label') }}
                            {{ html()->select('genre_id', $genres, old('genre_id', $candidate->genre_id))->class('form-control select2')->required()->disabled(! $canEdit) }}
                        </div>
                        <div class="col-md-6">
                            {{ html()->label(__('mediaradar::mediaradar.lbl_secondary_genres'), 'secondary_genre_ids')->class('form-label') }}
                            <select name="secondary_genre_ids[]" class="form-control select2" multiple @disabled(! $canEdit)>
                                @foreach ($genres as $id => $name)
                                    <option value="{{ $id }}" {{ in_array($id, (array) $candidate->secondary_genre_ids, true) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            {{ html()->label('Tags', 'editorial_tags')->class('form-label') }}
                            {{ html()->text('editorial_tags', implode(', ', (array) $candidate->editorial_tags))->class('form-control')->placeholder('tag one, tag two')->disabled(! $canEdit) }}
                        </div>
                        <div class="col-md-6">
                            {{ html()->label(__('mediaradar::mediaradar.cover_art'), 'poster_url')->class('form-label') }}
                            {{ html()->text('poster_url', old('poster_url', $candidate->poster_url))->class('form-control')->disabled(! $canEdit) }}
                            <small class="text-muted">
                                {{ $candidate->cover_art_source === 'cropped_local'
                                    ? __('mediaradar::mediaradar.cover_cropped')
                                    : __('mediaradar::mediaradar.cover_from_provider') }}
                            </small>
                        </div>
                        <div class="col-md-6">
                            {{ html()->label(__('mediaradar::mediaradar.cover_art').' (16:9)', 'thumbnail_url')->class('form-label') }}
                            {{ html()->text('thumbnail_url', old('thumbnail_url', $candidate->thumbnail_url))->class('form-control')->disabled(! $canEdit) }}
                        </div>
                        <div class="col-12">
                            <img src="{{ \Modules\MediaRadar\Services\CoverArtService::displayUrl($candidate->poster_url) }}"
                                alt="" class="rounded border" style="max-height:220px;">
                        </div>
                    </div>
                </div>
                @if ($canEdit)
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                    </div>
                @endif
                {{ html()->form()->close() }}
            </div>

            {{-- Decision history --}}
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('mediaradar::mediaradar.decision_history') }}</h5></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('messages.created_at') }}</th>
                                <th>{{ __('banner.lbl_action') }}</th>
                                <th>{{ __('messages.lbl_status') }}</th>
                                <th>{{ __('mediaradar::mediaradar.notes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($candidate->decisions as $decision)
                                <tr>
                                    <td>{{ $decision->created_at }}</td>
                                    <td>
                                        {{ ucfirst(str_replace('_', ' ', $decision->decision) ) }}
                                        <span class="text-muted small d-block">
                                            {{ optional($decision->editor)->name ?: 'Media Radar' }}
                                        </span>
                                    </td>
                                    <td>{{ $decision->previous_status }} &rarr; {{ $decision->new_status }}</td>
                                    <td>
                                        {{ \Modules\MediaRadar\Support\RejectionReason::label($decision->rejection_reason) }}
                                        {{ $decision->notes }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">-</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            {{-- Editorial controls --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('mediaradar::mediaradar.editorial_controls') }}</h5>
                    <span class="badge bg-primary-subtle">{{ \Modules\MediaRadar\Support\CandidateStatus::label((string) $candidate->status) }}</span>
                </div>
                <div class="card-body d-grid gap-2">
                    @if ($canAccept)
                        {{-- Primary action: accept and publish straight to the library. --}}
                        <button type="button" class="btn btn-success btn-lg media-radar-action"
                            data-url="{{ route('backend.media-radar-candidates.approve', $candidate->id) }}"
                            data-payload="publish_now=1">
                            <i class="ph ph-check-circle"></i> {{ __('mediaradar::mediaradar.approve_and_publish') }}
                        </button>
                        <button type="button" class="btn btn-outline-success media-radar-action"
                            data-url="{{ route('backend.media-radar-candidates.approve', $candidate->id) }}">
                            <i class="ph ph-clock"></i> {{ __('mediaradar::mediaradar.accept_hold') }}
                        </button>
                    @endif

                    @if ($canApprove && $approved && ! $candidate->published_entertainment_id)
                        <button type="button" class="btn btn-primary media-radar-action"
                            data-url="{{ route('backend.media-radar-candidates.publish', $candidate->id) }}">
                            <i class="ph ph-broadcast"></i> {{ __('mediaradar::mediaradar.publish_now') }}
                        </button>
                    @endif

                    @if ($canApprove)
                        <div class="input-group">
                            <input type="datetime-local" class="form-control" id="scheduled_for">
                            <button type="button" class="btn btn-outline-secondary media-radar-action" id="schedule-button"
                                data-url="{{ route('backend.media-radar-candidates.schedule', $candidate->id) }}">
                                <i class="ph ph-calendar"></i> {{ __('mediaradar::mediaradar.scheduled') }}
                            </button>
                        </div>

                        <div class="border rounded p-2">
                            <label class="form-label small mb-1">{{ __('mediaradar::mediaradar.rejection_reason') }}</label>
                            <select class="form-control mb-2" id="rejection_reason">
                                @foreach ($rejectionReasons as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <textarea class="form-control mb-2" id="rejection_notes" rows="2"
                                placeholder="{{ __('mediaradar::mediaradar.notes') }}"></textarea>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-danger reject-button media-radar-action"
                                    data-url="{{ route('backend.media-radar-candidates.reject', $candidate->id) }}"
                                    data-redirect="{{ route('backend.media-radar-candidates.index') }}">
                                    {{ __('mediaradar::mediaradar.reject') }}
                                </button>
                                <button type="button" class="btn btn-outline-danger reject-button media-radar-action" data-block="1"
                                    data-url="{{ route('backend.media-radar-candidates.reject', $candidate->id) }}"
                                    data-redirect="{{ route('backend.media-radar-candidates.index') }}">
                                    {{ __('mediaradar::mediaradar.reject_and_block') }}
                                </button>
                            </div>
                        </div>
                    @endif

                    @if ($canSources)
                        <button type="button" class="btn btn-outline-secondary media-radar-action"
                            data-url="{{ route('backend.media-radar-candidates.trust', $candidate->id) }}">
                            <i class="ph ph-shield-check"></i> {{ __('mediaradar::mediaradar.trust_creator') }}
                        </button>
                    @endif

                    @if ($canEdit)
                        <button type="button" class="btn btn-outline-secondary media-radar-action"
                            data-url="{{ route('backend.media-radar-candidates.analyze', $candidate->id) }}">
                            <i class="ph ph-arrows-clockwise"></i> {{ __('mediaradar::mediaradar.reanalyze') }}
                        </button>
                        <button type="button" class="btn btn-outline-secondary media-radar-action"
                            data-url="{{ route('backend.media-radar-candidates.archive', $candidate->id) }}"
                            data-confirm="{{ __('messages.are_you_sure?') }}">
                            <i class="ph ph-archive"></i> {{ __('mediaradar::mediaradar.archive') }}
                        </button>
                    @endif

                    @if ($candidate->published_entertainment_id)
                        <a class="btn btn-outline-primary" href="{{ route('backend.movies.edit', $candidate->published_entertainment_id) }}">
                            {{ __('mediaradar::mediaradar.published_as') }} #{{ $candidate->published_entertainment_id }}
                        </a>
                    @endif
                </div>
            </div>

            {{-- Analysis --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('mediaradar::mediaradar.ai_analysis') }}</h5>
                    <span class="badge bg-success-subtle fs-6">{{ $candidate->editorial_score ?? '-' }}</span>
                </div>
                <div class="card-body">
                    @if ($analysis)
                        <p class="small mb-2"><strong>{{ __('mediaradar::mediaradar.why_selected') }}:</strong> {{ $analysis->explanation }}</p>
                        <ul class="list-unstyled small mb-2">
                            <li>Relevance: {{ $analysis->relevance_score }}/30</li>
                            <li>Source quality: {{ $analysis->source_quality_score }}/15</li>
                            <li>Production quality: {{ $analysis->production_quality_score }}/15</li>
                            <li>Recency: {{ $analysis->recency_score }}/10</li>
                            <li>Audience interest: {{ $analysis->audience_interest_score }}/10</li>
                            <li>Originality: {{ $analysis->originality_score }}/10</li>
                            <li>Brand fit: {{ $analysis->brand_fit_score }}/10</li>
                        </ul>
                        @if ($analysis->riskFlags())
                            <p class="small mb-1"><strong>{{ __('mediaradar::mediaradar.risk_flags') }}:</strong></p>
                            @foreach ($analysis->riskFlags() as $flag)
                                <span class="badge bg-warning-subtle">{{ $flag }}</span>
                            @endforeach
                        @endif
                        <p class="small text-muted mt-2 mb-0">{{ $analysis->source }} &middot; {{ $analysis->model_reference ?: 'no model' }}</p>
                    @else
                        <p class="text-muted mb-0">-</p>
                    @endif
                </div>
            </div>

            {{-- Original information --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">{{ __('mediaradar::mediaradar.original_information') }}</h5></div>
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-5">{{ __('mediaradar::mediaradar.lbl_providers') }}</dt>
                        <dd class="col-7 text-capitalize">{{ $candidate->provider }}</dd>
                        <dt class="col-5">{{ __('mediaradar::mediaradar.lbl_creator_name') }}</dt>
                        <dd class="col-7">
                            @if ($candidate->creator_url)
                                <a href="{{ $candidate->creator_url }}" target="_blank" rel="noopener noreferrer">{{ $candidate->creator_name }}</a>
                            @else
                                {{ $candidate->creator_name ?: '-' }}
                            @endif
                        </dd>
                        <dt class="col-5">{{ __('mediaradar::mediaradar.lbl_release_years') }}</dt>
                        <dd class="col-7">{{ optional($candidate->published_at)->toDateString() ?: '-' }}</dd>
                        <dt class="col-5">{{ __('mediaradar::mediaradar.lbl_duration') }}</dt>
                        <dd class="col-7">{{ $candidate->durationLabel() }}</dd>
                        <dt class="col-5">{{ __('mediaradar::mediaradar.lbl_min_quality') }}</dt>
                        <dd class="col-7">
                            {{ $candidate->quality_label ?: '-' }}
                            @unless ($candidate->quality_verified)
                                <span class="text-muted">(reported as a floor)</span>
                            @endunless
                        </dd>
                        <dt class="col-5">Views</dt>
                        <dd class="col-7">{{ number_format((int) $candidate->view_count) }}</dd>
                        <dt class="col-5">Likes</dt>
                        <dd class="col-7">{{ number_format((int) $candidate->like_count) }}</dd>
                    </dl>
                    <hr>
                    <p class="text-muted mb-0">{{ \Illuminate\Support\Str::limit($candidate->original_description, 600) }}</p>
                </div>
            </div>

            {{-- Discovery information --}}
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('mediaradar::mediaradar.discovery_information') }}</h5></div>
                <div class="card-body small">
                    <p class="mb-1">
                        {{ __('mediaradar::mediaradar.matched_rules') }}:
                        @forelse ($candidate->ruleMatches as $match)
                            <span class="badge bg-secondary-subtle">{{ optional($match->rule)->name ?: '-' }}</span>
                        @empty
                            -
                        @endforelse
                    </p>
                    <p class="mb-1">
                        {{ __('mediaradar::mediaradar.matched_terms') }}:
                        @foreach ($candidate->ruleMatches as $match)
                            {{ implode(', ', (array) $match->matched_terms) }}
                        @endforeach
                    </p>
                    <p class="mb-2">
                        {{ __('mediaradar::mediaradar.last_checked') }}:
                        {{ optional($candidate->discovered_at)->diffForHumans() ?: '-' }}
                    </p>

                    @if ($siblings->isNotEmpty())
                        <hr>
                        <p class="mb-1">{{ __('mediaradar::mediaradar.from_same_creator') }}</p>
                        <ul class="list-unstyled mb-0">
                            @foreach ($siblings as $sibling)
                                <li>
                                    <a href="{{ route('backend.media-radar-candidates.show', $sibling->id) }}">
                                        {{ \Illuminate\Support\Str::limit($sibling->displayTitle(), 60) }}
                                    </a>
                                    <span class="text-muted">({{ $sibling->status }})</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
    @include('mediaradar::backend.partials.actions-script')

    <script type="text/javascript">
        /**
         * The schedule and reject buttons carry form values, so their payload is
         * built on mousedown -- before the delegated click handler in
         * actions-script runs and reads data-payload.
         */
        document.addEventListener('DOMContentLoaded', function () {
            const scheduleButton = document.getElementById('schedule-button');

            if (scheduleButton) {
                scheduleButton.addEventListener('mousedown', function () {
                    const value = document.getElementById('scheduled_for').value;
                    scheduleButton.dataset.payload = value
                        ? 'scheduled_for=' + encodeURIComponent(value)
                        : '';
                });
            }

            document.querySelectorAll('.reject-button').forEach(function (button) {
                button.addEventListener('mousedown', function () {
                    const reason = document.getElementById('rejection_reason').value;
                    const notes = document.getElementById('rejection_notes').value;

                    button.dataset.payload = 'rejection_reason=' + encodeURIComponent(reason)
                        + '&notes=' + encodeURIComponent(notes)
                        + '&block_creator=' + (button.dataset.block ? '1' : '0');
                });
            });
        });
    </script>
@endpush
