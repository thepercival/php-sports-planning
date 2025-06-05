<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules;

use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsOne;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsTwo;
use SportsPlanning\Schedules\Sports\ScheduleAgainstTwoVsTwo;
use SportsPlanning\Schedules\Sports\ScheduleTogetherSport;
use SportsPlanning\Sports\SportWithNrOfCycles;

final readonly class ScheduleWithNrOfPlaces
{
    /**
     * @var array<int, ScheduleTogetherSport|ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo>
     */
    protected array $sportSchedules;

    /**
     * @param int $nrOfPlaces
     * @param list<SportWithNrOfCycles> $sportsWithNrOfCycles
     */
    public function __construct(public int $nrOfPlaces, array $sportsWithNrOfCycles)
    {
        $sportSchedules = [];
        foreach($this->sortSports($sportsWithNrOfCycles) as $sportWithNrOfCycles) {
            $sportSchedule = $this->createSportSchedule(count($sportSchedules) + 1, $sportWithNrOfCycles);
            $sportSchedules[$sportSchedule->number] = $sportSchedule;
        }
        $this->sportSchedules = $sportSchedules;
    }

    /**
     * 1. Together NrOfGamePlaces = null
     * 2. Together NrOfGamePlaces = 1
     * 3. Together NrOfGamePlaces = 2
     * 4. AgainstOneVsOne
     * 5. AgainstOneVsTwo
     * 6. AgainstTwoVsTwo
     *
     * @param list<SportWithNrOfCycles> $sportsWithNrOfCycles
     * @return list<SportWithNrOfCycles>
     *
     */
    private  function sortSports(array $sportsWithNrOfCycles): array
    {
        uasort($sportsWithNrOfCycles, function (
            SportWithNrOfCycles $sportWithNrOfCyclesA,
            SportWithNrOfCycles $sportWithNrOfCyclesB): int {
            $sportA = $sportWithNrOfCyclesA->sport;
            $sportB = $sportWithNrOfCyclesB->sport;
            if ( $sportA instanceof TogetherSport && $sportB instanceof TogetherSport ) {
                return ($sportA->getNrOfGamePlaces() ?? 0) - ( $sportB->getNrOfGamePlaces() ?? 0);
            }
            else if ( $sportA instanceof TogetherSport ) {
                return -1;
            }
            else if ( $sportB instanceof TogetherSport ) {
                return 1;
            }
            else if ( $sportA instanceof AgainstOneVsOne && !($sportB instanceof AgainstOneVsOne) ) {
                return -1;
            }
            else if ( !($sportA instanceof AgainstOneVsOne) && $sportB instanceof AgainstOneVsOne ) {
                return 1;
            }
            else if ( $sportA instanceof AgainstOneVsTwo && !($sportB instanceof AgainstOneVsTwo) ) {
                return -1;
            }
            else if ( !($sportA instanceof AgainstOneVsTwo) && $sportB instanceof AgainstOneVsTwo ) {
                return 1;
            }
            return 0;
        });
        return array_values($sportsWithNrOfCycles);
    }

    private  function createSportSchedule(
        int $number,
        SportWithNrOfCycles $sportWithNrOfCycles
    ) : ScheduleTogetherSport|ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo
    {
        $sport = $sportWithNrOfCycles->sport;
        $nrOfCycles = $sportWithNrOfCycles->nrOfCycles;
        if ( $sport instanceof TogetherSport ) {
            return new ScheduleTogetherSport($this, $number, $sport, $nrOfCycles);
        } else if ( $sport instanceof AgainstOneVsOne ) {
            return new ScheduleAgainstOneVsOne($this, $number, $sport, $nrOfCycles);
        } else if ( $sport instanceof AgainstOneVsTwo ) {
            return new ScheduleAgainstOneVsTwo($this, $number, $sport, $nrOfCycles);
        }
        return new ScheduleAgainstTwoVsTwo($this, $number, $sport, $nrOfCycles);
    }

    /**
     * @return list<ScheduleTogetherSport|ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo>
     * @throws \Exception
     */
    public function getSportSchedules(): array {
        return array_values($this->sportSchedules);
    }

    public function getSportSchedule(int $number): ScheduleTogetherSport|ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo {
        if( !array_key_exists($number, $this->sportSchedules)) {
            throw new \Exception('unknown sportnr');
        }
        return $this->sportSchedules[$number];
    }

    /**
     * @return list<ScheduleTogetherSport>
     * @throws \Exception
     */
    public function getTogetherSportSchedules(): array {

        return array_values( array_filter(
            array_map( function($sportSchedule): ScheduleTogetherSport|null {
                return ($sportSchedule instanceof ScheduleTogetherSport ) ? $sportSchedule : null;
            }, $this->getSportSchedules() ),
            function(ScheduleTogetherSport|null $scheduleTogetherSport ): bool {
                return $scheduleTogetherSport instanceof ScheduleTogetherSport;
            }
        ) );
    }

    /**
     * @return list<ScheduleAgainstOneVsOne>
     * @throws \Exception
     */
    public function getAgainstOneVsOneSchedules(): array {

        return array_values( array_filter(
            array_map( function($sportSchedule): ScheduleAgainstOneVsOne|null {
                return ($sportSchedule instanceof ScheduleAgainstOneVsOne ) ? $sportSchedule : null;
            }, $this->getSportSchedules() ),
            function(ScheduleAgainstOneVsOne|null $scheduleAgainstSport ): bool {
                return $scheduleAgainstSport !== null;
            }
        ) );
    }

    /**
     * @return list<ScheduleAgainstOneVsTwo>
     * @throws \Exception
     */
    public function getAgainstOneVsTwoSchedules(): array {

        return array_values( array_filter(
            array_map( function($sportSchedule): ScheduleAgainstOneVsTwo|null {
                return ($sportSchedule instanceof ScheduleAgainstOneVsTwo ) ? $sportSchedule : null;
            }, $this->getSportSchedules() ),
            function(ScheduleAgainstOneVsTwo|null $scheduleAgainstSport ): bool {
                return $scheduleAgainstSport !== null;
            }
        ) );
    }

    /**
     * @return list<ScheduleAgainstTwoVsTwo>
     * @throws \Exception
     */
    public function getAgainstTwoVsTwoSchedules(): array {

        return array_values( array_filter(
            array_map( function($sportSchedule): ScheduleAgainstTwoVsTwo|null {
                return ($sportSchedule instanceof ScheduleAgainstTwoVsTwo ) ? $sportSchedule : null;
            }, $this->getSportSchedules() ),
            function(ScheduleAgainstTwoVsTwo|null $scheduleAgainstSport ): bool {
                return $scheduleAgainstSport !== null;
            }
        ) );
    }

    /**
     * @return list<ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo>
     * @throws \Exception
     */
    public function getAgainstSportSchedules(): array {

        return array_values( array_filter(
            array_map( function($sportSchedule): ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo|null {
                return ($sportSchedule instanceof ScheduleTogetherSport ) ? null : $sportSchedule;
            }, $this->getSportSchedules() ),
            function(ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo|null $scheduleAgainstSport ): bool {
                return $scheduleAgainstSport !== null;
            }
        ) );
    }


//    /**
//     * @param int $nrOfPlaces
//     * @param list<ScheduleTogetherSport|ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo> $sportSchedules
//     * @return bool
//     */
//    public function equals(int $nrOfPlaces, array $sports): bool {
//        if( $nrOfPlaces !== $this->nrOfPlaces ) {
//            return false;
//        }
//        return json_encode($this->createSports()) === json_encode($sports);
//    }

//    public function createSportVariantsName(): string
//    {
//        $name = json_encode($this->createSportVariants());
//        return $name === false ? '?' : $name;
//    }

    /**
     * @return list<TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo>
     */
    public function createSports(): array
    {
        return array_map( function(ScheduleTogetherSport|ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo $sportSchedule): TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo {
            return $sportSchedule->sport;
        } , array_values($this->sportSchedules ) );
    }

    public function createJson(): string
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
