<?php

declare(strict_types=1);

namespace SportsPlanning\Sports\Plannable;

use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsPlanning\Input;
use SportsPlanning\Sports\SportWithNrOfFields;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstTwoVsTwoWithNrOfPlaces;

class AgainstPlannableTwoVsTwo extends AgainstPlannableSport
{
    public function __construct(
        public readonly AgainstTwoVsTwo $sport,
        int                             $nrOfCycles,
        Input                           $input)
    {
        parent::__construct($nrOfCycles, $input);
        $this->input->getSports()->add($this);
    }

    public function createSportWithNrOfPlaces(int $nrOfPlaces): AgainstTwoVsTwoWithNrOfPlaces {
        return new AgainstTwoVsTwoWithNrOfPlaces( $nrOfPlaces, $this->sport );
    }

    public function createSportWithNrOfFields(): SportWithNrOfFields {
        return new SportWithNrOfFields( $this->sport, $this->getNrOfFields() );
    }

    public function createSportWithNrOfFieldsAndNrOfCycles(): SportWithNrOfFieldsAndNrOfCycles
    {
        return new SportWithNrOfFieldsAndNrOfCycles(
            $this->sport,
            $this->getNrOfFields(),
            $this->nrOfCycles
        );
    }

//    public function __toString(): string
//    {
//        return $this->createVariant() . ' f(' . $this->getNrOfFields() . ')';
//    }
}
