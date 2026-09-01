<div class="d-flex gap-2 align-items-center justify-content-end">
    @hasPermission('manage_media_radar_sources')
        <button type="button" class="btn btn-success-subtle btn-sm fs-4 media-radar-action"
            data-url="{{ route('backend.media-radar-sources.refresh', $data->id) }}"
            data-bs-toggle="tooltip" title="{{ __('mediaradar::mediaradar.source_refresh_queued') }}">
            <i class="ph ph-arrows-clockwise align-middle"></i>
        </button>

        <a href="{{ route('backend.media-radar-sources.destroy_trusted', $data->id) }}"
            id="trusted-delete-{{ $data->id }}" class="btn btn-danger-subtle btn-sm fs-4"
            data-type="ajax" data-method="DELETE" data-token="{{ csrf_token() }}"
            data-bs-toggle="tooltip" title="{{ __('mediaradar::mediaradar.trust_removed') }}"
            data-confirm="{{ __('messages.are_you_sure?') }}">
            <i class="ph ph-trash align-middle"></i>
        </a>
    @endhasPermission
</div>
