<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-3-17
 * Time: 20:18
 */

namespace SportsPlanning;

use SportsHelpers\Repository as BaseRepository;
use SportsHelpers\Range;

class Repository extends BaseRepository
{
    public function hasEndSuccess(Input $input): bool
    {
        $query = $this->createQueryBuilder('p')
            ->join("p.input", "pi")
            ->where('pi.structureConfig = :structureConfig')
            ->andWhere('pi.sportConfig = :sportConfig')
            ->andWhere('pi.nrOfReferees = :nrOfReferees')
            ->andWhere('pi.teamup = :teamup')
            ->andWhere('pi.selfReferee = :selfReferee')
            ->andWhere('pi.nrOfHeadtohead = :nrOfHeadtohead')
            ->andWhere('p.state = :state')
        ;

        $query = $query->setParameter('structureConfig', json_encode($input->getStructureConfig()));
        $query = $query->setParameter('sportConfig', json_encode($input->getSportConfig()));
        $query = $query->setParameter('nrOfReferees', $input->getNrOfReferees());
        $query = $query->setParameter('teamup', $input->getTeamup());
        $query = $query->setParameter('selfReferee', $input->getSelfReferee());
        $query = $query->setParameter('nrOfHeadtohead', $input->getNrOfHeadtohead());
        $query = $query->setParameter('state', Planning::STATE_SUCCESS);

        $query->setMaxResults(1);

        $x = $query->getQuery()->getResult();

        return count($x) === 1;
    }

    public function hasTried(Input $input, Range $nrOfBatchGamesRange): bool
    {
        $query = $this->createQueryBuilder('p')
            ->join("p.input", "pi")
            ->where('pi.structureConfig = :structureConfig')
            ->andWhere('pi.sportConfig = :sportConfig')
            ->andWhere('pi.nrOfReferees = :nrOfReferees')
            ->andWhere('pi.teamup = :teamup')
            ->andWhere('pi.selfReferee = :selfReferee')
            ->andWhere('pi.nrOfHeadtohead = :nrOfHeadtohead')
            ->andWhere('p.minNrOfBatchGames = :minNrOfBatchGames')
            ->andWhere('p.maxNrOfBatchGames = :maxNrOfBatchGames')
        ;

        $query = $query->setParameter('structureConfig', json_encode($input->getStructureConfig()));
        $query = $query->setParameter('sportConfig', json_encode($input->getSportConfig()));
        $query = $query->setParameter('nrOfReferees', $input->getNrOfReferees());
        $query = $query->setParameter('teamup', $input->getTeamup());
        $query = $query->setParameter('selfReferee', $input->getSelfReferee());
        $query = $query->setParameter('nrOfHeadtohead', $input->getNrOfHeadtohead());
        $query = $query->setParameter('minNrOfBatchGames', $nrOfBatchGamesRange->min);
        $query = $query->setParameter('maxNrOfBatchGames', $nrOfBatchGamesRange->max);

        $query->setMaxResults(1);

        $x = $query->getQuery()->getResult();

        return count($x) === 1;
    }

    public function get(Input $input, Range $nrOfBatchGamesRange, int $maxNrOfGamesInARow): Planning
    {
        $query = $this->createQueryBuilder('p')
            ->join("p.input", "pi")
            ->where('pi.structureConfig = :structureConfig')
            ->andWhere('pi.sportConfig = :sportConfig')
            ->andWhere('pi.nrOfReferees = :nrOfReferees')
            ->andWhere('pi.teamup = :teamup')
            ->andWhere('pi.selfReferee = :selfReferee')
            ->andWhere('pi.nrOfHeadtohead = :nrOfHeadtohead')
            ->andWhere('p.minNrOfBatchGames = :minNrOfBatchGames')
            ->andWhere('p.maxNrOfBatchGames = :maxNrOfBatchGames')
            ->andWhere('p.maxNrOfGamesInARow = :maxNrOfGamesInARow')
        ;

        $query = $query->setParameter('structureConfig', json_encode($input->getStructureConfig()));
        $query = $query->setParameter('sportConfig', json_encode($input->getSportConfig()));
        $query = $query->setParameter('nrOfReferees', $input->getNrOfReferees());
        $query = $query->setParameter('teamup', $input->getTeamup());
        $query = $query->setParameter('selfReferee', $input->getSelfReferee());
        $query = $query->setParameter('nrOfHeadtohead', $input->getNrOfHeadtohead());
        $query = $query->setParameter('minNrOfBatchGames', $nrOfBatchGamesRange->min);
        $query = $query->setParameter('maxNrOfBatchGames', $nrOfBatchGamesRange->max);
        $query = $query->setParameter('maxNrOfGamesInARow', $maxNrOfGamesInARow);

        $query->setMaxResults(1);

        return $query->getQuery()->getResult()->first();
    }

    public function getTimeout(int $maxTimeOutSeconds, array $structureConfig = null): ?Planning
    {
        $query = $this->createQueryBuilder('p')
            ->join("p.input", "pi")
            ->where('p.state = :state')
            ->andWhere('p.timeoutSeconds > 0')
            ->andWhere('pi.state = :pistate')
            ->andWhere('p.timeoutSeconds <= :maxTimeoutSeconds')
            ->orderBy('length(pi.structureConfig)', 'ASC')
            ->addOrderBy('pi.teamup', 'ASC')
            ->addOrderBy('p.id', 'ASC');
        if ($structureConfig !== null) {
            $query = $query
                ->andWhere('pi.structureConfig = :structureConfig')
                ->setParameter('structureConfig', json_encode($structureConfig));
        }

        $query = $query->setParameter('state', Planning::STATE_TIMEOUT);
        $query = $query->setParameter('pistate', Input::STATE_ALL_PLANNINGS_TRIED);
        $query = $query->setParameter('maxTimeoutSeconds', $maxTimeOutSeconds);

        $query->setMaxResults(1);
        $results = $query->getQuery()->getResult();
        $first = reset($results);
        return $first !== false ? $first : null;
    }

    public function isProcessing(int $state): bool
    {
        return $this->count(["state" => $state ]) > 0;
    }
}
