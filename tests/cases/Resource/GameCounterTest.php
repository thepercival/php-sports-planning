<?php


namespace SportsPlanning\Tests\Planning\Resource;

use SportsPlanning\Resource\GameCounter;
use Voetbal\Structure\Service as StructureService;
use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\TestHelper\PlanningCreator;
use Voetbal\TestHelper\PlanningReplacer;

class GameCounterTest extends \PHPUnit\Framework\TestCase
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

        $referee = $planning->getReferee(1);
        $gameCounter = new GameCounter($referee);

        self::assertSame("1", $gameCounter->getIndex());
        self::assertSame(0, $gameCounter->getNrOfGames());

        $gameCounter->increase();
        self::assertSame(1, $gameCounter->getNrOfGames());

        self::assertSame($referee, $gameCounter->getResource());
    }

}