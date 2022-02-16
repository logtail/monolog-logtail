# Logtail Monolog example project

To help you get started with using Logtail in your PHP projects, we have prepared a simple Python program that showcases the usage of Logtail logger.

## Download and install the example project

You can download the example project from GitHub directly or you can clone it to a select directory. In that directory, run the following command:

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

# Logging

In this section, we will take a look at actual logging as shown in the example project.

## Setup

First, if you haven’t done it already, require an autoloader file in which the class autoloader is defined. This autoloader will automatically require a class definition file whenever the class is called.

Then, you need to use the `Monolog\Logger` and `Logtail\Monolog\LogtailHandler` namespaces in which the logger is defined.

```php
# Setting autoloader
require "vendor/autoload.php";

# Setting logger
use Monolog\Logger;
use Logtail\Monolog\LogtailHandler;
```

Now it’s time to create a `Logger` instance and push a `LogtailHandler` handler instance on the top of the handler stack.

```php
$logger = new Logger("logtail-source");
$logger->pushHandler(new LogtailHandler("<source-token>"));
```

Don’t forget to change `<source-token>` to your actual token which you can find in the *Basic information* section when clicking on *Edit* on your select source.

Also, make sure that the `Logger` instance is accessible from all the branches of your application in which you wish to use the logger.

## Channels

When creating the `Logger` instance the `Logger()` constructor requires a `channel_string` representing the channel of your application.

You can split your application into imaginary channels that may correspond to functional components of your application and use the different channels for each of these components. This will allow you to filter your logs based on the channel. 

Creating multiple loggers for different channels is fairly easy:

```php
# Logger for shopping cart component
$cart_logger = new Logger("shoping-cart");
$cart_logger->pushHandler(new LogtailHandler("<source-token>"));

# Logger for payment component
$payment_logger = new Logger("payment");
$payment_logger->pushHandler(new LogtailHandler("<source-token>"));
```

Then you can filter your logs using the following search formula:

```
monolog.channel_string="shoping-cart"
```

This will only show logs from the `shoping-cart` channel.

## Logging

The `$logger` variable (instance of the `Logger` class) we have created in the setup has a number of methods used to send the logs to Logtail. Each of these methods corresponds to the Monolog log level. 

These levels are:

- **DEBUG** - Send debug information using the `debug()` method
- **INFO** - Send information about interesting events using the `info()` method
- **NOTICE** - Send information about normal but significant events using the `notice()` method
- **WARNING** - Send information about exceptional occurrences that might not be errors using the `warning()` method
- **ERROR** - Send information about runtime errors that might not require an immediate action using the `error()` method
- **CRITICAL** - Send information about critical conditions using the `critical()` method
- **ALERT** - Send an alert message about events for which action must be taken immediately using the `alert()` method
- **EMERGENCY** - Send an emergency message about events that forced the application to crash using the `emergency()` method

## Logging example

To send a log message of select log level, use the corresponding method. In this example, we will send the INFO level log and ERROR level log.

```php
# Send information about interesting events using info() method
$logger->info("An interesting event occured!");

# Send information about runtime errors that might not require an immediate action using error() method
$logger->error("Oops! An runtime ERROR occurred!");
```

This will output the following JSON data:

```json
{
   "dt":"2022-01-18 10:29:53.350 UTC",
   "level_string":"INFO",
   "message_string":"An interesting event occured!",
   "monolog":{
      "channel_string":"logtail-source",
      "context_string":"[]",
      "extra":{
         "file_string":"/path/to/file.php",
         "hostname_string":"USERAGENT",
         "line_integer":"28",
         "process_id_integer":"8677"
      }
   }
}

{
   "dt":"2022-01-18 10:29:53.550 UTC",
   "level_string":"ERROR",
   "message_string":"Oops! An runtime ERROR occurred!",
   "monolog":{
      "channel_string":"logtail-source",
      "context_string":"[]",
      "extra":{
         "file_string":"/path/to/file.php",
         "hostname_string":"USERAGENT",
         "line_integer":"43",
         "process_id_integer":"8677"
      }
   }
}
```

## Additional context

You can attach additional context information to the actual log message which you can review later and which will help you troubleshoot your application by providing useful information. 

Let’s say that the application crashed when the user clicked on a button inside a shopping cart. In that case, it's useful to attach additional information such as the URL representing the user's location before the crash and the URL user tried to access when the crash happened. You can also attach the content of the shopping cart for further context. All this data might help you find the problem and fix it efficiently.

You can provide additional context information to any method in form of an array and pass it as the second argument.

```php
$logger->emergency("Application crash",[
    "additional_data" => [
        "current_URL" => "app/cart",
        "target_URL" => "app/payment",
        "username" => "someuser",
				"shopping_cart" => [
		            "item1" => "T-shirt",
		            "item2" => "Sneakers"
		        ]
    ]
]);
```

This log will output the following JSON data:

```json
{
   "dt":"2022-01-18 11:42:57.782 UTC",
   "level_string":"EMERGENCY",
   "message_string":"Application crash",
   "monolog":{
      "channel_string":"logtail-source",
      "extra":{
         "file_string":"/path/to/file.php",
         "hostname_string":"USERAGENT",
         "line_integer":"55",
         "process_id_integer":"8680"
      },
      "context":{
         "additional_data":{
            "current_URL_string":"app/cart",
            "shopping_cart":{
               "item1_string":"T-shirt",
               "item2_string":"Sneakers"
            },
            "target_URL_string":"app/payment",
            "username_string":"someuser"
         }
      }
   }
}
```
