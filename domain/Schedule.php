<?php

declare(strict_types=1);

namespace SportsPlanning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use SportsHelpers\Identifiable;
use SportsHelpers\SportVariants\AgainstGpp;
use SportsHelpers\SportVariants\AgainstH2h;
use SportsHelpers\SportVariants\AllInOneGame;
use SportsHelpers\SportVariants\Single;
use SportsPlanning\Schedule\ScheduleSport as SportSchedule;
use SportsPlanning\Schedule\SportVariantWithNr;

class Schedule extends Identifiable
{
    private string $sportsConfigName;
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
     * @param list<SportVariantWithNr> $sportVariantsWithNr
     */
    public function __construct(protected int $nrOfPlaces, array $sportVariantsWithNr)
    {
        $this->sportSchedules = new ArrayCollection();
        $nrOfH2h = 0;
        array_map(function(SportVariantWithNr $sportVariantWithNr) use(&$nrOfH2h): SportSchedule {
            if(++$nrOfH2h > 1) {
                throw new \Exception('Only 1 h2h allowed');
            }
            return new SportSchedule($this, $sportVariantWithNr->number, $sportVariantWithNr->sportVariant->toPersistVariant());
        }, $sportVariantsWithNr);
        $this->sportsConfigName = $this->toJsonCustom();
    }

    public function getNrOfPlaces(): int
    {
        return $this->nrOfPlaces;
    }

    public function createSportVariantsName(): string
    {
        $name = json_encode($this->createSportVariants());
        return $name === false ? '?' : $name;
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

//    /**
//     * @return list<VariantWithFields>
//     */
//    public function createSportVariantsWithFields(): array
//    {
//        return array_map( function(Single|AgainstH2h|AgainstGpp|AllInOneGame $sportVariant): VariantWithFields {
//                return new VariantWithFields($sportVariant, 1);
//            } , $this->createSportVariants()
//        );
//    }

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

    public function toJsonCustom(): string
    {
        $output = new \stdClass();
        $output->nrOfPlaces = $this->nrOfPlaces;
        $output->sportVariants = $this->createSportVariants();
        $json = json_encode($output);
        return $json === false ? '?' : $json;
    }
}
