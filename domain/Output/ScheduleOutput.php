<?php

declare(strict_types=1);

namespace SportsPlanning\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Counter;
use SportsHelpers\Output as OutputHelper;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Counters\CounterForDuoPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\AllScheduleMaps;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Schedules as ScheduleBase;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsOne;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsTwo;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstTwoVsTwo;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Schedules\Sports\ScheduleTogetherSport;

class ScheduleOutput extends OutputHelper
{

    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

//    /**
//     * @param list<ScheduleWithNrOfPlaces> $schedules
//     * @param int|null $sportNumber
//     */
//    public function output(array $schedules, int $sportNumber = null): void
//    {
//        foreach ($schedules as $schedule) {
//            $prefix = '    ';
//            $this->logger->info( $prefix . ' schedule => ' . $schedule->createJson() );
//            foreach ($schedule->getSportSchedules() as $sportSchedule) {
//                if ($sportNumber !== null && $sportNumber !== $sportSchedule->number) {
//                    continue;
//                }
//                if( $sportSchedule instanceof ScheduleTogetherSport) {
//                    $sport = 'together(' . ($sportSchedule->sport->getNrOfGamePlaces() ?? 'null') . ')';
//                } else {
//                    $sport = 'against(' . $sportSchedule->sport->nrOfHomePlaces . 'vs' . $sportSchedule->sport->nrOfAwayPlaces . ')';
//                }
//
////    }
//                $this->logger->info( $prefix . '    sportschedule => sportNr: ' . $sportSchedule->number . ' , sport: "' . $sport . '"');
//                $sportSchedule->
//                foreach ($sportSchedule->getGames() as $gameRoundGame) {
//                    $this->logger->info('            ' . $gameRoundGame);
//                }
//            }
//        }
//    }

//    /**
//     * @param list<ScheduleWithNrOfPlaces> $schedules
//     */
//    public function outputTotals(array $schedules): void
//    {
//        foreach ($schedules as $schedule) {
//            $this->outputScheduleTotals($schedule->nrOfPlaces, $schedule);
//        }
//    }
//
//    public function outputScheduleTotals(int $nrOfPlaces, ScheduleWithNrOfPlaces $schedule): void
//    {
//        $hasWithSport = false;
//        $hasAgainstSport = false;
//
//        $allScheduleMaps = new AllScheduleMaps($nrOfPlaces);
//        $unequalNrOfGames = 0;
//        foreach ($schedule->getSportSchedules() as $sportSchedule) {
//            if ($sportSchedule instanceof ScheduleTogetherSport) {
//                continue;
//            }
//            $hasAgainstSport = true;
//            if( $sportSchedule->sport->hasMultipleSidePlaces() ) {
//                $hasWithSport = true;
//            }
//
//            $homeAways = $this->convertGamesToHomeAways($sportSchedule->getGames());
//            $allScheduleMaps->addHomeAways($homeAways);
//        }
//        $prefix = '         ';
//        $this->logger->info($prefix . 'unEqualNrOfGames: '.$unequalNrOfGames.'x');
//
////        $amountDifference = $rangedAmountNrCountersReport->getAmountDifference();
//        $header = 'Amount Totals :'; // (diff:'.$amountDifference.')
//        // Dit kan evt nog worden toegevoegd als er toch met margins gewerkt moet gaan worden
//        $allScheduleMaps->getAmountCounterMap()->output($this->logger, $prefix, $header );
//        if( $hasWithSport ) {
//            // $withAmountDifference = $allScheduleMaps->getWithCounterMap()->calculateReport()->getAmountDifference();
//            $header = 'With Totals : '; // (diff'.$withAmountDifference.')
//            $allScheduleMaps->getWithCounterMap()->output($this->logger, $prefix, $header);
//        }
//        if( $hasAgainstSport ) {
//            // $againstAmountDifference = $allScheduleMaps->getAgainstCounterMap()->calculateReport()->getAmountDifference();
//            $header = 'Against Totals :'; // (diff'.$againstAmountDifference.')
//            $allScheduleMaps->getAgainstCounterMap()->output($this->logger, $prefix, $header);
//            // $homeAmountDifference = $allScheduleMaps->getHomeCounterMap()->calculateReport()->getAmountDifference();
//            $header = 'Home Totals :k'; // (diff'.$homeAmountDifference.')
//            $allScheduleMaps->getHomeCounterMap()->output($this->logger, $prefix, $header);
//        }
//        if( $hasWithSport ) {
//            // $withAmountDifference = $allScheduleMaps->getWithCounterMap()->calculateReport()->getAmountDifference();
//            $header = 'Together Totals : '; // (diff'.$withAmountDifference.')
//            $allScheduleMaps->getTogetherCounterMap()->output($this->logger, $prefix, $header);
//        }
//    }

    /**
     * @param list<ScheduleGameAgainstOneVsOne|ScheduleGameAgainstOneVsTwo|ScheduleGameAgainstTwoVsTwo> $scheduleAgainstGames
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    public function convertGamesToHomeAways(array $scheduleAgainstGames): array {
        return array_map(
            function(
                ScheduleGameAgainstOneVsOne|ScheduleGameAgainstOneVsTwo|ScheduleGameAgainstTwoVsTwo $scheduleAgainstGame
            ): OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway {
            return $scheduleAgainstGame->convertToHomeAway();
        }, $scheduleAgainstGames );
    }

//    /**
//     * @param array<int, Counter> $assignedNrOfGames
//     * @param AgainstGame|TogetherGame $game
//     * @return void
//     */
//    protected function addToAssignedNrOfGames(array &$assignedNrOfGames, AgainstGame|TogetherGame $game): void
//    {
//        foreach ($game->getPlaces() as $gamePlace) {
//            if( !array_key_exists($gamePlace->getNumber(), $assignedNrOfGames)) {
//                $assignedNrOfGames[$gamePlace->getNumber()] = new Counter($gamePlace);
//            }
//            $assignedNrOfGames[$gamePlace->getNumber()]->increment();
//        }
//    }


    /**
     * @param list<Counter> $assignedNrOfGames
     * @param string $prefix
     * @return void
     */
    public function outputAssignedNrOfGames(array $assignedNrOfGames, string $prefix): void
    {
        $this->logger->info( $prefix . ' AssignedTotals');

        $amountPerLine = 8; $counter = 0; $line = '';
        foreach( $assignedNrOfGames as $placeNumber => $counterIt ) {
            $line .= $placeNumber . ' ' . $counterIt->count() . 'x, ';
            if( ++$counter === $amountPerLine ) {
                $this->logger->info($prefix . $line);
                $counter = 0;
                $line = '';
            }
        }
        if( strlen($line) > 0 ) {
            $this->logger->info($prefix . $line);
        }
    }

    /**
     * @param list<CounterForDuoPlaceNr> $assignedAgainstMap
     * @param string $prefix
     * @return void
     */
    public function outputPlaceCombinations(array $assignedAgainstMap, string $prefix): void
    {
        $amountPerLine = 8; $counter = 0; $line = '';
        foreach( $assignedAgainstMap as $duoPlaceCounter ) {
            $line .= $duoPlaceCounter . 'x, ';
            if( ++$counter === $amountPerLine ) {
                $this->logger->info($prefix . $line);
                $counter = 0;
                $line = '';
            }
        }
        if( strlen($line) > 0 ) {
            $this->logger->info($prefix . $line);
        }
    }

}
