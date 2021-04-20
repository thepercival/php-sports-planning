<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\GameGenerator;

use PHPUnit\Framework\TestCase;
use SportsHelpers\GameMode;
use SportsHelpers\SportRange;
use SportsPlanning\GameGenerator\GameMode\Single as SingleGameGenerator;
use SportsPlanning\Planning;
use SportsPlanning\TestHelper\PlanningCreator;

class TogetherTest extends TestCase
{
    use PlanningCreator;

    public function testSimple(): void
    {
        $sportVariant = $this->getSingleSportVariantWithFields(2, 3, 2);
        $planning = new Planning(
            $this->createInput([7], [$sportVariant]),
            new SportRange(1, 1),
            0);

//        $getPlacesDescription = function (array $togetherGamePlaces): string {
//            $description = "";
//            foreach ($togetherGamePlaces as $togetherGamePlace) {
//                $description .= $togetherGamePlace->getPlace()->getLocation() . " , ";
//            }
//            return $description;
//        };

        // alle tests zitten ook in de validator, dus een beeteje dubbel om hier
        // ook nog eens alles te testen!!!!
        $gameGenerator = new SingleGameGenerator($planning);

        $poule = $planning->getInput()->getPoule(1);
        $sports = array_values($planning->getInput()->getSports()->toArray());
        $gameGenerator->generate($poule, $sports);
//        foreach( $games as $game ) {
//            $output = "";
//            $places = "places: " . $getPlacesDescription($game->getPlaces()->toArray());
//            echo $output . $places .PHP_EOL;
//
//        }

        // $maxNrOfGamesSim = $calculator->getMaxNrOfGames( $pouleStructure, $sportConfigs, false );
        // check if GameRoundGenerator should be removed !!!!!!!!!!!!!!!!!
        self::assertCount(11, $planning->getTogetherGames());
        // check if GameRoundGenerator should be removed !!!!!!!!!!!!!!!!!
    }
}
