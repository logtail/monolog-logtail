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
        $logger->debug('test message');

        fclose($out);
        $debug = ob_get_clean();

        echo $debug;
    }
}
