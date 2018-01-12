<?php

namespace Uxmz\Ga\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

use Uxmz\Ga\Tracker;

class TrackerTest extends \PHPUnit_Framework_TestCase
{
    /** @var Tracker */
    private $SUT;
    private $cid;

    public function setUp() {
        /** @var ClientInterface */
        $httpClient = new Client();

        /** @var LoggerInterface */
        $logger = $this->prophesize(LoggerInterface::class);

        $trackerOptions = [
            "applicationName" => "Test",
            "webTrackingId" => 'UA-1234567-8',
            "appTrackingId" => 'UA-1234567-8',
            "batching" => false,
            "debug" => true,
        ];

        $this->SUT = new Tracker($httpClient, $logger->reveal(), $trackerOptions);
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
        $this->SUT->trackEvent($this->cid, 'category', 'action', 'label', 0);
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
        $this->SUT->trackException($this->cid, new \Exception('exception object'), false);
    }

    /**
     * @test
     * @covers trackException
     */
    public function should_track_exception_as_string() {
        $this->SUT->trackException($this->cid, 'exception string', false);
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
