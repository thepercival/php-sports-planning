<?php


namespace SportsPlanning\Tests\Planning\Resource\GameCounter;

use SportsPlanning\Resource\GameCounter\Place as PlaceCounter;
use Voetbal\Structure\Service as StructureService;
use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\TestHelper\PlanningCreator;
use Voetbal\TestHelper\PlanningReplacer;

class PlaceTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, PlanningCreator, PlanningReplacer;

    public function testCalculations()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 3);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $placeOne = $planning->getPoule(1)->getPlace(1);
        $gameCounter = new PlaceCounter($placeOne);

        self::assertSame($placeOne, $gameCounter->getPlace());

        self::assertSame($placeOne->getLocation(), $gameCounter->getIndex());
    }

}