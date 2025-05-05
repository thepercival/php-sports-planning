<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Games;

use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Combinations\DuoPlaceNr;
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
        $this->gamePlaces[] = $gamePlace;
    }

    /**
     * @return list<int>
     */
    public function convertToPlaceNrs(): array {
        return array_map(function(ScheduleGamePlaceTogether $gamePlace): int {
            return $gamePlace->placeNr;
        }, $this->gamePlaces);
    }

    /**
     * @return list<DuoPlaceNr>
     * @throws \Exception
     */
    public function convertToDuoPlaceNrs(): array {
        $duoPlaceNrs = [];
        foreach($this->gamePlaces as $gamePlaceOne) {
            foreach($this->gamePlaces as $gamePlaceTwo) {
                if( $gamePlaceOne >= $gamePlaceTwo ) {
                    continue;
                }
                $duoPlaceNrs[] = new DuoPlaceNr($gamePlaceOne->placeNr, $gamePlaceTwo->placeNr);
            }
        }
        return $duoPlaceNrs;
    }

    public function __toString(): string
    {
        $gamePlacesAsString = array_map(function (ScheduleGamePlaceTogether $gamePlace): string {
            return $gamePlace->placeNr . '(' . $gamePlace->cycleNr . ')';
        }, $this->getGamePlaces());
        return join(' & ', $gamePlacesAsString);
    }


}
