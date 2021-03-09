<?php

namespace SportsPlanning\Planning;

use SportsHelpers\PouleStructure;
use SportsHelpers\Repository as BaseRepository;
use SportsHelpers\Range;
use SportsHelpers\SportConfig as SportConfig;
use SportsPlanning\Planning;
use SportsPlanning\Input;

class Repository extends BaseRepository
{
    public function resetBatchGamePlanning(Planning $planning, int $state)
    {
        $planning->setState($state);
        $this->removeGamesInARowPlannings($planning);
        foreach ($planning->getPoules() as $poule) {
            $againstGames = $poule->getAgainstGames();
            while ($againstGames->count() > 0) {
                $game = $againstGames->first();
                $againstGames->removeElement($game);
                $this->remove($game);
            }
            $togetherGames = $poule->getTogetherGames();
            while ($togetherGames->count() > 0) {
                $game = $togetherGames->first();
                $togetherGames->removeElement($game);
                $this->remove($game);
            }
        }
        $this->save($planning);
    }

    protected function removeGamesInARowPlannings(Planning $batchGamePlanning)
    {
        $gamesInARowPlannings = $batchGamePlanning->getGamesInARowPlannings();
        while (count($gamesInARowPlannings) > 0) {
            $planning = array_pop($gamesInARowPlannings);
            $planning->getInput()->getPlannings()->removeElement($planning);
            $this->remove($planning);
        }
    }

    public function createGamesInARowPlannings(Planning $planning)
    {
        $maxNrOfGamesInARowInput = $planning->getInput()->getMaxNrOfGamesInARow();
        for ($gamesInARow = 1; $gamesInARow <= $maxNrOfGamesInARowInput - 1; $gamesInARow++) {
            $planning = new Planning($planning->getInput(), $planning->getNrOfBatchGames(), $gamesInARow);
            $this->save($planning);
        }
    }



//    public function hasEndSuccess(Input $input): bool
//    {
//        $query = $this->createQueryBuilder('p')
//            ->join("p.input", "pi")
//            ->where('pi.pouleStructureDb = :pouleStructure')
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
//            ->where('pi.pouleStructureDb = :pouleStructure')
//            ->andWhere('pi.sportConfigDb = :sportConfig')
//            ->andWhere('pi.nrOfReferees = :nrOfReferees')
//            ->andWhere('pi.teamup = :teamup')
//            ->andWhere('pi.selfReferee = :selfReferee')
//            ->andWhere('pi.nrOfHeadtohead = :nrOfHeadtohead')
//            ->andWhere('p.minNrOfBatchGames = :minNrOfBatchGames')
//            ->andWhere('p.maxNrOfBatchGames = :maxNrOfBatchGames')
//        ;
//
//        $query = $query->setParameter('pouleStructure', json_encode($input->getPouleStructure()->toArray()));
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
//    public function get(Input $input, Range $nrOfBatchGamesRange, int $maxNrOfGamesInARow): Planning
//    {
//        $query = $this->createQueryBuilder('p')
//            ->join("p.input", "pi")
//            ->where('pi.pouleStructureDb = :pouleStructure')
//            ->andWhere('pi.sportConfigDb = :sportConfig')
//            ->andWhere('pi.nrOfReferees = :nrOfReferees')
//            ->andWhere('pi.teamup = :teamup')
//            ->andWhere('pi.selfReferee = :selfReferee')
//            ->andWhere('pi.nrOfHeadtohead = :nrOfHeadtohead')
//            ->andWhere('p.minNrOfBatchGames = :minNrOfBatchGames')
//            ->andWhere('p.maxNrOfBatchGames = :maxNrOfBatchGames')
//            ->andWhere('p.maxNrOfGamesInARow = :maxNrOfGamesInARow')
//        ;
//
//        $query = $query->setParameter('pouleStructure', json_encode($input->getPouleStructure()->toArray()));
//        $query = $query->setParameter('sportConfig', $this->sportConfigsToString($input));
//        $query = $query->setParameter('nrOfReferees', $input->getNrOfReferees());
//        $query = $query->setParameter('teamup', $input->getTeamup());
//        $query = $query->setParameter('selfReferee', $input->getSelfReferee());
//        $query = $query->setParameter('nrOfHeadtohead', $input->getNrOfHeadtohead());
//        $query = $query->setParameter('minNrOfBatchGames', $nrOfBatchGamesRange->min);
//        $query = $query->setParameter('maxNrOfBatchGames', $nrOfBatchGamesRange->max);
//        $query = $query->setParameter('maxNrOfGamesInARow', $maxNrOfGamesInARow);
//
//        $query->setMaxResults(1);
//
//        return $query->getQuery()->getResult()->first();
//    }
//

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
