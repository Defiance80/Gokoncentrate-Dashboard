@php
    /**
     * Every public page's metadata, in one place.
     *
     * $entertainment is the per-page SEO object the detail controllers build
     * (see MovieController::movieDetails and siblings - "seoData"). $seo is the
     * site-wide fallback row from Modules\SEO. Pages with neither still emit a
     * complete, valid set from the site settings, because a half-filled head is
     * worse than a plain one: crawlers cache what they first see.
     */
    $seo = Modules\SEO\Models\Seo::first();

    $gkTitle = $entertainment->meta_title
        ?? $seo->meta_title
        ?? (function_exists('GetSettingValue') ? GetSettingValue('app_name') : null)
        ?? config('app.name');

    $gkDescription = trim(strip_tags(html_entity_decode(
        $entertainment->short_description ?? $seo->short_description ?? ''
    )));
    // Search engines truncate past ~160 characters; cut on a word boundary so
    // the snippet never ends mid-word.
    if (mb_strlen($gkDescription) > 300) {
        $gkDescription = rtrim(mb_substr($gkDescription, 0, 297), " \t\n\r\0\x0B.,;:-") . '...';
    }

    $gkKeywordsRaw = $entertainment->meta_keywords ?? $seo->meta_keywords ?? '';
    $gkKeywords = is_array($decoded = json_decode($gkKeywordsRaw))
        ? implode(', ', $decoded)
        : $gkKeywordsRaw;

    // The stored value is usually a bare filename. og:image is ignored unless it
    // is an absolute URL, which is why no link preview has ever shown artwork.
    $gkImageRaw = $entertainment->seo_image ?? $seo->seo_image ?? null;
    $gkImage = !empty($gkImageRaw)
        ? setBaseUrlWithFileName($gkImageRaw, 'image', $entertainment->page_type ?? 'movie')
        : asset('img/logo/favicon.png');
    if (!filter_var($gkImage, FILTER_VALIDATE_URL)) {
        $gkImage = url($gkImage);
    }

    // An empty canonical is worse than none. Default to the current URL without
    // its query string.
    $gkCanonical = $entertainment->canonical_url ?? $seo->canonical_url ?? null;
    if (empty($gkCanonical)) {
        $gkCanonical = url(request()->getPathInfo());
    }

    $gkSiteName = function_exists('GetSettingValue')
        ? (GetSettingValue('app_name') ?: config('app.name'))
        : config('app.name');

    $gkOgType = $entertainment->og_type ?? (isset($entertainment->page_type) ? 'video.other' : 'website');
@endphp

{{-- Core --}}
<title id="pageTitle">{{ $gkTitle }}</title>
<meta name="title" id="dynamicMetaTitle" content="{{ $gkTitle }}">
<meta name="description" id="dynamicMetaDescription" content="{{ $gkDescription }}">
<meta name="keywords" id="dynamicMetaKeywords" content="{{ $gkKeywords }}">
<link rel="canonical" id="dynamicCanonicalUrl" href="{{ $gkCanonical }}">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
@if (!empty($entertainment->google_site_verification ?? $seo->google_site_verification ?? ''))
    <meta name="google-site-verification" id="dynamicGoogleVerification"
        content="{{ $entertainment->google_site_verification ?? $seo->google_site_verification }}">
@endif

{{-- Apple Smart App Banner. iOS Safari draws a native bar offering the app
     above the page, which is the shortest route from a search result into a
     download. Rendered only once a real App Store id is configured. --}}
@php
    $gkIosUrl = trim((string) (GetSettingValue('ios_url') ?? ''));
    $gkAppStoreId = null;
    if ($gkIosUrl !== '' && preg_match('~/id(\d+)~', $gkIosUrl, $m)) {
        $gkAppStoreId = $m[1];
    }
@endphp
@if ($gkAppStoreId)
    <meta name="apple-itunes-app"
        content="app-id={{ $gkAppStoreId }}, app-argument={{ $gkCanonical }}">
@endif
{{-- Open Graph: what Facebook, LinkedIn, WhatsApp, iMessage and Slack read --}}
<meta property="og:site_name" content="{{ $gkSiteName }}">
<meta property="og:type" content="{{ $gkOgType }}">
<meta property="og:url" content="{{ $gkCanonical }}">
<meta property="og:title" content="{{ $gkTitle }}">
<meta property="og:description" content="{{ $gkDescription }}">
<meta property="og:image" content="{{ $gkImage }}">
<meta property="og:image:secure_url" content="{{ $gkImage }}">
<meta property="og:image:alt" content="{{ $gkTitle }}">
<meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">
@if (!empty($entertainment->duration_seconds))
    <meta property="video:duration" content="{{ (int) $entertainment->duration_seconds }}">
@endif
@if (!empty($entertainment->release_date))
    <meta property="video:release_date" content="{{ $entertainment->release_date }}">
@endif

{{-- Twitter / X --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $gkTitle }}">
<meta name="twitter:description" content="{{ $gkDescription }}">
<meta name="twitter:image" content="{{ $gkImage }}">
<meta name="twitter:image:alt" content="{{ $gkTitle }}">

{{-- Structured data. Search engines and AI crawlers read this in preference to
     the prose, so it carries the facts: what the page is, who made it, how long
     it runs, where it can be watched. --}}
@php
    $gkLd = isset($entertainment->page_type)
        ? [
            '@context' => 'https://schema.org',
            '@type' => $entertainment->schema_type ?? 'VideoObject',
            'name' => $gkTitle,
            'description' => $gkDescription,
            'thumbnailUrl' => [$gkImage],
            'image' => $gkImage,
            'url' => $gkCanonical,
            'inLanguage' => app()->getLocale(),
            'isFamilyFriendly' => isset($entertainment->is_restricted)
                ? !$entertainment->is_restricted
                : null,
            'uploadDate' => $entertainment->release_date ?? null,
            'duration' => !empty($entertainment->duration_seconds)
                ? 'PT' . (int) $entertainment->duration_seconds . 'S'
                : null,
            'genre' => $entertainment->genres ?? null,
            'director' => !empty($entertainment->directors)
                ? array_map(fn($d) => ['@type' => 'Person', 'name' => $d], (array) $entertainment->directors)
                : null,
            'actor' => !empty($entertainment->actors)
                ? array_map(fn($a) => ['@type' => 'Person', 'name' => $a], (array) $entertainment->actors)
                : null,
            'publisher' => [
                '@type' => 'Organization',
                'name' => $gkSiteName,
                'url' => url('/'),
            ],
        ]
        : [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $gkSiteName,
            'url' => url('/'),
            'description' => $gkDescription,
        ];

    // Drop anything we could not fill. Schema.org treats a null as a claim that
    // the value is absent, which is not the same as not knowing it.
    $gkLd = array_filter($gkLd, fn($v) => $v !== null && $v !== '' && $v !== []);
@endphp
<script type="application/ld+json">{!! json_encode($gkLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
