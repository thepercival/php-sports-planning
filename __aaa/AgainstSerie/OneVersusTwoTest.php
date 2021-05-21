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
// 1 vs 2      3        3(3)                1           3               2                       3(2 x 1 + 1)
// 1 vs 2      4        12(12)              3           4               3                       10(3 x 2 + 4)
// 1 vs 2      5        30(30)              6           5               4                       17(4 x 3 + 5)
// 1 vs 2      6        60(60)              10          6               5                       36(5 x 6 + 6)

class OneVersusTwoTest extends TestCase
{
    use PlanningCreator;

    public function testBasic(): void
    {
        $this->helper(3, 1, 3);
        $this->helper(3, 2, 6);   // 1 SERIE
        $this->helper(3, 3, 9);

        $this->helper(4, 1, 4);
        $this->helper(4, 3, 12); // 1 SERIE

        $this->helper(5, 1, 5);
        $this->helper(5, 6, 30); // 1 SERIE
    }

    protected function helper(int $nrOfPlaces, int $nrOfPartials, int $expectedNrOfGames): void
    {
        $sportVariant = $this->getAgainstSportVariantWithFields(1, 1, 2, 0, $nrOfPartials);
        $planning = new Planning($this->createInput([$nrOfPlaces], [$sportVariant]), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount($expectedNrOfGames, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }
}
