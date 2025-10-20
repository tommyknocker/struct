<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use tommyknocker\struct\Field;
use tommyknocker\struct\Struct;

enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
}

enum Priority: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;
}

enum SimpleEnum
{
    case One;
    case Two;
    case Three;
}

final class TaskStruct extends Struct
{
    #[Field('string')]
    public readonly string $title;

    #[Field(Status::class)]
    public readonly Status $status;

    #[Field(Priority::class)]
    public readonly Priority $priority;
}

final class StructEnumConversionTest extends TestCase
{
    public function testEnumFromString(): void
    {
        $task = new TaskStruct([
            'title' => 'My Task',
            'status' => 'active',
            'priority' => 2,
        ]);

        $this->assertSame('My Task', $task->title);
        $this->assertSame(Status::Active, $task->status);
        $this->assertSame(Priority::Medium, $task->priority);
    }

    public function testEnumFromEnumInstance(): void
    {
        $task = new TaskStruct([
            'title' => 'My Task',
            'status' => Status::Pending,
            'priority' => Priority::High,
        ]);

        $this->assertSame(Status::Pending, $task->status);
        $this->assertSame(Priority::High, $task->priority);
    }

    public function testInvalidEnumValue(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Invalid value 'invalid' for enum");

        new TaskStruct([
            'title' => 'My Task',
            'status' => 'invalid',
            'priority' => 1,
        ]);
    }

    public function testInvalidIntEnumValue(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Invalid value '99' for enum");

        new TaskStruct([
            'title' => 'My Task',
            'status' => 'active',
            'priority' => 99,
        ]);
    }

    public function testNonBackedEnum(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must be instance of enum');

        new class (['value' => 'test']) extends Struct {
            #[Field(SimpleEnum::class)]
            public readonly SimpleEnum $value;
        };
    }

    public function testNonBackedEnumWithInstance(): void
    {
        $struct = new class (['value' => SimpleEnum::One]) extends Struct {
            #[Field(SimpleEnum::class)]
            public readonly SimpleEnum $value;
        };

        $this->assertSame(SimpleEnum::One, $struct->value);
    }

    public function testEnumInArray(): void
    {
        $struct = new class (['statuses' => ['active', 'pending', 'inactive']]) extends Struct {
            #[Field(Status::class, isArray: true)]
            public readonly array $statuses;
        };

        $this->assertCount(3, $struct->statuses);
        $this->assertSame(Status::Active, $struct->statuses[0]);
        $this->assertSame(Status::Pending, $struct->statuses[1]);
        $this->assertSame(Status::Inactive, $struct->statuses[2]);
    }

    public function testEnumInArrayWithInvalidValue(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Invalid value 'invalid'");

        new class (['statuses' => ['active', 'invalid']]) extends Struct {
            #[Field(Status::class, isArray: true)]
            public readonly array $statuses;
        };
    }

    public function testEnumFromJson(): void
    {
        $json = '{"title":"Test","status":"pending","priority":3}';
        $task = TaskStruct::fromJson($json);

        $this->assertSame(Status::Pending, $task->status);
        $this->assertSame(Priority::High, $task->priority);
    }

    public function testEnumToArray(): void
    {
        $task = new TaskStruct([
            'title' => 'My Task',
            'status' => Status::Active,
            'priority' => Priority::Low,
        ]);

        $array = $task->toArray();

        $this->assertSame(Status::Active, $array['status']);
        $this->assertSame(Priority::Low, $array['priority']);
    }
}
