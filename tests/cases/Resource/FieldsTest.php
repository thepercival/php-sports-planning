<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Resource;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Resource\Fields;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\TestHelper\PlanningReplacer;

class FieldsTest extends TestCase
{
    use PlanningCreator;
    use PlanningReplacer;

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
            $this->getAgainstGppSportVariantWithFields(2, 1, 1, 3),
            $this->getAgainstGppSportVariantWithFields(2, 1, 1, 3),
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
