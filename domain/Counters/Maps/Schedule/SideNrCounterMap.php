<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsHelpers\Against\Side;
use SportsPlanning\Counters\Maps\PlaceNrCounterMapAbstract;
use SportsPlanning\Counters\Maps\PlaceNrCounterMapCreator;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

final class SideNrCounterMap extends PlaceNrCounterMapAbstract
{
    public function __construct(private readonly Side $side, int $nrOfPlaces)
    {
        parent::__construct($nrOfPlaces);
    }

    /**
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @return void
     */
    public function addHomeAways(array $homeAways): void
    {
        foreach ($homeAways as $homeAway) {
            $this->addHomeAway($homeAway);
        }
    }

    public function addHomeAway(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void
    {
        if( $homeAway instanceof OneVsOneHomeAway ) {
            $this->incrementPlaceNr($homeAway->get($this->side));
            return;
        }
        if( $homeAway instanceof OneVsTwoHomeAway ) {
            if( $this->side === Side::Home) {
                $this->incrementPlaceNr($homeAway->getHome());
            } else {
                foreach( $homeAway->getAway()->getPlaceNrs() as $awayPlaceNr) {
                    $this->incrementPlaceNr($awayPlaceNr);
                }
            }
            return;
        }
        foreach( $homeAway->get($this->side)->getPlaceNrs() as $sidePlaceNr) {
            $this->incrementPlaceNr($sidePlaceNr);
        }
    }
}
