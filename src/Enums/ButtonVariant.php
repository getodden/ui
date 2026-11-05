<?php

declare(strict_types=1);

namespace Odden\Ui\Enums;

enum ButtonVariant: string
{
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Outline = 'outline';
    case Ghost = 'ghost';
    case Danger = 'danger';
}
