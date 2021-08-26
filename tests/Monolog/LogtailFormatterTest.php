<?php

namespace Logtail\Monolog;

class LogtailFormatterTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var \Logtail\Monolog\LogtailFormatter
     */
    private $formatter = null;

    protected function setUp(): void {
        parent::setUp();
        $this->formatter = new \Logtail\Monolog\LogtailFormatter();
    }

    public function testJsonFormat(): void {
        $input = [
            'message' => 'some message',
            'context' => [],
            'level' => 100,
            'level_name' => 'DEBUG',
            'channel' => 'name',
            'extra' => ['x' => 'y'],
            'datetime' => '"2021-08-10T14:49:47.618908+00:00"'
        ];

        $json = $this->formatter->format($input);
        $decoded = \json_decode($json, true);

        $this->assertEquals($decoded['message'], $input['message']);
        $this->assertEquals($decoded['level'], $input['level_name']);
        $this->assertEquals($decoded['dt'], $input['datetime']);
        $this->assertEquals($decoded['monolog']['channel'], $input['channel']);
        $this->assertEquals($decoded['monolog']['extra'], $input['extra']);
        $this->assertEquals($decoded['monolog']['context'], $input['context']);
    }
}
