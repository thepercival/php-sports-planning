<?php

namespace SportsPlanning\Resource;

use SportsPlanning\Planning;
use SportsPlanning\Input;
use SportsPlanning\Batch;

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

    public function assign(Batch $batch)
    {
        if ($this->refereesEnabled() === false) {
            return;
        }
        $this->assignBatch($batch->getFirst(), $this->planning->getReferees()->toArray());
    }

    protected function assignBatch(Batch $batch, array $referees)
    {
        foreach ($batch->getGames() as $game) {
            $referee = array_shift($referees);
            $game->setReferee($referee);
            array_push($referees, $referee);
        }
        if ($batch->hasNext()) {
            $this->assignBatch($batch->getNext(), $referees);
        }
    }
}
