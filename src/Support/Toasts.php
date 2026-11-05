<?php

declare(strict_types=1);

namespace Odden\Ui\Support;

use Odden\Ui\Enums\ToastVariant;

final class Toasts
{
    private const string SessionKey = 'ui.toasts';

    public static function flash(string $message, ToastVariant $variant): void
    {
        session()->flash(self::SessionKey, [
            ...self::flashed(),
            ['message' => $message, 'variant' => $variant->value],
        ]);
    }

    /**
     * @return list<array{message: string, variant: string}>
     */
    public static function flashed(): array
    {
        /** @var list<array{message: string, variant: string}> */
        return array_values(session()->get(self::SessionKey, []));
    }
}
