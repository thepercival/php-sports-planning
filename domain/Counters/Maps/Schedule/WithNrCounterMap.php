<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsPlanning\Counters\Maps\DuoPlaceNrCounterMap;
use SportsPlanning\Counters\Maps\DuoPlaceNrCounterMapCreator;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

final class WithNrCounterMap extends DuoPlaceNrCounterMap
{
    /**
     * @param int|null $nrOfPlaces
     */
    public function __construct(int|null $nrOfPlaces = null)
    {
        if( $nrOfPlaces === null ) {
            $duoPlaceNrCounterMap = [];
        } else {
            $duoPlaceNrCounterMapCreator = new DuoPlaceNrCounterMapCreator();
            $duoPlaceNrCounterMap = $duoPlaceNrCounterMapCreator->initDuoPlaceNrCounterMap($nrOfPlaces);
        }
        parent::__construct($duoPlaceNrCounterMap);
    }

    /**
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @return void
     */
    public function addHomeAways(array $homeAways): void
    {
        foreach( $homeAways as $homeAway ) {
            $this->addHomeAway($homeAway);
        }
    }

    public function addHomeAway(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void
    {
        if($homeAway instanceof OneVsOneHomeAway) {
            throw new \Exception('oneVsOne does not have with');
        }

        if( $homeAway instanceof OneVsTwoHomeAway ) {
            $this->addDuoPlaceNr($homeAway->getWithDuoPlaceNr());
            return;
        }
        $this->addDuoPlaceNrs($homeAway->createWithDuoPlaceNrs());
    }
}
