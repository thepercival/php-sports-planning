<?php

declare(strict_types=1);

namespace SportsPlanning;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use SportsHelpers\Identifiable;
use SportsHelpers\SportRange;
use SportsPlanning\Exceptions\NoBestPlanningException;
use SportsPlanning\Planning\Comparer;
use SportsPlanning\Planning\Filter as PlanningFilter;
use SportsPlanning\Planning\HistoricalBestPlanning;
use SportsPlanning\Planning\PlanningState;
use SportsPlanning\Planning\Type as PlanningType;
use SportsPlanning\PlanningPouleStructure;
use SportsPlanning\Referee\PlanningRefereeInfo;

class Input extends Identifiable
{
    private const int MaxNrOfGamesInARow = 5;

    protected string $name;
    protected DateTimeImmutable $createdAt;
//    protected bool|null $hasBalancedStructure = null;
//    protected int $nrOfSimSelfRefs;
    protected int $seekingPercentage = -1;

    /**
     * @var Collection<int|string, Planning>
     */
    protected Collection $plannings;
    /**
     * @var Collection<int|string, HistoricalBestPlanning>
     */
    protected Collection $historicalBestPlannings;
    protected int|null $maxNrOfGamesInARow = null;

    public function __construct(public readonly PlanningConfiguration $configuration) {

        $this->plannings = new ArrayCollection();
        $this->historicalBestPlannings = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();

        $this->name = $configuration->getName();
    }

    public function caluclateBetterNrOfBatchGames(PlanningType $planningType, SportRange $batchGamesRange): int|null
    {
        try {
            if ($planningType === PlanningType::BatchGames) {
                // -1 because needs to be less nrOfBatches
                return $this->getBestPlanning(null)->getNrOfBatches() - 1;
            } else {
                $planningFilter = new PlanningFilter( null, null, $batchGamesRange, 0);
                $batchGamePlanning = $this->getPlanning($planningFilter);
                if ($batchGamePlanning !== null) {
                    return $batchGamePlanning->getNrOfBatches();
                }
            }
        } catch (NoBestPlanningException $e) {
        }
        return null;
    }


    public function getMaxNrOfBatchGames(): int
    {
        return $this->configuration->planningPouleStructure->getMaxNrOfGamesPerBatch();
    }

    public function getMaxNrOfGamesInARow(): int
    {
        if ($this->maxNrOfGamesInARow === null) {
            $this->maxNrOfGamesInARow = $this->configuration->planningPouleStructure->getMaxNrOfGamesInARow();
            if ($this->maxNrOfGamesInARow > self::MaxNrOfGamesInARow) {
                $this->maxNrOfGamesInARow = self::MaxNrOfGamesInARow;
            }
        }
        return $this->maxNrOfGamesInARow;
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
        if ($planning->getMaxNrOfGamesInARow() > 0) {
            return [];
        }
        $range = $planning->getNrOfBatchGames();
        $gamesInARowPlannings = array_values( array_filter( $this->getPlannings()->toArray(),
            function (Planning $planning) use ($range, $state): bool {
                return $planning->minNrOfBatchGames === $range->getMin()
                    && $planning->maxNrOfBatchGames === $range->getMax()
                    && $planning->getMaxNrOfGamesInARow() > 0
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
            if ($first->getMaxNrOfGamesInARow() === $second->getMaxNrOfGamesInARow()) {
                return $first->getNrOfBatchGames()->difference() > $second->getNrOfBatchGames()->difference() ? -1 : 1;
            }
            return $first->getMaxNrOfGamesInARow() - $second->getMaxNrOfGamesInARow();
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
