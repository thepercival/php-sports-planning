<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations\StatisticsCalculator\Against;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Combinations\PlaceCombinationCounterMap\Ranged as RangedPlaceCombinationCounterMap;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Combinations\PlaceCounterMap\Ranged as RangedPlaceCounterMap;
use SportsPlanning\Combinations\StatisticsCalculator;
use SportsPlanning\Combinations\StatisticsCalculator\Against\GamesPerPlace as GppStatisticsCalculator;
use SportsPlanning\Place;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;

class GamesPerPlace extends StatisticsCalculator
{
    protected bool $checkOnWith;

    public function __construct(
        protected AgainstGppWithPoule $againstGppWithPoule,
        RangedPlaceCombinationCounterMap $assignedHomeMap,
        int $nrOfHomeAwaysAssigned,
        // protected RangedPlaceCounterMap $assignedSportMap,
        protected RangedPlaceCounterMap $assignedMap,
        protected RangedPlaceCombinationCounterMap $assignedAgainstMap,
        protected RangedPlaceCombinationCounterMap $assignedWithMap,
        LoggerInterface $logger
    )
    {
        parent::__construct($assignedHomeMap,$nrOfHomeAwaysAssigned, $logger);
        $this->checkOnWith = $againstGppWithPoule->getSportVariant()->hasMultipleSidePlaces();
    }

    public function getNrOfGamesToGo(): int {
        return $this->againstGppWithPoule->getTotalNrOfGames() - $this->getNrOfHomeAwaysAssigned();
    }

