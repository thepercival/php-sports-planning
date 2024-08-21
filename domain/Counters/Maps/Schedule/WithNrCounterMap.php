<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsPlanning\Counters\Maps\DuoPlaceNrCounterMapAbstract;
use SportsPlanning\Counters\Maps\DuoPlaceNrCounterMapCreator;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

final class WithNrCounterMap extends DuoPlaceNrCounterMapAbstract
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
        if( $homeAway instanceof OneVsTwoHomeAway ) {
            $this->incrementDuoPlaceNr($homeAway->getWithDuoPlaceNr());
        } else if( $homeAway instanceof TwoVsTwoHomeAway ) {
            $this->incrementDuoPlaceNrs($homeAway->createWithDuoPlaceNrs());
        }
    }
}
