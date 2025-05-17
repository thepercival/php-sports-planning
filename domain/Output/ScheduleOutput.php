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
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainstOneVsOne;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainstOneVsTwo;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsOne;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsTwo;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstTwoVsTwo;
use SportsPlanning\Schedules\Cycles\ScheduleCycleTogether;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsOne;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsTwo;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstTwoVsTwo;

class ScheduleOutput extends OutputHelper
{

    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    /**
     * @param list<ScheduleCycleAgainstOneVsOne|ScheduleCycleAgainstOneVsTwo|ScheduleCycleAgainstTwoVsTwo|ScheduleCycleTogether> $cycles
     * @return void
     */
    public function outputCyclesTotals(int $nrOfPlaces, array $cycles): void
    {
        foreach( $cycles as $cycle )
        {
            $this->outputCycle($cycle);
        }
    }

    public function outputCycle(
        ScheduleCycleAgainstOneVsOne|ScheduleCycleAgainstOneVsTwo|ScheduleCycleAgainstTwoVsTwo|ScheduleCycleTogether $cycle,
        int $sportNumber = null): void
    {
        $prefix = '    ';

        while( $cycle !== null ) {
            if ($sportNumber !== null && $sportNumber !== $cycle->sportSchedule->number) {
                continue;
            }
            $this->logger->info( $prefix . ' nrOfPlaces: ' . $cycle->sportSchedule->scheduleWithNrOfPlaces->nrOfPlaces );
            $this->logger->info( $prefix . ' sportNr: ' . $cycle->sportSchedule->number );
            $this->logger->info( $prefix . ' sport  : ' . json_encode($cycle->sportSchedule->sport) );
            $this->logger->info( $prefix . ' cycle  : ' . $cycle->getNumber() . '/' . $cycle->getLeaf()->getNumber() );

            if( $cycle instanceof ScheduleCycleTogether ) {
                $output = new ScheduleCycleTogetherOutput($this->logger);
                $output->output($cycle);
            } else {
                $rootCyclePart = $cycle->firstPart;

                $output = new ScheduleCyclePartAgainstOutput($this->logger);
                if( $rootCyclePart instanceof ScheduleCyclePartAgainstOneVsTwo) {
                    throw new \Exception('implement');
                }
                $output->output($rootCyclePart, true);
            }

            $cycle = $cycle->getNext();
        }
    }


    public function outputCycleTotals(
        ScheduleCycleAgainstOneVsOne|ScheduleCycleAgainstOneVsTwo|ScheduleCycleAgainstTwoVsTwo|ScheduleCycleTogether $cycle
    ): void
    {
        $hasWithSport = false;
        $hasAgainstSport = false;

        $nrOfPlaces = $cycle->sportSchedule->scheduleWithNrOfPlaces->nrOfPlaces;
        $allScheduleMaps = new AllScheduleMaps($nrOfPlaces);
        $unequalNrOfGames = 0;
        while( $cycle !== null) {
            if ($cycle instanceof ScheduleCycleTogether) {
                continue;
            }
            $sport = $cycle->sportSchedule->sport;
            $hasAgainstSport = true;
            if( $sport->hasMultipleSidePlaces() ) {
                $hasWithSport = true;
            }

            $homeAways = $this->convertGamesToHomeAways($cycle->getAllCyclePartGames());
            $allScheduleMaps->addHomeAways($homeAways);

            $cycle = $cycle->getNext();
        }
        $prefix = '     ';
        $this->logger->info($prefix . 'unEqualNrOfGames: '.$unequalNrOfGames.'x');

//        $amountDifference = $rangedAmountNrCountersReport->getAmountDifference();
        $header = 'Amount Totals :'; // (diff:'.$amountDifference.')
        // Dit kan evt nog worden toegevoegd als er toch met margins gewerkt moet gaan worden
        $allScheduleMaps->getAmountCounterMap()->output($this->logger, $prefix, $header );
        if( $hasWithSport ) {
            // $withAmountDifference = $allScheduleMaps->getWithCounterMap()->calculateReport()->getAmountDifference();
            $header = 'With Totals : '; // (diff'.$withAmountDifference.')
            $allScheduleMaps->getWithCounterMap()->output($this->logger, $prefix, $header);
        }
        if( $hasAgainstSport ) {
            // $againstAmountDifference = $allScheduleMaps->getAgainstCounterMap()->calculateReport()->getAmountDifference();
            $header = 'Against Totals :'; // (diff'.$againstAmountDifference.')
            $allScheduleMaps->getAgainstCounterMap()->output($this->logger, $prefix, $header);
            // $homeAmountDifference = $allScheduleMaps->getHomeCounterMap()->calculateReport()->getAmountDifference();
            $header = 'Home Totals :k'; // (diff'.$homeAmountDifference.')
            $allScheduleMaps->getHomeCounterMap()->output($this->logger, $prefix, $header);
        }
        if( $hasWithSport ) {
            // $withAmountDifference = $allScheduleMaps->getWithCounterMap()->calculateReport()->getAmountDifference();
            $header = 'Together Totals : '; // (diff'.$withAmountDifference.')
            $allScheduleMaps->getTogetherCounterMap()->output($this->logger, $prefix, $header);
        }
    }

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
