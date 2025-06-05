<?php

declare(strict_types=1);

namespace SportsPlanning;

use DateTimeImmutable;
use Exception;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\Identifiable;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Batches\Batch;
use SportsPlanning\Batches\SelfRefereeBatchOtherPoules;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\AgainstGamePlace;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Planning\BatchGamesType;
use SportsPlanning\Planning\PlanningFilter;
use SportsPlanning\Planning\PlanningState as PlanningState;
use SportsPlanning\Planning\TimeoutState;
use SportsPlanning\Planning\PlanningType as PlanningType;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstOneVsOneWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstOneVsTwoWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstTwoVsTwoWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\TogetherSportWithNrAndFields;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

final class Planning extends Identifiable
{
    public readonly int $minNrOfBatchGames;
    public readonly int $maxNrOfBatchGames;
    protected DateTimeImmutable $createdDateTime;
    protected PlanningState $state;
    protected TimeoutState|null $timeoutState = null;
    protected int $nrOfBatches = 0;
    protected int $validity = -1;
    /**
     * @var list<Poule>
     */
    public readonly array $poules;
    /**
     * @var list<TogetherSportWithNrAndFields|AgainstOneVsOneWithNrAndFields|AgainstOneVsTwoWithNrAndFields|AgainstTwoVsTwoWithNrAndFields>
     */
    public readonly array $sports;
    /**
     * @var list<Referee>
     */
    public readonly array $referees;

    public const int ORDER_GAMES_BY_BATCH = 1;
    // public const ORDER_GAMES_BY_GAMENUMBER = 2;

    public string|null $content = null;

    public function __construct(
        protected PlanningOrchestration $orchestration,
        SportRange $nrOfBatchGames,
        public readonly int $maxNrOfGamesInARow)
    {
        $this->orchestration->getPlannings()->add($this);

        $this->minNrOfBatchGames = $nrOfBatchGames->getMin();
        $this->maxNrOfBatchGames = $nrOfBatchGames->getMax();

        $this->createdDateTime = new DateTimeImmutable();
        $this->state = PlanningState::NotProcessed;

        $configuration = $orchestration->configuration;
        $pouleStructure = $configuration->pouleStructure;
        $pouleNr = 1;
        $this->poules = array_map( function (int $nrOfPoulePlaces) use(&$pouleNr): Poule {
            return new Poule($pouleNr++, $nrOfPoulePlaces);
        }, $pouleStructure->toArray() );

        $sportNr = 1;
        $this->sports = array_map( function (SportWithNrOfFieldsAndNrOfCycles $sportWithNrOfFieldsAndNrOfCycles) use(&$sportNr): TogetherSportWithNrAndFields|AgainstOneVsOneWithNrAndFields|AgainstOneVsTwoWithNrAndFields|AgainstTwoVsTwoWithNrAndFields {
            $baseSport = $sportWithNrOfFieldsAndNrOfCycles->sport;
            $nrOfFields = $sportWithNrOfFieldsAndNrOfCycles->nrOfFields;
            if( $baseSport instanceof TogetherSport ) {
                $sport = new TogetherSportWithNrAndFields($sportNr, $baseSport, $nrOfFields);
            } else if( $baseSport instanceof AgainstOneVsOne ) {
                $sport = new AgainstOneVsOneWithNrAndFields($sportNr, $baseSport, $nrOfFields);
            } else if( $baseSport instanceof AgainstOneVsTwo ) {
                $sport = new AgainstOneVsTwoWithNrAndFields($sportNr, $baseSport, $nrOfFields);
            } else /*if( $baseSport instanceof AgainstTwoVsTwo )*/ {
                $sport = new AgainstTwoVsTwoWithNrAndFields($sportNr, $baseSport, $nrOfFields);
            }
            $sportNr++;
            return $sport;
        }, $configuration->sportsWithNrOfFieldsAndNrOfCycles );

        $referees = [];
        if ($configuration->refereeInfo->selfRefereeInfo->selfReferee === SelfReferee::Disabled) {
            for ($refNr = 1; $refNr <= $configuration->refereeInfo->nrOfReferees; $refNr++) {
                $referees[] = new Referee($refNr);
            }
        }
        $this->referees = $referees;
    }

