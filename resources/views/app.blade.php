<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ config('site.primary_color', '#16a34a') }}">
    <meta name="application-name" content="{{ config('site.name', config('app.name', 'ShopEase')) }}">
    @if (config('site.meta_description'))
    <meta name="description" content="{{ config('site.meta_description') }}">
    @endif
    @if (config('site.favicon'))
    <link rel="icon" href="{{ config('site.favicon') }}">
    <link rel="apple-touch-icon" href="{{ config('site.favicon') }}">
    @else
    {{-- Nothing uploaded yet: the default ShopEase store icon (same as the default logo). --}}
    <link rel="icon" type="image/x-icon" href="/favicon.ico" sizes="16x16 32x32 48x48">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" sizes="any">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    @endif

    @php
        $siteName = config('site.name', config('app.name', 'ShopEase'));
        $seo = request()->attributes->get('seo') ?? [];
        $seoTitle = $seo['title'] ?? $siteName;
        $seoDesc = $seo['description'] ?? config('site.meta_description');
        $seoImage = $seo['image'] ?? config('site.logo');
        if ($seoImage && ! preg_match('#^https?://#i', $seoImage)) {
            $seoImage = url($seoImage);
        }
    @endphp
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($seoDesc)
    <meta property="og:description" content="{{ $seoDesc }}">
    @if (! config('site.meta_description'))
    <meta name="description" content="{{ $seoDesc }}">
    @endif
    @endif
    @if ($seoImage)
    <meta property="og:image" content="{{ $seoImage }}">
    @endif
    <meta name="twitter:card" content="{{ $seoImage ? 'summary_large_image' : 'summary' }}">

    <title inertia>{{ config('site.name', config('app.name', 'ShopEase')) }}</title>

    @if (config('site.palette_css'))
    <style id="site-palette">{!! config('site.palette_css') !!}</style>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>
