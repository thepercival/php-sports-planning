<?php

declare(strict_types=1);

namespace SportsPlanning\SerializationHandler;

//use Doctrine\Common\Collections\ArrayCollection;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use SportsHelpers\SportRange;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\Field;
use SportsPlanning\Game\AgainstGame as AgainstGame;
use SportsPlanning\Game\TogetherGame as TogetherGame;
use SportsPlanning\PlanningOrchestration;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Poule;

/**
 * @psalm-type _AgainstGame = array{fieldUniqueIndex: string, pouleNr: int, refereePlaceLocation: string|null}
 * @psalm-type _TogetherGame = array{fieldUniqueIndex: string, pouleNr: int}
 * @psalm-type _FieldValue = array{planningConfiguration: PlanningConfiguration, againstGames: list<_AgainstGame>, togetherGames: list<_TogetherGame>, minNrOfBatchGames: int, maxNrOfBatchGames: int, maxNrOfGamesInARow: int, createdDateTime: string, nrOfBatches: int, state: string, validity: int}
 */
final class PlanningHandler extends Handler implements SubscribingHandlerInterface
{
    public function __construct(/*protected DummyCreator $dummyCreator*/)
    {
    }

    /**
     * @psalm-return list<array<string, int|string>>
     */
    #[\Override]
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(Planning::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param _FieldValue $fieldValue
     * @param array<string, array> $type
     * @param Context $context
     * @return Planning
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): Planning {
        /** @var PlanningConfiguration|null $planningConfiguration */
        $planningConfiguration = $this->getProperty(
            $visitor,
            $fieldValue,
            'planningConfiguration',
            PlanningConfiguration::class
        );
        if ($planningConfiguration === null ) {
            throw new \Exception('malformd json => no valid planningConfiguration', E_ERROR);
        }

        $orchestration = new PlanningOrchestration($planningConfiguration);
        $nrOfBatchGamesRange = new SportRange($fieldValue['minNrOfBatchGames'], $fieldValue['maxNrOfBatchGames']);
        $planning = new Planning($orchestration, $nrOfBatchGamesRange, $fieldValue['maxNrOfGamesInARow']);

        $planning->setCreatedDateTime(new DateTimeImmutable($fieldValue['createdDateTime']));
        $planning->setNrOfBatches($fieldValue['nrOfBatches']);
        $planning->setState(Planning\PlanningState::from($fieldValue['state']));
        $planning->setValidity($fieldValue['validity']);

        $pouleMap = $this->getPouleMap($planning->poules);
        $placeLocationMap = $this->getPlaceLocationMap($planning->poules);
        $fieldMap = $this->getFieldMap($planning->getFields());

        foreach ($fieldValue["againstGames"] as $arrAgainstGame) {
            $fieldValue["againstGame"] = $arrAgainstGame;
            $fieldValue["againstGame"]["planning"] = $planning;
            $poule = $pouleMap[ $arrAgainstGame['pouleNr'] ];
            $fieldValue["againstGame"]["poule"] = $poule;
            $fieldValue["againstGame"]["placeLocationMap"] = $placeLocationMap;
            $field = $fieldMap[ $arrAgainstGame['fieldUniqueIndex'] ];
            $fieldValue["againstGame"]["field"] = $field;
            $this->getProperty(
                $visitor,
                $fieldValue,
                "againstGame",
                AgainstGame::class
            );
        }

        foreach ($fieldValue["togetherGames"] as $arrTogetherGame) {
            $fieldValue["togetherGame"] = $arrTogetherGame;
            $fieldValue["togetherGame"]["planning"] = $planning;
            $poule = $pouleMap[ $arrTogetherGame['pouleNr'] ];
            $fieldValue["togetherGame"]["poule"] = $poule;
            $fieldValue["togetherGame"]["placeLocationMap"] = $placeLocationMap;
            $field = $fieldMap[ $arrTogetherGame['fieldUniqueIndex'] ];
            $fieldValue["togetherGame"]["field"] = $field;
            $this->getProperty(
                $visitor,
                $fieldValue,
                "togetherGame",
                TogetherGame::class
            );
        }
        return $planning;
    }

    /**
     * @param list<Poule> $poules
     * @return array<int, Poule>
     */
    private function getPouleMap(array $poules): array {
        $pouleMap = [];
        foreach ( $poules as $poule ) {
            $pouleMap[$poule->pouleNr] = $poule;
        }
        return $pouleMap;
    }

    /**
     * @param list<Poule> $poules
     * @return array<string, Place>
     */
    private function getPlaceLocationMap(array $poules): array {
        $placeLocationMap = [];
        foreach ( $poules as $poule ) {
            foreach ( $poule->places as $place ) {
                $placeLocationMap[$place->getUniqueIndex()] = $place;
            }
        }
        return $placeLocationMap;
    }

    /**
     * @param list<Field> $fields
     * @return array<string, Field>
     */
    private function getFieldMap(array $fields): array {
        $fieldMap = [];
        foreach ( $fields as $field ) {
            $fieldMap[$field->getUniqueIndex()] = $field;
        }
        return $fieldMap;
    }

//    /**
//     * @param array<string, int|bool|array<string, int|bool>> $arrConfig
//     * @param CompetitionSport $competitionSport
//     * @param Round $round
//     * @param ScoreConfig|null $previous
//     * @return ScoreConfig
//     */
//    protected function createAgainstGame(
//        array $arrConfig,
//        CompetitionSport $competitionSport,
//        Round $round,
//        ScoreConfig $previous = null
//    ): ScoreConfig {
//        $config = new ScoreConfig(
//            $competitionSport,
//            $round,
//            $arrConfig['direction'],
//            $arrConfig['maximum'],
//            $arrConfig['enabled'],
//            $previous
//        );
//        if (isset($arrConfig['next'])) {
//            $this->createScoreConfig($arrConfig['next'], $competitionSport, $round, $config);
//        }
//        return $config;
//    }
//
//    /**
//     * @param array<string, int|bool|array<string, int|bool|PointsCalculation>> $arrConfig
//     * @param CompetitionSport $competitionSport
//     * @param Round $round
//     * @return AgainstQualifyConfig
//     */
//    protected function createAgainstQualifyConfig(
//        array $arrConfig,
//        CompetitionSport $competitionSport,
//        Round $round
//    ): AgainstQualifyConfig {
//        $config = new AgainstQualifyConfig($competitionSport, $round, $arrConfig['pointsCalculation']);
//        $config->setWinPoints($arrConfig['winPoints']);
//        $config->setWinPointsExt($arrConfig['winPointsExt']);
//        $config->setDrawPoints($arrConfig['drawPoints']);
//        $config->setDrawPointsExt($arrConfig['drawPointsExt']);
//        $config->setLosePointsExt($arrConfig['losePointsExt']);
//        return $config;
//    }
}
