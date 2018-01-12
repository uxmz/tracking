<?php
/**
 * Tracker class providing Google Analytics measuring protocol implementation.
 * PHP Version 5
 *
 * @category  Library
 * @package   Ga
 * @author    UX Tech Team <tech@ux.co.mz>
 * @copyright 2016 UX - Information Technologies, Lda.
 * @license   https://www.tldrlegal.com/l/mit MIT
 * @link      https://github.com/uxmz/tracking
 */

namespace Uxmz\Ga;

use Psr\Log\LoggerInterface;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use \Exception, \InvalidArgumentException;

/**
 * Google Analytics Measurement Protocol Tracker Implementation.
 *
 * @category  Library
 * @package   Ga
 * @author    UX Tech Team <tech@ux.co.mz>
 * @copyright 2016 UX - Information Technologies, Lda.
 * @license   https://www.tldrlegal.com/l/mit MIT
 * @link      https://github.com/uxmz/tracking
 * @see       https://ga-dev-tools.appspot.com/hit-builder/
 * @see       https://developers.google.com/analytics/devguides/collection/protocol/v1/parameters#cid
 * @see       http://hayageek.com/php-curl-post-get/
 */
class Tracker
{

    /**
     * Indicates if this tracker is initialized.
     *
     * @var boolean
     */
    protected $initialized = false;

    /**
     * Class default options.
     *
     * @var array
     */
    protected $defaults = array(
        "applicationName" => "My-Awesome-App-Name", // Nice name for app reporting in Insights.

        // The Application Insights server
        "ssl" => true, // required for ga
        "host" => "www.google-analytics.com",
        "hit-url" => "/collect",
        "batch-url" => "/batch",
        "apiVersion" => 1, //  API version.
        "clientId" => 555, //  Anonymous Client ID.
        "appTrackingId" => "UA-XXXXXXXX-X",  // App Tracking ID / Property ID.
        "webTrackingId" => "UA-XXXXXXXX-X",  // Web Tracking ID / Property ID.

        // Quotas
        "batching" => false,  // If true, telemetry data is batched.
        "maxBatchHit" => 20,  // count
        "maxBatchPayloadSize" => 16,  // Kilobites
        "maxHitPayloadSize" => 8,  // Kilobites
        "maxHitsPerDay" => 200000,  // count
        "maxHitsPerMonth" => 10000000,  // count
        "maxHitsPerSession" => 500,  // count

        "userTraits" => [],  // Enabled / valid user traits

        "geoid" => 'MZ',

        "anonymizeIP" => true,  // If true, users IP addresses will be anonymized.
        "enabled" => true,  // If true, telemetry data is not collected or sent. Default true.
        // If true, data is sent immediately and not batched.
        // Hits sent with debug will not show up in reports. They are for debugging only.
        "debug" => false,
        "log" => false,  // If true, data is logged before sending

        "proxies" => [], // list of proxies to be checked for clients IP address
    );

    /**
     * Class options after initialization.
     *
     * @var array
     */
    protected $options = [];

    /**
     * The events queue.
     *
     * To optimize usage, the telemetry clients batches the events to send in this
     * Queue and then sends the data in fixed time intervals or whenever
     * the `maxBatchSize` limit is reached.
     *
     * @var array
     */
    protected $eventsQueue = [];

    // Events / Hit Types
    const NON_INTERACTIVE = "ni";
    const EVENT = "event";
    const EXCEPTION = "exception";
    const PAGE_VIEW = "pageview";
    const SCREEN_VIEW = "screenview";
    const TRANSACTION = "transaction";
    const ITEM = "item";
    const SOCIAL = "social";
    const TIMING = "timing";

    // Enabled / Valid event/hit types
    const EVENT_TYPES = array(
        self::NON_INTERACTIVE,
        self::EVENT,
        self::EXCEPTION,
        self::PAGE_VIEW,
        self::SCREEN_VIEW,
        self::TRANSACTION,
        self::ITEM,
        self::SOCIAL,
        self::TIMING
    );

