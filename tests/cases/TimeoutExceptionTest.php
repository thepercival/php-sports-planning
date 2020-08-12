<?php

namespace SportsPlanning\Tests;

use SportsPlanning\TimeoutException;

class TimeoutExceptionTest extends \PHPUnit\Framework\TestCase
{

    public function testThrow()
    {
        self::expectException(TimeoutException::class);
        throw new TimeoutException("just a test", E_ERROR);
    }
}
