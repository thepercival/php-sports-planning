<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Games;

use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainst;
use SportsPlanning\Schedules\GamePlaces\ScheduleGamePlaceAgainst;

abstract class ScheduleGameAgainstAbstract
{
    /**
     * @var list<ScheduleGamePlaceAgainst>
     */
    private array $gamePlaces = [];

    public function __construct(public readonly ScheduleCyclePartAgainst $cyclePart)
    {
    }


    /**
     * @return list<ScheduleGamePlaceAgainst>
     */
    public function getGamePlaces(): array
    {
        return $this->gamePlaces;
    }

    public function addGamePlace(ScheduleGamePlaceAgainst $gamePlace): void
    {
        $this->gamePlaces[] = $gamePlace;
    }
}
