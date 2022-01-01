<?php

declare(strict_types=1);

namespace SportsPlanning\Resource;

use SportsPlanning\Batch;
use SportsPlanning\Input;
use SportsPlanning\Referee;

class RefereeService
{
    public function __construct(private Input $input)
    {
    }

    protected function refereesEnabled(): bool
    {
        return !$this->input->selfRefereeEnabled() && $this->input->getReferees()->count() > 0;
    }

    public function assign(Batch $batch): void
    {
        $referees = array_values($this->input->getReferees()->toArray());
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
