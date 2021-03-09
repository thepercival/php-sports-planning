<?php

namespace SportsPlanning;

use \Doctrine\Common\Collections\ArrayCollection;
use \Doctrine\Common\Collections\Collection;
use SportsHelpers\SportBase;

class Sport extends SportBase
{
    /**
     * @var Collection | Field[]
     */
    protected $fields;

    public function __construct(protected Planning $planning, protected int $number, int $gameMode, int $nrOfGamePlaces)
    {
        parent::__construct($gameMode, $nrOfGamePlaces);
        $this->fields = new ArrayCollection();
    }

    public function getPlanning(): Planning
    {
        return $this->planning;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getFields(): Collection
    {
        return $this->fields;
    }
}
