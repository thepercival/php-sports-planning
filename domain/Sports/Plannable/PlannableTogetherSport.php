<?php

declare(strict_types=1);

namespace SportsPlanning\Sports\Plannable;

use Exception;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Input;
use SportsPlanning\Sports\SportWithNrOfFields;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsPlanning\Sports\SportWithNrOfPlaces\TogetherSportWithNrOfPlaces;

class PlannableTogetherSport extends PlannableSportAbstract
{
    public function __construct(
        public readonly TogetherSport       $sport,
        public readonly int                 $nrOfCycles,
        Input                               $input)
    {
        if( $this->nrOfCycles < 1 ) {
            throw new Exception('Nr of cycles must be greater than 1');
        }
        parent::__construct($input);
        $this->input->getSports()->add($this);
    }

    public function createSportWithNrOfPlaces(int $nrOfPlaces): TogetherSportWithNrOfPlaces {
        return new TogetherSportWithNrOfPlaces( $nrOfPlaces, $this->sport );
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
