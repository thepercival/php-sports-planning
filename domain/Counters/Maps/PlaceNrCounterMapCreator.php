<?php

namespace SportsPlanning\Counters\Maps;

use SportsPlanning\Counters\CounterForPlaceNr;

class PlaceNrCounterMapCreator
{
    /**
     * @param int $nrOfPlaces
     * @return array<int, CounterForPlaceNr>
     */
    public function initPlaceNrCounterMap(int $nrOfPlaces): array
    {
        $map = [];
        for ( $placeNr = 1 ; $placeNr <= $nrOfPlaces ; $placeNr++ ) {
            $map[$placeNr] = new CounterForPlaceNr( $placeNr );
        }
        return $map;
    }
}