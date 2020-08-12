<?php

namespace SportsPlanning\Tests;

use SportsPlanning\GameGenerator;
use SportsPlanning\TestHelper\PlanningCreator;

class GameGeneratorTest extends \PHPUnit\Framework\TestCase
{
    use PlanningCreator;

    public function testWithRefereePlaces()
    {
        $planning = $this->createPlanning(
            $this->createInput( [ 4 ], $this->getDefaultSportConfig(), 0  )
        );

        $gameGenerator = new GameGenerator($planning->getInput());

        $gameRounds = $gameGenerator->createPouleGameRounds($planning->getPoule(1), false);

        self::assertCount(3, $gameRounds);

        // also test number home, away, difference home away
        // also test for 5 and teamup
        // also test nr of games per place and total, maybe some reduncancy with validators
    }
}
