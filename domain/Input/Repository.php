<?php

namespace SportsPlanning\Input;

use SportsHelpers\Repository as BaseRepository;
use SportsHelpers\PouleStructure;
use SportsHelpers\Range;
use SportsHelpers\SportConfig as SportConfigHelper;
use SportsPlanning\Planning;
use SportsPlanning\Input;
use SportsPlanning\Planning\Validator;

class Repository extends BaseRepository
{
    /**
     * @param PouleStructure $pouleStructure
     * @param array|SportConfigHelper[] $sportConfigHelpers
     * @param int $nrOfReferees
     * @param bool $teamup
     * @param int $selfReferee
     * @param int $nrOfHeadtohead
     * @return Input|null
     */
    public function get(
        PouleStructure $pouleStructure,
        array $sportConfigHelpers,
        int $nrOfReferees,
        bool $teamup,
        int $selfReferee,
        int $nrOfHeadtohead
    ): ?Input {
        $query = $this->createQueryBuilder('pi')
            ->where('pi.pouleStructureDb = :pouleStructure')
            ->andWhere('pi.sportConfigDb = :sportConfig')
            ->andWhere('pi.nrOfReferees = :nrOfReferees')
            ->andWhere('pi.teamup = :teamup')
            ->andWhere('pi.selfReferee = :selfReferee')
            ->andWhere('pi.nrOfHeadtohead = :nrOfHeadtohead')
        ;

        $query = $query->setParameter('pouleStructure', json_encode($pouleStructure->toArray()));
        $query = $query->setParameter('sportConfig', json_encode($this->sportConfigHelpersToArray($sportConfigHelpers)));
        $query = $query->setParameter('nrOfReferees', $nrOfReferees);
        $query = $query->setParameter('teamup', $teamup);
        $query = $query->setParameter('selfReferee', $selfReferee);
        $query = $query->setParameter('nrOfHeadtohead', $nrOfHeadtohead);

        $query->setMaxResults(1);

        $results = $query->getQuery()->getResult();
        $first = reset($results);
        return $first !== false ? $first : null;
    }

    /**
     * @param array|SportConfigHelper[] $sportConfigHelpers
     * @return array
     */
    protected function sportConfigHelpersToArray(array $sportConfigHelpers): array {
        return array_map( function(SportConfigHelper $sportConfigHelper): array {
            return $sportConfigHelper->toArray();
        }, $sportConfigHelpers );
    }

    public function getFromInput(Input $input): ?Input
    {
        return $this->get(
            $input->getPouleStructure(),
            $input->getSportConfigHelpers(),
            $input->getNrOfReferees(),
            $input->getTeamup(),
            $input->getSelfReferee(),
            $input->getNrOfHeadtohead()
        );
    }

    public function removePlannings(Input $planningInput)
    {
        while ($planningInput->getPlannings()->count() > 0) {
            $planning = $planningInput->getPlannings()->first();
            $planningInput->getPlannings()->removeElement($planning);
            $this->_em->remove($planning);
        }
        $this->_em->flush();
    }

    public function reset(Input $planningInput)
    {
        $this->removePlannings($planningInput);
        $this->createBatchGamesPlannings($planningInput);
    }

    public function createBatchGamesPlannings(Input $planningInput)
    {
        $maxNrOfBatchGamesInput = $planningInput->getMaxNrOfBatchGames();

        for ($minNrOfBatchGames = 1; $minNrOfBatchGames <= $maxNrOfBatchGamesInput; $minNrOfBatchGames++) {
            for ($maxNrOfBatchGames = $minNrOfBatchGames; $maxNrOfBatchGames <= $maxNrOfBatchGamesInput; $maxNrOfBatchGames++) {
                $planning = new Planning($planningInput, new Range($minNrOfBatchGames, $maxNrOfBatchGames), 0);
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
     * @return array|Input[]
     */
    public function findNotValidated(
        bool $validateInvalid, int $limit,
        PouleStructure $pouleStructure = null, int $selfReferee = null): array
    {
        $exprNot = $this->getEM()->getExpressionBuilder();
        $exprInvalidStates = $this->getEM()->getExpressionBuilder();
        $exprNotValidated = $this->getEM()->getExpressionBuilder();
        $validOperator = '<';
        if( $validateInvalid ) {
            $validOperator = '<>';
        }

        $query = $this->createQueryBuilder('pi')
            // zonder succeeded
            ->andWhere(
                $exprNot->not(
                    $exprInvalidStates->exists(
                        $this->getEM()->createQueryBuilder()
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
                    $this->getEM()->createQueryBuilder()
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
                ->andWhere('pi.pouleStructureDb = :pouleStructure')
                ->setParameter('pouleStructure', json_encode($pouleStructure->toArray()));
        }
        if ($selfReferee !== null) {
            $query = $query
                ->andWhere('pi.selfReferee = :selfReferee')
                ->setParameter('selfReferee', $selfReferee);
        }

        $inputs = $query->getQuery()->getResult();
        return $inputs;
    }

// select * from planninginputs where exists( select * from  plannings where gamesinarow = 0 and state = timedout ) and  structure;


    public function findGamesInARowTimedout(int $maxTimeoutSeconds, PouleStructure $pouleStructure = null): ?Input
    {
        return $this->findTimedout( false, $maxTimeoutSeconds, $pouleStructure );
    }

    public function findBatchGamestTimedout(int $maxTimeoutSeconds, PouleStructure $pouleStructure = null): ?Input
    {
        return $this->findTimedout( true, $maxTimeoutSeconds, $pouleStructure );
    }

    protected function findTimedout(bool $bBatchGames, int $maxTimeoutSeconds, PouleStructure $pouleStructure = null): ?Input
    {
        $exprNot = $this->getEM()->getExpressionBuilder();
        $exprInputWithToBeProcessedPlannings = $this->getEM()->getExpressionBuilder();
        $exprTimedoutPlannings = $this->getEM()->getExpressionBuilder();

        $query = $this->createQueryBuilder('pi')
            ->andWhere(
                $exprNot->not(
                    $exprInputWithToBeProcessedPlannings->exists(
                        $this->getEM()->createQueryBuilder()
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
                    $this->getEM()->createQueryBuilder()
                        ->select('p.id')
                        ->from('SportsPlanning\Planning', 'p')
                        ->where('p.input = pi')
                        ->andWhere('p.state = :state')
                        ->andWhere('p.maxNrOfGamesInARow ' . ( $bBatchGames ? '=' : '>') .  ' 0')
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
                ->andWhere('pi.pouleStructureDb = :pouleStructure')
                ->setParameter('pouleStructure', json_encode($pouleStructure->toArray()));
        }

        $query->setMaxResults(1);
        $results = $query->getQuery()->getResult();
        $first = reset($results);
        return $first !== false ? $first : null;
    }
}