    /**
     * Log message prefix.
     */
    const TRACKING_LOG = "Tracking Log: ";

    /**
     * Tracker ID Regex
     */
    const TRACKER_ID_REGEXP = "/^(UA)-\d{4,10}-\d{1,4}$/";

    /**
     * The logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * The http client
     *
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * Checks if we can track or not.
     *
     * @internal
     * @return   boolean
     */
    protected function canTrack()
    {
        return $this->options["enabled"] === true;
    }

    /**
     * Stores events we want to track in queue.
     *
     * @param string $eventType the type of thing we want to test
     * @param string $name the name of the event name
     * @param array $data the data of the event
     * @param array $props Array of key-value (string/string) pair of properties related to the event
     * @param array $metrics Array of key-value (string/double) pair of metrics related to the event
     *
     * @return void
     * @throws Exception
     */
    protected function track($eventType, $name, $data = [], $props = [], $metrics = [])
    {
        if (!$this->initialized) {
            throw new \Exception('Class is not initialized');
        }

        if (!$this->canTrack()) {
            throw new \Exception('Tracking is not enabled');
        }

        if (!in_array($eventType, self::EVENT_TYPES)) {
            throw new \InvalidArgumentException('eventType');
        }

        if (!$this->validateProps($props)) {
            throw new \InvalidArgumentException('properties');
        }

        if (!$this->validateMetrics($metrics)) {
            throw new \InvalidArgumentException('metrics');
        }

        if ($this->options["log"] === true) {
            $dataString = "";
            $propsString = "";
            $metricsString = "";

            foreach ($data as $key => $value) {
                $dataString .= $key . ":" . $value. "\n";
            }

            foreach ($props as $key => $value) {
                $propsString .= $key . ": " . $value . "\n";
            }

            foreach ($metrics as $key => $value) {
                $metricsString .= $key . ": " . $value . "\n";
            }

            $this->logger->info(sprintf("%s => Type: %s \nName: %s \nData -> %s \nProperties -> %s \nMetrics -> %s", self::TRACKING_LOG, $eventType, $name, $dataString, $propsString, $metricsString));
        }

        if ($eventType === self::NON_INTERACTIVE) {
            $data["ni"] = true;
        } else {
            $data["t"] = $eventType;
        }

        if ($eventType === self::EXCEPTION) {
            $data["tid"] = $this->options["appTrackingId"] ? $this->options["appTrackingId"] : $this->options["webTrackingId"];
        }

        // Proxy-Overrides
        // ---
        $ip = $this->getClientIp(true, $this->options["proxies"]);
        if ($this->isValidIp($ip)) {
            $data["uip"] = $ip;
        }

        if (array_key_exists("HTTP_USER_AGENT", $_SERVER)) {
            $data["ua"] = $_SERVER["HTTP_USER_AGENT"]; // Note that Google has libraries to identify real user agents. Hand crafting your own agent could break at any time.
        }

        $data["geoid"] = $this->options["geoid"]; // Maybe get from IP

        // TODO: Allow overriding this so it gets the language chosen by the user.
        $data["ul"] = 'pt';

        // If there's a UID set in _ga cookie take it otherwise user internal if available
        if ($this->getGaUid()) {
            $data["uid"] = $this->getGaUid();
        }

        // Queue and flush
        // ---
        $this->eventsQueue[] = array(
            "time"      => strtotime("now"),
            "eventType" => $eventType,
            "name"      => $name,
            "data"      => $data,
            "props"     => $props,
            "metrics"   => $metrics,
        );

        if ($this->options["debug"] === true || $this->options["batching"] === false || count($this->eventsQueue) >= $this->options["maxBatchHit"]) {
            $this->_flush();
        }
    }

