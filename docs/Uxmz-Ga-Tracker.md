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

    const EVENT_TYPES = array(self::EVENT, self::EXCEPTION, self::PAGE_VIEW, self::SCREEN_VIEW, self::TRANSACTION, self::ITEM, self::SOCIAL, self::TIMING)





### TRACKING_LOG

    const TRACKING_LOG = "Tracking Log: "





Properties
----------


### $_initialized

    protected boolean $_initialized = false

Indicates if this tracker is initialized.



* Visibility: **protected**


### $_defaults

    protected array $_defaults = array("applicationName" => "My-Awesome-App-Name", "ssl" => true, "host" => "www.google-analytics.com", "hit-url" => "/collect", "batch-url" => "/batch", "apiVersion" => 1, "clientId" => 555, "appTrackingId" => "UA-XXXXXXXX-X", "webTrackingId" => "UA-XXXXXXXX-X", "batching" => false, "maxBatchHit" => 20, "maxBatchPayloadSize" => 16, "maxHitPayloadSize" => 8, "maxHitsPerDay" => 200000, "maxHitsPerMonth" => 10000000, "maxHitsPerSession" => 500, "userTraits" => array(), "geoid" => 'MZ', "anonymizeIP" => true, "enabled" => true, "debug" => false, "log" => false, "proxies" => array())

Class default options.



* Visibility: **protected**


### $_options

    protected array $_options = array()

Class options after initialization.



* Visibility: **protected**


### $_eventsQueue

    protected array $_eventsQueue = array()

The events queue.

To optimize usage, the telemetry clients batches the events to send in this
Queue and then sends the data in fixed time intervals or whenever
the `maxBatchSize` limit is reached.

* Visibility: **protected**


### $_logger

    protected \Psr\Log\LoggerInterface $_logger

The logger instance.



* Visibility: **protected**


Methods
-------


### __construct

    mixed Uxmz\Ga\Tracker::__construct(array $options, \Psr\Log\LoggerInterface|null $logger)

Tracker constructor.



* Visibility: **public**


#### Arguments
* $options **array** - &lt;p&gt;the tracker initialization options.&lt;/p&gt;
* $logger **Psr\Log\LoggerInterface|null** - &lt;p&gt;logger instance use to log errors during run-time.&lt;/p&gt;



### flush

    void Uxmz\Ga\Tracker::flush()

Does the appropriate sending of the collected events to "Houston" on demand.



* Visibility: **public**




### startSession

    mixed Uxmz\Ga\Tracker::startSession(string $cid)

Tracks the session starting time for a given client ID.

Add more context data otherwise it's meaningless

* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;



### endSession

    mixed Uxmz\Ga\Tracker::endSession(string $cid)

Tracks the session ending time for a given client ID.

Add more context data otherwise it's meaningless

* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;



### trackPageView

    mixed Uxmz\Ga\Tracker::trackPageView(string $cid, string $hostname, string $page, string $title)

Tracks page views.



* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;
* $hostname **string** - &lt;p&gt;Document hostname.&lt;/p&gt;
* $page **string** - &lt;p&gt;Page.&lt;/p&gt;
* $title **string** - &lt;p&gt;Title.&lt;/p&gt;



### trackEvent

    mixed Uxmz\Ga\Tracker::trackEvent(string $cid, string $category, string $action, string $label, string $value)

Tracks Events;



* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;
* $category **string** - &lt;p&gt;Event Category.&lt;/p&gt;
* $action **string** - &lt;p&gt;Event Action.&lt;/p&gt;
* $label **string** - &lt;p&gt;Event label.&lt;/p&gt;
* $value **string** - &lt;p&gt;Event value.&lt;/p&gt;



### trackTransaction

    mixed Uxmz\Ga\Tracker::trackTransaction(string $cid, string $txnId, string $affiliation, integer $revenue, integer $shipping, integer $tax, string $currency)

Tracks e-commerce transactions.



* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;
* $txnId **string** - &lt;p&gt;transaction ID.&lt;/p&gt;
* $affiliation **string** - &lt;p&gt;Transaction affiliation.&lt;/p&gt;
* $revenue **integer** - &lt;p&gt;Transaction revenue.&lt;/p&gt;
* $shipping **integer** - &lt;p&gt;Transaction shipping.&lt;/p&gt;
* $tax **integer** - &lt;p&gt;Transaction tax.&lt;/p&gt;
* $currency **string** - &lt;p&gt;Currency code.&lt;/p&gt;



### trackTransactionItem

    mixed Uxmz\Ga\Tracker::trackTransactionItem(string $cid, string $txnId, string $name, integer $price, integer $quantity, string $sku, string $variation, string $currency)

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
* $currency **string** - &lt;p&gt;Currency code.&lt;/p&gt;



### trackSocial

    mixed Uxmz\Ga\Tracker::trackSocial(string $cid, string $action, string $network, string $target)

Tracks social network like interactions.



* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;
* $action **string** - &lt;p&gt;Social Action.&lt;/p&gt;
* $network **string** - &lt;p&gt;Social Network.&lt;/p&gt;
* $target **string** - &lt;p&gt;Social Target.&lt;/p&gt;



### trackException

    null Uxmz\Ga\Tracker::trackException(string $cid, string|\Exception $ex, boolean $isFatal)

Tracks Exceptions.



* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;
* $ex **string|Exception** - &lt;p&gt;Exception description.&lt;/p&gt;
* $isFatal **boolean** - &lt;p&gt;Exception is fatal?&lt;/p&gt;



### trackUserTiming

    null Uxmz\Ga\Tracker::trackUserTiming(string $cid, string $category, string $variable, integer $time, string $label, integer $dnsLoadTime, integer $pageDownloadTime, integer $redirectResponseTime, integer $tcpConnectTime, integer $serverResponseTime)

Tracks User Timing



* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;
* $category **string** - &lt;p&gt;Timing category&lt;/p&gt;
* $variable **string** - &lt;p&gt;Timing variable.&lt;/p&gt;
* $time **integer** - &lt;p&gt;Timing time.&lt;/p&gt;
* $label **string** - &lt;p&gt;Timing label.

These values are part of browser load times&lt;/p&gt;
* $dnsLoadTime **integer** - &lt;p&gt;DNS load time.&lt;/p&gt;
* $pageDownloadTime **integer** - &lt;p&gt;Page download time.&lt;/p&gt;
* $redirectResponseTime **integer** - &lt;p&gt;Redirect time.&lt;/p&gt;
* $tcpConnectTime **integer** - &lt;p&gt;TCP connect time.&lt;/p&gt;
* $serverResponseTime **integer** - &lt;p&gt;Server response time.&lt;/p&gt;



### trackScreenView

    mixed Uxmz\Ga\Tracker::trackScreenView(string $cid, string $appName, string $appVersion, string $appId, string $appInstallerId, string $screenName)

Tracks app / screen views



* Visibility: **public**


#### Arguments
* $cid **string** - &lt;p&gt;Client ID.&lt;/p&gt;
* $appName **string** - &lt;p&gt;App name&lt;/p&gt;
* $appVersion **string** - &lt;p&gt;App version.&lt;/p&gt;
* $appId **string** - &lt;p&gt;App Id.&lt;/p&gt;
* $appInstallerId **string** - &lt;p&gt;App Installer Id.&lt;/p&gt;
* $screenName **string** - &lt;p&gt;Screen name / content description.&lt;/p&gt;


