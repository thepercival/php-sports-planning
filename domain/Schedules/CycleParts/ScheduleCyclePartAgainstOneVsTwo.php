<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\CycleParts;

use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Planning\ListNode;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsTwo;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsTwo;

/**
 * @template-extends ListNode<ScheduleCyclePartAgainstOneVsTwo>
 */
final class ScheduleCyclePartAgainstOneVsTwo extends ListNode
{
    protected AmountNrCounterMap $placeNrCounterMap;

    /**
     * @var list<ScheduleGameAgainstOneVsTwo>
     */
    protected array $games = [];

    public function __construct(
        public readonly ScheduleCycleAgainstOneVsTwo $cycle,
        ScheduleCyclePartAgainstOneVsTwo|null        $previous = null)
    {
        $this->placeNrCounterMap = new AmountNrCounterMap($cycle->sportSchedule->scheduleWithNrOfPlaces->nrOfPlaces);
        parent::__construct($previous);
    }

    public function isParticipating(int $placeNr): bool
    {
        return $this->placeNrCounterMap->count($placeNr) > 0;
    }

    public function createNext(): ScheduleCyclePartAgainstOneVsTwo
    {
        $this->next = new ScheduleCyclePartAgainstOneVsTwo($this->cycle, $this);
        return $this->next;
    }

    public function addGame(ScheduleGameAgainstOneVsTwo $againstGame): void
    {
        $homeAway = $againstGame->convertToHomeAway();
        foreach ($homeAway->convertToPlaceNrs() as $placeNr) {
            if( $this->placeNrCounterMap->count($placeNr) > 0 ) {
                throw new \Exception('a placeNr can only be used 1 time per gameRound');
            }
            $this->placeNrCounterMap->incrementPlaceNr($placeNr);
        }
        array_push($this->games, $againstGame );

    }

//    public function remove(OneVsTwoHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void
//    {
//        $this->placeNrCounterMap->removeHomeAway($homeAway);
//    }

//    public function swapSidesOfHomeAway(OneVsTwoHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $reversedHomeAway): bool
//    {
//        foreach( $this->homeAways as $needle => $homeAwayIt) {
//            if( $homeAwayIt->equals($reversedHomeAway) ) {
//                array_splice($this->homeAways, $needle, 1, [$reversedHomeAway]);
//                return true;
//            }
//        }
//        return false;
//    }

    /**
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    public function getGamesAsHomeAways(): array
    {
        return array_map(
            function(ScheduleGameAgainstOneVsTwo $againstGame){
                return $againstGame->convertToHomeAway();
            }, $this->games );
    }

    public function isSomeHomeAwayPlaceNrParticipating(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): bool
    {
        foreach ($homeAway->convertToPlaceNrs() as $placeNr) {
            if ($this->isParticipating($placeNr)) {
                return true;
            }
        }
        return false;
    }

    public function getSelfAndAllPreviousNrOfHomeAways(): int {
        $previous = $this->getPrevious();
        if( $previous !== null ) {
            return count($this->getGamesAsHomeAways()) + $previous->getSelfAndAllPreviousNrOfHomeAways();
        }
        return count($this->getGamesAsHomeAways());
    }




    /**
     * @return list<ScheduleGameAgainstOneVsTwo>
     */
    public function getGames(): array {
        return $this->games;
    }

}
