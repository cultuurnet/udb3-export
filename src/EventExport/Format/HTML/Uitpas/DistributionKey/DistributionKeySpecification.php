<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\DistributionKey;

interface DistributionKeySpecification
{
    public function isSatisfiedBy(\CultureFeed_Uitpas_DistributionKey $distributionKey);
}
