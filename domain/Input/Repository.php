<?php
declare(strict_types=1);

namespace SportsPlanning\Input;

use SportsHelpers\Repository\SaveRemove as SaveRemoveRepository;
use Exception;
use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SportsHelpers\PouleStructure;
use SportsHelpers\SportRange;
use SportsPlanning\Input as InputBase;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Validator;

/**
 * @template-extends EntityRepository<InputBase>
 * @template-implements SaveRemoveRepository<InputBase>
 */
class Repository extends EntityRepository implements SaveRemoveRepository
{
    use BaseRepository;

    public function get(string $uniqueString): ?InputBase {
        $query = $this->createQueryBuilder('pi')->where('pi.uniqueString = :uniqueString');
        $query = $query->setParameter('uniqueString', $uniqueString);

        $query->setMaxResults(1);
        /** @var list<InputBase> $results */
        $results = $query->getQuery()->getResult();
        $first = reset($results);
        return $first !== false ? $first : null;
    }

    public function getFromInput(InputBase $input): ?InputBase
    {
        return $this->get($input->getUniqueString());
    }

    public function removePlannings(InputBase $planningInput): void
    {
        while ($planning = $planningInput->getPlannings()->first()) {
            $planningInput->getPlannings()->removeElement($planning);
            $this->_em->remove($planning);
        }
        $this->_em->flush();
    }

    public function reset(InputBase $planningInput): void
    {
        $this->removePlannings($planningInput);
        $this->createBatchGamesPlannings($planningInput);
    }

    public function createBatchGamesPlannings(InputBase $planningInput): void
    {
        $maxNrOfBatchGamesInput = $planningInput->getMaxNrOfBatchGames();

        for ($minNrOfBatchGames = 1; $minNrOfBatchGames <= $maxNrOfBatchGamesInput; $minNrOfBatchGames++) {
            for ($maxNrOfBatchGames = $minNrOfBatchGames; $maxNrOfBatchGames <= $maxNrOfBatchGamesInput; $maxNrOfBatchGames++) {
                $planning = new Planning($planningInput, new SportRange($minNrOfBatchGames, $maxNrOfBatchGames), 0);
                $this->_em->persist($planning);
            }
        }
        $this->_em->flush();
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
     * @param int|null $selfReferee
     * @return list<InputBase>
     */
    public function findNotValidated(
        bool $validateInvalid,
        int $limit,
        PouleStructure $pouleStructure = null,
        int $selfReferee = null
    ): array {
        $exprNot = $this->_em->getExpressionBuilder();
        $exprInvalidStates = $this->_em->getExpressionBuilder();
        $exprNotValidated = $this->_em->getExpressionBuilder();
        $validOperator = '<';
        if ($validateInvalid) {
            $validOperator = '<>';
        }

        $query = $this->createQueryBuilder('pi')
            // zonder succeeded
            ->andWhere(
                $exprNot->not(
                    $exprInvalidStates->exists(
                        $this->_em->createQueryBuilder()
                            ->select('p1.id')
                            ->from('SportsPlanning\Planning', 'p1')
                            ->where('p1.input = pi')
                            ->andWhere('p1.state = :stateSuccess')
                            ->getDQL()
                    )
                )
            )
            ->orWhere(
                $exprNotValidated->exists(
                    $this->_em->createQueryBuilder()
                        ->select('p2.id')
                        ->from('SportsPlanning\Planning', 'p2')
                        ->where('p2.input = pi')
                        ->andWhere('p2.state = :stateSuccess')
                        ->andWhere('p2.validity ' . $validOperator . ' :valid')
                        ->getDQL()
                )
            )
            ->setMaxResults($limit)
            ->setParameter('stateSuccess', Planning::STATE_SUCCEEDED)
            ->setParameter('valid', Validator::VALID);

        if ($pouleStructure !== null) {
            $query = $query
                ->andWhere('pi.structureConfig = :pouleStructure')
                ->setParameter('pouleStructure', json_encode($pouleStructure->toArray()));
        }
        if ($selfReferee !== null) {
            $query = $query
                ->andWhere('pi.selfReferee = :selfReferee')
                ->setParameter('selfReferee', $selfReferee);
        }
        /** @var list<InputBase> $inputs */
        $inputs = $query->getQuery()->getResult();
        return $inputs;
    }

    // select * from planninginputs where exists( select * from  plannings where gamesinarow = 0 and state = timedout ) and  structure;


    public function findGamesInARowTimedout(int $maxTimeoutSeconds, PouleStructure $pouleStructure = null): ?InputBase
    {
        return $this->findTimedout(false, $maxTimeoutSeconds, $pouleStructure);
    }

    public function findBatchGamestTimedout(int $maxTimeoutSeconds, PouleStructure $pouleStructure = null): ?InputBase
    {
        return $this->findTimedout(true, $maxTimeoutSeconds, $pouleStructure);
    }

    protected function findTimedout(bool $bBatchGames, int $maxTimeoutSeconds, PouleStructure $pouleStructure = null): ?InputBase
    {
        $exprNot = $this->_em->getExpressionBuilder();
        $exprInputWithToBeProcessedPlannings = $this->_em->getExpressionBuilder();
        $exprTimedoutPlannings = $this->_em->getExpressionBuilder();

        $query = $this->createQueryBuilder('pi')
            ->andWhere(
                $exprNot->not(
                    $exprInputWithToBeProcessedPlannings->exists(
                        $this->_em->createQueryBuilder()
                            ->select('p1.id')
                            ->from('SportsPlanning\Planning', 'p1')
                            ->where('p1.input = pi')
                            ->andWhere('p1.state = :stateToBeProcessed')
                            ->getDQL()
                    )
                )
            )
            ->andWhere(
                $exprTimedoutPlannings->exists(
                    $this->_em->createQueryBuilder()
                        ->select('p.id')
                        ->from('SportsPlanning\Planning', 'p')
                        ->where('p.input = pi')
                        ->andWhere('p.state = :state')
                        ->andWhere('p.maxNrOfGamesInARow ' . ($bBatchGames ? '=' : '>') .  ' 0')
                        ->andWhere('p.timeoutSeconds > 0')
                        ->andWhere('p.timeoutSeconds <= :maxTimeoutSeconds')
                        ->getDQL()
                )
            );
        $query = $query->setParameter('stateToBeProcessed', Planning::STATE_TOBEPROCESSED);
        $query = $query->setParameter('state', Planning::STATE_TIMEDOUT);
        $query = $query->setParameter('maxTimeoutSeconds', $maxTimeoutSeconds);

        if ($pouleStructure !== null) {
            $query = $query
                ->andWhere('pi.uniqueString = :pouleStructure')
                ->setParameter('pouleStructure', json_encode($pouleStructure->toArray()));
        }

        $query->setMaxResults(1);
        /** @var list<InputBase> $results */
        $results = $query->getQuery()->getResult();
        $first = reset($results);
        return $first === false ? null : $first;
    }
}
