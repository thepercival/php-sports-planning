<?php


namespace SportsPlanning\Batch;

use SportsPlanning\Poule;

class PouleCounter
{
    /**
     * @var Poule
     */
    protected $poule;
    /**
     * @var int
     */
    protected $nrOfGames;
    /**
     * @var int
     */
    protected $nrOfPlacesAssigned;

    public function __construct(Poule $poule, int $nrOfPlacesAssigned = 0)
    {
        $this->poule = $poule;
        $this->nrOfGames = 0;
        $this->nrOfPlacesAssigned = $nrOfPlacesAssigned;
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function reset()
    {
        $this->nrOfGames = 0;
        $this->nrOfPlacesAssigned = 0;
    }

    public function add(int $nrOfPlacesAssigned)
    {
        $this->nrOfGames++;
        $this->nrOfPlacesAssigned += $nrOfPlacesAssigned;
    }

    public function getNrOfPlacesAssigned(): int
    {
        return $this->nrOfPlacesAssigned;
    }

    public function getNrOfGames(): int
    {
        return $this->nrOfGames;
    }
}