{{--
    Shown to signed-out visitors on content detail pages, which is where a link
    shared from the mobile app lands. Without this a share offered a stranger
    nothing but a sign-in form. Store URLs come from the same two settings the
    footer already uses (ios_url / android_url), so there is nothing new to
    configure; the bar simply hides itself if neither is set.
--}}
@guest
    @php
        $appStoreUrl = GetSettingValue('ios_url');
        $playStoreUrl = GetSettingValue('android_url');

        // android_url still carries the template vendor's own demo listing
        // (com.iqonic.streamitlaravel), which would send a visitor to someone
        // else's app. A wrong store link is worse than none, so drop it. This
        // guard stops applying by itself once the setting holds a real
        // GoKoncentrate listing.
        if (!empty($playStoreUrl) && str_contains($playStoreUrl, 'com.iqonic.')) {
            $playStoreUrl = null;
        }
    @endphp

    @if (!empty($appStoreUrl) || !empty($playStoreUrl))
        <div class="get-the-app-bar bg-body-tertiary border-bottom">
            <div class="container py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-between gap-3">
                    <div class="text-center text-md-start">
                        <div class="fw-bold mb-0">Watch this on GoKoncentrate</div>
                        <small class="text-muted">Get the free app to stream it on your phone or tablet.</small>
                    </div>
                    <ul class="list-unstyled d-flex align-items-center gap-2 mb-0">
                        @if (!empty($appStoreUrl))
                            <li>
                                <a href="{{ $appStoreUrl }}" class="btn btn-link p-0" target="_blank" rel="noopener"
                                    aria-label="Download on the App Store">
                                    <img src="{{ asset('img/web-img/app_store.png') }}" alt="Download on the App Store"
                                        class="img-fluid" style="max-height:44px">
                                </a>
                            </li>
                        @endif
                        @if (!empty($playStoreUrl))
                            <li>
                                <a href="{{ $playStoreUrl }}" class="btn btn-link p-0" target="_blank" rel="noopener"
                                    aria-label="Get it on Google Play">
                                    <img src="{{ asset('img/web-img/play_store.png') }}" alt="Get it on Google Play"
                                        class="img-fluid" style="max-height:44px">
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    @endif
@endguest
