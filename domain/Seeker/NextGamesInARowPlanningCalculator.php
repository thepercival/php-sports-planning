<?php
declare(strict_types=1);

namespace SportsPlanning\Seeker;

use SportsPlanning\Planning;
use SportsPlanning\Planning\State as PlanningState;

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
        $stateValue = PlanningState::ToBeProcessed->value;
        if ($this->maxTimeoutSeconds > 0) {
            $stateValue = PlanningState::TimedOut->value + PlanningState::GreaterNrOfGamesInARowTimedOut->value;
        }
        $plannings = $this->getPlannings($stateValue);
        $middleIndex = (int)floor(count($plannings) / 2);
        /** @var array<string|int,Planning> $deletedPlannings */
        $deletedPlannings = array_splice($plannings, $middleIndex, 1);
        $deletedPlanning = reset($deletedPlannings);
        if ($deletedPlanning === false) {
            return null;
        }
        return $deletedPlanning;
    }

    /**
     * @param int $stateValue
     * @return list<Planning>
     */
    protected function getPlannings(int $stateValue): array
    {
        $plannings = $this->batchGamePlanning->getGamesInARowPlannings($stateValue);
        if ($this->maxTimeoutSeconds === 0) {
            return $plannings;
        }
        $plannings = array_reverse(
            array_filter($plannings, function (Planning $planningIt): bool {
                return $planningIt->getTimeoutSeconds() <= $this->maxTimeoutSeconds;
            })
        );
        return array_values($plannings);
    }
}
