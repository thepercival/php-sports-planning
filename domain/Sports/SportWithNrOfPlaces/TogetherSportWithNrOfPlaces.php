<?php

namespace SportsPlanning\Sports\SportWithNrOfPlaces;

use SportsHelpers\Sports\TogetherSport;

class TogetherSportWithNrOfPlaces extends SportWithNrOfPlacesAbstract
{
    public function __construct(int $nrOfPlaces, public TogetherSport $sport ) {
        parent::__construct($nrOfPlaces );
    }

    // in function calls public int $nrOfCycles
}