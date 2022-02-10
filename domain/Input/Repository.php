<?php

declare(strict_types=1);

namespace SportsPlanning\Input;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\PouleStructure;
use SportsHelpers\Repository as BaseRepository;
use SportsHelpers\SelfReferee;
use SportsPlanning\Input as InputBase;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Planning\Type as PlanningType;
use SportsPlanning\Planning\Validator;

/**
 * @template-extends EntityRepository<InputBase>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<InputBase>
     */
    use BaseRepository;

    public function get(string $uniqueString): ?InputBase
    {
        $query = $this->createQueryBuilder('pi')->where('pi.uniqueString = :uniqueString');
        $query = $query->setParameter('uniqueString', $uniqueString);

        $query->setMaxResults(1);
        $results = $query->getQuery()->getResult();
        $first = reset($results);
        return $first !== false ? $first : null;
    }

    /**
     * @param int $amount
     * @return list<InputBase>
     */
    public function findToRecreate(int $amount): array
    {
        $query = $this->createQueryBuilder('pi')->where('pi.recreatedAt is null');
        $query->setMaxResults($amount);
        $results = $query->getQuery()->getResult();
        return $results;
    }

    public function getFromInput(InputBase $input): ?InputBase
    {
        return $this->get($input->getUniqueString());
    }

    public function removePlannings(InputBase $planningInput): void
    {
        while ($planning = $planningInput->getPlannings()->first()) {
            $planningInput->getPlannings()->removeElement($planning);
            $this->getEntityManager()->remove($planning);
        }
        $this->getEntityManager()->flush();
    }

    //-- planninginputs not validated
    //select 	count(*)
    //from 	planninginputs pi
    //where 	not exists( select * from plannings p where p.inputId = pi.Id and ( p.state = 2 or p.state = 8 or p.state = 16 ) )
    //and		exists( select * from plannings p where p.inputId = pi.Id and p.validity < 0 )
    /**
     * alles zonder een succeeded
     * of met succeeded en not valid or not validated
     *
     * @param bool $validateInvalid
     * @param int $limit
     * @param PouleStructure|null $pouleStructure
     * @param SelfReferee|null $selfReferee
     * @return list<InputBase>
     */
    public function findNotValidated(
        bool $validateInvalid,
        int $limit,
        PouleStructure|null $pouleStructure = null,
        SelfReferee|null $selfReferee = null
    ): array {
        $exprNot = $this->getEntityManager()->getExpressionBuilder();
        $exprInvalidStates = $this->getEntityManager()->getExpressionBuilder();
        $exprNotValidated = $this->getEntityManager()->getExpressionBuilder();
        $validOperator = '<';
        if ($validateInvalid) {
            $validOperator = '<>';
        }

        $query = $this->createQueryBuilder('pi')
            // zonder succeeded
            ->andWhere(
                $exprNot->not(
                    $exprInvalidStates->exists(
                        $this->getEntityManager()->createQueryBuilder()
                            ->select('p1.id')
                            ->from('SportsPlanning\Planning', 'p1')
                            ->where('p1.input = pi')
                            ->andWhere('p1.state = ' . PlanningState::Succeeded->value)
                            ->getDQL()
                    )
                )
            )
            ->orWhere(
                $exprNotValidated->exists(
                    $this->getEntityManager()->createQueryBuilder()
                        ->select('p2.id')
                        ->from('SportsPlanning\Planning', 'p2')
                        ->where('p2.input = pi')
                        ->andWhere('p2.state = ' . PlanningState::Succeeded->value)
                        ->andWhere('p2.validity ' . $validOperator . ' ' . Validator::VALID)
                        ->getDQL()
                )
            )
            ->andWhere('pi.seekingPercentage = 100')
            ->setMaxResults($limit);

        if ($pouleStructure !== null) {
            $query = $query
                ->andWhere('pi.structureConfig = :pouleStructure')
                ->setParameter('pouleStructure', json_encode($pouleStructure->toArray()));
        }
        if ($selfReferee !== null) {
            $query = $query
                ->andWhere('pi.selfReferee = :selfReferee')
                ->setParameter('selfReferee', $selfReferee->value);
        }
        $inputs = $query->getQuery()->getResult();
        return $inputs;
    }

    // select * from planninginputs where exists( select * from  plannings where gamesinarow = 0 and state = timedout ) and  structure;
    public function findTimedout(
        PlanningType $planningType,
        int $maxTimeoutSeconds,
        PouleStructure $pouleStructure = null
    ): ?InputBase {
        $exprTimedoutPlannings = $this->getEntityManager()->getExpressionBuilder();

        $operator = $planningType === PlanningType::BatchGames ? '=' : '>';
        $query = $this->createQueryBuilder('pi')
            ->andWhere(
                $exprTimedoutPlannings->exists(
                    $this->getEntityManager()->createQueryBuilder()
                        ->select('p.id')
                        ->from('SportsPlanning\Planning', 'p')
                        ->where('p.input = pi')
                        ->andWhere('p.state = ' . PlanningState::TimedOut->value)
                        ->andWhere('p.maxNrOfGamesInARow ' . $operator . ' 0')
                        ->andWhere('p.timeoutSeconds > 0')
                        ->andWhere('p.timeoutSeconds <= :maxTimeoutSeconds')
                        ->getDQL()
                )
            );
        $query = $query->setParameter('maxTimeoutSeconds', $maxTimeoutSeconds);

        if ($pouleStructure !== null) {
            $query = $query
                ->andWhere('pi.uniqueString LIKE :pouleStructure')
                ->setParameter('pouleStructure', json_encode($pouleStructure->toArray()) . '%');
        }

        $query->setMaxResults(1);
        $results = $query->getQuery()->getResult();
        $first = reset($results);
        return $first === false ? null : $first;
    }
}
