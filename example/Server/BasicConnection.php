<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Server;

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Socket\ConnectionInterface;
use Temporal\Client\Worker\Uuid4;

final class BasicConnection extends Connection
{
    /**
     * @param LoopInterface $loop
     * @param ConnectionInterface $connection
     * @param LoggerInterface $logger
     * @throws \JsonException
     */
    public function __construct(LoopInterface $loop, ConnectionInterface $connection, LoggerInterface $logger)
    {
        parent::__construct($loop, $connection, $logger);

        $this->process(function () {
            return $this->start();
        });
    }

    /**
     * @throws \JsonException
     */
    private function start(): \Generator
    {
        // Fetch info from client
        $result = yield $this->request('GetWorkerInfo');

        foreach ($this->onGetWorkerInfo($result) as $startWorkflow) {
            $result = yield $startWorkflow;
        }
    }

    /**
     * @param array $result
     * @return \Generator
     * @throws \Exception
     */
    private function onGetWorkerInfo(array $result): \Generator
    {
        foreach ($result['workflows'] as $workflow) {
            $info = $this->allocateRunId();

            /*   foreach ($workflow['queries'] ?? [] as $query) {
                   $this->request('InvokeQueryMethod', \array_merge($info, [
                       'name' => $query,
                   ]));
               }

               foreach ($workflow['signals'] ?? [] as $signal) {
                   $this->request('InvokeSignalMethod', \array_merge($info, [
                       'name'    => $signal,
                       'payload' => [1, 2, 3],
                   ]));
               }*/

            $this->runId = $info['rid'];

            yield $this->request('StartWorkflow', \array_merge($info, [
                'name'    => $workflow['name'],
                'payload' => [1, 2, 3],
            ]));
        }
    }

    /**
     * @return string[]
     * @throws \Exception
     */
    private function allocateRunId(): array
    {
        return [
            'wid'       => 'WorkflowId<' . Uuid4::create() . '>',
            'rid'       => 'WorkflowRunId<' . Uuid4::create() . '>',
            'taskQueue' => 'WorkerTaskQueue<' . Uuid4::create() . '>',
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function onCommand(string $name, array $params, Deferred $deferred): void
    {
        switch ($name) {
            case 'ExecuteActivity':
                $this->onExecuteActivity($deferred);
                break;

            case 'NewTimer':
                $this->loop->addTimer($params['ms'] / 1000, function () use ($deferred) {
                    $deferred->resolve((new \DateTime())->format(\DateTime::RFC3339));
                });
                break;

            case 'CompleteWorkflow':
                $deferred->resolve('Okay');
                break;

            default:
                throw new \LogicException('Unrecognized command ' . $name);
        }
    }

    /**
     * @param Deferred $deferred
     */
    private function onExecuteActivity(Deferred $deferred): void
    {
        $deferred->resolve('Activity execution result');
    }
}
