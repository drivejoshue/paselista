
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>SchoolCore | {{ $dashboard['school']['name'] }}</title>

    @viteReactRefresh
    @vite('resources/js/schoolcore/main.tsx')
</head>
<body>
    <div id="schoolcore-root"></div>



<script id="schoolcore-bootstrap" type="application/json">
    {!! \Illuminate\Support\Js::encode([
        ...$dashboard,
        'navigation' => $navigation,
        'canManageFreeModules' => $canManageFreeModules,
        'initialPage' => $initialPage ?? 'dashboard',
        'academic' => $academic ?? null,
    ]) !!}
</script>


</body>
</html>
