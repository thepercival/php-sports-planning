<?php

namespace SportsPlanning;

class Batch
{
    /**
     * @var int
     */
    protected $number;
    /**
     * @var Batch
     */
    protected $previous;
    /**
     * @var Batch
     */
    protected $next;
    /**
     * @var array | Game[]
     */
    protected $games = [];
    /**
     * @var array | Place[]
     */
    protected $places = [];
    /**
     * @var array | int[]
     */
    protected $previousGamesInARowMap = [];

    public function __construct(Batch $previous = null)
    {
        $this->previous = $previous;
        $this->number = $previous === null ? 1 : $previous->getNumber() + 1;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function hasNext(): bool
    {
        return $this->next !== null;
    }

    /**
     * @return Batch
     */
    public function getNext(): Batch
    {
        return $this->next;
    }

    public function createNext(): Batch
    {
        $this->next = new Batch($this);
        $this->getNext()->setPreviousGamesInARow($this->previousGamesInARowMap, $this->createMapGamesInARowMap() );
        return $this->getNext();
    }

    /**
     * @return array|int[]
     */
    protected function createMapGamesInARowMap(): array
    {
        $map = [];
        foreach( $this->places as $place ) {
            $map[$place->getLocation()] = 1;
        }
        return $map;
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
        return $this->getPreviousGamesInARow($place) + 1;
    }

    /**
     * @param array|int[] $previousPreviousGamesInARowMap
     * @param array|int[] $previousGamesInARowMap
     */
    public function setPreviousGamesInARow( array $previousPreviousGamesInARowMap, array $previousGamesInARowMap )
    {
        $this->previousGamesInARowMap = $previousGamesInARowMap;
        foreach( $this->previousGamesInARowMap as $placeLocation => $nrOfGamesInARow ) {
            if( !array_key_exists( $placeLocation, $previousPreviousGamesInARowMap) ) {
                continue;
            }
            $this->previousGamesInARowMap[$placeLocation] += $previousPreviousGamesInARowMap[$placeLocation];
        }
    }

    public function getPreviousGamesInARow( Place $place ): int {
        if( !array_key_exists( $place->getLocation(), $this->previousGamesInARowMap ) ) {
            return 0;
        }
        return $this->previousGamesInARowMap[$place->getLocation()];
    }

    public function add(Game $game)
    {
        $this->games[] = $game;
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
        foreach ($game->getPlaces() as $gamePlace) {
            unset($this->places[$gamePlace->getPlace()->getLocation()]);
        }
    }

    /**
     * @return array|Place[]
     */
    protected function getPlaces(): array
    {
        return $this->places;
    }

    /**
     * @param Poule|null $poule
     * @return array|Game[]
     */
    public function getGames( Poule $poule = null ): array
    {
        if( $poule === null ) {
            return $this->games;
        }
        return array_filter( $this->games, function( Game $game) use ($poule ): bool { return $game->getPoule() === $poule; } );
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



    /**
     * @return array|Poule[]
     */
    public function getPoules(): array {
        $poules = [];
        foreach( $this->getGames() as $game ) {
            if( array_search( $game->getPoule(), $poules, true ) === false ) {
                $poules[] = $game->getPoule();
            }
        }
        return $poules;
    }
}
