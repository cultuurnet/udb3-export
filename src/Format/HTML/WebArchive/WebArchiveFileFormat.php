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
     * @param string $brand
     * @param string $logo
     * @param string $title
     * @param string|null $subtitle
     * @param string|null $footer
     * @param string|null $publisher
     * @param string|null $partner
     * @param boolean $onMap
     */
    public function __construct(
        $brand,
        $logo,
        $title,
        $subtitle = null,
        $footer = null,
        $publisher = null,
        $partner = null,
        $onMap = false
    ) {
        $variables = [
            'brand' => $brand,
            'logo' => $logo,
            'title' => $title,
            'subtitle' => $subtitle,
            'footer' => $footer,
            'publisher' => $publisher,
            'partner' => !in_array($brand, array('uit', 'vlieg', 'uitpas', 'paspartoe'))
        ];
        if ($onMap) {
            $this->htmlFileWriter = new HTMLFileWriter('map-export.html.twig', $variables);
        } else {
            $this->htmlFileWriter = new HTMLFileWriter('export.html.twig', $variables);
        }
    }

    /**
     * @return HTMLFileWriter
     */
    public function getHTMLFileWriter()
    {
        return $this->htmlFileWriter;
    }
}
