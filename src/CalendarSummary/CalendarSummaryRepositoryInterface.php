<?php

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

use CultuurNet\UDB3\EventExport\CalendarSummary\SummaryGoneException;

interface CalendarSummaryRepositoryInterface
{
    /**
     * @param string $offerId
     * @param ContentType $type
     * @param Format $format
     *
     * @return string|null
     * @throws UnsupportedContentTypeException
     * @throws SummaryGoneException
     */
    public function get($offerId, ContentType $type, Format $format);
}
