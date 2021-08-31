<?php
declare(strict_types=1);

namespace SportsPlanning\Planning;

use Psr\Log\LoggerInterface;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\GameGenerator;
use SportsPlanning\Planning;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use SportsPlanning\Resource\Service as ResourceService;

class GameCreator
{
    protected bool $throwOnTimeout;

    public function __construct(protected LoggerInterface $logger)
    {
        $this->throwOnTimeout = true;
    }

    public function createAssignedGames(Planning $planning): void
    {
        (new GameGenerator($this->logger))->generateUnassignedGames($planning);

        $games = $this->getGamesByGameNumber($planning);

        $resourceService = new ResourceService($planning, $this->logger);
        if (!$this->throwOnTimeout) {
            $resourceService->disableThrowOnTimeout();
        }
        $state = $resourceService->assign($games);
        if ($state === Planning::STATE_FAILED || $state === Planning::STATE_TIMEDOUT) {
            $planning->getAgainstGames()->clear();
            $planning->getTogetherGames()->clear();
            $planning->setState($state);
            return;
        }

        $firstBatch = $planning->createFirstBatch();
        if ($firstBatch instanceof SelfRefereeBatchOtherPoule || $firstBatch instanceof SelfRefereeBatchSamePoule) {
            $refereePlaceService = new RefereePlaceService($planning);
            if (!$this->throwOnTimeout) {
                $refereePlaceService->disableThrowOnTimeout();
            }
            $state = $refereePlaceService->assign($firstBatch);
            if ($state === Planning::STATE_FAILED || $state === Planning::STATE_TIMEDOUT) {
                $this->logger->error('   could not assign refereeplaces');
                $planning->setState($state);
                return;
            }
        }
        $planning->setState(Planning::STATE_SUCCEEDED);
        $planning->setNrOfBatches($firstBatch->getLeaf()->getNumber());
    }

    /**
     * @param Planning $planning
     * @return list<AgainstGame|TogetherGame>
     */
    /*protected function getGamesByGameNumber(Planning $planning): array
    {
        $games = $planning->getGames();
        uasort($games, function (AgainstGame|TogetherGame $g1, AgainstGame|TogetherGame $g2): int {
            if ($this->getDefaultGameNumber($g1) !== $this->getDefaultGameNumber($g2)) {
                return $this->getDefaultGameNumber($g1) - $this->getDefaultGameNumber($g2);
            }
            return $g1->getPoule()->getNumber() - $g2->getPoule()->getNumber();
        });
        return array_values($games);
    }

    protected function getDefaultGameNumber(TogetherGame|AgainstGame $game): int
    {
        if ($game instanceof AgainstGame) {
            return $game->getGameRoundNumber();
        }
        $firstGamePlace = $game->getPlaces()->first();
        return $firstGamePlace !== false ? $firstGamePlace->getGameRoundNumber() : 0;
    }*/

    /**
     * @param Planning $planning
     * @return list<AgainstGame|TogetherGame>
     */
    protected function getGamesByGameNumber(Planning $planning): array
    {
        $games = $planning->getGames();
        uasort($games, function (AgainstGame|TogetherGame $g1, AgainstGame|TogetherGame $g2): int {
            if ($this->getDefaultGameNumber($g1) !== $this->getDefaultGameNumber($g2)) {
                return $this->getDefaultGameNumber($g1) - $this->getDefaultGameNumber($g2);
            }
            $sumPlaceNrs1 = $this->getSumPlaceNrs($g1);
            $sumPlaceNrs2 = $this->getSumPlaceNrs($g2);
            if ($sumPlaceNrs1 !== $sumPlaceNrs2) {
                return $sumPlaceNrs1 - $sumPlaceNrs2;
            }
            return $g1->getPoule()->getNumber() - $g2->getPoule()->getNumber();
        });
        return array_values($games);
    }

    protected function getSumPlaceNrs(AgainstGame|TogetherGame $game): int
    {
        $total = 0;
        foreach( $game->getPlaces() as $gamePlace ) {
            $total += $gamePlace->getPlace()->getNumber();
        }
        return $total;
    }

    protected function getDefaultGameNumber(TogetherGame|AgainstGame $game): int
    {
        if ($game instanceof AgainstGame) {
            return $game->getGameRoundNumber();
        }
        $firstGamePlace = $game->getPlaces()->first();
        return $firstGamePlace !== false ? $firstGamePlace->getGameRoundNumber() : 0;
    }

    public function disableThrowOnTimeout(): void
    {
        $this->throwOnTimeout = false;
    }
}
