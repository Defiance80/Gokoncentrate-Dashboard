@php
    $mediaUrls = $mediaUrls ?? [];
    $videoExtensions = ['mp4', 'mov', 'avi', 'webm', 'mkv', 'm4v'];
    $imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp', 'tif', 'tiff', 'ico'];
@endphp

@if (empty($mediaUrls))
    <div class="text-center text-muted py-3">{{ __('messages.no_data_available') }}</div>
@else
    @foreach ($mediaUrls as $mediaUrl)
        @php
            $mediaPath = parse_url($mediaUrl, PHP_URL_PATH) ?? '';
            $extension = strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION));
            $fileName = basename($mediaPath);
            $folderName = trim(dirname($mediaPath), '/');
            $folderName = $folderName !== '.' ? basename($folderName) : 'default';
            $type = in_array($extension, $videoExtensions, true) ? 'video' : (in_array($extension, $imageExtensions, true) ? 'image' : 'file');
        @endphp

        <div class="iq-media-images position-relative mb-3" id="media-images" title="{{ $fileName }}">
            <button type="button"
                class="btn btn-sm btn-danger iq-button-delete position-absolute top-0 end-0 m-1"
                onclick="deleteImage(@json($mediaUrl), @json($type), @json($fileName), @json($folderName))"
                aria-label="{{ __('frontend.delete') }}">
                <i class="ph ph-trash"></i>
            </button>

            @if ($type === 'video')
                <video class="img-fluid" controls preload="metadata" style="max-width: 10rem; max-height: 10rem;">
                    <source src="{{ $mediaUrl }}">
                </video>
            @elseif ($type === 'image')
                <img src="{{ $mediaUrl }}" class="img-fluid" loading="lazy"
                    style="max-width: 10rem; max-height: 10rem; object-fit: cover;" alt="{{ $fileName }}">
            @else
                <div class="border rounded p-3 bg-light text-muted" style="width: 10rem;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ph ph-file"></i>
                        <span class="media-title">{{ $fileName }}</span>
                    </div>
                </div>
            @endif
        </div>
    @endforeach
@endif
