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
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Counters\CounterForPlaceCombination;
use SportsPlanning\Counters\Maps\Schedule\AllScheduleMaps;
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
            $tmpInput = new Input(new Input\Configuration(
                new PouleStructure($schedule->getNrOfPlaces()),
                $schedule->createSportVariantWithFields(),
                new Info(), false
            ));
            $this->outputScheduleTotals($tmpInput->getFirstPoule(), $schedule);
        }
    }

    public function outputScheduleTotals(Poule $poule, ScheduleBase $schedule): void
    {
        $hasWithSport = false;
        $hasAgainstSport = false;

        $sportVariants = $schedule->createSportVariants();
        $allScheduleMaps = new AllScheduleMaps($poule, $sportVariants);
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
            $homeAways = $this->convertGamesToHomeAways($poule, array_values( $sportSchedule->getGames()->toArray()));
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
     * @param Poule $poule
     * @param list<ScheduleBase\Game> $scheduleGames
     * @return list<HomeAway>
     */
    public function convertGamesToHomeAways(Poule $poule, array $scheduleGames): array {
        return array_map( function(ScheduleBase\Game $game) use($poule): HomeAway {
            return $this->gameToHomeAway( $game, $poule );
        }, $scheduleGames );
    }

    public function gameToHomeAway(ScheduleBase\Game $game, Poule $poule): HomeAway {
        $homePlaceNrs = $game->getSidePlaceNrs(AgainstSide::Home);
        $homePlaces = array_map( function(int $placeNr) use($poule): \SportsPlanning\Place {
                return $poule->getPlace($placeNr);
        }, $homePlaceNrs );
        $awayPlaceNrs = $game->getSidePlaceNrs(AgainstSide::Away);
        $awayPlaces = array_map( function(int $placeNr) use($poule): \SportsPlanning\Place {
            return $poule->getPlace($placeNr);
        }, $awayPlaceNrs );

        return new HomeAway( new PlaceCombination( $homePlaces ), new PlaceCombination( $awayPlaces ) );
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
     * @param list<CounterForPlaceCombination> $assignedAgainstMap
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
