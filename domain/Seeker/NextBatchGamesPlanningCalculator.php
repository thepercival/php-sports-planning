<?php
declare(strict_types=1);

namespace SportsPlanning\Seeker;

use SportsPlanning\Input;
use SportsPlanning\Planning;

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
        $state = Planning::STATE_TOBEPROCESSED;
        if ($this->maxTimeoutSeconds > 0) {
            $state = Planning::STATE_TIMEDOUT + Planning::STATE_GREATER_NROFBATCHES_TIMEDOUT;
        }
        $plannings = $this->getPlannings($state);
        $middleIndex = (int) floor(count($plannings) / 2);
        /** @var array<string|int,Planning> $deletedPlannings */
        $deletedPlannings = array_splice($plannings, $middleIndex, 1);
        $firstDeletedPlanning = reset($deletedPlannings);
        if ($firstDeletedPlanning === false) {
            return null;
        }
        return $firstDeletedPlanning;
    }

    /**
     * @param int $state
     * @return list<Planning>
     */
    protected function getPlannings(int $state): array
    {
        $plannings = $this->input->getBatchGamesPlannings($state);
        if ($this->maxTimeoutSeconds === 0) {
            return $plannings;
        }
        $plannings = array_reverse(array_filter($plannings, function (Planning $planningIt): bool {
            return $planningIt->getTimeoutSeconds() <= $this->maxTimeoutSeconds;
        }));
        return array_values($plannings);
    }
}
