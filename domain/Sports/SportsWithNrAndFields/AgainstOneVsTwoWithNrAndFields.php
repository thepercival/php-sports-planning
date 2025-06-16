<?php

declare(strict_types=1);

namespace SportsPlanning\Sports\SportsWithNrAndFields;

use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsPlanning\Field;
use SportsPlanning\Sports\SportWithNrOfFields;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstOneVsTwoWithNrOfPlaces;

final class AgainstOneVsTwoWithNrAndFields extends SportWithNrAndFieldsAbstract
{
    /**
     * @param int $sportNr
     * @param AgainstOneVsTwo $sport
     * @param list<Field> $fields
     */
    private function __construct(int $sportNr, public readonly AgainstOneVsTwo $sport, array $fields)
    {
        parent::__construct($sportNr, $fields);
    }

    public static function fromNrOfFields(int $sportNr, AgainstOneVsTwo $sport, int $nrOfFields): self {
        $fields = [];
        for ($fieldNr = 1; $fieldNr <= $nrOfFields; $fieldNr++) {
            $fields[] = new Field($fieldNr, $sportNr);
        }
        return new self($sportNr, $sport, $fields);
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
