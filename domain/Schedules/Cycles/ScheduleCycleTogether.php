<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Cycles;

use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\Planning\ListNode;
use SportsPlanning\Schedules\GamePlaces\ScheduleGamePlaceTogether;
use SportsPlanning\Schedules\Games\ScheduleGameTogether;

/**
 * @template-extends ListNode<ScheduleCycleTogether>
 */
class ScheduleCycleTogether extends ListNode
{
    protected AmountNrCounterMap $placeNrCounterMap;

    /**
     * @var list<ScheduleGameTogether>
     */
    protected array $games = [];

    public function __construct(public readonly int $nrOfPlaces, ScheduleCycleTogether|null $previous = null)
    {
        $this->placeNrCounterMap = new AmountNrCounterMap($nrOfPlaces);
        parent::__construct($previous);

    }

    public function createNext(): ScheduleCycleTogether
    {
        $this->next = new ScheduleCycleTogether($this->nrOfPlaces, $this);
        return $this->next;
    }


    /**
     * @return list<ScheduleGameTogether>
     */
    public function getGames(): array
    {
        return $this->games;
    }

    public function addGame(ScheduleGameTogether $game): void
    {
        $this->games[] = $game;
    }

    /**
     * @param ScheduleGamePlaceTogether $gamePlace
     * @return void
     * @throws \Exception
     */
    public function addGamePlace(ScheduleGamePlaceTogether $gamePlace): void
    {
        $cycle = $this->findCycleByNumber($gamePlace->cycleNr);
        $cycle->addPlaceNrToAmountMap($gamePlace->placeNr);
    }

    protected function findCycleByNumber(int $number): ScheduleCycleTogether {
        if( $number === $this->number) {
            return $this;
        }
        if( $number < $this->number) {
            if( $this->previous === null) {
                throw new \Exception('cycle could not be found for "' . $number . '"');
            }
            return $this->previous->findCycleByNumber($number);
        }
        if( $this->next === null) {
            throw new \Exception('cycle could not be found for "' . $number . '"');
        }
        return $this->next->findCycleByNumber($number);
    }

    public function addPlaceNrToAmountMap(int $placeNr): void {
        if( $this->placeNrCounterMap->count($placeNr) > 0 ) {
            throw new \Exception('placeNr "' .$placeNr. '" already exists for cycle "' . $this->number . '"');
        }
        $this->placeNrCounterMap->incrementPlaceNr($placeNr);
    }
}
