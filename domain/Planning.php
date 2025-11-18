<?php

declare(strict_types=1);

namespace SportsPlanning;

use Exception;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\AgainstGamePlace;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstOneVsOneWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstOneVsTwoWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstTwoVsTwoWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\TogetherSportWithNrAndFields;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

final class Planning
{
    /**
     * @param list<Category> $categories
     * @param list<TogetherSportWithNrAndFields|AgainstOneVsOneWithNrAndFields|AgainstOneVsTwoWithNrAndFields|AgainstTwoVsTwoWithNrAndFields> $sports
     * @param list<Referee> $referees
     */
    private function __construct(
        public readonly array $categories,
        public readonly array $sports,
        public readonly array $referees
    )
    {
    }

    public static function fromConfiguration(PlanningConfiguration $configuration): self {

        $pouleStructure = $configuration->pouleStructure;

        $pouleNr = 1;
        $poules = array_map( function (int $nrOfPoulePlaces) use(&$pouleNr): Poule {
            return Poule::fromNrOfPlaces($pouleNr++, $nrOfPoulePlaces);
        }, $pouleStructure->toArray() );

        $sportNr = 1;
        $sports = array_map( function (SportWithNrOfFieldsAndNrOfCycles $sportWithNrOfFieldsAndNrOfCycles) use(&$sportNr): TogetherSportWithNrAndFields|AgainstOneVsOneWithNrAndFields|AgainstOneVsTwoWithNrAndFields|AgainstTwoVsTwoWithNrAndFields {
            $baseSport = $sportWithNrOfFieldsAndNrOfCycles->sport;
            $nrOfFields = $sportWithNrOfFieldsAndNrOfCycles->nrOfFields;
            if( $baseSport instanceof TogetherSport ) {
                $sport = TogetherSportWithNrAndFields::fromNrOfFields($sportNr, $baseSport, $nrOfFields);
            } else if( $baseSport instanceof AgainstOneVsOne ) {
                $sport = AgainstOneVsOneWithNrAndFields::fromNrOfFields($sportNr, $baseSport, $nrOfFields);
            } else if( $baseSport instanceof AgainstOneVsTwo ) {
                $sport = AgainstOneVsTwoWithNrAndFields::fromNrOfFields($sportNr, $baseSport, $nrOfFields);
            } else /*if( $baseSport instanceof AgainstTwoVsTwo )*/ {
                $sport = AgainstTwoVsTwoWithNrAndFields::fromNrOfFields($sportNr, $baseSport, $nrOfFields);
            }
            $sportNr++;
            return $sport;
        }, $configuration->sportsWithNrOfFieldsAndNrOfCycles );

        $referees = [];
        $nrOfReferees = $configuration->refereeInfo?->nrOfReferees;
        if ($nrOfReferees !== null) {
            for ($refNr = 1; $refNr <= $nrOfReferees; $refNr++) {
                $referees[] = new Referee($refNr);
            }
        }
        return new self($poules, $sports, $referees );
    }

    /**
     * @return list<Poule>
     * @throws Exception
     */
    public function createMergedPoules(): array {
        $poules = [];
        foreach( $this->categories as $category) {
            $poules = array_merge($poules, $category->poules);
        }
        return $poules;
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
        if ($order === PlanningWithMeta::ORDER_GAMES_BY_BATCH) {
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

    public function getSport(int $sportNr): TogetherSportWithNrAndFields|AgainstOneVsOneWithNrAndFields|AgainstOneVsTwoWithNrAndFields|AgainstTwoVsTwoWithNrAndFields
    {
        foreach ($this->sports as $sport) {
            if ($sport->sportNr === $sportNr) {
                return $sport;
            }
        }
        throw new Exception('de sport kan niet gevonden worden', E_ERROR);
    }

    /**
     * @return array<int,Poule>
     */
    public function createPouleMap(): array
    {
        $pouleMap = [];
        foreach ($this->poules as $poule) {
            $pouleMap[$poule->pouleNr] = $poule;
        }
        return $pouleMap;
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
