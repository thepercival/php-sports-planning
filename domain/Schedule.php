<?php

namespace SportsPlanning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Schedule\Name as ScheduleName;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsPlanning\Schedule\Sport as SportSchedule;

class Schedule
{
    protected int $gamePlaceStrategy;
    protected string $sportsConfigName;

    /**
     * @phpstan-var ArrayCollection<int|string, SportSchedule>|PersistentCollection<int|string, SportSchedule>|SportSchedule[]
     * @psalm-var ArrayCollection<int|string, SportSchedule>
     */
    protected ArrayCollection|PersistentCollection $sportSchedules;

    public function __construct(protected int $nrOfPlaces, Input $input)
    {
        $sportVariants = array_values($input->createSportVariants()->toArray());
        $this->sportsConfigName = (string)new ScheduleName($sportVariants);
        $this->gamePlaceStrategy = $input->getGamePlaceStrategy();
        $this->sportSchedules = new ArrayCollection();
    }

    public function getNrOfPlaces(): int
    {
        return $this->nrOfPlaces;
    }

    /**
     * @phpstan-return ArrayCollection<int|string, SportSchedule>|PersistentCollection<int|string, SportSchedule>|SportSchedule[]
     * @psalm-return ArrayCollection<int|string, SportSchedule>
     */
    public function getSportSchedules(): ArrayCollection|PersistentCollection
    {
        return $this->sportSchedules;
    }

    /**
     * @return Collection<int|string, SingleSportVariant|AgainstSportVariant|AllInOneGameSportVariant>
     */
    public function createSportVariants(): Collection
    {
        return $this->sportSchedules->map(function (SportSchedule $sportSchedule): SingleSportVariant|AgainstSportVariant|AllInOneGameSportVariant {
            return $sportSchedule->createVariant();
        });
    }
}
