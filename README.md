# Google Analytics measuring protocol implementation

## TODO
- [ ] Use Guzzle to as the client to send data

## Dependencies
- [cURL](http://php.net/manual/en/book.curl.php)
- [psr/log](https://packagist.org/packages/psr/log)

## Usage

## Notes
- Exceptions tracking doesn't work on a Normal Tracker, it requires an Application Tracker,
so please make sure when you create an instance for the tracker you include an `appTrackingId` to be used for sending events.
If you don't, the tracker will default to the normal tracker and you won't be able to see anything in your dashboard.

## Docs Generation
```bash
# 1. Run phpdoc command
vendor/bin/phpdoc -d src -t -docs --template="xml"


# 2. Next, run phpdocmd:
vendor/bin/phpdocmd -docs/structure.xml docs


# 3. delete temporary XML docs
rm -fR -docs
```
