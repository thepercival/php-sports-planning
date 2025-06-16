<?php

declare(strict_types=1);

namespace SportsPlanning\Sports\SportsWithNrAndFields;

use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsPlanning\Field;
use SportsPlanning\Sports\SportWithNrOfFields;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstTwoVsTwoWithNrOfPlaces;

final class AgainstTwoVsTwoWithNrAndFields extends SportWithNrAndFieldsAbstract
{
    /**
     * @param int $sportNr
     * @param AgainstTwoVsTwo $sport
     * @param list<Field> $fields
     */
    private function __construct(int $sportNr, public readonly AgainstTwoVsTwo $sport, array $fields)
    {
        parent::__construct($sportNr, $fields);
    }

    public static function fromNrOfFields(int $sportNr, AgainstTwoVsTwo $sport, int $nrOfFields): self {
        $fields = [];
        for ($fieldNr = 1; $fieldNr <= $nrOfFields; $fieldNr++) {
            $fields[] = new Field($fieldNr, $sportNr);
        }
        return new self($sportNr, $sport, $fields);
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
