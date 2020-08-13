<?php

namespace SportsPlanning\Tests;

include_once __DIR__ . '/../../data/CompetitionCreator.php';
include_once __DIR__ . '/../../helpers/Console.php';

use SportsPlanning\Field;
use SportsPlanning\Game;
use SportsPlanning\Place;
use SportsPlanning\Config\Optimalization\Service as OptimalizationService;
use SportsPlanning\Sport;
use SportsPlanning\Structure\Service as StructureService;
use SportsPlanning\Competition;
use SportsPlanning\Service as PlanningService;
use SportsPlanning\Sport\Config\Service as SportConfigService;


class ServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testVariations()
    {
        $maxNrOfCompetitors = 40;
        $maxNrOfSports = 1;
        $maxNrOfFields = 20;
        $maxNrOfHeadtohead = 4;

        for ($nrOfCompetitors = 2; $nrOfCompetitors <= $maxNrOfCompetitors; $nrOfCompetitors++) {
            $nrOfPoules = 0;
            // let teamup = false;
            // let selfReferee = false;

            while (((int) floor($nrOfCompetitors / ++$nrOfPoules)) >= 2) {
                for ($nrOfSports = 1; $nrOfSports <= $maxNrOfSports; $nrOfSports++) {
                    for ($nrOfFields = 1; $nrOfFields <= $maxNrOfFields; $nrOfFields++) {
                        for ($nrOfHeadtohead = 1; $nrOfHeadtohead <= $maxNrOfHeadtohead; $nrOfHeadtohead++) {
                            if ($nrOfCompetitors !== 8 || $nrOfPoules > 1 || $nrOfSports !== 1 || $nrOfFields > 3 /*|| $nrOfHeadtohead !== 3*/) {
                                continue;
                            }
                            $assertConfig = $this->getAssertionsConfig($nrOfCompetitors, $nrOfPoules, $nrOfSports, $nrOfFields, $nrOfHeadtohead);
//                            if ($assertConfig !== null) {
                            echo
                                    'nrOfCompetitors ' . $nrOfCompetitors . ', nrOfPoules ' . $nrOfPoules . ', nrOfSports ' . $nrOfSports
                                    . ', nrOfFields ' . $nrOfFields . ', nrOfHeadtohead ' . $nrOfHeadtohead
                                    . PHP_EOL;
                            $this->checkPlanning($nrOfCompetitors, $nrOfPoules, $nrOfSports, $nrOfFields, $nrOfHeadtohead, $assertConfig);
//                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * check if every place has the same amount of games
     * check if one place is not two times in one game
     * for planning : add selfreferee if is this enables
     *
     * @param Place $place
     * @param array|Game[] $games
     * @param array|int[] $expectedValue
     */
    protected function assertValidGamesParticipations(Place $place, array $games, array $expectedValue)
    {
        // use validator
    }

    /**
     * @param Place $place
     * @param array|Game[] $games
     * @param int $maxInRow
     */
    protected function assertGamesInRow(Place $place, array $games, int $maxInRow)
    {
        // use validator
    }

    /**
     * check if every batch has no double fields, referees or place
     *
     * @param array|Game[] $games
     */
    protected function assertValidResourcesPerBatch(array $games)
    {
        // use validator
    }

    protected function addSport(Competition $competition)
    {
        $sportConfigService = new SportConfigService();
        $id = count($competition->getSportConfigs()) + 1;
        $sport = new Sport('sport' . $id);
        $sport->setId($id);
        $sportConfigService->createDefault($sport, $competition);
        return $sport;
    }

    protected function checkPlanning(
        int $nrOfCompetitors,
        int $nrOfPoules,
        int $nrOfSports,
        int $nrOfFields,
        int $nrOfHeadtohead,
        AssertConfig $assertConfig = null
    ) {
        $competition = createCompetition();

        $competitionFirstSports = [];
        for ($sportNr = 2; $sportNr <= $nrOfSports; $sportNr++) {
            $competitionFirstSports[] = $this->addSport($competition);
        }
        $competitionSports = $competition->getSportConfigs()->map(function ($sportConfig) {
            return $sportConfig->getSport();
        })->toArray();

        $sports = [];
        if ($nrOfFields > 1) {
            $x = "1";
        }
        while (count($sports) < $nrOfFields) {
            $init = count($sports) === 0;
            $sports = array_merge($sports, $competitionSports);
            if ($init && count($competitionSports) > 1) {
                array_shift($sports);
            }
        }
        for ($fieldNr = 2; $fieldNr <= $nrOfFields; $fieldNr++) {
            $field = new Field($competition, $fieldNr);
            $field->setSport(array_shift($sports));
        }

        $structureService = new StructureService();
        $structure = $structureService->create($competition, $nrOfCompetitors, $nrOfPoules);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $firstRoundNumber->getValidPlanningConfig()->setNrOfHeadtohead($nrOfHeadtohead);

        $planningService = new PlanningService();

        if ($nrOfCompetitors === 2 && $nrOfSports === 1 && $nrOfFields === 2 && $nrOfHeadtohead === 1) {
            $x = 1;
        }

        $planningService->create($firstRoundNumber);
        if ($assertConfig === null) {
            return;
        }
        $games = $planningService->getGamesForRoundNumber($firstRoundNumber, Game::ORDER_RESOURCEBATCH);
        // consoleGames($games); echo PHP_EOL;
        $this->assertSame(
            count($games),
            $assertConfig->nrOfGames,
            'het aantal wedstrijd voor de hele ronde komt niet overeen'
        );
        $this->assertValidResourcesPerBatch($games);
        foreach ($firstRoundNumber->getPlaces() as $place) {
            $this->assertValidGamesParticipations($place, $games, $assertConfig->nrOfPlaceGames);
            $this->assertGamesInRow($place, $games, $assertConfig->maxNrOfGamesInARow);
        }
        $this->assertLessThan($assertConfig->maxNrOfBatches + 1, array_pop($games)->getResourceBatch(), 'het aantal batches moet minder zijn dan ..');
    }

    protected function getAssertionsConfig(
        int $nrOfCompetitors,
        int $nrOfPoules,
        int $nrOfSports,
        int $nrOfFields,
        int $nrOfHeadtohead
    ): ?AssertConfig {
        $competitors = [
            2 => Variations\Config2::get(), 3 => Variations\Config3::get(), 4 => Variations\Config4::get(), 5 => Variations\Config5::get(),
            6 => Variations\Config5::get(),
            8 => Variations\Config8::get(), 9 => Variations\Config9::get(), 16 => Variations\Config16::get(), 40 => Variations\Config40::get(),
        ];

        if (array_key_exists($nrOfCompetitors, $competitors) === false) {
            return null;
        }
        $nrOfCompetitorsConfigs = $competitors[$nrOfCompetitors];
        if (array_key_exists($nrOfPoules, $nrOfCompetitorsConfigs["nrOfPoules"]) === false) {
            return null;
        }
        $nrOfPoulesConfigs = $nrOfCompetitorsConfigs["nrOfPoules"][$nrOfPoules];
        if (array_key_exists($nrOfSports, $nrOfPoulesConfigs["nrOfSports"]) === false) {
            return null;
        }
        $nrOfSportsConfigs = $nrOfPoulesConfigs["nrOfSports"][$nrOfSports];
        if (array_key_exists($nrOfFields, $nrOfSportsConfigs["nrOfFields"]) === false) {
            return null;
        }
        $nrOfFieldsConfigs = $nrOfSportsConfigs["nrOfFields"][$nrOfFields];
        if (array_key_exists($nrOfHeadtohead, $nrOfFieldsConfigs["nrOfHeadtohead"]) === false) {
            return null;
        }
        return $nrOfFieldsConfigs["nrOfHeadtohead"][$nrOfHeadtohead];
    }




    // // with one poule referee can be from same poule
    // it('self referee 1 poule of 3', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const field2 = new Field(competition, 2); field2.setSport(competition.getFirstSportConfig().getSport());

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 3, 1);
    //     const firstRoundNumber = structure.getFirstRoundNumber();
    //     firstRoundNumber.getPlanningConfig().setSelfReferee(true);

    //     const planningService = new PlanningService(competition);
    //     planningService.create(firstRoundNumber);

    //     const games = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);

    //     expect(games.length).to.equal(3);
    //     const firstGame = games.shift();
    //     expect(firstGame.getResourceBatch()).to.equal(1);
    //     expect(firstGame.getRefereePlace()).to.equal(firstGame.getPoule().getPlace(1));
    //     expect(games.shift().getResourceBatch()).to.equal(2);
    //     expect(games.shift().getResourceBatch()).to.equal(3);

    //     assertValidResourcesPerBatch(firstRoundNumber.getGames());
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, firstRoundNumber.getGames(), 2);
    //     });
    // });

    // // games should be ordered by roundnumber, subnumber because if sorted by poule
    // // the planning is not optimized.
    // // If all competitors of poule A play first and there are still fields free
    // // than they cannot be referee. This will be most bad when there are two poules.
    // it('self referee 4 fields, 66', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const field2 = new Field(competition, 2); field2.setSport(competition.getFirstSportConfig().getSport());
    //     const field3 = new Field(competition, 3); field3.setSport(competition.getFirstSportConfig().getSport());
    //     const field4 = new Field(competition, 4); field4.setSport(competition.getFirstSportConfig().getSport());

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 12, 2);
    //     const firstRoundNumber = structure.getFirstRoundNumber();
    //     firstRoundNumber.getPlanningConfig().setSelfReferee(true);

    //     const planningService = new PlanningService(competition);
    //     planningService.create(firstRoundNumber);

    //     const games = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games);
    //     expect(games.length).to.equal(30);

    //     expect(games.shift().getResourceBatch()).to.equal(1);
    //     expect(games.shift().getResourceBatch()).to.equal(1);
    //     expect(games.shift().getResourceBatch()).to.equal(1);
    //     expect(games.shift().getResourceBatch()).to.equal(1);
    //     expect(games.pop().getResourceBatch()).to.be.lessThan(9);

    //     assertValidResourcesPerBatch(firstRoundNumber.getGames());
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, firstRoundNumber.getGames(), 5);
    //     });
    // });


    // it('2 fields 2 sports, 5->(3)', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    //     const sport2 = addSport(competition);
    //     const field2 = new Field(competition, 2); field2.setSport(sport2);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 5, 1);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const rootRound = structure.getRootRound();
    //     structureService.addQualifiers(rootRound, QualifyGroup.WINNERS, 3);

    //     const planningService = new PlanningService(competition);
    //     planningService.create(firstRoundNumber);

    //     const games1 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games1);
    //     expect(games1.length).to.equal(10);
    //     assertValidResourcesPerBatch(games1);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games1, 4);
    //     });
    //     expect(games1.pop().getResourceBatch()).to.be.lessThan(6);

    //     const secondRoundNumber = firstRoundNumber.getNext();
    //     const games2 = planningService.getGamesForRoundNumber(secondRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games1);
    //     expect(games2.length).to.equal(6);
    //     assertValidResourcesPerBatch(games2);
    //     secondRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games2, 4);
    //     });
    //     expect(games2.pop().getResourceBatch()).to.be.lessThan(7);
    // });

    // it('2 fields 2 sports, 4', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    //     const sport2 = addSport(competition);
    //     const field2 = new Field(competition, 2); field2.setSport(sport2);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 4, 1);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const planningService = new PlanningService(competition);

    //     planningService.create(firstRoundNumber);
    //     const games1 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     consoleGames(games1); console.log('');
    //     expect(games1.length).to.equal(6);
    //     assertValidResourcesPerBatch(games1);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games1, 3);
    //     });
    //     expect(games1.pop().getResourceBatch()).to.be.lessThan(7);

    //     firstRoundNumber.getValidPlanningConfig().setNrOfHeadtohead(2);
    //     planningService.create(firstRoundNumber);
    //     const games2 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     consoleGames(games2); console.log('');
    //     expect(games2.length).to.equal(12);
    //     assertValidResourcesPerBatch(games2);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games2, 6);
    //     });
    //     expect(games2.pop().getResourceBatch()).to.be.lessThan(7);
    // });

    // it('2 fields 2 sports, 44', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    //     const sport2 = addSport(competition);
    //     const field2 = new Field(competition, 2); field2.setSport(sport2);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 8, 2);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const planningService = new PlanningService(competition);

    //     planningService.create(firstRoundNumber);
    //     const games1 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games1); console.log('');
    //     expect(games1.length).to.equal(12);
    //     assertValidResourcesPerBatch(games1);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games1, 3);
    //     });
    //     expect(games1.pop().getResourceBatch()).to.be.lessThan(7);
    // });


    // it('3 fields 3 sports, 4', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    //     const sport2 = addSport(competition);
    //     const field2 = new Field(competition, 2); field2.setSport(sport2);
    //     const sport3 = addSport(competition);
    //     const field3 = new Field(competition, 3); field3.setSport(sport3);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 4, 1);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const planningService = new PlanningService(competition);

    //     planningService.create(firstRoundNumber);
    //     const games1 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games1);
    //     expect(games1.length).to.equal(6);
    //     assertValidResourcesPerBatch(games1);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games1, 3);
    //     });
    //     expect(games1.pop().getResourceBatch()).to.be.lessThan(7);

    //     firstRoundNumber.getValidPlanningConfig().setNrOfHeadtohead(2);
    //     planningService.create(firstRoundNumber);
    //     const games2 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     consoleGames(games2);
    //     expect(games2.length).to.equal(12);
    //     assertValidResourcesPerBatch(games2);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games2, 6);
    //     });
    //     expect(games2.pop().getResourceBatch()).to.be.lessThan(7);

    //     firstRoundNumber.getValidPlanningConfig().setNrOfHeadtohead(3);
    //     planningService.create(firstRoundNumber);
    //     const games3 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games3);
    //     expect(games3.length).to.equal(18);
    //     assertValidResourcesPerBatch(games3);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games3, 9);
    //     });
    //     expect(games3.pop().getResourceBatch()).to.be.lessThan(13);
    // });

    // it('3 fields 3 sports, 44', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    //     const sport2 = addSport(competition);
    //     const field2 = new Field(competition, 2); field2.setSport(sport2);
    //     const sport3 = addSport(competition);
    //     const field3 = new Field(competition, 3); field3.setSport(sport3);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 8, 2);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const planningService = new PlanningService(competition);

    //     planningService.create(firstRoundNumber);
    //     const games1 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games1);
    //     expect(games1.length).to.equal(12);
    //     assertValidResourcesPerBatch(games1);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games1, 3);
    //     });
    //     expect(games1.pop().getResourceBatch()).to.be.lessThan(13);

    //     firstRoundNumber.getValidPlanningConfig().setNrOfHeadtohead(2);
    //     planningService.create(firstRoundNumber);
    //     const games2 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     consoleGames(games2);
    //     expect(games2.length).to.equal(24);
    //     assertValidResourcesPerBatch(games2);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games2, 6);
    //     });
    //     expect(games2.pop().getResourceBatch()).to.be.lessThan(13);
    // });

    // it('2 fields 2 sports, 5', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    //     const sport2 = addSport(competition);
    //     const field2 = new Field(competition, 2); field2.setSport(sport2);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 5, 1);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const planningService = new PlanningService(competition);
    //     planningService.create(firstRoundNumber);

    //     const games1 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games1);
    //     expect(games1.length).to.equal(10);
    //     assertValidResourcesPerBatch(games1);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games1, 4);
    //     });
    //     expect(games1.pop().getResourceBatch()).to.be.lessThan(6);
    // });

    // it('2 fields 2 sports, 55', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    //     const sport2 = addSport(competition);
    //     const field2 = new Field(competition, 2); field2.setSport(sport2);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 10, 2);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const planningService = new PlanningService(competition);
    //     planningService.create(firstRoundNumber);

    //     const games1 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games1);
    //     expect(games1.length).to.equal(20);
    //     assertValidResourcesPerBatch(games1);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games1, 4);
    //     });
    //     expect(games1.pop().getResourceBatch()).to.be.lessThan(11);
    // });


    // it('3 fields 2 sports, 5', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    //     const sport2 = addSport(competition);
    //     const field2 = new Field(competition, 2); field2.setSport(sport2);
    //     const field3 = new Field(competition, 3); field3.setSport(sport2);

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 5, 1);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const planningService = new PlanningService(competition);
    //     planningService.create(firstRoundNumber);

    //     const games1 = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games1);
    //     expect(games1.length).to.equal(10);
    //     assertValidResourcesPerBatch(games1);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games1, 4);
    //     });
    //     expect(games1.pop().getResourceBatch()).to.be.lessThan(6);
    // });


    // // should not be possible, fields determine nrofsports
    // // it('2 sports(1 & 3 fields), 5', () => {
    // //     const competitionMapper = getMapper('competition');
    // //     const competition = competitionMapper.toObject(jsonCompetition);
    // //     const sportConfigService = new SportConfigService(new SportScoreConfigService(), new SportPlanningConfigService());
    // //     const sport2 = addSport(competition);
    // //     const field2 = new Field(competition, 2); field2.setSport(sport2);
    // //     const field3 = new Field(competition, 3); field3.setSport(sport2);
    // //     const field4 = new Field(competition, 4); field4.setSport(sport2);

    // //     const structureService = new StructureService();
    // //     const structure = structureService.create(competition, 5, 1);
    // //     const firstRoundNumber = structure.getFirstRoundNumber();

    // //     const planningService = new PlanningService(competition);
    // //     planningService.create(firstRoundNumber);

    // //     const games = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    // //     // consoleGames(games1);
    // //     expect(games.length).to.equal(10);
    // //     assertValidResourcesPerBatch(games);
    // //     firstRoundNumber.getPlaces().forEach(place => {
    // //         this.assertValidGamesParticipations(place, games, 4);
    // //     });
    // //     expect(games.pop().getResourceBatch()).to.be.lessThan(6);
    // // });

    // it('2 fields check games in row, 6', () => {
    //     const competitionMapper = getMapper('competition');
    //     const competition = competitionMapper.toObject(jsonCompetition);
    //     const field2 = new Field(competition, 2); field2.setSport(competition.getFirstSportConfig().getSport());

    //     const structureService = new StructureService();
    //     const structure = structureService.create(competition, 6, 1);
    //     const firstRoundNumber = structure.getFirstRoundNumber();

    //     const planningService = new PlanningService(competition);
    //     planningService.create(firstRoundNumber);

    //     const games = planningService.getGamesForRoundNumber(firstRoundNumber, Game.ORDER_RESOURCEBATCH);
    //     // consoleGames(games);
    //     expect(games.length).to.equal(15);
    //     assertValidResourcesPerBatch(games);
    //     firstRoundNumber.getPlaces().forEach(place => {
    //         this.assertValidGamesParticipations(place, games, 5);
    //         this.assertGamesInRow(place, games, 3);
    //     });
    //     expect(games.pop().getResourceBatch()).to.be.lessThan(9);
    // });





    // it('recursive', () => {
    //     const numbers = [1, 2, 3, 4, 5, 6];
    //     const nrOfItemsPerBatch = 3;

    //     const itemSuccess = (newNumber: number): boolean => {
    //         return (newNumber % 2) === 1;
    //     };
    //     const endSuccess = (batch: number[]): boolean => {
    //         if (nrOfItemsPerBatch < batch.length) {
    //             return false;
    //         }
    //         let sum = 0;
    //         batch.forEach(number => sum += number);
    //         return sum === 9;
    //     };

    //     const showC = (list: number[], batch: number[] = []): boolean => {
    //         if (endSuccess(batch)) {
    //             console.log(batch);
    //             return true;
    //         }
    //         if (list.length + batch.length < nrOfItemsPerBatch) {
    //             return false;
    //         }
    //         const numberToTry = list.shift();
    //         if (itemSuccess(numberToTry)) {
    //             batch.push(numberToTry);
    //             if (showC(list.slice(), batch) === true) {
    //                 return true;
    //             }
    //             batch.pop();
    //             return showC(list, batch);

    //         }
    //         return showC(list, batch);
    //     };

    //     if (!showC(numbers)) {
    //         console.log('no combinations found');
    //     };
    // });
}
