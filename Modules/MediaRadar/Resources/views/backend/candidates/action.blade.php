@php
    $canApprove = auth()->user()->can('approve_media_radar');
    $canEdit = auth()->user()->can('edit_media_radar');
    $reviewable = $data->isReviewable();
    $approved = in_array($data->status, [\Modules\MediaRadar\Support\CandidateStatus::APPROVED, \Modules\MediaRadar\Support\CandidateStatus::SCHEDULED], true);
@endphp

<div class="d-flex gap-2 align-items-center justify-content-end">
    <a class="btn btn-secondary-subtle btn-sm fs-4" href="{{ route('backend.media-radar-candidates.show', $data->id) }}"
        data-bs-toggle="tooltip" title="{{ __('mediaradar::mediaradar.preview') }}">
        <i class="ph ph-eye align-middle"></i>
    </a>

    @if ($canApprove && $reviewable)
        <button type="button" class="btn btn-success-subtle btn-sm fs-4 media-radar-action"
            data-url="{{ route('backend.media-radar-candidates.approve', $data->id) }}"
            data-bs-toggle="tooltip" title="{{ __('mediaradar::mediaradar.approve') }}">
            <i class="ph ph-check-circle align-middle"></i>
        </button>
        <button type="button" class="btn btn-danger-subtle btn-sm fs-4 media-radar-action"
            data-url="{{ route('backend.media-radar-candidates.reject', $data->id) }}"
            data-confirm="{{ __('messages.are_you_sure?') }}"
            data-bs-toggle="tooltip" title="{{ __('mediaradar::mediaradar.reject') }}">
            <i class="ph ph-x-circle align-middle"></i>
        </button>
    @endif

    @if ($canApprove && $approved && ! $data->published_entertainment_id)
        <button type="button" class="btn btn-primary-subtle btn-sm fs-4 media-radar-action"
            data-url="{{ route('backend.media-radar-candidates.publish', $data->id) }}"
            data-bs-toggle="tooltip" title="{{ __('mediaradar::mediaradar.publish_now') }}">
            <i class="ph ph-broadcast align-middle"></i>
        </button>
    @endif

    @if ($canEdit)
        <button type="button" class="btn btn-warning-subtle btn-sm fs-4 media-radar-action"
            data-url="{{ route('backend.media-radar-candidates.archive', $data->id) }}"
            data-confirm="{{ __('messages.are_you_sure?') }}"
            data-bs-toggle="tooltip" title="{{ __('mediaradar::mediaradar.archive') }}">
            <i class="ph ph-archive align-middle"></i>
        </button>
    @endif
</div>
