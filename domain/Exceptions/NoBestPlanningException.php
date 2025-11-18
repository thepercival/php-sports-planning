<?php

namespace SportsPlanning\Exceptions;

use SportsPlanning\Input;
use SportsPlanning\Planning\State;
use SportsPlanning\Planning\Type;

final class NoBestPlanningException extends \Exception
{
    public function __construct(Input $input, Type|null $planningType) {
        $msg = 'no planning with state "' . State::Succeeded->value . '"';
        if( $planningType !== null) {
            $msg .= ' and type "' . $planningType->value . '"';
        }
        $msg .= ' found for input "' . $input->createConfiguration()->getName() . '"';
        parent::__construct( $msg, E_ERROR );
    }
}