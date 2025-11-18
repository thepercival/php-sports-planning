<?php

declare(strict_types=1);

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Referee;

final class RefereeTest extends TestCase
{


    public function testConstruct(): void
    {
        $referee = new Referee(1, []);
        $referee->setPriority(2);
        self::assertSame(1, $referee->refereeNr);
        self::assertSame(2, $referee->getPriority());
    }
}
