<?php

declare(strict_types=1);

namespace SportsPlanning\Sports\Plannable;

use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsPlanning\Input;
use SportsPlanning\Sports\SportWithNrOfFields;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstOneVsTwoWithNrOfPlaces;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstTwoVsTwoWithNrOfPlaces;

abstract class AgainstPlannableTwoVsTwo extends AgainstPlannableSport
{
    public function __construct(
        public readonly AgainstTwoVsTwo $sport,
        int                             $nrOfCycles,
        Input                           $input)
    {
        parent::__construct($nrOfCycles, $input);
    }

    public function createSportWithNrOfPlaces(int $nrOfPlaces): AgainstTwoVsTwoWithNrOfPlaces {
        return new AgainstTwoVsTwoWithNrOfPlaces( $nrOfPlaces, $this->sport );
    }

    public function createSportWithNrOfFields(): SportWithNrOfFields {
        return new SportWithNrOfFields( $this->sport, $this->getNrOfFields() );
    }

//    public function __toString(): string
//    {
//        return $this->createVariant() . ' f(' . $this->getNrOfFields() . ')';
//    }
}
