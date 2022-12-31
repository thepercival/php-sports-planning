<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Schedule\Creator as ScheduleCreator;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsPlanning\SportVariant\WithPoule\Against\H2h as AgainstH2hWithPoule;

abstract class StatisticsCalculator
{
    protected bool $useWith;

    /**
     * @param AgainstH2hWithPoule|AgainstGppWithPoule $againstWithPoule
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
        protected AgainstH2hWithPoule|AgainstGppWithPoule $againstWithPoule,
        protected array $assignedSportMap,
        protected array $assignedMap,
        protected array $assignedWithSportMap,
        protected array $assignedAgainstSportMap,
        protected readonly array $assignedAgainstPreviousSportsMap,
        protected PlaceCombinationCounterMap $assignedHomeMap,
        protected array $leastAgainstAssigned,
        protected readonly int $allowedMargin,
        protected int $nrOfHomeAwaysAssigned = 0
    )
    {
        $this->useWith = $this->againstWithPoule->getSportVariant()->hasMultipleSidePlaces();
    }

    public function useWith(): bool {
        return $this->useWith;
    }


    public function getNrOfHomeAwaysAssigned(): int {
        return $this->nrOfHomeAwaysAssigned;
    }


    abstract public function addHomeAway(HomeAway $homeAway): self;

    public function allAssigned(): bool
    {
        if ($this->nrOfHomeAwaysAssigned < $this->againstWithPoule->getTotalNrOfGames()) {
            return false;
        }
//        $nrOfIncompletePlaces = 0;
//        foreach ($this->assignedSportMap as $assignedCounter) {
//            if ($assignedCounter->count() < $this->againstWithPoule->getSportVariant()->getNrOfGamesPerPlace()) {
//                $nrOfIncompletePlaces++;
//            }
//
//            if ($nrOfIncompletePlaces >= $this->againstWithPoule->getNrOfGamePlaces()) {
//                return false;
//            }
//        }
        return true;
    }

    /**
     * @param array<int, PlaceCounter> $map
     * @return array<int, PlaceCounter>
     */
    protected function copyPlaceCounterMap(array $map): array {
        $newMap = [];
        foreach( $map as $idx => $counter ) {
            $newMap[$idx] = new PlaceCounter($counter->getPlace(), $counter->count());
        }
        return $newMap;
    }

    /**
     * @param array<string, PlaceCombinationCounter> $map
     * @return array<string, PlaceCombinationCounter>
     */
    protected function copyPlaceCombinationCounterMap(array $map): array {
        $newMap = [];
        foreach( $map as $idx => $counter ) {
            $newMap[$idx] = new PlaceCombinationCounter($counter->getPlaceCombination(), $counter->count());
        }
        return $newMap;
    }

    /**
     * @param array<string, PlaceCombinationCounter> $map
     * @return array<string, PlaceCombination>
     */
    protected function convertToPlaceCombinationMap(array $map): array {
        $newMap = [];
        foreach( $map as $idx => $counter ) {
            $newMap[$idx] = $counter->getPlaceCombination();
        }
        return $newMap;
    }

    protected function minimalWithIsAssigned(): bool {
        $sportVariant = $this->againstWithPoule->getSportVariant();
        if( !($sportVariant instanceof AgainstGpp)) {
            return true;
        }

        $minWithAmount = $this->getMinWithAmount($this->againstWithPoule->getNrOfPlaces(), $sportVariant) - $this->allowedMargin;

        foreach( $this->assignedWithSportMap as $placeCombinationCounter ) {
            if( $placeCombinationCounter->count() < $minWithAmount ) {
                return false;
            }
        }
        return true;
    }

//    private function getHomeAwayAssigned(AgainstHomeAway $ha): int {
//        $assigned = 0;
//        foreach( $ha->getPlaces() as $place) {
//            if( !array_key_exists($place->getNumber(), $this->assignedSportMap)) {
//               continue;
//            }
//            $assigned += $this->assignedSportMap[$place->getNumber()]->count();
//            if( !array_key_exists($place->getNumber(), $this->assignedMap)) {
//                continue;
//            }
//            $assigned += $this->assignedMap[$place->getNumber()]->count();
//        }
//        return $assigned;
//    }



