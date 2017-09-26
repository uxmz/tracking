<?php

namespace Uxmz\Ga\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

use Uxmz\Ga\Tracker;

class TrackerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp() {
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar']),
        ]);

        $handler = HandlerStack::create($mock);
        /** @var ClientInterface */
        $this->httpClient = new Client(/*['handler' => $handler]*/);

        /** @var LoggerInterface */
        $this->logger = null;

        $trackerOptions = [
            "applicationName" => "Test",
            "webTrackingId" => 'UA-1234567-8',
            "appTrackingId" => 'UA-1234567-8',
            "batching" => false,
            "debug" => true,
        ];

        $this->SUT = new Tracker($trackerOptions, $this->httpClient, $this->logger);
        $this->cid = '';
    }

    /**
     * @test
     */
    public function should_have_been_created() {
        $this->assertInstanceOf(Tracker::class, $this->SUT);
    }

    /**
     * @test
     * @covers startSession
     */
    public function should_track_session_start() {
        $this->SUT->startSession($this->cid);
    }

    /**
     * @test
     * @covers endSession
     */
    public function should_track_session_stop() {
        $this->SUT->endSession($this->cid);
    }

    /**
     * @test
     * @covers trackPageView
     */
    public function should_track_page_view() {
        $this->SUT->trackPageView($this->cid, 'example.com', '/', 'home');
    }

    /**
     * @test
     * @covers trackEvent
     */
    public function should_track_page_event() {
        $this->SUT->trackEvent($this->cid, 'category', 'action', 'label', 'value');
    }

    /**
     * @test
     * @covers trackTransaction
     * @covers trackTransactionItem
     */
    public function should_track_e_commerce_transaction() {
        $transactionId = 0;
        $this->SUT->trackTransaction($this->cid, $transactionId, 'affiliation', 0, 0, 0, 'USD');
        $this->SUT->trackTransactionItem($this->cid, $transactionId, 'item-name', 0, 1, 'sku', 'variation a', 'USD');
    }

    /**
     * @test
     * @covers trackSocial
     */
    public function should_track_social_network_interaction() {
        $this->SUT->trackSocial($this->cid, 'like', 'facebook', 'target');
    }

    /**
     * @test
     * @covers trackException
     */
    public function should_track_exception_as_object() {
        $this->SUT->trackException($this->cid, new \Exception, false);
    }

    /**
     * @test
     * @covers trackException
     */
    public function should_track_exception_as_string() {
        $this->SUT->trackException($this->cid, 'bloody exception', 'target');
    }

    /**
     * @test
     * @covers trackUserTiming
     */
    public function should_track_user_time() {
        $this->SUT->trackUserTiming($this->cid, 'category', 'variable', 1);
    }

    /**
     * @test
     * @covers trackScreenView
     */
    public function should_track_screen_view() {
        $this->SUT->trackScreenView($this->cid, 'test app', '1.0.1', 'org.test.app', 'org.test.app', 'dashboard');
    }
}
