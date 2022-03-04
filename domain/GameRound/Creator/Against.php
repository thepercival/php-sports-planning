<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\Indirect\Map as IndirectMap;
use SportsPlanning\Combinations\MultipleCombinationsCounter\Against as AgainstCounter;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\SportVariant\WithPoule as VariantWithPoule;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\HomeAwayCreator\GamesPerPlace as GppHomeAwayCreator;
use SportsPlanning\Combinations\HomeAwayCreator\H2h as H2hHomeAwayCreator;
use SportsPlanning\Combinations\Output\GameRound as GameRoundOutput;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\GameRound\Creator\Against\GamesPerPlace as GppGameRoundCreator;
use SportsPlanning\GameRound\Creator\Against\H2h as H2hGameRoundCreator;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;

abstract class Against
{
    protected GameRoundOutput $gameRoundOutput;

    public function __construct(protected LoggerInterface $logger)
    {
        $this->gameRoundOutput = new GameRoundOutput($logger);
    }

    /**
     * @param AgainstGameRound $gameRound
     * @param list<AgainstHomeAway> $homeAways
     * @return AgainstGameRound
     */
    protected function toNextGameRound(AgainstGameRound $gameRound, array &$homeAways): AgainstGameRound
    {
        foreach ($gameRound->getHomeAways() as $homeAway) {
            $foundHomeAwayIndex = array_search($homeAway, $homeAways, true);
            if ($foundHomeAwayIndex !== false) {
                array_splice($homeAways, $foundHomeAwayIndex, 1);
            }
        }
        return $gameRound->createNext();
    }

    /**
     * @param array<int, PlaceCounter> $placeCounters
     * @return array<int, PlaceCounter>
     */
    protected function copyCounters(array $placeCounters): array
    {
        return array_map(fn(PlaceCounter $placeCounter) => clone $placeCounter, $placeCounters);
    }

    /**
     * @param array<int, PlaceCombinationCounter> $counters
     * @return array<int, PlaceCombinationCounter>
     */
    protected function copyWithCounters(array $counters): array
    {
        return array_map(fn(PlaceCombinationCounter $counter) => clone $counter, $counters);
    }

    /**
     * @param array<int, AgainstCounter> $againstCounters
     * @return array<int, AgainstCounter>
     */
    protected function copyAgainstCounters(array $againstCounters): array
    {
        return array_map(fn(AgainstCounter $againstCounter) => clone $againstCounter, $againstCounters);
    }

    protected function isGameRoundCompleted(VariantWithPoule $variantWithPoule, AgainstGameRound $gameRound): bool
    {
        return count($gameRound->getHomeAways()) === $variantWithPoule->getNrOfGamesSimultaneously();
    }

