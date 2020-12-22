<?php

namespace SportsPlanning;

use \Doctrine\Common\Collections\ArrayCollection;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\AgainstEachOther as AgainstEachOtherGame;

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
     * @var AgainstEachOtherGame[] | ArrayCollection
     */
    protected $againstEachOtherGames;
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
        $this->againstEachOtherGames = new ArrayCollection();
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
     * @return AgainstEachOtherGame[] | TogetherGame[] | ArrayCollection
     */
    public function getGames()
    {
        if( $this->againstEachOtherGames->count() > 0 ) {
            return $this->againstEachOtherGames;
        }
        return $this->togetherGames;
    }

    /**
     * @return AgainstEachOtherGame[] | ArrayCollection
     */
    public function getAgainstEachOtherGames()
    {
        return $this->againstEachOtherGames;
    }

    /**
     * @return TogetherGame[] | ArrayCollection
     */
    public function getTogetherGames()
    {
        return $this->togetherGames;
    }
}
