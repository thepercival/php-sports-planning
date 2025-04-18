<?php

declare(strict_types=1);

namespace SportsPlanning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use SportsHelpers\Identifiable;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Schedule\ScheduleAgainstOneVsOne;
use SportsPlanning\Schedule\ScheduleAgainstOneVsTwo;
use SportsPlanning\Schedule\ScheduleAgainstTwoVsTwo;
use SportsPlanning\Schedule\ScheduleSport as SportSchedule;
use SportsPlanning\Schedule\ScheduleTogetherSport;

class Schedule extends Identifiable
{
    private string $sportsConfigName;

    protected Poule|null $poule = null;

    /**
     * @phpstan-var ArrayCollection<int|string, ScheduleTogetherSport|ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo>|PersistentCollection<int|string, ScheduleTogetherSport|ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo>|SportSchedule[]
     * @psalm-var ArrayCollection<int|string, ScheduleTogetherSport|ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo>
     */
    protected ArrayCollection|PersistentCollection $sportSchedules;

    /**
     * @param int $nrOfPlaces
     */
    public function __construct(protected int $nrOfPlaces)
    {
        $this->sportSchedules = new ArrayCollection();
        $this->sportsConfigName = $this->toJsonCustom();

    }

    public function getNrOfPlaces(): int
    {
        return $this->nrOfPlaces;
    }

    public function setSportSchedules(array $sportSchedules): void {
        $nrOfOneVsOne = 0;
        foreach( $sportSchedules as $sportSchedule ) {
            if( $sportSchedule instanceof ScheduleAgainstOneVsOne ) {
                $nrOfOneVsOne++;
            }
            if(++$nrOfOneVsOne > 1) {
                throw new \Exception('Only 1 OneVsOne allowed');
            }
        }
        $this->sportSchedules = new ArrayCollection($sportSchedules);
        $this->sportsConfigName = $this->toJsonCustom();
    }

//    public function createSportVariantsName(): string
//    {
//        $name = json_encode($this->createSportVariants());
//        return $name === false ? '?' : $name;
//    }

    /**
     * @return Collection<int|string, ScheduleTogetherSport|ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo>
     */
    public function getSportSchedules(): Collection
    {
        return $this->sportSchedules;
    }

    /**
     * @return list<TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo>
     */
    public function createSports(): array
    {
        return array_values(
                array_map( function(ScheduleTogetherSport|ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo $sportSchedule): TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo {
                return $sportSchedule->sport;
            } , $this->getSportSchedules()->toArray() )
        );
    }

    public function toJsonCustom(): string
    {
        $output = new \stdClass();
        $output->nrOfPlaces = $this->nrOfPlaces;
        $output->sports = $this->createSports();
        $json = json_encode($output);
        return $json === false ? '?' : $json;
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
}
