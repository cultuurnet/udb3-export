<?php

namespace CultuurNet\UDB3\EventExport\Format\TabularData;

use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfoServiceInterface;
use CultuurNet\UDB3\EventExport\PriceFormatter;
use CultuurNet\UDB3\EventExport\UitpasInfoFormatter;
use CultuurNet\UDB3\StringFilter\StripHtmlStringFilter;

class TabularDataEventFormatter
{
    /**
     * Class used to filter out HTML from strings.
     * @var StripHtmlStringFilter
     */
    protected $htmlFilter;

    /**
     * A list of all included properties
     * @var string[]
     */
    protected $includedProperties;

    /**
     * @var UitpasInfoFormatter
     */
    protected $uitpasInfoFormatter;

    /**
     * @var EventInfoServiceInterface|null
     */
    protected $uitpas;

    /**
     * @param string[] $include A list of properties to include
     * @param EventInfoServiceInterface|null $uitpas
     */
    public function __construct(
        array $include,
        EventInfoServiceInterface $uitpas = null
    ) {
        $this->htmlFilter = new StripHtmlStringFilter();
        $this->includedProperties = $this->includedOrDefaultProperties($include);
        $this->uitpas = $uitpas;
        $this->uitpasInfoFormatter = new UitpasInfoFormatter(new PriceFormatter(2, ',', '.', 'Gratis'));
    }

    public function formatHeader()
    {
        $columns = array();
        foreach ($this->includedProperties as $property) {
            $columns[] = $this->columns()[$property]['name'];
        }

        return $columns;
    }

    public function formatEvent($event)
    {
        $event = json_decode($event);
        $includedProperties = $this->includedProperties;
        $row = $this->emptyRow();

        foreach ($includedProperties as $property) {
            $column = $this->columns()[$property];
            $value = $column['include']($event);

            if ($value) {
                $row[$property] = $value;
            } else {
                $row[$property] = '';
            }
        }

        return $row;
    }

    /**
     * @param string $date Date in ISO 8601 format.
     * @return string Date formatted for tabular data export.
     */
    protected function formatDate($date)
    {
        $timezone = new \DateTimeZone('Europe/Brussels');

        // Try to create from various formats to maintain backwards
        // compatibility with external systems with older json-ld
        // projections (eg. OMD).
        $formats = [\DateTime::ATOM, \DateTime::ISO8601, 'Y-m-d\TH:i:s'];

        do {
            $datetime = \DateTime::createFromFormat(current($formats), $date, $timezone);
        } while ($datetime === false && next($formats));

        if ($datetime instanceof \DateTime) {
            return $datetime->format('Y-m-d H:i');
        } else {
            return '';
        }
    }

    /**
     * @param string $date Date in ISO 8601 format.
     * @return string Date formatted for tabular data export.
     */
    protected function formatDateWithoutTime($date)
    {
        $timezone = new \DateTimeZone('Europe/Brussels');
        $datetime = \DateTime::createFromFormat(\DateTime::ATOM, $date, $timezone);
        return $datetime->format('Y-m-d');
    }

    public function emptyRow()
    {
        $row = array();

        foreach ($this->includedProperties as $property) {
            $row[$property] = '';
        }

        return $row;
    }

    protected function expandMultiColumnProperties($properties)
    {
        $expandedProperties = [];

        $expansions = [
            'address' => [
                'address.streetAddress',
                'address.postalCode',
                'address.addressLocality',
                'address.addressCountry',
            ],
            'contactPoint' => [
                'contactPoint.email',
                'contactPoint.phone',
                'contactPoint.url',
                //'contactPoint.reservations.email',
                //'contactPoint.reservations.telephone',
                //'contactPoint.reservations.url',
            ],
            'bookingInfo' => [
                'bookingInfo.price',
                'bookingInfo.url',
            ],
        ];

        foreach ($properties as $property) {
            if (isset($expansions[$property])) {
                $expandedProperties = array_merge($expandedProperties, $expansions[$property]);
            } else {
                $expandedProperties[] = $property;
            }
        }

        return $expandedProperties;
    }

