<?php

declare(strict_types=1);

namespace SportsPlanning\HomeAways;

use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Place;

class HomeAwaySearcher
{
    public function __construct() {
    }

//    /**
//     * @param HomeAwayAbstract $homeAways
//     * @param AgainstSide $side
//     * @param HomeAwayAbstract $places
//     * @return HomeAwayAbstract
//     */
//    public function getHomeAwaysBySide(array $homeAways, AgainstSide $side, array $places): array
//    {
//        return array_values(array_filter($homeAways, function(HomeAwayAbstract $homeAway) use ($side, $places): bool{
//            return $homeAway->getPlaces($side) == $places;
//        }));
//    }
//
//    /**
//     * @param HomeAwayAbstract $homeAways
//     * @param AgainstSide $side
//     * @param Place $place
//     * @return HomeAwayAbstract
//     */
//    public function getHomeAwaysByPlace(array $homeAways, AgainstSide $side, Place $place): array
//    {
//        return array_values(array_filter($homeAways, function(HomeAwayAbstract $homeAway) use ($side, $place): bool{
//            return $homeAway->hasPlace($place, $side);
//        }));
//    }
}
