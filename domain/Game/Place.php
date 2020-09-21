<?php

namespace SportsPlanning\Game;

use SportsPlanning\Game;
use SportsPlanning\Place as PoulePlace;

class Place
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var Game
     */
    private $game;
    /**
     * @var bool
     */
    private $homeaway;
    /**
     * @var PoulePlace
     */
    private $place;

    public function __construct(Game $game, PoulePlace $place, bool $homeaway)
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
     * @return Game
     */
    public function getGame()
    {
        return $this->game;
    }

    public function setGame(Game $game)
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

    public function getPlace(): PoulePlace
    {
        return $this->place;
    }

    public function setPlace(PoulePlace $place)
    {
        $this->place = $place;
    }
}
