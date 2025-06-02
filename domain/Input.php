<?php

declare(strict_types=1);

namespace SportsPlanning;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use SportsHelpers\Identifiable;
use SportsPlanning\Exceptions\NoBestPlanningException;
use SportsPlanning\Planning\Comparer;
use SportsPlanning\Planning\PlanningFilter as PlanningFilter;
use SportsPlanning\Planning\HistoricalBestPlanning;
use SportsPlanning\Planning\PlanningState;
use SportsPlanning\Planning\PlanningType as PlanningType;

class Input extends Identifiable
{
    protected DateTimeImmutable $createdAt;
    protected int $seekingPercentage = -1;

    /**
     * @var Collection<int|string, Planning>
     */
    protected Collection $plannings;
    /**
     * @var Collection<int|string, HistoricalBestPlanning>
     */
    protected Collection $historicalBestPlannings;
    public readonly string $configContent;

    public function __construct(public readonly PlanningConfiguration $configuration) {

        $this->plannings = new ArrayCollection();
        $this->historicalBestPlannings = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();

        $configContent = json_encode($this->configuration);
        if( $configContent === false) {
            throw new \Exception("invalid json for planningconfiguration");
        }
        $this->configContent = $configContent;
    }

    /**
     * @return Collection<int|string, Planning>
     */
    public function getPlannings(): Collection
    {
        return $this->plannings;
    }

    /**
     * @param PlanningFilter|null $filter
     * @return list<Planning>
     */
    public function getFilteredPlannings(PlanningFilter|null $filter = null): array
    {
        if( $filter === null ) {
            return array_values( $this->plannings->toArray() );
        }
        $filtered = $this->plannings->filter( function(Planning $planning) use($filter):  bool {
            return $filter->equals($planning);
        })->toArray();
        uasort($filtered, function (Planning $first, Planning $second) {
            if ($first->maxNrOfBatchGames === $second->maxNrOfBatchGames) {
                return $second->minNrOfBatchGames - $first->minNrOfBatchGames;
            }
            return $second->maxNrOfBatchGames - $first->maxNrOfBatchGames;
        });

        return array_values($filtered);
    }

    public function getPlanning(PlanningFilter $filter): Planning|null
    {
        $plannings = $this->getFilteredPlannings($filter);
        $planning = reset($plannings);
        return $planning === false ? null : $planning;
    }

    public function getBestPlanning(PlanningType|null $type): Planning
    {
        $filter = new PlanningFilter(
            $type, PlanningState::Succeeded, null, null
        );
        $succeededPlannings = $this->getFilteredPlannings($filter);

        uasort($succeededPlannings, function (Planning|HistoricalBestPlanning $first, Planning|HistoricalBestPlanning $second): int {
            return (new Comparer())->compare($first, $second);
        });
        $bestPlanning = array_shift($succeededPlannings);
        if ($bestPlanning === null) {
            throw new NoBestPlanningException($this, $type);
        }
        return $bestPlanning;
    }

    /**
     * @return Collection<int|string, HistoricalBestPlanning>
     */
    public function getHistoricalBestPlannings(): Collection
    {
        return $this->historicalBestPlannings;
    }

    public function getHistoricalVeryBestPlanning(): HistoricalBestPlanning|null
    {
        $historicalBestPlannings = $this->getHistoricalBestPlannings()->toArray();
        uasort($historicalBestPlannings, function (Planning|HistoricalBestPlanning $first, Planning|HistoricalBestPlanning $second): int {
            return (new Comparer())->compare($first, $second);
        });
        return array_shift($historicalBestPlannings);
    }

    /**
     * @param Planning $planning
     * @param PlanningState|null $state
     * @return list<Planning>
     */
    public function getGamesInARowPlannings(Planning $planning, PlanningState|null $state = null): array
    {
        if ($planning->maxNrOfGamesInARow > 0) {
            return [];
        }
        $range = $planning->getNrOfBatchGames();
        $gamesInARowPlannings = array_values( array_filter( $this->getPlannings()->toArray(),
            function (Planning $planning) use ($range, $state): bool {
                return $planning->minNrOfBatchGames === $range->getMin()
                    && $planning->maxNrOfBatchGames === $range->getMax()
                    && $planning->maxNrOfGamesInARow > 0
                    && ($state === null || (($planning->getState()->value & $state->value) > 0));
            }
        ) );
        return $this->orderGamesInARowPlannings($gamesInARowPlannings);
    }

    /**
     * @param list<Planning> $gamesInARowPlannings
     * @return list<Planning>
     */
    protected function orderGamesInARowPlannings(array $gamesInARowPlannings): array
    {
        uasort($gamesInARowPlannings, function (Planning $first, Planning $second) {
            if ($first->maxNrOfGamesInARow === $second->maxNrOfGamesInARow) {
                return $first->getNrOfBatchGames()->difference() > $second->getNrOfBatchGames()->difference() ? -1 : 1;
            }
            return $first->maxNrOfGamesInARow - $second->maxNrOfGamesInARow;
        });
        return array_values($gamesInARowPlannings);
    }

    public function getBestGamesInARowPlanning(Planning $plannning): Planning|null
    {
        $succeededGamesInARowPlannings = $this->getGamesInARowPlannings($plannning, PlanningState::Succeeded);
        return array_shift($succeededGamesInARowPlannings);
    }


//    public function getSelfRefereeInfo(): SelfRefereeInfo
//    {
//        return new SelfRefereeInfo($this->selfReferee, $this->nrOfSimSelfRefs);
//    }

//    public function getRefereeInfo(): PlanningRefereeInfo
//    {
//        if( $this->selfReferee === SelfReferee::Disabled ) {
//            $selfRefereeInfoOrNrOfReferees = count($this->getReferees());
//        } else {
//            $selfRefereeInfoOrNrOfReferees = $this->getSelfRefereeInfo();
//        }
//        return new PlanningRefereeInfo($selfRefereeInfoOrNrOfReferees);
//    }

    public function getSeekingPercentage(): int|null
    {
        return $this->seekingPercentage;
    }

    public function setSeekingPercentage(int $seekingPercentage): void
    {
        $this->seekingPercentage = $seekingPercentage;
    }
}
