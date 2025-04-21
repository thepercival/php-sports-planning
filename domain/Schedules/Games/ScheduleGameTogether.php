<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Games;

use SportsPlanning\Schedules\Cycles\ScheduleCycleTogether;
use SportsPlanning\Schedules\GamePlaces\ScheduleGamePlaceTogether;

class ScheduleGameTogether
{
    /**
     * @var list<ScheduleGamePlaceTogether>
     */
    private array $gamePlaces = [];

    public function __construct(public readonly ScheduleCycleTogether $cycle)
    {
        $cycle->addGame($this);
    }

    /**
     * @return list<ScheduleGamePlaceTogether>
     */
    public function getGamePlaces(): array
    {
        return $this->gamePlaces;
    }

    public function addGamePlace(ScheduleGamePlaceTogether $gamePlace): void
    {
        $this->cycle->addGamePlace($gamePlace);
        $this->gamePlaces[] = $gamePlace;
    }

    public function __toString(): string
    {
        $gamePlacesAsString = array_map(function (ScheduleGamePlaceTogether $gamePlace): string {
            return $gamePlace->placeNr . '(cy' . $gamePlace->cycleNr . ')';
        }, $this->getGamePlaces());
        return join(' & ', $gamePlacesAsString);
    }


}
