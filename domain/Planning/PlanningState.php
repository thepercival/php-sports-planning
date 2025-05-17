<?php

declare(strict_types=1);

namespace SportsPlanning\Planning;

enum PlanningState: string
{
    case NotProcessed = 'notProcessed';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case TimedOut = 'timedOut';
}
