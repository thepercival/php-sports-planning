<?php

declare(strict_types=1);

namespace SportsPlanning\Planning;

enum TimeoutState: string
{
    case Time1xNoSort = 'Time1xNoSort';
    case Time4xSort = 'Time4xSort';
    case Time4xNoSort = 'Time4xNoSort';
    case Time10xSort = 'Time10xSort';
    case Time10xNoSort = 'Time10xNoSort';
}
