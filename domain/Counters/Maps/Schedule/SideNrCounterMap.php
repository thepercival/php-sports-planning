<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsHelpers\Against\Side;
use SportsPlanning\Counters\Maps\PlaceNrCounterMap;
use SportsPlanning\Counters\Maps\PlaceNrCounterMapCreator;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

final class SideNrCounterMap extends PlaceNrCounterMap
{
    public function __construct(private readonly Side $side, int|null $nrOfPlaces = null)
    {
        if( $nrOfPlaces === null ) {
            $placeNrCounterMap = [];
        } else {
            $placeNrCounterMapCreator = new PlaceNrCounterMapCreator();
            $placeNrCounterMap = $placeNrCounterMapCreator->initPlaceNrCounterMap($nrOfPlaces);
        }
        parent::__construct($placeNrCounterMap);
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
            $this->addPlaceNr($homeAway->get($this->side));
            return;
        }
        if( $homeAway instanceof OneVsTwoHomeAway ) {
            if( $this->side === Side::Home) {
                $this->addPlaceNr($homeAway->getHome());
            } else {
                foreach( $homeAway->getAway()->getPlaceNrs() as $awayPlaceNr) {
                    $this->addPlaceNr($awayPlaceNr);
                }
            }
            return;
        }
        foreach( $homeAway->get($this->side)->getPlaceNrs() as $sidePlaceNr) {
            $this->addPlaceNr($sidePlaceNr);
        }
    }
}
