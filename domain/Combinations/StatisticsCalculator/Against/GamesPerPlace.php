<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations\StatisticsCalculator\Against;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
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
        protected PlaceCombinationCounterMap $assignedWithMap,
        protected PlaceCombinationCounterMap $assignedAgainstMap,
        protected readonly bool $assignAgainstGppSportsEqually,
        protected readonly AgainstGppDifference $difference,
        protected LoggerInterface $logger
    )
    {
        parent::__construct(
            $assignedHomeMap,
            $nrOfHomeAwaysAssigned
        );
//        $this->initAgainstSportShortage();
//        $this->initWithSportShortage();
    }



//    private function initAgainstSportShortage(): void {
//
//        $minAgainstAmountPerPlace = $this->againstGppWithPoule->getMinAgainstAmountPerPlace();
//        $maxAgainstAmountPerPlace = $this->againstGppWithPoule->getMaxAgainstAmountPerPlace();
//        foreach( $this->assignedAgainstSportMap as $placeCombinationCounter ) {
//            $nrOfAgainst = $placeCombinationCounter->count();
//
//            if( $nrOfAgainst < ($minAgainstAmountPerPlace - $this->allowedMargin) ) {
//                $this->againstShortage += $minAgainstAmountPerPlace - $nrOfAgainst;
//            }
//            if( $nrOfAgainst > ($maxAgainstAmountPerPlace + $this->allowedMargin) ) {
//                $this->amountOverAgainstAmountForAllPlaces += $nrOfAgainst - $maxAgainstAmountPerPlace;
//            }
//            if( $nrOfAgainst <= $this->leastNrOfAgainstAssigned ) {
//                $this->leastNrOfAgainstAssigned = $nrOfAgainst;
////                $this->amountLeastAgainstAssigned++;
//            }
//        }
//        if( !$this->againstGppWithPoule->allPlacesSameNrOfGamesAssignable() ) {
//            $this->againstShortage -= $this->againstWithPoule->getSportVariant()->getNrOfGamePlaces() - 1;
//        }
//    }
//
//    private function initWithSportShortage(): void {
//        $nrOfPlaces = $this->againstWithPoule->getNrOfPlaces();
//        $sportVariant = $this->againstGppWithPoule->getSportVariant();
//        $minWithAmount = $this->getMinWithAmount($nrOfPlaces, $sportVariant) - $this->allowedMargin;
//        foreach( $this->assignedWithSportMap as $placeCombinationCounter ) {
//            $nrOfWith = $placeCombinationCounter->count();
//            if( $nrOfWith < $minWithAmount ) {
//                $this->withShortage += $minWithAmount - $nrOfWith;
//            }
//            if( $nrOfWith <= $this->leastNrOfWithAssigned ) {
//                $this->leastNrOfWithAssigned = $nrOfWith;
//                $this->amountLeastWithAssigned++;
//            }
//        }
//        if( !$this->againstGppWithPoule->allPlacesSameNrOfGamesAssignable() ) {
//            $this->withShortage -= $sportVariant->getNrOfWithCombinationsPerGame() - 1;
//        }
//    }

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
            $assignedWithMap,
            $assignedAgainstMap,
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
            return false;
        }

        if( !$this->withWithinMargin() ) {
            return false;
        }
        return true;
    }

    public function againstWithinMargin(): bool
    {
        if( $this->assignedAgainstMap->getMaxDifference() <= $this->difference->allowedCumMargin ) {
            return true;
        }
        if( !$this->difference->lastSport && $this->assignedAgainstMap->getMax() > $this->difference->allowedAgainstAmount ) {
//            $this->output();
            return false;
        }

        if ( $this->assignedAgainstMap->getMaxDifference() === $this->difference->allowedCumMargin + 1 ) {
            return $this->assignedAgainstMap->getNrOfAssignedToMin() >= $this->difference->minNrOfAgainstAllowedToAssignedToMinimumCum;
            // && $this->assignedAgainstMap->getNrOfAssignedToMax() <= $this->difference->maxNrOfAgainstAllowedToAssignedToMaximumCum;
        }
        return false;
    }

    public function againstWithinMarginDuring(): bool
    {
        $allowedMargin = $this->difference->allowedCumMargin;
        if( $allowedMargin < 2 ) {
            $allowedMargin = 2;
        }
        if( $this->assignedAgainstMap->getMaxDifference() <= $allowedMargin ) {
            return true;
        }
        if( $this->assignedAgainstMap->getMax() > $this->difference->allowedAgainstAmount ) {
            return false;
        }
        return true;
    }


    public function withWithinMargin(): bool
    {
        if( $this->assignedWithMap->getMaxDifference() <= $this->difference->allowedCumMargin ) {
            return true;
        }
        if( !$this->difference->lastSport && $this->assignedWithMap->getMax() > $this->difference->allowedWithAmount ) {
//            $this->output();
            return false;
        }

        if ( $this->assignedWithMap->getMaxDifference() === $this->difference->allowedCumMargin + 1 ) {
            return $this->assignedWithMap->getNrOfAssignedToMin() >= $this->difference->minNrOfWithAllowedToAssignedToMinimumCum;
        }
        return false;
    }

    public function withWithinMarginDuring(): bool
    {
        $allowedMargin = $this->difference->allowedCumMargin;
        if( $allowedMargin < 1 ) {
            $allowedMargin = 1;
        }
        if( $this->assignedWithMap->getMaxDifference() <= $allowedMargin ) {
            return true;
        }
        return true;
    }

    /**
     * @param list<HomeAway> $homeAways
     * @param LoggerInterface $logger
     * @return list<HomeAway>
     */
    public function sortHomeAways(array $homeAways, LoggerInterface $logger): array {
//        $time_start = microtime(true);

        $leastAmountAssigned = [];
        $leastHomeAmountAssigned = [];
        foreach($homeAways as $homeAway ) {
            $leastAmountAssigned[$homeAway->getIndex()] = $this->getLeastAssigned($this->assignedMap, $homeAway);
            $leastHomeAmountAssigned[$homeAway->getIndex()] = $this->getLeastHomeAssigned($this->assignedHomeMap, $homeAway);
        }
        uasort($homeAways, function (
            HomeAway $homeAwayA,
            HomeAway $homeAwayB
        ) use($leastAmountAssigned, $leastHomeAmountAssigned): int {

            list($amountA, $nrOfPlacesA) = $leastAmountAssigned[$homeAwayA->getIndex()];
            list($amountB, $nrOfPlacesB) = $leastAmountAssigned[$homeAwayB->getIndex()];
            if ($amountA !== $amountB) {
                return $amountA - $amountB;
            }
            if ($nrOfPlacesA !== $nrOfPlacesB) {
                return $nrOfPlacesB - $nrOfPlacesA;
            }

            if( $this->difference->scheduleMargin < ScheduleCreator::MAX_ALLOWED_GPP_MARGIN) {
                $sportAmountAgainstA = $this->getAgainstAmountAssigned($homeAwayA);
                $sportAmountAgainstB = $this->getAgainstAmountAssigned($homeAwayB);
                if ($sportAmountAgainstA !== $sportAmountAgainstB) {
                    return $sportAmountAgainstA - $sportAmountAgainstB;
                }
            }

            if( $this->difference->scheduleMargin < ScheduleCreator::MAX_ALLOWED_GPP_MARGIN) {
                $sportAmountWithA = $this->getWithAmountAssigned($homeAwayA);
                $sportAmountWithB = $this->getWithAmountAssigned($homeAwayB);
                if ($sportAmountWithA !== $sportAmountWithB) {
                    return $sportAmountWithA - $sportAmountWithB;
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
        //        $logger->info("sorting homeaways .. " . (microtime(true) - $time_start));
//        $logger->info('after sorting ');
//        (new HomeAway($logger))->outputHomeAways(array_values($homeAways));
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

    public function output(): void {
        $header = 'nrOfHomeAwaysAssigned: ' . $this->nrOfHomeAwaysAssigned . ', scheduleMargin : ' . $this->difference->scheduleMargin;
        $this->logger->info($header);
        $prefix = '    ';
        $this->outputAgainstTotals($prefix);
        $this->outputWithTotals($prefix);
        $this->assignedHomeMap->output( $this->logger, $prefix, 'HomeTotals');
    }

    public function outputAgainstTotals(string $prefix): void {
        $header = 'AgainstTotals ( maxAgainstDifference : '.$this->assignedAgainstMap->getMaxDifference() .' )';
        $this->logger->info($prefix . $header);

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

    public function outputWithTotals(string $prefix): void
    {
        $header = 'WithTotals (  maxWithDifference : '.$this->assignedWithMap->getMaxDifference() .' )';
        $this->logger->info($prefix . $header);
        $prefix =  '    ' . $prefix;
        $amountPerLine = 4; $counter = 0; $line = '';
        foreach( $this->assignedWithMap->getList() as $counterIt ) {
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
