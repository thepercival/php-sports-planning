<?php

declare(strict_types=1);

namespace SportsPlanning;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use SportsHelpers\Identifiable;
use SportsHelpers\SelfReferee;
use SportsHelpers\SportRange;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeOtherPouleBatch;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeSamePouleBatch;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Planning\State as PlanningState;

class Planning extends Identifiable
{
    protected int $minNrOfBatchGames;
    protected int $maxNrOfBatchGames;
    protected DateTimeImmutable $createdDateTime;
    protected int $timeoutSeconds;
    protected PlanningState $state;
    protected int $nrOfBatches = 0;
    protected int $validity = -1;
    /**
     * @var Collection<int|string, AgainstGame>
     */
    protected Collection $againstGames;
    /**
     * @psalm-var Collection<int|string, TogetherGame>
     */
    protected Collection $togetherGames;

    public const MINIMUM_TIMEOUTSECONDS = 5;
    private const MAXIMUM_TIMEOUTSECONDS = 15;

    public function __construct(protected Input $input, SportRange $nrOfBatchGames, protected int $maxNrOfGamesInARow)
    {
        $this->minNrOfBatchGames = $nrOfBatchGames->getMin();
        $this->maxNrOfBatchGames = $nrOfBatchGames->getMax();
        $this->input->getPlannings()->add($this);

        $this->againstGames = new ArrayCollection();
        $this->togetherGames = new ArrayCollection();

        $this->createdDateTime = new DateTimeImmutable();
        $this->timeoutSeconds = $this->getDefaultTimeoutSeconds();
        $this->state = PlanningState::ToBeProcessed;
    }

    public function minIsMaxNrOfBatchGames(): bool
    {
        return $this->getMinNrOfBatchGames() === $this->getMaxNrOfBatchGames();
    }

    public function getMinNrOfBatchGames(): int
    {
        return $this->minNrOfBatchGames;
    }

//    public function setMinNrOfBatchGames( int $minNrOfBatchGames ) {
//        $this->minNrOfBatchGames = $minNrOfBatchGames;
//    }

    public function getMaxNrOfBatchGames(): int
    {
        return $this->maxNrOfBatchGames;
    }

//    public function setMaxNrOfBatchGames( int $maxNrOfBatchGames ) {
//        $this->maxNrOfBatchGames = $maxNrOfBatchGames;
//    }

    public function getNrOfBatchGames(): SportRange
    {
        return new SportRange($this->getMinNrOfBatchGames(), $this->getMaxNrOfBatchGames());
    }

    public function getMaxNrOfGamesInARow(): int
    {
        return $this->maxNrOfGamesInARow;
    }


    public function isBatchGames(): bool
    {
        return $this->maxNrOfGamesInARow === 0;
    }


    public function getCreatedDateTime(): DateTimeImmutable
    {
        return $this->createdDateTime;
    }

    public function setCreatedDateTime(DateTimeImmutable $createdDateTime): void
    {
        $this->createdDateTime = $createdDateTime;
    }

