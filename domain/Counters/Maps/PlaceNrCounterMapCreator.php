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
        for ( $placeNrOne = 1 ; $placeNrOne <= $nrOfPlaces ; $placeNrOne++ ) {
            $map[$nrOfPlaces] = new CounterForPlaceNr( $nrOfPlaces );
        }
        return $map;
    }
}