<?php

declare(strict_types=1);

namespace SportsPlanning;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use SportsHelpers\SelfReferee;
use SportsHelpers\SportRange;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeOtherPouleBatch;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeSamePouleBatch;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Input\Configuration as InputConfiguration;
use SportsPlanning\Planning\Filter;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Planning\TimeoutState;
use SportsPlanning\Planning\TimeoutConfig;
use SportsPlanning\Planning\Type as PlanningType;

class Planning extends Identifiable
{
    protected int $minNrOfBatchGames;
    protected int $maxNrOfBatchGames;
    protected DateTimeImmutable $createdDateTime;
    protected PlanningState $state;
    protected TimeoutState|null $timeoutState = null;
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

    public function __construct(protected Input $input, SportRange $nrOfBatchGames, protected int $maxNrOfGamesInARow)
    {
        $this->minNrOfBatchGames = $nrOfBatchGames->getMin();
        $this->maxNrOfBatchGames = $nrOfBatchGames->getMax();
        $this->input->getPlannings()->add($this);

        $this->againstGames = new ArrayCollection();
        $this->togetherGames = new ArrayCollection();

        $this->createdDateTime = new DateTimeImmutable();
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

    public function isNrOfBatchGamesUnequal(): bool
    {
        return $this->getNrOfBatchGames()->difference() > 0;
    }

    public function getMaxNrOfGamesInARow(): int
    {
        return $this->maxNrOfGamesInARow;
    }

    public function isEqualBatchGames(): bool
    {
        return $this->isBatchGames() && $this->getMinNrOfBatchGames() === $this->getMaxNrOfBatchGames();
    }

    public function isUnequalBatchGames(): bool
    {
        return $this->isBatchGames() && $this->getMinNrOfBatchGames() !== $this->getMaxNrOfBatchGames();
    }

    public function isBatchGames(): bool
    {
        return $this->maxNrOfGamesInARow === 0;
    }

    public function getType(): PlanningType
    {
        return $this->isBatchGames() ? PlanningType::BatchGames : PlanningType::GamesInARow;
    }

    public function getCreatedDateTime(): DateTimeImmutable
    {
        return $this->createdDateTime;
    }

    public function setCreatedDateTime(DateTimeImmutable $createdDateTime): void
    {
        $this->createdDateTime = $createdDateTime;
    }

    public function getTimeoutState(): TimeoutState|null
    {
        return $this->timeoutState;
    }

    public function setTimeoutState(TimeoutState|null $timeoutState): void
    {
        $this->timeoutState = $timeoutState;
    }

    public function getState(): PlanningState
    {
        return $this->state;
    }

    public function setState(PlanningState $state): void
    {
        $this->state = $state;
    }

    public function getStateDescription(): string
    {
        $stateDescription = $this->getState()->name;
        if ($this->getState() === PlanningState::TimedOut) {
            $timeoutState = $this->getTimeoutState();
            if ($timeoutState !== null) {
                $stateDescription .= '(' . $timeoutState->value . ')';
            }
        }
        return $stateDescription;
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
     * @param PlanningState|null $state
     * @return list<Planning>
     */
    public function getGamesInARowPlannings(PlanningState|null $state = null): array
    {
        if ($this->getMaxNrOfGamesInARow() > 0) {
            return [];
        }
        $range = $this->getNrOfBatchGames();
        $gamesInARowPlannings = $this->getInput()->getPlannings()->filter(
            function (Planning $planning) use ($range, $state): bool {
                return $planning->getMinNrOfBatchGames() === $range->getMin()
                    && $planning->getMaxNrOfBatchGames() === $range->getMax()
                    && $planning->getMaxNrOfGamesInARow() > 0
                    && ($state === null || (($planning->getState()->value & $state->value) > 0));
            }
        );
        return $this->orderGamesInARowPlannings($gamesInARowPlannings);
    }

    public function createFilter(): Filter
    {
        return new Filter(null, null, $this->getNrOfBatchGames(), $this->getMaxNrOfGamesInARow());
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
            return $first->getMaxNrOfGamesInARow() - $second->getMaxNrOfGamesInARow();
        });
        return array_values($plannings);
    }

    public function getBestGamesInARowPlanning(): Planning|null
    {
        $succeededGamesInARowPlannings = $this->getGamesInARowPlannings(PlanningState::Succeeded);
        return array_shift($succeededGamesInARowPlannings);
    }

    public function getMaxNrOfBatches(): int
    {
        $sportVariants = $this->input->createSportVariants();
        $totalNrOfGames = $this->input->createPouleStructure()->getTotalNrOfGames($sportVariants);
        return (int)ceil($totalNrOfGames / $this->getMinNrOfBatchGames());
    }

    public function createInputConfiguration(): InputConfiguration {
        return new InputConfiguration(
            $this->input->createPouleStructure(),
            $this->input->createSportVariantsWithFields(),
            $this->input->getRefereeInfo(),
            $this->input->getPerPoule()
        );
    }
}
