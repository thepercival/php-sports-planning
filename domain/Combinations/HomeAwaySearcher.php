<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Place;

class HomeAwaySearcher
{
    public function __construct() {
    }

    /**
     * @param list<HomeAway> $homeAways
     * @param AgainstSide $side
     * @param list<Place> $places
     * @return list<HomeAway>
     */
    public function getHomeAwaysBySide(array $homeAways, AgainstSide $side, array $places): array
    {
        return array_values(array_filter($homeAways, function(HomeAway $homeAway) use ($side, $places): bool{
            return $homeAway->getPlaces($side) == $places;
        }));
    }

    /**
     * @param list<HomeAway> $homeAways
     * @param AgainstSide $side
     * @param Place $place
     * @return list<HomeAway>
     */
    public function getHomeAwaysByPlace(array $homeAways, AgainstSide $side, Place $place): array
    {
        return array_values(array_filter($homeAways, function(HomeAway $homeAway) use ($side, $place): bool{
            return $homeAway->hasPlace($place, $side);
        }));
    }
}
