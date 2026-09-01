<div class="d-flex gap-2 align-items-center justify-content-end">
    @hasPermission('manage_media_radar_rules')
        <button type="button" class="btn btn-success-subtle btn-sm fs-4 media-radar-action"
            data-url="{{ route('backend.media-radar-rules.run', $data->id) }}"
            data-bs-toggle="tooltip" title="{{ __('mediaradar::mediaradar.run_now') }}">
            <i class="ph ph-play-circle align-middle"></i>
        </button>
    @endhasPermission

    <a class="btn btn-secondary-subtle btn-sm fs-4" href="{{ route('backend.media-radar-rules.runs', $data->id) }}"
        data-bs-toggle="tooltip" title="{{ __('mediaradar::mediaradar.runs') }}">
        <i class="ph ph-clock-counter-clockwise align-middle"></i>
    </a>

    @hasPermission('manage_media_radar_rules')
        <a class="btn btn-warning-subtle btn-sm fs-4" href="{{ route('backend.media-radar-rules.edit', $data->id) }}"
            data-bs-toggle="tooltip" title="{{ __('messages.edit') }}">
            <i class="ph ph-pencil-simple-line align-middle"></i>
        </a>

        <button type="button" class="btn btn-info-subtle btn-sm fs-4 media-radar-action"
            data-url="{{ route('backend.media-radar-rules.duplicate', $data->id) }}"
            data-bs-toggle="tooltip" title="{{ __('mediaradar::mediaradar.duplicate') }}">
            <i class="ph ph-copy align-middle"></i>
        </button>

        <a href="{{ route('backend.media-radar-rules.destroy', $data->id) }}" id="rule-delete-{{ $data->id }}"
            class="btn btn-danger-subtle btn-sm fs-4" data-type="ajax" data-method="DELETE"
            data-token="{{ csrf_token() }}" data-bs-toggle="tooltip" title="{{ __('messages.delete') }}"
            data-confirm="{{ __('messages.are_you_sure?') }}">
            <i class="ph ph-trash align-middle"></i>
        </a>
    @endhasPermission
</div>
