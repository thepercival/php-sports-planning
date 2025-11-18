<?php

declare(strict_types=1);

namespace SportsPlanning\SerializationHandler;

//use Doctrine\Common\Collections\ArrayCollection;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use SportsHelpers\SportRange;
use SportsPlanning\Field;
use SportsPlanning\Input;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Input\Configuration as InputConfiguration;

use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Poule;

/**
 * @psalm-type _AgainstGame = array{fieldUniqueIndex: string, pouleNr: int, refereePlaceLocation: string|null}
 * @psalm-type _TogetherGame = array{fieldUniqueIndex: string, pouleNr: int}
 * @psalm-type _FieldValue = array{inputConfiguration: InputConfiguration, againstGames: list<_AgainstGame>, togetherGames: list<_TogetherGame>, minNrOfBatchGames: int, maxNrOfBatchGames: int, maxNrOfGamesInARow: int, createdDateTime: string, nrOfBatches: int, state: string, validity: int}
 */
final class PlanningHandler extends Handler implements SubscribingHandlerInterface
{
    public function __construct(/*protected DummyCreator $dummyCreator*/)
    {
    }

    #[\Override]
    /**
     * @psalm-return list<array<string, int|string>>
     */
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
        /** @var InputConfiguration|null $inputConfiguration */
        $inputConfiguration = $this->getProperty(
            $visitor,
            $fieldValue,
            'inputConfiguration',
            InputConfiguration::class
        );
        if ($inputConfiguration === null ) {
            throw new \Exception('malformd json => no valid inputConfiguration', E_ERROR);
        }

        $input = new Input($inputConfiguration);
        $nrOfBatchGamesRange = new SportRange($fieldValue['minNrOfBatchGames'], $fieldValue['maxNrOfBatchGames']);
        $planning = new Planning($input, $nrOfBatchGamesRange, $fieldValue['maxNrOfGamesInARow']);

        $planning->setCreatedDateTime(new DateTimeImmutable($fieldValue['createdDateTime']));
        $planning->setNrOfBatches($fieldValue['nrOfBatches']);
        $planning->setState(Planning\State::from($fieldValue['state']));
        $planning->setValidity($fieldValue['validity']);

        $pouleMap = $this->getPouleMap($input->getPoules());
        $placeLocationMap = $this->getPlaceLocationMap($input->getPoules());
        $fieldMap = $this->getFieldMap($input->getFields());

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
     * @param Collection<int|string, Poule> $poules
     * @return array<int, Poule>
     */
    private function getPouleMap(Collection $poules): array {
        $pouleMap = [];
        foreach ( $poules as $poule ) {
            $pouleMap[$poule->getNumber()] = $poule;
        }
        return $pouleMap;
    }

    /**
     * @param Collection<int|string, Poule> $poules
     * @return array<string, Place>
     */
    private function getPlaceLocationMap(Collection $poules): array {
        $placeLocationMap = [];
        foreach ( $poules as $poule ) {
            foreach ( $poule->getPlaces() as $place ) {
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
