<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use SportsHelpers\Sport\PersistVariant;
use SportsPlanning\Schedule;

class Sport extends PersistVariant
{
    /**
     * @phpstan-var ArrayCollection<int|string, Game>|PersistentCollection<int|string, Game>|Game[]
     * @psalm-var ArrayCollection<int|string, Game>
     */
    protected ArrayCollection|PersistentCollection $games;

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
            $schedule->getSportSchedules()->add($this) ;
        }
        $this->games = new ArrayCollection();
    }

    public function getNumber(): int {
        return $this->number;
    }

    /**
     * @phpstan-return ArrayCollection<int|string, Game>|PersistentCollection<int|string, Game>|Game[]
     * @psalm-return ArrayCollection<int|string, Game>
     */
    public function getGames(): ArrayCollection|PersistentCollection
    {
        return $this->games;
    }
    // ArrayCollection $gameRoundGames (home: [1,2], away: [3,4], single: [1,2,3,4,5])
}
