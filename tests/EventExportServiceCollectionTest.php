<?php

namespace CultuurNet\UDB3\EventExport;

class EventExportServiceCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_can_add_an_event_service_export_collection(): void
    {
        $eventExportServiceCollection = new EventExportServiceCollection();

        $sapi2EventExportService = $this->createMock(EventExportServiceInterface::class);
        $sapi3EventExportService = $this->createMock(EventExportServiceInterface::class);

        $eventExportServiceCollection = $eventExportServiceCollection
            ->addService(
                new SapiVersion(SapiVersion::V2),
                $sapi2EventExportService
            )
            ->addService(
                new SapiVersion(SapiVersion::V3),
                $sapi3EventExportService
            );

        $this->assertEquals(
            $sapi2EventExportService,
            $eventExportServiceCollection->getService(
                new SapiVersion(SapiVersion::V2)
            )
        );
        $this->assertEquals(
            $sapi3EventExportService,
            $eventExportServiceCollection->getService(
                new SapiVersion(SapiVersion::V3)
            )
        );
    }
}
