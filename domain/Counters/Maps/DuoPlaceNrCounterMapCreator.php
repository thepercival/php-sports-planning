<?php

namespace SportsPlanning\Counters\Maps;

use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\CounterForDuoPlaceNr;

class DuoPlaceNrCounterMapCreator
{
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
}