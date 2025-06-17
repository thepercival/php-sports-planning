<?php

declare(strict_types=1);

namespace SportsPlanning\Sports\SportsWithNrAndFields;

use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsPlanning\Field;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstOneVsTwoWithNrOfPlaces;

final class AgainstOneVsTwoWithNrAndFields
{
    use SportWithNrAndFieldsTrait;

    /**
     * @param int $sportNr
     * @param AgainstOneVsTwo $sport
     * @param list<Field> $fields
     */
    private function __construct(public int $sportNr, public AgainstOneVsTwo $sport, public array $fields)
    {
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

//    public function __toString(): string
//    {
//        return $this->createVariant() . ' f(' . $this->getNrOfFields() . ')';
//    }
}
