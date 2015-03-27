<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\Deserializer\MissingValueException;
use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\EventExport\Format\HTML\Brand;
use CultuurNet\UDB3\EventExport\Format\HTML\Footer;
use CultuurNet\UDB3\EventExport\Format\HTML\Publisher;
use CultuurNet\UDB3\EventExport\Format\HTML\Subtitle;
use CultuurNet\UDB3\EventExport\Format\HTML\Title;
use ValueObjects\String\String;
use ValueObjects\Web\EmailAddress;

class ExportEventsAsPDFJSONDeserializer extends JSONDeserializer
{
    /**
     * @param String $data
     * @return ExportEventsAsPDF
     */
    public function deserialize(String $data)
    {
        $json = parent::deserialize($data);

        if (!isset($json->query)) {
            throw new MissingValueException('query is missing');
        }

        $query = new EventExportQuery($json->query);

        if (!isset($json->customizations)) {
            throw new MissingValueException('customizations is missing');
        }

        if (!is_object($json->customizations)) {
            throw new \InvalidArgumentException(
                'customizations should be an object'
            );
        }

        $customizations = $json->customizations;

        if (!isset($customizations->brand)) {
            throw new MissingValueException('brand is missing');
        }

        $brand = new Brand($customizations->brand);

        if (!isset($customizations->title)) {
            throw new MissingValueException('title is missing');
        }

        $title = new Title($customizations->title);

        $command = new ExportEventsAsPDF(
            $query,
            $brand,
            $title
        );

        if (isset($json->email)) {
            $emailAddress = new EmailAddress($json->email);
            $command = $command->withEmailNotificationTo($emailAddress);
        }

        if (isset($json->selection)) {
            $command = $command->withSelection($json->selection);
        }

        if (isset($customizations->subtitle)) {
            $command = $command->withSubtitle(
                new Subtitle($customizations->subtitle)
            );
        }

        if (isset($customizations->footer)) {
            $command = $command->withFooter(
                new Footer($customizations->footer)
            );
        }

        if (isset($customizations->publisher)) {
            $command = $command->withPublisher(
                new Publisher($customizations->publisher)
            );
        }

        return $command;
    }
}