    /**
     * Immediately send all queued telemetry data.
     *
     * @return void
     */
    protected function _flush()
    {
        // Don't flush if no hits exist.
        $hitCount = count($this->eventsQueue);
        if ($hitCount <= 0) {
            return;
        }

        $url = ($this->options["ssl"] === true ? "https://" : "http://") . $this->options["host"] . ($this->options["debug"] === true ? "/debug" : "");
        $body = array("v" => $this->options["apiVersion"]);
        $post = [];

        if (!array_key_exists("tid", $body)) {
            $body["tid"] = $this->options["webTrackingId"];
        }

        if ($this->options["anonymizeIP"] === true) {
            $body["aip"] = 1;
        }

        // Cache buster
        $body["z"] = strtotime("now");

        if ($hitCount === 1) {
            $url .= $this->options["hit-url"];
            $body = array_merge($body, $this->eventsQueue[0]["data"]);
            if (!array_key_exists("cid", $body) || !$this->isGuid($body["cid"])) {
                $body["cid"] = $this->options["clientId"];
            }
        }

        if ($hitCount > 1) {
            $url .= $this->options["batch-url"];
            foreach ($this->eventsQueue as $key => $event) {
                if (!array_key_exists("cid", $event["data"]) || !$this->isGuid($event["data"]["cid"])) {
                    $event["data"]["cid"] = $this->options["clientId"];
                }
                $post[] = http_build_query(array_merge($event["data"], $body));
            }
            $post = rtrim(implode("\r\n", $post), "\r\n");
        }

        $promise = $hitCount === 1
            ? $this->httpClient->requestAsync('GET', $url . "?" . http_build_query($body))
            : $this->httpClient->requestAsync(
                'POST', $url, [
                    'body' => $post,
                    'headers' => [
                        'cache-control' => 'no-cache',
                        'content-type' => 'text/html',
                    ]
                ]
            );

        $newPromise = $promise->then(
            function (ResponseInterface $response) {
                if ($this->options["debug"]) {
                    $responseData = json_decode($response->getBody(), true);
                    if (!$responseData['hitParsingResult'][0]['valid']) {
                        throw new Exception("Invalid hit sent. Got response:\n" . $response->getBody());
                    }

                    $this->logger->debug($response->getBody());
                }
            },
            function (Exception $exception) {
                if ($this->options["debug"]) {
                    throw $exception;
                }

                $this->logger->error($exception->getMessage());
            }
        );

        $newPromise->wait();

        $this->eventsQueue = []; // clear queue.
    }

    // Helpers
    // ---

