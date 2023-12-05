<?php

declare(strict_types=1);

namespace SportsPlanning;

class Field extends Identifiable implements Resource
{
    protected int $number;

    public function __construct(protected Sport $sport)
    {
        $sport->getFields()->add($this);
        $this->number = $sport->getFields()->count();
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getUniqueIndex(): string
    {
        return $this->getSport()->getNumber() . '.' . $this->getNumber();
    }

    public function getSport(): Sport
    {
        return $this->sport;
    }
}
