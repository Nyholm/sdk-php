<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Temporal\Tests\Workflow;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Temporal\Activity\ActivityOptions;
use Temporal\Promise;
use Temporal\Tests\Activity\SimpleActivity;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
class UpdateWorkflow
{
    private array $greetings = [];
    private bool $exit = false;

    #[WorkflowMethod(name: "Update.greet")]
    public function greet()
    {
        yield Workflow::await(fn() => $this->exit);
        return $this->greetings;
    }

    #[Workflow\UpdateMethod]
    public function addNameWithoutValidation(string $name): mixed
    {
        $this->greetings[] = $result = \sprintf('Hello, %s!', $name);
        return $result;
    }

    #[Workflow\UpdateMethod]
    public function addName(string $name): mixed
    {
        $this->greetings[] = $result = \sprintf('Hello, %s!', $name);
        return $result;
    }

    #[Workflow\UpdateValidatorMethod(forUpdate: 'addName')]
    public function validateName(string $name): void
    {
        if (\preg_match('/\\d/', $name) === 1) {
            throw new \InvalidArgumentException('Name must not contain digits');
        }
    }

    #[Workflow\UpdateMethod]
    public function randomizeName(int $count = 1): mixed
    {
        $promises = [];
        for ($i = 0; $i < $count; $i++) {
            $promises[] = Workflow::sideEffect(
                static fn(): string => \sprintf('Hello, %s!', ['Antony', 'Alexey', 'John'][\random_int(0, 2)]),
            )->then(
                function (string $greeting) {
                    $this->greetings[] = $greeting;
                }
            );
        }
        yield Promise::all($promises);
        return $this->greetings;
    }

    #[Workflow\UpdateMethod]
    public function addNameViaActivity(string $name): mixed
    {
        $name = yield Workflow::newActivityStub(
            SimpleActivity::class,
            ActivityOptions::new()->withStartToCloseTimeout('10 seconds'),
        )->lower($name);
        $this->greetings[] = $result = \sprintf('Hello, %s!', $name);
        return $result;
    }

    #[Workflow\UpdateMethod]
    public function throwException(string $name): mixed
    {
        throw new \Exception("Test exception with $name");
    }

    #[Workflow\UpdateMethod]
    public function returnNilUuid(): UuidInterface
    {
        return Uuid::fromString(Uuid::NIL);
    }

    #[Workflow\UpdateMethod]
    #[Workflow\ReturnType(UuidInterface::class)]
    public function returnUuid(UuidInterface $datetime)
    {
        return $datetime;
    }

    #[Workflow\UpdateMethod]
    #[Workflow\ReturnType('object')]
    public function returnAsObject(mixed $mixed): object
    {
        return (object)(array)$mixed;
    }

    #[Workflow\SignalMethod]
    public function exit(): void
    {
        $this->exit = true;
    }
}
