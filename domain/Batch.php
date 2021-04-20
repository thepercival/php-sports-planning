<?php

namespace SportsPlanning;

use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;

class Batch
{
    protected int $number;
    protected Batch|null $previous;
    protected Batch|null $next = null;
    /**
     * @var list<TogetherGame|AgainstGame>
     */
    protected array $games = [];
    /**
     * @var array<int|string,Place>
     */
    protected array $placeMap = [];
    /**
     * @var array<string,int>
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
     * @return array<string,int>
     */
    protected function createMapGamesInARowMap(): array
    {
        $map = [];
        foreach ($this->placeMap as $place) {
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
     * @param array<string,int> $previousPreviousGamesInARowMap
     * @param array<string,int> $previousGamesInARowMap
     * @return void
     */
    public function setPreviousGamesInARow(array $previousPreviousGamesInARowMap, array $previousGamesInARowMap): void
    {
        $this->previousGamesInARowMap = $previousGamesInARowMap;
        foreach (array_keys($this->previousGamesInARowMap) as $placeLocation) {
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

    public function add(TogetherGame|AgainstGame $game): void
    {
        $this->games[] = $game;
        foreach ($game->getPlaces() as $gamePlace) {
            $this->placeMap[$gamePlace->getPlace()->getLocation()] = $gamePlace->getPlace();
        }
    }

    public function remove(TogetherGame|AgainstGame $game): void
    {
        $index = array_search($game, $this->games, true);
        if ($index !== false) {
            array_splice($this->games, $index, 1);
        }
        foreach ($game->getPlaces() as $gamePlace) {
            unset($this->placeMap[$gamePlace->getPlace()->getLocation()]);
        }
    }

    /**
     * @return array<int|string,Place>
     */
    protected function getPlaceMap(): array
    {
        return $this->placeMap;
    }

    /**
     * @param Poule|null $poule
     * @return list<TogetherGame|AgainstGame>
     */
    public function getGames(Poule $poule = null): array
    {
        if ($poule === null) {
            return $this->games;
        }
        $games = array_filter($this->games, function (TogetherGame|AgainstGame $game) use ($poule): bool {
            return $game->getPoule() === $poule;
        });
        return array_values($games);
    }

    public function isParticipating(Place $place): bool
    {
        return array_key_exists($place->getLocation(), $this->placeMap);
    }

    /**
     * @return list<TogetherGame|AgainstGame>
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
     * @return list<Poule>
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
