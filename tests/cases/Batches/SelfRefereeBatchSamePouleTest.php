<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Batches;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Batches\Batch;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Combinations\AmountBoundary;
use SportsPlanning\Combinations\AmountRange;
use SportsPlanning\Counters\CounterForAmount;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\AgainstGamePlace;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Planning;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsPlanning\TestHelper\PlanningCreator;

final class SelfRefereeBatchSamePouleTest extends TestCase
{
    use PlanningCreator;

    public function testNrOfPlacesParticipating(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $planningRefereeInfo = new PlanningRefereeInfo();
        $orchestration = $this->createOrchestration([5],$sportsWithNrOfFieldsAndNrOfCycles, $planningRefereeInfo);

        $planning = new Planning($orchestration, new SportRange(2,2),2);
        $poule = $planning->getPoule(1);
        $sport = $planning->getSport(1);
        $field = $sport->getField(1);
        $againstGame = new AgainstGame($poule, $field, 1, 1);
        $againstGame->addGamePlace(AgainstSide::Home, 1);
        $againstGame->addGamePlace( AgainstSide::Away, 2);

        $batch = new SelfRefereeBatchSamePoule(new Batch());
        $batch->add($againstGame);

        self::assertSame(3, $batch->getNrOfPlacesParticipating($poule, 1));
    }
}
