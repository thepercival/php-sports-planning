<?php

declare(strict_types=1);

namespace SportsPlanning;

use SportsHelpers\Identifiable;
use SportsPlanning\Sports\Plannable\PlannableSport;

class Field extends Identifiable implements Resource
{
    protected int $number;

    public function __construct(protected PlannableSport $sport, int $number = null)
    {
        if( $number === null ) {
            $number = $sport->getFields()->count() + 1;
        }
        $this->number = $number;
        $sport->getFields()->add($this);
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getUniqueIndex(): string
    {
        return $this->getSport()->getNumber() . '.' . $this->getNumber();
    }

    public function getSport(): PlannableSport
    {
        return $this->sport;
    }
}
