<?php

namespace Logtail\Monolog;

class LogtailFormatterTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var \Logtail\Monolog\LogtailFormatter
     */
    private $formatter = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new \Logtail\Monolog\LogtailFormatter();
    }

    public function testJsonFormat(): void
    {
        $input = new \Monolog\LogRecord(
            datetime: new \DateTimeImmutable("2021-08-10T14:49:47.618908+00:00"),
            channel: 'name',
            level: \Monolog\Level::Debug,
            message: 'some message',
            context: [],
            extra: ['x' => 'y'],
        );

        $json = $this->formatter->format($input);
        $decoded = \json_decode($json, true);

        $this->assertEquals($decoded['message'], $input->message);
        $this->assertEquals($decoded['level'], $input->level->value);
        $this->assertEquals($decoded['dt'], $input->datetime->format('Y-m-d\TH:i:sP'));
        $this->assertEquals($decoded['monolog']['channel'], $input->channel);
        $this->assertEquals($decoded['monolog']['extra'], $input->extra);
        $this->assertEquals($decoded['monolog']['context'], $input->context);
    }


    public function testJsonBatchFormat(): void
    {
        $input = [
            new \Monolog\LogRecord(
                datetime: new \DateTimeImmutable("2021-08-10T14:49:47.618908+00:00"),
                channel: 'name',
                level: \Monolog\Level::Debug,
                message: 'some message',
                context: [],
                extra: ['x' => 'y'],
            ),
            new \Monolog\LogRecord(
                datetime: new \DateTimeImmutable("2022-08-10T14:49:47.618908+00:00"),
                channel: 'name',
                level: \Monolog\Level::Critical,
                message: 'second message',
                context: ["some context"],
                extra: ['x' => 'z'],
            ),
        ];

        $json = $this->formatter->formatBatch($input);
        $decoded = \json_decode($json, true);

        $this->assertEquals($decoded[0]['message'], $input[0]->message);
        $this->assertEquals($decoded[0]['level'], $input[0]->level->value);
        $this->assertEquals($decoded[0]['dt'], $input[0]->datetime->format('Y-m-d\TH:i:sP'));
        $this->assertEquals($decoded[0]['monolog']['channel'], $input[0]->channel);
        $this->assertEquals($decoded[0]['monolog']['extra'], $input[0]->extra);
        $this->assertEquals($decoded[0]['monolog']['context'], $input[0]->context);

        $this->assertEquals($decoded[1]['message'], $input[1]->message);
        $this->assertEquals($decoded[1]['level'], $input[1]->level->value);
        $this->assertEquals($decoded[1]['dt'], $input[1]->datetime->format('Y-m-d\TH:i:sP'));
        $this->assertEquals($decoded[1]['monolog']['channel'], $input[1]->channel);
        $this->assertEquals($decoded[1]['monolog']['extra'], $input[1]->extra);
        $this->assertEquals($decoded[1]['monolog']['context'], $input[1]->context);
    }

}
