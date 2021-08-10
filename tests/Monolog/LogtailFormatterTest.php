<?php

namespace Logtail\Monolog;

class LogtailFormatterTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var \Logtail\Monolog\LogtailFormatter
     */
    private $formatter = null;

    protected function setUp() {
        parent::setUp();
        $this->formatter = new \Logtail\Monolog\LogtailFormatter();
    }

    public function testJsonFormat() {
        $record = [
            'message' => 'some message',
            'context' => [],
            'level' => 100,
            'level_name' => 'DEBUG',
            'channel' => 'name',
            'extra' => ['x' => 'y'],
            'datetime' => '"2021-08-10T14:49:47.618908+00:00"'
        ];

        $json = $this->formatter->format($record);
        $decoded_json = \json_decode($json, true);

        $this->assertEquals($decoded_json['message'], $record['message']);
        $this->assertEquals($decoded_json['level'], $record['level_name']);
        $this->assertEquals($decoded_json['dt'], $record['datetime']);
        $this->assertEquals($decoded_json['monolog']['channel'], $record['channel']);
        $this->assertEquals($decoded_json['monolog']['extra'], $record['extra']);
        $this->assertEquals($decoded_json['monolog']['context'], $record['context']);
    }
}
