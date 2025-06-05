<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules\Games;

use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainstOneVsTwo;

final class ScheduleGameAgainstOneVsTwo extends ScheduleGameAgainstAbstract
{
    public function __construct(ScheduleCyclePartAgainstOneVsTwo $cyclePart)
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

    public function convertToHomeAway(): OneVsTwoHomeAway {
        $homePlaceNrs = $this->getSidePlaceNrs(AgainstSide::Home);
        $awayPlaceNrs = $this->getSidePlaceNrs(AgainstSide::Away);
        return new OneVsTwoHomeAway(
            $homePlaceNrs[0],
            new DuoPlaceNr($awayPlaceNrs[0], $awayPlaceNrs[1])
        );

    }

    public function __toString(): string
    {
        return 'cy ' . $this->cyclePart->getNumber(). '.' . $this->cyclePart->cycle->getNumber(). ' : ' . (string)$this->convertToHomeAway();
    }

}
