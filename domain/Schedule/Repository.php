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
            ->setParameter('sportsConfigName', $scheduleName);

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

    /**
     * @param int $nrToProcess
     * @return list<Schedule>
     */
    public function findWithoutMargin(int $nrToProcess): array
    {
        $queryBuilder = $this->createQueryBuilder('sch')
            ->where('sch.succeededMargin = -1');
        $queryBuilder->setMaxResults($nrToProcess);

        /** @var list<Schedule> $schedules */
        $schedules = $queryBuilder->getQuery()->getResult();
        return $schedules;
    }

    /**
     * @param int $nrToProcess
     * @param int $maxNrOfTimeoutSeconds
     * @return list<Schedule>
     */
    public function findOrderedByNrOfTimeoutSecondsAndMargin(int $nrToProcess, int|null $maxNrOfTimeoutSeconds = null): array
    {
        $queryBuilder = $this->createQueryBuilder('sch')
            ->where('sch.succeededMargin > 0')
            ->where('sch.nrOfTimeoutSecondsTried >= 0')
            ->orderBy('sch.nrOfTimeoutSecondsTried', 'ASC')
            ->addOrderBy('sch.succeededMargin', 'DESC')
        ;
        if( $maxNrOfTimeoutSeconds !== null ) {
            $queryBuilder = $queryBuilder
                ->andWhere('sch.nrOfTimeoutSecondsTried <= :nrOfTimeoutSeconds')
                ->setParameter('nrOfTimeoutSeconds', $maxNrOfTimeoutSeconds);
        }
        $queryBuilder->setMaxResults($nrToProcess);

        /** @var list<Schedule> $schedules */
        $schedules = $queryBuilder->getQuery()->getResult();
        return $schedules;
    }
}
