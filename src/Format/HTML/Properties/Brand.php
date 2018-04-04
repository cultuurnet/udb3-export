<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\HTML\Properties;

use ValueObjects\StringLiteral\StringLiteral;

class Brand extends StringLiteral
{
    public function __construct($brand)
    {
        parent::__construct($brand);
    }
}
