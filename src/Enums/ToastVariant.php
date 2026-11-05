<?php

declare(strict_types=1);

namespace Odden\Ui\Enums;

enum ToastVariant: string
{
    case Success = 'success';
    case Info = 'info';
    case Warning = 'warning';
    case Danger = 'danger';
}
