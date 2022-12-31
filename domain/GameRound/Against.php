<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound;

use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\GameRound;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\Planning\ListNode;

/**
 * @template-extends ListNode<Against>
 */
class Against extends ListNode
{
    use GameRound;

    /**
     * @var list<HomeAway>
     */
    protected array $homeAways = [];

    public function __construct(Against|null $previous = null)
    {
        parent::__construct($previous);
    }

    public function createNext(): Against
    {
        $this->next = new Against($this);
        return $this->next;
    }

    public function add(HomeAway $homeAway): void
    {
        $this->homeAways[] = $homeAway;
        foreach ($homeAway->getPlaces() as $place) {
            $this->placeMap[$place->getLocation()] = $place;
        }
    }

    public function remove(HomeAway $homeAway): void
    {
        $index = array_search($homeAway, $this->homeAways, true);
        if ($index !== false) {
            array_splice($this->homeAways, $index, 1);
        }
        foreach ($homeAway->getPlaces() as $place) {
            unset($this->placeMap[$place->getLocation()]);
        }
    }

    public function reverseSidesOfHomeAway(HomeAway $reversedHomeAway): bool
    {
        foreach( $this->homeAways as $needle => $homeAwayIt) {
            if( $homeAwayIt->equals($reversedHomeAway) ) {
                array_splice($this->homeAways, $needle, 1, [$reversedHomeAway]);
                return true;
            }
        }
        return false;
    }


    /**
     * @param bool $swap
     * @return list<HomeAway>
     */
    public function getHomeAways(bool $swap = false): array
    {
        if ($swap === false) {
            return $this->homeAways;
        }
        return array_map(fn(HomeAway $homeAway) => $homeAway->swap(), $this->homeAways);
    }

    public function isHomeAwayPlaceParticipating(HomeAway $homeAway): bool
    {
        foreach ($homeAway->getPlaces() as $place) {
            if ($this->isParticipating($place)) {
                return true;
            }
        }
        return false;
    }

    public function getNrOfHomeAwaysRecursive(): int {
        $previous = $this->getPrevious();
        if( $previous !== null ) {
            return count($this->getHomeAways()) + $previous->getNrOfHomeAwaysRecursive();
        }
        return count($this->getHomeAways());
    }

    /**
     * @return list<HomeAway>
     */
    public function getAllHomeAways(): array
    {
        $homeAways = [];
        $gameRound = $this->getFirst();
        while ($gameRound) {
            foreach ($gameRound->getHomeAways() as $homeAway) {
                array_push($homeAways, $homeAway);
            }
            $gameRound = $gameRound->getNext();
        }
        return $homeAways;
    }
}
