<?php

namespace SportsPlanning\Exceptions;

use SportsPlanning\Input;
use SportsPlanning\Planning\PlanningState;
use SportsPlanning\Planning\Type;
class NoBestPlanningException extends \Exception
{
    public function __construct(Input $input, Type|null $planningType) {
        $msg = 'no planning with state "' . PlanningState::Succeeded->value . '"';
        if( $planningType !== null) {
            $msg .= ' and type "' . $planningType->value . '"';
        }
        $msg .= ' found for input "' . $input->configuration->getName() . '"';
        parent::__construct( $msg, E_ERROR );
    }
}