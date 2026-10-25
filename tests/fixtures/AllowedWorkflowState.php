<?php

declare(strict_types=1);

namespace tommyknocker\struct\tests\fixtures;

enum AllowedWorkflowState: string
{
    case Ready = 'ready';
    case Failed = 'failed';
}
