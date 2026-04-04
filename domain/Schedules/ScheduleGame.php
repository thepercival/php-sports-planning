<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceNrCombination;
use SportsPlanning\Identifiable;
use SportsPlanning\Schedules\ScheduleSport;

final class ScheduleGame extends Identifiable
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

    public function convertToHomeAway(): HomeAway {
        $homePlaceNrs = $this->getSidePlaceNrs(AgainstSide::Home);
        $awayPlaceNrs = $this->getSidePlaceNrs(AgainstSide::Away);
        return new HomeAway( new PlaceNrCombination( $homePlaceNrs ), new PlaceNrCombination( $awayPlaceNrs ) );
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
                $poulePlaceNrs[] = $gameRoundGamePlace->getNumber();
            }
        }
        return $poulePlaceNrs;
    }

    public function __toString(): string
    {
        $retVal = $this->gameRoundNumber !== null ? 'gameroundnr ' . $this->gameRoundNumber . ' : ' : '';
        $retVal .= implode(' & ', $this->getGamePlaces()->toArray());
        return $retVal;
    }
}
