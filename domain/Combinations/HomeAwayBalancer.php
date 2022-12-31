<?php

namespace SportsPlanning\Combinations;

use Psr\Log\LoggerInterface;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\Combinations\Output\HomeAway as HomeAwayOutput;

class HomeAwayBalancer
{
    // leastAmountAssgined
    // mostAmountAssgined
    // differenceAmountAssigned

    // use gameRound->reverseSidesOfHomeAway
    // private $oneHhomeDiff// needs all homeAways

    public function __construct(private LoggerInterface $logger)
    {
    }


    /**
     * @param PlaceCombinationCounterMap $assignedHomeMap
     * @param list<HomeAway> $sportHomeAways
     * @param int $maxDiff
     * @return list<HomeAway>
     */
    public function balance(PlaceCombinationCounterMap $assignedHomeMap, array $sportHomeAways, int $maxDiff): array {
        $reversedHomeAways = [];

        $sportHomeAwaysMap = [];
        foreach ( $sportHomeAways as $sportHomeAway ) {
            $assignedHomeMap = $assignedHomeMap->addPlaceCombination($sportHomeAway->getHome());
            $sportHomeAwaysMap[$sportHomeAway->getIndex()] = $sportHomeAway;
        }
        $minDifference = $assignedHomeMap->getMinDifference();
        $homeAwaysByDifference = $this->getHomeAwaysByDifference($assignedHomeMap, array_values($sportHomeAwaysMap));
        // $sportDifference = $this->getMaxDifference($homeAwaysByDifference);
        // (new HomeAwayOutput($this->logger))->outputHomeTotals($sportHomeAways);
//        $assignedHomeMap->output($this->logger, 'HomeTotals');
//        $this->outputHomeDiffsPerAmount($homeAwaysByDifference);
        //
        while( $assignedHomeMap->getMaxDifference() > $minDifference ) {
            $homeAwayMostDifference = $this->getBestHomeAway($reversedHomeAways, $homeAwaysByDifference);
            if( $homeAwayMostDifference === null) {
                break;
            }

            $reversedHomeAway = $homeAwayMostDifference->swap();

            unset($sportHomeAwaysMap[$homeAwayMostDifference->getIndex()]);
            $sportHomeAwaysMap[$reversedHomeAway->getIndex()] = $reversedHomeAway;

            $assignedHomeMap = $assignedHomeMap->removePlaceCombination($homeAwayMostDifference->getHome());
            $assignedHomeMap = $assignedHomeMap->addPlaceCombination($reversedHomeAway->getHome());

            $homeAwaysByDifference = $this->getHomeAwaysByDifference($assignedHomeMap, array_values($sportHomeAwaysMap));
            // $sportDifference = $this->getMaxDifference($homeAwaysByDifference);

            $reversedHomeAways[$reversedHomeAway->getIndex()] = $reversedHomeAway;
//            $assignedHomeMap->output($this->logger, 'HomeTotals AFTER REVERSE');
//            $this->outputHomeDiffsPerAmount($homeAwaysByDifference);
        }

        return array_values($reversedHomeAways);
    }

    /**
     * @param array<string, HomeAway> $reversedHomeAways
     * @param array<int,list<HomeAway>> $homeAwaysByDifference
     * @return HomeAway|null
     */
    protected function getBestHomeAway(array &$reversedHomeAways, array $homeAwaysByDifference): HomeAway|null {

        foreach( $homeAwaysByDifference as $homeAways) {
            foreach ($homeAways as $homeAway) {
                if (!array_key_exists($homeAway->getIndex(), $reversedHomeAways)) {
                    return $homeAway;
                }
            }
        }
        return null;
    }


    /**
     * @param PlaceCombinationCounterMap $assignedHomeMap
     * @param list<HomeAway> $sportHomeAways
     * @return array<int, list<HomeAway>>
     */
    public function getHomeAwaysByDifference(PlaceCombinationCounterMap $assignedHomeMap, array $sportHomeAways): array {
        $homeDiffsPerAmount = [];
        foreach( $sportHomeAways as $sportHomeAway) {
            $homeDiff = $this->getHomeDiff($assignedHomeMap, $sportHomeAway);
            if( !array_key_exists($homeDiff, $homeDiffsPerAmount)) {
                $homeDiffsPerAmount[$homeDiff] = [];
            }
            $homeDiffsPerAmount[$homeDiff][] = $sportHomeAway;
        }
        krsort($homeDiffsPerAmount);
        return $homeDiffsPerAmount;
    }

    /**
     * @param array<int, list<HomeAway>> $homeAwaysByDifference
     * @return int
     */
    protected function getMaxDifference(array $homeAwaysByDifference): int {
        $keys = array_keys($homeAwaysByDifference);
        $max = array_shift($keys);
        $min = array_pop($keys);
        if( $max === null ) {
            return 0;
        }
        if( $min === null ) {
            return $max;
        }
        return $max - $min;
    }


    private function getHomeDiff(PlaceCombinationCounterMap $assignedHomeMap, HomeAway $sportHomeAway): int {
        $homeDiff = $assignedHomeMap->count($sportHomeAway->getHome()) - $assignedHomeMap->count($sportHomeAway->getAway());
        return $homeDiff; //  < 0 ? 0 : $homeDiff;
    }

    /**
     * @param array<int, list<HomeAway>> $homeDiffsPerAmount

     */
    public function outputHomeDiffsPerAmount(array $homeDiffsPerAmount): void {
        $header = 'HomeAway Balancer';
        $this->logger->info($header);


        foreach( $homeDiffsPerAmount as $diff => $homeAways) {
            $this->logger->info('   HomeAways with difference ' . $diff );
            $amountPerLine = 4; $counter = 0; $line = '';
            foreach( $homeAways as $homeAway) {
                $line .= $homeAway->getIndex() . ', ';
                if( ++$counter === $amountPerLine ) {
                    $this->logger->info('    ' . $line);
                    $counter = 0;
                    $line = '';
                }
            }
            if( strlen($line) > 0 ) {
                $this->logger->info('       ' . $line);
            }
        }
    }

//    private function getStartDifference(): int {
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
//
//
//    protected function HomeAwaysalance(array $homeAways): array {
//
//    }
}