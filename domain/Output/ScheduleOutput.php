<?php

declare(strict_types=1);

namespace SportsPlanning\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\Counter;
use SportsHelpers\Output\OutputAbstract;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Creator as VariantCreator;
use SportsHelpers\Sport\Variant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceNrCombination;
use SportsPlanning\Combinations\PlaceNrCombinationCounter;
use SportsPlanning\Input;
use SportsPlanning\PlanningRefereeInfo;
use SportsPlanning\Schedules\Schedule;
use SportsPlanning\Schedules\ScheduleGame;
use SportsPlanning\Schedules\ScheduleName;

final class ScheduleOutput extends OutputAbstract
{

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    /**
     * @param list<Schedule> $schedules
     * @param int|null $sportNumber
     */
    public function output(array $schedules, int|null $sportNumber = null): void
    {
        foreach ($schedules as $schedule) {
            $prefix = '    ';
            $name = new ScheduleName($schedule->createSportVariants());
            $this->logger->info( $prefix . ' schedule => nrOfPlaces: ' . $schedule->getNrOfPlaces() . ' , name: "' . ((string)$name) . '"');
            foreach ($schedule->getSportSchedules() as $sportSchedule) {
                if ($sportNumber !== null && $sportNumber !== $sportSchedule->getNumber()) {
                    continue;
                }
                $this->logger->info(
                    $prefix . '    sportschedule => sportNr: ' . $sportSchedule->getNumber() .
                    ' , variant: "' . ((string)$sportSchedule->createVariant()) . '"'
                );
                foreach ($sportSchedule->getGames() as $gameRoundGame) {
                    $this->logger->info('            ' . ((string)$gameRoundGame));
                }
            }
        }
    }

    /**
     * @param list<Schedule> $schedules
     */
    public function outputTotals(array $schedules): void
    {
        foreach ($schedules as $schedule) {
            $tmpInput = new Input(new Input\Configuration(
                new PouleStructure($schedule->getNrOfPlaces()),
                $schedule->createSportVariantWithFields(),
                new PlanningRefereeInfo(), false
            ));
            $this->outputScheduleTotals(count($tmpInput->getFirstPoule()->getPlaces()), $schedule);
        }
    }

    public function outputScheduleTotals(int $nrOfPlaces, Schedule $schedule): void
    {
        $hasWithSport = false;
        $hasAgainstSport = false;

        $sportVariants = $schedule->createSportVariants();
        $assignedCounter = new AssignedCounter($nrOfPlaces, $sportVariants);
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

            $variantWithPoule = (new VariantCreator())->createWithPoule($schedule->getNrOfPlaces(), $sportVariant);

            if( $variantWithPoule instanceof AgainstGppWithPoule && !$variantWithPoule->allPlacesSameNrOfGamesAssignable() ){
                $unequalNrOfGames++;
            }
            $homeAways = $this->convertGamesToHomeAways(array_values( $sportSchedule->getGames()->toArray()));
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
            $header = 'ScheduleAgainstGameRound Totals (diff:'.$againstAmountDifference.')';
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

    /**
     * @param list<ScheduleGame> $scheduleGames
     * @return list<HomeAway>
     */
    public function convertGamesToHomeAways(array $scheduleGames): array {
        return array_map( function(ScheduleGame $game): HomeAway {
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
     * @param list<PlaceNrCombinationCounter> $assignedAgainstMap
     * @param string $prefix
     * @return void
     */
    public function outputPlaceNrCombinations(array $assignedAgainstMap, string $prefix): void
    {
        $amountPerLine = 8; $counter = 0; $line = '';
        foreach( $assignedAgainstMap as $placeNrCombinationCounter ) {
            $line .= ((string)$placeNrCombinationCounter->getPlaceNrCombination()) .
                ' ' . $placeNrCombinationCounter->count() . 'x, ';
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
