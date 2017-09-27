Uxmz\Ga\Tracker
===============

Google Analytics Measurement Protocol Tracker Implementation.




* Class name: Tracker
* Namespace: Uxmz\Ga



Constants
----------


### NON_INTERACTIVE

    const NON_INTERACTIVE = "ni"





### EVENT

    const EVENT = "event"





### EXCEPTION

    const EXCEPTION = "exception"





### PAGE_VIEW

    const PAGE_VIEW = "pageview"





### SCREEN_VIEW

    const SCREEN_VIEW = "screenview"





### TRANSACTION

    const TRANSACTION = "transaction"





### ITEM

    const ITEM = "item"





### SOCIAL

    const SOCIAL = "social"





### TIMING

    const TIMING = "timing"





### EVENT_TYPES

    const EVENT_TYPES = array(self::NON_INTERACTIVE, self::EVENT, self::EXCEPTION, self::PAGE_VIEW, self::SCREEN_VIEW, self::TRANSACTION, self::ITEM, self::SOCIAL, self::TIMING)





### TRACKING_LOG

    const TRACKING_LOG = "Tracking Log: "





### TRACKER_ID_REGEXP

    const TRACKER_ID_REGEXP = "/^(UA)-\d{4,10}-\d{1,4}$/"





Properties
----------


### $initialized

    protected boolean $initialized = false

Indicates if this tracker is initialized.



* Visibility: **protected**


### $defaults

    protected array $defaults = array("applicationName" => "My-Awesome-App-Name", "ssl" => true, "host" => "www.google-analytics.com", "hit-url" => "/collect", "batch-url" => "/batch", "apiVersion" => 1, "clientId" => 555, "appTrackingId" => "UA-XXXXXXXX-X", "webTrackingId" => "UA-XXXXXXXX-X", "batching" => false, "maxBatchHit" => 20, "maxBatchPayloadSize" => 16, "maxHitPayloadSize" => 8, "maxHitsPerDay" => 200000, "maxHitsPerMonth" => 10000000, "maxHitsPerSession" => 500, "userTraits" => array(), "geoid" => 'MZ', "anonymizeIP" => true, "enabled" => true, "debug" => false, "log" => false, "proxies" => array())

Class default options.



* Visibility: **protected**


### $options

    protected array $options = array()

Class options after initialization.



* Visibility: **protected**


### $eventsQueue

    protected array $eventsQueue = array()

The events queue.

To optimize usage, the telemetry clients batches the events to send in this
Queue and then sends the data in fixed time intervals or whenever
the `maxBatchSize` limit is reached.

* Visibility: **protected**


### $logger

    protected \Psr\Log\LoggerInterface $logger

The logger instance.



* Visibility: **protected**


### $httpClient

    protected \GuzzleHttp\ClientInterface $httpClient

The http client



* Visibility: **protected**


Methods
-------


### track

    void Uxmz\Ga\Tracker::track(string $eventType, string $name, array $data, array $props, array $metrics)

Stores events we want to track in queue.



* Visibility: **protected**


#### Arguments
* $eventType **string** - &lt;p&gt;the type of thing we want to test&lt;/p&gt;
* $name **string** - &lt;p&gt;the name of the event name&lt;/p&gt;
* $data **array** - &lt;p&gt;the data of the event&lt;/p&gt;
* $props **array** - &lt;p&gt;Array of key-value (string/string) pair of properties related to the event&lt;/p&gt;
* $metrics **array** - &lt;p&gt;Array of key-value (string/double) pair of metrics related to the event&lt;/p&gt;



### _flush

    void Uxmz\Ga\Tracker::_flush()

Immediately send all queued telemetry data.



* Visibility: **protected**




### validateProps

    boolean Uxmz\Ga\Tracker::validateProps(array $props)

Validates given properties.



* Visibility: **protected**


#### Arguments
* $props **array** - &lt;p&gt;properties to be validated&lt;/p&gt;



### validateMetrics

    boolean Uxmz\Ga\Tracker::validateMetrics(array $metrics)

Validates the given metrics.



* Visibility: **protected**


#### Arguments
* $metrics **array** - &lt;p&gt;metrics to be validated&lt;/p&gt;



### validateOptions

    boolean Uxmz\Ga\Tracker::validateOptions(array $options)

Validates initialization options for the tracker.



* Visibility: **protected**


#### Arguments
* $options **array** - &lt;p&gt;list of options&lt;/p&gt;



