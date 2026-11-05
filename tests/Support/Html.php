<?php

declare(strict_types=1);

namespace Odden\Ui\Tests\Support;

use Dom\HTMLDocument;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

final class Html
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function render(string $template, array $data = []): HTMLDocument
    {
        return HTMLDocument::createFromString(
            '<body>'.Blade::render($template, $data).'</body>',
            LIBXML_NOERROR,
        );
    }

    /**
     * @param  array<string, string>  $messages
     */
    public static function errors(array $messages): ViewErrorBag
    {
        return (new ViewErrorBag)->put('default', new MessageBag(array_map(fn (string $message): array => [$message], $messages)));
    }
}
