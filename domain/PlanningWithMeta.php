<?php

declare(strict_types=1);

namespace SportsPlanning;

use DateTimeImmutable;
use Exception;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\Identifiable;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Batches\Batch;
use SportsPlanning\Batches\SelfRefereeBatchOtherPoules;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\AgainstGamePlace;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Planning\BatchGamesType;
use SportsPlanning\Planning\PlanningFilter;
use SportsPlanning\Planning\PlanningState as PlanningState;
use SportsPlanning\Planning\TimeoutState;
use SportsPlanning\Planning\PlanningType as PlanningType;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstOneVsOneWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstOneVsTwoWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstTwoVsTwoWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\TogetherSportWithNrAndFields;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

final class PlanningWithMeta extends Identifiable
{
    public readonly int $minNrOfBatchGames;
    public readonly int $maxNrOfBatchGames;
    protected DateTimeImmutable $createdDateTime;
    protected PlanningState $state;
    protected TimeoutState|null $timeoutState = null;
    protected int $nrOfBatches = 0;
    protected int $validity = -1;

    public const int ORDER_GAMES_BY_BATCH = 1;
    // public const ORDER_GAMES_BY_GAMENUMBER = 2;

    public string|null $content = null;

    public function __construct(
        protected PlanningOrchestration $orchestration,
        SportRange $nrOfBatchGames,
        public readonly int $maxNrOfGamesInARow,
        public readonly Planning $planning)
    {
        $this->orchestration->getPlanningsWithMeta()->add($this);

        $this->minNrOfBatchGames = $nrOfBatchGames->getMin();
        $this->maxNrOfBatchGames = $nrOfBatchGames->getMax();

        $this->createdDateTime = new DateTimeImmutable();
        $this->state = PlanningState::NotProcessed;
    }

    public function minIsMaxNrOfBatchGames(): bool
    {
        return $this->minNrOfBatchGames === $this->maxNrOfBatchGames;
    }

//    public function setMinNrOfBatchGames( int $minNrOfBatchGames ) {
//        $this->minNrOfBatchGames = $minNrOfBatchGames;
//    }

//    public function setMaxNrOfBatchGames( int $maxNrOfBatchGames ) {
//        $this->maxNrOfBatchGames = $maxNrOfBatchGames;
//    }

    public function getNrOfBatchGames(): SportRange
    {
        return new SportRange($this->minNrOfBatchGames, $this->maxNrOfBatchGames);
    }

    public function isNrOfBatchGamesUnequal(): bool
    {
        return $this->getNrOfBatchGames()->difference() > 0;
    }

    public function getBatchGamesType(): BatchGamesType
    {
        if( $this->minNrOfBatchGames === $this->maxNrOfBatchGames ) {
            return BatchGamesType::RangeIsZero;
        }
        return BatchGamesType::RangeIsOneOrMore;
    }

    public function getType(): PlanningType
    {
        return $this->maxNrOfGamesInARow === 0 ? PlanningType::BatchGames : PlanningType::GamesInARow;
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

    public function getConfiguration(): PlanningConfiguration
    {
        return $this->orchestration->configuration;
    }

    public function createFirstBatch(): Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules
    {
        $games = $this->planning->getGames(PlanningWithMeta::ORDER_GAMES_BY_BATCH);
        $batch = new Batch($this->planning->createPouleMap());
        $configuration = $this->getConfiguration();
        $selfReferee = $configuration->refereeInfo?->selfRefereeInfo?->selfReferee;
        if ($selfReferee === SelfReferee::SamePoule) {
            $batch = new SelfRefereeBatchSamePoule($batch);
        } else if( $selfReferee === SelfReferee::OtherPoules) {
            $batch = new SelfRefereeBatchOtherPoules($batch);
        }
        foreach ($games as $game) {
            if ($game->getBatchNr() === ($batch->getNumber() + 1)) {
                $batch = $batch->createNext();
            }
            $batch->add($game);
        }
        return $batch->getFirst();
    }

    public function createFilter(): PlanningFilter
    {
        return new PlanningFilter(null, null, $this->getNrOfBatchGames(), $this->maxNrOfGamesInARow);
    }

    public function getNrOfPlaces(): int
    {
        return $this->getConfiguration()->pouleStructure->getNrOfPlaces();
    }
}
