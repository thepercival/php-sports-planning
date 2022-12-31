<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations\StatisticsCalculator\Against;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\Combinations\PlaceCombinationCounterMap;
use SportsPlanning\Combinations\StatisticsCalculator;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;

class GamesPerPlace extends StatisticsCalculator
{
    protected int $withShortage = 0;
    protected int $leastNrOfWithAssigned = 0;
    protected int $amountLeastWithAssigned = 0;

    protected int $againstShortage = 0;
    protected int $amountOverAgainstAmountForAllPlaces = 0;
    protected int $leastNrOfAgainstAssigned = 0;
//    protected int $amountLeastAgainstAssigned = 0;



    /**
     * @param AgainstGppWithPoule $againstGppWithPoule
     * @param array<int, PlaceCounter> $assignedSportMap
     * @param array<int, PlaceCounter> $assignedMap
     * @param array<string, PlaceCombinationCounter> $assignedWithSportMap
     * @param array<string, PlaceCombinationCounter> $assignedAgainstSportMap
     * @param array<string, PlaceCombinationCounter> $assignedAgainstPreviousSportsMap
     * @param PlaceCombinationCounterMap $assignedHomeMap
     * @param array<string, PlaceCombination> $leastAgainstAssigned
     * @param int $allowedMargin
     * @param int $nrOfHomeAwaysAssigned
     */
    public function __construct(
        protected AgainstGppWithPoule $againstGppWithPoule,
        array $assignedSportMap,
        array $assignedMap,
        array $assignedWithSportMap,
        array $assignedAgainstSportMap,
        array $assignedAgainstPreviousSportsMap,
        PlaceCombinationCounterMap $assignedHomeMap,
        array $leastAgainstAssigned,
        int $allowedMargin,
        int $nrOfHomeAwaysAssigned = 0
    )
    {
        parent::__construct(
            $againstGppWithPoule,
            $assignedSportMap,
            $assignedMap,
            $assignedWithSportMap,
            $assignedAgainstSportMap,
            $assignedAgainstPreviousSportsMap,
            $assignedHomeMap,
            $leastAgainstAssigned,
            $allowedMargin,
            $nrOfHomeAwaysAssigned
        );
        $this->initAgainstSportShortage();
        $this->initWithSportShortage();
    }

    private function initAgainstSportShortage(): void {

        $minAgainstAmountPerPlace = $this->againstGppWithPoule->getMinAgainstAmountPerPlace();
        $maxAgainstAmountPerPlace = $this->againstGppWithPoule->getMaxAgainstAmountPerPlace();
        foreach( $this->assignedAgainstSportMap as $placeCombinationCounter ) {
            $nrOfAgainst = $placeCombinationCounter->count();

            if( $nrOfAgainst < ($minAgainstAmountPerPlace - $this->allowedMargin) ) {
                $this->againstShortage += $minAgainstAmountPerPlace - $nrOfAgainst;
            }
            if( $nrOfAgainst > ($maxAgainstAmountPerPlace + $this->allowedMargin) ) {
                $this->amountOverAgainstAmountForAllPlaces += $nrOfAgainst - $maxAgainstAmountPerPlace;
            }
            if( $nrOfAgainst <= $this->leastNrOfAgainstAssigned ) {
                $this->leastNrOfAgainstAssigned = $nrOfAgainst;
//                $this->amountLeastAgainstAssigned++;
            }
        }
        if( !$this->againstGppWithPoule->allPlacesSameNrOfGamesAssignable() ) {
            $this->againstShortage -= $this->againstWithPoule->getSportVariant()->getNrOfGamePlaces() - 1;
        }
    }

    private function initWithSportShortage(): void {
        $nrOfPlaces = $this->againstWithPoule->getNrOfPlaces();
        $sportVariant = $this->againstGppWithPoule->getSportVariant();
        $minWithAmount = $this->getMinWithAmount($nrOfPlaces, $sportVariant) - $this->allowedMargin;
        foreach( $this->assignedWithSportMap as $placeCombinationCounter ) {
            $nrOfWith = $placeCombinationCounter->count();
            if( $nrOfWith < $minWithAmount ) {
                $this->withShortage += $minWithAmount - $nrOfWith;
            }
            if( $nrOfWith <= $this->leastNrOfWithAssigned ) {
                $this->leastNrOfWithAssigned = $nrOfWith;
                $this->amountLeastWithAssigned++;
            }
        }
        if( !$this->againstGppWithPoule->allPlacesSameNrOfGamesAssignable() ) {
            $this->withShortage -= $sportVariant->getNrOfWithCombinationsPerGame() - 1;
        }
    }

