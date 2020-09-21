<?php


namespace SportsPlanning;

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

    public function add(int $nrOfPlacesToAssign)
    {
        $this->addNrOfGames(1);
        $this->addNrOfAssignedPlaces($nrOfPlacesToAssign);
    }

    public function addNrOfGames(int $nrOfGames)
    {
        $this->nrOfGames += $nrOfGames;
    }

    public function addNrOfAssignedPlaces(int $nrOfAssignedPlaces)
    {
        $this->nrOfPlacesAssigned += $nrOfAssignedPlaces;
    }

    public function remove(int $nrOfPlacesToUnassign)
    {
        $this->nrOfGames--;
        $this->nrOfPlacesAssigned -= $nrOfPlacesToUnassign;
    }

    public function getNrOfPlacesAssigned( bool $addRefereePlace = null ): int
    {
        if( $addRefereePlace ) {
            return $this->nrOfPlacesAssigned + $this->nrOfGames;
        }
        return $this->nrOfPlacesAssigned;
    }

    public function getNrOfGames(): int
    {
        return $this->nrOfGames;
    }
}