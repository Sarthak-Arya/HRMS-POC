<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'FlipCore: Enterprise Payroll & HRMS for Indian Companies' }}</title>
    <meta name="description" content="{{ $description ?? 'Run payroll with confidence: attendance lock, salary structures, PF/ESI, loan EMIs and adjustments in one locked monthly run with payslips and salary sheets.' }}">
    <meta property="og:title" content="{{ $title ?? 'FlipCore: Enterprise Payroll & HRMS for Indian Companies' }}">
    <meta property="og:description" content="{{ $description ?? 'Run payroll with confidence: attendance lock, salary structures, PF/ESI, loan EMIs and adjustments in one locked monthly run with payslips and salary sheets.' }}">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/apple-icon.png') }}?v=2">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/img/favicon.svg') }}?v=2">
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/landing.css') }}" rel="stylesheet">
</head>
<body>
    {{ $slot }}
</body>
</html>
