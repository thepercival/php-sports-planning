<?php

namespace SportsPlanning\Exceptions;

use SportsPlanning\PlanningOrchestration;
use SportsPlanning\Planning\PlanningState;
use SportsPlanning\Planning\PlanningType;
final class NoBestPlanningException extends \Exception
{
    public function __construct(PlanningOrchestration $orchestration, PlanningType|null $planningType) {
        $msg = 'no planning with state "' . PlanningState::Succeeded->value . '"';
        if( $planningType !== null) {
            $msg .= ' and type "' . $planningType->value . '"';
        }
        $configurationJson = json_encode($orchestration->configuration);
        $configurationJson = $configurationJson === false ? '?' : $configurationJson;
        $msg .= ' found for config "' . $configurationJson . '"';
        parent::__construct( $msg, E_ERROR );
    }
}