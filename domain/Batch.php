<?php
declare(strict_types=1);

namespace SportsPlanning;

use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Planning\ListNode;

/**
 * @template-extends ListNode<Batch>
 */
class Batch extends ListNode
{
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
        parent::__construct($previous);
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

    public function createNext(): Batch
    {
        $this->next = new Batch($this);
        $this->next->setPreviousGamesInARow($this->previousGamesInARowMap, $this->createMapGamesInARowMap());
        return $this->next;
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
