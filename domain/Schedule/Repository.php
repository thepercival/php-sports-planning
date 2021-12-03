<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SportsPlanning\Input;
use SportsPlanning\Poule;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Name as ScheduleName;

/**
 * @template-extends EntityRepository<Schedule>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<Schedule>
     */
    use BaseRepository;

    /**
     * @param Input $input
     * @return list<Schedule>
     */
    public function findByInput(Input $input): array
    {
        $sportVariants = array_values($input->createSportVariants()->toArray());
        $scheduleName = (string)new ScheduleName($sportVariants);

        $pouleNrOfPlaces = array_unique(
            $input->getPoules()->map(function (Poule $poule): string {
                return 'grsch.nrOfPlaces = ' . $poule->getPlaces()->count();
            })->toArray()
        );
        $orExprNrOfPlaces = join(' OR ', $pouleNrOfPlaces);

        $query = $this->createQueryBuilder('grsch')
            ->where($orExprNrOfPlaces)
            ->andWhere('grsch.sportsConfigName = :sportsConfigName')
            ->andWhere('grsch.gamePlaceStrategy = :gamePlaceStrategy')
            ->setParameter('sportsConfigName', $scheduleName)
            ->setParameter('gamePlaceStrategy', $input->getGamePlaceStrategy()->value);
        /** @var list<Schedule> $schedules */
        $schedules = $query->getQuery()->getResult();

        return $schedules;
    }

    public function getDistinctNrOfPoulePlaces(Input $input): int
    {
        return count(array_unique(
            $input->getPoules()->map(function (Poule $poule): int {
                return $poule->getPlaces()->count();
            })->toArray()
        ));
    }
}
