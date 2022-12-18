<?php

declare(strict_types=1);

namespace SportsPlanning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use SportsHelpers\Identifiable;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGame;
use SportsHelpers\Sport\Variant\Single as Single;
use SportsPlanning\Schedule\Name as ScheduleName;
use SportsPlanning\Schedule\Sport as SportSchedule;

class Schedule extends Identifiable implements \Stringable
{
    protected string $sportsConfigName;
    protected int $succeededMargin = -1;
    protected int $nrOfTimeoutSecondsTried = -1;

    /**
     * @phpstan-var ArrayCollection<int|string, SportSchedule>|PersistentCollection<int|string, SportSchedule>|SportSchedule[]
     * @psalm-var ArrayCollection<int|string, SportSchedule>
     */
    protected ArrayCollection|PersistentCollection $sportSchedules;

    public function __construct(protected int $nrOfPlaces, Input $input)
    {
        $sportVariants = array_values($input->createSportVariants()->toArray());
        $this->sportsConfigName = (string)new ScheduleName($sportVariants);
        $this->sportSchedules = new ArrayCollection();
    }

    public function getNrOfPlaces(): int
    {
        return $this->nrOfPlaces;
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
     * @return Collection<int|string, Single|AgainstH2h|AgainstGpp|AllInOneGame>
     */
    public function createSportVariants(): Collection
    {
        return $this->sportSchedules->map(
            function (SportSchedule $sportSchedule): Single|AgainstH2h|AgainstGpp|AllInOneGame {
                return $sportSchedule->createVariant();
            }
        );
    }

    public function getSucceededMargin(): int
    {
        return $this->succeededMargin;
    }

    public function putSucceededMargin(int $succeededMargin): void
    {
        $this->succeededMargin = $succeededMargin;
    }

    public function getNrOfTimeoutSecondsTried(): int
    {
        return $this->nrOfTimeoutSecondsTried;
    }

    public function putNrOfTimeoutSecondsTried(int $nrOfTimeoutSecondsTried): void
    {
        $this->nrOfTimeoutSecondsTried = $nrOfTimeoutSecondsTried;
    }

    public function __toString()
    {
        $XYZ = 'XYZ';
        $scheduleName = (string)new ScheduleName(array_values($this->createSportVariants()->toArray()));
        $json = json_encode(["nrOfPlaces" => $this->nrOfPlaces, "sportsConfigName" => $XYZ]);
        if ($json === false) {
            return '';
        }
        return str_replace($XYZ, $scheduleName, $json);
    }
}
