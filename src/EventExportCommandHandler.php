<?php

namespace CultuurNet\UDB3\EventExport;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsCSV;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsJsonLD;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsOOXML;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsPDF;
use CultuurNet\UDB3\EventExport\Format\HTML\PDF\PDFWebArchiveFileFormat;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfoServiceInterface;
use CultuurNet\UDB3\EventExport\Format\JSONLD\JSONLDFileFormat;
use CultuurNet\UDB3\EventExport\Format\TabularData\CSV\CSVFileFormat;
use CultuurNet\UDB3\EventExport\Format\TabularData\OOXML\OOXMLFileFormat;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class EventExportCommandHandler extends CommandHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var EventExportServiceCollection
     */
    protected $eventExportServiceCollection;

    /**
     * @var string
     */
    protected $princeXMLBinaryPath;

    /**
     * @var EventInfoServiceInterface|null
     */
    protected $uitpas;

    /**
     * @var CalendarSummaryRepositoryInterface
     */
    protected $calendarSummaryRepository;

    /**
     * @var string
     */
    protected $gmapApiKey;

    /**
     * @param EventExportServiceCollection $eventExportServiceCollection
     * @param string $princeXMLBinaryPath
     * @param EventInfoServiceInterface|null $uitpas
     * @param CalendarSummaryRepositoryInterface $calendarSummaryRepository
     * @param string|null $gmapApiKey
     */
    public function __construct(
        EventExportServiceCollection $eventExportServiceCollection,
        $princeXMLBinaryPath,
        EventInfoServiceInterface $uitpas = null,
        CalendarSummaryRepositoryInterface $calendarSummaryRepository = null,
        $gmapApiKey = null
    ) {
        $this->eventExportServiceCollection = $eventExportServiceCollection;
        $this->princeXMLBinaryPath = $princeXMLBinaryPath;
        $this->uitpas = $uitpas;
        $this->calendarSummaryRepository = $calendarSummaryRepository;
        $this->gmapApiKey = $gmapApiKey;
    }

    /**
     * @param ExportEventsAsJsonLD $exportCommand
     */
    public function handleExportEventsAsJsonLD(
        ExportEventsAsJsonLD $exportCommand
    ): void {
        $this->eventExportServiceCollection->delegateToServiceWithAppropriateSapiVersion(
            new JSONLDFileFormat($exportCommand->getInclude()),
            $exportCommand,
            $this->logger
        );
    }

    /**
     * @param ExportEventsAsCSV $exportCommand
     */
    public function handleExportEventsAsCSV(
        ExportEventsAsCSV $exportCommand
    ): void {
        $this->eventExportServiceCollection->delegateToServiceWithAppropriateSapiVersion(
            new CSVFileFormat($exportCommand->getInclude()),
            $exportCommand,
            $this->logger
        );
    }

    /**
     * @param ExportEventsAsOOXML $exportCommand
     */
    public function handleExportEventsAsOOXML(
        ExportEventsAsOOXML $exportCommand
    ): void {
        $this->eventExportServiceCollection->delegateToServiceWithAppropriateSapiVersion(
            new OOXMLFileFormat(
                $exportCommand->getInclude(),
                $this->uitpas,
                $this->calendarSummaryRepository
            ),
            $exportCommand,
            $this->logger
        );
    }

    /**
     * @param ExportEventsAsPDF $exportEvents
     */
    public function handleExportEventsAsPDF(
        ExportEventsAsPDF $exportEvents
    ): void {
        $fileFormat = new PDFWebArchiveFileFormat(
            $this->princeXMLBinaryPath,
            $exportEvents->getBrand(),
            $exportEvents->getLogo(),
            $exportEvents->getTitle(),
            $exportEvents->getSubtitle(),
            $exportEvents->getFooter(),
            $exportEvents->getPublisher(),
            $exportEvents->getOnMap(),
            $this->uitpas,
            $this->calendarSummaryRepository,
            $this->gmapApiKey
        );

        $this->eventExportServiceCollection->delegateToServiceWithAppropriateSapiVersion(
            $fileFormat,
            $exportEvents,
            $this->logger
        );
    }
}
