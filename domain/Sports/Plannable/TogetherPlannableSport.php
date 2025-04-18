<?php

declare(strict_types=1);

namespace SportsPlanning\Sports\Plannable;

use Exception;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Input;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstTwoVsTwoWithNrOfPlaces;
use SportsPlanning\Sports\SportWithNrOfPlaces\TogetherSportWithNrOfPlaces;

class TogetherPlannableSport extends PlannableSport
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
    }

    public function createSportWithNrOfPlaces(int $nrOfPlaces): TogetherSportWithNrOfPlaces {
        return new TogetherSportWithNrOfPlaces( $nrOfPlaces, $this->sport );
    }

//    public function createVariantWithFields(): SportPersistVariantWithNrOfFields
//    {
//        return new SportPersistVariantWithNrOfFields($this->createVariant(), $this->getNrOfFields());
//    }

//    public function __toString(): string
//    {
//        return $this->createVariant() . ' f(' . $this->getNrOfFields() . ')';
//    }
}