    public function addHomeAway(HomeAway $homeAway): self
    {
        // $assignedSportMap = $this->assignedSportMap;
        $assignedMap = $this->assignedMap;
        foreach ($homeAway->getPlaces() as $place) {
            // $assignedSportMap = $assignedSportMap->addPlace($place);
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
            // $assignedSportMap,
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

        if( !$this->amountWithinMarginHelper() ) {
//            $this->output();
            return false;
        }

        if( !$this->againstWithinMarginHelper() ) {
//            $this->output();
            return false;
        }

        if( !$this->withWithinMarginHelper() ) {
            return false;
        }
        return true;
    }

    public function isHomeAwayAssignable(HomeAway $homeAway): bool {
        $statisticsCalculator = $this->addHomeAway($homeAway);
        if( !$statisticsCalculator->amountWithinMarginDuring() ) {
            return false;
        }

        if( !$statisticsCalculator->againstWithinMarginDuring() ) {
            return false;
        }

        if( !$statisticsCalculator->withWithinMarginDuring() ) {
            return false;
        }
        return true;
    }

    public function amountWithinMarginDuring(): bool
    {
        $minAllowedAmountDifference = $this->getMinAllowedDifference($this->assignedMap->getAllowedRange());
        return $this->amountWithinMarginHelper($minAllowedAmountDifference);
    }

    private function amountWithinMarginHelper(int|null $minimalAllowedDifference = null): bool
    {
        $assignedRange = $this->assignedMap->getRange();
        if( $assignedRange === null) {
            return true;
        }
        if( $minimalAllowedDifference !== null ) {
            if ($assignedRange->getAmountDifference() > $minimalAllowedDifference ) {
                return false;
            }
            if ($assignedRange->getAmountDifference() === $minimalAllowedDifference ) {
                $minAssigned = $assignedRange->getMin();
                $nextAssigned = $this->assignedMap->countAmount($minAssigned->amount + 1);
                if( $minAssigned->count > $nextAssigned ) {
                    return false;
                }
            }
//            if( $this->nrOfHomeAwaysAssigned > 80 && $assignedRange->getAmountDifference() > 1 ) {
//                return false;
//            }
        }

        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlacesGo = $nrOfGamesToGo * $this->againstGppWithPoule->getSportVariant()->getNrOfGamePlaces();
        if( $this->assignedMap->withinRange($nrOfPlacesGo) ) {
            return true;
        }
        return false;
    }

    public function againstWithinMarginDuring(): bool
    {
        $minAllowedAgainstDifference = $this->getMinAllowedDifference($this->assignedAgainstMap->getAllowedRange());
        return $this->againstWithinMarginHelper($minAllowedAgainstDifference);
    }

    private function againstWithinMarginHelper(int|null $minimalAllowedDifference = null): bool
    {
        $assignedRange = $this->assignedAgainstMap->getRange();
        if( $assignedRange === null) {
            return true;
        }
        if( $minimalAllowedDifference !== null ) {
            if ($assignedRange->getAmountDifference() > $minimalAllowedDifference ) {
                return false;
            }
            if ($assignedRange->getAmountDifference() === $minimalAllowedDifference ) {
                $minAssigned = $assignedRange->getMin();
                $nextAssigned = $this->assignedAgainstMap->countAmount($minAssigned->amount + 1);
                if( $minAssigned->count > $nextAssigned ) {
                    return false;
                }
            }
        }

        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithPoule->getSportVariant()->getNrOfAgainstCombinationsPerGame();
        if( $this->assignedAgainstMap->withinRange($nrOfPlaceCombinationsToGo) ) {
            return true;
        }
        return false;
    }

    public function withWithinMarginDuring(): bool
    {
        $minAllowedWithDifference = $this->getMinAllowedDifference($this->assignedWithMap->getAllowedRange());
        return $this->withWithinMarginHelper($minAllowedWithDifference);
    }



    public function withWithinMarginHelper(int|null $minimalAllowedDifference = null): bool
    {
        if( !$this->checkOnWith ) {
            return true;
        }
        $assignedRange = $this->assignedWithMap->getRange();
        if( $assignedRange === null ) {
            return true;
        }
        if( $minimalAllowedDifference !== null) {
            if ($assignedRange->getAmountDifference() > $minimalAllowedDifference ) {
                return false;
            }
            if ($assignedRange->getAmountDifference() === $minimalAllowedDifference ) {
                $minAssigned = $assignedRange->getMin();
                $nextAssigned = $this->assignedWithMap->countAmount($minAssigned->amount + 1);
                if( $minAssigned->count > $nextAssigned /* && $minAssigned->count > 10*/ ) {
                    return false;
                }
            }
        }

        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithPoule->getSportVariant()->getNrOfWithCombinationsPerGame();
        if( $this->assignedWithMap->withinRange($nrOfPlaceCombinationsToGo) ) {
            return true;
        }

        return false;
    }

    private function getMinAllowedDifference(AmountRange $allowedRange): int {
        if( $allowedRange->getAmountDifference() < 2 ) {
            return 2;
        }
        return $allowedRange->getAmountDifference();
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
            $leastAmountAssigned[$homeAway->getIndex()] = $this->getLeastAssigned($this->assignedMap->getMap(), $homeAway);
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

//    public function minimalSportCanStillBeAssigned(): bool {
//        // HIER OOK MEENEMEN DAT JE NOG EEN X AANTAL SPEELRONDEN HEBT,
//        // WAARDOOR SOMMIGE PLEKKEN OOK NIET MEER KUNNEN
//        // BETER IS NOG OM HET VERSCHIL NIET GROTER DAN 1 TE LATEN ZIJN,
//        // DAN HOEF JE AAN HET EIND OOK NIET MEER TE CONTROLEREN
//        // EERST DUS EEN RANGEDPLACECOUNTERMAP MAKEN
//        $nrOfGamesToGo = $this->againstGppWithPoule->getTotalNrOfGames() - $this->nrOfHomeAwaysAssigned;
//        $minNrOfGamesPerPlace = $this->getMinNrOfGamesPerPlace();
//
//        foreach( $this->againstGppWithPoule->getPoule()->getPlaces() as $place ) {
//            if( ($this->assignedSportMap->count($place) + $nrOfGamesToGo) < $minNrOfGamesPerPlace ) {
//                return false;
//            }
//        }
//        return true;
//    }

//    public function sportWillBeOverAssigned(Place $place): bool
//    {
//        return $this->assignedSportMap->count($place) >= $this->getMaxNrOfGamesPerPlace();
//    }

    private function getMinNrOfGamesPerPlace(): int {
        $totalNrOfGamesPerPlace = $this->againstGppWithPoule->getSportVariant()->getNrOfGamesPerPlace();
        return $totalNrOfGamesPerPlace - (!$this->againstGppWithPoule->allPlacesSameNrOfGamesAssignable() ? 1 : 0);
    }

    private function getMaxNrOfGamesPerPlace(): int {
        return $this->againstGppWithPoule->getSportVariant()->getNrOfGamesPerPlace();
    }

    /**
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
     */
    public function filterBeforeGameRound(array $homeAways): array {
        $homeAways = array_filter(
            $homeAways,
            function (HomeAway $homeAway) : bool {
                foreach ($homeAway->getPlaces() as $place) {
                    if( $this->assignedMap->count($place) + 1 > $this->assignedMap->getAllowedRange()->getMax()->amount ) {
                        return false;
                    }
                }
                foreach( $homeAway->getAgainstPlaceCombinations() as $placeCombination) {
                    if( $this->assignedAgainstMap->count($placeCombination) + 1 > $this->assignedAgainstMap->getAllowedRange()->getMax()->amount ) {
                        return false;
                    }
                }
                foreach( $homeAway->getWithPlaceCombinations() as $placeCombination) {
                    if( $this->assignedWithMap->count($placeCombination) + 1 > $this->assignedWithMap->getAllowedRange()->getMax()->amount ) {
                        return false;
                    }
                }

//                $statisticsCalculator = $this->addHomeAway($homeAway);
//                if( !$statisticsCalculator->amountWithinMargin() ) {
//                    return false;
//                }
//                if( !$statisticsCalculator->againstWithinMargin() ) {
//                    return false;
//                }
//                if( !$statisticsCalculator->withWithinMargin() ) {
//                    return false;
//                }
                 return true;
            }
        );
        return array_values($homeAways);
    }

    public function output(bool $withDetails): void {
        $header = 'nrOfHomeAwaysAssigned/max: ' . $this->nrOfHomeAwaysAssigned;
        $header .= '/' . $this->againstGppWithPoule->getTotalNrOfGames();
        $this->logger->info($header);
        $prefix = '    ';
        $this->outputAssignedTotals($prefix, $withDetails);
        $this->outputAgainstTotals($prefix, $withDetails);
        $this->outputWithTotals($prefix, $withDetails);
        $this->outputHomeTotals($prefix, $withDetails);
    }

    public function outputAssignedTotals(string $prefix, bool $withDetails): void {
        $header = 'AssignedTotals : ';
        $allowedRange = $this->assignedMap->getAllowedRange();
        $header .= ' allowedRange : ' . $allowedRange;
        $nrOfPossiblities = $this->assignedMap->getMap()->count();
        $header .= ', belowMinimum/max : ' . $this->assignedMap->getNrOfPlacesBelowMinimum();
        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithPoule->getSportVariant()->getNrOfGamePlaces();
        $header .= '/' . $nrOfPlaceCombinationsToGo;
        $header .= ', nrOfPossibilities : ' . $nrOfPossiblities;

        $this->logger->info($prefix . $header);

        $mapRange = $this->assignedMap->getRange();
        if( $mapRange !== null ) {
            $map = $this->assignedMap->getMap()->getAmountMap();
            $mapOutput = $prefix . 'map: ';
            foreach($map as $amount) {
                $mapOutput .= $amount  . ', ';
            }
            $this->logger->info($prefix . $mapOutput . ' => range / difference : '. $mapRange . '/' . $this->assignedMap->getAmountDifference());
        }
    }

    public function outputAgainstTotals(string $prefix, bool $withDetails): void {
        $header = 'AgainstTotals : ';
        $allowedRange = $this->assignedAgainstMap->getAllowedRange();
        $header .= ' allowedRange : ' . $allowedRange;
        $nrOfPossiblities = count( $this->assignedAgainstMap->getMap()->getList() );
        $header .= ', belowMinimum/max : ' . $this->assignedAgainstMap->getNrOfPlaceCombinationsBelowMinimum();
        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithPoule->getSportVariant()->getNrOfAgainstCombinationsPerGame();
        $header .= '/' . $nrOfPlaceCombinationsToGo;
        $header .= ', nrOfPossibilities : ' . $nrOfPossiblities;

        $this->logger->info($prefix . $header);

        $mapRange = $this->assignedAgainstMap->getRange();
        if( $mapRange !== null ) {
            $map = $this->assignedAgainstMap->getMap()->getAmountMap();
            $mapOutput = $prefix . 'map: ';
            foreach($map as $amount) {
                $mapOutput .= $amount  . ', ';
            }
            $this->logger->info($prefix . $mapOutput . ' => range / difference : '. $mapRange . '/' . $this->assignedAgainstMap->getAmountDifference());
        }

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
        $allowedRange = $this->assignedWithMap->getAllowedRange();
        $header .= ' allowedRange : ' . $allowedRange;
        $nrOfPossiblities = count( $this->assignedWithMap->getMap()->getList() );
        $header .= ', belowMinimum/max : ' . $this->assignedWithMap->getNrOfPlaceCombinationsBelowMinimum();
        $nrOfGamesToGo = $this->getNrOfGamesToGo();
        $nrOfPlaceCombinationsToGo = $nrOfGamesToGo * $this->againstGppWithPoule->getSportVariant()->getNrOfWithCombinationsPerGame();
        $header .= '/' . $nrOfPlaceCombinationsToGo;
        $header .= ', nrOfPossibilities : ' . $nrOfPossiblities;
        $this->logger->info($prefix . $header);

        $mapRange = $this->assignedWithMap->getRange();
        if( $mapRange !== null ) {
            $map = $this->assignedWithMap->getMap()->getAmountMap();
            $mapOutput = $prefix . 'map: ';
            foreach($map as $amount) {
                $mapOutput .= $amount  . ', ';
            }
            $this->logger->info($prefix . $mapOutput . ' => range / difference : '. $mapRange . '/' . $this->assignedWithMap->getAmountDifference());
        }

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
