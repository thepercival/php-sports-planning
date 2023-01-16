<?php

namespace SportsPlanning\Combinations\PlaceCombinationCounterMap;

use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Combinations\Amount;
use SportsPlanning\Combinations\Amount\Calculator as AmountCalculator;
use SportsPlanning\Combinations\PlaceCombinationCounterMap;
use SportsPlanning\Combinations\PlaceCombinationCounterMap as PlaceCombinationCounterMapBase;

class Ranged
{
    // private int|null $shortage = null;
    // private bool|null $overAssigned = null;
    private readonly PlaceCombinationCounterMapBase $map;
    private int|null $nrOfPlaceCombinationsBelowMinimum = null;
    private int|null $nrOfPlaceCombinationsAboveMaximum = null;
    private readonly AmountRange $amountRange;

    public function __construct( PlaceCombinationCounterMapBase $map, AmountRange $amountRange) {
        $this->map = $map;
        $this->amountRange = $amountRange;
    }

    public function getRange(): AmountRange {
        return $this->amountRange;
    }

    public function getMap(): PlaceCombinationCounterMap {
        return $this->map;
    }

    public function addPlaceCombination(PlaceCombination $placeCombination): self {

        return new self($this->map->addPlaceCombination($placeCombination), $this->amountRange );
    }

    public function removePlaceCombination(PlaceCombination $placeCombination): self {

        return new self($this->map->removePlaceCombination($placeCombination), $this->amountRange);
    }

//    public function getMaxShortage(): int
//    {
//        $nrOfPlaceCombinations = count($this->map->getList());
//        $shortage = $nrOfPlaceCombinations * $this->minimum;
//        $shortage += $this->minNrToAssignToMinimum;
//        return $shortage;
//    }

//    public function getShortage(): int
//    {
//        if( $this->shortage === null) {
//            $this->shortage = 0;
//            $this->overAssigned = false;
//            $nrOfAmountLessThanMinimum = 0;
//            foreach( $this->map->getPerAmount() as $amount => $counters ) {
//                $nrOfAmount = count($counters);
//                if( $amount < $this->minimum) {
//                    $this->shortage += (int)($nrOfAmount * ($this->minimum - $amount));
//                }
//                if( $amount < $this->minimum) {
//                    $nrOfAmountLessThanMinimum += $nrOfAmount;
//                }
//                if( $amount === $this->maximum && $nrOfAmount > $this->maxNrToAssignToMaximum ) {
//                    $this->overAssigned = true;
//                }
//                if( $amount > $this->maximum ) {
//                    $this->overAssigned = true;
//                }
//            }
//            if( $nrOfAmountLessThanMinimum < $this->minNrToAssignToMinimum) {
//                $this->shortage += $this->minNrToAssignToMinimum - $nrOfAmountLessThanMinimum;
//            }
//        }
//        return $this->shortage;
//    }

    public function getNrOfPlaceCombinationsBelowMinimum(): int
    {
        if( $this->nrOfPlaceCombinationsBelowMinimum === null) {
            $calculator = new AmountCalculator(count($this->getMap()->getList()), $this->amountRange);
            $this->nrOfPlaceCombinationsBelowMinimum = $calculator->countBeneathMinimum( $this->map->getAmountMap() );
        }
        return $this->nrOfPlaceCombinationsBelowMinimum;
    }

    public function getNrOfPlaceCombinationsAboveMaximum(): int
    {
        if( $this->nrOfPlaceCombinationsAboveMaximum === null) {
            $calculator = new AmountCalculator(count($this->getMap()->getList()), $this->amountRange);
            $this->nrOfPlaceCombinationsAboveMaximum = $calculator->countAboveMaximum( $this->map->getAmountMap() );
        }
        return $this->nrOfPlaceCombinationsAboveMaximum;
    }



    public function count(PlaceCombination $placeCombination): int
    {
        return $this->map->count($placeCombination);
    }

    public function getAmountDifference(): int
    {
        return $this->map->getAmountDifference();
    }

    public function getMaxAmount(): int
    {
        return $this->map->getMaxAmount();
    }

    public function withinRange(int $nrOfCombinationsToGo): bool
    {
        return $this->minimumCanBeReached($nrOfCombinationsToGo) && $this->getNrOfPlaceCombinationsAboveMaximum() === 0;
    }

    public function minimumCanBeReached(int $nrOfCombinationsToGo): bool
    {
        return $this->getNrOfPlaceCombinationsBelowMinimum() <= $nrOfCombinationsToGo;
    }

}