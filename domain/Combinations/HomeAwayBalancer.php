<?php

namespace SportsPlanning\Combinations;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsPlanning\Combinations\PlaceCombinationCounterMap\Ranged as RangedPlaceCombinationCounterMap;

class HomeAwayBalancer
{
    public function __construct(private LoggerInterface $logger)
    {
    }


    /**
     * @param RangedPlaceCombinationCounterMap $assignedHomeBaseMap
     * @param PlaceCombinationCounterMap $assignedAwayMap
     * @param list<HomeAway> $sportHomeAways
     * @return list<HomeAway>
     */
    public function balance2(
        RangedPlaceCombinationCounterMap $assignedHomeBaseMap,
        PlaceCombinationCounterMap $assignedAwayMap,
        array $sportHomeAways): array {

        $assignedHomeMap = $assignedHomeBaseMap->getMap();
        $sportHomeAwaysAfterAdding = $this->addHomeAwaysToExisting(
            $assignedHomeMap,
            $assignedAwayMap,
            $sportHomeAways
        );


        $rangedAssignedHomeBaseMap = new RangedPlaceCombinationCounterMap(
            $assignedHomeMap, $assignedHomeBaseMap->getAllowedRange()
        );
        if( $rangedAssignedHomeBaseMap->withinRange(0) ) {
            return $this->getSwapped($sportHomeAways, $sportHomeAwaysAfterAdding );
        }

        $sportHomeAwaysAfterMajorBalancing = $sportHomeAwaysAfterAdding;
        $homeAwaysToSwap = $this->getHomeAwaysWithAtLeastTwoDifference($assignedHomeMap, $sportHomeAwaysAfterMajorBalancing);
        while ( count($homeAwaysToSwap) > 0) {
            $this->swapHomeAways(
                $assignedHomeMap,
                $assignedAwayMap,
                $sportHomeAwaysAfterMajorBalancing,
                $homeAwaysToSwap);
            $homeAwaysToSwap = $this->getHomeAwaysWithAtLeastTwoDifference($assignedHomeMap, $sportHomeAwaysAfterMajorBalancing);
        }
        if( $rangedAssignedHomeBaseMap->withinRange(0)
            || $rangedAssignedHomeBaseMap->getAllowedRange()->getAmountDifference() > 0 ) {
            return $this->getSwapped($sportHomeAways, $sportHomeAwaysAfterAdding );
        }

        // $assignedHomeMap->output($this->logger, '', 'before minor');

        $nrOfHomeGames = $rangedAssignedHomeBaseMap->getAllowedRange()->getMax()->amount;
        $sportHomeAwaysAfterMinorBalancing = $sportHomeAwaysAfterMajorBalancing;
        $swapRoute = $this->getSwapRoute($nrOfHomeGames, $assignedHomeMap, $assignedAwayMap, $sportHomeAwaysAfterMinorBalancing);
        while ( $swapRoute !== null ) {
            $this->swapHomeAways(
                $assignedHomeMap,
                $assignedAwayMap,
                $sportHomeAwaysAfterMinorBalancing,
                $swapRoute);
            $swapRoute = $this->getSwapRoute($nrOfHomeGames, $assignedHomeMap, $assignedAwayMap, $sportHomeAwaysAfterMinorBalancing);
        }
        // $assignedHomeMap->output($this->logger, '', 'after minor');
        return $this->getSwapped($sportHomeAways, $sportHomeAwaysAfterMinorBalancing );
    }

    /**
     * @param PlaceCombinationCounterMap $assignedHomeMap
     * @param PlaceCombinationCounterMap $assignedAwayMap
     * @param list<HomeAway> $sportHomeAways
     * @return list<HomeAway>
     */
    private function addHomeAwaysToExisting(
        PlaceCombinationCounterMap &$assignedHomeMap,
        PlaceCombinationCounterMap &$assignedAwayMap,
        array $sportHomeAways): array {

        $newSportHomeAways = [];
        while( $sportHomeAway = $this->getBestSwappableHomeAway( $assignedHomeMap, $assignedAwayMap, $sportHomeAways ) ) {
            $key = array_search($sportHomeAway, $sportHomeAways, true);
            if( $key !== false ) {
                array_splice($sportHomeAways, $key, 1);
            }
            if( $this->shouldSwap($assignedHomeMap, $assignedAwayMap, $sportHomeAway) ) {
                $sportHomeAway = $sportHomeAway->swap();
            }
            $newSportHomeAways[] = $sportHomeAway;
            $assignedHomeMap = $assignedHomeMap->addPlaceCombination($sportHomeAway->getHome());
            $assignedAwayMap = $assignedAwayMap->addPlaceCombination($sportHomeAway->getAway());
        }
        return $newSportHomeAways;
    }

