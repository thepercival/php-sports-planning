<?php
declare(strict_types=1);

namespace SportsPlanning\Planning;

use Psr\Log\LoggerInterface;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
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
        $gameGenerator = new GameGenerator();
        if (!$this->throwOnTimeout) {
            $gameGenerator->disableThrowOnTimeout();
        }
        $state = $gameGenerator->generateUnAssignedGames($planning);
        if ($state === Planning::STATE_FAILED || $state === Planning::STATE_TIMEDOUT) {
            $planning->getAgainstGames()->clear();
            $planning->getTogetherGames()->clear();
            $planning->setState($state);
            return;
        }
        $games = $planning->getGames(/*Game::ORDER_BY_GAMENUMBER*/);

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
                $planning->setState($state);
                return;
            }
        }
        $planning->setState(Planning::STATE_SUCCEEDED);
        $planning->setNrOfBatches($firstBatch->getLeaf()->getNumber());
    }

    public function disableThrowOnTimeout(): void
    {
        $this->throwOnTimeout = false;
    }
}
