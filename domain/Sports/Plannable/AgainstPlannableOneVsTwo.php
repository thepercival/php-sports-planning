<?php

declare(strict_types=1);

namespace SportsPlanning\Sports\Plannable;

use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsPlanning\Input;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstOneVsTwoWithNrOfPlaces;

class AgainstPlannableOneVsTwo extends AgainstPlannableSport
{
    public function __construct(
        public readonly AgainstOneVsTwo $sport,
        int                             $nrOfCycles,
        Input                           $input)
    {
        parent::__construct($nrOfCycles, $input);
    }

    public function createSportWithNrOfPlaces(int $nrOfPlaces): AgainstOneVsTwoWithNrOfPlaces {
        return new AgainstOneVsTwoWithNrOfPlaces( $nrOfPlaces, $this->sport );
    }

//    public function __toString(): string
//    {
//        return $this->createVariant() . ' f(' . $this->getNrOfFields() . ')';
//    }
}