    /**
     * @param PlaceCombinationCounterMap $assignedHomeMap
     * @param list<HomeAway> $sportHomeAways
     * @return list<HomeAway>
     */
    private function getHomeAwaysWithAtLeastTwoDifference(
        PlaceCombinationCounterMap $assignedHomeMap, array $sportHomeAways): array {
            return array_values(array_filter( $sportHomeAways, function(HomeAway $homeAway) use ($assignedHomeMap): bool {
                return $this->getHomeDifference($assignedHomeMap, $homeAway) > 1;
            }));
    }

    /**
     * @param PlaceCombinationCounterMap $assignedHomeMap
     * @param list<HomeAway> $sportHomeAways
     * @param list<HomeAway> $homeAwaysToSwap
     * @return void
     */
    protected function swapHomeAways(
        PlaceCombinationCounterMap &$assignedHomeMap,
        PlaceCombinationCounterMap &$assignedAwayMap,
        array &$sportHomeAways, array $homeAwaysToSwap): void {
        foreach( $homeAwaysToSwap as $homeAwayToSwap) {
            $key = array_search($homeAwayToSwap, $sportHomeAways, true);
            if( $key === false ) {
                continue;
            }
            array_splice($sportHomeAways, $key, 1);
            $assignedHomeMap = $assignedHomeMap->removePlaceCombination($homeAwayToSwap->getHome());
            $assignedAwayMap = $assignedAwayMap->removePlaceCombination($homeAwayToSwap->getAway());
            $swappedHomeAway = $homeAwayToSwap->swap();
            $assignedHomeMap = $assignedHomeMap->addPlaceCombination($swappedHomeAway->getHome());
            $assignedAwayMap = $assignedAwayMap->addPlaceCombination($swappedHomeAway->getAway());
            $sportHomeAways[] = $swappedHomeAway;
        }
    }

    /**
     * @param list<HomeAway> $sportHomeAways
     * @param list<HomeAway> $newHomeAways
     * @return list<HomeAway>
     */
    protected function getSwapped(array $sportHomeAways, array $newHomeAways ): array {
        $swappedHomeAways = [];

        $nrOfSportHomeAways = count($sportHomeAways);
        foreach( $newHomeAways as $newHomeAway) {
            $count = 0;
            $sportHomeAway = array_shift($sportHomeAways);
            while( ++$count <= $nrOfSportHomeAways && $sportHomeAway !== null ) {
                if( $sportHomeAway->getIndex() === $newHomeAway->getIndex() ) {
                    break;
                }
                if( $sportHomeAway->getIndex() === $newHomeAway->swap()->getIndex() ) {
                    $swappedHomeAways[] = $newHomeAway;
                    break;
                }
                array_push($sportHomeAways, $sportHomeAway);
                $sportHomeAway = array_shift($sportHomeAways);
            }
        }
        return $swappedHomeAways;
    }

    /**
     * @param int $nrOfHomeGames
     * @param PlaceCombinationCounterMap $assignedHomeMap
     * @param PlaceCombinationCounterMap $assignedAwayMap
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>|null
     */
    protected function getSwapRoute(
        int $nrOfHomeGames,
        PlaceCombinationCounterMap $assignedHomeMap,
        PlaceCombinationCounterMap $assignedAwayMap,
        array $homeAways): array|null {

        $greater = $this->getWithNrOfHomeGames($nrOfHomeGames + 1, $assignedHomeMap);
        $greater = array_shift($greater);
        if( $greater === null ) {
            return null;
        }
        $equal = $this->getWithNrOfHomeGames($nrOfHomeGames, $assignedHomeMap);
        if( count($equal) === 0 ) {
            return null;
        }
        $smaller = $this->getWithNrOfHomeGames($nrOfHomeGames - 1, $assignedHomeMap);
        $smaller = array_shift($smaller);
        if( $smaller === null ) {
            return null;
        }

        $greaterHomeHomeAways = $this->getHomeAwaysWithSide(Side::Home, $greater, $homeAways);
        $otherHomeAways = $this->getHomeAwaysNotWithSide(Side::Home, $greater, $homeAways);
        // $maxRouteLength = 1 + count($equal) + 1;
        $routeLength = 2;
        //while ( $routeLength <= $maxRouteLength ) {
            $swapRoute = $this->getSwapRouteHelper($greaterHomeHomeAways, $otherHomeAways, $smaller, [], $routeLength);
            if( $swapRoute !== null ) {
                return $swapRoute;
            }
           // $routeLength++;
        //}
        return null;
    }

