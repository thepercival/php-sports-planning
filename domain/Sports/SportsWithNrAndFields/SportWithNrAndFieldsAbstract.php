<?php

declare(strict_types=1);

namespace SportsPlanning\Sports\SportsWithNrAndFields;

use Exception;
use SportsPlanning\Field;

abstract class SportWithNrAndFieldsAbstract
{
    /**
     * @var list<Field>
     */
    public readonly array $fields;

    public function __construct(public readonly int $sportNr, int $nrOfFields)
    {
        $fields = [];
        for ($fieldNr = 1; $fieldNr <= $nrOfFields; $fieldNr++) {
            $fields[] = new Field($fieldNr, $sportNr);
        }
        $this->fields = $fields;
    }

    public function getField(int $fieldNr): Field
    {
        foreach ($this->fields as $field) {
            if ($field->fieldNr === $fieldNr) {
                return $field;
            }
        }
        throw new Exception('het veld kan niet gevonden worden', E_ERROR);
    }

//    public function __toString(): string
//    {
//        return $this->createVariant() . ' f(' . $this->getNrOfFields() . ')';
//    }
}
