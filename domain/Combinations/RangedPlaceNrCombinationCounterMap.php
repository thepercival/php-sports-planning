<?php

namespace SportsPlanning\Combinations;

use SportsPlanning\Combinations\Amounts\AmountCalculator as AmountCalculator;
use SportsPlanning\Combinations\Amounts\AmountRange as AmountRange;

final class RangedPlaceNrCombinationCounterMap
{
    // private int|null $shortage = null;
    // private bool|null $overAssigned = null;
    private readonly PlaceNrCombinationCounterMap $map;
    private int|null $nrOfPlaceNrCombinationsBelowMinimum = null;
    private int|null $nrOfPlaceNrCombinationsAboveMaximum = null;
    private readonly AmountRange $allowedRange;

    public function __construct(PlaceNrCombinationCounterMap $map, AmountRange $allowedRange) {
        $this->map = $map;
        $this->allowedRange = $allowedRange;
    }

    public function getAllowedRange(): AmountRange {
        return $this->allowedRange;
    }

    public function getMap(): PlaceNrCombinationCounterMap {
        return $this->map;
    }

    public function addPlaceNrCombination(PlaceNrCombination $placeNrCombination): self {

        return new self($this->map->addPlaceNrCombination($placeNrCombination), $this->allowedRange );
    }

    public function removePlaceNrCombination(PlaceNrCombination $placeNrCombination): self {

        return new self($this->map->removePlaceNrCombination($placeNrCombination), $this->allowedRange);
    }

    public function getNrOfPlaceNrCombinationsBelowMinimum(): int
    {
        if( $this->nrOfPlaceNrCombinationsBelowMinimum === null) {
            $calculator = new AmountCalculator(count($this->getMap()->getList()), $this->allowedRange);
            $this->nrOfPlaceNrCombinationsBelowMinimum = $calculator->countBeneathMinimum( $this->map->getAmountMap() );
        }
        return $this->nrOfPlaceNrCombinationsBelowMinimum;
    }

    public function getNrOfPlaceNrCombinationsAboveMaximum(): int
    {
        if( $this->nrOfPlaceNrCombinationsAboveMaximum === null) {
            $calculator = new AmountCalculator(count($this->getMap()->getList()), $this->allowedRange);
            $this->nrOfPlaceNrCombinationsAboveMaximum = $calculator->countAboveMaximum( $this->map->getAmountMap() );
        }
        return $this->nrOfPlaceNrCombinationsAboveMaximum;
    }



    public function count(PlaceNrCombination $placeNrCombination): int
    {
        return $this->map->count($placeNrCombination);
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
        if( $this->getNrOfPlaceNrCombinationsBelowMinimum() <= $nrOfCombinationsToGo ) {
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
        if( $this->getNrOfPlaceNrCombinationsAboveMaximum() === 0 ) {
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