    /**
     * @param list<HomeAway> $homeAways
     * @param list<HomeAway> $otherHomeAways
     * @param PlaceCombination $target
     * @param list<HomeAway> $route
     * @param int $maxRouteLength
     * @return list<HomeAway>|null
     */
    protected function getSwapRouteHelper(
        array $homeAways,
        array $otherHomeAways,
        PlaceCombination $target,
        array $route,
        int $maxRouteLength): array|null {

        if( count($homeAways) === 0 || count($otherHomeAways) === 0) {
            return null;
        }
        if( count($route) === $maxRouteLength) {
            return null;
        }

        foreach( $homeAways as $homeAway) {
            $routeToTry = $route;
            $routeToTry[] = $homeAway;
            if( $homeAway->getAway()->getIndex() === $target->getIndex()) {
                return $routeToTry;
            }
            $newHomeHomeAways = $this->getHomeAwaysWithSide(Side::Home, $homeAway->getAway(), $otherHomeAways);
            $newOtherHomeAways = $this->getHomeAwaysNotWithSide(Side::Home, $homeAway->getAway(), $otherHomeAways);

            $finalRoute = $this->getSwapRouteHelper($newHomeHomeAways, $newOtherHomeAways, $target, $routeToTry, $maxRouteLength);
            if( $finalRoute !== null) {
                return $finalRoute;
            }
        }
        return null;
    }



    /**
     * @param int $nrOfHomeGames
     * @param PlaceCombinationCounterMap $assignedHomeMap
     * @return list<PlaceCombination>
     */
    protected function getWithNrOfHomeGames(int $nrOfHomeGames, PlaceCombinationCounterMap $assignedHomeMap): array {
        $amountMap = $assignedHomeMap->getPerAmount();
        if( !array_key_exists($nrOfHomeGames, $amountMap) ) {
            return [];
        }
        return array_map( function(PlaceCombinationCounter $counter): PlaceCombination {
           return $counter->getPlaceCombination();
        }, $amountMap[$nrOfHomeGames]);
    }

    /**
     * @param PlaceCombinationCounterMap $assignedHomeMap
     * @param PlaceCombinationCounterMap $assignedAwayMap
     * @param list<HomeAway> $sportHomeAways
     * @return HomeAway|null
     */
    protected function getBestSwappableHomeAway(
        PlaceCombinationCounterMap $assignedHomeMap,
        PlaceCombinationCounterMap $assignedAwayMap,
        array $sportHomeAways): HomeAway|null {

        if( count($sportHomeAways) === 0) {
            return null;
        }
        $bestHomeAway = null;
        $leastHome = null;
        foreach( $sportHomeAways as $homeAway) {
            if( $bestHomeAway === null || $assignedHomeMap->count($homeAway->getHome()) < $leastHome) {
                $leastHome = $assignedHomeMap->count($homeAway->getHome());
                $bestHomeAway = $homeAway;
            } else if( $leastHome === $assignedHomeMap->count($homeAway->getHome())
                && $assignedAwayMap->count($homeAway->getAway()) > $assignedAwayMap->count($bestHomeAway->getAway()) ) {
                $bestHomeAway = $homeAway;
            }
        }
        return $bestHomeAway;
    }

    private function shouldSwap(
        PlaceCombinationCounterMap $assignedHomeMap,
        PlaceCombinationCounterMap $assignedAwayMap,
        HomeAway $homeAway): bool {
        $homeCountHome = $assignedHomeMap->count($homeAway->getHome());
        $homeCountAway = $assignedHomeMap->count($homeAway->getAway());
        $awayCountHome = $assignedAwayMap->count($homeAway->getHome());
        $awayCountAway = $assignedAwayMap->count($homeAway->getAway());
        return ( $homeCountHome > $homeCountAway
            || ($homeCountHome === $homeCountAway && $awayCountHome < $awayCountAway) );
    }

    private function getHomeDifference(PlaceCombinationCounterMap $assignedHomeMap, HomeAway $sportHomeAway): int {
        $homeDiff = $assignedHomeMap->count($sportHomeAway->getHome())
            - $assignedHomeMap->count($sportHomeAway->getAway());
        return $homeDiff < 0 ? 0 : $homeDiff;
    }

    /**
     * @param Side $side
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
     */
    protected function getHomeAwaysWithSide(Side $side, PlaceCombination $placeCombination, array $homeAways): array {
        return array_values( array_filter($homeAways, function(HomeAway $homeAway) use($side, $placeCombination): bool {
            return $homeAway->get($side)->getIndex() === $placeCombination->getIndex();
        }));
    }

    /**
     * @param Side $side
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
     */
    protected function getHomeAwaysNotWithSide(Side $side, PlaceCombination $placeCombination, array $homeAways): array {
        return array_values( array_filter($homeAways, function(HomeAway $homeAway) use($side, $placeCombination): bool {
            return $homeAway->get($side)->getIndex() !== $placeCombination->getIndex();
        }));
    }

}