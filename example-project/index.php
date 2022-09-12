<?php

# This simple script presents logtail integration for PHP

# Setting autoloader
require "vendor/autoload.php";

# Setting logger
use Monolog\Logger;
use Logtail\Monolog\LogtailHandler;

# Check for arguments
if($argc != 2){
    # No argument was provided
    echo "No source token was provided. Please, run the script as followed:\n php index.php <source-token>\n";
    exit;
}

$logger = new Logger("logtail-source");
$logger->pushHandler(new LogtailHandler($argv[1]));

# Below you can see available methods that can be used to send logs to logtail.
# Each method corresponds to Monologs log level.
# You can also add additional context information in form of an array to any logging method and pass it as the
# second argument of the select method (as shown in the warn() method ).

# Send debug information using debug() method
$logger->debug("Logtail logger is ready!");

# Send information about interesting events using info() method
$logger->info("An interesting event occured!");

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
$logger->error("Oops! An runtime ERROR occurred!");

# Send information about critical conditions using critical() method
$logger->critical("Oh no! An critical event occurred!");

# Send an alert message about events for which action must be taken immediately using alert() method
$logger->alert("Something terrible happend! Imidiate action is required!");

# Send an emergency message about events that forced the application to crash using emergency() method 
$logger->emergency("Application just crashed! Imidiate action is required!");


echo "All done, you can check your logs in the control panel. \n";