    public function getTimeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }

    public function setTimeoutSeconds(int $timeoutSeconds): void
    {
        $this->timeoutSeconds = $timeoutSeconds;
    }

    protected function getDefaultTimeoutSeconds(): int
    {
        $sportVariants = array_values($this->input->createSportVariants()->toArray());
        $totalNrOfGames = $this->input->createPouleStructure()->getTotalNrOfGames($sportVariants);
        $nrOfGamesPerSecond = 10;
        $nrOfSeconds = (int)ceil($totalNrOfGames / $nrOfGamesPerSecond);
        if ($nrOfSeconds < self::MINIMUM_TIMEOUTSECONDS) {
            $nrOfSeconds = self::MINIMUM_TIMEOUTSECONDS;
        }
        if ($nrOfSeconds > self::MAXIMUM_TIMEOUTSECONDS) {
            $nrOfSeconds = self::MAXIMUM_TIMEOUTSECONDS;
        }
        return $nrOfSeconds;
    }

    public function getState(): PlanningState
    {
        return $this->state;
    }

    public function setState(PlanningState $state): void
    {
        $this->state = $state;
    }

    public function getNrOfBatches(): int
    {
        return $this->nrOfBatches;
    }

    public function setNrOfBatches(int $nrOfBatches): void
    {
        $this->nrOfBatches = $nrOfBatches;
    }

    public function getValidity(): int
    {
        return $this->validity;
    }

    public function setValidity(int $validity): void
    {
        $this->validity = $validity;
    }

    public function getInput(): Input
    {
        return $this->input;
    }

    public function createFirstBatch(): Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch
    {
        $games = $this->getGames(Game::ORDER_BY_BATCH);
        $batch = new Batch();
        if ($this->input->selfRefereeEnabled()) {
            if ($this->input->getSelfReferee() === SelfReferee::SamePoule) {
                $batch = new SelfRefereeSamePouleBatch($batch);
            } else {
                $poules = array_values($this->input->getPoules()->toArray());
                $batch = new SelfRefereeOtherPouleBatch($poules, $batch);
            }
        }
        foreach ($games as $game) {
            if ($game->getBatchNr() === ($batch->getNumber() + 1)) {
                $batch = $batch->createNext();
            }
            $batch->add($game);
        }
        return $batch->getFirst();
    }

    /**
     * @param int|null $order
     * @return list<AgainstGame|TogetherGame>
     */
    public function getGames(int|null $order = null): array
    {
        $games = [];
        foreach ($this->input->getPoules() as $poule) {
            $games = array_merge($games, $this->getGamesForPoule($poule));
        }
        if ($order === Game::ORDER_BY_BATCH) {
            uasort($games, function (Game $g1, Game  $g2): int {
                if ($g1->getBatchNr() === $g2->getBatchNr()) {
                    if ($g1->getField()->getUniqueIndex() === $g2->getField()->getUniqueIndex()) {
                        return 0;
                    }
                    return $g1->getField()->getUniqueIndex() < $g2->getField()->getUniqueIndex() ? -1 : 1;
                }
                return $g1->getBatchNr() - $g2->getBatchNr();
            });
        }
        return array_values($games);
    }

    /**
     * @return list<AgainstGame|TogetherGame>
     */
    public function getGamesForPoule(Poule $poule): array
    {
        $allGames = array_merge($this->getAgainstGames()->toArray(), $this->getTogetherGames()->toArray());
        return array_values(array_filter($allGames, fn ($game) => $game->getPoule() === $poule));
    }

    /**
     * @return Collection<int|string, AgainstGame>
     */
    public function getAgainstGames(): Collection
    {
        return $this->againstGames;
    }

    /**
     * @return list<AgainstGame>
     */
    public function getAgainstGamesForPoule(Poule $poule): array
    {
        $games = $this->getGamesForPoule($poule);
        return array_values(array_filter($games, function ($game): bool {
            return $game instanceof AgainstGame;
        }));
    }

    /**
     * @return Collection<int|string, TogetherGame>
     */
    public function getTogetherGames(): Collection
    {
        return $this->togetherGames;
    }

    /**
     * @return list<TogetherGame>
     */
    public function getTogetherGamesForPoule(Poule $poule): array
    {
        $games = $this->getGamesForPoule($poule);
        return array_values(array_filter($games, function ($game): bool {
            return $game instanceof TogetherGame;
        }));
    }

    /**
     * @param int|null $stateValue
     * @return list<Planning>
     */
    public function getGamesInARowPlannings(int $stateValue = null): array
    {
        if ($this->getMaxNrOfGamesInARow() > 0) {
            return [];
        }
        $range = $this->getNrOfBatchGames();
        $gamesInARowPlannings = $this->getInput()->getPlannings()->filter(
            function (Planning $planning) use ($range, $stateValue): bool {
                return $planning->getMinNrOfBatchGames() === $range->getMin()
                    && $planning->getMaxNrOfBatchGames() === $range->getMax()
                    && $planning->getMaxNrOfGamesInARow() > 0
                    && ($stateValue === null || (($planning->getState()->value & $stateValue) > 0));
            }
        );
        return $this->orderGamesInARowPlannings($gamesInARowPlannings);
    }

    // from most efficient to less efficient
    /**
     * @param Collection<int|string,Planning> $gamesInARowPlannings
     * @return list<Planning>
     */
    protected function orderGamesInARowPlannings(Collection $gamesInARowPlannings): array
    {
        $plannings = $gamesInARowPlannings->toArray();
        uasort($plannings, function (Planning $first, Planning $second) {
            if ($first->getMaxNrOfGamesInARow() === $second->getMaxNrOfGamesInARow()) {
                return $first->getNrOfBatchGames()->difference() > $second->getNrOfBatchGames()->difference() ? -1 : 1;
            }
            return $first->getMaxNrOfGamesInARow() < $second->getMaxNrOfGamesInARow() ? -1 : 1;
        });
        return array_values($plannings);
    }

    public function getBestGamesInARowPlanning(): Planning
    {
        $succeededGamesInARowPlannings = $this->getGamesInARowPlannings(PlanningState::Succeeded->value);
        if (count($succeededGamesInARowPlannings) >= 1) {
            return reset($succeededGamesInARowPlannings);
        }
        if ($this->getState() === PlanningState::Succeeded) {
            return $this;
        }
        throw new Exception('er kan geen planning gevonden worden', E_ERROR);
    }

    public function getMaxNrOfBatches(): int
    {
        $sportVariants = array_values($this->input->createSportVariants()->toArray());
        $totalNrOfGames = $this->input->createPouleStructure()->getTotalNrOfGames($sportVariants);
        return (int)ceil($totalNrOfGames / $this->getMinNrOfBatchGames());
    }
}
