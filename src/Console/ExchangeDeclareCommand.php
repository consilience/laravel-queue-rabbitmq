<?php

namespace VladimirYuldashev\LaravelQueueRabbitMQ\Console;

use Exception;
use Illuminate\Console\Command;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Connectors\RabbitMQConnector;

class ExchangeDeclareCommand extends Command
{
    protected $signature = 'rabbitmq:exchange-declare
                            {name : The name of the exchange to declare}
                            {connection=rabbitmq : The name of the queue connection to use}
                            {--type=direct}
                            {--durable=1}
                            {--auto-delete=0}
                            {--option=* : List of options name[|value[|type]]}';

    protected $description = 'Declare exchange';

    /**
     * @param RabbitMQConnector $connector
     * @throws Exception
     */
    public function handle(RabbitMQConnector $connector): void
    {
        $config = $this->laravel['config']->get('queue.connections.'.$this->argument('connection'));

        $queue = $connector->connect($config);

        if ($queue->isExchangeExists($this->argument('name'))) {
            $this->warn('Exchange already exists.');

            return;
        }

        $options = collect($this->option('option'))->mapWithKeys(function ($item) {
            [$name, $value, $type] = explode('|', $item, 3) + [null, null, null];

            if ($type) {
                switch ($type) {
                    case 'int':
                    case 'integer':
                        $value = (int)$value;
                        break;
                    case 'bool':
                    case 'boolean':
                        $value = (bool)$value;
                        break;
                    }
            }

            return [$name => $value];
        })->filter(function($value, $key) {
            // Filter out empty "--option="
            return $key !== '';
        })->toArray();

        $queue->declareExchange(
            $this->argument('name'),
            $this->option('type'),
            (bool) $this->option('durable'),
            (bool) $this->option('auto-delete'),
            $options
        );

        $this->info('Exchange declared successfully.');
    }
}
