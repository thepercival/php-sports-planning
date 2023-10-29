<?php

declare(strict_types=1);

namespace SportsPlanning\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Counter;
use SportsHelpers\Output as OutputHelper;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Creator as VariantCreator;
use SportsHelpers\Sport\Variant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\Schedule as ScheduleBase;
use SportsPlanning\Output\Game as GameOutput;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Schedule\Name;

class Schedule extends OutputHelper
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
            $name = new Name(array_values($schedule->createSportVariants()->toArray()));
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
            $this->outputScheduleTotals($schedule);
        }
    }

    public function outputScheduleTotals(ScheduleBase $schedule): void
    {
        $hasWithSport = false;
        $hasAgainstSport = false;

        $sportVariants = array_values($schedule->createSportVariants()->toArray());
        $assignedCounter = new AssignedCounter($schedule->getPoule(), $sportVariants);
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

            $nrOfPlaces = $schedule->getPoule()->getPlaces()->count();
            $variantWithPoule = (new VariantCreator())->createWithPoule($nrOfPlaces, $sportVariant);

            if( $variantWithPoule instanceof AgainstGppWithPoule && !$variantWithPoule->allPlacesSameNrOfGamesAssignable() ){
                $unequalNrOfGames++;
            }
            $homeAways = $sportSchedule->convertGamesToHomeAways();
            $assignedCounter->assignHomeAways($homeAways);
        }
        $prefix = '        ';
        $this->logger->info($prefix . 'unEqualNrOfGames: '.$unequalNrOfGames.'x');

        $this->logger->info('');
        $amountDifference = $assignedCounter->getAmountDifference();
        $header = 'Amount Totals (diff:'.$amountDifference.')';
        $assignedCounter->getAssignedMap()->output($this->logger, $prefix, $header );
        $this->logger->info('');

        if( $hasAgainstSport ) {
            $this->logger->info('');
            $againstAmountDifference = $assignedCounter->getAgainstAmountDifference();
            $header = 'Against Totals (diff:'.$againstAmountDifference.')';
            $assignedCounter->getAssignedAgainstMap()->output($this->logger, $prefix, $header);
        }
        if( $hasWithSport ) {
            $this->logger->info('');
            $withAmountDifference = $assignedCounter->getWithAmountDifference();
            $header = 'With Totals (diff:'.$withAmountDifference.')';
            $assignedCounter->getAssignedWithMap()->output($this->logger, $prefix, $header);
        }
        if( $hasAgainstSport ) {
            $this->logger->info('');
            $homeAmountDifference = $assignedCounter->getHomeAmountDifference();
            $header = 'Home Totals (diff:'.$homeAmountDifference.')';
            $assignedCounter->getAssignedHomeMap()->output($this->logger, $prefix, $header);
        }
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
     * @param list<PlaceCombinationCounter> $assignedAgainstMap
     * @param string $prefix
     * @return void
     */
    public function outputPlaceCombinations(array $assignedAgainstMap, string $prefix): void
    {
        $amountPerLine = 8; $counter = 0; $line = '';
        foreach( $assignedAgainstMap as $placeCombinationCounter ) {
            $line .= $placeCombinationCounter->getPlaceCombination() . ' ' . $placeCombinationCounter->count() . 'x, ';
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
