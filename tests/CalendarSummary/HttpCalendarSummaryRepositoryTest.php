<?php

namespace CalendarSummary;

use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarLanguage;
use CultuurNet\UDB3\EventExport\CalendarSummary\ContentType;
use CultuurNet\UDB3\EventExport\CalendarSummary\Format;
use CultuurNet\UDB3\EventExport\CalendarSummary\HttpCalendarSummaryRepository;
use CultuurNet\UDB3\EventExport\CalendarSummary\SummaryUnavailableException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Client\Exception\HttpException;
use Http\Client\HttpClient;
use League\Uri\Schemes\Http;

class HttpCalendarSummaryRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_fetch_calendar_summaries_by_id_at_a_configured_location()
    {
        $offerId = 'D352C4E6-90C6-4120-8DBB-A09B486170CD';
        $expectedRequest = new Request(
            'GET',
            'http://uitdatabank.io/event/D352C4E6-90C6-4120-8DBB-A09B486170CD/calendar-summary?format=lg&language=nl',
            [
                'Accept' => 'text/plain'
            ]
        );

        $summariesLocation = Http::createFromString('http://uitdatabank.io/');
        /** @var HttpClient|\PHPUnit_Framework_MockObject_MockObject $httpClient */
        $httpClient = $this->createMock(HttpClient::class);

        $repository = new HttpCalendarSummaryRepository($httpClient, $summariesLocation);

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn(new Response());

        $repository->get($offerId, ContentType::PLAIN(), Format::LARGE(), CalendarLanguage::DUTCH());
    }

    /**
     * @test
     */
    public function it_should_throw_an_unavailable_exception_when_processing_the_summary_request_fails()
    {
        $offerId = 'D352C4E6-90C6-4120-8DBB-A09B486170CD';
        /** @var HttpException|\PHPUnit_Framework_MockObject_MockObject $httpException */
        $httpException = $this->createMock(HttpException::class);

        $summariesLocation = Http::createFromString('http://uitdatabank.io/');
        /** @var HttpClient|\PHPUnit_Framework_MockObject_MockObject $httpClient */
        $httpClient = $this->createMock(HttpClient::class);

        $repository = new HttpCalendarSummaryRepository($httpClient, $summariesLocation);

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->withAnyParameters()
            ->willThrowException($httpException);

        $this->expectException(SummaryUnavailableException::class);

        $repository->get($offerId, ContentType::PLAIN(), Format::LARGE());
    }

    /**
     * @test
     */
    public function it_should_fetch_calendar_summaries_in_dutch_if_no_language_is_giving()
    {
        $offerId = 'D352C4E6-90C6-4120-8DBB-A09B486170CD';
        $expectedRequest = new Request(
            'GET',
            'http://uitdatabank.io/event/D352C4E6-90C6-4120-8DBB-A09B486170CD/calendar-summary?format=lg&language=nl',
            [
                'Accept' => 'text/plain'
            ]
        );

        $summariesLocation = Http::createFromString('http://uitdatabank.io/');
        /** @var HttpClient|\PHPUnit_Framework_MockObject_MockObject $httpClient */
        $httpClient = $this->createMock(HttpClient::class);

        $repository = new HttpCalendarSummaryRepository($httpClient, $summariesLocation);

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn(new Response());

        $repository->get($offerId, ContentType::PLAIN(), Format::LARGE());
    }

    /**
     * @test
     */
    public function it_should_fetch_calendar_summaries_in_other_languages_if_requested()
    {
        $offerId = 'D352C4E6-90C6-4120-8DBB-A09B486170CD';
        $expectedRequest = new Request(
            'GET',
            'http://uitdatabank.io/event/D352C4E6-90C6-4120-8DBB-A09B486170CD/calendar-summary?format=lg&language=fr',
            [
                'Accept' => 'text/plain'
            ]
        );

        $summariesLocation = Http::createFromString('http://uitdatabank.io/');
        /** @var HttpClient|\PHPUnit_Framework_MockObject_MockObject $httpClient */
        $httpClient = $this->createMock(HttpClient::class);

        $repository = new HttpCalendarSummaryRepository($httpClient, $summariesLocation);

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($expectedRequest)
            ->willReturn(new Response());

        $repository->get($offerId, ContentType::PLAIN(), Format::LARGE(), CalendarLanguage::FRENCH());
    }
}
