<?php

declare(strict_types=1);

namespace SportsPlanning\Sports\Plannable;

use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsPlanning\Sports\SportWithNrOfFields;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstTwoVsTwoWithNrOfPlaces;

class AgainstTwoVsTwoWithNrAndFields extends SportWithNrAndFields
{
    public function __construct(int $sportNr, public readonly AgainstTwoVsTwo $sport, int $nrOfFields)
    {
        parent::__construct($sportNr, $nrOfFields);
    }

    public function createSportWithNrOfPlaces(int $nrOfPlaces): AgainstTwoVsTwoWithNrOfPlaces {
        return new AgainstTwoVsTwoWithNrOfPlaces( $nrOfPlaces, $this->sport );
    }

    public function createSportWithNrOfFields(): SportWithNrOfFields {
        return new SportWithNrOfFields( $this->sport, count($this->fields) );
    }

//    public function __toString(): string
//    {
//        return $this->createVariant() . ' f(' . $this->getNrOfFields() . ')';
//    }
}
