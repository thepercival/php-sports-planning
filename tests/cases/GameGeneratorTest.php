<?php
declare(strict_types=1);

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\GameGenerator;
use SportsPlanning\TestHelper\PlanningCreator;

class GameGeneratorTest extends TestCase
{
    use PlanningCreator;

    public function testGameInstanceAgainst(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([2], null, 0)
        );
        $gameGenerator = new GameGenerator();
        $gameGenerator->generateGames($planning);
        $games = $planning->getGames();
        self::assertInstanceOf(AgainstGame::class, reset($games));
    }

    public function testGameInstanceTogether(): void
    {
        $singleSportVariantWithFields = $this->getSingleSportVariantWithFields(2);
        $planning = $this->createPlanning(
            $this->createInput([2], [$singleSportVariantWithFields], 0)
        );
        $gameGenerator = new GameGenerator();
        $gameGenerator->generateGames($planning);
        $games = $planning->getGames();
        self::assertInstanceOf(TogetherGame::class, reset($games));
    }

    public function testMixedGameModes(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(2, 2, 2, 4),
            $this->getSingleSportVariantWithFields(2, 4, 2),
        ];
        $planning = $this->createPlanning($this->createInput([4], $sportVariants));
        self::assertCount(12, $planning->getAgainstGames());
        self::assertCount(8, $planning->getTogetherGames());
    }
}
