<?php

namespace SportsPlanning\Combinations\PlaceCombinationCounterMap;

use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\Combinations\PlaceCombinationCounterMap;
use SportsPlanning\Combinations\PlaceCombinationCounterMap as PlaceCombinationCounterMapBase;
use SportsPlanning\Schedule\CreatorHelpers\AgainstGppDifference;

class Ranged
{
    /**
     * @param array<string, PlaceCombinationCounter> $placeCombinationCounters
     */
    public function __construct(
        private readonly PlaceCombinationCounterMapBase $map,
        public readonly int $minimum,
        public readonly int $minNrToAssignToMinimum,
        public readonly int $maximum,
        public readonly int $maxNrToAssignToMaximum,
        public readonly int $shortage,
        private readonly bool $overAssigned = false) {

    }

    public function getMap(): PlaceCombinationCounterMap {
        return $this->map;
    }

    public function addPlaceCombination(PlaceCombination $placeCombination): self {

        $count = $this->map->count($placeCombination);
        $newMap = $this->map->addPlaceCombination($placeCombination);

        $newShortage = $this->shortage;
        $newCount = $newMap->count($placeCombination);

        if( ($newCount < $this->minimum
            || ($newCount === $this->minimum && $newMap->getNrOfAssignedTo($newCount) >= $this->minNrToAssignToMinimum)
            || ($count === $this->minimum /*&& $this->map->getNrOfAssignedTo($count) < $this->minNrToAssignToMinimum*/))
            // && ($newCount > $this->minimum || ( $newCount === $this->minimum && $newMap->getNrOfAssignedTo($newCount) >= $this->minNrToAssignToMinimum))
        ) {
            $newShortage--;
        }

        $newOverAssigned = $this->overAssigned;
        if( !$this->overAssigned ) {
            if( ($count < $this->maximum || ( $count === $this->maximum && $newMap->getNrOfAssignedTo($count) <= $this->maxNrToAssignToMaximum))
                && ($newCount > $this->maximum || ( $newCount === $this->maximum && $newMap->getNrOfAssignedTo($newCount) > $this->maxNrToAssignToMaximum))
            ) {
                $newOverAssigned = true;
            }
        }
        return new self(
            $newMap,
            $this->minimum,
            $this->minNrToAssignToMinimum,
            $this->maximum,
            $this->maxNrToAssignToMaximum,
            $newShortage,
            $newOverAssigned);

    }

    public function removePlaceCombination(PlaceCombination $placeCombination): self {

        $count = $this->map->count($placeCombination);
        $newMap = $this->map->removePlaceCombination($placeCombination);

        $newShortage = $this->shortage;
        $newCount = $newMap->count($placeCombination);

        if( /*($count > $this->minimum || ( $count === $this->minimum && $newMap->getNrOfAssignedTo($count) >= $this->minNrToAssignToMinimum))
            &&*/ $newCount < $this->minimum || ( $newCount === $this->minimum && $newMap->getNrOfAssignedTo($newCount) < $this->minNrToAssignToMinimum)
        ) {
            $newShortage--;
        }

        $newOverAssigned = $this->overAssigned;
        if( $this->overAssigned ) {
            if( ($count > $this->maximum || ( $count === $this->maximum && $newMap->getNrOfAssignedTo($count) > $this->maxNrToAssignToMaximum))
                && ($newCount < $this->maximum || ( $newCount === $this->maximum && $newMap->getNrOfAssignedTo($newCount) <= $this->maxNrToAssignToMaximum))
            ) {
                $newOverAssigned = true;
            }
        }

        return new self(
            $newMap,
            $this->minimum,
            $this->minNrToAssignToMinimum,
            $this->maximum,
            $this->maxNrToAssignToMaximum,
            $newShortage,
            $newOverAssigned);
    }

    public function count(PlaceCombination $placeCombination): int
    {
        return $this->map->count($placeCombination);
    }

    public function getMaxDifference(): int
    {
        return $this->map->getMaxDifference();
    }

    public function getMax(): int
    {
        return $this->map->getMax();
    }

    public function withinRange(int $nrOfCombinationsToGo): bool
    {
        return ($this->minimum === 0 || $nrOfCombinationsToGo >= $this->shortage) && !$this->overAssigned;
    }

}