    /**
     * @param VariantWithPoule $variantWithPoule
     * @param array<int, PlaceCounter> $assignedMap
     * @return bool
     */
    protected function isCompleted(VariantWithPoule $variantWithPoule, array $assignedMap): bool
    {
        $nrOfIncompletePlaces = 0;
        foreach ($assignedMap as $assignedCounter) {
            if ($assignedCounter->count() < $variantWithPoule->getTotalNrOfGamesPerPlace()) {
                $nrOfIncompletePlaces++;
            }

            if ($nrOfIncompletePlaces >= $variantWithPoule->getNrOfGamePlaces()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param AgainstGameRound $gameRound
     * @param AgainstHomeAway $homeAway
     * @param array<int, PlaceCounter> $assignedSportMap
     * @param array<int, PlaceCounter> $assignedMap
     * @param array<int, PlaceCombinationCounter> $assignedWithMap
     * @param IndirectMap $assignedAgainstMap
     * @param array<int, PlaceCounter> $assignedHomeMap
     */
    protected function assignHomeAway(
        AgainstGameRound $gameRound,
        AgainstHomeAway $homeAway,
        array &$assignedSportMap,
        array &$assignedMap,
        array &$assignedWithMap,
        IndirectMap $assignedAgainstMap,
        array &$assignedHomeMap
    ): IndirectMap {
        foreach ($homeAway->getPlaces() as $place) {
            $assignedSportMap[$place->getNumber()]->increment();
            $assignedMap[$place->getNumber()]->increment();
        }
        $assignedWithMap[$homeAway->getHome()->getNumber()]->increment();
        $assignedWithMap[$homeAway->getAway()->getNumber()]->increment();
//        $assignedAgainstMap[$homeAway->getHome()->getNumber()]->addCombination($homeAway->getAway());
//        $assignedAgainstMap[$homeAway->getAway()->getNumber()]->addCombination($homeAway->getHome());
        foreach ($homeAway->getHome()->getPlaces() as $homePlace) {
            $assignedHomeMap[$homePlace->getNumber()]->increment();
        }
        $gameRound->add($homeAway);
        return $assignedAgainstMap->addHomeAway($homeAway);
    }

    /**
     * @param AgainstHomeAway $homeAway
     * @param array<int, PlaceCounter> $assignedMap
     * @return list<int>
     */
    protected function getLeastAmountAssigned(AgainstHomeAway $homeAway, array $assignedMap): array
    {
        $leastAmount = -1;
        $nrOfPlaces = 0;
        foreach ($homeAway->getPlaces() as $place) {
            $amountAssigned = $assignedMap[$place->getNumber()]->count();
            if ($leastAmount === -1 || $amountAssigned < $leastAmount) {
                $leastAmount = $amountAssigned;
                $nrOfPlaces = 0;
            }
            if ($amountAssigned === $leastAmount) {
                $nrOfPlaces++;
            }
        }
        return [$leastAmount, $nrOfPlaces];
    }

    /**
     * @param AgainstHomeAway $homeAway
     * @param array<int, PlaceCombinationCounter> $assignedWithMap
     * @return int
     */
    protected function getWithAmountAssigned(AgainstHomeAway $homeAway, array $assignedWithMap): int
    {
        $homeWithAmountAssigned = $assignedWithMap[$homeAway->getHome()->getNumber()]->count();
        $awayWithAmountAssigned = $assignedWithMap[$homeAway->getAway()->getNumber()]->count();
        return $homeWithAmountAssigned + $awayWithAmountAssigned;
    }

    protected function releaseHomeAway(AgainstGameRound $gameRound, AgainstHomeAway $homeAway): void
    {
        $gameRound->remove($homeAway);
    }

    /**
     * @param VariantWithPoule $variantWithPoule
     * @param AgainstGameRound $gameRound
     * @param AgainstHomeAway $homeAway
     * @param array<int, PlaceCounter> $assignedSportMap
     * @return bool
     */
    protected function isHomeAwayAssignable(
        VariantWithPoule $variantWithPoule,
        AgainstGameRound $gameRound,
        AgainstHomeAway $homeAway,
        array $assignedSportMap
    ): bool {
        foreach ($homeAway->getPlaces() as $place) {
            if ($gameRound->isParticipating($place) || $this->willBeOverAssigned(
                    $variantWithPoule,
                    $place,
                    $assignedSportMap
                )) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param VariantWithPoule $variantWithPoule
     * @param Place $place
     * @param array<int, PlaceCounter> $assignedSportMap
     * @return bool
     */
    private function willBeOverAssigned(VariantWithPoule $variantWithPoule, Place $place, array $assignedSportMap): bool
    {
        $totalNrOfGamesPerPlace = $variantWithPoule->getTotalNrOfGamesPerPlace();
        $sportVariant = $variantWithPoule->getSportVariant();
        $nrOfPlaces = $variantWithPoule->getNrOfPlaces();
        $notAllPlacesPlaySameNrOfGames = $sportVariant instanceof AgainstGpp
            && !$sportVariant->allPlacesPlaySameNrOfGames($nrOfPlaces);
        $totalNrOfGamesPerPlace = $totalNrOfGamesPerPlace + ($notAllPlacesPlaySameNrOfGames ? 1 : 0);
//        if ($assignedMap[$place->getNumber()]->count() > $totalNrOfGamesPerPlace === true) {
//            $rofirjf = 123;
//        }
        return $assignedSportMap[$place->getNumber()]->count() > $totalNrOfGamesPerPlace;
    }

    /**
     * @param VariantWithPoule $variantWithPoule
     * @param int $currentGameRoundNumber
     * @param list<AgainstHomeAway> $homeAways
     * @return bool
     */
    protected function isOverAssigned(
        VariantWithPoule $variantWithPoule,
        int $currentGameRoundNumber,
        array $homeAways
    ): bool {
        if ($this instanceof H2hGameRoundCreator) {
            return false;
        }
//        $sportVariant = $this->getSportVariant();
//        if ($sportVariant instanceof AgainstGpp
//                && !$sportVariant->allPlacesPlaySameNrOfGames(count($poule->getPlaces()))
//            ) {
//            return false;
//        }
        $poule = $variantWithPoule->getPoule();
        $unassignedMap = [];
        foreach ($poule->getPlaces() as $place) {
            $unassignedMap[$place->getNumber()] = new PlaceCounter($place);
        }
        foreach ($homeAways as $homeAway) {
            foreach ($homeAway->getPlaces() as $place) {
                $unassignedMap[$place->getNumber()]->increment();
            }
        }

        $maxNrOfGameGroups = $variantWithPoule->getNrOfGameGroups();
        foreach ($poule->getPlaces() as $place) {
            if ($currentGameRoundNumber + $unassignedMap[$place->getNumber()]->count() > $maxNrOfGameGroups) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Poule $poule
     * @return array<int, PlaceCounter>
     */
    protected function getAssignedSportCounters(Poule $poule): array
    {
        $map = [];
        foreach ($poule->getPlaces() as $place) {
            $map[$place->getNumber()] = new PlaceCounter($place);
        }
        return $map;
    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     */
    protected function outputUnassignedHomeAways(array $homeAways): void
    {
        $this->logger->info('unassigned');
        foreach ($homeAways as $homeAway) {
            $this->logger->info($homeAway);
        }
    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     */
    protected function outputUnassignedTotals(array $homeAways): void
    {
        $map = [];
        foreach ($homeAways as $homeAway) {
            foreach ($homeAway->getPlaces() as $place) {
                if (!isset($map[$place->getLocation()])) {
                    $map[$place->getLocation()] = new PlaceCounter($place);
                }
                $map[$place->getLocation()]->increment();
            }
        }
        foreach ($map as $location => $placeCounter) {
            $this->logger->info($location . ' => ' . $placeCounter->count());
        }
    }
}
