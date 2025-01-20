<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>App</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>

<body>

<div id="app" class="layout has-sidebar fixed-sidebar fixed-header">
    <loading></loading>
    <popup></popup>
    <confirmation-popup></confirmation-popup>
    <x-layout.notification></x-layout.notification>
    <div class="layout">
        <div id="main-container" class="painel">
            {{$slot}}
        </div>
        <main class="content">
        </main>
    </div>
</div>
</body>

</html>