    protected function includedOrDefaultProperties($include)
    {
        if ($include) {
            $properties = $this->expandMultiColumnProperties($include);

            array_unshift($properties, 'id');
        } else {
            $properties = array_keys($this->columns());
        }

        return $properties;
    }

    protected function columns()
    {
        $formatter = $this;
        $contactPoint = function (\stdClass $event) use ($formatter) {
            return $formatter->contactPoint($event);
        };

        return [
            'id' => [
                'name' => 'id',
                'include' => function ($event) {
                    $eventUri = $event->{'@id'};
                    $uriParts = explode('/', $eventUri);
                    $eventId = array_pop($uriParts);

                    return $eventId;
                },
                'property' => 'id'
            ],
            'name' => [
                'name' => 'titel',
                'include' => function ($event) {
                    if ($event->name) {
                        return reset($event->name);
                    }
                },
                'property' => 'name'
            ],
            'creator' => [
                'name' => 'auteur',
                'include' => function ($event) {
                    return $event->creator;
                },
                'property' => 'creator'
            ],
            'bookingInfo.price' => [
                'name' => 'prijs',
                'include' => function ($event) {
                    $basePrice = null;

                    if (property_exists($event, 'priceInfo') && is_array($event->priceInfo)) {
                        foreach ($event->priceInfo as $price) {
                            if ($price->category == 'base') {
                                $basePrice = $price;
                                break;
                            }
                        }
                    }

                    return $basePrice ? $basePrice->price : '';
                },
                'property' => 'bookingInfo'
            ],
            'kansentarief' => [
                'name' => 'kansentarief',
                'include' => function ($event) {
                    $eventUri = $event->{'@id'};
                    $uriParts = explode('/', $eventUri);
                    $eventId = array_pop($uriParts);

                    $uitpasInfo = $this->uitpas->getEventInfo($eventId);
                    if ($uitpasInfo) {
                        $uitpasInfo = $this->uitpasInfoFormatter->format($uitpasInfo);

                        $cardSystems = array_reduce($uitpasInfo['prices'], function ($cardSystems, $tariff) {
                            $cardSystem = isset($cardSystems[$tariff['cardSystem']]) ? $cardSystems[$tariff['cardSystem']] : '';
                            $cardSystem = empty($cardSystem)
                                ? $tariff['cardSystem'] .': € ' . $tariff['price']
                                : $cardSystem . ' / € ' . $tariff['price'];

                            $cardSystems[$tariff['cardSystem']] = $cardSystem;
                            return $cardSystems;
                        }, []);

                        $formattedTariffs = array_reduce($cardSystems, function ($tariffs, $cardSystemPrices) {
                            return $tariffs ? $tariffs . ' | ' . $cardSystemPrices : $cardSystemPrices;
                        });

                        if (!empty($formattedTariffs)) {
                            return $formattedTariffs;
                        }
                    }
                },
                'property' => 'kansentarief'
            ],
            'bookingInfo.url' => [
                'name' => 'ticket link',
                'include' => function ($event) {
                    if (property_exists($event, 'bookingInfo')) {
                        $first = reset($event->bookingInfo);
                        if (is_object($first) && property_exists($first, 'url')) {
                            return $first->url;
                        }
                    }
                },
                'property' => 'bookingInfo'
            ],
            'description' => [
                'name' => 'omschrijving',
                'include' => function ($event) {
                    if (property_exists($event, 'description')) {
                        $description = reset($event->description);

                        // the following preg replace statements will strip unwanted line-breaking characters
                        // except for markup

                        // do not add a whitespace when a line break follows a break tag
                        $description = preg_replace('/<br\ ?\/?>\s+/', '<br>', $description);

                        // replace all leftover line breaks with a space to prevent words from sticking together
                        $description = trim(preg_replace('/\s+/', ' ', $description));

                        return $this->htmlFilter->filter($description);
                    }
                },
                'property' => 'description'
            ],
            'organizer' => [
                'name' => 'organisatie',
                'include' => function ($event) {
                    if (property_exists($event, 'organizer') &&
                        isset($event->organizer->name)
                    ) {
                        return $event->organizer->name;
                    }
                },
                'property' => 'organizer'
            ],
            'calendarSummary' => [
                'name' => 'tijdsinformatie',
                'include' => function ($event) {
                    return $event->calendarSummary;
                },
                'property' => 'calendarSummary'
            ],
            'labels' => [
                'name' => 'labels',
                'include' => function ($event) {
                    if (isset($event->labels)) {
                        return implode(';', $event->labels);
                    }
                },
                'property' => 'labels'
            ],
            'typicalAgeRange' => [
                'name' => 'leeftijd',
                'include' => function ($event) {
                    return $event->typicalAgeRange;
                },
                'property' => 'typicalAgeRange'
            ],
            'performer' => [
                'name' => 'uitvoerders',
                'include' => function ($event) {
                    if (property_exists($event, 'performer')) {
                        $performerNames = [];
                        foreach ($event->performer as $performer) {
                            $performerNames[] = $performer->name;
                        }

                        return implode(';', $performerNames);
                    }
                },
                'property' => 'performer'
            ],
            'language' => [
                'name' => 'taal van het aanbod',
                'include' => function ($event) {
                    if (property_exists($event, 'language')) {
                        return implode(';', $event->language);
                    }
                },
                'property' => 'language'
            ],
            'terms.theme' => [
                'name' => 'thema',
                'include' => function ($event) {
                    if (property_exists($event, 'terms')) {
                        foreach ($event->terms as $term) {
                            if ($term->domain && $term->label && $term->domain == 'theme') {
                                return $term->label;
                            }
                        }
                    }
                },
                'property' => 'terms.theme'
            ],
            'terms.eventtype' => [
                'name' => 'soort aanbod',
                'include' => function ($event) {
                    if (property_exists($event, 'terms')) {
                        foreach ($event->terms as $term) {
                            if ($term->domain && $term->label && $term->domain == 'eventtype') {
                                return $term->label;
                            }
                        }
                    }
                },
                'property' => 'terms.eventtype'
            ],
            'created' => [
                'name' => 'datum aangemaakt',
                'include' => function ($event) {
                    if (!empty($event->created)) {
                        return $this->formatDate($event->created);
                    } else {
                        return '';
                    }
                },
                'property' => 'created'
            ],
            'modified' => [
                'name' => 'datum laatste aanpassing',
                'include' => function ($event) {
                    if (!empty($event->modified)) {
                        return $this->formatDate($event->modified);
                    } else {
                        return '';
                    }
                },
                'property' => 'modified'
            ],
            'available' => [
                'name' => 'embargodatum',
                'include' => function ($event) {
                    if (!empty($event->available)) {
                        return $this->formatDateWithoutTime($event->available);
                    } else {
                        return '';
                    }
                },
                'property' => 'available'
            ],
            'startDate' => [
                'name' => 'startdatum',
                'include' => function ($event) {
                    if (!empty($event->startDate)) {
                        return $this->formatDate($event->startDate);
                    } else {
                        return '';
                    }
                },
                'property' => 'startDate'
            ],
            'endDate' => [
                'name' => 'einddatum',
                'include' => function ($event) {
                    if (!empty($event->endDate)) {
                        return $this->formatDate($event->endDate);
                    } else {
                        return '';
                    }
                },
                'property' => 'endDate'
            ],
            'calendarType' => [
                'name' => 'tijd type',
                'include' => function ($event) {
                    return $event->calendarType;
                },
                'property' => 'calendarType'
            ],
            'location' => [
                'name' => 'locatie naam',
                'include' => function ($event) {
                    if (property_exists($event, 'location') && isset($event->location->name)) {
                        return reset($event->location->name);
                    }
                },
                'property' => 'location'
            ],
            'address.streetAddress' => [
                'name' => 'straat',
                'include' => function ($event) {
                    if (isset($event->location->address->streetAddress)) {
                        return $event->location->address->streetAddress;
                    }
                },
                'property' => 'address.streetAddress'
            ],
            'address.postalCode' => [
                'name' => 'postcode',
                'include' => function ($event) {
                    if (isset($event->location->address->postalCode)) {
                        return $event->location->address->postalCode;
                    }
                },
                'property' => 'address.postalCode'
            ],
            'address.addressLocality' => [
                'name' => 'gemeente',
                'include' => function ($event) {
                    if (isset($event->location->address->addressLocality)) {
                        return $event->location->address->addressLocality;
                    }
                },
                'property' => 'address.addressLocality'
            ],
            'address.addressCountry' => [
                'name' => 'land',
                'include' => function ($event) {
                    if (isset($event->location->address->addressCountry)) {
                        return $event->location->address->addressCountry;
                    }
                },
                'property' => 'address.addressCountry'
            ],
            'image' => [
                'name' => 'afbeelding',
                'include' => function ($event) {
                    return !empty($event->image) ? $event->image : '';
                },
                'property' => 'image'
            ],
            'sameAs' => [
                'name' => 'externe ids',
                'include' => function ($event) {
                    if (property_exists($event, 'sameAs')) {
                        $ids = array();

                        foreach ($event->sameAs as $externalId) {
                            $ids[] = $externalId;
                        }

                        return implode("\r\n", $ids);
                    }
                },
                'property' => 'sameAs'
            ],
            'contactPoint.email' => [
                'name' => 'e-mail',
                'include' => function ($event) use ($contactPoint) {
                    return $this->listJsonldProperty(
                        $contactPoint($event),
                        'email'
                    );
                },
                'property' => 'contactPoint'
            ],
            'contactPoint.phone' => [
                'name' => 'telefoon',
                'include' => function ($event) use ($contactPoint) {
                    return $this->listJsonldProperty(
                        $contactPoint($event),
                        'phone'
                    );
                },
                'property' => 'contactPoint'
            ],
            'contactPoint.url' => [
                'name' => 'url',
                'include' => function ($event) use ($contactPoint) {
                    return $this->listJsonldProperty(
                        $contactPoint($event),
                        'url'
                    );
                },
                'property' => 'contactPoint'
            ],
            'contactPoint.reservations.email' => [
                'name' => 'e-mail reservaties',
                'include' => function ($event) use ($contactPoint) {
                    return '';
                },
                'property' => 'contactPoint'
            ],
            'contactPoint.reservations.telephone' => [
                'name' => 'telefoon reservaties',
                'include' => function ($event) use ($contactPoint) {
                        return '';
                },
                'property' => 'contactPoint'
            ],
            'contactPoint.reservations.url' => [
                'name' => 'online reservaties',
                'include' => function ($event) use ($contactPoint) {
                    return '';
                },
                'property' => 'contactPoint'
            ],
        ];
    }

    /**
     * @param object $jsonldData
     *  An object that contains the jsonld data.
     *
     * @param string $propertyName
     *  The name of the property that contains an array of values.
     *
     * @return string
     */
    private function listJsonldProperty($jsonldData, $propertyName)
    {
        if (property_exists($jsonldData, $propertyName)) {
            return implode("\r\n", $jsonldData->{$propertyName});
        } else {
            return '';
        }
    }

    /**
     * @param object $event
     * @return object
     */
    private function contactPoint($event)
    {
        if (property_exists($event, 'contactPoint')) {
            return $event->contactPoint;
        }

        return new \stdClass();
    }
}