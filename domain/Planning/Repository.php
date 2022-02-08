<?php

declare(strict_types=1);

namespace SportsPlanning\Planning;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SportsHelpers\SportRange;
use SportsPlanning\Input;
use SportsPlanning\Planning;
use SportsPlanning\Planning as PlanningBase;

/**
 * @template-extends EntityRepository<PlanningBase>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<PlanningBase>
     */
    use BaseRepository;

    public function resetBatchGamePlanning(PlanningBase $planning, State $state): void
    {
        $this->resetPlanning($planning, $state);
        $this->removeGamesInARowPlannings($planning);
        $this->save($planning);
    }

    public function resetPlanning(PlanningBase $planning, State $state): void
    {
        $planning->setState($state);

        $againstGames = $planning->getAgainstGames();
        while ($game = $againstGames->first()) {
            $againstGames->removeElement($game);
            $this->getEntityManager()->remove($game);
        }
        $togetherGames = $planning->getTogetherGames();
        while ($game = $togetherGames->first()) {
            $togetherGames->removeElement($game);
            $this->getEntityManager()->remove($game);
        }

        $this->save($planning);
    }

//    public function hasEndSuccess(Input $input): bool
//    {
//        $query = $this->createQueryBuilder('p')
//            ->join("p.input", "pi")
//            ->where('pi.structureConfig = :pouleStructure')
//            ->andWhere('pi.sportConfigDb = :sportConfig')
//            ->andWhere('pi.nrOfReferees = :nrOfReferees')
//            ->andWhere('pi.teamup = :teamup')
//            ->andWhere('pi.selfReferee = :selfReferee')
//            ->andWhere('pi.nrOfHeadtohead = :nrOfHeadtohead')
//            ->andWhere('p.state = :state')
//        ;
//
//        $query = $query->setParameter('pouleStructure', json_encode($input->getPouleStructure()->toArray()));
//        $query = $query->setParameter('sportConfig', $this->sportConfigsToString($input));
//        $query = $query->setParameter('nrOfReferees', $input->getNrOfReferees());
//        $query = $query->setParameter('teamup', $input->getTeamup());
//        $query = $query->setParameter('selfReferee', $input->getSelfReferee());
//        $query = $query->setParameter('nrOfHeadtohead', $input->getNrOfHeadtohead());
//        $query = $query->setParameter('state', Planning::STATE_SUCCESS);
//
//        $query->setMaxResults(1);
//
//        $x = $query->getQuery()->getResult();
//
//        return count($x) === 1;
//    }
//
//    public function hasTried(Input $input, Range $nrOfBatchGamesRange): bool
//    {
//        $query = $this->createQueryBuilder('p')
//            ->join("p.input", "pi")
//            ->where('pi.structureConfig = :pouleStructure')
//            ->andWhere('pi.sportConfigDb = :sportConfig')
//            ->andWhere('pi.nrOfReferees = :nrOfReferees')
//            ->andWhere('pi.teamup = :teamup')
//            ->andWhere('pi.selfReferee = :selfReferee')
//            ->andWhere('pi.nrOfHeadtohead = :nrOfHeadtohead')
//            ->andWhere('p.minNrOfBatchGames = :minNrOfBatchGames')
//            ->andWhere('p.maxNrOfBatchGames = :maxNrOfBatchGames')
//        ;
//
//        $query = $query->setParameter('structureConfig', json_encode($input->getPouleStructure()->toArray()));
//        $query = $query->setParameter('sportConfig', $this->sportConfigsToString($input));
//        $query = $query->setParameter('nrOfReferees', $input->getNrOfReferees());
//        $query = $query->setParameter('teamup', $input->getTeamup());
//        $query = $query->setParameter('selfReferee', $input->getSelfReferee());
//        $query = $query->setParameter('nrOfHeadtohead', $input->getNrOfHeadtohead());
//        $query = $query->setParameter('minNrOfBatchGames', $nrOfBatchGamesRange->min);
//        $query = $query->setParameter('maxNrOfBatchGames', $nrOfBatchGamesRange->max);
//
//        $query->setMaxResults(1);
//
//        $x = $query->getQuery()->getResult();
//
//        return count($x) === 1;
//    }
//


    public function findBatchGames(Input $input, SportRange $nrOfBatchGamesRange): Planning|null
    {
        return $this->findOneByExt($input, $nrOfBatchGamesRange, 0);
    }

    public function findGamesInARow(
        Input $input,
        SportRange $nrOfBatchGamesRange,
        int $maxNrOfGamesInARow
    ): Planning|null {
        if ($maxNrOfGamesInARow > 0) {
            return null;
        }
        return $this->findOneByExt($input, $nrOfBatchGamesRange, $maxNrOfGamesInARow);
    }

    protected function findOneByExt(
        Input $input,
        SportRange $nrOfBatchGamesRange,
        int $maxNrOfGamesInARow
    ): Planning|null {
        $query = $this->createQueryBuilder('p')
            ->where('p.input = :input')
            ->andWhere('p.minNrOfBatchGames = :minNrOfBatchGames')
            ->andWhere('p.maxNrOfBatchGames = :maxNrOfBatchGames')
            ->andWhere('p.maxNrOfGamesInARow = :maxNrOfGamesInARow');

        $query = $query->setParameter('input', $input);
        $query = $query->setParameter('minNrOfBatchGames', $nrOfBatchGamesRange->getMin());
        $query = $query->setParameter('maxNrOfBatchGames', $nrOfBatchGamesRange->getMax());
        $query = $query->setParameter('maxNrOfGamesInARow', $maxNrOfGamesInARow);

        $query->setMaxResults(1);

        $results = $query->getQuery()->getResult();
        $first = reset($results);
        return $first !== false ? $first : null;
    }

