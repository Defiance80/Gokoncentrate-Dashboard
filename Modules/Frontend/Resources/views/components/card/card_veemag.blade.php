{{-- VeeMag gallery card. Uses the same card structure as the other content
     cards so a VeeMag looks identical to a film or show on the surface;
     only the destination differs (the distinct issue experience). --}}
@foreach ($values as $value)
    <div class="slick-item">
        <div class="iq-card card-hover entainment-slick-card hover-card-container">
            <div class="block-images position-relative w-100">
                <a href="{{ route('veemag.detail', ['slug' => $value->slug]) }}"
                    class="position-absolute top-0 bottom-0 start-0 end-0 w-100 h-100"></a>
                <div class="image-box w-100 position-relative">
                    <img src="{{ $value->cover_url }}" alt="{{ $value->title }}"
                        class="img-fluid object-cover w-100 d-block border-0" loading="lazy">
                    <span class="product-new" style="background:var(--bs-primary)">VeeMag</span>
                </div>
            </div>
        </div>
    </div>
@endforeach
