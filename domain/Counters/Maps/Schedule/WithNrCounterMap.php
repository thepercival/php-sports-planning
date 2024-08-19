<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsPlanning\Counters\Maps\DuoPlaceNrCounterMapAbstract;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

final class WithNrCounterMap extends DuoPlaceNrCounterMapAbstract
{
    public function __construct(int $nrOfPlaces)
    {
        parent::__construct($nrOfPlaces);
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
            $this->incrementDuoPlaceNr($homeAway->getWithDuoPlaceNr());
            return;
        }
        $this->incrementDuoPlaceNrs($homeAway->createWithDuoPlaceNrs());
    }
}
