@php
    /**
     * Signed-out backdrop: a wall of real catalogue artwork behind the auth
     * card, dimmed so the form stays readable. Falls back to the static banner
     * when the catalogue is empty or unreachable.
     */
    $posters = app(\Modules\Frontend\Services\AuthBackdropService::class)->posters(28);
    $fallback = asset('/dummy-images/login_banner.jpg');
@endphp

<div class="gk-auth-backdrop" aria-hidden="true">
    @if (count($posters) > 0)
        <div class="gk-auth-backdrop__wall">
            {{-- Repeated so the wall fills tall screens without a short catalogue looking sparse. --}}
            @foreach (array_merge($posters, $posters) as $poster)
                <div class="gk-auth-backdrop__tile">
                    <img src="{{ $poster }}" alt="" loading="lazy" decoding="async">
                </div>
            @endforeach
        </div>
    @else
        <div class="gk-auth-backdrop__wall gk-auth-backdrop__wall--fallback"
             style="background-image: url('{{ $fallback }}');"></div>
    @endif

    <div class="gk-auth-backdrop__scrim"></div>
</div>

@once
    @push('styles')
        <style>
            .gk-auth-backdrop {
                pointer-events: none;
                position: fixed;
                inset: 0;
                z-index: 0;
                overflow: hidden;
                background-color: #0b0b0f;
            }

            .gk-auth-backdrop__wall {
                position: absolute;
                /* Overscan so the blur never reveals a soft edge. */
                inset: -4%;
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
                gap: 8px;
                /* Tilted like the Netflix title wall. */
                transform: rotate(-4deg) scale(1.18);
                filter: blur(2px) saturate(0.9);
                opacity: 0.55;
            }

            .gk-auth-backdrop__wall--fallback {
                display: block;
                background-size: cover;
                background-position: center;
                transform: none;
                filter: none;
                opacity: 0.6;
            }

            .gk-auth-backdrop__tile {
                aspect-ratio: 2 / 3;
                overflow: hidden;
                border-radius: 6px;
                background-color: #16161c;
            }

            .gk-auth-backdrop__tile img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }

            /* Darkens the edges and the centre so the card always has contrast. */
            .gk-auth-backdrop__scrim {
                position: absolute;
                inset: 0;
                background:
                    radial-gradient(ellipse at center, rgba(8, 8, 12, 0.72) 0%, rgba(8, 8, 12, 0.88) 45%, rgba(6, 6, 9, 0.96) 100%),
                    linear-gradient(to bottom, rgba(6, 6, 9, 0.9) 0%, rgba(6, 6, 9, 0.55) 35%, rgba(6, 6, 9, 0.92) 100%);
            }

            /* The auth card and its contents sit above the backdrop. */
            .gk-auth-stage {
                position: relative;
                z-index: 1;
                min-height: 100vh;
                overflow-y: auto;
            }

            @media (max-width: 767.98px) {
                .gk-auth-backdrop__wall {
                    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                    transform: rotate(-4deg) scale(1.3);
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .gk-auth-backdrop__wall {
                    transform: none;
                }
            }
        </style>
    @endpush
@endonce
