<?php

# This simple script presents logtail integration for PHP

# Setting autoloader
require "vendor/autoload.php";

# Setting logger
use Monolog\Logger;
use Logtail\Monolog\LogtailHandlerBuilder;

# Read the source token and ingesting host from the environment
$sourceToken = getenv("BETTER_STACK_SOURCE_TOKEN");
$ingestingHost = getenv("BETTER_STACK_INGESTING_HOST");
if (!$sourceToken || !$ingestingHost) {
    fwrite(STDERR, "Usage: BETTER_STACK_SOURCE_TOKEN=<source-token> BETTER_STACK_INGESTING_HOST=<ingesting-host> php index.php\n");
    exit(1);
}

$logger = new Logger("logtail-source");
$handler = LogtailHandlerBuilder::withSourceToken($sourceToken)
  ->withEndpoint("https://" . $ingestingHost)
  ->withBufferLimit(100)
  ->withFlushIntervalMilliseconds(500)
  ->withExceptionThrowing(true)
  ->build();
$logger->pushHandler($handler);

# Below you can see available methods that can be used to send logs to logtail.
# Each method corresponds to Monolog's log level.
# You can also add additional context information in form of an array to any logging method and pass it as the
# second argument of the select method (as shown in the warn() method ).

# Send debug information using debug() method
$logger->debug("Logtail logger is ready!");

# Send information about interesting events using info() method
$logger->info("An interesting event occurred!");

# Send information about normal but significant events using notice() method
$logger->notice("Sending notice");

# Send information about exceptional occurrences that might not be errors using warning() method
# You can also pass additional context information to any logging method in form of an array as the second argument
$logger->warning("Something is not quite right ...",[
    "additional_data" => [
        "item1" => "value1",
        "item2" => "value2"
    ]
]);

# Send information about runtime errors that might not require an immediate action using error() method
# Pass a caught exception under the "exception" key of the context to log its class, message, code and file
try {
    throw new RuntimeException("Could not connect to the database");
} catch (RuntimeException $exception) {
    $logger->error("Oops! A runtime ERROR occurred!", ["exception" => $exception]);
}

# Send information about critical conditions using critical() method
$logger->critical("Oh no! A critical event occurred!");

# Send an alert message about events for which action must be taken immediately using alert() method
$logger->alert("Something terrible happened! Immediate action is required!");

# Send an emergency message about events that forced the application to crash using emergency() method 
$logger->emergency("Application just crashed! Immediate action is required!");

# The handler buffers logs and sends them in batches. Send what is left before the script ends, so that a failure
# to deliver them stops the script here instead of after the line below.
$logger->close();

echo "All done, you can check your logs in the control panel. \n";