    public function addHomeAway(HomeAway $homeAway): self
    {
        $assignedSportMap = $this->copyPlaceCounterMap($this->assignedSportMap);
        $assignedMap = $this->copyPlaceCounterMap($this->assignedMap);
        foreach ($homeAway->getPlaces() as $place) {
            $assignedSportMap[$place->getNumber()]->increment();
            $assignedMap[$place->getNumber()]->increment();
        }

        $assignedAgainstSportMap = $this->copyPlaceCombinationCounterMap($this->assignedAgainstSportMap);
        foreach ($homeAway->getAgainstPlaceCombinations() as $placeCombination) {
            $assignedAgainstSportMap[$placeCombination->getIndex()]->increment();
        }
        $assignedWithMap = $this->copyPlaceCombinationCounterMap($this->assignedWithSportMap);
        if ($this->useWith) {
            if (count($homeAway->getHome()->getPlaces()) > 1) {
                $assignedWithMap[$homeAway->getHome()->getIndex()]->increment();
            }
            $assignedWithMap[$homeAway->getAway()->getIndex()]->increment();
        }

        $assignedHomeMap = $this->assignedHomeMap->addPlaceCombination($homeAway->getHome());

        $leastAgainstAssigned = $this->leastAgainstAssigned;
//        $unsetForNewLeastAssigned = [];
//        foreach($homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination ) {
//            if (array_key_exists($againstPlaceCombination->getNumber(), $leastAgainstAssigned)) {
//                unset($leastAgainstAssigned[$againstPlaceCombination->getNumber()]);
//            } else {
//                $unsetForNewLeastAssigned[] = $againstPlaceCombination;
//            }
//        }
//        if( count($leastAgainstAssigned) === 0) {
//            $leastAgainstAssigned = $this->convertToPlaceCombinationMap($this->assignedAgainstSportMap);
//            foreach($unsetForNewLeastAssigned as $againstPlaceCombination ) {
//                unset($leastAgainstAssigned[$againstPlaceCombination->getNumber()]);
//            }
//        }

        return new self(
            $this->againstGppWithPoule,
            $assignedSportMap,
            $assignedMap,
            $assignedWithMap,
            $assignedAgainstSportMap,
            $this->assignedAgainstPreviousSportsMap,
            $assignedHomeMap,
            $leastAgainstAssigned,
            $this->allowedMargin,
            $this->nrOfHomeAwaysAssigned + 1
        );
    }

    public function allAssigned(): bool
    {
        if( !parent::allAssigned() ) {
            return false;
        }

        if( !$this->minimalSportCanStillBeAssigned() ) {
            return false;
        }

        if( !$this->minimalAgainstCanStillBeAssigned(null) ) {
            return false;
        }
        if( !$this->useWith() ) {
            return true;
        }
        return $this->minimalWithIsAssigned();
    }

    public function minimalWithCanStillBeAssigned(HomeAway|null $homeAway): bool {
        $nrOfWithCombinationsPerGame = $this->againstGppWithPoule->getSportVariant()->getNrOfWithCombinationsPerGame();
        $nrOfGamesToGo = $this->againstGppWithPoule->getTotalNrOfGames() - $this->nrOfHomeAwaysAssigned;
        $nrOfWithCombinationsTogo = $nrOfGamesToGo * $nrOfWithCombinationsPerGame;
        if( $this->withShortage <= $nrOfWithCombinationsTogo ) {
            return true;
        }
        if( $homeAway === null) {
            return false;
        }
        $withShortageIncludingHomeAway = $this->getWithShortageIncludingHomeAway($homeAway);
        return $withShortageIncludingHomeAway <= $nrOfWithCombinationsTogo;
    }

    public function minimalSportCanStillBeAssigned(): bool {
        $nrOfGamesToGo = $this->againstWithPoule->getTotalNrOfGames() - $this->nrOfHomeAwaysAssigned;
        $minNrOfGamesPerPlace = $this->getMinNrOfGamesPerPlace();

        foreach( $this->againstWithPoule->getPoule()->getPlaces() as $place ) {
            if( ($this->assignedSportMap[$place->getNumber()]->count() + $nrOfGamesToGo) < $minNrOfGamesPerPlace ) {
                return false;
            }
        }
        return true;
    }

    public function sportWillBeOverAssigned(Place $place): bool
    {
        return $this->assignedSportMap[$place->getNumber()]->count() >= $this->getMaxNrOfGamesPerPlace();
    }

    private function getMinNrOfGamesPerPlace(): int {
        $totalNrOfGamesPerPlace = $this->againstGppWithPoule->getSportVariant()->getNrOfGamesPerPlace();
        return $totalNrOfGamesPerPlace - (!$this->againstGppWithPoule->allPlacesSameNrOfGamesAssignable() ? 1 : 0);
    }

    private function getMaxNrOfGamesPerPlace(): int {
        return $this->againstGppWithPoule->getSportVariant()->getNrOfGamesPerPlace();
    }

    protected function getWithShortageIncludingHomeAway(HomeAway $homeAway): int {
        $sportVariant = $this->againstWithPoule->getSportVariant();
        if( !($sportVariant instanceof AgainstGpp)) {
            return 0;
        }
        $minWithAmount = $this->getMinWithAmount($this->againstWithPoule->getNrOfPlaces(), $sportVariant) - $this->allowedMargin;
        $withShortage = $this->withShortage;

        $homeAmount = 0;
        if( array_key_exists($homeAway->getHome()->getIndex(), $this->assignedWithSportMap)) {
            $homeAmount = $this->assignedWithSportMap[$homeAway->getHome()->getIndex()]->count();
        }
        if( $homeAmount < $minWithAmount ) {
            $withShortage--;
        }

        $awayAmount = 0;
        if( array_key_exists($homeAway->getAway()->getIndex(), $this->assignedWithSportMap)) {
            $awayAmount = $this->assignedWithSportMap[$homeAway->getAway()->getIndex()]->count();
        }
        if( $awayAmount < $minWithAmount ) {
            $withShortage--;
        }
        return $withShortage;
    }

