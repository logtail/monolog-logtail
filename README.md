# [Better Stack](https://betterstack.com/logs) PHP client

📣 Logtail is now part of Better Stack. [Learn more ⇗](https://betterstack.com/press/introducing-better-stack/)

[![Better Stack dashboard](https://github.com/logtail/logtail-python/assets/10132717/e2a1196b-7924-4abc-9b85-055e17b5d499)](https://betterstack.com/logs)

[![ISC License](https://img.shields.io/badge/license-ISC-ff69b4.svg)](LICENSE.md)
[![PHP package](https://badge.fury.io/ph/logtail%2Fmonolog-logtail.svg)](https://badge.fury.io/ph/logtail%2Fmonolog-logtail)
[![Build](https://github.com/logtail/monolog-logtail/actions/workflows/main.yml/badge.svg?branch=master)](https://github.com/logtail/monolog-logtail/actions/workflows/main.yml)

Experience SQL-compatible structured log management based on ClickHouse. [Learn more ⇗](https://betterstack.com/logs)

## Documentation

[Getting started ⇗](https://betterstack.com/docs/logs/php/)

## When Better Stack rejects your logs

If Better Stack answers with an HTTP error, for example 401 when the source token or the ingesting host is wrong, the logs are dropped and the handler raises a PHP warning (`E_USER_WARNING`) naming the status and the endpoint. It warns every time it sends logs, so about once per request, until you copy the source token and the ingesting host from your source's settings in Better Stack. With `withExceptionThrowing(true)` it throws a `Logtail\Monolog\LogtailIngestException` instead, with the HTTP status as its code.

To keep an environment without a working Better Stack source quiet, for example local development, don't add the handler there.

## Need help?
Please let us know at [hello@betterstack.com](mailto:hello@betterstack.com). We're happy to help!

**Using older Monolog?** Install Logtail for Monolog 2:

```bash
composer require logtail/monolog-logtail:^2.0.0
```
---

[ISC license](https://github.com/logtail/monolog-logtail/blob/master/LICENSE.md), [example project](https://github.com/logtail/monolog-logtail/tree/master/example-project)
