<?php

namespace SportsPlanning;

use \Doctrine\Common\Collections\ArrayCollection;
use SportsHelpers\GameMode;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Against as AgainstGame;

class Poule
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
     * @var Planning
     */
    protected $planning;
    /**
     * @var Place[] | ArrayCollection
     */
    protected $places;
    /**
     * @var AgainstGame[] | ArrayCollection
     */
    protected $againstGames;
    /**
     * @var TogetherGame[] | ArrayCollection
     */
    protected $togetherGames;

    public function __construct(Planning $planning, int $number, int $nrOfPlaces)
    {
        $this->planning = $planning;
        $this->number = $number;
        $this->places = new ArrayCollection();
        for ($placeNr = 1 ; $placeNr <= $nrOfPlaces ; $placeNr++) {
            $this->places->add(new Place($this, $placeNr));
        }
        $this->againstGames = new ArrayCollection();
        $this->togetherGames = new ArrayCollection();
    }

    public function getPlanning(): Planning
    {
        return $this->planning;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @return Place[] | ArrayCollection
     */
    public function getPlaces()
    {
        return $this->places;
    }

    /**
     * @return ?Place
     */
    public function getPlace($number): ?Place
    {
        $places = $this->getPlaces()->filter(function ($place) use ($number): bool {
            return $place->getNumber() === $number;
        });
        if ($places->count() === 0) {
            return null;
        }
        return $places->first();
    }

    /**
     * @return AgainstGame[] | TogetherGame[] | ArrayCollection
     */
    public function getGames()
    {
        if( $this->getPlanning()->getInput()->getGameMode() === GameMode::AGAINST ) {
            return $this->againstGames;
        }
        return $this->togetherGames;
    }

    /**
     * @return AgainstGame[] | ArrayCollection
     */
    public function getAgainstGames()
    {
        return $this->againstGames;
    }

    /**
     * @return TogetherGame[] | ArrayCollection
     */
    public function getTogetherGames()
    {
        return $this->togetherGames;
    }
}
