<?php
// namespace Uxmz\Ga;

// use Psr\Log\LoggerInterface;

/**
 * Google Analytics Measurement Protocol Tracker Implementation.
 *
 * @see https://ga-dev-tools.appspot.com/hit-builder/
 * @see https://developers.google.com/analytics/devguides/collection/protocol/v1/parameters#cid
 * @see http://hayageek.com/php-curl-post-get/
 */
class Tracker {

    /**
     * Indicates if this tracker is initialized.
     * @type {Boolean}
     */
    protected $_initialized = false;

    /**
     * Class default options.
     * @type {Array}
     */
    protected $_defaults = array(
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

        "userTraits" => array(),  // Enabled / valid user traits

        "geoid" => 'MZ',

        "anonymizeIP" => true,  // If true, users IP addresses will be anonymized.
        "enabled" => true,  // If true, telemetry data is not collected or sent. Default true.
        // If true, data is sent immediately and not batched.
        // Hits sent with debug will not show up in reports. They are for debugging only.
        "debug" => false,
        "log" => false,  // If true, data is logged before sending
    );

    /**
     * Class options after initialization.
     * @type {Array}
     */
    protected $_options = array();

    /**
     * The events queue.
     *
     * To optmize usage, the telemetry clients batches the events to send in this
     * Queue and then sends the data in fixed time intervals or whenever
     * the `maxBatchSize` limit is reached.
     * @type {Array}
     */
    protected $_eventsQueue = array();

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
    public $EVENT_TYPES = array(self::EVENT, self::EXCEPTION, self::PAGE_VIEW, self::SCREEN_VIEW, self::TRANSACTION, self::ITEM, self::SOCIAL, self::TIMING);

    /**
     * Log message prefix.
     */
    const TRACKING_LOG = "Tracking Log: ";

    /**
     * The logger instance.
     * @var Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Checks if we can track or not.
     * @return {Boolean}
     */
    protected function _canTrack()
    {
        return $this->_options["enabled"] === true;
    }

    /**
     * Stores events we want to track in queue.
     * @param  {String} eventType the type of thing we want to test
     * @param  {string} name    the name of the event
     * @param  {string} data    the data of the event
     * @param  {Array} props   Array of key-value (string/string) pair of properties related to the event
     * @param  {Array} metrics Array of key-value (string/double) pair of metrics related to the event
     * @return {void}
     */
    protected function _track($eventType, $name, $data = array(), $props = array(), $metrics = array())
    {
        if ( !$this->_initialized )
            return $this->_log_error(sprintf("%s %s is not initialized", self::TRACKING_LOG, __FUNCTION__));

        if ( !$this->_canTrack() )
            return $this->_log_error(sprintf("%s %s is not enabled", self::TRACKING_LOG, __FUNCTION__));

        if ( !$this->_validateProps($props) )
            return $this->_log_error(sprintf("%s %s given properties are invalid", self::TRACKING_LOG, __FUNCTION__));

        if ( !$this->_validateMetrics($metrics) )
            return $this->_log_error(sprintf("%s %s given metrics are invalid", self::TRACKING_LOG, __FUNCTION__));

        if ($this->_options["log"] === true) {
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

            $this->_log_info(sprintf("%s => Type: %s \nName: %s \nData -> %s \nProperties -> %s \nMetrics -> %s", self::TRACKING_LOG, $eventType, $name, $dataString, $propsString, $metricsString));
        }

        if ($eventType === self::NON_INTERACTIVE)
            $data["ni"] = true;
        else
            $data["t"] = $eventType;

        if ($eventType === self::EXCEPTION)
            $data["tid"] = $this->_options["appTrackingId"] ? $this->_options["appTrackingId"] : $this->_options["webTrackingId"];

        // Proxy-Overrides
        // ---
        if (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $this->_get_client_ip(true))) {
            $data["uip"] = $this->_get_client_ip(true);
        }

        if (array_key_exists("HTTP_USER_AGENT", $_SERVER)) {
            $data["ua"] = $_SERVER["HTTP_USER_AGENT"]; // Note that Google has libraries to identify real user agents. Hand crafting your own agent could break at any time.
        }

        $data["geoid"] = $this->_options["geoid"]; // Maybe get from IP
        // $data["ul"] = visitor_language(); // TODO: Make user override this so he gets the language chosen by the user.

