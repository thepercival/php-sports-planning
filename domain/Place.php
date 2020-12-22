<?php

namespace SportsPlanning;

use SportsHelpers\Identifiable;

class Place extends Identifiable implements Resource
{
    /**
     * @var Poule
     */
    protected $poule;
    /**
     * @var int
     */
    protected $number;
    /**
     * @var string
     */
    protected $location;

    public function __construct(Poule $poule, int $number)
    {
        $this->poule = $poule;
        $this->number = $number;
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getLocation(): string
    {
        if ($this->location === null) {
            $this->location = $this->poule->getNumber() . '.' . $this->number;
        }
        return $this->location;
    }
}
