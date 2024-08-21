<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Schedule\GameRounds;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Counters\CounterForPlaceNr;
use SportsPlanning\Schedule\GameRounds\AgainstGameRound;
use SportsPlanning\TestHelper\PlanningCreator;

class AgainstGameRoundTest extends TestCase
{
    public function testIsParticipating(): void
    {
        $gameRound = new AgainstGameRound(4);
        self::assertFalse($gameRound->isParticipating(1));
    }

    public function testCreateNext(): void
    {
        $gameRound = new AgainstGameRound(4);
        self::assertInstanceOf(AgainstGameRound::class, $gameRound->createNext());
    }
}
