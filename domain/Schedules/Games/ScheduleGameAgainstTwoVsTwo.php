<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Games;

use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Schedules\Sports\ScheduleAgainstTwoVsTwo;

class ScheduleGameAgainstTwoVsTwo extends ScheduleGameAgainstAbstract
{
    public function __construct(
        public readonly ScheduleAgainstTwoVsTwo $scheduleSport,
        public readonly int $cycleNr,
        public readonly int $cyclePartNr)
    {
        parent::__construct();
        $this->scheduleSport->addGame($this);
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

    public function convertToHomeAway(): OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway {
        $homePlaceNrs = $this->getSidePlaceNrs(AgainstSide::Home);
        $awayPlaceNrs = $this->getSidePlaceNrs(AgainstSide::Away);
        if( count($homePlaceNrs) === 1 && count($awayPlaceNrs) === 1) {
            return new OneVsOneHomeAway($homePlaceNrs[0], $awayPlaceNrs[0]);
        }
        if( count($homePlaceNrs) === 1 && count($awayPlaceNrs) === 2) {
            return new OneVsTwoHomeAway(
                $homePlaceNrs[0],
                new DuoPlaceNr($awayPlaceNrs[0], $awayPlaceNrs[1])
            );
        }
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
        return 'cy ' . $this->cycleNr. '.' . $this->cyclePartNr. ' : ' . $this->convertToHomeAway();
    }

}
