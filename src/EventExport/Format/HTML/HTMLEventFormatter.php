<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\HTML;

use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\EventSpecificationInterface;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\Has1Taalicoon;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\Has2Taaliconen;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\Has3Taaliconen;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\Has4Taaliconen;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\HasUiTPASBrand;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications\HasVliegBrand;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\TaalicoonDescription;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event\EventAdvantage;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfoServiceInterface;
use CultuurNet\UDB3\EventExport\PriceFormatter;
use CultuurNet\UDB3\StringFilter\CombinedStringFilter;
use CultuurNet\UDB3\StringFilter\StripHtmlStringFilter;
use CultuurNet\UDB3\StringFilter\TruncateStringFilter;
use stdClass;
use ValueObjects\String\String;

class HTMLEventFormatter
{
    /**
     * @var CombinedStringFilter
     */
    protected $filters;

    /**
     * @var EventSpecificationInterface[]
     */
    protected $taalicoonSpecs;

    /**
     * @var EventSpecificationInterface[]
     */
    protected $brandSpecs;

    /**
     * @var EventInfoServiceInterface|null
     */
    protected $uitpas;

    /**
     * @var PriceFormatter
     */
    protected $priceFormatter;

    /**
     * @param EventInfoServiceInterface|null $uitpas
     */
    public function __construct(EventInfoServiceInterface $uitpas = null)
    {
        $this->uitpas = $uitpas;

        $this->priceFormatter = new PriceFormatter(2, ',', '.', 'Gratis');

        $this->filters = new CombinedStringFilter();

        $this->filters->addFilter(new StripHtmlStringFilter());

        $truncateFilter = new TruncateStringFilter(300);
        $truncateFilter->addEllipsis();
        $truncateFilter->turnOnWordSafe(1);
        $this->filters->addFilter($truncateFilter);

        $this->taalicoonSpecs = array(
            'EEN_TAALICOON' => new Has1Taalicoon(),
            'TWEE_TAALICONEN' => new Has2Taaliconen(),
            'DRIE_TAALICONEN' => new Has3Taaliconen(),
            'VIER_TAALICONEN' => new Has4Taaliconen()
        );

        $this->brandSpecs = array(
            'uitpas' => new HasUiTPASBrand(),
            'vlieg' => new HasVliegBrand()
        );
    }

    /**
     * @param string $eventString
     *   The cultural event encoded as JSON-LD
     *
     * @return array
     *   The event as an array suitable for rendering with HTMLFileWriter
     */
    public function formatEvent($eventString)
    {
        $event = json_decode($eventString);

        $formattedEvent = [];

        if (isset($event->image)) {
            $formattedEvent['image'] = 'http:' . $event->image;
        }

        $type = EventType::fromJSONLDEvent($eventString);
        $formattedEvent['type'] = $type->getLabel();

        $formattedEvent['title'] = reset($event->name);
        $formattedEvent['description'] = $this->filters->filter(
            reset($event->description)
        );

        $formattedEvent['address'] = [
            'name' => $event->location->name,
            'street' => $event->location->address->streetAddress,
            'postcode' => $event->location->address->postalCode,
            'municipality' => $event->location->address->addressLocality,
        ];

        if (isset($event->bookingInfo)) {
            $firstPrice = reset($event->bookingInfo);
            $formattedEvent['price'] = $this->priceFormatter->format($firstPrice->price);
        } else {
            $formattedEvent['price'] = 'Niet ingevoerd';
        }

        $formattedEvent['calendarType'] = $event->calendarType;
        if (isset($event->startDate)) {
            $formattedEvent['startDate'] = new \DateTime($event->startDate);
        }
        if (isset($event->endDate)) {
            $formattedEvent['endDate'] = new \DateTime($event->endDate);
        }

        $this->addUitpasInfo($event, $formattedEvent);

        $this->formatTaaliconen($event, $formattedEvent);

        $formattedEvent['brands'] = $this->getBrands($event);

        if ($formattedEvent['uitpas'] && !in_array('uitpas', array_keys($formattedEvent['brands']))) {
            var_dump($event);
        }

        if (isset($event->typicalAgeRange)) {
            $ageRange = $event->typicalAgeRange;
            $formattedEvent['ageFrom'] = explode('-', $ageRange)[0];
        }

        return $formattedEvent;
    }

    /**
     * @param stdClass $event
     * @param array $formattedEvent
     */
    private function addUitpasInfo(stdClass $event, array &$formattedEvent)
    {
        if ($this->uitpas) {
            $urlParts = explode('/', $event->{'@id'});
            $eventId = end($urlParts);
            $uitpasInfo = $this->uitpas->getEventInfo($eventId);
            if ($uitpasInfo) {
                // Format prices.
                $prices = $uitpasInfo->getPrices();
                foreach ($prices as &$price) {
                    $price['price'] = $this->priceFormatter->format($price['price']);
                }

                // Format advantage labels. Start from a list of all known
                // advantage labels, and filter out the ones that don't apply.
                // Otherwise the order could get mixed up.
                $advantages = $uitpasInfo->getAdvantages();
                $advantageLabels = [
                    EventAdvantage::POINT_COLLECTING => 'Spaar punten',
                    EventAdvantage::KANSENTARIEF => 'Korting voor kansentarief',
                ];
                foreach ($advantageLabels as $advantage => $advantageLabel) {
                    if (!in_array($advantage, $advantages)) {
                        unset($advantageLabels[$advantage]);
                    }
                }
                $advantages = array_values($advantageLabels);

                // Add all uitpas info to the event.
                $formattedEvent['uitpas'] = [
                    'prices' => $prices,
                    'advantages' => $advantages,
                ];
            }
        }
    }

    /**
     * @param $event
     * @param $formattedEvent
     */
    private function formatTaaliconen($event, &$formattedEvent)
    {
        $taalicoonCount = 0;
        $description = '';
        $i = 0;
        $satisfiedCount = 0;

        foreach ($this->taalicoonSpecs as $name => $spec) {
            $i++;
            /** @var EventSpecificationInterface $spec */
            if ($spec->isSatisfiedBy($event)) {
                $satisfiedCount++;
                $taalicoonCount = $i;
                $description = TaalicoonDescription::getByName($name)->getValue();
            }
        }

        // Only add the taalicoonCount if the event was tagged with a single "taaliconen" tag. If multiple tags were
        // added, simply ignore the taaliconen.
        if ($taalicoonCount > 0 && $satisfiedCount == 1) {
            $formattedEvent['taalicoonCount'] = $taalicoonCount;
            $formattedEvent['taalicoonDescription'] = $description;
        }
    }

    /**
     * @param $event
     * @return string[]
     */
    private function getBrands($event)
    {
        return array_keys(array_filter(
            $this->brandSpecs,
            function (EventSpecificationInterface $brandSpec) use ($event) {
                return $brandSpec->isSatisfiedBy($event);
            }
        ));
    }
}
