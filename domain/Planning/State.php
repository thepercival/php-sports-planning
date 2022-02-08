<?php

declare(strict_types=1);

namespace SportsPlanning\Planning;

enum State: int
{
    case ToBeProcessed = 1;
    case Succeeded = 2;
    case Failed = 4;
    case TimedOut = 8;
}
