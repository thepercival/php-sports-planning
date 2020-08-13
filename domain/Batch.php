<?php

namespace SportsPlanning;

class Batch
{
    /**
     * @var int
     */
    private $number;
    /**
     * @var Batch
     */
    private $previous;
    /**
     * @var Batch
     */
    private $next;
    /**
     * @var array | Game[]
     */
    private $games = [];
    /**
     * @var array | Place[]
     */
    private $places = [];
    /**
     * @var array | Place[]
     */
    private $placesAsReferee = [];

    public function __construct(Batch $previous = null)
    {
        $this->previous = $previous;
        $this->number = $previous === null ? 1 : $previous->getNumber() + 1;
        ;
        $this->placesAsReferee = [];
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function hasNext(): bool
    {
        return $this->next !== null;
    }

    public function getNext(): Batch
    {
        return $this->next;
    }

    public function createNext(): Batch
    {
        $this->next = new Batch($this);
        return $this->getNext();
    }

    /*public function removeNext() {
        $this->next = null;
    }*/

    public function hasPrevious(): bool
    {
        return $this->previous !== null;
    }

    public function getPrevious(): Batch
    {
        return $this->previous;
    }

    public function getFirst(): Batch
    {
        return $this->hasPrevious() ? $this->previous->getFirst() : $this;
    }

    public function getLeaf(): Batch
    {
        return $this->hasNext() ? $this->next->getLeaf() : $this;
    }

    public function getGamesInARow(Place $place): int
    {
        $hasPlace = $this->isParticipating($place);
        if (!$hasPlace) {
            return 0;
        }
        if (!$this->hasPrevious()) {
            return 1;
        }
        return $this->getPrevious()->getGamesInARow($place) + 1;
    }

    public function add(Game $game)
    {
        $this->games[] = $game;
        /** @var Game\Place $gamePlace */
        foreach ($game->getPlaces() as $gamePlace) {
            $this->places[$gamePlace->getPlace()->getLocation()] = $gamePlace->getPlace();
        }
    }

    public function remove(Game $game)
    {
        $index = array_search($game, $this->games, true);
        if ($index !== false) {
            unset($this->games[$index]);
        }
        /** @var Game\Place $gamePlace */
        foreach ($game->getPlaces() as $gamePlace) {
            unset($this->places[$gamePlace->getPlace()->getLocation()]);
        }
    }

    protected function getPlaces(): array
    {
        return $this->places;
    }

    public function getGames(): array
    {
        return $this->games;
    }

    public function isParticipating(Place $place): bool
    {
        return array_key_exists($place->getLocation(), $this->places);
    }

    /**
     * @return array|Game[]
     */
    public function getAllGames(): array
    {
        if ($this->hasNext() === false) {
            return $this->getGames();
        }
        return array_merge($this->getGames(), $this->getNext()->getAllGames());
    }

    public function addAsReferee(Place $placeReferee)
    {
        $this->placesAsReferee[$placeReferee->getLocation()] = $placeReferee;
    }

    /**
     * @return array|Place[]
     */
    public function getPlacesAsReferees(): array
    {
        return $this->placesAsReferee;
    }

    public function removeAsReferee(Place $placeReferee)
    {
        unset($this->placesAsReferee[$placeReferee->getLocation()]);
    }

    public function emptyPlacesAsReferees()
    {
        $this->placesAsReferee = [];
    }

    public function isParticipatingAsReferee(Place $placeReferee): bool
    {
        return array_key_exists($placeReferee->getLocation(), $this->placesAsReferee);
    }
}
