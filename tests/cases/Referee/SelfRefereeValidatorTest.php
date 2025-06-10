<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Referee;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\PlanningOrchestration;
use SportsPlanning\Referee\SelfRefereeValidator;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

final class SelfRefereeValidatorTest extends TestCase
{

    public function test332(): void
    {
        $validator = new SelfRefereeValidator();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $orchestration = new PlanningOrchestration(
            new PlanningConfiguration(
                new PouleStructure([3, 2, 2]),
                [$sportWithNrOfFieldsAndNrOfCycles],
                null,
                false
            )
        );

        self::assertTrue(
            $validator->canSelfRefereeBeAvailable($orchestration->configuration->pouleStructure, [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test2(): void
    {
        $validator = new SelfRefereeValidator();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $orchestration = new PlanningOrchestration(
            new PlanningConfiguration(
                new PouleStructure([2]),
                [$sportWithNrOfFieldsAndNrOfCycles],
                null,
                false
            )
        );

        self::assertFalse(
            $validator->canSelfRefereeBeAvailable($orchestration->configuration->pouleStructure, [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test22SamePoule(): void
    {
        $validator = new SelfRefereeValidator();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $orchestration = new PlanningOrchestration(
            new PlanningConfiguration(
                new PouleStructure([2,2]),
                [$sportWithNrOfFieldsAndNrOfCycles],
                null,
                false
            )
        );

        self::assertFalse(
            $validator->canSelfRefereeSamePouleBeAvailable($orchestration->configuration->pouleStructure, [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test32SamePoule(): void
    {
        $validator = new SelfRefereeValidator();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $orchestration = new PlanningOrchestration(
            new PlanningConfiguration(
                new PouleStructure([3,2]),
                [$sportWithNrOfFieldsAndNrOfCycles],
                null,
                false
            )
        );

        self::assertFalse(
            $validator->canSelfRefereeSamePouleBeAvailable($orchestration->configuration->pouleStructure, [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test33SamePoule(): void
    {
        $validator = new SelfRefereeValidator();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $orchestration = new PlanningOrchestration(
            new PlanningConfiguration(
                new PouleStructure([3,3]),
                [$sportWithNrOfFieldsAndNrOfCycles],
                null,
                false
            )
        );

        self::assertTrue(
            $validator->canSelfRefereeSamePouleBeAvailable($orchestration->configuration->pouleStructure, [$sportWithNrOfFieldsAndNrOfCycles->sport])
        );
    }

    public function test3OtherPoule(): void
    {
        $validator = new SelfRefereeValidator();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $orchestration = new PlanningOrchestration(
            new PlanningConfiguration(
                new PouleStructure([3]),
                [$sportWithNrOfFieldsAndNrOfCycles],
                null,
                false
            )
        );

        self::assertFalse(
            $validator->canSelfRefereeOtherPoulesBeAvailable($orchestration->configuration->pouleStructure)
        );
    }

    public function test22OtherPoule(): void
    {
        $validator = new SelfRefereeValidator();
        $sportWithNrOfFieldsAndNrOfCycles = new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1);
        $orchestration = new PlanningOrchestration(
            new PlanningConfiguration(
                new PouleStructure([2, 2]),
                [$sportWithNrOfFieldsAndNrOfCycles],
                null,
                false
            )
        );

        self::assertTrue(
            $validator->canSelfRefereeOtherPoulesBeAvailable($orchestration->configuration->pouleStructure)
        );
    }
}
