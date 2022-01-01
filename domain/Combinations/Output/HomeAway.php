<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\Place;

class HomeAway extends OutputHelper
{
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     * @param string|null $prefix
     * @return void
     */
    public function outputHomeAways(array $homeAways, string $prefix = null): void
    {
        foreach ($homeAways as $homeAway) {
            $this->output($homeAway, null, $prefix);
        }
    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     * @return void
     */
    public function outputTotals(array $homeAways): void
    {
        $map = [];
        foreach ($homeAways as $homeAway) {
            foreach ($homeAway->getHome()->getPlaces() as $place) {
                if (!isset($map[$place->getNumber()])) {
                    $map[$place->getNumber()] = 0;
                }
                $map[$place->getNumber()]++;
            }
        }
        $output = 'places nr of home games:';
        foreach ($map as $placeNr => $count) {
            $output .= $placeNr . ':' . $count . ', ';
        }
        $this->logger->info($output);
    }

    public function output(AgainstHomeAway $homeAway, AgainstGameRound|null $gameRound = null, string|null $prefix = null): void
    {
        $useColors = $this->useColors();
        $gameRoundColor = ($useColors && $gameRound !== null) ? ($gameRound->getNumber() % 10) : -1;
        $this->logger->info(
            ($prefix !== null ? $prefix : '') .
            ($gameRound !== null ? $this->getColored($gameRoundColor, 'gameRound ' . $gameRound->getNumber()) .  ', ' : '')
            // . 'substr(' . $game->getRoundNumber(), 2 ) . substr( $game->getSubNumber(), 2 ) . ") "
            . $this->getPlaces($homeAway, $gameRound)
        );
    }

    protected function getPlaces(AgainstHomeAway $homeAway, AgainstGameRound|null $gameRound = null): string
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
        $useColors = $this->useColors();
        $colorNumber = $useColors ? $place->getNumber() : -1;
        return $this->getColored($colorNumber, $place->getLocation());
    }
}
