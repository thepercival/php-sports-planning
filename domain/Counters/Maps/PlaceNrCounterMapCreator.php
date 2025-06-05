<?php

namespace SportsPlanning\Counters\Maps;

use SportsPlanning\Counters\CounterForPlaceNr;

final class PlaceNrCounterMapCreator
{
    /**
     * @param int $nrOfPlaces
     * @return non-empty-array<int<1, max>, CounterForPlaceNr>
     */
    public function initPlaceNrCounterMap(int $nrOfPlaces): array
    {
        $map = [];
        for ( $placeNr = 1 ; $placeNr <= $nrOfPlaces ; $placeNr++ ) {
            $map[$placeNr] = new CounterForPlaceNr( $placeNr );
        }
        if( count($map) < 1) {
            throw new \Exception('nrOfPlaces must be at least 1');
        }

        return $map;
    }
}