    /**
     * Validates given properties.
     *
     * @param array $props properties to be validated
     *
     * @return bool
     */
    protected function validateProps($props)
    {
        if (!is_array($props)) {
            return false;
        }

        foreach ($props as $prop) {
            if (!is_string($prop)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Validates the given metrics.
     *
     * @param array $metrics metrics to be validated
     *
     * @return bool
     */
    protected function validateMetrics($metrics)
    {
        if (!is_array($metrics)) {
            return false;
        }

        foreach ($metrics as $metric) {
            if (!is_numeric($metric)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validates initialization options for the tracker.
     *
     * @param array $options list of options
     *
     * @return bool
     */
    protected function validateOptions($options)
    {
        if (!is_array($options)) {
            return false;
        }

        if (array_key_exists("webTrackingId", $options)) {
            if (!preg_match(self::TRACKER_ID_REGEXP, $options["webTrackingId"])) {
                return false;
            }
        }

        if (array_key_exists("appTrackingId", $options)) {
            if (!preg_match(self::TRACKER_ID_REGEXP, $options["appTrackingId"])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if a given string is in Guid format.
     *
     * When sending hits to google the cid param must be in GUID/UUID format
     * as per RFC4122 (http://www.ietf.org/rfc/rfc4122.txt)
     *
     * @param string $guid the value to be checked
     *
     * @return boolean
     */
    protected function isGuid($guid)
    {
        if (empty($guid)) {
            return false;
        }

        if (function_exists("is_guid")) {
            return is_guid($guid);
        }

        return preg_match('/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/i', $guid);
    }

    /**
     * Check that a given string is a valid IP address.
     *
     * @param string $ip the value to be checked
     *
     * @return boolean
     */
    protected function isValidIp($ip)
    {
        $flags = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6;
        if (filter_var($ip, FILTER_VALIDATE_IP, $flags) === false) {
            return false;
        }
        return true;
    }

    /**
     * Get Google Analytics UID
     * Taken from http://stackoverflow.com/questions/16102436/what-are-the-values-in-ga-cookie
     *
     * @return int
     */
    protected function getGaUid()
    {
        $uid = 0;
        if (isset($_COOKIE['__utma'])) {
            list($hash_domain, $uid, $first_visit, $previous_visit, $time_start, $num_visits) = sscanf($_COOKIE['__utma'], '%d.%d.%d.%d.%d.%d');
        } elseif (isset($_COOKIE['_ga'])) {
            list($c_format, $c_domain, $uid, $first_visit) = sscanf($_COOKIE['_ga'], 'GA%d.%d.%d.%d');
        }

        return $uid;
    }

    /**
     * Gets the current request's IP.
     *
     * @param bool  $check_proxies if it should also check proxies or not
     * @param array $proxies       the list of trusted proxies
     *
     * @return null|string the client's IP.
     */
    protected function getClientIp($check_proxies=true, $proxies = [])
    {
        $ip = null;
        $forwarded_headers = [
            'X-FORWARDED-FOR',
            'X-FORWARDED',
            'X-CLUSTER-CLIENT-IP',
            'CLIENT-IP',
        ];

        $proxies = array_merge($proxies, $this->options["proxies"]);

        if (isset($_SERVER['REMOTE_ADDR']) && $this->isValidIp($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if ($check_proxies && !empty($proxies)) {
            if (!in_array($ip, $proxies)) {
                $check_proxies = false;
            }
        }

        if ($check_proxies) {
            foreach ($forwarded_headers as $header) {
                if (isset($_SERVER['HTTP_' . $header])) {
                    $ip = trim(explode(',', $_SERVER['HTTP_' . $header])[0]);
                    if ($this->isValidIp($ip)) {
                        break;
                    }
                }
            }
        }

        if (!$this->isValidIp($ip)) {
            $ip = gethostbyname($ip);
        }

        return $ip;
    }

    // Public API
    // ---

    /**
     * Tracker constructor.
     *
     * @param ClientInterface      $httpClient http client used for making requests.
     * @param LoggerInterface|null $logger     logger instance use to log errors during run-time.
     * @param array                $options    the tracker initialization options.
     *
     * @throws Exception if cURL is not enabled.
     * @throws InvalidArgumentException if given options are invalid.
     */
    public function __construct(ClientInterface $httpClient, LoggerInterface $logger, $options = [])
    {
        if (!$this->validateOptions($options)) {
            throw new InvalidArgumentException("Tracker initialization options are invalid!");
        }

        $this->options = array_merge($this->defaults, $options);

        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->initialized = true;
    }

    /**
     * Does the appropriate sending of the collected events to "Houston" on demand.
     *
     * @return void
     */
    public function flush()
    {
        $this->_flush();
    }

    /**
     * Tracks the session starting time for a given client ID.
     * Add more context data otherwise it's meaningless
     *
     * @param string $cid Client ID.
     *
     * @return void
     * @throws Exception
     */
    public function startSession($cid)
    {
        $data = array(
            "cid" => $cid,
            "sc"  => "start",
            "dp"  => "/",
        );

        $this->track(self::NON_INTERACTIVE, self::NON_INTERACTIVE, $data);
    }

    /**
     * Tracks the session ending time for a given client ID.
     * Add more context data otherwise it's meaningless
     *
     * @param string $cid Client ID.
     *
     * @return void
     * @throws Exception
     */
    public function endSession($cid)
    {
        $data = array(
            "cid" => $cid,
            "sc"  => "end",
            "dp"  => "/",
        );

        $this->track(self::NON_INTERACTIVE, self::NON_INTERACTIVE, $data);
    }


    /**
     * Tracks page views.
     *
     * @param string $cid Client ID.
     * @param string $hostname Document hostname.
     * @param string $page Page.
     * @param string $title Title.
     *
     * @return void
     * @throws Exception
     */
    public function trackPageView($cid, $hostname, $page, $title)
    {
        if (!is_string($hostname) || empty($hostname)) {
            throw new \InvalidArgumentException('hostname');
        }

        if (!is_string($page) || empty($page)) {
            throw new \InvalidArgumentException('page');
        }

        if (!is_string($title) || empty($title)) {
            throw new \InvalidArgumentException('title');
        }

        $data = array(
            "cid" => $cid,
            "dh"  => $hostname,
            "dp"  => $page[0] === "/" ? $page : "/" . $page,
            "dt"  => $title
        );

        $this->track(self::PAGE_VIEW, self::PAGE_VIEW, $data);
    }


    /**
     * Tracks Events;
     *
     * @param string $cid Client ID.
     * @param string $category Event Category.
     * @param string $action Event Action.
     * @param string $label Event label.
     * @param int $value Event value.
     *
     * @return void
     * @throws Exception
     */
    public function trackEvent($cid, $category, $action, $label = null, $value = null)
    {
        if (!isset($category) || !is_string($category) || empty($category)) {
            throw new \InvalidArgumentException('category');
        }

        if (!isset($action) || !is_string($action) || empty($action)) {
            throw new \InvalidArgumentException('action');
        }

        if (isset($label) && !is_string($label)) {
            throw new \InvalidArgumentException('label');
        }

        if (isset($value) && !is_integer($value)) {
            throw new \InvalidArgumentException('value');
        }

        $data = array(
            "cid" => $cid,
            "ec"  => $category,
            "ea"  => $action
        );

        if (isset($label)) {
            $data["el"] = $label;
        }

        if (isset($value)) {
            $data["ev"] = $value;
        }

        $this->track(self::EVENT, self::EVENT, $data);
    }

    // Enhanced e-Commerce tracking
    // ---

    // e-Commerce tracking
    // ---

    // To send e-commerce data, send one transaction hit to represent an entire transaction,
    // then send an item hit for each item in the transaction.
    // The transaction ID ti links all the hits together to represent the entire purchase.

    /**
     * Tracks e-commerce transactions.
     *
     * @param string $cid Client ID.
     * @param string $txnId transaction ID.
     * @param string $affiliation Transaction affiliation.
     * @param int $revenue Transaction revenue.
     * @param int $shipping Transaction shipping.
     * @param int $tax Transaction tax.
     * @param string $currency Currency code. It should belong on the
     *                            {@link
     *                            https://support.google.com/analytics/answer/6205902?hl=en#supported-currencies
     *                            Supported currencies and codes} list
     *
     * @return void
     * @throws Exception
     */
    public function trackTransaction($cid, $txnId, $affiliation, $revenue = 0, $shipping = 0, $tax = 0, $currency="USD")
    {
        if (!isset($txnId)) {
            throw new \InvalidArgumentException('txnId');
        }

        if (isset($affiliation) && (!is_string($affiliation) || strlen($affiliation) === 0)) {
            throw new \InvalidArgumentException('affiliation');
        }

        if (isset($revenue) && (!is_numeric($revenue) || $revenue < 0)) {
            throw new \InvalidArgumentException('revenue');
        }

        if (isset($shipping) && (!is_numeric($shipping) || $shipping < 0)) {
            throw new \InvalidArgumentException('shipping');
        }

        if (isset($tax) && (!is_numeric($tax) || $tax < 0)) {
            throw new \InvalidArgumentException('tax');
        }

        // TODO: Drop this poor regex and validate properly against ISO 4217 - http://www.iso.org/iso/home/standards/currency_codes.htm
        if (isset($currency) && (!is_string($currency) || strlen($currency) != 3)) {
            throw new \InvalidArgumentException('currency');
        }

        $data = array(
            "cid" => $cid,
            "ti"  => $txnId
        );

        if (isset($affiliation)) {
            $data["ta"] = $affiliation;
        }

        if (isset($revenue)) {
            $data["tr"] = $revenue;
        }

        if (isset($shipping)) {
            $data["ts"] = $shipping;
        }

        if (isset($tax)) {
            $data["tt"] = $tax;
        }

        if (isset($currency)) {
            $data["cu"] = $currency;
        }

        $this->track(self::TRANSACTION, self::TRANSACTION, $data);
    }

    /**
     * Tracks e-commerce transaction items
     *
     * @param string $cid Client ID.
     * @param string $txnId Transaction ID.
     * @param string $name Item name.
     * @param int $price Item price.
     * @param int $quantity Item quantity.
     * @param string $sku Item code / SKU.
     * @param string $variation Item variation / category.
     * @param string $currency Currency code. It should belong on the
     *                          {@link
     *                          https://support.google.com/analytics/answer/6205902?hl=en#supported-currencies
     *                          Supported currencies and codes} list
     *
     * @return void
     * @throws Exception
     */
    public function trackTransactionItem($cid, $txnId, $name, $price = 0, $quantity = 1, $sku=null, $variation=null, $currency="USD")
    {
        if (!isset($txnId)) {
            throw new \InvalidArgumentException('txnId');
        }

        if (!isset($name) || !is_string($name) || strlen($name) === 0) {
            throw new \InvalidArgumentException('name');
        }

        if (isset($price) && (!is_numeric($price) || $price < 0)) {
            throw new \InvalidArgumentException('price');
        }

        if (isset($quantity) && (!is_integer($quantity) || $quantity < 1)) {
            throw new \InvalidArgumentException('quantity');
        }

        if (isset($sku) && (!is_string($variation) || strlen($variation) === 0)) {
            throw new \InvalidArgumentException('sku');
        }

        if (isset($variation) && (!is_string($variation) || strlen($variation) === 0)) {
            throw new \InvalidArgumentException('variation');
        }

        // TODO: Drop this poor regex and validate properly against ISO 4217 - http://www.iso.org/iso/home/standards/currency_codes.htm
        if (isset($currency) && (!is_string($currency) || strlen($currency) != 3)) {
            throw new \InvalidArgumentException('currency');
        }

        $data = array(
            "cid" => $cid,
            "ti"  => $txnId,
            "in"  => $name
        );

        if (isset($price)) {
            $data["ip"] = $price;
        }

        if (isset($quantity)) {
            $data["iq"] = $quantity;
        }

        if (isset($sku)) {
            $data["ic"] = $sku;
        }

        if (isset($variation)) {
            $data["iv"] = $variation;
        }

        if (isset($currency)) {
            $data["cu"] = $currency;
        }

        $this->track(self::ITEM, self::ITEM, $data);
    }

    /**
     * Tracks social network like interactions.
     *
     * @param string $cid Client ID.
     * @param string $action Social Action.
     * @param string $network Social Network.
     * @param string $target Social Target.
     *
     * @return void
     * @throws Exception
     */
    public function trackSocial($cid, $action, $network, $target)
    {
        if (!isset($action) || !is_string($action)) {
            throw new \InvalidArgumentException('action');
        }

        if (!isset($network) || !is_string($network)) {
            throw new \InvalidArgumentException('network');
        }

        if (!isset($target) || !is_string($target)) {
            throw new \InvalidArgumentException('target');
        }

        $data = array(
            "cid" => $cid,
            "sa"  => $action,
            "sn"  => $network,
            "st"  => $target
        );

        $this->track(self::SOCIAL, self::SOCIAL, $data);
    }

    /**
     * Tracks Exceptions.
     *
     * @param string $cid Client ID.
     * @param string|Exception $ex Exception description.
     * @param boolean $isFatal Exception is fatal?
     *
     * @return void
     * @throws Exception
     */
    public function trackException($cid, $ex, $isFatal = false)
    {
        if (!is_bool($isFatal)) {
            throw new \InvalidArgumentException('isFatal');
        }

        if (!is_string($ex) && !($ex instanceof Exception)) {
            throw new \InvalidArgumentException('ex');
        }

        $data = [
            'cid' => $cid,
            'exd' => is_string($ex) ? $ex : $ex->getMessage(),
            'exf' => $isFatal
        ];

        $data["tid"] = $this->options["appTrackingId"];

        $this->track(self::EXCEPTION, self::EXCEPTION, $data);
    }

    /**
     * Tracks User Timing
     *
     * @param string $cid Client ID.
     * @param string $category Timing category
     * @param string $variable Timing variable.
     * @param int $time Timing time.
     * @param string $label Timing label.
     *                                     These values
     *                                     are part of
     *                                     browser load
     *                                     times
     * @param int $dnsLoadTime DNS load time.
     * @param int $pageDownloadTime Page download time.
     * @param int $redirectResponseTime Redirect time.
     * @param int $tcpConnectTime TCP connect time.
     * @param int $serverResponseTime Server response time.
     *
     * @return void
     * @throws Exception
     */
    public function trackUserTiming($cid, $category, $variable, $time, $label = null, $dnsLoadTime = null, $pageDownloadTime = null, $redirectResponseTime = null, $tcpConnectTime = null, $serverResponseTime = null)
    {
        if (!isset($category) || !is_string($category)) {
            throw new \InvalidArgumentException('category');
        }

        if (!isset($variable) || !is_string($variable)) {
            throw new \InvalidArgumentException('variable');
        }

        if (!isset($time) || !is_integer($time)) {
            throw new \InvalidArgumentException('time');
        }

        if (isset($label) && !is_string($label)) {
            throw new \InvalidArgumentException('category');
        }

        if (isset($dnsLoadTime) && !is_integer($dnsLoadTime)) {
            throw new \InvalidArgumentException('$dnsLoadTime');
        }

        if (isset($pageDownloadTime) && !is_integer($pageDownloadTime)) {
            throw new \InvalidArgumentException('pageDownloadTime');
        }

        if (isset($redirectResponseTime) && !is_integer($redirectResponseTime)) {
            throw new \InvalidArgumentException('redirectResponseTime');
        }

        if (isset($tcpConnectTime) && !is_integer($tcpConnectTime)) {
            throw new \InvalidArgumentException('tcpConnectTime');
        }

        if (isset($serverResponseTime) && !is_integer($serverResponseTime)) {
            throw new \InvalidArgumentException('serverResponseTime');
        }

        $data = array(
            "cid" => $cid,
            "utc" => $category,
            "utv" => $variable,
            "utt" => $time
        );

        if (isset($label)) {
            $data["utl"] = $label;
        }

        if (isset($dnsLoadTime)) {
            $data["dns"] = $dnsLoadTime;
        }

        if (isset($pageDownloadTime)) {
            $data["pdt"] = $pageDownloadTime;
        }

        if (isset($redirectResponseTime)) {
            $data["rrt"] = $redirectResponseTime;
        }

        if (isset($tcpConnectTime)) {
            $data["tcp"] = $tcpConnectTime;
        }

        if (isset($serverResponseTime)) {
            $data["srt"] = $serverResponseTime;
        }

        $this->track(self::TIMING, self::TIMING, $data);
    }

    /**
     * Tracks app / screen views
     *
     * @param string $cid Client ID.
     * @param string $appName App name
     * @param string $appVersion App version.
     * @param string $appId App Id.
     * @param string $appInstallerId App Installer Id.
     * @param string $screenName Screen name / content description.
     *
     * @return void
     * @throws Exception
     */
    public function trackScreenView($cid, $appName, $appVersion, $appId, $appInstallerId, $screenName)
    {
        $data = array(
            "cid"  => $cid,
            "an"   => $appName,
            "av"   => $appVersion,
            "aid"  => $appId,
            "aiid" => $appInstallerId,
            "cd"   => $screenName
        );

        $this->track(self::SCREEN_VIEW, self::SCREEN_VIEW, $data);
    }
}
