<?php

declare(strict_types=1);

namespace SportsPlanning\Sports\SportsWithNrAndFields;

use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Field;
use SportsPlanning\Sports\SportWithNrOfPlaces\TogetherSportWithNrOfPlaces;

final readonly class TogetherSportWithNrAndFields
{
    use SportWithNrAndFieldsTrait;

    /**
     * @param int $sportNr
     * @param TogetherSport $sport
     * @param list<Field> $fields
     */
    private function __construct(public int $sportNr, public TogetherSport $sport, public array $fields)
    {
    }

    public static function fromNrOfFields(int $sportNr, TogetherSport $sport, int $nrOfFields): self {
        $fields = [];
        for ($fieldNr = 1; $fieldNr <= $nrOfFields; $fieldNr++) {
            $fields[] = new Field($fieldNr, $sportNr);
        }
        return new self($sportNr, $sport, $fields);
    }

    public function createSportWithNrOfPlaces(int $nrOfPlaces): TogetherSportWithNrOfPlaces {
        return new TogetherSportWithNrOfPlaces( $nrOfPlaces, $this->sport );
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
