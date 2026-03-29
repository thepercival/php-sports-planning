<?php

declare(strict_types=1);

namespace SportsPlanning\Output\Combinations;

use old\GameRounds\AgainstGameRound;
use Psr\Log\LoggerInterface;
use SportsHelpers\Output\OutputAbstract;
use SportsPlanning\Combinations\HomeAway as HomeAwayBase;
use SportsPlanning\Combinations\PlaceNrCombination;
use SportsPlanning\Combinations\PlaceNrCombinationCounter;
use SportsPlanning\Place;
use SportsPlanning\Schedules\GameRounds\ScheduleAgainstGameRound;

final class HomeAwayOutput extends OutputAbstract
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    /**
     * @param list<HomeAwayBase> $homeAways
     * @param string|null $prefix
     * @return void
     */
    public function outputHomeAways(array $homeAways, string|null $prefix = null): void
    {
        foreach ($homeAways as $homeAway) {
            $this->output($homeAway, null, $prefix);
        }
    }

    /**
     * @param list<HomeAwayBase> $homeAways
     * @param bool $againstTotals
     * @param bool $withTotals
     * @param bool $homeTotals
     * @return void
     */
    public function outputTotalDetails(array $homeAways,
                                       bool $againstTotals, bool $withTotals, bool $homeTotals): void {
        if( $againstTotals ) {
            $this->outputAgainstTotals($homeAways);
        }
        if( $withTotals ) {
            $this->outputWithTotals($homeAways);
        }
        if( $homeTotals ) {
            $this->outputWithTotals($homeAways);
        }
    }

    /**
     * @param list<HomeAwayBase> $homeAways $homeAways
     * @return void
     */
    public function outputAgainstTotals(array $homeAways): void {
        $header = 'AgainstTotals';
        $this->logger->info($header);
        $map = $this->convertToAgainstPlaceNrCombinationMap($homeAways);
        $this->outputTotalsHelpers($map);
    }

    /**
     * @param list<HomeAwayBase> $homeAways
     * @return array<string, PlaceNrCombinationCounter> $map
     */
    protected function convertToAgainstPlaceNrCombinationMap(array $homeAways): array {
        $map = [];
        foreach ($homeAways as $homeAway) {
            foreach( $homeAway->getAgainstPlaceNrCombinations() as $withPlaceNrCombincation ) {
                if( !array_key_exists($withPlaceNrCombincation->getIndex(), $map)) {
                    $map[$withPlaceNrCombincation->getIndex()] = new PlaceNrCombinationCounter($withPlaceNrCombincation);
                }
                $map[$withPlaceNrCombincation->getIndex()]->increment();
            }
        }
        return $map;
    }

    /**
     * @param list<HomeAwayBase> $homeAways
     * @return void
     */
    public function outputWithTotals(array $homeAways): void {
        if(count($homeAways) === 0) {
            return;
        }
        $header = '==== WithTotals ====';
        $this->logger->info($header);

        $map = $this->convertToWithPlaceNrCombinationMap($homeAways);
        $this->outputTotalsHelpers($map);
    }

    /**
     * @param list<HomeAwayBase> $homeAways
     * @return array<string, PlaceNrCombinationCounter> $map
     */
    protected function convertToWithPlaceNrCombinationMap(array $homeAways): array {
        $map = [];
        foreach ($homeAways as $homeAway) {
            foreach( $homeAway->getWithPlaceNrCombinations() as $withPlaceNrCombincation ) {
                if( !array_key_exists($withPlaceNrCombincation->getIndex(), $map)) {
                    $map[$withPlaceNrCombincation->getIndex()] = new PlaceNrCombinationCounter($withPlaceNrCombincation);
                }
                $map[$withPlaceNrCombincation->getIndex()]->increment();
            }
        }
        return $map;
    }

    /**
     * @param array<string, PlaceNrCombinationCounter> $map
     * @return void
     */
    public function outputTotalsHelpers(array $map): void {
        $amountPerLine = 4; $counter = 0; $line = '';
        foreach( $map as $counterIt ) {
            $line .= ((string)$counterIt->getPlaceNrCombination()) . ' ' . $counterIt->count() . 'x, ';
            if( ++$counter === $amountPerLine ) {
                $this->logger->info('    ' . $line);
                $counter = 0;
                $line = '';
            }
        }
        if( strlen($line) > 0 ) {
            $this->logger->info('    ' . $line);
        }
    }

    /**
     * @param list<HomeAwayBase> $homeAways
     * @return void
     */
    public function outputHomeTotals(array $homeAways): void
    {
        $map = [];
        foreach ($homeAways as $homeAway) {
            foreach ($homeAway->getHome()->getPlaceNrs() as $homePlaceNr) {
                if (!isset($map[$homePlaceNr])) {
                    $map[$homePlaceNr] = 0;
                }
                $map[$homePlaceNr]++;
            }
        }
        $output = 'places nr of home games:';
        foreach ($map as $placeNr => $count) {
            $output .= $placeNr . ':' . $count . ', ';
        }
        $this->logger->info($output);
    }


    public function output(HomeAwayBase $homeAway, ScheduleAgainstGameRound|null $gameRound = null, string|null $prefix = null): void
    {
        $gameRoundColorNr = $gameRound !== null ? ($gameRound->getNumber() % 10) : -1;
        $gameRoundColor = $this->convertNumberToColor($gameRoundColorNr);
        $this->logger->info(
            ($prefix !== null ? $prefix : '') .
            ($gameRound !== null ? $this->getColoredString(
                    $gameRoundColor,
                    'gameRound ' . $gameRound->getNumber()
                ) . ', ' : '')
            // . 'substr(' . $game->getRoundNumber(), 2 ) . substr( $game->getSubNumber(), 2 ) . ") "
            . $this->getPlaces($homeAway, $gameRound)
        );
    }

    protected function getPlaces(HomeAwayBase $homeAway, ScheduleAgainstGameRound|null $gameRound = null): string
    {
        $homeGamePlaces = $this->getPlacesHelper($homeAway->getHome());
        $awayGamePlaces = $this->getPlacesHelper($homeAway->getAway());
        return $homeGamePlaces . ' vs ' . $awayGamePlaces;
    }

    protected function getPlacesHelper(PlaceNrCombination $placeNrCombination): string
    {
        return implode(' & ', $placeNrCombination->getPlaceNrs());
    }

    protected function getPlace(Place $place): string
    {
        $colorNumber = $place->getPlaceNr();
        $color = $this->convertNumberToColor($colorNumber);
        return $this->getColoredString($color, (string)$place);
    }
}
