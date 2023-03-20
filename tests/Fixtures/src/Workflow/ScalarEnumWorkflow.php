<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Temporal\Tests\Workflow;

use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Tests\Activity\SimpleActivity;
use Temporal\Tests\Unit\DTO\Type\EnumType\Stub\ScalarEnum;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowMethod;

#[Workflow\WorkflowInterface]
class ScalarEnumWorkflow
{
    #[WorkflowMethod(name: 'ScalarEnumWorkflow')]
    public function handler(ScalarEnum $enum): iterable
    {
        $simple = Workflow::newActivityStub(
            SimpleActivity::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(5)
                ->withRetryOptions(
                    RetryOptions::new()->withMaximumAttempts(2)
                )
        );

        return yield $simple->scalarEnum($enum);
    }
}