//    public function findFirstBestPlanningWithoutGamesInARow(): Planning|null
//    {
//        $exprNot = $this->_em->getExpressionBuilder();
//        $exprMinNrOfBatches = $this->_em->getExpressionBuilder();
//        $exprNoGamesInARow = $this->_em->getExpressionBuilder();
//
//        $query = $this->createQueryBuilder('p')
//            ->andWhere(
//                $exprNot->not(
//                    $exprMinNrOfBatches->exists(
//                        $this->_em->createQueryBuilder()
//                            ->select('p1.id')
//                            ->from('SportsPlanning\Planning', 'p1')
//                            ->where('p1.input = p.input')
//                            ->andWhere('p1.state = ' . PlanningState::Succeeded->value)
//                            ->andWhere('p1.nrOfBatches < p.nrOfBatches')
//                            ->getDQL()
//                    )
//                )
//            )
//            ->andWhere(
//                $exprNot->not(
//                    $exprNoGamesInARow->exists(
//                        $this->_em->createQueryBuilder()
//                            ->select('p2.id')
//                            ->from('SportsPlanning\Planning', 'p2')
//                            ->where('p2.input = p.input')
//                            ->andWhere('p2.minNrOfBatchGames = p.minNrOfBatchGames')
//                            ->andWhere('p2.maxNrOfBatchGames = p.maxNrOfBatchGames')
//                            ->andWhere('p2.maxNrOfGamesInARow > 0')
//                            ->getDQL()
//                    )
//                )
//            )
//            ->andWhere('p.state = ' . PlanningState::Succeeded->value);
//
//        $query->setMaxResults(1);
//        /** @var list<Planning> $results */
//        $results = $query->getQuery()->getResult();
//        $first = reset($results);
//        return $first === false ? null : $first;
//    }

//
//    /**
//     * @param Input $input
//     * @return string
//     */
//    protected function sportConfigsToString(Input $input): string {
//        return json_encode( array_map( function(SportConfig $sportConfig): array {
//            return $sportConfig->toArray();
//        }, $input->getSportConfigs() ) );
//    }
}
