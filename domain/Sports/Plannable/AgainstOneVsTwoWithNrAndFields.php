<?php

declare(strict_types=1);

namespace SportsPlanning\Sports\Plannable;

use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsPlanning\Sports\SportWithNrOfFields;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstOneVsTwoWithNrOfPlaces;

class AgainstOneVsTwoWithNrAndFields extends SportWithNrAndFields
{
    public function __construct(int $sportNr, public readonly AgainstOneVsTwo $sport, int $nrOfFields)
    {
        parent::__construct($sportNr, $nrOfFields);
    }

    public function createSportWithNrOfPlaces(int $nrOfPlaces): AgainstOneVsTwoWithNrOfPlaces {
        return new AgainstOneVsTwoWithNrOfPlaces( $nrOfPlaces, $this->sport );
    }

    public function createSportWithNrOfFields(): SportWithNrOfFields {
        return new SportWithNrOfFields( $this->sport, count($this->fields) );
    }

//    public function __toString(): string
//    {
//        return $this->createVariant() . ' f(' . $this->getNrOfFields() . ')';
//    }
}
