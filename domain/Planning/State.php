<?php

declare(strict_types=1);

namespace SportsPlanning\Planning;

enum State: string
{
    case ToBeProcessed = 'toBeProcessed';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case TimedOut = 'timedOut';
}
