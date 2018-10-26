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
     */
    public function addService(
        SapiVersion $sapiVersion,
        EventExportServiceInterface $eventExportService
    ): void {
        $this->eventExportServices[$sapiVersion->toNative()] = $eventExportService;
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
