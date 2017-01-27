<?php

namespace CultuurNet\UDB3\EventExport\Media;

use stdClass;

class Url implements MediaSpecificationInterface
{
    /**
     * @var string
     */
    private $url;

    /**
     * Url constructor.
     * @param string $url
     */
    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * @param stdClass $mediaObject
     * @return bool
     */
    public function matches($mediaObject)
    {
        return $mediaObject->contentUrl === $this->url;
    }
}
