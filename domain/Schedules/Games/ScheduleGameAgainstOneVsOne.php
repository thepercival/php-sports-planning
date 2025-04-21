<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Games;

use SportsHelpers\Against\AgainstSide;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainst;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsOne;

class ScheduleGameAgainstOneVsOne extends ScheduleGameAgainstAbstract
{
    public function __construct(ScheduleCyclePartAgainst $cyclePart)
    {
        parent::__construct($cyclePart);
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

    public function convertToHomeAway(): OneVsOneHomeAway {
        $homePlaceNrs = $this->getSidePlaceNrs(AgainstSide::Home);
        $awayPlaceNrs = $this->getSidePlaceNrs(AgainstSide::Away);
        return new OneVsOneHomeAway($homePlaceNrs[0], $awayPlaceNrs[0]);
    }

    public function __toString(): string
    {
        return 'cy ' . $this->cyclePart->getNumber(). '.' . $this->cyclePart->cycle->getNumber(). ' : ' . $this->convertToHomeAway();
    }

}
