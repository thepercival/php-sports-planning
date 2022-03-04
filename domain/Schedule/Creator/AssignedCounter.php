<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\Creator;

use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\HomeAwayCreator\GamesPerPlace as GppHomeAwayCreator;
use SportsPlanning\Combinations\HomeAwayCreator\H2h as H2hHomeAwayCreator;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Combinations\Indirect\Map as IndirectMap;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\Combinations\MultipleCombinationsCounter\Against as AgainstCounter;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;

class AssignedCounter
{
    /**
     * @var array<int, PlaceCounter>
     */
    protected array $assignedMap = [];
    /**
     * @var array<int, PlaceCombinationCounter>
     */
    protected array $assignedWithMap = [];
    protected IndirectMap $assignedAgainstMap;
    /**
     * @var array<int, PlaceCounter>
     */
    protected array $assignedHomeMap = [];
    /**
     * @var array<string,array<string,PlaceCounter>>
     */
    protected array $assignedTogetherMap = [];

    /**
     * @param Poule $poule
     * @param list<AgainstH2h|AgainstGpp|Single|AllInOneGame> $sportVariants
     */
    public function __construct(Poule $poule, array $sportVariants)
    {
        foreach ($poule->getPlaces() as $place) {
            $this->assignedMap[$place->getNumber()] = new PlaceCounter($place);
            $this->assignedHomeMap[$place->getNumber()] = new PlaceCounter($place);

            $this->assignedTogetherMap[$place->getLocation()] = [];
            foreach ($poule->getPlaces() as $coPlace) {
                if ($coPlace === $place) {
                    continue;
                }
                $this->assignedTogetherMap[$place->getLocation()][$coPlace->getLocation()] = new PlaceCounter($coPlace);
            }
        }

        foreach ($sportVariants as $sportVariant) {
            if ($sportVariant instanceof AgainstH2h || $sportVariant instanceof AgainstGpp) {
                $homeAwayCreator = $this->getHomeAwayCreator($poule, $sportVariant);
                if ($homeAwayCreator instanceof H2hHomeAwayCreator) {
                    $homeAways = $homeAwayCreator->createForOneH2H();
                    $this->initAssignedWithMap($homeAways);
                } elseif ($sportVariant instanceof AgainstGpp) {
                    $homeAways = $homeAwayCreator->create($sportVariant);
                    $this->initAssignedWithMap($homeAways);
                }
                // $this->initAssignedAgainstMap();
            }
        }
        $this->assignedAgainstMap = new IndirectMap();
    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     */
    protected function initAssignedWithMap(array $homeAways): void
    {
        foreach ($homeAways as $homeAway) {
            $home = $homeAway->getHome();
            if (!isset($this->assignedWithMap[$home->getNumber()])) {
                $this->assignedWithMap[$home->getNumber()] = new PlaceCombinationCounter($home);
            }
            $away = $homeAway->getAway();
            if (!isset($this->assignedWithMap[$away->getNumber()])) {
                $this->assignedWithMap[$away->getNumber()] = new PlaceCombinationCounter($away);
            }
        }
    }

//    protected function initAssignedAgainstMap(): void
//    {
//        $possibleHomeAways = array_map(function (PlaceCombinationCounter $counter): PlaceCombination {
//            return $counter->getPlaceCombination();
//        }, $this->assignedWithMap);
//        for ($counter = 0; $counter < count($possibleHomeAways); $counter++) {
//            $possibleHomeAway = array_shift($possibleHomeAways);
//            if ($possibleHomeAway === null) {
//                return;
//            }
//            $idx = $possibleHomeAway->getNumber();
//            $this->assignedAgainstMap[$idx] = new AgainstCounter($possibleHomeAway, array_values($possibleHomeAways));
//            array_push($possibleHomeAways, $possibleHomeAway);
//        }
//    }

    /**
     * @return array<int, PlaceCounter>
     */
    public function getAssignedMap(): array
    {
        return $this->assignedMap;
    }

    /**
     * @return array<int, PlaceCombinationCounter>
     */
    public function getAssignedWithMap(): array
    {
        return $this->assignedWithMap;
    }

    public function getAssignedAgainstMap(): IndirectMap
    {
        return $this->assignedAgainstMap;
    }

    /**
     * @return array<int, PlaceCounter>
     */
    public function getAssignedHomeMap(): array
    {
        return $this->assignedHomeMap;
    }

    /**
     * @return array<string,array<string,PlaceCounter>>
     */
    public function getAssignedTogetherMap(): array
    {
        return $this->assignedTogetherMap;
    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     */
    public function assignAgainstHomeAways(array $homeAways): void
    {
        foreach ($homeAways as $homeAway) {
            $this->assignAgainst($homeAway);
        }
    }

    /**
     * @param AgainstHomeAway $homeAway
     */
    protected function assignAgainst(AgainstHomeAway $homeAway): void
    {
        $this->assignToMap($homeAway->getHome());
        $this->assignToMap($homeAway->getAway());

        $this->assignToWithMap($homeAway->getHome());
        $this->assignToWithMap($homeAway->getAway());
        $this->assignedAgainstMap = $this->assignedAgainstMap->addHomeAway($homeAway);

        foreach ($homeAway->getHome()->getPlaces() as $homePlace) {
            $this->assignedHomeMap[$homePlace->getNumber()]->increment();
        }

        $this->assignToTogetherMap($homeAway->getHome());
        $this->assignToTogetherMap($homeAway->getAway());
    }


    public function getAssignedPlaceCounter(Place $place): PlaceCounter|null
    {
        if (!isset($this->assignedMap[$place->getNumber()])) {
            return null;
        }
        return $this->assignedMap[$place->getNumber()];
    }

    public function getTogetherPlaceCounter(Place $place, Place $coPlace): PlaceCounter|null
    {
        if (!isset($this->assignedTogetherMap[$place->getLocation()])
            || !isset($this->assignedTogetherMap[$place->getLocation()][$coPlace->getLocation()])) {
            return null;
        }
        return $this->assignedTogetherMap[$place->getLocation()][$coPlace->getLocation()];
    }

    /**
     * @param list<PlaceCombination> $placeCombinations
     */
    public function assignTogether(array $placeCombinations): void
    {
        foreach ($placeCombinations as $placeCombination) {
            $this->assignToMap($placeCombination);
            $this->assignToTogetherMap($placeCombination);
            $this->assignToWithMap($placeCombination);
        }
    }
//
//
//    /**
//     * @param PlaceCombination $placeCombination
//     */
//    protected function assignPlaceCombination(PlaceCombination $placeCombination): void
//    {

//        $this->assignToAgainstMap($placeCombination);
//    }

    /**
     * @param PlaceCombination $placeCombination
     */
    protected function assignToMap(PlaceCombination $placeCombination): void
    {
        foreach ($placeCombination->getPlaces() as $place) {
            $this->assignedMap[$place->getNumber()]->increment();
        }
    }

    /**
     * @param PlaceCombination $placeCombination
     */
    protected function assignToWithMap(PlaceCombination $placeCombination): void
    {
        if (!isset($this->assignedWithMap[$placeCombination->getNumber()])) {
            $this->assignedWithMap[$placeCombination->getNumber()] = new PlaceCombinationCounter($placeCombination);
        }
        $this->assignedWithMap[$placeCombination->getNumber()]->increment();
    }

    /**
     * @param PlaceCombination $placeCombination
     */
    protected function assignToTogetherMap(PlaceCombination $placeCombination): void
    {
        $places = $placeCombination->getPlaces();
        foreach ($places as $placeIt) {
            foreach ($places as $coPlace) {
                if ($coPlace === $placeIt) {
                    continue;
                }
                $this->assignedTogetherMap[$placeIt->getLocation()][$coPlace->getLocation()]->increment();
            }
        }
    }

    protected function getHomeAwayCreator(
        Poule $poule,
        AgainstH2h|AgainstGpp $sportVariant
    ): H2hHomeAwayCreator|GppHomeAwayCreator {
        if ($sportVariant instanceof AgainstH2h) {
            return new H2hHomeAwayCreator($poule);
        }
        return new GppHomeAwayCreator($poule);
    }
}
