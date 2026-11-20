@php
    /**
     * "Get the app" prompt, shown once per visitor before they carry on.
     *
     * Deliberately renders nothing unless there is a real store listing to send
     * people to. The template this site is built from ships with the vendor's
     * own demo app (com.iqonic.*) in android_url, and a prompt that sends our
     * visitors to a stranger's app is worse than no prompt at all - the same
     * guard already used in get_the_app, header and getFooterData.
     */
    $gkIos = trim((string) (GetSettingValue('ios_url') ?? ''));
    $gkAndroid = trim((string) (GetSettingValue('android_url') ?? ''));

    if ($gkAndroid !== '' && str_contains($gkAndroid, 'com.iqonic.')) {
        $gkAndroid = '';
    }
    if ($gkIos !== '' && !str_contains($gkIos, 'apps.apple.com')) {
        $gkIos = '';
    }

    $gkHasStore = $gkIos !== '' || $gkAndroid !== '';
    $gkAppName = GetSettingValue('app_name') ?: config('app.name');
    $gkLogo = GetSettingValue('logo') ?: asset('img/logo/favicon.png');
@endphp

@if ($gkHasStore)
    <div class="gk-app-prompt" id="gkAppPrompt" role="dialog" aria-modal="true"
        aria-labelledby="gkAppPromptTitle" aria-describedby="gkAppPromptText" hidden>
        <div class="gk-app-prompt__backdrop" data-gk-dismiss></div>

        <div class="gk-app-prompt__panel" role="document">
            <button type="button" class="gk-app-prompt__close" data-gk-dismiss
                aria-label="Close">&times;</button>

            <img src="{{ $gkLogo }}" alt="" class="gk-app-prompt__logo" width="72" height="72">

            <h2 class="gk-app-prompt__title" id="gkAppPromptTitle">
                Watch {{ $gkAppName }} in the app
            </h2>

            <p class="gk-app-prompt__text" id="gkAppPromptText">
                Downloads for offline viewing, picture-in-picture and Chromecast -
                none of which the browser can do. It is free, and your account
                carries straight over.
            </p>

            <div class="gk-app-prompt__actions">
                @if ($gkIos !== '')
                    <a class="gk-app-prompt__store" href="{{ $gkIos }}" rel="noopener"
                        data-gk-platform="ios">Download on the App Store</a>
                @endif
                @if ($gkAndroid !== '')
                    <a class="gk-app-prompt__store" href="{{ $gkAndroid }}" rel="noopener"
                        data-gk-platform="android">Get it on Google Play</a>
                @endif
            </div>

            <button type="button" class="gk-app-prompt__continue" data-gk-dismiss>
                Continue in the browser
            </button>
        </div>
    </div>

    <style>
        .gk-app-prompt { position: fixed; inset: 0; z-index: 2000; display: flex;
            align-items: center; justify-content: center; padding: 16px; }
        .gk-app-prompt[hidden] { display: none; }
        .gk-app-prompt__backdrop { position: absolute; inset: 0;
            background: rgba(0, 0, 0, .72); backdrop-filter: blur(4px); }
        .gk-app-prompt__panel { position: relative; width: 100%; max-width: 420px;
            background: #14161b; color: #fff; border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 16px; padding: 32px 24px 24px; text-align: center;
            box-shadow: 0 24px 64px rgba(0, 0, 0, .55);
            animation: gkAppPromptIn .22s ease-out both; }
        @keyframes gkAppPromptIn { from { opacity: 0; transform: translateY(12px) scale(.98); } }
        @media (prefers-reduced-motion: reduce) {
            .gk-app-prompt__panel { animation: none; }
        }
        .gk-app-prompt__close { position: absolute; top: 8px; right: 12px; background: none;
            border: 0; color: rgba(255, 255, 255, .6); font-size: 28px; line-height: 1;
            cursor: pointer; padding: 4px 8px; }
        .gk-app-prompt__close:hover, .gk-app-prompt__close:focus-visible { color: #fff; }
        .gk-app-prompt__logo { border-radius: 16px; object-fit: cover; margin-bottom: 16px; }
        .gk-app-prompt__title { font-size: 1.3rem; font-weight: 700; margin: 0 0 8px; }
        .gk-app-prompt__text { font-size: .92rem; line-height: 1.5;
            color: rgba(255, 255, 255, .72); margin: 0 0 20px; }
        .gk-app-prompt__actions { display: flex; flex-direction: column; gap: 10px; }
        .gk-app-prompt__store { display: block; padding: 13px 16px; border-radius: 10px;
            background: var(--bs-primary, #e87b23); color: #fff; font-weight: 600;
            text-decoration: none; }
        .gk-app-prompt__store:hover, .gk-app-prompt__store:focus-visible {
            filter: brightness(1.08); color: #fff; }
        .gk-app-prompt__continue { margin-top: 16px; background: none; border: 0;
            color: rgba(255, 255, 255, .55); font-size: .88rem; text-decoration: underline;
            cursor: pointer; }
        .gk-app-prompt__continue:hover, .gk-app-prompt__continue:focus-visible { color: #fff; }
    </style>

    <script>
        (function () {
            var KEY = 'gk_app_prompt_dismissed_at';
            var QUIET_DAYS = 7;
            var el = document.getElementById('gkAppPrompt');
            if (!el) return;

            // Never interrupt a crawler. It also keeps the prompt out of the
            // rendered page search engines judge, which is the behaviour
            // Google's intrusive-interstitial guidance asks for.
            if (/bot|crawl|spider|slurp|facebookexternalhit|embedly|preview|gptbot|claudebot|perplexity/i
                    .test(navigator.userAgent)) {
                return;
            }

            var dismissedAt = 0;
            try { dismissedAt = parseInt(localStorage.getItem(KEY) || '0', 10) || 0; } catch (e) {}
            if (Date.now() - dismissedAt < QUIET_DAYS * 864e5) return;

            var lastFocus = document.activeElement;

            function dismiss() {
                el.hidden = true;
                document.body.style.overflow = '';
                try { localStorage.setItem(KEY, String(Date.now())); } catch (e) {}
                document.removeEventListener('keydown', onKey);
                if (lastFocus && lastFocus.focus) lastFocus.focus();
            }

            function onKey(e) {
                if (e.key === 'Escape') { dismiss(); return; }
                if (e.key !== 'Tab') return;
                // Keep focus inside the dialog while it is open.
                var f = el.querySelectorAll('a[href], button:not([disabled])');
                if (!f.length) return;
                var first = f[0], last = f[f.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            }

            // Tapping a store link is a decision too - do not ask again.
            el.querySelectorAll('[data-gk-platform]').forEach(function (a) {
                a.addEventListener('click', function () {
                    try { localStorage.setItem(KEY, String(Date.now())); } catch (e) {}
                });
            });

            el.querySelectorAll('[data-gk-dismiss]').forEach(function (n) {
                n.addEventListener('click', dismiss);
            });

            el.hidden = false;
            document.body.style.overflow = 'hidden';
            document.addEventListener('keydown', onKey);
            var firstFocusable = el.querySelector('a[href], button');
            if (firstFocusable) firstFocusable.focus();
        })();
    </script>
@endif
