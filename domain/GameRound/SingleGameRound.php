<?php
declare(strict_types=1);

namespace SportsPlanning\GameRound;

use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\GameRound;
use SportsPlanning\Planning\ListNode;

/**
 * @template-extends ListNode<SingleGameRound>
 */
class SingleGameRound extends ListNode
{
    use GameRound;
    /**
     * @var list<PlaceCombination>
     */
    protected array $placeCombinations = [];

    public function __construct(SingleGameRound|null $previous = null)
    {
        parent::__construct($previous);
    }

    public function createNext(): SingleGameRound
    {
        $this->next = new SingleGameRound($this);
        return $this->next;
    }

    public function add(PlaceCombination $placeCombination): void
    {
        $this->placeCombinations[] = $placeCombination;
        foreach ($placeCombination->getPlaces() as $place) {
            $this->placeMap[$place->getLocation()] = $place;
        }
    }

    public function remove(PlaceCombination $placeCombination): void
    {
        $index = array_search($placeCombination, $this->placeCombinations, true);
        if ($index !== false) {
            array_splice($this->placeCombinations, $index, 1);
        }
        foreach ($placeCombination->getPlaces() as $place) {
            unset($this->placeMap[$place->getLocation()]);
        }
    }

    /**
     * @return list<PlaceCombination>
     */
    public function getPlaceCombinations(): array
    {
        return $this->placeCombinations;
    }
}

