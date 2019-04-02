<?php

namespace CultuurNet\UDB3\EventExport\Format\HTML;

class HTMLFileWriterOnMap extends HTMLFileWriter
{
    /**
     * @param $events
     * @return string
     */
    private function getHTML($events)
    {
        $variables = $this->variables;

        $variables['events'] = $events['events'];
        //$variables['locations'] = $events['locations'];

        return $this->twig->render($this->template, $variables);
    }
}
