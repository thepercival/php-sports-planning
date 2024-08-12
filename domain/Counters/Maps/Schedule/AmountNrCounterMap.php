<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsPlanning\Counters\Maps\PlaceNrCounterMap;
use SportsPlanning\Counters\Maps\PlaceNrCounterMapCreator;
use SportsPlanning\HomeAways\HomeAwayInterface;

final class AmountNrCounterMap extends PlaceNrCounterMap
{
    /**
     * @param int|null $nrOfPlaces
     */
    public function __construct(int|null $nrOfPlaces = null)
    {
        if( $nrOfPlaces === null ) {
            $placeNrCounterMap = [];
        } else {
            $placeNrCounterMapCreator = new PlaceNrCounterMapCreator();
            $placeNrCounterMap = $placeNrCounterMapCreator->initPlaceNrCounterMap($nrOfPlaces);
        }
        parent::__construct($placeNrCounterMap);
    }

    public function addHomeAway(HomeAwayInterface $homeAway): void
    {
        foreach( $homeAway->convertToPlaceNrs() as $placeNr ) {
            $this->addPlaceNr($placeNr);
        }
    }
}
