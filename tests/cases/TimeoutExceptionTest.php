<?php
declare(strict_types=1);

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsPlanning\TimeoutException;

class TimeoutExceptionTest extends TestCase
{

    public function testThrow(): void
    {
        self::expectException(TimeoutException::class);
        throw new TimeoutException("just a test", E_ERROR);
    }
}
