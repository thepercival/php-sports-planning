<?php

namespace SportsPlanning;

use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;

class Batch
{
    /**
     * @var int
     */
    protected $number;
    protected Batch|null $previous;
    protected Batch|null $next = null;
    /**
     * @var array<TogetherGame|AgainstGame>
     */
    protected array $games = [];
    /**
     * @var array<Place>
     */
    protected array $places = [];
    /**
     * @var array<int>
     */
    protected array $previousGamesInARowMap = [];

    public function __construct(Batch|null $previous = null)
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

    public function getNext(): Batch|null
    {
        return $this->next;
    }

    public function createNext(): Batch
    {
        $this->next = new self($this);
        $this->next->setPreviousGamesInARow($this->previousGamesInARowMap, $this->createMapGamesInARowMap());
        return $this->next;
    }

    /**
     * @return array<int>
     */
    protected function createMapGamesInARowMap(): array
    {
        $map = [];
        foreach ($this->places as $place) {
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

    public function getPrevious(): Batch|null
    {
        return $this->previous;
    }

    public function getFirst(): Batch
    {
        $previous = $this->getPrevious();
        return $previous !== null ? $previous->getFirst() : $this;
    }

    public function getLeaf(): Batch
    {
        $next = $this->getNext();
        return $next !== null ? $next->getLeaf() : $this;
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
     * @return void
     */
    public function setPreviousGamesInARow(array $previousPreviousGamesInARowMap, array $previousGamesInARowMap): void
    {
        $this->previousGamesInARowMap = $previousGamesInARowMap;
        foreach ($this->previousGamesInARowMap as $placeLocation => $nrOfGamesInARow) {
            if (!array_key_exists($placeLocation, $previousPreviousGamesInARowMap)) {
                continue;
            }
            $this->previousGamesInARowMap[$placeLocation] += $previousPreviousGamesInARowMap[$placeLocation];
        }
    }

    public function getPreviousGamesInARow(Place $place): int
    {
        if (!array_key_exists($place->getLocation(), $this->previousGamesInARowMap)) {
            return 0;
        }
        return $this->previousGamesInARowMap[$place->getLocation()];
    }

    /**
     * @param TogetherGame|AgainstGame $game
     *
     * @return void
     */
    public function add($game): void
    {
        $this->games[] = $game;
        foreach ($game->getPlaces() as $gamePlace) {
            $this->places[$gamePlace->getPlace()->getLocation()] = $gamePlace->getPlace();
        }
    }

    /**
     * @param TogetherGame|AgainstGame $game
     *
     * @return void
     */
    public function remove($game): void
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
     * @return array<Place>
     */
    protected function getPlaces(): array
    {
        return $this->places;
    }

    /**
     * @param Poule|null $poule
     * @return array<TogetherGame|AgainstGame>
     */
    public function getGames(Poule $poule = null): array
    {
        if ($poule === null) {
            return $this->games;
        }
        return array_filter($this->games, function ($game) use ($poule): bool {
            return $game->getPoule() === $poule;
        });
    }

    public function isParticipating(Place $place): bool
    {
        return array_key_exists($place->getLocation(), $this->places);
    }

    /**
     * @return array<TogetherGame|AgainstGame>
     */
    public function getAllGames(): array
    {
        $next = $this->getNext();
        if ($next === null) {
            return $this->getGames();
        }
        return array_merge($this->getGames(), $next->getAllGames());
    }

    /**
     * @return array<Poule>
     */
    public function getPoules(): array
    {
        $poules = [];
        foreach ($this->getGames() as $game) {
            if (array_search($game->getPoule(), $poules, true) === false) {
                $poules[] = $game->getPoule();
            }
        }
        return $poules;
    }
}