    public function minIsMaxNrOfBatchGames(): bool
    {
        return $this->minNrOfBatchGames === $this->maxNrOfBatchGames;
    }

//    public function setMinNrOfBatchGames( int $minNrOfBatchGames ) {
//        $this->minNrOfBatchGames = $minNrOfBatchGames;
//    }

//    public function setMaxNrOfBatchGames( int $maxNrOfBatchGames ) {
//        $this->maxNrOfBatchGames = $maxNrOfBatchGames;
//    }

    public function getNrOfBatchGames(): SportRange
    {
        return new SportRange($this->minNrOfBatchGames, $this->maxNrOfBatchGames);
    }

    public function isNrOfBatchGamesUnequal(): bool
    {
        return $this->getNrOfBatchGames()->difference() > 0;
    }

    public function getBatchGamesType(): BatchGamesType
    {
        if( $this->minNrOfBatchGames === $this->maxNrOfBatchGames ) {
            return BatchGamesType::RangeIsZero;
        }
        return BatchGamesType::RangeIsOneOrMore;
    }

    public function getType(): PlanningType
    {
        return $this->maxNrOfGamesInARow === 0 ? PlanningType::BatchGames : PlanningType::GamesInARow;
    }

    public function getCreatedDateTime(): DateTimeImmutable
    {
        return $this->createdDateTime;
    }

    public function setCreatedDateTime(DateTimeImmutable $createdDateTime): void
    {
        $this->createdDateTime = $createdDateTime;
    }

    public function getTimeoutState(): TimeoutState|null
    {
        return $this->timeoutState;
    }

    public function setTimeoutState(TimeoutState|null $timeoutState): void
    {
        $this->timeoutState = $timeoutState;
    }

    public function getState(): PlanningState
    {
        return $this->state;
    }

    public function setState(PlanningState $state): void
    {
        $this->state = $state;
    }

    public function getStateDescription(): string
    {
        $stateDescription = $this->getState()->name;
        if ($this->getState() === PlanningState::TimedOut) {
            $timeoutState = $this->getTimeoutState();
            if ($timeoutState !== null) {
                $stateDescription .= '(' . $timeoutState->value . ')';
            }
        }
        return $stateDescription;
    }

    public function getNrOfBatches(): int
    {
        return $this->nrOfBatches;
    }

    public function setNrOfBatches(int $nrOfBatches): void
    {
        $this->nrOfBatches = $nrOfBatches;
    }

    public function getValidity(): int
    {
        return $this->validity;
    }

    public function setValidity(int $validity): void
    {
        $this->validity = $validity;
    }

    public function getConfiguration(): PlanningConfiguration
    {
        return $this->orchestration->configuration;
    }

    public function createFirstBatch(): Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules
    {
        $games = $this->getGames(Planning::ORDER_GAMES_BY_BATCH);
        $batch = new Batch();
        $configuration = $this->getConfiguration();
        $selfReferee = $configuration->refereeInfo->selfRefereeInfo->selfReferee;
        if ($selfReferee === SelfReferee::SamePoule) {
            $batch = new SelfRefereeBatchSamePoule($batch);
        } else if( $selfReferee === SelfReferee::OtherPoules) {
            $batch = new SelfRefereeBatchOtherPoules($this->poules, $batch);
        }
        foreach ($games as $game) {
            if ($game->getBatchNr() === ($batch->getNumber() + 1)) {
                $batch = $batch->createNext();
            }
            $batch->add($game);
        }
        return $batch->getFirst();
    }

    /**
     * @param int|null $order
     * @return list<AgainstGame|TogetherGame>
     */
    public function getGames(int|null $order = null): array
    {
        $games = [];
        foreach ($this->poules as $poule) {
            $games = array_merge($games, $poule->getGames());
        }
        if ($order === Planning::ORDER_GAMES_BY_BATCH) {
            uasort($games, function (TogetherGame|AgainstGame $g1, TogetherGame|AgainstGame $g2): int {
                if ($g1->getBatchNr() === $g2->getBatchNr()) {
                    if ($g1->getField()->getUniqueIndex() === $g2->getField()->getUniqueIndex()) {
                        return 0;
                    }
                    return $g1->getField()->getUniqueIndex() < $g2->getField()->getUniqueIndex() ? -1 : 1;
                }
                return $g1->getBatchNr() - $g2->getBatchNr();
            });
        }
        return array_values($games);
    }

