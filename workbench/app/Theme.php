<?php

declare(strict_types=1);

namespace Workbench\App;

final class Theme
{
    public static function css(): string
    {
        return (string) file_get_contents(__DIR__.'/../../resources/css/theme.css');
    }
}