    protected function getMaxWithAmount(int $nrOfPlaces, AgainstGpp $sportVariant): int {
        $maxNrOfSidePlaces = max( $sportVariant->getNrOfHomePlaces(), $sportVariant->getNrOfAwayPlaces() );
        return (int)ceil($sportVariant->getNrOfGamesPerPlace() / ( ($nrOfPlaces - 1) * ($maxNrOfSidePlaces - 1) ));
    }

    protected function getMinWithAmount(int $nrOfPlaces, AgainstGpp $sportVariant): int {
        $minNrOfSidePlaces = min( $sportVariant->getNrOfHomePlaces(), $sportVariant->getNrOfAwayPlaces() );
        if( $minNrOfSidePlaces === 1) {
            return 0;
        }
        return (int)floor($sportVariant->getNrOfGamesPerPlace() / ( ($nrOfPlaces - 1) * ($minNrOfSidePlaces - 1) ));
    }

//    protected function getMaxAmountOverMaxAgainstAmountForAllPlaces(): int {
//        $maxAgainst = $this->againstWithPoule->getMaxNrOfAgainstPlacesForPlace() + $this->allowedMargin;
//        $rest = $maxAgainst % ($this->againstWithPoule->getNrOfPlaces() - 1);
//        return ($rest * $this->againstWithPoule->getNrOfPlaces() );
//    }

    public function output(LoggerInterface $logger, bool $againstTotals, bool $withTotals, bool $homehTotals): void {
        $header = 'nrOfHomeAwaysAssigned: ' . $this->nrOfHomeAwaysAssigned;
        $nrOfPlaces = $this->againstWithPoule->getNrOfPlaces();
        $againstSportVariant = $this->againstWithPoule->getSportVariant();
        if( $againstSportVariant instanceof AgainstGpp && $this->againstWithPoule instanceof AgainstGppWithPoule) {
            $minAgainst = $this->againstWithPoule->getMinAgainstAmountPerPlace();
            $maxAgainst = $this->againstWithPoule->getMaxAgainstAmountPerPlace();
            $header .= ', againstRange ' . $minAgainst . ' - ' . $maxAgainst;
            if( $this->useWith ) {
                $minWith = $this->getMinWithAmount($nrOfPlaces, $againstSportVariant);
                $maxWith = $this->getMaxWithAmount($nrOfPlaces, $againstSportVariant);
                $header .= ', withRange: ' . $minWith . ' - ' . $maxWith;
            }
        }
        if( $this->allowedMargin > 0 ) {
            $header .= ', allowedMargin ' . $this->allowedMargin;
        }
        $logger->info($header);
        if( $againstTotals ) {
            $this->outputAgainstTotals($logger);
        }
        if( $withTotals ) {
            $this->outputWithTotals($logger);
        }
        if( $homehTotals ) {
            $this->assignedHomeMap->output($logger, 'HomeTotals');
        }
    }

    protected function outputAgainstTotals(LoggerInterface $logger): void {
        foreach( $this->againstWithPoule->getPoule()->getPlaces() as $place ) {
            $this->outputAgainstPlaceTotals($logger, $place);
        }
    }

    private function outputAgainstPlaceTotals(LoggerInterface $logger, Place $place): void {
        $placeNr = $place->getNumber() < 10 ? '0' . $place->getNumber() : $place->getNumber();
        $out = '    ' . $placeNr . " => ";
        foreach( $this->againstWithPoule->getPoule()->getPlaces() as $opponent ) {
            $opponentNr = $opponent->getNumber() < 10 ? '0' . $opponent->getNumber() : $opponent->getNumber();
            $placeCombination = new PlaceCombination([$place, $opponent]);
            $out .= '' . $opponentNr . ':' . $this->getAmount($placeCombination) . ',';
        }
        $logger->info($out);
    }

    private function getAmount(PlaceCombination $placeCombination): string {
        if( !array_key_exists($placeCombination->getIndex(), $this->assignedAgainstSportMap)) {
            return '0x';
        }
        return $this->assignedAgainstSportMap[$placeCombination->getIndex()]->count() . 'x';

    }

