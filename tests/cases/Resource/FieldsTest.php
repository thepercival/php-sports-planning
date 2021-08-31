<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\Resource;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsPlanning\Resource\Fields;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\TestHelper\PlanningReplacer;
use SportsPlanning\Planning\Output as PlanningOutput;

class FieldsTest extends TestCase
{
    use PlanningCreator, PlanningReplacer;

    public function testOnePouleTwoFields(): void
    {
        $input = $this->createInput([2]);
        $planning = $this->createPlanning($input);

        $fields = new Fields($planning->getInput());

        $sport = $input->getSport(1);
        self::assertCount(2, $fields->getAssignableFields($sport));
    }

    public function testMultipleSports(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(2, 1, 1, 1, 0),
            $this->getAgainstSportVariantWithFields(2, 1, 1, 1, 0),
        ];
        $input = $this->createInput([4], $sportVariants);
        $planning = $this->createPlanning($input);

        $fields = new Fields($planning->getInput());

        self::assertCount(2, $fields->getAssignableFields($input->getSport(1)));
        self::assertCount(2, $fields->getAssignableFields($input->getSport(2)));
    }

    public function testSixPoulesTwoFields(): void
    {
        $input = $this->createInput([2,2,2,2,2,2]);
        $nrOfGamesPerBatchRange = new SportRange(2, 2);
        $planning = $this->createPlanning($input, $nrOfGamesPerBatchRange);

        // (new PlanningOutput())->outputWithGames($planning, true);

        $fields = new Fields($planning->getInput());
        $lastGame = $planning->getAgainstGames()->last();
        self::assertInstanceOf(AgainstGame::class, $lastGame);
        $fields->assignToGame($lastGame);

        $sport = $input->getSport(1);
        self::assertFalse($fields->isSomeFieldAssignable($sport, $input->getPoule(6)));
    }

}
