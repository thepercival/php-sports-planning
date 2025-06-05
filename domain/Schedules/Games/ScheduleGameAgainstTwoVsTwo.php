<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Games;

use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainstTwoVsTwo;
use SportsPlanning\Schedules\GamePlaces\ScheduleGamePlaceAgainst;

final class ScheduleGameAgainstTwoVsTwo extends ScheduleGameAgainstAbstract
{
    public function __construct(ScheduleCyclePartAgainstTwoVsTwo $cyclePart, TwoVsTwoHomeAway|null $homeAway = null)
    {
        parent::__construct($cyclePart);
        if( $homeAway instanceof TwoVsTwoHomeAway ){
            foreach( [AgainstSide::Home, AgainstSide::Away] as $side ) {
                $duoPlaceNr = $homeAway->get($side);
                new ScheduleGamePlaceAgainst($this, $side, $duoPlaceNr->placeNrOne);
                new ScheduleGamePlaceAgainst($this, $side, $duoPlaceNr->placeNrTwo);
            }
        }
    }

    /**
     * @param AgainstSide $againstSide
     * @return list<int>
     */
    public function getSidePlaceNrs(AgainstSide $againstSide): array
    {
        $poulePlaceNrs = [];
        foreach ($this->getGamePlaces() as $gameRoundGamePlace) {
            if ($gameRoundGamePlace->againstSide === $againstSide) {
                $poulePlaceNrs[] = $gameRoundGamePlace->placeNr;
            }
        }
        return $poulePlaceNrs;
    }

    public function convertToHomeAway(): TwoVsTwoHomeAway {
        $homePlaceNrs = $this->getSidePlaceNrs(AgainstSide::Home);
        $awayPlaceNrs = $this->getSidePlaceNrs(AgainstSide::Away);
        if( count($homePlaceNrs) === 2 && count($awayPlaceNrs) === 2) {
            return new TwoVsTwoHomeAway(
                new DuoPlaceNr($homePlaceNrs[0], $homePlaceNrs[1]),
                new DuoPlaceNr($awayPlaceNrs[0], $awayPlaceNrs[1])
            );
        }
        throw new \Exception('unknown number of sidePlaceNrs');
    }

    public function __toString(): string
    {
        return 'cy ' . $this->cyclePart->getNumber(). '.' . $this->cyclePart->cycle->getNumber(). ' : ' . (string)$this->convertToHomeAway();
    }

}
