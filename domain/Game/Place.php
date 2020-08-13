<?php

namespace SportsPlanning\Game;

use SportsPlanning\Game as PlanningGame;
use SportsPlanning\Place as PlanningPlace;

class Place
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var PlanningGame
     */
    private $game;
    /**
     * @var bool
     */
    private $homeaway;
    /**
     * @var PlanningPlace
     */
    private $place;

    public function __construct(PlanningGame $game, PlanningPlace $place, bool $homeaway)
    {
        $this->setGame($game);
        $this->setPlace($place);
        $this->setHomeaway($homeaway);
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return PlanningGame
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * @param PlanningGame $game
     */
    public function setGame(PlanningGame $game)
    {
        if ($this->game === null and !$game->getPlaces()->contains($this)) {
            $game->getPlaces()->add($this) ;
        }
        $this->game = $game;
    }

    /**
     * @return bool
     */
    public function getHomeaway()
    {
        return $this->homeaway;
    }

    /**
     * @param bool $homeaway
     */
    public function setHomeaway($homeaway)
    {
        $this->homeaway = $homeaway;
    }

//    public function setPlaceNr( int $placeNr )
//    {
//        $this->placeNr = $placeNr;
//    }

    /**
     * @return PlanningPlace
     */
    public function getPlace(): PlanningPlace
    {
        return $this->place;
    }

    /**
     * @param PlanningPlace $place
     */
    public function setPlace(PlanningPlace $place)
    {
        $this->place = $place;
    }
}
