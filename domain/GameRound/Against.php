<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound;

use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\GameRound;
use SportsPlanning\Planning\ListNode;

/**
 * @template-extends ListNode<Against>
 */
class Against extends ListNode
{
    use GameRound;

    /**
     * @var list<AgainstHomeAway>
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
     * @param bool $swap
     * @return list<AgainstHomeAway>
     */
    public function getHomeAways(bool $swap = false): array
    {
        if ($swap === false) {
            return $this->homeAways;
        }
        return array_map(fn(AgainstHomeAway $homeAway) => $homeAway->swap(), $this->homeAways);
    }

    public function isHomeAwayPlaceParticipating(AgainstHomeAway $homeAway): bool
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
}
