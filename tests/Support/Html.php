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
        $document = HTMLDocument::createFromString(
            '<body>'.Blade::render($template, $data).'</body>',
            LIBXML_NOERROR,
        );

        while (($teleport = $document->querySelector('template[x-teleport]')) !== null) {
            $teleport->outerHTML = $teleport->innerHTML;
        }

        return $document;
    }

    public static function parse(string $html): HTMLDocument
    {
        return HTMLDocument::createFromString('<body>'.$html.'</body>', LIBXML_NOERROR);
    }

    /**
     * @param  array<string, string>  $messages
     */
    public static function errors(array $messages): ViewErrorBag
    {
        return (new ViewErrorBag)->put('default', new MessageBag(array_map(fn (string $message): array => [$message], $messages)));
    }
}
