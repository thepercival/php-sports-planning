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
        PlaceCombinationCounterMap $assignedHomeMap,
        int $nrOfHomeAwaysAssigned,
        protected PlaceCounterMap $assignedSportMap,
        protected PlaceCounterMap $assignedMap,
        protected RangedPlaceCombinationCounterMap $assignedAgainstMap,
        protected RangedPlaceCombinationCounterMap $assignedWithMap,
        protected readonly bool $assignAgainstGppSportsEqually,
        protected readonly AmountRange $againstAmountRange,
        protected readonly AmountRange $withAmountRange,
        protected LoggerInterface $logger
    )
    {
        parent::__construct($assignedHomeMap,$nrOfHomeAwaysAssigned);
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
            $this->assignAgainstGppSportsEqually,
            $this->againstAmountRange,
            $this->withAmountRange,
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
        if( !$this->againstWithinMarginEnd() ) {
//            $this->output();
            return false;
        }

        if( !$this->withWithinMarginEnd() ) {
            return false;
        }
        return true;
    }

    public function againstWithinMarginEnd(): bool
    {
        return $this->againstWithinMarginHelper();
//        if( $this->assignedAgainstMap->getMaxDifference() > $this->difference->allowedCumMargin ) {
//            return false;
//        }
//        if( $this->assignedAgainstMap->getMax() > $this->difference->allowedAgainstRange->getMax() ) {
////            $this->output();
//            return false;
//        }
//
//        $nrOfGamesToGo = $this->againstGppWithPoule->getTotalNrOfGames() - $this->getNrOfHomeAwaysAssigned();
//        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithPoule->getSportVariant()->getNrOfAgainstCombinationsPerGame();
//        return $this->assignedAgainstMap->withinRange($nrOfPlaceCombinationsToGo);
    }

    public function againstWithinMarginDuring(): bool
    {
        return $this->againstWithinMarginHelper(2);
    }

    private function againstWithinMarginHelper(int|null $minimumDifference = null): bool
    {
//        $allowedAmountDifference = $this->againstAmountRange->getAmountDifference();
//        if( $minimumDifference !== null && $allowedAmountDifference < $minimumDifference ) {
//            $allowedAmountDifference = $minimumDifference;
//        }
//        if( $this->assignedAgainstMap->getAmountDifference() > $allowedAmountDifference ) {
//            return false;
//        }
//        if( $this->assignedAgainstMap->getAmountDifference() < $allowedAmountDifference ) {
//            return true;
//        }

        $nrOfGamesToGo = $this->againstGppWithPoule->getTotalNrOfGames() - $this->getNrOfHomeAwaysAssigned();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithPoule->getSportVariant()->getNrOfAgainstCombinationsPerGame();
        return $this->assignedAgainstMap->withinRange($nrOfPlaceCombinationsToGo);
    }


    public function withWithinMarginEnd(): bool
    {
        return $this->withWithinMarginHelper();
//        if( $this->assignedWithMap->getMaxDifference() > $this->difference->allowedCumMargin ) {
//            return false;
//        }
//        if( $this->assignedWithMap->getMax() > $this->difference->allowedWithRange->getMax() ) {
////            $this->output();
//            return false;
//        }
//
//        $nrOfGamesToGo = $this->withGppWithPoule->getTotalNrOfGames() - $this->getNrOfHomeAwaysAssigned();
//        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->withGppWithPoule->getSportVariant()->getNrOfWithCombinationsPerGame();
//        return $this->assignedWithMap->withinRange($nrOfPlaceCombinationsToGo);
    }

    public function withWithinMarginDuring(): bool
    {
        return $this->withWithinMarginHelper(2);
    }

    private function withWithinMarginHelper(int|null $minimumDifference = null): bool
    {
//        $allowedDifference = $this->difference->allowedWithRange->difference();
//        if( $minimumDifference !== null && $allowedDifference < $minimumDifference ) {
//            $allowedDifference = $minimumDifference;
//        }
//        if( $this->assignedWithMap->getMax() > $this->difference->allowedWithRange->getMax() ) {
////            $this->output();
//            return false;
//        }
//        if( $this->assignedWithMap->getMaxDifference() > $allowedDifference ) {
//            return false;
//        }

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
            $leastHomeAmountAssigned[$homeAway->getIndex()] = $this->getLeastWithCombinationAssigned($this->assignedHomeMap, $homeAway);
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

//    public function minimalWithCanStillBeAssigned(HomeAway|null $homeAway): bool {
//        $nrOfWithCombinationsPerGame = $this->againstGppWithPoule->getSportVariant()->getNrOfWithCombinationsPerGame();
//        $nrOfGamesToGo = $this->againstGppWithPoule->getTotalNrOfGames() - $this->nrOfHomeAwaysAssigned;
//        $nrOfWithCombinationsTogo = $nrOfGamesToGo * $nrOfWithCombinationsPerGame;
//        if( $this->withShortage <= $nrOfWithCombinationsTogo ) {
//            return true;
//        }
//        if( $homeAway === null) {
//            return false;
//        }
//        $withShortageIncludingHomeAway = $this->getWithShortageIncludingHomeAway($homeAway);
//        return $withShortageIncludingHomeAway <= $nrOfWithCombinationsTogo;
//    }
//
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
            function (HomeAway $homeAway) /*use($self)*/ : bool {
//                $self2 = $self->addHomeAway($homeAway);
                $statisticsCalculator = $this->addHomeAway($homeAway);
                if( !$statisticsCalculator->againstWithinMarginDuring() ) {
                    return false;
                }

                if( !$statisticsCalculator->withWithinMarginDuring() ) {
                    return false;
                }
//                return $self2->againstWithinMarginDuring() && $self2->withWithinMarginDuring();
                 return true;
                // return $this->assignedAgainstMap->getMax() > $this->difference->allowedAgainstAmount;
                // return !$this->withWillBeOverAssigned($homeAway) && !$this->againstWillBeOverAssigned($homeAway)
                    /*&& !$this->againstWillBeTooMuchDiffAssigned($homeAway)
                    && !$this->withWillBeTooMuchDiffAssigned($homeAway)*/;
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
        if( $withDetails ) {
            $this->assignedHomeMap->output( $this->logger, $prefix, 'HomeTotals');
        } else {
            $header = 'HomeTotals ( difference : '.$this->assignedHomeMap->getAmountDifference() .' )';
            $this->logger->info($prefix . $header);

            $map = $this->assignedHomeMap->getAmountMap();
            $mapOutput = 'map: ';
            foreach($map as $amount) {
                $mapOutput .= $amount . ', ';
            }
            $this->logger->info($prefix . $mapOutput);
        }
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
        $header .= ' range : ' . $this->againstAmountRange;
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
        $header .= ' range : ' . $this->withAmountRange;
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
