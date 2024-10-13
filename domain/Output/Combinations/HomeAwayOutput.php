<?php

declare(strict_types=1);

namespace SportsPlanning\Output\Combinations;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\Against\Side;
use SportsHelpers\Output as OutputHelper;
use SportsHelpers\Output\Color;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\CounterForDuoPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\AgainstNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\WithNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Place;
use SportsPlanning\Schedule\GameRounds\AgainstGameRound as AgainstGameRound;

class HomeAwayOutput extends OutputHelper
{
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    /**
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
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
     * @param int $nrOfPlaces
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @param bool $againstTotals
     * @param bool $withTotals
     * @param bool $homeTotals
     * @return void
     */
    public function outputTotalDetails(int $nrOfPlaces, array $homeAways,
                                       bool $againstTotals, bool $withTotals, bool $homeTotals): void {
        if( $againstTotals ) {
            $this->outputAgainstTotals($nrOfPlaces, $homeAways);
        }
        if( $withTotals ) {
            $this->outputWithTotals($nrOfPlaces, $homeAways);
        }
        if( $homeTotals ) {
            $this->outputWithTotals($nrOfPlaces, $homeAways);
        }
    }

    /**
     * @param int $nrOfPlaces
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @return void
     */
    public function outputAgainstTotals(int $nrOfPlaces, array $homeAways): void {
        $map = new AgainstNrCounterMap($nrOfPlaces);
        $map->addHomeAways($homeAways);
        $map->output($this->logger, '', '==== AgainstTotals ====');
    }

    /**
     * @param int $nrOfPlaces
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @return void
     */
    public function outputWithTotals(int $nrOfPlaces, array $homeAways): void {
        $map = new WithNrCounterMap($nrOfPlaces);
        $map->addHomeAways($homeAways);
        $map->output($this->logger, '', '==== WithTotals ====');
    }

    /**
     * @param int $nrOfPlaces
     * @param list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway> $homeAways
     * @return void
     */
    public function outputHomeTotals(int $nrOfPlaces, array $homeAways): void
    {
        $map = new SideNrCounterMap(AgainstSide::Home, $nrOfPlaces);
        $map->addHomeAways($homeAways);
        $map->output($this->logger, '', '==== HomeTotals ====');
    }


    public function output(
        OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway,
        AgainstGameRound|null $gameRound = null,
        string|null $prefix = null): void
    {
        $useColors = $this->useColors();
        $gameRoundColorNr = ($useColors && $gameRound !== null) ? ($gameRound->getNumber() % 10) : -1;
        $gameRoundColor = $this->convertNumberToColor($gameRoundColorNr);
        $this->logger->info(
            ($prefix !== null ? $prefix : '') .
            ($gameRound !== null ? Color::getColored(
                    $gameRoundColor,
                    'gameRound ' . $gameRound->getNumber()
                ) . ', ' : '')
            // . 'substr(' . $game->getRoundNumber(), 2 ) . substr( $game->getSubNumber(), 2 ) . ") "
            . $homeAway
        );
    }
}
