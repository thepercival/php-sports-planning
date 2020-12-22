<?php

namespace SportsPlanning\Resource\RefereePlace;

use DateTimeImmutable;
use SportsPlanning\Batch\Output as BatchOutput;
use SportsPlanning\Game\AgainstEachOther as AgainstEachOtherGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Planning;
use SportsPlanning\Game;
use SportsPlanning\Place;
use SportsPlanning\Input;
use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
use SportsPlanning\Resource\GameCounter\Place as PlaceGameCounter;
use SportsPlanning\TimeoutException;

class Service
{
    /**
     * @var Planning
     */
    private $planning;
    /**
     * @var int
     */
    protected $nrOfPlaces;
    /**
     * @var array
     */
    // private $canBeSamePoule;
    /**
     * @var Replacer
     */
    private $replacer;
    private bool $throwOnTimeout;

    public function __construct(Planning $planning)
    {
        $this->planning = $planning;
        $this->nrOfPlaces = $this->planning->getPouleStructure()->getNrOfPlaces();
        $this->replacer = new Replacer($planning->getInput()->getSelfReferee() === Input::SELFREFEREE_SAMEPOULE);
        $this->throwOnTimeout = true;
    }

    protected function getInput(): Input
    {
        return $this->planning->getInput();
    }

    public function assign(SelfRefereeBatch $batch ): int
    {
        if (!$this->getInput()->selfRefereeEnabled()) {
            return Planning::STATE_SUCCEEDED;
        }
        return $this->assignHelper( $batch );
    }

    public function assignHelper(SelfRefereeBatch $batch): int
    {
        $timeoutDateTime = (new DateTimeImmutable())->modify("+" . $this->planning->getTimeoutSeconds() . " seconds");
        $this->replacer->setTimeoutDateTime( $timeoutDateTime );
        $refereePlaces = $this->getRefereePlaces();
        try {
            if ($this->assignBatch($batch, $batch->getBase()->getGames(), $refereePlaces, $timeoutDateTime)) {
                return Planning::STATE_SUCCEEDED;
            };
        } catch (TimeoutException $timeoutExc) {
            return Planning::STATE_TIMEDOUT;
        }
        return Planning::STATE_FAILED;
    }

    /**
     * @return array|PlaceGameCounter[]
     */
    protected function getRefereePlaces(): array
    {
        $refereePlaces = [];
        foreach ($this->planning->getPlaces() as $place) {
            $gameCounter = new PlaceGameCounter($place);
            $refereePlaces[$gameCounter->getIndex()] = $gameCounter;
        }
        return $refereePlaces;
    }

    /**
     * @param SelfRefereeBatch $batch
     * @param array|TogetherGame[]|AgainstEachOtherGame[] $batchGames
     * @param array|PlaceGameCounter[] $refereePlaces
     * @param DateTimeImmutable $timeoutDateTime
     * @return bool
     * @throws TimeoutException
     */
    protected function assignBatch(
        SelfRefereeBatch $batch,
        array $batchGames,
        array $refereePlaces,
        DateTimeImmutable $timeoutDateTime
    ): bool {
        if (count($batchGames) === 0) { // batchsuccess
            if ($batch->hasNext() === false) { // endsuccess
                // (new BatchOutput())->output($batch);
                return $this->equallyAssign($batch);
            }
            if ( $this->throwOnTimeout && (new DateTimeImmutable()) > $timeoutDateTime) {
                throw new TimeoutException(
                    "exceeded maximum duration",
                    E_ERROR
                );
            }
            $nextBatch = $batch->getNext();
            return $this->assignBatch($nextBatch, $nextBatch->getBase()->getGames(), $refereePlaces, $timeoutDateTime);
        }

        $game = array_shift($batchGames);
        /** @var PlaceGameCounter $refereePlace */
        foreach ($refereePlaces as $refereePlace) {
            if ($this->isRefereePlaceAssignable($batch, $game, $refereePlace->getPlace())) {
                $newRefereePlaces = $this->assignRefereePlace($batch, $game, $refereePlace->getPlace(), $refereePlaces);
                if ($this->assignBatch($batch, $batchGames, $newRefereePlaces, $timeoutDateTime)) {
                    return true;
                }
                // statics
                $game->setRefereePlace( null );
                $batch->removeAsReferee($refereePlace->getPlace(), null );
            }
        }
        return false;
    }

    protected function equallyAssign(SelfRefereeBatch $batch): bool
    {
        return $this->replacer->replaceUnequals($this->planning, $batch->getFirst());
    }

    private function isRefereePlaceAssignable(SelfRefereeBatch $batch, Game $game, Place $refereePlace): bool
    {
        if ($batch->getBase()->isParticipating($refereePlace) || $batch->isParticipatingAsReferee($refereePlace)) {
            return false;
        }
        if ($this->planning->getInput()->getSelfReferee() === Input::SELFREFEREE_SAMEPOULE) {
            return $refereePlace->getPoule() === $game->getPoule();
        }
//        if (array_key_exists($batch->getNumber(), $this->canBeSamePoule)
//            && $this->canBeSamePoule[$batch->getNumber()] === $refereePlace->getPoule()) {
//            return true;
//        }
        return $refereePlace->getPoule() !== $game->getPoule();
    }

    /**
     * @param SelfRefereeBatch $batch
     * @param TogetherGame|AgainstEachOtherGame $game
     * @param Place $assignPlace
     * @param array|PlaceGameCounter[] $refereePlaces
     * @return array|PlaceGameCounter[]
     */
    private function assignRefereePlace(SelfRefereeBatch $batch, $game, Place $assignPlace, array $refereePlaces): array
    {
        $batch->addAsReferee($game, $assignPlace);

        $newRefereePlaces = [];
        foreach ($refereePlaces as $refereePlace) {
            $place = $refereePlace->getPlace();
            $newRefereePlace = new PlaceGameCounter($place, $refereePlace->getNrOfGames());
            $newRefereePlaces[$newRefereePlace->getIndex()] = $newRefereePlace;
            if ($place === $assignPlace) {
                $newRefereePlace->increase();
            }
        }
        uasort(
            $newRefereePlaces,
            function (PlaceGameCounter $a, PlaceGameCounter $b): int {
                return $a->getNrOfGames() < $b->getNrOfGames() ? -1 : 1;
            }
        );
        return $newRefereePlaces;
    }

    public function disableThrowOnTimeout() {
        $this->throwOnTimeout = false;
        $this->replacer->disableThrowOnTimeout();
    }
}
