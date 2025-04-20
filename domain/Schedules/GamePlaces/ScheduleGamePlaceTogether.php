<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\GamePlaces;


use SportsPlanning\Schedules\Games\ScheduleGameTogether;

class ScheduleGamePlaceTogether
{
    public function __construct(
        public readonly ScheduleGameTogether $game,
        public readonly int $placeNr,
        public readonly int $cycleNr)
    {
        $game->addGamePlace($this);
    }
}
