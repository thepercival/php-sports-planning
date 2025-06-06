<?php

declare(strict_types=1);

namespace SportsPlanning\Sports\SportsWithNrAndFields;

use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Sports\SportWithNrOfFields;
use SportsPlanning\Sports\SportWithNrOfPlaces\TogetherSportWithNrOfPlaces;

final class TogetherSportWithNrAndFields extends SportWithNrAndFieldsAbstract
{
    public function __construct(int $sportNr, public readonly TogetherSport $sport, int $nrOfFields)
    {
        parent::__construct($sportNr, $nrOfFields);
    }

    public function createSportWithNrOfPlaces(int $nrOfPlaces): TogetherSportWithNrOfPlaces {
        return new TogetherSportWithNrOfPlaces( $nrOfPlaces, $this->sport );
    }

    public function createSportWithNrOfFields(): SportWithNrOfFields {
        return new SportWithNrOfFields( $this->sport, count($this->fields) );
    }

//    public function createSportWithNrOfFieldsAndNrOfCycles(): SportWithNrOfFieldsAndNrOfCycles
//    {
//        return new SportWithNrOfFieldsAndNrOfCycles(
//            $this->sport,
//            $this->getNrOfFields(),
//            $this->nrOfCycles
//        );
//    }

//    public function __toString(): string
//    {
//        return $this->createVariant() . ' f(' . $this->getNrOfFields() . ')';
//    }
}
