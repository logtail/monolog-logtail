<?php

namespace Logtail\Monolog;

class LogtailHandlerTest extends \PHPUnit\Framework\TestCase {
    public function testHandlerWrite()
    {
        ob_start();
        $out = fopen('php://output', 'w');

        $handler = new \Logtail\Monolog\LogtailHandler("sourceTokenXYZ");

        $logger = new \Monolog\Logger('test');
        $logger->pushHandler($handler);

        $getHandle = function() { $this->handle; };
        $handle = $getHandle->call($handler);

        \curl_setopt($handle, CURLOPT_VERBOSE, true);
        \curl_setopt($handle, CURLOPT_STDERR, $out);

        $logger->debug('test message');

        fclose($out);
        $output = ob_get_clean();

        echo "--- START";
        echo $output;
        echo "--- END";

        $this->assertEquals($output, "abc");
    }
}
