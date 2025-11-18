<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use SportsHelpers\Sport\PersistVariant;
use SportsHelpers\Sport\Variant\Creator as VariantCreator;
use SportsHelpers\Sport\Variant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Schedule;

final class Sport extends PersistVariant
{
    /**
     * @var Collection<int|string, Game>
     */
    protected Collection $games;

    public function __construct(protected Schedule $schedule, protected int $number, PersistVariant $sportVariant)
    {
        parent::__construct(
            $sportVariant->getGameMode(),
            $sportVariant->getNrOfHomePlaces(),
            $sportVariant->getNrOfAwayPlaces(),
            $sportVariant->getNrOfGamePlaces(),
            $sportVariant->getNrOfH2H(),
            $sportVariant->getNrOfGamesPerPlace()
        );
        if (!$schedule->getSportSchedules()->contains($this)) {
            $schedule->getSportSchedules()->add($this);
        }
        $this->games = new ArrayCollection();
    }

    public function getSchedule(): Schedule {
        return $this->schedule;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @return Collection<int|string, Game>
     */
    public function getGames(): Collection
    {
        return $this->games;
    }
    // ArrayCollection $gameRoundGames (home: [1,2], away: [3,4], single: [1,2,3,4,5])

//    public function allPlacesSameNrOfGamesAssignable(): bool
//    {
//        $nrOfPlaces = $this->schedule->getNrOfPlaces();
//        $variantWithPoule = (new VariantCreator())->createWithPoule($nrOfPlaces, $this->createVariant());
//        if( !($variantWithPoule instanceof AgainstGppWithPoule) ) {
//            return true;
//        }
//        return $variantWithPoule->allPlacesSameNrOfGamesAssignable();
//    }
}
