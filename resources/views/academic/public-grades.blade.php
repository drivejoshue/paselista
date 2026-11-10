<!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Consulta de calificaciones · {{ $schoolName }}</title>
    @vite('resources/js/grade-portal/main.tsx')
</head>
<body style="margin:0; background:#f1f5f9">

<div
    id="grade-portal-root"
    data-school-name="{{ $schoolName }}"
    data-school-slug="{{ $schoolSlug }}"
    data-school-logo="{{ $schoolLogoUrl ?? '' }}"
    data-school-primary="{{ $schoolPrimary ?? '#2563eb' }}"
    data-school-secondary="{{ $schoolSecondary ?? '#0f172a' }}"
    data-grades-enabled="{{ !empty($gradesEnabled) ? '1' : '0' }}"
></div>

</body>
</html>
