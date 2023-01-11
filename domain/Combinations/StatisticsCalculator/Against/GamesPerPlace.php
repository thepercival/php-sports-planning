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
use SportsPlanning\PlaceCounter;
use SportsPlanning\Schedule\CreatorHelpers\AgainstGppDifference;
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
        protected readonly AgainstGppDifference $difference,
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
            $this->difference,
            $this->logger
        );
    }

    public function allAssigned(): bool
    {
        if ($this->nrOfHomeAwaysAssigned < $this->againstGppWithPoule->getTotalNrOfGames()) {
            return false;
        }

        $allowedDifference = $this->assignAgainstGppSportsEqually ? 0 : 1;
        if( $this->assignedMap->getMaxDifference() > $allowedDifference ) {
            return false;
        }

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
        if( !$this->assignedAgainstMap->withinRange(0) ) {
            return false;
        }

        if( $this->assignedAgainstMap->getMaxDifference() > $this->difference->allowedCumMargin ) {
            return false;
        }
        if( !$this->difference->lastSport && $this->assignedAgainstMap->getMax() > $this->difference->allowedAgainstRange->getMax() ) {
//            $this->output();
            return false;
        }

        $nrOfGamesToGo = $this->againstGppWithPoule->getTotalNrOfGames() - $this->getNrOfHomeAwaysAssigned();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithPoule->getSportVariant()->getNrOfAgainstCombinationsPerGame();
        return $this->assignedAgainstMap->withinRange($nrOfPlaceCombinationsToGo);
    }

    public function againstWithinMarginDuring(): bool
    {
//        $allowedMargin = $this->difference->allowedCumMargin;
//        if( $allowedMargin < 2 ) {
//            $allowedMargin = 2;
//        }
//        if( /*!$this->difference->lastSport &&*/ $this->assignedAgainstMap->getMax() > $this->difference->allowedAgainstRange->getMax() ) {
////            $this->output();
//            return false;
//        }
//        if( $this->assignedAgainstMap->getMaxDifference() > $allowedMargin ) {
//            return false;
//        }

        $nrOfGamesToGo = $this->againstGppWithPoule->getTotalNrOfGames() - $this->getNrOfHomeAwaysAssigned();
//        if( $this->getNrOfHomeAwaysAssigned() > 107) {
//            $this->output(false);
//        }
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithPoule->getSportVariant()->getNrOfAgainstCombinationsPerGame();
        if( !$this->assignedAgainstMap->withinRange($nrOfPlaceCombinationsToGo) ) {
            return false;
        }

//        if ( $this->difference->allowedCumMargin === 2 && $this->assignedAgainstMap->getMaxDifference() === 3 ) {
//            return true;
//        }
//
//        if ( $this->difference->allowedCumMargin === 2
//            && ($this->assignedAgainstMap->getMaxDifference() === 3 || $this->assignedAgainstMap->getMaxDifference() === 4 ) ) {
//            return true;
//        }

//        if( $this->difference->allowedCumMargin === 0 && $this->assignedAgainstMap->getMax() > $this->difference->allowedAgainstAmount ) {
//            return false;
//        }
        return true;
    }


    public function withWithinMargin(): bool
    {
        if( !$this->assignedWithMap->withinRange(0) ) {
            return false;
        }

        if( $this->assignedWithMap->getMaxDifference() > $this->difference->allowedCumMargin ) {
            return false;
        }
        if( !$this->difference->lastSport && $this->assignedWithMap->getMax() > $this->difference->allowedWithRange->getMax() ) {
//            $this->output();
            return false;
        }

//        if ( $this->difference->allowedCumMargin === 2
//            && ($this->assignedWithMap->getMaxDifference() === 3 || $this->assignedWithMap->getMaxDifference() === 4 ) ) {
//            return true;
//        }

        $nrOfGamesToGo = $this->againstGppWithPoule->getTotalNrOfGames() - $this->getNrOfHomeAwaysAssigned();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithPoule->getSportVariant()->getNrOfWithCombinationsPerGame();
        return $this->assignedWithMap->withinRange($nrOfPlaceCombinationsToGo);
    }

    public function withWithinMarginDuring(): bool
    {
//        $allowedMargin = $this->difference->allowedCumMargin;
//        if( $allowedMargin < 1 ) {
//            $allowedMargin = 1;
//        }
//        if( /*!$this->difference->lastSport &&*/ $this->assignedWithMap->getMax() > $this->difference->allowedWithRange->getMax() ) {
////            $this->output();
//            return false;
//        }
//        if( $this->assignedWithMap->getMaxDifference() <= $allowedMargin ) {
//            return true;
//        }

        $nrOfGamesToGo = $this->againstGppWithPoule->getTotalNrOfGames() - $this->getNrOfHomeAwaysAssigned();
//        if( $this->getNrOfHomeAwaysAssigned() > 107) {
//            $this->output(false);
//        }
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithPoule->getSportVariant()->getNrOfWithCombinationsPerGame();
        if( !$this->assignedWithMap->withinRange($nrOfPlaceCombinationsToGo) ) {
            return false;
        }

//        if( $this->difference->allowedCumMargin === 0 && $this->assignedWithMap->getMax() > $this->difference->allowedWithAmount ) {
//            return false;
//        }
        return true;
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
            $leastHomeAmountAssigned[$homeAway->getIndex()] = $this->getLeastCombinationAssigned($this->assignedHomeMap, $homeAway);
            $leastAgainstAmountAssigned[$homeAway->getIndex()] = $this->getLeastCombinationAssigned($this->assignedAgainstMap->getMap(), $homeAway);
            $leastWithAmountAssigned[$homeAway->getIndex()] = $this->getLeastCombinationAssigned($this->assignedWithMap->getMap(), $homeAway);
        }
        uasort($homeAways, function (
            HomeAway $homeAwayA,
            HomeAway $homeAwayB
        ) use($leastAmountAssigned, $leastAgainstAmountAssigned, $leastWithAmountAssigned, $leastHomeAmountAssigned): int {

            list($amountA, $nrOfPlacesA) = $leastAmountAssigned[$homeAwayA->getIndex()];
            list($amountB, $nrOfPlacesB) = $leastAmountAssigned[$homeAwayB->getIndex()];
            if ($amountA !== $amountB) {
                return $amountA - $amountB;
            }
            if ($nrOfPlacesA !== $nrOfPlacesB) {
                return $nrOfPlacesB - $nrOfPlacesA;
            }

            if( $this->difference->scheduleMargin < ScheduleCreator::MAX_ALLOWED_GPP_MARGIN) {
                list($amountAgainstA, $nrOfPlacesAgainstA) = $leastAgainstAmountAssigned[$homeAwayA->getIndex()];
                list($amountAgainstB, $nrOfPlacesAgainstB) = $leastAgainstAmountAssigned[$homeAwayB->getIndex()];
                if ($amountAgainstA !== $amountAgainstB) {
                    return $amountAgainstA - $amountAgainstB;
                } else if ($nrOfPlacesAgainstA !== $nrOfPlacesAgainstB) {
                    return $amountAgainstB - $amountAgainstA;
                }
            }

            if( $this->difference->scheduleMargin < ScheduleCreator::MAX_ALLOWED_GPP_MARGIN) {
                list($amountWithA, $nrOfPlacesWithA) = $leastWithAmountAssigned[$homeAwayA->getIndex()];
                list($amountWithB, $nrOfPlacesWithB) = $leastWithAmountAssigned[$homeAwayB->getIndex()];
                if ($amountWithA !== $amountWithB) {
                    return $amountWithA - $amountWithB;
                } else if ($nrOfPlacesWithA !== $nrOfPlacesWithB) {
                    return $amountWithB - $amountWithA;
                }
            }

            list($amountHomeA, $nrOfPlacesHomeA) = $leastHomeAmountAssigned[$homeAwayA->getIndex()];
            list($amountHomeB, $nrOfPlacesHomeB) = $leastHomeAmountAssigned[$homeAwayB->getIndex()];
            if ($amountHomeA !== $amountHomeB) {
                return $amountHomeA - $amountHomeB;
            }
            return $nrOfPlacesHomeA - $nrOfPlacesHomeB;
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
        // $self = clone $this;
//        $homeAways = array_filter(
//            $homeAways,
//            function (HomeAway $homeAway) /*use($self)*/ : bool {
////                $self2 = $self->addHomeAway($homeAway);
//                $statisticsCalculator = $this->addHomeAway($homeAway);
//                if( !$statisticsCalculator->againstWithinMarginDuring() ) {
//                    return false;
//                }
//
//                if( !$statisticsCalculator->withWithinMarginDuring() ) {
//                    return false;
//                }
////                return $self2->againstWithinMarginDuring() && $self2->withWithinMarginDuring();
//                 return true;
//                // return $this->assignedAgainstMap->getMax() > $this->difference->allowedAgainstAmount;
//                // return !$this->withWillBeOverAssigned($homeAway) && !$this->againstWillBeOverAssigned($homeAway)
//                    /*&& !$this->againstWillBeTooMuchDiffAssigned($homeAway)
//                    && !$this->withWillBeTooMuchDiffAssigned($homeAway)*/;
//            }
//        );
//        return array_values($homeAways);
         return $homeAways;
    }

    public function output(bool $withDetails): void {
        $header = 'nrOfHomeAwaysAssigned: ' . $this->nrOfHomeAwaysAssigned . ', scheduleMargin : ' . $this->difference->scheduleMargin;
        $this->logger->info($header);
        $prefix = '    ';
        $this->outputAgainstTotals($prefix, $withDetails);
        $this->outputWithTotals($prefix, $withDetails);
        if( $withDetails ) {
            $this->assignedHomeMap->output( $this->logger, $prefix, 'HomeTotals');
        } else {
            $header = 'HomeTotals ( maxHomeDifference : '.$this->assignedHomeMap->getMaxDifference() .' )';
            $this->logger->info($prefix . $header);

            $map = $this->assignedHomeMap->getPerAmount();
            $mapOutput = 'map: ';
            foreach($map as $amount => $counters) {
                $mapOutput .= $amount  . '('.count($counters).'x), ';
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
        $header = 'AgainstTotals ( maxDifference : '.$this->assignedAgainstMap->getMaxDifference();
        $header .= ', shortage : ' . $this->assignedAgainstMap->shortage;
        $header .= ', allowed/nrOfMin : ' . $this->difference->allowedAgainstRange;
        $header .= '/' . $this->difference->minNrOfAgainstAllowedToAssignedToMinimumCum .' )';
        $this->logger->info($prefix . $header);

        $map = $this->assignedAgainstMap->getMap()->getPerAmount();
        $mapOutput = 'map: ';
        $shortage = 0;
        foreach($map as $amount => $counters) {
            $shortageDescription = '';
            if( $amount < $this->difference->allowedAgainstRange->getMin() ) {
                $totalShortage = count($counters) * ($this->difference->allowedAgainstRange->getMin() - $amount);
                $shortageDescription = 'x'.($this->difference->allowedAgainstRange->getMin() - $amount).'='.$totalShortage;
                $shortage += $totalShortage;
            } else if( $amount === $this->difference->allowedAgainstRange->getMin()
                && count($counters) < $this->difference->minNrOfAgainstAllowedToAssignedToMinimumCum ) {
                $totalShortage = ($this->difference->minNrOfAgainstAllowedToAssignedToMinimumCum - count($counters));
                $shortageDescription = 'x => ('.$this->difference->minNrOfAgainstAllowedToAssignedToMinimumCum.'-' . count($counters) . '=' . $totalShortage;
                $shortage += $totalShortage;
            }
            $mapOutput .= $amount  . '('.count($counters). $shortageDescription . '), ';
        }
        $this->logger->info($prefix . $mapOutput . ', totalShortage = ' . $shortage);

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
        $header = 'WithTotals ( maxWithDifference : '.$this->assignedWithMap->getMaxDifference();
        $header .= ', shortage : ' . $this->assignedWithMap->shortage;
        $header .= ', allowed/nrOfMin : ' . $this->difference->allowedWithRange;
        $header .= '/' . $this->difference->minNrOfWithAllowedToAssignedToMinimumCum .' )';
        $this->logger->info($prefix . $header);

        $map = $this->assignedWithMap->getMap()->getPerAmount();
        $mapOutput = 'map: ';
        foreach($map as $amount => $counters) {
            $mapOutput .= $amount  . '('.count($counters).'x), ';
        }
        $this->logger->info($prefix . $mapOutput);

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
