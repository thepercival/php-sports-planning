<?php

declare(strict_types=1);

namespace SportsPlanning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use SportsHelpers\Identifiable;
use SportsPlanning\Schedule\ScheduleAgainstSportOneVsOne;
use SportsPlanning\Schedule\ScheduleAgainstSportOneVsTwo;
use SportsPlanning\Schedule\ScheduleAgainstSportTwoVsTwo;
use SportsPlanning\Schedule\ScheduleSport as SportSchedule;
use SportsPlanning\Schedule\ScheduleTogetherSport;

class Schedule extends Identifiable
{
    private string $sportsConfigName;

    protected Poule|null $poule = null;

    /**
     * @phpstan-var ArrayCollection<int|string, SportSchedule>|PersistentCollection<int|string, SportSchedule>|SportSchedule[]
     * @psalm-var ArrayCollection<int|string, SportSchedule>
     */
    protected ArrayCollection|PersistentCollection $sportSchedules;

    /**
     * @param int $nrOfPlaces
     * @param list<ScheduleTogetherSport|ScheduleAgainstSportOneVsOne|ScheduleAgainstSportOneVsTwo|ScheduleAgainstSportTwoVsTwo> $scheduleSports
     */
    public function __construct(protected int $nrOfPlaces, array $scheduleSports)
    {
        $this->sportSchedules = new ArrayCollection();
        $nrOfOneVsOne = 0;
        foreach( $scheduleSports as $scheduleSport ) {
            if( $scheduleSport instanceof ScheduleAgainstSportOneVsOne ) {
                $nrOfOneVsOne++;
            }
            if(++$nrOfOneVsOne > 1) {
                throw new \Exception('Only 1 OneVsOne allowed');
            }


        }
        $this->sportsConfigName = "UNKNOWN"; // $this->toJsonCustom();
    }

    public function getNrOfPlaces(): int
    {
        return $this->nrOfPlaces;
    }

//    public function createSportVariantsName(): string
//    {
//        $name = json_encode($this->createSportVariants());
//        return $name === false ? '?' : $name;
//    }

    /**
     * @return Collection<int|string, SportSchedule>
     */
    public function getSportSchedules(): Collection
    {
        return $this->sportSchedules;
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

//    public function toJsonCustom(): string
//    {
//        $output = new \stdClass();
//        $output->nrOfPlaces = $this->nrOfPlaces;
//        $output->sportVariants = $this->createSportVariants();
//        $json = json_encode($output);
//        return $json === false ? '?' : $json;
//    }
}
