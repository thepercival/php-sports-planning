<?php

declare(strict_types=1);

namespace SportsPlanning;

/**
 * @api
 */
class Field extends Identifiable implements Resource
{
    protected int $number;

    public function __construct(protected Sport $sport, int|null $number)
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

    #[\Override]
    public function getUniqueIndex(): string
    {
        return $this->getSport()->getNumber() . '.' . $this->getNumber();
    }

    public function getSport(): Sport
    {
        return $this->sport;
    }
}
