<?php

namespace CultuurNet\UDB3\EventExport;

class EventExportServiceCollection
{
    /**
     * @var EventExportServiceInterface[]
     */
    private $eventExportServices;

    /**
     * @param SapiVersion $sapiVersion
     * @param EventExportServiceInterface $eventExportService
     * @return EventExportServiceCollection
     */
    public function addService(
        SapiVersion $sapiVersion,
        EventExportServiceInterface $eventExportService
    ): EventExportServiceCollection {
        $c = clone $this;
        $c->eventExportServices[$sapiVersion->toNative()] = $eventExportService;
        return $c;
    }

    /**
     * @param SapiVersion $sapiVersion
     * @return EventExportServiceInterface|null
     */
    public function getService(SapiVersion $sapiVersion): ?EventExportServiceInterface
    {
        if (!isset($this->eventExportServices[$sapiVersion->toNative()])) {
            return null;
        }

        return $this->eventExportServices[$sapiVersion->toNative()];
    }
}
