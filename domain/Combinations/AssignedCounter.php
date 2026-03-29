<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use Countable;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;

final class AssignedCounter
{
    protected PlaceNrCounterMap $assignedMap;
    protected PlaceNrCombinationCounterMap $assignedAgainstMap;
    protected PlaceNrCombinationCounterMap $assignedWithMap;
    protected PlaceNrCombinationCounterMap $assignedHomeMap;
    /**
     * @var array<int,array<int,PlaceNrCounter>>
     */
    protected array $assignedTogetherMap = [];
    protected bool $hasAgainstSportWithMultipleSidePlaces;

    /**
     * @param int $nrOfPlaces
     * @param list<Single|AllInOneGame|AgainstGpp|AgainstH2h> $sportVariants
     */
    public function __construct(public readonly int $nrOfPlaces, array $sportVariants)
    {
        $combinationMapper = new Mapper();
        $this->assignedMap = new PlaceNrCounterMap( $combinationMapper->getPlaceNrMap($nrOfPlaces) );
        $this->assignedAgainstMap = new PlaceNrCombinationCounterMap( $combinationMapper->getAgainstMap($nrOfPlaces) );

        $againstVariants = array_values(array_filter($sportVariants,
            function(Single|AllInOneGame|AgainstGpp|AgainstH2h $sportVariant): bool {
                return (($sportVariant instanceof AgainstGpp) || ($sportVariant instanceof AgainstH2h));
            }));
        $this->hasAgainstSportWithMultipleSidePlaces = count(array_filter($againstVariants,
                function(AgainstGpp|AgainstH2h $againstVariant): bool {
                    return $againstVariant->hasMultipleSidePlaces();
                })) > 0;
        $withCounters = $combinationMapper->getWithMap($nrOfPlaces, $againstVariants);
        $this->assignedWithMap = new PlaceNrCombinationCounterMap( $withCounters );
        $this->assignedHomeMap = new PlaceNrCombinationCounterMap( $withCounters );
        for ($placeNr = 1 ; $placeNr <= $nrOfPlaces ; $placeNr++) {
            $this->assignedTogetherMap[$placeNr] = [];
            for ($coPlaceNr = 1 ; $coPlaceNr <= $nrOfPlaces ; $coPlaceNr++) {
                if ($coPlaceNr === $placeNr) {
                    continue;
                }
                $this->assignedTogetherMap[$placeNr][$coPlaceNr] = new PlaceNrCounter($coPlaceNr);
            }
        }
    }

    public function getAssignedMap(): PlaceNrCounterMap
    {
        return $this->assignedMap;
    }

    public function getAssignedAgainstMap(): PlaceNrCombinationCounterMap
    {
        return $this->assignedAgainstMap;
    }

    public function getAssignedWithMap(): PlaceNrCombinationCounterMap
    {
        return $this->assignedWithMap;
    }

    public function getAssignedHomeMap(): PlaceNrCombinationCounterMap
    {
        return $this->assignedHomeMap;
    }

    public function getAssignedAwayMap(): PlaceNrCombinationCounterMap
    {
        $counters = [];
        foreach( $this->assignedWithMap->getPlaceNrCombinationCounters() as $withCounter ) {
            $withCombination = $withCounter->getPlaceNrCombination();
            $nrOfAgainst = $withCounter->count() - $this->assignedHomeMap->count($withCombination);
            if( $nrOfAgainst < 0) {
                $nrOfAgainst = 0;
            }
            $counters[$withCombination->getIndex()] = new PlaceNrCombinationCounter($withCombination, $nrOfAgainst);
        }
        return new PlaceNrCombinationCounterMap($counters);
    }

    /**
     * @return array<int,array<int,PlaceNrCounter>>
     */
    public function getAssignedTogetherMap(): array
    {
        return $this->assignedTogetherMap;
    }

    /**
     * @param array<int,array<int,PlaceNrCounter>> $assignedTogetherMap
     */
    public function setAssignedTogetherMap(array $assignedTogetherMap): void
    {
        $this->assignedTogetherMap = $assignedTogetherMap;
    }

