<?php

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportConfig;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\GameGenerator;
use SportsPlanning\TestHelper\PlanningCreator;

class GameGeneratorTest extends TestCase
{
    use PlanningCreator;

    public function testGameInstanceAgainst()
    {
        $defaultSportConfig = $this->getDefaultSportConfig();
        $planning = $this->createPlanning(
            $this->createInput( [ 2 ], SportConfig::GAMEMODE_AGAINST, [$defaultSportConfig], 0  )
        );
        $gameGenerator = new GameGenerator();
        $gameGenerator->createGames($planning);
        $games = $planning->getGames();
        self::assertInstanceOf(AgainstGame::class, reset($games));
    }

    public function testGameInstanceTogether()
    {
        $defaultSportConfig = $this->getDefaultSportConfig();
        $planning = $this->createPlanning(
            $this->createInput( [ 2 ], SportConfig::GAMEMODE_TOGETHER, [$defaultSportConfig], 0  )
        );
        $gameGenerator = new GameGenerator();
        $gameGenerator->createGames($planning);
        $games = $planning->getGames();
        self::assertInstanceOf(TogetherGame::class, reset($games));
    }
}
