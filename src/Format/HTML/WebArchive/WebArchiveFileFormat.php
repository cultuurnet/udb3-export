<?php

namespace CultuurNet\UDB3\EventExport\Format\HTML\WebArchive;

use CultuurNet\UDB3\EventExport\Format\HTML\HTMLFileWriter;

abstract class WebArchiveFileFormat
{
    /**
     * @var HTMLFileWriter
     */
    protected $htmlFileWriter;

    /**
     * @param WebArchiveTemplate $template
     * @param string $brand
     * @param string $logo
     * @param string $title
     * @param string|null $subtitle
     * @param string|null $footer
     * @param string|null $publisher
     * @param string|null $partner
     */
    public function __construct(
        WebArchiveTemplate $template,
        $brand,
        $logo,
        $title,
        $subtitle = null,
        $footer = null,
        $publisher = null,
        $partner = null
    ) {
        $variables = [
            'brand' => $brand,
            'logo' => $logo,
            'title' => $title,
            'subtitle' => $subtitle,
            'footer' => $footer,
            'publisher' => $publisher,
            'partner' => !in_array($brand, array('uit', 'vlieg', 'uitpas', 'paspartoe')),
            'showMap' => $template->sameValueAs(WebArchiveTemplate::MAP()),
        ];
        $this->htmlFileWriter = new HTMLFileWriter('export.html.twig', $variables);
    }

    /**
     * @return HTMLFileWriter
     */
    public function getHTMLFileWriter()
    {
        return $this->htmlFileWriter;
    }
}
