<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Odden UI demo</title>
    <script type="importmap">{!! json_encode(['imports' => \Workbench\App\ImportMap::imports()], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    <script type="module">
        import { registerEditor } from '/__workbench/js/editor.js'

        document.addEventListener('alpine:init', () => registerEditor(window.Alpine))
    </script>
    <style>
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
        .fixed { position: fixed; }
        .relative { position: relative; }
        .inset-0 { inset: 0; }
        .inset-x-0 { left: 0; right: 0; }
        .bottom-0 { bottom: 0; }
        .z-50 { z-index: 50; }
        .z-60 { z-index: 60; }
        .pointer-events-none { pointer-events: none; }
        .pointer-events-auto { pointer-events: auto; }
        .flex { display: flex; }
        .flex-col { flex-direction: column; }
        .items-center { align-items: center; }
        .justify-center { justify-content: center; }
        .bg-white { background: white; }
        .bg-gray-950\/60 { background: rgba(0, 0, 0, 0.6); }
        {!! \Workbench\App\Theme::css() !!}
        body { background: #fff; color: #0f0f11; }
        figure button { background: transparent; border: 0; padding: 0; }
        .dark body { background: #0f0f11; color: #f4f4f2; }
        .fill-gray-600 { fill: #52525b; }
        .fill-gray-900 { fill: #0f0f11; }
        .stroke-gray-200 { stroke: #e4e4e7; }
        .stroke-gray-400 { stroke: #8b8b94; }
        .stroke-white { stroke: #fff; }
        .text-gray-600 { color: #52525b; }
        .text-gray-700 { color: #2a2a2e; }
        .text-gray-900 { color: #0f0f11; }
        .text-primary-700 { color: #5b21b6; }
        .text-emerald-700 { color: #047857; }
        .text-rose-700 { color: #be123c; }
        .dark .dark\:bg-gray-900 { background: #0f0f11; }
        .dark .dark\:text-emerald-400 { color: #34d399; }
        .dark .dark\:text-rose-400 { color: #fb7185; }
        .dark .dark\:fill-gray-100 { fill: #f4f4f2; }
        .dark .dark\:fill-gray-400 { fill: #8b8b94; }
        .dark .dark\:stroke-gray-500 { stroke: #62626b; }
        .dark .dark\:stroke-gray-800 { stroke: #232326; }
        .dark .dark\:stroke-gray-900 { stroke: #0f0f11; }
        .dark .dark\:text-gray-100 { color: #f4f4f2; }
        .dark .dark\:text-gray-300 { color: #d4d4d8; }
        .dark .dark\:text-gray-400 { color: #8b8b94; }
        .dark .dark\:text-primary-400 { color: #a78bfa; }
    </style>
</head>
<body>
    <main>
        {{ $slot }}
    </main>
    <x-ui::toasts :duration="1500" />
</body>
</html>