    public function minimalAgainstCanStillBeAssigned(HomeAway|null $homeAway): bool {

        $nrOfCombinationsPerGame = $this->againstGppWithPoule->getSportVariant()->getNrOfAgainstCombinationsPerGame();
        $nrOfGamesToGo = $this->againstGppWithPoule->getTotalNrOfGames() - $this->nrOfHomeAwaysAssigned;
        $nrOfAgainstCombinationsTogo = $nrOfGamesToGo * $nrOfCombinationsPerGame;

        if( ($this->againstShortage) <= $nrOfAgainstCombinationsTogo ) {
            return true;
        }
        if( $homeAway === null ) {
            return false;
        }
        $againstShortageIncludingHomeAway = $this->getAgainstShortageIncludingHomeAway($homeAway);
        return $againstShortageIncludingHomeAway <= $nrOfAgainstCombinationsTogo;
    }

    protected function getAgainstShortageIncludingHomeAway(HomeAway $homeAway): int {
        $minAgainstAmountPerPlace = $this->againstGppWithPoule->getMinAgainstAmountPerPlace();
        $againstShortage = $this->againstShortage;

        foreach( $homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination ) {
            if( !array_key_exists($againstPlaceCombination->getIndex(), $this->assignedAgainstSportMap ) ) {
                $againstShortage -= ($minAgainstAmountPerPlace - $this->allowedMargin);
                continue;
            }
            $amount = $this->assignedAgainstSportMap[$againstPlaceCombination->getIndex()]->count();
            if( $amount < ($minAgainstAmountPerPlace - $this->allowedMargin) ) {
                $againstShortage--;
            }
        }
        return $againstShortage;
    }

    /**
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
     */
    public function filterBeforeGameRound(array $homeAways): array {
        $homeAways = array_filter(
            $homeAways,
            function (HomeAway $homeAway): bool {
                return !$this->withWillBeOverAssigned($homeAway) && !$this->againstWillBeOverAssigned($homeAway)
                    /*&& !$this->againstWillBeTooMuchDiffAssigned($homeAway)
                    && !$this->withWillBeTooMuchDiffAssigned($homeAway)*/;
            }
        );
        return array_values($homeAways);
    }

    public function withWillBeOverAssigned(HomeAway $homeAway): bool
    {
        if( !$this->useWith) {
            return false;
        }
        $againstGppSportVariant = $this->againstWithPoule->getSportVariant();
        if( !($againstGppSportVariant instanceof AgainstGpp)) {
            return false;
        }

        $homeWithAmount = $this->getHomeWithAmountAssigned($homeAway);
        $awayWithAmount = $this->assignedWithSportMap[$homeAway->getAway()->getIndex()]->count();

        $nrOfPlaces = $this->againstWithPoule->getNrOfPlaces();

        $maxWithAmount = $this->getMaxWithAmount($nrOfPlaces, $againstGppSportVariant) + $this->allowedMargin;
        return ($homeWithAmount + 1) > $maxWithAmount || ($awayWithAmount + 1) > $maxWithAmount;
    }

    public function againstWillBeOverAssigned(HomeAway $homeAway): bool
    {
        $maxAgainstAmountPerPlace = $this->againstGppWithPoule->getMaxAgainstAmountPerPlace() + $this->allowedMargin;
        //$amountOverAgainstPerPlace = 0;
        foreach( $homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination ) {
            if( !array_key_exists($againstPlaceCombination->getIndex(), $this->assignedAgainstSportMap) ) {
                continue;
            }
            $newAmount = $this->assignedAgainstSportMap[$againstPlaceCombination->getIndex()]->count() + 1;

            if( $newAmount <= $maxAgainstAmountPerPlace ) {
                continue;
            }
//            if( $newAmount === ($maxAgainstAmountPerPlace + 1 ) ) {
//                $amountOverAgainstPerPlace++;
//                continue;
//            }
            return true;
        }
        return false; // ($this->amountOverAgainstAmountForAllPlaces + $amountOverAgainstPerPlace) > $this->getMaxAmountOverMaxAgainstAmountForAllPlaces();
    }

    public function outputAgainstTotals(LoggerInterface $logger): void {
        $header = 'AgainstTotals ( ' . $this->againstShortage . ' againstShortage, amount overAssigned is ' . $this->amountOverAgainstAmountForAllPlaces . ')';
        $logger->info($header);
        parent::outputAgainstTotals($logger);
    }

    public function outputWithTotals(LoggerInterface $logger): void
    {
        $header = 'WithTotals ( ' . $this->withShortage . ' withShortage )';
        $logger->info($header);
        parent::outputWithTotals($logger);
    }
}
