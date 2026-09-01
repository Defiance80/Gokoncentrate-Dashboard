<div class="d-flex gap-2 align-items-center justify-content-end">
    <a class="btn btn-secondary-subtle btn-sm fs-4" href="{{ route('backend.media-radar-runs.show', $data->id) }}"
        data-bs-toggle="tooltip" title="{{ __('mediaradar::mediaradar.preview') }}">
        <i class="ph ph-eye align-middle"></i>
    </a>

    @hasPermission('manage_media_radar_rules')
        @if ($data->rule_id)
            <button type="button" class="btn btn-warning-subtle btn-sm fs-4 media-radar-action"
                data-url="{{ route('backend.media-radar-runs.retry', $data->id) }}"
                data-bs-toggle="tooltip" title="{{ __('mediaradar::mediaradar.run_now') }}">
                <i class="ph ph-arrows-clockwise align-middle"></i>
            </button>
        @endif
    @endhasPermission
</div>
