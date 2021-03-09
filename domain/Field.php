<?php

namespace SportsPlanning;

use SportsHelpers\Identifiable;

class Field extends Identifiable implements Resource
{
    /**
     * @var int
     */
    protected $number;
    /**
     * @var Sport
     */
    protected $sport;

    public function __construct(Sport $sport)
    {
        $this->sport = $sport;
        $sport->getFields()->add($this);
        $this->number = $sport->getFields()->count();
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getSport(): Sport
    {
        return $this->sport;
    }
}
