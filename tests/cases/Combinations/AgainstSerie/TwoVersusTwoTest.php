<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\Series;

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
// 2 vs 2      4        6(3)                6           1               1                       ?
// 2 vs 2      5        30(15)              6           5               4                       24
// 2 vs 2      6        90(45)              15          6               ?                       30
// 2 vs 2      7        210(105)            30          7               ?                       ?

class TwoVersusTwoTest extends TestCase
{
    use PlanningCreator;

    public function testBasic(): void
    {
        $this->helper(4, 1, 1);
        $this->helper(4, 2, 2);
        $this->helper(4, 3, 3);
        $this->helper(4, 4, 4);

        $this->helper(5, 1, 5);
        $this->helper(5, 3, 15);
        $this->helper(5, 6, 30); // SERIE: (4 4) * 3 = 3

        $this->helper(6, 1, 6);
        $this->helper(6, 15, 90);  // SERIE: (5 4) * 3 = 15

        $this->helper(7, 1, 7);
        $this->helper(7, 45, 315); // SERIE: (6 4) * 3 = 45

        $this->helper(8, 1, 8);
        $this->helper(8, 105, 840); // SERIE: (7 4) * 3 = 105
    }

    protected function helper(int $nrOfPlaces, int $nrOfPartials, int $expectedNrOfGames): void
    {
        $sportVariant = $this->getAgainstSportVariantWithFields(1, 2, 2, 0, $nrOfPartials);
        $planning = new Planning($this->createInput([$nrOfPlaces], [$sportVariant]), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount($expectedNrOfGames, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }
}
