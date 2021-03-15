<?php

namespace SportsPlanning\Resource;

use SportsPlanning\Planning;
use SportsPlanning\Input;
use SportsPlanning\Batch;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeSamePouleBatch;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeOtherPouleBatch;

class RefereeService
{
    private Planning $planning;

    public function __construct(Planning $planning)
    {
        $this->planning = $planning;
    }

    protected function getInput(): Input
    {
        return $this->planning->getInput();
    }

    protected function refereesEnabled(): bool
    {
        return !$this->getInput()->selfRefereeEnabled() && $this->getInput()->getNrOfReferees() > 0;
    }

    public function assign(Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch): void
    {
        $this->assignBatch($batch->getFirst(), $this->planning->getReferees()->toArray());
    }

    protected function assignBatch(Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch, array $referees): void
    {
        foreach ($batch->getGames() as $game) {
            $referee = array_shift($referees);
            $game->setReferee($referee);
            array_push($referees, $referee);
        }
        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            $this->assignBatch($nextBatch, $referees);
        }
    }
}
