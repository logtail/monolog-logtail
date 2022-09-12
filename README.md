# Logtail - PHP Logging Made Easy
  
  [![Logtail dashboard](https://user-images.githubusercontent.com/19272921/154085622-59997d5a-3f91-4bc9-a815-3b8ead16d28d.jpeg)](https://betterstack.com/logtail)


[![License: ISC](https://img.shields.io/badge/License-ISC-blue.svg)](LICENSE.md) ![Unit tests](https://github.com/logtail/logtail-php/actions/workflows/main.yml/badge.svg)
[![PHP version](https://badge.fury.io/ph/logtail%2Fmonolog-logtail.svg)](https://badge.fury.io/ph/logtail%2Fmonolog-logtail)

Collect logs from your PHP projects, including Laravel, Symfony, CodeIgniter, CakePHP, Zend, and more.

[Logtail](https://betterstack.com/logtail) is a hosted service that centralizes all of your logs into one place. Allowing for analysis, correlation and filtering with SQL. Actionable Grafana dashboards and collaboration come built-in. Logtail works with [any language or platform and any data source](https://docs.logtail.com/).

### Features
- Simple integration. Built on well-known Monolog logging library.
- Support for structured logging and events.
- Automatically captures useful context.
- Performant, light weight, with a thoughtful design.

### Supported language versions
- PHP 8 or newer
- Composer 1.10.1 or newer

# Installation

Install the Logtail Monolog library using composer:

```bash
composer require logtail/monolog-logtail
```

---

# Example project

To help you get started with using Logtail in your PHP projects, we have prepared a simple program that showcases the usage of Logtail logger.

## Download and install the example project

You can download the [example project](https://github.com/logtail/monolog-logtail/tree/master/example-project) from GitHub directly or you can clone it to a select directory. In that directory, run the following command:

```bash
composer update
```
This command will install all dependencies from `composer.json` file and lock them in `composer.lock` file.

 ## Run the example project
 
 To run the example application, simply run the following command:

```bash
php index.php <source-token>
```

*Don't forget to replace `<source-token>` with your actual source token which you can find by going to logtail.com -> sources -> edit.*

You should see the following output:
```text
All done, you can check your logs in the control panel.
```

This will create and send a total of 8 log messages to the Logtail. Each message corresponds to a specific log level. Detail explanation follows below.

## Explore how example project works
 
Learn how to setup PHP logging by exploring the workings of the [example project](https://github.com/logtail/monolog-logtail/tree/master/example-project) in detail. 

## Improve performance with batches
You can use `Monolog\Handler\BufferHandler` to send your logs periodically in batches for optimized network traffic.

```
$logger->pushHandler(new \Monolog\Handler\BufferHandler(new LogtailHandler($token)));
```

---

## Get in touch

Have any questions? Please explore the Logtail [documentation](https://docs.logtail.com/) or contact our [support](https://betterstack.com/help).
