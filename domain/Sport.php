<?php

namespace SportsPlanning;

use \Doctrine\Common\Collections\ArrayCollection;
use \Doctrine\Common\Collections\Collection;

class Sport
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    protected $number;
    /**
     * @var int
     */
    protected $nrOfGamePlaces;
    /**
     * @var Planning
     */
    protected $planning;
    /**
     * @var Collection | Field[]
     */
    protected $fields;

    public function __construct(Planning $planning, int $number, int $nrOfGamePlaces)
    {
        $this->planning = $planning;
        $this->number = $number;
        $this->nrOfGamePlaces = $nrOfGamePlaces;
        $this->fields = new ArrayCollection();
    }

    public function getPlanning(): Planning
    {
        return $this->planning;
    }

    /**
     * @return int
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @return int
     */
    public function getNrOfGamePlaces(): int
    {
        return $this->nrOfGamePlaces;
    }

    public function getFields(): Collection
    {
        return $this->fields;
    }
}
