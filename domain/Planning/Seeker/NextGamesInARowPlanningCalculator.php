<?php

namespace SportsPlanning\Planning\Seeker;

use SportsPlanning\Input;
use SportsPlanning\Planning;

class NextGamesInARowPlanningCalculator
{
    protected Planning $batchGamePlanning;
    protected int $maxTimeoutSeconds;

    public function __construct( Planning $planning, int $maxTimeoutSeconds ) {
        $this->batchGamePlanning = $planning;
        $this->maxTimeoutSeconds = $maxTimeoutSeconds;
    }

    public function next(): ?Planning
    {
        $state = Planning::STATE_TOBEPROCESSED;
        if( $this->maxTimeoutSeconds > 0 ) {
            $state = Planning::STATE_TIMEDOUT + Planning::STATE_GREATER_GAMESINAROW_TIMEDOUT;
        }
        $plannings = $this->batchGamePlanning->getGamesInARowPlannings( $state );

        if( $this->maxTimeoutSeconds > 0 ) {
            $plannings = array_reverse( array_filter( $plannings, function( Planning $planningIt ): bool {
                return $planningIt->getTimeoutSeconds() <= $this->maxTimeoutSeconds;
            } ) );
        }
        $middleIndex = (int) floor( count($plannings) / 2 );
        $deletedPlannings = array_splice( $plannings, $middleIndex, 1 );
        $deletedPlanning = reset($deletedPlannings);
        if( $deletedPlanning === false ) {
            return null;
        }
        return $deletedPlanning;
    }
}
