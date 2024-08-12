<?php

namespace SportsPlanning\Counters\Maps;

use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\CounterForDuoPlaceNr;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;

class DuoPlaceNrCounterMapCreator
{

//    /**
//     * @param Poule $poule
//     * @param AgainstGpp $sportVariants
//     * @return array<string, CounterForDuoPlace>
//     */
//    public function initTogetherCounterMap(Poule $poule, array $sportVariants): array
//    {
//        $map = [];
//        $this->addToDuoPlaceMap($map, $poule, $nrOfSidePlaces);
//        return $map;
//    }
//
    /**
     * @param int $nrOfPlaces
     * @return array<string, CounterForDuoPlaceNr>
     */
    public function initDuoPlaceNrCounterMap(int $nrOfPlaces): array
    {
        $map = [];
        for ( $placeNrOne = 1 ; $placeNrOne <= $nrOfPlaces ; $placeNrOne++ ) {
            for ( $placeNrTwo = 1 ; $placeNrTwo <= $nrOfPlaces ; $placeNrTwo++ ) {
                if ($placeNrOne < $placeNrTwo) {
                    $duoPlace = new DuoPlaceNr($placeNrOne, $placeNrTwo);
                    $map[$duoPlace->getIndex()] = new CounterForDuoPlaceNr( $duoPlace );
                }
            }
        }
        return $map;
    }
////
////
////    /**
////     * @param array<string, CounterForDuoPlace> $map
////     * @param list<Place> $places
////     */
////    protected function addPlacesToDuoPlaceMap(array &$map, array $places): void
////    {
////        $placeCombination = new DuoPlace($places);
////        $map[$placeCombination->getIndex()] = new CounterForDuoPlace($placeCombination);
////    }
//
//
//    /**
//     * @param Poule $poule
//     * @return array<int, CounterForPlace>
//     */
//    public function initPlaceCounterMap(Poule $poule): array
//    {
//        $map = [];
//        foreach ($poule->getPlaces() as $place) {
//            $map[$place->getPlaceNr()] = new CounterForPlace($place);
//        }
//        return $map;
//    }
//
////    /**
////     * @param list<HomeAwayAbstract> $homeAways
////     * @return array<int, CounterForPlace>
////     */
////    public function initPlaceCounterMapForHomeAways(array $homeAways): array
////    {
////        $map = [];
////        foreach ($homeAways as $homeAway) {
////            foreach ($homeAway->getPlaces() as $place) {
////                if( !array_key_exists($place->getPlaceNr(), $map)) {
////                    $map[$place->getPlaceNr()] = new CounterForPlace($place);
////                }
////            }
////        }
////        return $map;
////    }
//
////    /**
////     * @param Side $side
////     * @param list<HomeAwayAbstract> $homeAways
////     * @return SideCounterMap
////     */
////    public function initAndFillSideCounterMap(Side $side, array $homeAways): SideCounterMap {
////        $placeCounterMap = (new CombinationMapper())->initPlaceCounterMapForHomeAways( $homeAways );
////        $sideCounterMap = new SideCounterMap($side, $placeCounterMap);
////        foreach( $homeAways as $homeAway) {
////            $sideCounterMap->addHomeAway($homeAway);
////        }
////        return $sideCounterMap;
////    }
}