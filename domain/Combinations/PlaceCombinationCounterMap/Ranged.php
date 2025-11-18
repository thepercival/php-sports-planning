<?php

namespace SportsPlanning\Combinations\PlaceCombinationCounterMap;

use SportsPlanning\Combinations\Amount;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Combinations\Amount\Calculator as AmountCalculator;
use SportsPlanning\Combinations\PlaceCombinationCounterMap;
use SportsPlanning\Combinations\PlaceCombinationCounterMap as PlaceCombinationCounterMapBase;

final class Ranged
{
    // private int|null $shortage = null;
    // private bool|null $overAssigned = null;
    private readonly PlaceCombinationCounterMapBase $map;
    private int|null $nrOfPlaceCombinationsBelowMinimum = null;
    private int|null $nrOfPlaceCombinationsAboveMaximum = null;
    private readonly AmountRange $allowedRange;

    public function __construct( PlaceCombinationCounterMapBase $map, AmountRange $allowedRange) {
        $this->map = $map;
        $this->allowedRange = $allowedRange;
    }

    public function getAllowedRange(): AmountRange {
        return $this->allowedRange;
    }

    public function getMap(): PlaceCombinationCounterMap {
        return $this->map;
    }

    public function addPlaceCombination(PlaceCombination $placeCombination): self {

        return new self($this->map->addPlaceCombination($placeCombination), $this->allowedRange );
    }

    public function removePlaceCombination(PlaceCombination $placeCombination): self {

        return new self($this->map->removePlaceCombination($placeCombination), $this->allowedRange);
    }

    public function getNrOfPlaceCombinationsBelowMinimum(): int
    {
        if( $this->nrOfPlaceCombinationsBelowMinimum === null) {
            $calculator = new AmountCalculator(count($this->getMap()->getList()), $this->allowedRange);
            $this->nrOfPlaceCombinationsBelowMinimum = $calculator->countBeneathMinimum( $this->map->getAmountMap() );
        }
        return $this->nrOfPlaceCombinationsBelowMinimum;
    }

    public function getNrOfPlaceCombinationsAboveMaximum(): int
    {
        if( $this->nrOfPlaceCombinationsAboveMaximum === null) {
            $calculator = new AmountCalculator(count($this->getMap()->getList()), $this->allowedRange);
            $this->nrOfPlaceCombinationsAboveMaximum = $calculator->countAboveMaximum( $this->map->getAmountMap() );
        }
        return $this->nrOfPlaceCombinationsAboveMaximum;
    }



    public function count(PlaceCombination $placeCombination): int
    {
        return $this->map->count($placeCombination);
    }

    public function countAmount(int $amount): int {
        $amountMap = $this->map->getAmountMap();
        return array_key_exists($amount, $amountMap) ? $amountMap[$amount]->count : 0;
    }

    public function getAmountDifference(): int
    {
        return $this->map->getAmountDifference();
    }

    public function getRange(): AmountRange|null
    {
        return $this->map->getRange();
    }

    public function getMinAmount(): int
    {
        return $this->map->getMinAmount();
    }


    public function getCountOfMinAmount(): int
    {
        return $this->map->getCountOfMinAmount();
    }

    public function getMaxAmount(): int
    {
        return $this->map->getMaxAmount();
    }

    public function getCountOfMaxAmount(): int
    {
        return $this->map->getCountOfMaxAmount();
    }

    public function withinRange(int $nrOfCombinationsToGo): bool
    {
        return $this->minimumCanBeReached($nrOfCombinationsToGo) && !$this->aboveMaximum($nrOfCombinationsToGo);
    }

    public function minimumCanBeReached(int $nrOfCombinationsToGo): bool
    {
        if( $this->getNrOfPlaceCombinationsBelowMinimum() <= $nrOfCombinationsToGo ) {
            return true;
        };

        $allowedMin = $this->allowedRange->getMin();
        $nrOfPossibleCombinations = count( $this->getMap()->getList() );

        if ( $this->getMinAmount() === $allowedMin->amount
            && $this->getCountOfMinAmount() + $nrOfCombinationsToGo <= $nrOfPossibleCombinations
        ) {
            return true;
        }
        return false;
    }

    public function aboveMaximum(int $nrOfCombinationsToGo): bool
    {
        if( $this->getNrOfPlaceCombinationsAboveMaximum() === 0 ) {
            return false;
        }

        $allowedMax = $this->allowedRange->getMax();
        $nrOfPossibleCombinations = count( $this->getMap()->getList() );

        if ( $this->getMaxAmount() === $allowedMax->amount
            &&
            (
            $this->getCountOfMaxAmount() + $nrOfCombinationsToGo <= $nrOfPossibleCombinations
            )
        ) {
            return false;
        }
        return true;
    }

}