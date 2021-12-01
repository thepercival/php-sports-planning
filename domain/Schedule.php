<?php
declare(strict_types=1);

namespace SportsPlanning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use SportsHelpers\Identifiable;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Schedule\Name as ScheduleName;
use SportsPlanning\Schedule\Sport as SportSchedule;

class Schedule extends Identifiable implements \Stringable
{
    protected GamePlaceStrategy $gamePlaceStrategy;
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

    public function getGamePlaceStrategy(): GamePlaceStrategy
    {
        return $this->gamePlaceStrategy;
    }

    public function getSportsConfigName(): string
    {
        return $this->sportsConfigName;
    }

    /**
     * @return Collection<int|string, SportSchedule>
     */
    public function getSportSchedules(): Collection
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

    public function __toString()
    {
        $json = json_encode( [
            "nrOfPlaces" => $this->nrOfPlaces,
            "gamePlaceStrategy" => $this->gamePlaceStrategy,
            "sportsConfigName" => new ScheduleName(array_values( $this->createSportVariants()->toArray() ) )
        ] );
        return $json ? $json : '';
    }
}
