<?php

declare(strict_types=1);

namespace SportsPlanning\Sports\Plannable;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use SportsPlanning\Field;
use SportsPlanning\Input;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

abstract class PlannableSport
{
    /**
     * @var Collection<int|string, Field>
     */
    protected Collection $fields;
    protected int $number;

    public function __construct(protected Input $input)
    {
        $this->number = $input->getSports()->count() + 1;
        $this->fields = new ArrayCollection();
    }

    public function getInput(): Input
    {
        return $this->input;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @return Collection<int|string, Field>
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function getField(int $number): Field
    {
        foreach ($this->getFields() as $field) {
            if ($field->getNumber() === $number) {
                return $field;
            }
        }
        throw new Exception('het veld kan niet gevonden worden', E_ERROR);
    }

    public function getNrOfFields(): int
    {
        return $this->getFields()->count();
    }




//    public function __toString(): string
//    {
//        return $this->createVariant() . ' f(' . $this->getNrOfFields() . ')';
//    }
}
