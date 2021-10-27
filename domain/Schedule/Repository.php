<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SportsPlanning\Schedule;
use SportsPlanning\Input;
use SportsPlanning\Schedule\Name as ScheduleName;
use SportsPlanning\Poule;

/**
 * @template-extends EntityRepository<Schedule>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<Schedule>
     */
    use BaseRepository;

    public function hasSchedules(Input $input): bool
    {
        try {
            $this->findByInput($input);
            return true;
        } catch( \Exception $e ) {

        }
        return false;
    }

    /**
     * @param Input $input
     * @return list<Schedule>
     */
    public function findByInput(Input $input): array
    {
        // @TODO CDK doe meerdere sql-statements hier!!!

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
            ->andWhere('grsch.name = :name')
            ->andWhere('grsch.gamePlaceStrategy = :gamePlaceStrategy')
            ->setParameter('name', $scheduleName)
            ->setParameter('gamePlaceStrategy', $input->getGamePlaceStrategy());
        /** @var list<Schedule> $schedules */
        $schedules = $query->getQuery()->getResult();

        if (count($pouleNrOfPlaces) !== count($schedules)) {
            throw new \Exception('the distinct nrOfPlaces-amount should be equal to nrOfSchedules', E_ERROR);
        }
        return $schedules;
    }
}
