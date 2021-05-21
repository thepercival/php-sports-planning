<?php
declare(strict_types=1);

namespace SportsPlanning\Combinations\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\GameRound;
use SportsPlanning\Combinations\PlaceCombination;
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

    public function output(AgainstHomeAway $homeAway, GameRound|null $gameRound = null, string|null $prefix = null): void
    {
        $useColors = $this->useColors();
        $gameRoundColor = ($useColors && $gameRound !== null) ? ($gameRound->getNumber() % 10) : -1;
        $this->logger->info(
            ($prefix !== null ? $prefix : '') .
            ($gameRound !== null ? $this->outputColor($gameRoundColor, 'gameRound ' . $gameRound->getNumber()) .  ', ' : '')
            // . 'substr(' . $game->getRoundNumber(), 2 ) . substr( $game->getSubNumber(), 2 ) . ") "
            . $this->outputPlaces($homeAway, $gameRound)
        );
    }

    protected function outputPlaces(AgainstHomeAway $homeAway, GameRound|null $gameRound = null): string
    {
        $homeGamePlaces = $this->outputPlacesHelper($homeAway->getHome());
        $awayGamePlaces = $this->outputPlacesHelper($homeAway->getAway());
        return $homeGamePlaces . ' vs ' . $awayGamePlaces;
    }

    protected function outputPlacesHelper(PlaceCombination $placeCombination): string
    {
        $useColors = $this->useColors();
        $placesAsArrayOfStrings = array_map(
            function (Place $place) use ($useColors): string {
                $colorNumber = $useColors ? $place->getNumber() : -1;
                return $this->outputColor($colorNumber, $place->getLocation());
            },
            $placeCombination->getPlaces()
        );
        return implode(' & ', $placesAsArrayOfStrings);
    }
}
