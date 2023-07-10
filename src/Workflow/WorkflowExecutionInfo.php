<?php

declare(strict_types=1);

namespace Temporal\Workflow;

use DateTimeInterface;
use JetBrains\PhpStorm\Immutable;
use Temporal\Common\WorkerVersionStamp;
use Temporal\DataConverter\EncodedCollection;
use Temporal\Workflow\ResetPointInfo as ResetPointInfoDto;

/**
 * @see \Temporal\Api\Workflow\V1\WorkflowExecutionInfo
 * @psalm-immutable
 */
#[Immutable]
final class WorkflowExecutionInfo
{
    /**
     * @param array<ResetPointInfoDto> $autoResetPoints
     */
    public function __construct(
        public WorkflowExecution $execution,
        public WorkflowType $type,
        public ?DateTimeInterface $startTime,
        public ?DateTimeInterface $closeTime,
        public WorkflowExecutionStatus $status,
        public int $historyLength,
        public ?string $parentNamespaceId,
        public ?WorkflowExecution $parentExecution,
        public ?DateTimeInterface $executionTime,
        public EncodedCollection $memo,
        public EncodedCollection $searchAttributes,
        public array $autoResetPoints,
        public string $taskQueue,
        public int $stateTransitionCount,
        public int $historySizeBytes,
        public ?WorkerVersionStamp $mostRecentWorkerVersionStamp,
    ) {
    }
}