### isGuid

    boolean Uxmz\Ga\Tracker::isGuid(string $guid)

Checks if a given string is in Guid format.

When sending hits to google the cid param must be in GUID/UUID format
as per RFC4122 (http://www.ietf.org/rfc/rfc4122.txt)

* Visibility: **protected**


#### Arguments
* $guid **string** - &lt;p&gt;the value to be checked&lt;/p&gt;



### isValidIp

    boolean Uxmz\Ga\Tracker::isValidIp(string $ip)

Check that a given string is a valid IP address.



* Visibility: **protected**


#### Arguments
* $ip **string** - &lt;p&gt;the value to be checked&lt;/p&gt;



### getGaUid

    integer Uxmz\Ga\Tracker::getGaUid()

Get Google Analytics UID
Taken from http://stackoverflow.com/questions/16102436/what-are-the-values-in-ga-cookie



* Visibility: **protected**




### getClientIp

    null|string Uxmz\Ga\Tracker::getClientIp(boolean $check_proxies, array $proxies)

Gets the current request's IP.



* Visibility: **protected**


#### Arguments
* $check_proxies **boolean** - &lt;p&gt;if it should also check proxies or not&lt;/p&gt;
* $proxies **array** - &lt;p&gt;the list of trusted proxies&lt;/p&gt;



### logDebug

    void Uxmz\Ga\Tracker::logDebug(string $message)

Logs a debug message.



* Visibility: **protected**


#### Arguments
* $message **string** - &lt;p&gt;the message to be logged&lt;/p&gt;



### logInfo

    void Uxmz\Ga\Tracker::logInfo(string $message)

Logs an info message.



* Visibility: **protected**


#### Arguments
* $message **string** - &lt;p&gt;the message to be logged&lt;/p&gt;



### logError

    void Uxmz\Ga\Tracker::logError(string $message)

Logs an error message.



* Visibility: **protected**


#### Arguments
* $message **string** - &lt;p&gt;the message to be logged&lt;/p&gt;



### __construct

    mixed Uxmz\Ga\Tracker::__construct(\GuzzleHttp\ClientInterface $httpClient, \Psr\Log\LoggerInterface|null $logger, array $options)

Tracker constructor.



* Visibility: **public**


#### Arguments
* $httpClient **GuzzleHttp\ClientInterface** - &lt;p&gt;http client used for making requests.&lt;/p&gt;
* $logger **Psr\Log\LoggerInterface|null** - &lt;p&gt;logger instance use to log errors during run-time.&lt;/p&gt;
* $options **array** - &lt;p&gt;the tracker initialization options.&lt;/p&gt;



### flush

    void Uxmz\Ga\Tracker::flush()

Does the appropriate sending of the collected events to "Houston" on demand.



* Visibility: **public**




### startSession

    void Uxmz\Ga\Tracker::startSession(string $cid)

Tracks the session starting time for a given client ID.

Add more context data otherwise it's meaningless

* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;



### endSession

    void Uxmz\Ga\Tracker::endSession(string $cid)

Tracks the session ending time for a given client ID.

Add more context data otherwise it's meaningless

* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;



### trackPageView

    void Uxmz\Ga\Tracker::trackPageView(string $cid, string $hostname, string $page, string $title)

Tracks page views.



* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;
* $hostname **string** - &lt;p&gt;Document hostname.&lt;/p&gt;
* $page **string** - &lt;p&gt;Page.&lt;/p&gt;
* $title **string** - &lt;p&gt;Title.&lt;/p&gt;



### trackEvent

    void Uxmz\Ga\Tracker::trackEvent(string $cid, string $category, string $action, string $label, string $value)

Tracks Events;



* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;
* $category **string** - &lt;p&gt;Event Category.&lt;/p&gt;
* $action **string** - &lt;p&gt;Event Action.&lt;/p&gt;
* $label **string** - &lt;p&gt;Event label.&lt;/p&gt;
* $value **string** - &lt;p&gt;Event value.&lt;/p&gt;



### trackTransaction

    void Uxmz\Ga\Tracker::trackTransaction(string $cid, string $txnId, string $affiliation, integer $revenue, integer $shipping, integer $tax, string $currency)

Tracks e-commerce transactions.



* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;
* $txnId **string** - &lt;p&gt;transaction ID.&lt;/p&gt;
* $affiliation **string** - &lt;p&gt;Transaction affiliation.&lt;/p&gt;
* $revenue **integer** - &lt;p&gt;Transaction revenue.&lt;/p&gt;
* $shipping **integer** - &lt;p&gt;Transaction shipping.&lt;/p&gt;
* $tax **integer** - &lt;p&gt;Transaction tax.&lt;/p&gt;
* $currency **string** - &lt;p&gt;Currency code. It should belong on the
{@link
&lt;a href=&quot;https://support.google.com/analytics/answer/6205902?hl=en#supported-currencies&quot;&gt;https://support.google.com/analytics/answer/6205902?hl=en#supported-currencies&lt;/a&gt;
Supported currencies and codes} list&lt;/p&gt;



### trackTransactionItem

    void Uxmz\Ga\Tracker::trackTransactionItem(string $cid, string $txnId, string $name, integer $price, integer $quantity, string $sku, string $variation, string $currency)

Tracks e-commerce transaction items



* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;
* $txnId **string** - &lt;p&gt;Transaction ID.&lt;/p&gt;
* $name **string** - &lt;p&gt;Item name.&lt;/p&gt;
* $price **integer** - &lt;p&gt;Item price.&lt;/p&gt;
* $quantity **integer** - &lt;p&gt;Item quantity.&lt;/p&gt;
* $sku **string** - &lt;p&gt;Item code / SKU.&lt;/p&gt;
* $variation **string** - &lt;p&gt;Item variation / category.&lt;/p&gt;
* $currency **string** - &lt;p&gt;Currency code. It should belong on the
{@link
&lt;a href=&quot;https://support.google.com/analytics/answer/6205902?hl=en#supported-currencies&quot;&gt;https://support.google.com/analytics/answer/6205902?hl=en#supported-currencies&lt;/a&gt;
Supported currencies and codes} list&lt;/p&gt;



### trackSocial

    void Uxmz\Ga\Tracker::trackSocial(string $cid, string $action, string $network, string $target)

Tracks social network like interactions.



* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;
* $action **string** - &lt;p&gt;Social Action.&lt;/p&gt;
* $network **string** - &lt;p&gt;Social Network.&lt;/p&gt;
* $target **string** - &lt;p&gt;Social Target.&lt;/p&gt;



### trackException

    void Uxmz\Ga\Tracker::trackException(string $cid, string|\Exception $ex, boolean $isFatal)

Tracks Exceptions.



* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;
* $ex **string|Exception** - &lt;p&gt;Exception description.&lt;/p&gt;
* $isFatal **boolean** - &lt;p&gt;Exception is fatal?&lt;/p&gt;



### trackUserTiming

    void Uxmz\Ga\Tracker::trackUserTiming(string $cid, string $category, string $variable, integer $time, string $label, integer $dnsLoadTime, integer $pageDownloadTime, integer $redirectResponseTime, integer $tcpConnectTime, integer $serverResponseTime)

Tracks User Timing



* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;
* $category **string** - &lt;p&gt;Timing category&lt;/p&gt;
* $variable **string** - &lt;p&gt;Timing variable.&lt;/p&gt;
* $time **integer** - &lt;p&gt;Timing time.&lt;/p&gt;
* $label **string** - &lt;p&gt;Timing label.
                                    These values
                                    are part of
                                    browser load
                                    times&lt;/p&gt;
* $dnsLoadTime **integer** - &lt;p&gt;DNS load time.&lt;/p&gt;
* $pageDownloadTime **integer** - &lt;p&gt;Page download time.&lt;/p&gt;
* $redirectResponseTime **integer** - &lt;p&gt;Redirect time.&lt;/p&gt;
* $tcpConnectTime **integer** - &lt;p&gt;TCP connect time.&lt;/p&gt;
* $serverResponseTime **integer** - &lt;p&gt;Server response time.&lt;/p&gt;



### trackScreenView

    void Uxmz\Ga\Tracker::trackScreenView(string $cid, string $appName, string $appVersion, string $appId, string $appInstallerId, string $screenName)

Tracks app / screen views



* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;
* $appName **string** - &lt;p&gt;App name&lt;/p&gt;
* $appVersion **string** - &lt;p&gt;App version.&lt;/p&gt;
* $appId **string** - &lt;p&gt;App Id.&lt;/p&gt;
* $appInstallerId **string** - &lt;p&gt;App Installer Id.&lt;/p&gt;
* $screenName **string** - &lt;p&gt;Screen name / content description.&lt;/p&gt;


