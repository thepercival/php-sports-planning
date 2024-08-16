<?php

declare(strict_types=1);

namespace SportsPlanning\Planning;

use DateTimeImmutable;
use SportsHelpers\Identifiable;
use SportsHelpers\SportRange;
use SportsPlanning\Input;
use SportsPlanning\Planning\Type as PlanningType;

class HistoricalBestPlanning extends Identifiable
{
    protected int $minNrOfBatchGames;
    protected int $maxNrOfBatchGames;
    protected DateTimeImmutable $removalDateTime;

    public function __construct(
        protected Input $input,
        SportRange $nrOfBatchGames,
        protected int $maxNrOfGamesInARow,
        protected string $recalculateReason,
        protected int $nrOfBatches )
    {
        $this->minNrOfBatchGames = $nrOfBatchGames->getMin();
        $this->maxNrOfBatchGames = $nrOfBatchGames->getMax();
        $this->removalDateTime = new DateTimeImmutable();
        $this->input->getHistoricalBestPlannings()->add($this);
    }

    public function getMinNrOfBatchGames(): int
    {
        return $this->minNrOfBatchGames;
    }

    public function getMaxNrOfBatchGames(): int
    {
        return $this->maxNrOfBatchGames;
    }

    public function getNrOfBatchGames(): SportRange
    {
        return new SportRange($this->getMinNrOfBatchGames(), $this->getMaxNrOfBatchGames());
    }

    public function getMaxNrOfGamesInARow(): int
    {
        return $this->maxNrOfGamesInARow;
    }

    public function getBatchGamesType(): BatchGamesType
    {
        if( $this->getMinNrOfBatchGames() === $this->getMaxNrOfBatchGames() ) {
            return BatchGamesType::RangeIsZero;
        }
        return BatchGamesType::RangeIsOneOrMore;
    }

    public function getType(): PlanningType
    {
        return $this->maxNrOfGamesInARow === 0 ? PlanningType::BatchGames : PlanningType::GamesInARow;
    }

    public function getRecalculateReason(): string
    {
        return $this->recalculateReason;
    }

    public function getRemovalDateTime(): DateTimeImmutable
    {
        return $this->removalDateTime;
    }

    public function getNrOfBatches(): int
    {
        return $this->nrOfBatches;
    }

    public function getInput(): Input
    {
        return $this->input;
    }

//    public function createInputConfiguration(): InputConfiguration {
//        return new InputConfiguration(
//            $this->input->createPouleStructure(),
//            $this->input->createSportVariantsWithFields(),
//            $this->input->getRefereeInfo(),
//            $this->input->getPerPoule()
//        );
//    }
}
