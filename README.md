# monolog-logtail

[![License: ISC](https://img.shields.io/badge/License-ISC-blue.svg)](https://opensource.org/licenses/ISC) ![Unit tests](https://github.com/logtail/logtail-php/actions/workflows/main.yml/badge.svg)

## Installation

Install using composer:

```bash
composer require logtail/monolog-logtail  
```

## Usage

The only parameter you need is a `source_token` which you'll get when you [create a new Source](https://logtail.com/team/0/sources) in your [Logtail account](https://logtail.com).

```php
require 'vendor/autoload.php';

use Monolog\Logger;
use Logtail\Monolog\LogtailHandler;

$log = new Logger('testing-logtail');
$log->pushHandler(new LogtailHandler("YOUR_LOGTAIL_SOURCE_TOKEN_GOES_HERE"));

$log->info('Hello Logtail!')
$log->debug('Log with some...', ['additional' => ['structured' => 123, 'logs' => true]]);
```

## License

This library in Licensed under ISC.

