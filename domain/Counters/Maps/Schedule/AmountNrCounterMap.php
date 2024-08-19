<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsPlanning\Counters\Maps\PlaceNrCounterMapAbstract;
use SportsPlanning\Counters\Maps\PlaceNrCounterMapCreator;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

final class AmountNrCounterMap extends PlaceNrCounterMapAbstract
{
    /**
     * @param int $nrOfPlaces
     */
    public function __construct(int $nrOfPlaces)
    {
        parent::__construct($nrOfPlaces);
    }

    public function addHomeAway(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void
    {
        foreach( $homeAway->convertToPlaceNrs() as $placeNr ) {
            $this->incrementPlaceNr($placeNr);
        }
    }
}
