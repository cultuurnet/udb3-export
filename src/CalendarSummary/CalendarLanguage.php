<?php

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

use ValueObjects\Enum\Enum;

/**
 * @method static Format DUTCH()
 * @method static Format FRENCH()
 */
class CalendarLanguage extends Enum
{
    const DUTCH = 'nl';
    const FRENCH = 'fr';
}
