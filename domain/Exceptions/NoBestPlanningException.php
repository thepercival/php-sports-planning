<?php

namespace SportsPlanning\Exceptions;

use SportsPlanning\PlanningOrchestration;
use SportsPlanning\Planning\PlanningState;
use SportsPlanning\Planning\PlanningType;
class NoBestPlanningException extends \Exception
{
    public function __construct(PlanningOrchestration $input, PlanningType|null $planningType) {
        $msg = 'no planning with state "' . PlanningState::Succeeded->value . '"';
        if( $planningType !== null) {
            $msg .= ' and type "' . $planningType->value . '"';
        }
        $msg .= ' found for input "' . $input->configContent . '"';
        parent::__construct( $msg, E_ERROR );
    }
}