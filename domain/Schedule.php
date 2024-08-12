<?php

declare(strict_types=1);

namespace SportsPlanning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use SportsPlanning\Identifiable;
use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGame;
use SportsHelpers\Sport\Variant\Single as Single;
use SportsHelpers\Sport\VariantWithFields;
use SportsPlanning\Schedule\Name as ScheduleName;
use SportsPlanning\Schedule\Sport as SportSchedule;

class Schedule extends Identifiable implements \Stringable
{
    protected string $sportsConfigName;
    protected int $succeededMargin = -1;
    protected Poule|null $poule = null;
    protected int $nrOfTimeoutSecondsTried = 0;

    /**
     * @phpstan-var ArrayCollection<int|string, SportSchedule>|PersistentCollection<int|string, SportSchedule>|SportSchedule[]
     * @psalm-var ArrayCollection<int|string, SportSchedule>
     */
    protected ArrayCollection|PersistentCollection $sportSchedules;

    /**
     * @param int $nrOfPlaces
     * @param list<Single|AgainstH2h|AgainstGpp|AllInOneGame> $sportVariants
     */
    public function __construct(protected int $nrOfPlaces, array $sportVariants)
    {
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
     * @return list<Single|AgainstH2h|AgainstGpp|AllInOneGame>
     */
    public function createSportVariants(): array
    {
        return array_map(
            function (SportSchedule $sportSchedule): Single|AgainstH2h|AgainstGpp|AllInOneGame {
                return $sportSchedule->createVariant();
            }, array_values($this->sportSchedules->toArray())
        );
    }

//    public function createSportVariantWithPoules(): array
//    {
//        return array_values(
//                array_map( function(Single|AgainstH2h|AgainstGpp|AllInOneGame $sportVariant): VariantWithPoule {
//                return new VariantWithPoule($sportVariant, $this->getNrOfPlaces());
//            } , $this->createSportVariants()->toArray() )
//        );
//    }

    /**
     * @return list<VariantWithFields>
     */
    public function createSportVariantWithFields(): array
    {
        return array_map( function(Single|AgainstH2h|AgainstGpp|AllInOneGame $sportVariant): VariantWithFields {
                return new VariantWithFields($sportVariant, 1);
            } , $this->createSportVariants()
        );
    }

    public function getSucceededMargin(): int
    {
        return $this->succeededMargin;
    }

    public function setSucceededMargin(int $succeededMargin): void
    {
        $this->succeededMargin = $succeededMargin;
    }

    public function getNrOfTimeoutSecondsTried(): int
    {
        return $this->nrOfTimeoutSecondsTried;
    }

    public function setNrOfTimeoutSecondsTried(int $nrOfTimeoutSecondsTried): void
    {
        $this->nrOfTimeoutSecondsTried = $nrOfTimeoutSecondsTried;
    }

    public function __toString(): string
    {
        $XYZ = 'XYZ';
        $scheduleName = (string)new ScheduleName($this->createSportVariants());
        $json = json_encode(["nrOfPlaces" => $this->nrOfPlaces, "sportsConfigName" => $XYZ]);
        if ($json === false) {
            return '';
        }
        return str_replace($XYZ, $scheduleName, $json);
    }
}
