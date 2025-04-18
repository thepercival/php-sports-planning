<?php

declare(strict_types=1);

namespace SportsPlanning\Sports\Plannable;

use Exception;
use SportsPlanning\Input;
use SportsPlanning\Sports\SportWithNrOfPlaces\SportWithNrOfPlacesInterface;

abstract class AgainstPlannableSport extends PlannableSport
{
    public function __construct(public readonly int $nrOfCycles, Input $input)
    {
        if( $this->nrOfCycles < 1 ) {
            throw new Exception('Nr of cycles must be greater than 1');
        }
        parent::__construct($input);
    }

//    public function __toString(): string
//    {
//        return $this->createVariant() . ' f(' . $this->getNrOfFields() . ')';
//    }
}
