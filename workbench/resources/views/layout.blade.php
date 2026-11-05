<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Odden UI demo</title>
</head>
<body>
    <main>
        {{ $slot }}
    </main>
    <x-ui::toasts :duration="1500" />
</body>
</html>