    /**
     * @param list<HomeAway> $homeAways
     */
    public function assignHomeAways(array $homeAways): void
    {
        foreach ($homeAways as $homeAway) {
            $this->assignHomeAway($homeAway);
        }
    }

    /**
     * @param HomeAway $homeAway
     */
    protected function assignHomeAway(HomeAway $homeAway): void
    {
        foreach ($homeAway->getPlaceNrs() as $placeNr) {
            $this->assignedMap = $this->assignedMap->addPlaceNr($placeNr);
        }

        foreach ($homeAway->getAgainstPlaceNrCombinations() as $againstPlaceNrCombination) {
            $this->assignedAgainstMap = $this->assignedAgainstMap->addPlaceNrCombination($againstPlaceNrCombination);
        }

        foreach ($homeAway->getWithPlaceNrCombinations() as $withPlaceNrCombination) {
            $this->assignedWithMap = $this->assignedWithMap->addPlaceNrCombination($withPlaceNrCombination);
        }

        $this->assignedHomeMap = $this->assignedHomeMap->addPlaceNrCombination($homeAway->getHome());

        $this->assignToTogetherMap($homeAway->getHome());
        $this->assignToTogetherMap($homeAway->getAway());
    }

//    public function getAssignedPlaceCounter(Place $place): PlaceNrCounter|null
//    {
//        $this->assignedMap->
//        if (!isset($this->assignedMap[$place->getNumber()])) {
//            return null;
//        }
//        return $this->assignedMap[$place->getNumber()];
//    }

    public function getTogetherPlaceNrCounter(int $placeNr, int $coPlaceNr): PlaceNrCounter|null
    {
        if (!isset($this->assignedTogetherMap[$placeNr])
            || !isset($this->assignedTogetherMap[$placeNr][$coPlaceNr])) {
            return null;
        }
        return $this->assignedTogetherMap[$placeNr][$coPlaceNr];
    }

    /**
     * @param list<PlaceNrCombination> $placeNrCombinations
     * @param bool $withAssigned
     */
    public function assignTogether(array $placeNrCombinations, bool $withAssigned): void
    {
        foreach ($placeNrCombinations as $placeNrCombination) {
            $this->assignToTogetherMap($placeNrCombination);
            if( $this->hasAgainstSportWithMultipleSidePlaces ) {
                $this->assignedWithMap = $this->assignedWithMap->addPlaceNrCombination($placeNrCombination);
            }
            if( $withAssigned ) {
                foreach( $placeNrCombination->getPlaceNrs() as $placeNr ) {
                    $this->assignedMap = $this->assignedMap->addPlaceNr($placeNr);
                }
            }
        }
    }

//    /**
//     * @param PlaceNrCombination $placeNrCombination
//     */
//    protected function assignToMap(PlaceNrCombination $placeNrCombination): void
//    {
//        foreach ($placeNrCombination->getPlaceNrs() as $placeNr) {
//            $this->assignedMap[$placeNr]->increment();
//        }
//    }

    /**
     * @param PlaceNrCombination $placeNrCombination
     */
    protected function assignToTogetherMap(PlaceNrCombination $placeNrCombination): void
    {
        $placeNrs = $placeNrCombination->getPlaceNrs();
        foreach ($placeNrs as $placeNr) {
            foreach ($placeNrs as $coPlaceNr) {
                if ($coPlaceNr === $placeNr) {
                    continue;
                }
                $this->assignedTogetherMap[$placeNr][$coPlaceNr]->increment();
            }
        }
    }

    public function getAmountDifference(): int {
        return $this->assignedMap->getAmountDifference();
    }

    public function getAgainstAmountDifference(): int {
        return $this->assignedAgainstMap->getAmountDifference();
    }

    public function getWithAmountDifference(): int {
        return $this->assignedWithMap->getAmountDifference();
    }


    public function getHomeAmountDifference(): int {
        return $this->assignedHomeMap->getAmountDifference();
    }

    /**
     * @param list<Countable> $counters
     * @return int
     */
    protected function getMapDifference(array $counters): int {
        $counts = array_map( function(Countable $counter): int {
            return $counter->count();
        }, $counters );
        if( count($counts) === 0 ) {
            return 0;
        }
        return max($counts) - min($counts);
    }
}
