<?php
declare(strict_types=1);

namespace SportsPlanning\Combinations;

class MultipleCombinationsCounter
{
    /**
     * @var array<int, PlaceCombinationCounter>
     */
    protected array $counters;

    /**
     * @param list<PlaceCombination> $placeCombinations
     */
    public function __construct(array $placeCombinations)
    {
        $this->counters = [];
        foreach ($placeCombinations as $placeCombinationIt) {
            $this->counters[$placeCombinationIt->getNumber()] = new PlaceCombinationCounter($placeCombinationIt);
        }
    }

    public function addCombination(PlaceCombination $placeCombination): void
    {
        if (isset($this->counters[$placeCombination->getNumber()])) {
            $this->counters[$placeCombination->getNumber()]->increment();
        }
    }

    public function balanced(): bool
    {
        $count = null;
        foreach ($this->counters as $counter) {
            if ($count === null) {
                $count = $counter->count();
            }
            if ($count !== $counter->count()) {
                return false;
            }
        }
        return true;
    }

    public function totalCount(): int
    {
        $totalCount = 0;
        foreach ($this->counters as $counter) {
            $totalCount += $counter->count();
        }
        return $totalCount;
    }

    public function __toString(): string
    {
        $lines = '';
        foreach ($this->counters as $counter) {
            $lines .= $counter . PHP_EOL;
        }
        return $lines;
    }
}
