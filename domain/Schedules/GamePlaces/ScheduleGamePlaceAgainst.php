<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\GamePlaces;

use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsOne;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsTwo;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstTwoVsTwo;

class ScheduleGamePlaceAgainst
{

    public function __construct(
        public readonly ScheduleGameAgainstOneVsOne|ScheduleGameAgainstOneVsTwo|ScheduleGameAgainstTwoVsTwo $game,
        public readonly AgainstSide $againstSide,
        public readonly int $placeNr)
    {
        $game->addGamePlace($this);
    }
}
