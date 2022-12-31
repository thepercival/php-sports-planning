<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use Psr\Log\LoggerInterface;

class PlaceCombinationCounterMap
{
    /**
     * @var array<string, PlaceCombinationCounter>
     */
    private array $map;

    /**
     * @param list<PlaceCombinationCounter> $placeCombinationCounters
     */
    public function __construct(
        array $placeCombinationCounters
    )
    {
        $this->map = [];
        foreach( $placeCombinationCounters as $placeCombinationCounter) {
            $this->map[$placeCombinationCounter->getIndex()] = $placeCombinationCounter;
        }
    }

    public function getPlaceCombination(string $index): PlaceCombination
    {
        return $this->map[$index]->getPlaceCombination();
    }

    public function count(PlaceCombination $placeCombination): int
    {
        if( !array_key_exists($placeCombination->getIndex(), $this->map) ) {
            return 0;
        }
        return $this->map[$placeCombination->getIndex()]->count();
    }

    /**
     * @return list<PlaceCombinationCounter>
     */
    public function getPlaceCombinationCounters(): array
    {
        return array_values($this->map);
    }

    public function addPlaceCombination(PlaceCombination $placeCombination): self {

        $newCounter = $this->map[$placeCombination->getIndex()]->increment2();
        $map = $this->map;
        $map[$placeCombination->getIndex()] = $newCounter;


        return new self(
            array_values($map)
        );
    }

    public function removePlaceCombination(PlaceCombination $placeCombination): self {

        $newCounter = $this->map[$placeCombination->getIndex()]->decrement();
        $map = $this->map;
        $map[$placeCombination->getIndex()] = $newCounter;

        return new self(
            array_values($map)
        );
    }

    /**
     * @return list<PlaceCombinationCounter>
     */
    public function getList(): array
    {
        return array_values($this->map);
    }

    public function getMaxDifference(): int {
        $perAmount = $this->getPerAmount();
        $min = 0;
        $max = 0;
        foreach( array_keys($perAmount) as $amount) {
            if( $min === 0 ) {
                $min = $amount;
            }
            $max = $amount;
        }
        return $max - $min;
    }

    public function getMinDifference(): int {
        return $this->canBeBalanced() ? 0 : 1;
    }

    protected function canBeBalanced(): bool {
        $totalCount = 0;
        foreach( $this->map as $placeCombinationCounter) {
            $totalCount += $placeCombinationCounter->count();
        }
        return ($totalCount % count($this->map)) === 0;
    }

    /**
     * @return array<int, list<PlaceCombinationCounter>>
     */
    public function getPerAmount(): array {
        $perAmount = [];
        foreach( $this->map as $combinationCounter) {
            if( !array_key_exists($combinationCounter->count(), $perAmount)) {
                $perAmount[$combinationCounter->count()] = [];
            }
            $perAmount[$combinationCounter->count()][] = $combinationCounter;
        }
        ksort($perAmount);
        return $perAmount;
    }


    public function output(LoggerInterface $logger, string $header): void {
        $logger->info($header);

        $amountPerLine = 4; $counter = 0; $line = '';
        foreach( $this->getPlaceCombinationCounters() as $counterIt ) {
            $line .= $counterIt->getPlaceCombination() . ' ' . $counterIt->count() . 'x, ';
            if( ++$counter === $amountPerLine ) {
                $logger->info('    ' . $line);
                $counter = 0;
                $line = '';
            }
        }
        if( strlen($line) > 0 ) {
            $logger->info('    ' . $line);
        }
    }

}
