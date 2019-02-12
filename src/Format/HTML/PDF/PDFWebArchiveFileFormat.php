<?php

namespace CultuurNet\UDB3\EventExport\Format\HTML\PDF;

use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\FileFormatInterface;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfoServiceInterface;
use CultuurNet\UDB3\EventExport\Format\HTML\WebArchive\WebArchiveFileFormat;

class PDFWebArchiveFileFormat extends WebArchiveFileFormat implements FileFormatInterface
{
    /**
     * @var string
     */
    protected $princeXMLBinaryPath;

    /**
     * @var EventInfoServiceInterface
     */
    protected $uitpas;

    /**
     * @var CalendarSummaryRepositoryInterface
     */
    protected $calendarSummaryRepository;

    /**
     * @param string $princeXMLBinaryPath
     * @param string $brand
     * @param string $logo
     * @param string $title
     * @param string|null $subTitle
     * @param string|null $footer
     * @param string|null $publisher
     * @param boolean $onMap
     * @param EventInfoServiceInterface|null $uitpas
     * @param CalendarSummaryRepositoryInterface|null $calendarSummaryRepository
     */
    public function __construct(
        $princeXMLBinaryPath,
        $brand,
        $logo,
        $title,
        $subTitle = null,
        $footer = null,
        $publisher = null,
        $onMap = false,
        EventInfoServiceInterface $uitpas = null,
        CalendarSummaryRepositoryInterface $calendarSummaryRepository = null
    ) {
        parent::__construct($brand, $logo, $title, $subTitle, $footer, $publisher, $onMap);
        $this->princeXMLBinaryPath = $princeXMLBinaryPath;
        $this->uitpas = $uitpas;
        $this->calendarSummaryRepository = $calendarSummaryRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getFileNameExtension()
    {
        return 'pdf';
    }

    /**
     * {@inheritdoc}
     */
    public function getWriter()
    {
        return new PDFWebArchiveFileWriter(
            $this->princeXMLBinaryPath,
            $this->getHTMLFileWriter(),
            $this->uitpas,
            $this->calendarSummaryRepository
        );
    }
}
