<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Odden UI demo</title>
    <style>
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
    </style>
</head>
<body>
    <main>
        {{ $slot }}
    </main>
    <x-ui::toasts :duration="1500" />
</body>
</html>
