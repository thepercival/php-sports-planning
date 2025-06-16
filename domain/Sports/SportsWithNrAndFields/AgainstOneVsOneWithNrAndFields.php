<?php

declare(strict_types=1);

namespace SportsPlanning\Sports\SportsWithNrAndFields;

use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Field;
use SportsPlanning\Sports\SportWithNrOfFields;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstOneVsOneWithNrOfPlaces;

final class AgainstOneVsOneWithNrAndFields extends SportWithNrAndFieldsAbstract
{
    /**
     * @param int $sportNr
     * @param AgainstOneVsOne $sport
     * @param list<Field> $fields
     */
    private function __construct(int $sportNr, public readonly AgainstOneVsOne $sport, array $fields)
    {
        parent::__construct($sportNr, $fields);
    }

    public static function fromNrOfFields(int $sportNr, AgainstOneVsOne $sport, int $nrOfFields): self {
        $fields = [];
        for ($fieldNr = 1; $fieldNr <= $nrOfFields; $fieldNr++) {
            $fields[] = new Field($fieldNr, $sportNr);
        }
        return new self($sportNr, $sport, $fields);
    }

    public function createSportWithNrOfPlaces(int $nrOfPlaces): AgainstOneVsOneWithNrOfPlaces {
        return new AgainstOneVsOneWithNrOfPlaces( $nrOfPlaces, $this->sport );
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
