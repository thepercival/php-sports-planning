<?php

namespace SportsPlanning\Combinations;

use SportsPlanning\Combinations\Amounts\AmountCalculator as AmountCalculator;
use SportsPlanning\Combinations\Amounts\AmountRange as AmountRange;
use SportsPlanning\Combinations\PlaceNrCounterMap as PlaceCounterMapBase;
use SportsPlanning\Place;

final class RangedPlaceNrCounterMap
{
    // private int|null $shortage = null;
    // private bool|null $overAssigned = null;
    private readonly PlaceCounterMapBase $map;
    private int|null $nrOfPlacesBelowMinimum = null;
    private int|null $nrOfPlacesAboveMaximum = null;
    private readonly AmountRange $allowedRange;

    public function __construct(PlaceCounterMapBase $map, AmountRange $allowedRange) {
        $this->map = $map;
        $this->allowedRange = $allowedRange;
    }

    public function getAllowedRange(): AmountRange {
        return $this->allowedRange;
    }

    public function getMap(): PlaceCounterMapBase {
        return $this->map;
    }

    public function addPlaceNr(int $placeNr): self {

        return new self($this->map->addPlaceNr($placeNr), $this->allowedRange );
    }

    public function removePlaceNr(int $placeNr): self {

        return new self($this->map->removePlaceNr($placeNr), $this->allowedRange);
    }

    public function getNrOfPlacesBelowMinimum(): int
    {
        if( $this->nrOfPlacesBelowMinimum === null) {
            $calculator = new AmountCalculator($this->getMap()->count(), $this->allowedRange);
            $this->nrOfPlacesBelowMinimum = $calculator->countBeneathMinimum( $this->map->getAmountMap() );
        }
        return $this->nrOfPlacesBelowMinimum;
    }

    public function getNrOfPlacesAboveMaximum(): int
    {
        if( $this->nrOfPlacesAboveMaximum === null) {
            $calculator = new AmountCalculator($this->getMap()->count(), $this->allowedRange);
            $this->nrOfPlacesAboveMaximum = $calculator->countAboveMaximum( $this->map->getAmountMap() );
        }
        return $this->nrOfPlacesAboveMaximum;
    }



    public function count(int $placeNr): int
    {
        return $this->map->count($placeNr);
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
        if( $this->getNrOfPlacesBelowMinimum() <= $nrOfCombinationsToGo ) {
            return true;
        };

        $allowedMin = $this->allowedRange->getMin();
        $nrOfPossibleCombinations = $this->getMap()->count();

        if ( $this->getMinAmount() === $allowedMin->amount
            && $this->getCountOfMinAmount() + $nrOfCombinationsToGo <= $nrOfPossibleCombinations
        ) {
            return true;
        }
        return false;
    }

    public function aboveMaximum(int $nrOfCombinationsToGo): bool
    {
        if( $this->getNrOfPlacesAboveMaximum() === 0 ) {
            return false;
        }

        $allowedMax = $this->allowedRange->getMax();
        $nrOfPossibleCombinations = $this->getMap()->count();

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