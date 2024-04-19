<?php

declare(strict_types=1);

namespace SportsPlanning\Planning;

enum BatchGamesType: string
{
    case RangeIsZero = 'rangeIsZero';
    case RangeIsOneOrMore = 'angeIsOneOrMore';
}
