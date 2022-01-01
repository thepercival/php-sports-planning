<?php

declare(strict_types=1);

namespace SportsPlanning\Seeker;

use SportsPlanning\Input;
use SportsPlanning\Planning;
use SportsPlanning\Planning\State as PlanningState;

class NextBatchGamesPlanningCalculator
{
    public function __construct(protected Input $input, protected int $maxTimeoutSeconds)
    {
    }

    /**
     * Haal lijst op met tobeprocessed en neem de middelste.
     *
     * @return Planning|null
     */
    public function next(): ?Planning
    {
        $stateValue = PlanningState::ToBeProcessed->value;
        if ($this->maxTimeoutSeconds > 0) {
            $stateValue = PlanningState::TimedOut->value + PlanningState::GreaterNrOfBatchesTimedOut->value;
        }
        $plannings = $this->getPlannings($stateValue);
        $middleIndex = (int)floor(count($plannings) / 2);
        /** @var array<string|int,Planning> $deletedPlannings */
        $deletedPlannings = array_splice($plannings, $middleIndex, 1);
        $firstDeletedPlanning = reset($deletedPlannings);
        if ($firstDeletedPlanning === false) {
            return null;
        }
        return $firstDeletedPlanning;
    }

    /**
     * @param int $stateValue
     * @return list<Planning>
     */
    protected function getPlannings(int $stateValue): array
    {
        $plannings = $this->input->getBatchGamesPlannings($stateValue);
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
