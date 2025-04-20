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

class ScheduleWithNrOfPlaces
{
    /**
     * @var list<ScheduleTogetherSport|ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo>
     */
    protected array $sportSchedules = [];

    /**
     * @param int $nrOfPlaces
     * @param list<TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo> $sports
     */
    public function __construct(public int $nrOfPlaces, array $sports)
    {
        foreach($this->sortSports($sports) as $sport) {
            $this->createSportSchedule(count($this->sportSchedules) + 1, $sport);
        }
    }

    /**
     * 1. Together NrOfGamePlaces = null
     * 2. Together NrOfGamePlaces = 1
     * 3. Together NrOfGamePlaces = 2
     * 4. AgainstOneVsOne
     * 5. AgainstOneVsTwo
     * 6. AgainstTwoVsTwo
     *
     * @param list<TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo> $sports
     * @return list<TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo>
     *
     */
    private  function sortSports(array $sports): array
    {
        uasort($sports, function (
            TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo $sportA,
            TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo $sportB): int {

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
        return array_values($sports);
    }

    private  function createSportSchedule(
        int $number,
        TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo $sport
    ) : ScheduleTogetherSport|ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo
    {
        if ( $sport instanceof TogetherSport ) {
            return new ScheduleTogetherSport($this, $number, $sport);
        } else if ( $sport instanceof AgainstOneVsOne ) {
            return new ScheduleAgainstOneVsOne($this, $number, $sport);
        } else if ( $sport instanceof AgainstOneVsTwo ) {
            return new ScheduleAgainstOneVsTwo($this, $number, $sport);
        }
        return new ScheduleAgainstTwoVsTwo($this, $number, $sport);
    }

    public function addSportSchedule(
        ScheduleTogetherSport|ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo $sportSchedule
    ): void {
        $this->sportSchedules[] = $sportSchedule;
    }

    /**
     * @return list<ScheduleTogetherSport|ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo>
     * @throws \Exception
     */
    public function getSportSchedules(): array {
        return $this->sportSchedules;
    }

    /**
     * @param list<ScheduleTogetherSport|ScheduleAgainstOneVsOne|ScheduleAgainstOneVsTwo|ScheduleAgainstTwoVsTwo> $sportSchedules
     * @return void
     * @throws \Exception
     */
    public function setSportSchedules(array $sportSchedules): void {
        $this->sportSchedules = $sportSchedules;
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
        } , $this->sportSchedules );
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
