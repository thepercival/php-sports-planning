<?php
declare(strict_types=1);

namespace SportsPlanning\Planning\Seeker;

use SportsPlanning\Planning;

class NextGamesInARowPlanningCalculator
{
    protected Planning $batchGamePlanning;
    protected int $maxTimeoutSeconds;

    public function __construct(Planning $planning, int $maxTimeoutSeconds)
    {
        $this->batchGamePlanning = $planning;
        $this->maxTimeoutSeconds = $maxTimeoutSeconds;
    }

    public function next(): ?Planning
    {
        $state = Planning::STATE_TOBEPROCESSED;
        if ($this->maxTimeoutSeconds > 0) {
            $state = Planning::STATE_TIMEDOUT + Planning::STATE_GREATER_GAMESINAROW_TIMEDOUT;
        }
        $plannings = $this->getPlannings($state);
        $middleIndex = (int) floor(count($plannings) / 2);
        /** @var array<string|int,Planning> $deletedPlannings */
        $deletedPlannings = array_splice($plannings, $middleIndex, 1);
        $deletedPlanning = reset($deletedPlannings);
        if ($deletedPlanning === false) {
            return null;
        }
        return $deletedPlanning;
    }

    /**
     * @param int $state
     * @return list<Planning>
     */
    protected function getPlannings(int $state): array
    {
        $plannings = $this->batchGamePlanning->getGamesInARowPlannings($state);
        if ($this->maxTimeoutSeconds === 0) {
            return $plannings;
        }
        $plannings = array_reverse(array_filter($plannings, function (Planning $planningIt): bool {
            return $planningIt->getTimeoutSeconds() <= $this->maxTimeoutSeconds;
        }));
        return array_values($plannings);
    }
}
