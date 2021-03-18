<?php
declare(strict_types=1);

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\GameMode;
use SportsHelpers\SportConfig;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\GameGenerator;
use SportsPlanning\TestHelper\PlanningCreator;

class GameGeneratorTest extends TestCase
{
    use PlanningCreator;

    public function testGameInstanceAgainst(): void
    {
        $defaultSportConfig = $this->getDefaultSportConfig();
        $planning = $this->createPlanning(
            $this->createInputNew([2], [$defaultSportConfig], 0)
        );
        $gameGenerator = new GameGenerator();
        $gameGenerator->generateGames($planning);
        $games = $planning->getGames();
        self::assertInstanceOf(AgainstGame::class, reset($games));
    }

    public function testGameInstanceTogether(): void
    {
        $defaultSportConfig = $this->getDefaultSportConfig(GameMode::TOGETHER);
        $planning = $this->createPlanning(
            $this->createInputNew([2], [$defaultSportConfig], 0)
        );
        $gameGenerator = new GameGenerator();
        $gameGenerator->generateGames($planning);
        $games = $planning->getGames();
        self::assertInstanceOf(TogetherGame::class, reset($games));
    }

    public function testMixedGameModes(): void
    {
        $sportConfigs = [
            new SportConfig(GameMode::AGAINST, 2, 2, 2),
            new SportConfig(GameMode::TOGETHER, 2, 2, 2),
        ];
        $planning = $this->createPlanning($this->createInputNew([4], $sportConfigs));
        $againstGames = array_filter($planning->getGames(), function (AgainstGame|TogetherGame $game): bool {
            return $game instanceof AgainstGame;
        });
        self::assertCount(12, $againstGames);
        $togetherGames = array_filter($planning->getGames(), function (AgainstGame|TogetherGame $game): bool {
            return $game instanceof TogetherGame;
        });
        self::assertCount(4, $togetherGames);
    }
}
