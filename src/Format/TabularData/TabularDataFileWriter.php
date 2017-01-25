<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\TabularData;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CultuurNet\UDB3\EventExport\FileWriterInterface;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfoServiceInterface;

class TabularDataFileWriter implements FileWriterInterface
{
    /**
     * @var string[]
     */
    protected $includedProperties;

    /**
     * @var TabularDataEventFormatter
     */
    protected $eventFormatter;

    /**
     * @var TabularDataFileWriterFactoryInterface
     */
    protected $tabularDataFileWriterFactory;

    public function __construct(
        TabularDataFileWriterFactoryInterface $tabularDataFileWriterFactory,
        $include,
        EventInfoServiceInterface $uitpas
    ) {
        $this->tabularDataFileWriterFactory = $tabularDataFileWriterFactory;
        $this->eventFormatter = new TabularDataEventFormatter($include, $uitpas);
    }

    /**
     * @param TabularDataFileWriterInterface $tabularDataFileWriter
     */
    protected function writeHeader(TabularDataFileWriterInterface $tabularDataFileWriter)
    {
        $headerRow = $this->eventFormatter->formatHeader();

        $tabularDataFileWriter->writeRow($headerRow);
    }

    /**
     * {@inheritdoc}
     */
    public function write($filePath, $events)
    {
        $tabularDataFileWriter = $this->openFileWriter($filePath);

        $this->writeHeader($tabularDataFileWriter);
        $this->writeEvents($tabularDataFileWriter, $events);

        $tabularDataFileWriter->close();
    }

    /**
     * @param TabularDataFileWriterInterface $tabularDataFileWriter
     * @param \Traversable $events
     */
    protected function writeEvents(
        TabularDataFileWriterInterface $tabularDataFileWriter,
        $events
    ) {
        foreach ($events as $event) {
            $eventRow = $this->eventFormatter->formatEvent($event);
            $tabularDataFileWriter->writeRow($eventRow);
        }
    }

    /**
     * @param string $filePath
     * @return TabularDataFileWriterInterface
     */
    protected function openFileWriter($filePath)
    {
        return $this->tabularDataFileWriterFactory->openTabularDataFileWriter(
            $filePath
        );
    }
}
