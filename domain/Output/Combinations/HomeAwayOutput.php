<?php

declare(strict_types=1);

namespace SportsPlanning\Output\Combinations;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output\Color;
use SportsHelpers\Output\OutputAbstract;
use SportsPlanning\Combinations\HomeAway as HomeAwayBase;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\Place;

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
        $map = $this->convertToAgainstPlaceCombinationMap($homeAways);
        $this->outputTotalsHelpers($map);
    }

    /**
     * @param list<HomeAwayBase> $homeAways
     * @return array<string, PlaceCombinationCounter> $map
     */
    protected function convertToAgainstPlaceCombinationMap(array $homeAways): array {
        $map = [];
        foreach ($homeAways as $homeAway) {
            foreach( $homeAway->getAgainstPlaceCombinations() as $withPlaceCombincation ) {
                if( !array_key_exists($withPlaceCombincation->getIndex(), $map)) {
                    $map[$withPlaceCombincation->getIndex()] = new PlaceCombinationCounter($withPlaceCombincation);
                }
                $map[$withPlaceCombincation->getIndex()]->increment();
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

        $map = $this->convertToWithPlaceCombinationMap($homeAways);
        $this->outputTotalsHelpers($map);
    }

    /**
     * @param list<HomeAwayBase> $homeAways
     * @return array<string, PlaceCombinationCounter> $map
     */
    protected function convertToWithPlaceCombinationMap(array $homeAways): array {
        $map = [];
        foreach ($homeAways as $homeAway) {
            foreach( $homeAway->getWithPlaceCombinations() as $withPlaceCombincation ) {
                if( !array_key_exists($withPlaceCombincation->getIndex(), $map)) {
                    $map[$withPlaceCombincation->getIndex()] = new PlaceCombinationCounter($withPlaceCombincation);
                }
                $map[$withPlaceCombincation->getIndex()]->increment();
            }
        }
        return $map;
    }

    /**
     * @param array<string, PlaceCombinationCounter> $map
     * @return void
     */
    public function outputTotalsHelpers(array $map): void {
        $amountPerLine = 4; $counter = 0; $line = '';
        foreach( $map as $counterIt ) {
            $line .= ((string)$counterIt->getPlaceCombination()) . ' ' . $counterIt->count() . 'x, ';
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
            foreach ($homeAway->getHome()->getPlaces() as $place) {
                if (!isset($map[$place->getPlaceNr()])) {
                    $map[$place->getPlaceNr()] = 0;
                }
                $map[$place->getPlaceNr()]++;
            }
        }
        $output = 'places nr of home games:';
        foreach ($map as $placeNr => $count) {
            $output .= $placeNr . ':' . $count . ', ';
        }
        $this->logger->info($output);
    }


    public function output(HomeAwayBase $homeAway, AgainstGameRound|null $gameRound = null, string|null $prefix = null): void
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

    protected function getPlaces(HomeAwayBase $homeAway, AgainstGameRound|null $gameRound = null): string
    {
        $homeGamePlaces = $this->getPlacesHelper($homeAway->getHome());
        $awayGamePlaces = $this->getPlacesHelper($homeAway->getAway());
        return $homeGamePlaces . ' vs ' . $awayGamePlaces;
    }

    protected function getPlacesHelper(PlaceCombination $placeCombination): string
    {
        $placesAsArrayOfStrings = array_map(
            function (Place $place): string {
                return $this->getPlace($place);
            },
            $placeCombination->getPlaces()
        );
        return implode(' & ', $placesAsArrayOfStrings);
    }

    protected function getPlace(Place $place): string
    {
        $colorNumber = $place->getPlaceNr();
        $color = $this->convertNumberToColor($colorNumber);
        return $this->getColoredString($color, (string)$place);
    }
}
