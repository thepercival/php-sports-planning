<?php

declare(strict_types=1);

namespace SportsPlanning\Game;

use Psr\Log\LoggerInterface;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Output as GameOutput;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Planning;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use SportsPlanning\Resource\Service as ResourceService;

class Assigner
{
    protected bool $throwOnTimeout;

    public function __construct(protected LoggerInterface $logger)
    {
        $this->throwOnTimeout = true;
    }

    public function assignGames(Planning $planning): void
    {
        $games = (new PreAssignSorter())->getGames($planning);
        // (new GameOutput($this->logger))->outputGames($games);

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

    public function disableThrowOnTimeout(): void
    {
        $this->throwOnTimeout = false;
    }
}
