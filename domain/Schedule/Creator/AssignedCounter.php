<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\Creator;

use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\HomeAwayCreator;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;
use SportsHelpers\Sport\Variant\Against as AgainstVariant;
use SportsHelpers\Sport\Variant\Single as SingleVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameVariant;

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
     * @param list<AgainstVariant|SingleVariant|AllInOneGameVariant> $sportVariants
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
            if ($sportVariant instanceof AgainstVariant) {
                $homeAwayCreator = new HomeAwayCreator($poule, $sportVariant);
                $homeAways = $homeAwayCreator->createForOneH2H();
                $this->initAssignedWithMap($homeAways);
            }
        }
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
    public function assignHomeAways(array $homeAways): void
    {
        foreach ($homeAways as $homeAway) {
            $this->assignHomeAway($homeAway);
        }
    }

    /**
     * @param AgainstHomeAway $homeAway
     */
    public function assignHomeAway(AgainstHomeAway $homeAway): void
    {
        $this->assignToMap($homeAway->getHome());
        $this->assignToMap($homeAway->getAway());

        $this->assignToWithMap($homeAway->getHome());
        $this->assignToWithMap($homeAway->getAway());

        foreach ($homeAway->getHome()->getPlaces() as $homePlace) {
            $this->assignedHomeMap[$homePlace->getNumber()]->increment();
        }

        $this->assignToTogetherMap($homeAway->getHome());
        $this->assignToTogetherMap($homeAway->getAway());
    }

    /**
     * @param list<PlaceCombination> $placeCombinations
     */
    public function assignPlaceCombinations(array $placeCombinations): void
    {
        foreach ($placeCombinations as $placeCombination) {
            $this->assignPlaceCombination($placeCombination);
        }
    }


    /**
     * @param PlaceCombination $placeCombination
     */
    public function assignPlaceCombination(PlaceCombination $placeCombination): void
    {
        $this->assignToMap($placeCombination);
        $this->assignToTogetherMap($placeCombination);
        $this->assignToWithMap($placeCombination);
    }

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
}
