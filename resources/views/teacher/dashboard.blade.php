<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Portal docente · SchoolCore Academic</title>

    @viteReactRefresh
    @vite('resources/js/teacher/main.tsx')
</head>
<body>
    <div id="teacher-root"></div>

    <script id="teacher-bootstrap" type="application/json">{!!
        json_encode(
            array_merge($bootstrap, [
                'csrf' => csrf_token(),
                'flash' => session('success') ?? session('status'),
                'errors' => $errors->all(),
            ]),
            JSON_HEX_TAG |
            JSON_HEX_AMP |
            JSON_HEX_APOS |
            JSON_HEX_QUOT |
            JSON_UNESCAPED_UNICODE |
            JSON_THROW_ON_ERROR
        )
    !!}</script>
</body>
</html>
