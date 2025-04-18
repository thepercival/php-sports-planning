<?php

declare(strict_types=1);

namespace SportsPlanning\Sports\Plannable;

use oldsportshelpers\old\WithNrOfPlaces\SportWithNrOfPlaces;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Input;
use SportsPlanning\Sports\SportWithNrOfFields;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstOneVsOneWithNrOfPlaces;

abstract class AgainstPlannableOneVsOne extends AgainstPlannableSport
{
    public function __construct(
        public readonly AgainstOneVsOne $sport,
        int                             $nrOfCycles,
        Input                           $input)
    {
        parent::__construct($nrOfCycles, $input);
    }

    public function createSportWithNrOfPlaces(int $nrOfPlaces): AgainstOneVsOneWithNrOfPlaces {
        return new AgainstOneVsOneWithNrOfPlaces( $nrOfPlaces, $this->sport );
    }

    public function createSportWithNrOfFields(): SportWithNrOfFields {
        return new SportWithNrOfFields( $this->sport, $this->getNrOfFields() );
    }

//    public function __toString(): string
//    {
//        return $this->createVariant() . ' f(' . $this->getNrOfFields() . ')';
//    }
}
