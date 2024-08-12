<?php

declare(strict_types=1);

namespace SportsPlanning\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\Counter;
use SportsHelpers\Output as OutputHelper;
use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Creator as VariantCreator;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\GamesPerPlace as AgainstGppWithNrOfPlaces;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\CounterForDuoPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\AllScheduleMaps;
use SportsPlanning\HomeAways\HomeAwayAbstract;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Input;
use SportsPlanning\Poule;
use SportsPlanning\Referee\Info;
use SportsPlanning\Schedule as ScheduleBase;
use SportsPlanning\Schedule\Name;

class ScheduleOutput extends OutputHelper
{

    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    /**
     * @param list<ScheduleBase> $schedules
     * @param int|null $sportNumber
     */
    public function output(array $schedules, int $sportNumber = null): void
    {
        foreach ($schedules as $schedule) {
            $prefix = '    ';
            $name = new Name($schedule->createSportVariants());
            $this->logger->info( $prefix . ' schedule => nrOfPlaces: ' . $schedule->getNrOfPlaces() . ' , name: "' . $name . '"');
            foreach ($schedule->getSportSchedules() as $sportSchedule) {
                if ($sportNumber !== null && $sportNumber !== $sportSchedule->getNumber()) {
                    continue;
                }
                $this->logger->info( $prefix . '    sportschedule => sportNr: ' . $sportSchedule->getNumber() . ' , variant: "' . $sportSchedule->createVariant() . '"');
                foreach ($sportSchedule->getGames() as $gameRoundGame) {
                    $this->logger->info('            ' . $gameRoundGame);
                }
            }
        }
    }

    /**
     * @param list<ScheduleBase> $schedules
     */
    public function outputTotals(array $schedules): void
    {
        foreach ($schedules as $schedule) {
            $this->outputScheduleTotals($schedule->getNrOfPlaces(), $schedule);
        }
    }

    public function outputScheduleTotals(int $nrOfPlaces, ScheduleBase $schedule): void
    {
        $hasWithSport = false;
        $hasAgainstSport = false;

        $allScheduleMaps = new AllScheduleMaps($nrOfPlaces);
        $unequalNrOfGames = 0;
        foreach ($schedule->getSportSchedules() as $sportSchedule) {
            $sportVariant = $sportSchedule->createVariant();
            if (!($sportVariant instanceof AgainstGpp || $sportVariant instanceof AgainstH2h)) {
                continue;
            }
            $hasAgainstSport = true;
            if( $sportVariant->hasMultipleSidePlaces() ) {
                $hasWithSport = true;
            }

            $variantWithNrOfPlaces = (new VariantCreator())->createWithNrOfPlaces($schedule->getNrOfPlaces(), $sportVariant);

            if( $variantWithNrOfPlaces instanceof AgainstGppWithNrOfPlaces && !$variantWithNrOfPlaces->allPlacesSameNrOfGamesAssignable() ){
                $unequalNrOfGames++;
            }
            $homeAways = $this->convertGamesToHomeAways(array_values( $sportSchedule->getGames()->toArray()));
            $allScheduleMaps->addHomeAways($homeAways);
        }
        $prefix = '        ';
        $this->logger->info($prefix . 'unEqualNrOfGames: '.$unequalNrOfGames.'x');

        $this->logger->info('');
        $amountDifference = $allScheduleMaps->getAmountCounterMap()->calculateReport()->getAmountDifference();
        $header = 'Amount Totals (diff:'.$amountDifference.')';
        $allScheduleMaps->getAmountCounterMap()->output($this->logger, $prefix, $header );
        $this->logger->info('');

        if( $hasWithSport ) {
            $this->logger->info('');
            $withAmountDifference = $allScheduleMaps->getWithCounterMap()->calculateReport()->getAmountDifference();
            $header = 'With Totals (diff:'.$withAmountDifference.')';
            $allScheduleMaps->getWithCounterMap()->output($this->logger, $prefix, $header);
        }
        if( $hasAgainstSport ) {
            $this->logger->info('');
            $againstAmountDifference = $allScheduleMaps->getAgainstCounterMap()->calculateReport()->getAmountDifference();
            $header = 'Against Totals (diff:'.$againstAmountDifference.')';
            $allScheduleMaps->getAgainstCounterMap()->output($this->logger, $prefix, $header);
        }
        if( $hasAgainstSport ) {
            $this->logger->info('');
            $homeAmountDifference = $allScheduleMaps->getHomeCounterMap()->calculateReport()->getAmountDifference();
            $header = 'Home Totals (diff:'.$homeAmountDifference.')';
            $allScheduleMaps->getHomeCounterMap()->output($this->logger, $prefix, $header);
        }
    }

    /**
     * @param list<ScheduleBase\Game> $scheduleGames
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    public function convertGamesToHomeAways(array $scheduleGames): array {
        return array_map( function(ScheduleBase\Game $game): OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway {
            return $game->convertToHomeAway();
        }, $scheduleGames );
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
