<div class="d-flex gap-2 align-items-center justify-content-end">
    @hasPermission('manage_media_radar_sources')
        <a href="{{ route('backend.media-radar-sources.destroy_blocked', $data->id) }}"
            id="blocked-delete-{{ $data->id }}" class="btn btn-danger-subtle btn-sm fs-4"
            data-type="ajax" data-method="DELETE" data-token="{{ csrf_token() }}"
            data-bs-toggle="tooltip" title="{{ __('mediaradar::mediaradar.block_removed') }}"
            data-confirm="{{ __('messages.are_you_sure?') }}">
            <i class="ph ph-trash align-middle"></i>
        </a>
    @endhasPermission
</div>
