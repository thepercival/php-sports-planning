<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations\AgainstSerie;

use drupol\phpermutations\Generators\Permutations;
use drupol\phpermutations\Iterators\Combinations as CombinationIt;
use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsPlanning\GameGenerator;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Planning\Validator as PlanningValidator;
use SportsPlanning\TestHelper\PlanningCreator;

// gamePl      pl       games(per h2h)      partials    gamesPerPartial gamesPerPlacePerPartial gamesPerPlace
// 1 vs 1      2        2(1)                2           1               1                       2
// 1 vs 1      3        6(3)                2           3               2                       4
// 1 vs 1      4        12(6)               3           4               3                       6
// 1 vs 1      5        20(10)              4           5               4                       8
// 1 vs 1      6        30(15)              5           6               5                       10

class OneVersusOneTest extends TestCase
{
    use PlanningCreator;

    public function testBasic(): void
    {
        $this->helper(2, 1, 1);
        $this->helper(2, 2, 2);
        $this->helper(2, 3, 3);

        $this->helper(3, 1, 3);
        $this->helper(3, 2, 6);
        $this->helper(3, 3, 9);
        $this->helper(3, 4, 12);

        $this->helper(4, 1, 6);
        $this->helper(4, 2, 12);
        $this->helper(4, 3, 18);

        $this->helper(5, 1, 10);
        $this->helper(5, 2, 20);
        $this->helper(5, 3, 30);

        $this->helper(6, 1, 15);
        $this->helper(6, 2, 30);
        $this->helper(6, 3, 45);

        $this->helper(7, 1, 21);
        $this->helper(7, 2, 42);
        $this->helper(7, 3, 63);

        $this->helper(8, 1, 28);
        $this->helper(8, 2, 56);
        $this->helper(8, 3, 84);
    }

    protected function helper(int $nrOfPlaces, int $nrOfH2H, int $expectedNrOfGames): void
    {
        $sportVariant = $this->getAgainstSportVariantWithFields(1, 1, 1, $nrOfH2H, 0);
        $planning = new Planning($this->createInput([$nrOfPlaces], [$sportVariant]), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount($expectedNrOfGames, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }
}
