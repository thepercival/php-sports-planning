<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations\StatisticsCalculator\Against;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\PlaceCombinationCounterMap\Ranged as RangedPlaceCombinationCounterMap;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\Combinations\PlaceCombinationCounterMap;
use SportsPlanning\Combinations\PlaceCounterMap;
use SportsPlanning\Combinations\StatisticsCalculator;
use SportsPlanning\Schedule\Creator as ScheduleCreator;
use SportsPlanning\Place;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;

class GamesPerPlace extends StatisticsCalculator
{
    public function __construct(
        protected AgainstGppWithPoule $againstGppWithPoule,
        RangedPlaceCombinationCounterMap $assignedHomeMap,
        int $nrOfHomeAwaysAssigned,
        protected PlaceCounterMap $assignedSportMap,
        protected PlaceCounterMap $assignedMap,
        protected RangedPlaceCombinationCounterMap $assignedAgainstMap,
        protected RangedPlaceCombinationCounterMap $assignedWithMap,
        LoggerInterface $logger
    )
    {
        parent::__construct($assignedHomeMap,$nrOfHomeAwaysAssigned, $logger);
    }

    public function addHomeAway(HomeAway $homeAway): self
    {
        $assignedSportMap = $this->assignedSportMap;
        $assignedMap = $this->assignedMap;
        foreach ($homeAway->getPlaces() as $place) {
            $assignedSportMap = $assignedSportMap->addPlace($place);
            $assignedMap = $assignedMap->addPlace($place);
        }

        $assignedAgainstMap = $this->assignedAgainstMap;
        foreach ($homeAway->getAgainstPlaceCombinations() as $placeCombination) {
            $assignedAgainstMap = $assignedAgainstMap->addPlaceCombination($placeCombination);
        }

        $assignedWithMap = $this->assignedWithMap;
        foreach ($homeAway->getWithPlaceCombinations() as $placeCombination) {
            $assignedWithMap = $assignedWithMap->addPlaceCombination($placeCombination);
        }

        $assignedHomeMap = $this->assignedHomeMap->addPlaceCombination($homeAway->getHome());

        return new self(
            $this->againstGppWithPoule,
            $assignedHomeMap,
            $this->nrOfHomeAwaysAssigned + 1,
            $assignedSportMap,
            $assignedMap,
            $assignedAgainstMap,
            $assignedWithMap,
            $this->logger
        );
    }

    public function allAssigned(): bool
    {
        if ($this->nrOfHomeAwaysAssigned < $this->againstGppWithPoule->getTotalNrOfGames()) {
            return false;
        }

//        $allowedDifference = $this->assignAgainstGppSportsEqually ? 0 : 1;
//        if( $this->assignedMap->getMaxDifference() > $allowedDifference ) {
//            return false;
//        }

        // during assigning  margin = +1
        if( !$this->againstWithinMargin() ) {
//            $this->output();
            return false;
        }

        if( !$this->withWithinMargin() ) {
            return false;
        }
        return true;
    }

    public function againstWithinMargin(): bool
    {
        $nrOfGamesToGo = $this->againstGppWithPoule->getTotalNrOfGames() - $this->getNrOfHomeAwaysAssigned();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithPoule->getSportVariant()->getNrOfAgainstCombinationsPerGame();
        return $this->assignedAgainstMap->withinRange($nrOfPlaceCombinationsToGo);
    }


    public function withWithinMargin(): bool
    {
        $nrOfGamesToGo = $this->againstGppWithPoule->getTotalNrOfGames() - $this->getNrOfHomeAwaysAssigned();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithPoule->getSportVariant()->getNrOfWithCombinationsPerGame();
        return $this->assignedWithMap->withinRange($nrOfPlaceCombinationsToGo);
    }

