<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule;

enum TimeoutState: string
{
    case FirstTryMaxDiff2 = 'FirstTryMaxDiff2';
    case SecondTryMaxDiff2 = 'SecondTryMaxDiff2';
    case FirstTryMinDiff = 'FirstTryMinDiff';
}
