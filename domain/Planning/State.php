<?php

declare(strict_types=1);

namespace SportsPlanning\Planning;

enum State: int
{
    case ToBeProcessed = 1;
    case Succeeded = 2;
    case LesserNrOfBatchesSucceeded = 4;
    case LesserNrOfGamesInARowSucceeded = 8;
    case Failed = 16;
    case GreaterNrOfBatchesFailed = 32;
    case GreaterNrOfGamesInARowFailed = 64;
    case TimedOut = 128;
    case GreaterNrOfBatchesTimedOut = 256;
    case GreaterNrOfGamesInARowTimedOut = 512;
}