    /**
     * @param list<HomeAway> $homeAways
     * @param LoggerInterface $logger
     * @return list<HomeAway>
     */
    public function sortHomeAways(array $homeAways, LoggerInterface $logger): array {
        // $time_start = microtime(true);

        $leastAmountAssigned = [];
        $leastAgainstAmountAssigned = [];
        $leastWithAmountAssigned = [];
        $leastHomeAmountAssigned = [];
        foreach($homeAways as $homeAway ) {
            $leastAmountAssigned[$homeAway->getIndex()] = $this->getLeastAssigned($this->assignedMap, $homeAway);
            $leastHomeAmountAssigned[$homeAway->getIndex()] = $this->getLeastWithCombinationAssigned($this->assignedHomeMap->getMap(), $homeAway);
            $leastAgainstAmountAssigned[$homeAway->getIndex()] = $this->getLeastAgainstCombinationAssigned($this->assignedAgainstMap->getMap(), $homeAway);
            $leastWithAmountAssigned[$homeAway->getIndex()] = $this->getLeastWithCombinationAssigned($this->assignedWithMap->getMap(), $homeAway);
        }
        uasort($homeAways, function (
            HomeAway $homeAwayA,
            HomeAway $homeAwayB
        ) use($leastAmountAssigned, $leastAgainstAmountAssigned, $leastWithAmountAssigned, $leastHomeAmountAssigned): int {

            list($amountLeastAssignedA, $nrOfLeastAssignedPlacesA) = $leastAmountAssigned[$homeAwayA->getIndex()];
            list($amountLeastAssignedB, $nrOfLeastAssignedPlacesB) = $leastAmountAssigned[$homeAwayB->getIndex()];
            if ($amountLeastAssignedA !== $amountLeastAssignedB) {
                return $amountLeastAssignedA - $amountLeastAssignedB;
            } else if ($nrOfLeastAssignedPlacesA !== $nrOfLeastAssignedPlacesB) {
                return $nrOfLeastAssignedPlacesB - $nrOfLeastAssignedPlacesA;
            }

            // if( $this->difference->scheduleMargin < ScheduleCreator::MAX_ALLOWED_GPP_MARGIN) {
                list($amountLeastAssignedAgainstA, $nrOfLeastAssignedAgainstA) = $leastAgainstAmountAssigned[$homeAwayA->getIndex()];
                list($amountLeastAssignedAgainstB, $nrOfLeastAssignedAgainstB) = $leastAgainstAmountAssigned[$homeAwayB->getIndex()];
                if ($amountLeastAssignedAgainstA !== $amountLeastAssignedAgainstB) {
                    return $amountLeastAssignedAgainstA - $amountLeastAssignedAgainstB;
                } else if ($nrOfLeastAssignedAgainstA !== $nrOfLeastAssignedAgainstB) {
                    return $nrOfLeastAssignedAgainstB - $nrOfLeastAssignedAgainstA;
                }
            // }

            // if( $this->difference->scheduleMargin < ScheduleCreator::MAX_ALLOWED_GPP_MARGIN) {
                list($amountLeastAssignedWithA, $nrOfLeastAssignedWithA) = $leastWithAmountAssigned[$homeAwayA->getIndex()];
                list($amountLeastAssignedWithB, $nrOfLeastAssignedWithB) = $leastWithAmountAssigned[$homeAwayB->getIndex()];
                if ($amountLeastAssignedWithA !== $amountLeastAssignedWithB) {
                    return $amountLeastAssignedWithA - $amountLeastAssignedWithB;
                } else if ($nrOfLeastAssignedWithA !== $nrOfLeastAssignedWithB) {
                    return $nrOfLeastAssignedWithB - $nrOfLeastAssignedWithA;
                }
            // }

            list($amountLeastAssignedHomeA, $nrOfLeastAssignedHomeA) = $leastHomeAmountAssigned[$homeAwayA->getIndex()];
            list($amountLeastAssignedHomeB, $nrOfLeastAssignedHomeB) = $leastHomeAmountAssigned[$homeAwayB->getIndex()];
            if ($amountLeastAssignedHomeA !== $amountLeastAssignedHomeB) {
                return $amountLeastAssignedHomeA - $amountLeastAssignedHomeB;
            }
            return $nrOfLeastAssignedHomeA - $nrOfLeastAssignedHomeB;
            // return 0;
        });
        // $logger->info("sorted homeaways in " . (microtime(true) - $time_start));
        // (new HomeAway($logger))->outputHomeAways(array_values($homeAways));
        return array_values($homeAways);
    }

    public function minimalSportCanStillBeAssigned(): bool {
        $nrOfGamesToGo = $this->againstGppWithPoule->getTotalNrOfGames() - $this->nrOfHomeAwaysAssigned;
        $minNrOfGamesPerPlace = $this->getMinNrOfGamesPerPlace();

        foreach( $this->againstGppWithPoule->getPoule()->getPlaces() as $place ) {
            if( ($this->assignedSportMap->count($place) + $nrOfGamesToGo) < $minNrOfGamesPerPlace ) {
                return false;
            }
        }
        return true;
    }

    public function sportWillBeOverAssigned(Place $place): bool
    {
        return $this->assignedSportMap->count($place) >= $this->getMaxNrOfGamesPerPlace();
    }

    private function getMinNrOfGamesPerPlace(): int {
        $totalNrOfGamesPerPlace = $this->againstGppWithPoule->getSportVariant()->getNrOfGamesPerPlace();
        return $totalNrOfGamesPerPlace - (!$this->againstGppWithPoule->allPlacesSameNrOfGamesAssignable() ? 1 : 0);
    }

    private function getMaxNrOfGamesPerPlace(): int {
        return $this->againstGppWithPoule->getSportVariant()->getNrOfGamesPerPlace();
    }

    private function getAgainstAmountAssigned(HomeAway $homeAway): int {
        $amount = 0;
        foreach($homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination ) {
            $amount += $this->assignedAgainstMap->count($againstPlaceCombination);
        }
        return $amount;
    }

    private function getWithAmountAssigned(HomeAway $homeAway): int {
        $amount = 0;
        foreach($homeAway->getWithPlaceCombinations() as $placeCombination ) {
            $amount += $this->assignedWithMap->count($placeCombination);
        }
        return $amount;
    }

