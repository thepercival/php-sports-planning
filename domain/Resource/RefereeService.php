<?php

namespace SportsPlanning\Resource;

use SportsPlanning\Planning;
use SportsPlanning\Input;
use SportsPlanning\Batch;
use SportsPlanning\Referee;

class RefereeService
{
    public function __construct(private Planning $planning)
    {
    }

    protected function getInput(): Input
    {
        return $this->planning->getInput();
    }

    protected function refereesEnabled(): bool
    {
        return !$this->getInput()->selfRefereeEnabled() && $this->getInput()->getReferees()->count() > 0;
    }

    public function assign(Batch $batch): void
    {
        $referees = array_values($this->planning->getInput()->getReferees()->toArray());
        $this->assignBatch($batch->getFirst(), $referees);
    }

    /**
     * @param Batch $batch
     * @param list<Referee> $referees
     */
    protected function assignBatch(Batch $batch, array $referees): void
    {
        foreach ($batch->getGames() as $game) {
            $referee = array_shift($referees);
            if ($referee === null) {
                break;
            }
            $game->setReferee($referee);
            array_push($referees, $referee);
        }
        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            $this->assignBatch($nextBatch, $referees);
        }
    }
}
