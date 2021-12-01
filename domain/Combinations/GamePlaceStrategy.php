<?php
declare(strict_types=1);

namespace SportsPlanning\Combinations;

enum GamePlaceStrategy: int
{
    case EquallyAssigned = 1;
    case RandomlyAssigned = 2;
}