    /**
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
     */
    public function filterBeforeGameRound(array $homeAways): array {
         $homeAways = array_filter(
            $homeAways,
            function (HomeAway $homeAway) : bool {
                $statisticsCalculator = $this->addHomeAway($homeAway);
                if( !$statisticsCalculator->againstWithinMargin() ) {
                    return false;
                }

                if( !$statisticsCalculator->withWithinMargin() ) {
                    return false;
                }
                 return true;
            }
        );
        return array_values($homeAways);
//         return $homeAways;
    }

    public function output(bool $withDetails): void {
        $header = 'nrOfHomeAwaysAssigned: ' . $this->nrOfHomeAwaysAssigned; //  . ', scheduleMargin : ' . $this->difference->scheduleMargin;
        $this->logger->info($header);
        $prefix = '    ';
        $this->outputAgainstTotals($prefix, $withDetails);
        $this->outputWithTotals($prefix, $withDetails);
        $this->outputHomeTotals($prefix, $withDetails);
    }

//    public function getAgainstShortage(): int {
//        $map = $this->assignedAgainstMap->getPerAmount();
//        $shortage = 0;
//        $minimum = $this->difference->allowedAgainstRange->getMin();
//        for( $amount = 0 ; $amount < $minimum ; $amount++ ) {
//            if( array_key_exists($amount, $map)) {
//                $shortage += count($map[$amount]);
//            }
//        }
//        if( array_key_exists($minimum, $map)) {
//            $shortage += count($map[$amount]);
//        }
//
//
//        foreach($map as $amount => $counters) {
//            if( $amount < $this->difference->allowedAgainstRange->getMin() ) {
//                $totalShortage = count($counters) * ($this->difference->allowedAgainstRange->getMin() - $amount);
//                $shortage += $totalShortage;
//            } else if( $amount === $this->difference->allowedAgainstRange->getMin()
//                && count($counters) < $this->difference->minNrOfAgainstAllowedToAssignedToMinimumCum ) {
//                $totalShortage = ($this->difference->minNrOfAgainstAllowedToAssignedToMinimumCum - count($counters));
//                $shortage += $totalShortage;
//            }
//        }
//        return $shortage;
//    }

    public function outputAgainstTotals(string $prefix, bool $withDetails): void {
        $header = 'AgainstTotals : ';
        $header .= ' allowedRange : ' . $this->assignedAgainstMap->getRange();
        $header .= ', belowMinimum : ' . $this->assignedAgainstMap->getNrOfPlaceCombinationsBelowMinimum();
        $this->logger->info($prefix . $header);

        $map = $this->assignedAgainstMap->getMap()->getAmountMap();
        $mapOutput = $prefix . 'map: ';
        foreach($map as $amount) {
            $mapOutput .= $amount  . ', ';
        }
        $this->logger->info($prefix . $mapOutput . 'difference : '.$this->assignedAgainstMap->getAmountDifference());

        if( !$withDetails ) {
            return;
        }
        foreach( $this->againstGppWithPoule->getPoule()->getPlaces() as $place ) {
            $this->outputAgainstPlaceTotals($place, $prefix . '    ');
        }
    }

    private function outputAgainstPlaceTotals(Place $place, string $prefix): void {
        $placeNr = $place->getNumber() < 10 ? '0' . $place->getNumber() : $place->getNumber();
        $out = $placeNr . " => ";
        foreach( $this->againstGppWithPoule->getPoule()->getPlaces() as $opponent ) {
            if( $opponent->getNumber() <= $place->getNumber() ) {
                $out .= '     ,';
            } else {
                $opponentNr = $opponent->getNumber() < 10 ? '0' . $opponent->getNumber() : $opponent->getNumber();
                $placeCombination = new PlaceCombination([$place, $opponent]);
                $out .= '' . $opponentNr . ':' . $this->getOutputAmount($placeCombination) . ',';
            }
        }
        $this->logger->info($prefix . $out);
    }

    private function getOutputAmount(PlaceCombination $placeCombination): string {
        return $this->assignedAgainstMap->count($placeCombination) . 'x';
    }

    public function outputWithTotals(string $prefix, bool $withDetails): void
    {
        $header = 'WithTotals : ';
        $header .= ' allowedRange : ' . $this->assignedWithMap->getRange();
        $header .= ', belowMinimum : ' . $this->assignedWithMap->getNrOfPlaceCombinationsBelowMinimum();
        $this->logger->info($prefix . $header);

        $map = $this->assignedWithMap->getMap()->getAmountMap();
        $mapOutput = $prefix . 'map: ';
        foreach($map as $amount) {
            $mapOutput .= $amount  . ', ';
        }
        $this->logger->info($prefix . $mapOutput . 'difference : '.$this->assignedWithMap->getAmountDifference());

        if( !$withDetails ) {
            return;
        }
        $prefix =  '    ' . $prefix;
        $amountPerLine = 4; $counter = 0; $line = '';
        foreach( $this->assignedWithMap->getMap()->getList() as $counterIt ) {
            $line .= $counterIt->getPlaceCombination() . ' ' . $counterIt->count() . 'x, ';
            if( ++$counter === $amountPerLine ) {
                $this->logger->info($prefix . $line);
                $counter = 0;
                $line = '';
            }
        }
        if( strlen($line) > 0 ) {
            $this->logger->info($prefix . $line);
        }
    }
}
