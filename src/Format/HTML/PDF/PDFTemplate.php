<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\PDF;

use ValueObjects\Enum\Enum;

/**
 * @method static PDFTemplate TIPS()
 * @method static PDFTemplate MAP()
 */
final class PDFTemplate extends Enum
{
    const TIPS = 'tips';
    const MAP = 'map';
}
