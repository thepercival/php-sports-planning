<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\Identifiable;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Schedule\ScheduleSport;

class ScheduleGame extends Identifiable
{
    /**
     * @var Collection<int|string, ScheduleGamePlace>
     */
    protected Collection $places;

    public function __construct(protected ScheduleSport $scheduleSport, protected int|null $gameRoundNumber = null)
    {
        if (!$scheduleSport->getGames()->contains($this)) {
            $scheduleSport->getGames()->add($this) ;
        }
        $this->places = new ArrayCollection();
    }

    public function getGameRoundNumber(): int
    {
        if ($this->gameRoundNumber === null) {
            throw new \Exception('schedule-game->gameRoundNumber can not be null', E_ERROR);
        }
        return $this->gameRoundNumber;
    }

    /**
     * @return Collection<int|string, ScheduleGamePlace>
     */
    public function getGamePlaces(): Collection
    {
        return $this->places;
    }

    /**
     * @param AgainstSide $againstSide
     * @return list<int>
     */
    public function getSidePlaceNrs(AgainstSide $againstSide): array
    {
        $poulePlaceNrs = [];
        foreach ($this->getGamePlaces() as $gameRoundGamePlace) {
            if ($gameRoundGamePlace->getAgainstSide() === $againstSide) {
                $poulePlaceNrs[] = $gameRoundGamePlace->getPlaceNr();
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
        if( $this->gameRoundNumber !== null ) { // Against, AllInOneGame
            return 'gr ' . $this->gameRoundNumber . ' : ' . $this->convertToHomeAway();
        }
        // Single
        $gamePlacesAsString = array_map(function (ScheduleGamePlace $gamePlace): string {
            return $gamePlace->getPlaceNr() . '(gr' . $gamePlace->getGameRoundNumber() . ')';
        }, $this->getGamePlaces()->toArray());
        return join(' & ', $gamePlacesAsString);
    }


}
