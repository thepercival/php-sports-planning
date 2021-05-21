<?php
declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Place;
use SportsPlanning\Planning\ListNode;
use SportsPlanning\Poule;

/**
 * @template-extends ListNode<GameRound>
 */
class GameRound extends ListNode
{
    /**
     * @var list<AgainstHomeAway>
     */
    protected array $homeAways = [];
    /**
     * @var array<int|string,Place>
     */
    protected array $placeMap = [];
    /**
     * @var array<string,int>
     */
    protected array $previousGamesInARowMap = [];

    public function __construct(GameRound|null $previous = null)
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

    public function createNext(): GameRound
    {
        $this->next = new GameRound($this);
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

    public function add(AgainstHomeAway $homeAway): void
    {
        $this->homeAways[] = $homeAway;
        foreach ($homeAway->getPlaces() as $place) {
            $this->placeMap[$place->getLocation()] = $place;
        }
    }

    public function remove(AgainstHomeAway $homeAway): void
    {
        $index = array_search($homeAway, $this->homeAways, true);
        if ($index !== false) {
            array_splice($this->homeAways, $index, 1);
        }
        foreach ($homeAway->getPlaces() as $place) {
            unset($this->placeMap[$place->getLocation()]);
        }
    }

    /**
     * @return array<int|string,Place>
     */
    protected function getPlaceMap(): array
    {
        return $this->placeMap;
    }

//    /**
//     * @param Poule|null $poule
//     * @return list<TogetherGame|AgainstGame>
//     */
//    public function getGames(Poule $poule = null): array
//    {
//        if ($poule === null) {
//            return $this->games;
//        }
//        $games = array_filter($this->games, function (TogetherGame|AgainstGame $game) use ($poule): bool {
//            return $game->getPoule() === $poule;
//        });
//        return array_values($games);
//    }

    public function isParticipating(Place $place): bool
    {
        return array_key_exists($place->getLocation(), $this->placeMap);
    }

    /**
     * @param bool $swap
     * @return list<AgainstHomeAway>
     */
    public function getHomeAways(bool $swap = false): array
    {
        if ($swap === false) {
            return $this->homeAways;
        }
        return array_map(fn (AgainstHomeAway $homeAway) => $homeAway->swap(), $this->homeAways);
    }
//    /**
//     * @return list<TogetherGame|AgainstGame>
//     */
//    public function getAllGames(): array
//    {
//        $next = $this->getNext();
//        if ($next === null) {
//            return $this->getGames();
//        }
//        return array_merge($this->getGames(), $next->getAllGames());
//    }
}
