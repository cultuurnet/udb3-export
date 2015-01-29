<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport;


use Psr\Log\LoggerInterface;

interface EventExportServiceInterface
{
    /**
     * @param EventExportQuery $query
     * @param LoggerInterface $logger
     * @return string
     *   Publicly accessible path of the file
     */
    public function exportEventsAsJsonLD(EventExportQuery $query, LoggerInterface $logger = NULL);
}
