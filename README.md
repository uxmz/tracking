# Google Analytics measuring protocol implementation

# TODO
- [] Use Guzzle to as the client to send data

# Dependencies
- [cURL](http://php.net/manual/en/book.curl.php)
- [psr/log](https://packagist.org/packages/psr/log)

# Usage

# Notes
- Exceptions tracking doesn't work on a Normal Tracker, it requires an Application Tracker,
so please make sure when you create an instance for the tracker you include an `appTrackingId` to be used for sending events.

If you don't the tracker will default to the normal tracker but you won't be able to see anything in your dashboard.
-
