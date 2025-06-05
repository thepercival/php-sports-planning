<?php

declare(strict_types=1);

namespace SportsPlanning\SerializationHandler;

use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use SportsPlanning\Field;
use SportsPlanning\Game\TogetherGame as TogetherGame;
use SportsPlanning\Game\TogetherGamePlace as TogetherGamePlace;
use SportsPlanning\Poule;

/**
 * @psalm-type _TogetherGamePlace = array{placeNr: int, cycleNr: int}
 * @psalm-type _TogetherGame = array{poule: Poule, gamePlaces: list<_TogetherGamePlace>, field: Field, refereePlaceUniqueIndex: string|null, refereeNr: int|null, batchNr: int}
 */
final class TogetherGameHandler extends Handler implements SubscribingHandlerInterface
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
        return static::getDeserializationMethods(TogetherGame::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param _TogetherGame $fieldValue
     * @param array<string, array> $type
     * @param Context $context
     * @return TogetherGame
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): TogetherGame {

        $togetherGame = new TogetherGame($fieldValue['poule'],$fieldValue['field']);

        $togetherGame->setBatchNr($fieldValue['batchNr']);

        if( isset($fieldValue['refereePlaceUniqueIndex'])) {
            $togetherGame->setRefereePlaceUniqueIndex( $fieldValue['refereePlaceUniqueIndex'] );
        }
        if( isset($fieldValue['refereeNr'])) {
            $togetherGame->setRefereeNr( $fieldValue['refereeNr'] );
        }

        foreach ($fieldValue['gamePlaces'] as $arrGamePlace) {
            $togetherGame->addGamePlace(new TogetherGamePlace($arrGamePlace['placeNr'], $arrGamePlace['cycleNr']));
        }
        return $togetherGame;
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
