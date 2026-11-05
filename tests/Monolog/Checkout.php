<?php

namespace App;

use Psr\Log\LoggerInterface;

/**
 * Logs the way a customer's application does. The IntrospectionProcessor skips the frames of every class in the
 * Logtail and Monolog namespaces, these tests included, so a test that checks where a record was logged logs from here.
 */
class Checkout
{
    public function placeOrder(LoggerInterface $logger): void
    {
        $logger->info('Order placed');
    }
}
