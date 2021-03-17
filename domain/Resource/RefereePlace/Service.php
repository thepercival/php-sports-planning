<?php

namespace SportsPlanning\Resource\RefereePlace;

use DateTimeImmutable;
use SportsPlanning\Batch\Output as BatchOutput;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\SelfReferee;
use SportsPlanning\Planning;
use SportsPlanning\Game;
use SportsPlanning\Place;
use SportsPlanning\Input;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Resource\GameCounter\Place as PlaceGameCounter;
use SportsPlanning\TimeoutException;

class Service
{
    protected int $nrOfPlaces;
    private Replacer $replacer;
    private bool $throwOnTimeout;

    public function __construct(private Planning $planning)
    {
        $this->nrOfPlaces = $this->planning->getPouleStructure()->getNrOfPlaces();
        $this->replacer = new Replacer($planning->getInput()->getSelfReferee() === SelfReferee::SAMEPOULE);
        $this->throwOnTimeout = true;
    }

    protected function getInput(): Input
    {
        return $this->planning->getInput();
    }

    public function assign(SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch): int
    {
        return $this->assignHelper($batch);
    }

    public function assignHelper(SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch): int
    {
        $timeoutDateTime = (new DateTimeImmutable())->modify("+" . $this->planning->getTimeoutSeconds() . " seconds");
        $this->replacer->setTimeoutDateTime($timeoutDateTime);
        $refereePlaceMap = $this->getRefereePlaceMap();
        try {
            if ($this->assignBatch($batch, $batch->getBase()->getGames(), $refereePlaceMap, $timeoutDateTime)) {
                return Planning::STATE_SUCCEEDED;
            };
        } catch (TimeoutException $timeoutExc) {
            return Planning::STATE_TIMEDOUT;
        }
        return Planning::STATE_FAILED;
    }

    /**
     * @return array<string,PlaceGameCounter>
     */
    protected function getRefereePlaceMap(): array
    {
        $refereePlaces = [];
        foreach ($this->planning->getPlaces() as $place) {
            $gameCounter = new PlaceGameCounter($place);
            $refereePlaces[$gameCounter->getIndex()] = $gameCounter;
        }
        return $refereePlaces;
    }

    /**
     * @param SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch
     * @param list<TogetherGame|AgainstGame> $batchGames
     * @param array<string,PlaceGameCounter> $refereePlaceMap
     * @param DateTimeImmutable $timeoutDateTime
     * @return bool
     * @throws TimeoutException
     */
    protected function assignBatch(
        SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch,
        array $batchGames,
        array $refereePlaceMap,
        DateTimeImmutable $timeoutDateTime
    ): bool {
        if (count($batchGames) === 0) { // batchsuccess
            $nextBatch = $batch->getNext();
            if ($nextBatch === null) { // endsuccess
                // (new BatchOutput())->output($batch);
                return $this->equallyAssign($batch);
            }
            if ($this->throwOnTimeout && (new DateTimeImmutable()) > $timeoutDateTime) {
                throw new TimeoutException(
                    "exceeded maximum duration",
                    E_ERROR
                );
            }
            return $this->assignBatch($nextBatch, $nextBatch->getBase()->getGames(), $refereePlaceMap, $timeoutDateTime);
        }

        $game = array_shift($batchGames);
        foreach ($refereePlaceMap as $refereePlace) {
            if ($this->isRefereePlaceAssignable($batch, $game, $refereePlace->getPlace())) {
                $newRefereePlaces = $this->assignRefereePlace($batch, $game, $refereePlace->getPlace(), $refereePlaceMap);
                if ($this->assignBatch($batch, $batchGames, $newRefereePlaces, $timeoutDateTime)) {
                    return true;
                }
                // statics
                $game->setRefereePlace(null);
                $batch->removeAsReferee($refereePlace->getPlace(), null);
            }
        }
        return false;
    }

    protected function equallyAssign(SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch): bool
    {
        return $this->replacer->replaceUnequals($this->planning, $batch->getFirst());
    }

    private function isRefereePlaceAssignable(SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch, Game $game, Place $refereePlace): bool
    {
        if ($batch->getBase()->isParticipating($refereePlace) || $batch->isParticipatingAsReferee($refereePlace)) {
            return false;
        }
        if ($this->planning->getInput()->getSelfReferee() === SelfReferee::SAMEPOULE) {
            return $refereePlace->getPoule() === $game->getPoule();
        }
//        if (array_key_exists($batch->getNumber(), $this->canBeSamePoule)
//            && $this->canBeSamePoule[$batch->getNumber()] === $refereePlace->getPoule()) {
//            return true;
//        }
        return $refereePlace->getPoule() !== $game->getPoule();
    }

    /**
     * @param SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch
     * @param TogetherGame|AgainstGame $game
     * @param Place $assignPlace
     * @param array<string,PlaceGameCounter> $refereePlaceMap
     * @return array<string,PlaceGameCounter>
     */
    private function assignRefereePlace(
        SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch,
        TogetherGame|AgainstGame $game,
        Place $assignPlace,
        array $refereePlaceMap
    ): array
    {
        $batch->addAsReferee($game, $assignPlace);

        $newRefereePlaceMap = [];
        foreach ($refereePlaceMap as $refereePlace) {
            $place = $refereePlace->getPlace();
            $newRefereePlace = new PlaceGameCounter($place, $refereePlace->getNrOfGames());
            $newRefereePlaceMap[$newRefereePlace->getIndex()] = $newRefereePlace;
            if ($place === $assignPlace) {
                $newRefereePlace->increase();
            }
        }
        uasort(
            $newRefereePlaceMap,
            function (PlaceGameCounter $a, PlaceGameCounter $b): int {
                return $a->getNrOfGames() < $b->getNrOfGames() ? -1 : 1;
            }
        );
        return $newRefereePlaceMap;
    }

    public function disableThrowOnTimeout(): void
    {
        $this->throwOnTimeout = false;
        $this->replacer->disableThrowOnTimeout();
    }
}
