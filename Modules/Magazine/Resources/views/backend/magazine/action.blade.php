<div class="d-flex gap-2 align-items-center justify-content-end">
    <a class="btn btn-warning-subtle btn-sm fs-4" href="{{ route('backend.magazines.edit', $data->id) }}" data-bs-toggle="tooltip" title="{{ __('messages.edit') }}">
        <i class="ph ph-pencil-simple-line align-middle"></i>
    </a>
    <a class="btn btn-secondary-subtle btn-sm fs-4" href="{{ route('backend.magazine-issues.index') }}?magazine_id={{ $data->id }}" data-bs-toggle="tooltip" title="{{ __('magazine::issues') }}">
        <i class="ph ph-list align-middle"></i>
    </a>
    <a href="{{ route('backend.magazines.destroy', $data->id) }}" class="btn btn-danger-subtle btn-sm fs-4" data-type="ajax" data-method="DELETE" data-token="{{ csrf_token() }}" data-bs-toggle="tooltip" title="{{ __('messages.delete') }}" data-confirm="{{ __('messages.are_you_sure?') }}">
        <i class="ph ph-trash align-middle"></i>
    </a>
</div>
