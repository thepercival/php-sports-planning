<?php

namespace SportsPlanning\Planning\Seeker;

use SportsPlanning\Input;
use SportsPlanning\Planning;

class NextBatchGamesPlanningCalculator
{
    protected Input $input;
    protected int $maxTimeoutSeconds;

    public function __construct( Input $input, int $maxTimeoutSeconds ) {
        $this->input = $input;
        $this->maxTimeoutSeconds = $maxTimeoutSeconds;
    }

    /**
     * Haal lijst op met tobeprocessed en neem de middelste.
     *
     * @return Planning|null
     */
    public function next(): ?Planning
    {
        $state = Planning::STATE_TOBEPROCESSED;
        if( $this->maxTimeoutSeconds > 0 ) {
            $state = Planning::STATE_TIMEDOUT + Planning::STATE_GREATER_NROFBATCHES_TIMEDOUT;
        }
        $plannings = $this->input->getBatchGamesPlannings( $state );
        if( $this->maxTimeoutSeconds > 0 ) {
            $plannings = array_reverse( array_filter( $plannings, function( Planning $planningIt ): bool {
                return $planningIt->getTimeoutSeconds() <= $this->maxTimeoutSeconds;
            } ) );
        }
        $middleIndex = (int) floor( count($plannings) / 2 );
        $deletedPlannings = array_splice( $plannings, $middleIndex, 1 );
        $firstDeletedPlanning = reset($deletedPlannings);
        if( $firstDeletedPlanning === false ) {
            return null;
        }
        return $firstDeletedPlanning;
    }
}