    public function convertAgainstGameToHomeAway(AgainstGame $againstGame): OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway {
        $homeGamePlaces = $againstGame->getSideGamePlaces(AgainstSide::Home);
        $homePlaceNrs = array_map(fn(AgainstGamePlace $gamePlace) => $gamePlace->placeNr, $homeGamePlaces);
        $awayGamePlaces = $againstGame->getSideGamePlaces(AgainstSide::Away);
        $awayPlaceNrs = array_map(fn(AgainstGamePlace $gamePlace) => $gamePlace->placeNr, $awayGamePlaces);
        $sportWithNrAndFields = $this->getSport($againstGame->getField()->sportNr);
        if( $sportWithNrAndFields->sport instanceof AgainstOneVsOne ) {
            return new OneVsOneHomeAway($homePlaceNrs[0], $awayPlaceNrs[0]);
        } else if( $sportWithNrAndFields->sport instanceof AgainstOneVsTwo ) {
            return new OneVsTwoHomeAway($homePlaceNrs[0], new DuoPlaceNr($awayPlaceNrs[0], $awayPlaceNrs[1]));
        } else { // TwoVsTwoHomeAway
            return new TwoVsTwoHomeAway(
                new DuoPlaceNr($homePlaceNrs[0], $homePlaceNrs[1]),
                new DuoPlaceNr($awayPlaceNrs[0], $awayPlaceNrs[1]));
        }
    }

    public function removeGames(): void
    {
        foreach( $this->poules as $poule) {
            $poule->removeGames();
        }
    }

    public function createFilter(): PlanningFilter
    {
        return new PlanningFilter(null, null, $this->getNrOfBatchGames(), $this->maxNrOfGamesInARow);
    }

    // from most efficient to less efficient

    /*public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function getPoulesOrderedBySize(): array
    {
        foreach ($this->getPoules() as $poule) {
            if ($poule->getNumber() === $pouleNr) {
                return $poule;
            }
        }
        throw new Exception('de poule kan niet gevonden worden', E_ERROR);
    }
    */

    public function getSport(int $sportNr): TogetherSportWithNrAndFields|AgainstOneVsOneWithNrAndFields|AgainstOneVsTwoWithNrAndFields|AgainstTwoVsTwoWithNrAndFields
    {
        foreach ($this->sports as $sport) {
            if ($sport->sportNr === $sportNr) {
                return $sport;
            }
        }
        throw new Exception('de sport kan niet gevonden worden', E_ERROR);
    }

    public function getPoule(int $pouleNr): Poule
    {
        foreach ($this->poules as $poule) {
            if ($poule->pouleNr === $pouleNr) {
                return $poule;
            }
        }
        throw new Exception('de poule kan niet gevonden worden', E_ERROR);
    }

    public function getFirstPoule(): Poule
    {
        return $this->getPoule(1);
    }

    public function getLastPoule(): Poule
    {
        return $this->getPoule(count($this->poules));
    }

    /**
     * @return list<Place>
     */
    public function getPlaces(): array
    {
        $places = [];
        foreach ($this->poules as $poule) {
            $places = array_merge($places, $poule->places);
        }
        return $places;
    }

    public function getPlace(string $location): Place
    {
        $pos = strpos($location, ".");
        if ($pos === false) {
            throw new Exception('geen punt gevonden in locatie', E_ERROR);
        }
        $pouleNr = (int)substr($location, 0, $pos);
        $placeNr = (int)substr($location, $pos + 1);
        return $this->getPoule($pouleNr)->getPlace($placeNr);
    }

    public function getNrOfPlaces(): int
    {
        return $this->getConfiguration()->pouleStructure->getNrOfPlaces();
    }

    /**
     * @return list<Field>
     */
    public function getFields(): array
    {
        $fields = [];
        foreach ($this->sports as $sport) {
            $fields = array_merge($fields, $sport->fields);
        }
        return $fields;
    }

    public function getReferee(int $refereeNr): Referee
    {
        foreach ($this->referees as $referee) {
            if ($referee->refereeNr === $refereeNr) {
                return $referee;
            }
        }
        throw new Exception('de scheidsrechter kan niet gevonden worden', E_ERROR);
    }
}
