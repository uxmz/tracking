# Google Analytics Measurement Protocol

Provides a Google Analytics Measurement Protocol implementation 

## Usage

Create a new tracker and then send events to google analytics by invoking the
tracker's `track_*` methods like shown bellow

```php
$httpClient = new GuzzleHttp\Client(); // we use guzzle as http client to send hits to google analytics
$logger = null; // You can set this to any logger adhering to the Psr\Log standard
$trackerOptions = [
   "applicationName" => "Test",
   "webTrackingId" => 'UA-XXXX-Y', // initialize with you analytics property ID
   "appTrackingId" => 'UA-XXXX-Y', // initialize with you analytics property ID
   "batching" => false,
   "debug" => true,
];
$tracker = new Tracker($httpClient, $logger, $trackerOptions);
$tracker->trackPageView('user-cid', 'example.com', '/', 'home');
```

## Notes

- Exceptions tracking doesn't work on a Normal Tracker, it requires an 
  Application Tracker, so please make sure when you create an instance for the 
  tracker you include an `appTrackingId` to be used for sending events.

  If you don't, the tracker will default to the normal tracker and you won't be 
  able to see anything in your dashboard.

## Docs Generation

```bash
# 1. Run phpdoc command
vendor/bin/phpdoc -d src -t -docs --template="xml"


# 2. Next, run phpdocmd:
vendor/bin/phpdocmd -docs/structure.xml docs


# 3. delete temporary XML docs
rm -fR -docs
```
