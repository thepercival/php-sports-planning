<?php

namespace SportsPlanning;

use \Doctrine\Common\Collections\ArrayCollection;
use \Doctrine\Common\Collections\Collection;
use SportsHelpers\SportBase;

class Sport extends SportBase
{
    /**
     * @var int
     */
    protected $number;
    /**
     * @var Planning
     */
    protected $planning;
    /**
     * @var Collection | Field[]
     */
    protected $fields;

    public function __construct(Planning $planning, int $number, int $nrOfGamePlaces )
    {
        parent::__construct($nrOfGamePlaces);
        $this->planning = $planning;
        $this->number = $number;
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