        // If there's a UID set in _ga cookie take it otherwise user internal if available
        if ($this->_getGAUID()) {
            $data["uid"] = $this->_getGAUID();
        }

        // Queue and flush
        // ---
        $this->_eventsQueue[] = array(
            "time"      => strtotime("now"),
            "eventType" => $eventType,
            "name"      => $name,
            "data"      => $data,
            "props"     => $props,
            "metrics"   => $metrics,
        );

        if ($this->_options["debug"] === true || $this->_options["batching"] === false || count($this->_eventsQueue) >= $this->_options["maxBatchHit"]) {
            $this->_flush();
        }
    }

    /**
     * Immediately send all queued telemetry data.
     * Should be asynchronous.
     *
     * @return {void}
     */
    protected function _flush()
    {
        // Don't flush if no hits exist.
        $hitCount = count($this->_eventsQueue);
        if ($hitCount <= 0)
            return;

        $url = ($this->_options["ssl"] === true ? "https://" : "http://") . $this->_options["host"] . ($this->_options["debug"] === true ? "/debug" : "");
        $headers = array();
        $body = array("v" => $this->_options["apiVersion"]);
        $post = array();

        if ( !array_key_exists("tid", $body) )
            $body["tid"] = $this->_options["webTrackingId"];

        if ($this->_options["anonymizeIP"] === true)
            $body["aip"] = 1;

        // Cache buster
        $body["z"] = strtotime("now");

        if ($hitCount === 1) {
            $url .= $this->_options["hit-url"];
            $body = array_merge($body, $this->_eventsQueue[0]["data"]);
            if ( !array_key_exists("cid", $body) || !$this->_is_guid($body["cid"]) )
                $body["cid"] = $this->_options["clientId"];
        } else {
            $url .= $this->_options["batch-url"];
            foreach ($this->_eventsQueue as $key => $event) {
                if ( !array_key_exists("cid", $event["data"]) || !$this->_is_guid($event["data"]["cid"]) )
                    $event["data"]["cid"] = $this->_options["clientId"];
                $post[] = http_build_query(array_merge($event["data"], $body));
            }
            $post = rtrim(implode("\r\n", $post), "\r\n");
        }

        $req = curl_init(); // create curl resource

        if ($hitCount === 1) {
            curl_setopt($req, CURLOPT_URL, $url . "?" . http_build_query($body)); // set url
        } else {
            curl_setopt($req, CURLOPT_URL, $url); // set url
            curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($req, CURLOPT_POSTFIELDS, $post);
            curl_setopt($req, CURLOPT_HTTPHEADER, array(
                "cache-control: no-cache",
                "content-type: text/html"
            ));
        }

        curl_setopt($req, CURLOPT_VERBOSE, true);
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true); //return the transfer as a string
        curl_setopt($req, CURLOPT_ENCODING, "");
        curl_setopt($req, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
        curl_setopt($req, CURLOPT_MAXREDIRS, 10); //only 2 redirects
        curl_setopt($req, CURLOPT_TIMEOUT, 30);
        curl_setopt($req, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($req, CURLOPT_FRESH_CONNECT, true); // Always ensure the connection is fresh
        curl_setopt($req, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($req); // execute the curl command

        if ($res === false) {
            $this->_log_error(sprintf("%s cURL error (%d): %s", self::TRACKING_LOG, curl_errno($req), curl_error($req)));
        } else {
            if ($this->_options["log"] === true) {
                $this->_log_error(sprintf("%s %s response %s\n", self::TRACKING_LOG, __FUNCTION__, var_export(json_decode($res, true), true)));
            }
        }

        curl_close($req); // close the connection
        $this->_eventsQueue = array(); // clear queue.
    }

    // Helpers
    // ---

    protected function _validateProps($props)
    {
        if ( !is_array($props) )
            return false;

        foreach ($props as $prop) {
            if ( !is_string($prop) )
                return false;
        }

        return true;
    }

    protected function _validateMetrics($metrics)
    {
        if ( !is_array($metrics) )
            return false;

        foreach ($metrics as $metric) {
            if ( !is_numeric($metric) )
                return false;
        }

        return true;
    }

    protected function _validateOptions($options)
    {
        if (!is_array($options))
            return false;

        // Make sure tid is valid with regex
        if (array_key_exists("appTrackingId", $options)) {
            if (!preg_match("/^(UA)-\d{4,10}-\d{1,4}$/", $options["appTrackingId"]))
                return false;
        }

        if (array_key_exists("webTrackingId", $options)) {
            if (!preg_match("/^(UA)-\d{4,10}-\d{1,4}$/", $options["webTrackingId"]))
                return false;
        }

        return true;
    }

    /**
     * Checks if a given string is in Guid format.
     *
     * When sending hits to google the cid param must be in GUID/UUID format
     * as per RFC4122 (http://www.ietf.org/rfc/rfc4122.txt)
     *
     * @param  string  $guid [description]
     * @return boolean       [description]
     */
    protected function _is_guid($guid)
    {
        return !empty($guid) && preg_match('/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/i', $guid);
    }

    /**
     * Get Google Analytics UID
     * Taken from http://stackoverflow.com/questions/16102436/what-are-the-values-in-ga-cookie
     * @return int
     */
    protected function _getGAUID()
    {
        $uid = 0;
        if ( isset($_COOKIE['__utma']) ) {
            list($hash_domain, $uid, $first_visit, $prew_visit, $time_start, $num_visits) = sscanf($_COOKIE['__utma'], '%d.%d.%d.%d.%d.%d');
        } elseif ( isset($_COOKIE['_ga']) ) {
            list($c_format, $c_domain, $uid, $first_visit) = sscanf($_COOKIE['_ga'], 'GA%d.%d.%d.%d');
        }

        return $uid;
    }

    protected function _get_client_ip($forwarded=true)
    {
        $addr = !$forwarded || empty($_SERVER['X-Forwarded-For'])
                    ? $_SERVER['REMOTE_ADDR']
                    : trim(preg_replace('/,.*$/', '', $_SERVER['X-Forwarded-For']));


        if (!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $addr))
            $addr = gethostbyname($addr);

        return $addr;
    }

    protected function _log_info($message)
    {
        if ($this->_logger) {
            $this->_logger->info($message);
        } else {
            error_log($message);
        }
    }

    protected function _log_error($message)
    {
        if (isset($this->_logger)) {
            $this->_logger->error($message);
        } else {
            error_log($message);
        }
    }

    // Public API
    // ---

    public function __construct($options = array(), /*LoggerInterface*/ $logger = null)
    {
        if (!is_callable('curl_init'))
            throw new Exception("Tracker class requires cURL to be enabled!");

        if (!is_array($options))
            throw new Exception("Tracker options must be provided in array format!");

        if (!$this->_validateOptions($options))
            throw new Exception("Tracker initializatin options are invalid!");

        $this->_options = array_merge($this->_defaults, $options);

        $this->_logger = $logger;
        $this->_initialized = true;
    }

    /**
     * Does the approriate sending of the collected events to Houston on demand.
     * @return {void}
     */
    public function flush()
    {
        $this->_flush();
    }

    /** Add more context data otherwise it's meaningless */
    public function startSession($cid)
    {
        if ( isset($cid) && !$this->_is_guid($cid) )
            return $this->_log_error(sprintf("%s %s invalid param \$cid", self::TRACKING_LOG, __FUNCTION__));

        $data = array(
            "cid" => $cid,
            "sc"  => "start",
            "dp"  => "/",
        );

        $this->_track(self::NON_INTERACTIVE, self::NON_INTERACTIVE, $data, array(), array());
    }

    /** Add more context data otherwise it's meaningless */
    public function endSession($cid)
    {
        if ( isset($cid) && !$this->_is_guid($cid) )
            return $this->_log_error(sprintf("%s %s invalid param \$cid", self::TRACKING_LOG, __FUNCTION__));

        $data = array(
            "cid" => $cid,
            "sc"  => "end",
            "dp"  => "/",
        );

        $this->_track(self::NON_INTERACTIVE, self::NON_INTERACTIVE, $data, array(), array());
    }

    public function trackPageView($cid, $hostname, $page, $title)
    {
        if ( isset($cid) && !$this->_is_guid($cid) )
            return $this->_log_error(sprintf("%s %s invalid param \$cid", self::TRACKING_LOG, __FUNCTION__));

        if ( !is_string($hostname) || empty($hostname) )
            return $this->_log_error(sprintf("%s %s invalid param \$hostname", self::TRACKING_LOG, __FUNCTION__));

        if ( !is_string($page) || empty($page) )
            return $this->_log_error(sprintf("%s %s invalid param \$page", self::TRACKING_LOG, __FUNCTION__));

        if ( !is_string($title) || empty($title) )
            return $this->_log_error(sprintf("%s %s invalid param \$title", self::TRACKING_LOG, __FUNCTION__));

        $data = array(
            "cid" => $cid,
            "dh"  => $hostname,
            "dp"  => $page[0] === "/" ? $page : "/" . $page,
            "dt"  => $title
        );

        $this->_track(self::PAGE_VIEW, self::PAGE_VIEW, $data, array(), array());
    }

    public function trackEvent($cid, $category, $action, $label = null, $value = null)
    {
        if ( isset($cid) && !$this->_is_guid($cid) )
            return $this->_log_error(sprintf("%s %s invalid param \$cid", self::TRACKING_LOG, __FUNCTION__));

        if (!isset($category) || !is_string($category) || empty($category))
            return $this->_log_error(sprintf("%s %s invalid param \$category", self::TRACKING_LOG, __FUNCTION__));

        if (!isset($action) || !is_string($action) || empty($action))
            return $this->_log_error(sprintf("%s %s invalid param \$action", self::TRACKING_LOG, __FUNCTION__));

        if (isset($label) && !is_string($label))
            return $this->_log_error(sprintf("%s %s invalid param \$label", self::TRACKING_LOG, __FUNCTION__));

        if (isset($value) && !is_int($value))
            return $this->_log_error(sprintf("%s %s invalid param \$value", self::TRACKING_LOG, __FUNCTION__));

        $data = array(
            "cid" => $cid,
            "ec"  => $category,
            "ea"  => $action
        );

        if (isset($label))
            $data["el"] = $label;

        if (isset($value))
            $data["ev"] = $value;

        $this->_track(self::EVENT, self::EVENT, $data, array(), array());
    }

    // Enhanced e-Commerce tracking
    // ---

    // e-Commerce tracking
    // ---

    // To send ecommerce data, send one transaction hit to represent an entire transaction,
    // then send an item hit for each item in the transaction.
    // The transaction ID ti links all the hits together to represent the entire purchase.

    public function trackTransaction($cid, $txnId, $affiliation = "Biscate", $revenue = 0, $shipping = 0, $tax = 0, $currency="MZN")
    {
        if ( isset($cid) && !$this->_is_guid($cid) )
            return $this->_log_error(sprintf("%s %s invalid param \$cid", self::TRACKING_LOG, __FUNCTION__));

        if ( !isset($txnId) )
            return $this->_log_error(sprintf("%s %s invalid param \$txnId", self::TRACKING_LOG, __FUNCTION__));

        if ( isset($affiliation) && (!is_string($affiliation) || strlen($affiliation) === 0))
            return $this->_log_error(sprintf("%s %s invalid param \$affiliation", self::TRACKING_LOG, __FUNCTION__));

        if ( isset($revenue) && (!is_numeric($revenue) || $revenue < 0) )
            return $this->_log_error(sprintf("%s %s invalid param \$revenue", self::TRACKING_LOG, __FUNCTION__));

        if ( isset($shipping) && (!is_numeric($shipping) || $shipping < 0) )
            return $this->_log_error(sprintf("%s %s invalid param \$shipping", self::TRACKING_LOG, __FUNCTION__));

        if ( isset($tax) && (!is_numeric($tax) || $tax < 0) )
            return $this->_log_error(sprintf("%s %s invalid param \$tax", self::TRACKING_LOG, __FUNCTION__));

        // TODO: Drop this poor regex and validate properly against ISO 4217 - http://www.iso.org/iso/home/standards/currency_codes.htm
        if ( isset($currency) && (!is_string($currency) || strlen($currency) != 3) )
            return $this->_log_error(sprintf("%s %s invalid param \$currency", self::TRACKING_LOG, __FUNCTION__));

        $data = array(
            "cid" => $cid,
            "ti"  => $txnId
        );

        if (isset($affiliation))
            $data["ta"] = $affiliation;

        if (isset($revenue))
            $data["tr"] = $revenue;

        if (isset($shipping))
            $data["ts"] = $shipping;

        if (isset($tax))
            $data["tt"] = $tax;

        if (isset($currency))
            $data["cu"] = $currency;

        $this->_track(self::TRANSACTION, self::TRANSACTION, $data, array(), array());
    }

    public function trackTransactionItem($cid, $txnId, $name, $price = 0, $quantity = 1, $sku=NULL, $variation=NULL, $currency="MZN")
    {
        if ( isset($cid) && !$this->_is_guid($cid) )
            return $this->_log_error(sprintf("%s %s invalid param \$cid", self::TRACKING_LOG, __FUNCTION__));

        if ( !isset($txnId) )
            return $this->_log_error(sprintf("%s %s invalid param \$txnId", self::TRACKING_LOG, __FUNCTION__));

        if ( !isset($name) || !is_string($name) || strlen($name) === 0 )
            return $this->_log_error(sprintf("%s %s invalid param \$name", self::TRACKING_LOG, __FUNCTION__));

        if ( isset($price) && (!is_numeric($price) || $price < 0) )
            return $this->_log_error(sprintf("%s %s invalid param \$price", self::TRACKING_LOG, __FUNCTION__));

        if ( isset($quantity) && (!is_integer($quantity) || $quantity < 1) )
            return $this->_log_error(sprintf("%s %s invalid param \$quantity", self::TRACKING_LOG, __FUNCTION__));

        if ( isset($sku) && (!is_string($variation) || strlen($variation) === 0) )
            return $this->_log_error(sprintf("%s %s invalid param \$sku", self::TRACKING_LOG, __FUNCTION__));

        if ( isset($variation) && (!is_string($variation) || strlen($variation) === 0) )
            return $this->_log_error(sprintf("%s %s invalid param \$variation", self::TRACKING_LOG, __FUNCTION__));

        // TODO: Drop this poor regex and validate properly against ISO 4217 - http://www.iso.org/iso/home/standards/currency_codes.htm
        if ( isset($currency) && (!is_string($currency) || strlen($currency) != 3 ) )
            return $this->_log_error(sprintf("%s %s invalid param \$currency", self::TRACKING_LOG, __FUNCTION__));

        $data = array(
            "cid" => $cid,
            "ti"  => $txnId,
            "in"  => $name
        );

        if (isset($price))
            $data["ip"] = $price;

        if (isset($quantity))
            $data["iq"] = $quantity;

        if (isset($sku))
            $data["ic"] = $sku;

        if (isset($variation))
            $data["iv"] = $variation;

        if (isset($currency))
            $data["cu"] = $currency;

        $this->_track(self::ITEM, self::ITEM, $data, array(), array());
    }

    public function trackSocial($cid, $action, $network, $target)
    {
        if ( isset($cid) && !$this->_is_guid($cid) )
            return $this->_log_error(sprintf("%s %s invalid param \$cid", self::TRACKING_LOG, __FUNCTION__));

        if (!isset($action) || !is_string($action))
            return $this->_log_error(sprintf("%s %s invalid param \$action", self::TRACKING_LOG, __FUNCTION__));

        if (!isset($network) || !is_string($network))
            return $this->_log_error(sprintf("%s %s invalid param \$network", self::TRACKING_LOG, __FUNCTION__));

        if  (!isset($target) || !is_string($target))
            return $this->_log_error(sprintf("%s %s invalid param \$target", self::TRACKING_LOG, __FUNCTION__));

        $data = array(
            "cid" => $cid,
            "sa"  => $action,
            "sn"  => $network,
            "st"  => $target
        );

        $this->_track(self::SOCIAL, self::SOCIAL, $data, array(), array());
    }

    public function trackException($cid, $ex = null, $isFatal = null)
    {
        if ( isset($cid) && !$this->_is_guid($cid) )
            return $this->_logger->error(sprintf("%s %s invalid param \$cid", self::TRACKING_LOG, __FUNCTION__));

        if (!isset($isFatal) || !is_bool($isFatal) )
            return $this->_logger->error(sprintf("%s %s invalid param \$isFatal", self::TRACKING_LOG, __FUNCTION__));

        $data = array("cid" => $cid);

        if (isset($ex)) {
            if (is_subclass_of($ex, "Exception"))
                $data["exd"] = $ex->getMessage();

            elseif (is_string($ex))
                $data["exd"] = $ex;
            else
                return $this->_logger->error(sprintf("%s %s invalid param type for \$ex", self::TRACKING_LOG, __FUNCTION__));
        } else {
            return $this->_logger->error(sprintf("%s %s invalid param \$ex", self::TRACKING_LOG, __FUNCTION__));
        }

        if (isset($isFatal))
            $data["exf"] = $isFatal;

        $data["tid"] = $this->_options["appTrackingId"];

        $this->_track(self::EXCEPTION, self::EXCEPTION, $data, array(), array());
    }

    public function trackUserTiming($cid, $category, $variable, $time, $label = null, $dnsLoadTime = null, $pageDownloadTime = null, $redirectResponseTime = null, $tcpConnectTime = null, $serverResponseTime = null)
    {
        if ( isset($cid) && !$this->_is_guid($cid) )
            return $this->_logger->error(sprintf("%s %s invalid param \$cid", self::TRACKING_LOG, __FUNCTION__));

        if (!isset($category) || !is_string($category))
            return $this->_logger->error(sprintf("%s %s invalid param \$category", self::TRACKING_LOG, __FUNCTION__));

        if (!isset($variable) || !is_string($variable))
            return $this->_logger->error(sprintf("%s %s invalid param \$variable", self::TRACKING_LOG, __FUNCTION__));

        if (!isset($time) || !is_integer($time))
            return $this->_logger->error(sprintf("%s %s invalid param \$time", self::TRACKING_LOG, __FUNCTION__));

        if (isset($label) && !is_string($label))
            return $this->_logger->error(sprintf("%s %s invalid param \$category", self::TRACKING_LOG, __FUNCTION__));

        if (isset($dnsLoadTime) && !is_integer($dnsLoadTime))
            return $this->_logger->error(sprintf("%s %s invalid param \$dnsLoadTime", self::TRACKING_LOG, __FUNCTION__));

        if (isset($pageDownloadTime) && !is_integer($pageDownloadTime))
            return $this->_logger->error(sprintf("%s %s invalid param \$pageDownloadTime", self::TRACKING_LOG, __FUNCTION__));

        if (isset($redirectResponseTime) && !is_integer($redirectResponseTime))
            return $this->_logger->error(sprintf("%s %s invalid param \$redirectResponseTime", self::TRACKING_LOG, __FUNCTION__));

        if (isset($tcpConnectTime) && !is_integer($tcpConnectTime))
            return $this->_logger->error(sprintf("%s %s invalid param \$tcpConnectTime", self::TRACKING_LOG, __FUNCTION__));

        if (isset($serverResponseTime) && !is_integer($serverResponseTime))
            return $this->_logger->error(sprintf("%s %s invalid param \$serverResponseTime", self::TRACKING_LOG, __FUNCTION__));

        $data = array(
            "cid" => $cid,
            "utc" => $category,
            "utv" => $variable,
            "utt" => $time
        );

        if (isset($label))
            $data["utl"] = $label;

        if (isset($dnsLoadTime))
            $data["dns"] = $dnsLoadTime;

        if (isset($pageDownloadTime))
            $data["pdt"] = $pageDownloadTime;

        if (isset($redirectResponseTime))
            $data["rrt"] = $redirectResponseTime;

        if (isset($tcpConnectTime))
            $data["tcp"] = $tcpConnectTime;

        if (isset($serverResponseTime))
            $data["srt"] = $serverResponseTime;

        $this->_track(self::USER_TIMING, self::USER_TIME, $data, array(), array());
    }

    public function trackScreenView($appName, $appVersion, $appId, $appInstallerId, $screenName)
    {
        if ( isset($cid) && !$this->_is_guid($cid) )
            return $this->_logger->error(sprintf("%s %s invalid param \$cid", self::TRACKING_LOG, __FUNCTION__));

        $data = array(
            "cid"  => $cid,
            "an"   => $appName,
            "av"   => $appVersion,
            "aid"  => $appId,
            "aiid" => $appInstallerId,
            "cd"   => $screenName
        );

        $this->_track(self::SCREEN_VIEW, self::SCREEN_VIEW, $data, array(), array());
    }
}

?>