    public function outputWithTotals(LoggerInterface $logger): void {
        $amountPerLine = 4; $counter = 0; $line = '';
        foreach( $this->assignedWithSportMap as $counterIt ) {
            $line .= $counterIt->getPlaceCombination() . ' ' . $counterIt->count() . 'x, ';
            if( ++$counter === $amountPerLine ) {
                $logger->info('    ' . $line);
                $counter = 0;
                $line = '';
            }
        }
        if( strlen($line) > 0 ) {
            $logger->info('    ' . $line);
        }
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
            $leastAmountAssigned[$homeAway->getIndex()] = $this->getLeastAmountAssigned($homeAway);
            $leastHomeAmountAssigned[$homeAway->getIndex()] = $this->getLeastHomeAmountAssigned($homeAway);
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
            if( $this->allowedMargin >= ScheduleCreator::MAX_ALLOWED_GPP_MARGIN) {
                return 0;
            }
            $sportAmountAgainstA = $this->getAgainstAmountAssigned($homeAwayA);
            $sportAmountAgainstB = $this->getAgainstAmountAssigned($homeAwayB);
            if ($sportAmountAgainstA !== $sportAmountAgainstB) {
                return $sportAmountAgainstA - $sportAmountAgainstB;
            }
            if( $this->useWith ) {
                $amountWithA = $this->getWithAmountAssigned($homeAwayA);
                $amountWithB = $this->getWithAmountAssigned($homeAwayB);
                if ($amountWithA !== $amountWithB) {
                    return $amountWithA - $amountWithB;
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

    // ALL BENEATH SHOULD PERFORM BETTER
    private function getAgainstAmountAssigned(HomeAway $homeAway): int {
        $amount = 0;
        foreach($homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination ) {
            $combinationNumber = $againstPlaceCombination->getIndex();
            if( array_key_exists($combinationNumber, $this->assignedAgainstSportMap ) ) {
                $amount += $this->assignedAgainstSportMap[$combinationNumber]->count();
            }
            if( array_key_exists($combinationNumber, $this->assignedAgainstPreviousSportsMap ) ) {
                $amount += $this->assignedAgainstPreviousSportsMap[$combinationNumber]->count();
            }
        }
        return $amount;
    }

    /**
     * @param HomeAway $homeAway
     * @return list<int>
     */
    public function getLeastAmountAssigned(HomeAway $homeAway): array
    {
        $leastAmount = -1;
        $nrOfPlaces = 0;
        foreach ($homeAway->getPlaces() as $place) {
            $amountAssigned = $this->assignedMap[$place->getNumber()]->count();
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
     * @param HomeAway $homeAway
     * @return list<int>
     */
    public function getLeastHomeAmountAssigned(HomeAway $homeAway): array
    {
        $leastAmount = -1;
        $nrOfSides = 0;
        foreach ([Side::Home,Side::Away] as $side ) {
            $sidePlaceCombination = $homeAway->get($side);
            $amountAssigned = $this->assignedHomeMap->count($sidePlaceCombination);
            if ($leastAmount === -1 || $amountAssigned < $leastAmount) {
                $leastAmount = $amountAssigned;
                $nrOfSides = 0;
            }
            if ($amountAssigned === $leastAmount) {
                $nrOfSides++;
            }
        }
        return [$leastAmount, $nrOfSides];
    }

    protected function getWithAmountAssigned(HomeAway $homeAway): int
    {
        $awayWithAmountAssigned = $this->assignedWithSportMap[$homeAway->getAway()->getIndex()]->count();
        return $this->getHomeWithAmountAssigned($homeAway) + $awayWithAmountAssigned;
    }

    protected function getHomeWithAmountAssigned(HomeAway $homeAway): int
    {
        if( $this->againstWithPoule->getSportVariant()->getNrOfHomePlaces() > 1 ) {
            return $this->assignedWithSportMap[$homeAway->getHome()->getIndex()]->count();
        }
        return 0;
    }

//    /**
//     * @param AgainstHomeAway $homeAway
//     * @param array<int, AgainstCounter> $assignedAgainstMap
//     * @return int
//     */
//    protected function getAgainstAmountAssigned(AgainstHomeAway $homeAway, array $assignedAgainstMap): int
//    {
//        $home = $homeAway->getHome();
//        $away = $homeAway->getAway();
//        $homeAgainstAmountAssigned = $assignedAgainstMap[$home->getNumber()]->count($away);
//        $awayAgainstAmountAssigned = $assignedAgainstMap[$away->getNumber()]->count($home);
//        return $homeAgainstAmountAssigned + $awayAgainstAmountAssigned;
//    }

}
