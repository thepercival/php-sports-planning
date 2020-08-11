<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-3-17
 * Time: 20:18
 */

namespace SportsPlanning\Input;

use SportsHelpers\Repository as BaseRepository;
use SportsPlanning\Planning;
use SportsPlanning\Input;
use SportsPlanning\Validator;

class Repository extends BaseRepository
{
    public function get(
        array $structureConfig,
        array $sportConfig,
        int $nrOfReferees,
        bool $teamup,
        int $selfReferee,
        int $nrOfHeadtohead
    ): ?Input {
        $query = $this->createQueryBuilder('pi')
            ->where('pi.structureConfig = :structureConfig')
            ->andWhere('pi.sportConfig = :sportConfig')
            ->andWhere('pi.nrOfReferees = :nrOfReferees')
            ->andWhere('pi.teamup = :teamup')
            ->andWhere('pi.selfReferee = :selfReferee')
            ->andWhere('pi.nrOfHeadtohead = :nrOfHeadtohead')
        ;

        $query = $query->setParameter('structureConfig', json_encode($structureConfig));
        $query = $query->setParameter('sportConfig', json_encode($sportConfig));
        $query = $query->setParameter('nrOfReferees', $nrOfReferees);
        $query = $query->setParameter('teamup', $teamup);
        $query = $query->setParameter('selfReferee', $selfReferee);
        $query = $query->setParameter('nrOfHeadtohead', $nrOfHeadtohead);

        $query->setMaxResults(1);

        $results = $query->getQuery()->getResult();
        $first = reset($results);
        return $first !== false ? $first : null;
    }

    public function getFromInput(Input $input): ?Input
    {
        return $this->get(
            $input->getStructureConfig(),
            $input->getSportConfig(),
            $input->getNrOfReferees(),
            $input->getTeamup(),
            $input->getSelfReferee(),
            $input->getNrOfHeadtohead()
        );
    }

    public function reset(Input $planningInput)
    {
        while ($planningInput->getPlannings()->count() > 0) {
            $planning = $planningInput->getPlannings()->first();
            $planningInput->getPlannings()->removeElement($planning);
            $this->remove($planning);
        }
        $planningInput->setState(Input::STATE_CREATED);
        $this->save($planningInput);
    }

    //-- planninginputs not validated
//select 	count(*)
//from 	planninginputs pi
//where 	not exists( select * from plannings p where p.inputId = pi.Id and ( p.state = 2 or p.state = 8 or p.state = 16 ) )
//and		exists( select * from plannings p where p.inputId = pi.Id and p.validity < 0 )
    /**
     * @param int $limit
     * @param array|int[] $structureConfig
     * @return array|Input[]
     */
    public function findNotValidated(int $limit, array $structureConfig = null, int $selfReferee = null): array
    {
        $exprNot = $this->getEM()->getExpressionBuilder();
        $exprInvalidStates = $this->getEM()->getExpressionBuilder();
        $exprNotValidated = $this->getEM()->getExpressionBuilder();

        $states = Planning::STATE_TIMEOUT + Planning::STATE_UPDATING_SELFREFEE + Planning::STATE_PROCESSING;

        $query = $this->createQueryBuilder('pi')
            ->where('pi.state = :inputState')
            ->andWhere(
                $exprNot->not(
                    $exprInvalidStates->exists(
                        $this->getEM()->createQueryBuilder()
                            ->select('p1.id')
                            ->from('SportsPlanning', 'p1')
                            ->where('p1.input = pi')
                            ->andWhere('BIT_AND(p1.state, :states) > 0')
                            ->getDQL()
                    )
                )
            )
            ->andWhere(
                $exprNotValidated->exists(
                    $this->getEM()->createQueryBuilder()
                        ->select('p2.id')
                        ->from('SportsPlanning', 'p2')
                        ->where('p2.input = pi')
                        ->andWhere('p2.validity = :notvalidated')
                        ->getDQL()
                )
            )
            ->setMaxResults($limit)
            ->setParameter('inputState', Input::STATE_ALL_PLANNINGS_TRIED)
            ->setParameter('states', $states)
            ->setParameter('notvalidated', Validator::NOT_VALIDATED);

        if ($structureConfig !== null) {
            $query = $query
                ->andWhere('pi.structureConfig = :structureConfig')
                ->setParameter('structureConfig', json_encode($structureConfig));
        }
        if ($selfReferee !== null) {
            $query = $query
                ->andWhere('pi.selfReferee = :selfReferee')
                ->setParameter('selfReferee', $selfReferee);
        }

        $inputs = $query->getQuery()->getResult();
        return $inputs;
    }


//    -- obsolete planninginputs
//    select 	count(*)
//    from 	planninginputs pi
//    where 	not exists( select * from plannings p where p.inputId = pi.Id and ( p.state = 2 or p.state = 8 or p.state = 16 ) )
//    and		( select count(*) from plannings p where p.inputId = pi.Id and p.state = 4 ) > 1 --success
//    and		pi.state = 8
    /**
     * @return array|Input[]
     */
    public function findWithObsoletePlannings(): array
    {
        $exprNot = $this->getEM()->getExpressionBuilder();
        $exprUnfinished = $this->getEM()->getExpressionBuilder();

        $unfinishedStates = Planning::STATE_TIMEOUT + Planning::STATE_UPDATING_SELFREFEE + Planning::STATE_PROCESSING;
        $finishedStates = Planning::STATE_SUCCESS + Planning::STATE_FAILED;

        $query = $this->createQueryBuilder('pi')
            ->where('pi.state = :inputState')
            ->andWhere(
                $exprNot->not(
                    $exprUnfinished->exists(
                        $this->getEM()->createQueryBuilder()
                            ->select('p1.id')
                            ->from('SportsPlanning', 'p1')
                            ->where('p1.input = pi')
                            ->andWhere('BIT_AND(p1.state, :unfinishedStates) > 0')
                            ->getDQL()
                    )
                )
            )
            ->andWhere(
                "(" . $this->getEM()->createQueryBuilder()
                    ->select('count(p2.id)')
                    ->from('SportsPlanning', 'p2')
                    ->where('p2.input = pi')
                    ->andWhere('BIT_AND(p2.state, :finishedStates) > 0')
                    ->getDQL()
                . ") > 1"
            )
            ->setParameter('inputState', Input::STATE_ALL_PLANNINGS_TRIED)
            ->setParameter('unfinishedStates', $unfinishedStates)
            ->setParameter('finishedStates', $finishedStates);
        $inputs = $query->getQuery()->getResult();

        return $inputs;
    }
}
