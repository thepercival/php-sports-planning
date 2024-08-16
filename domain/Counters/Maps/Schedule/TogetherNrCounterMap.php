<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsPlanning\Counters\Maps\DuoPlaceNrCounterMap;
use SportsPlanning\Counters\Maps\DuoPlaceNrCounterMapCreator;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

final class TogetherNrCounterMap extends DuoPlaceNrCounterMap
{
    /**
     * @param int $nrOfPlaces
     */
    public function __construct(int $nrOfPlaces)
    {
        $duoPlaceNrCounterMapCreator = new DuoPlaceNrCounterMapCreator();
        $withNrCounterMap = $duoPlaceNrCounterMapCreator->initDuoPlaceNrCounterMap($nrOfPlaces);
        parent::__construct($withNrCounterMap);
    }

    public function addHomeAway(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void
    {
        if( $homeAway instanceof OneVsOneHomeAway ) {
            $this->incrementDuoPlaceNr($homeAway->createAgainstDuoPlaceNr());
            return;
        }
        if( $homeAway instanceof OneVsTwoHomeAway ) {
            $this->incrementDuoPlaceNrs($homeAway->createTogetherDuoPlaceNrs());
            return;
        }
        $this->incrementDuoPlaceNrs($homeAway->createTogetherDuoPlaceNrs());
    }
}
