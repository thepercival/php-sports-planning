<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\Sport;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SportsPlanning\Schedule\Sport as SportSchedule;

/**
 * @template-extends EntityRepository<SportSchedule>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<SportSchedule>
     */
    use BaseRepository;
}
