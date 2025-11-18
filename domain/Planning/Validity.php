<?php

namespace SportsPlanning\Planning;

final class Validity
{
    public const NOT_VALIDATED = -1;
    public const VALID = 0;
    public const NO_GAMES = 1;
    public const EMPTY_PLACE = 2;
    public const EMPTY_REFEREE = 8;
    public const EMPTY_REFEREEPLACE = 16;
    public const UNEQUAL_GAME_HOME_AWAY = 32;
    public const UNEQUAL_GAME_AGAINST = 64;
    public const NOT_EQUALLY_ASSIGNED_PLACES = 128;
    public const TOO_MANY_GAMES_IN_A_ROW = 256;
    public const MULTIPLE_ASSIGNED_FIELDS_IN_BATCH = 512;
    public const MULTIPLE_ASSIGNED_REFEREES_IN_BATCH = 1024;
    public const MULTIPLE_ASSIGNED_PLACES_IN_BATCH = 2048;
    public const UNEQUALLY_ASSIGNED_FIELDS = 4096;
    public const UNEQUALLY_ASSIGNED_REFEREES = 8192;
    public const UNEQUALLY_ASSIGNED_REFEREEPLACES = 16384;
    public const INVALID_ASSIGNED_REFEREEPLACE = 32768;
    public const UNEQUAL_PLACE_NROFHOMESIDES = 65536;
    public const INVALID_REFEREESELF_AND_REFEREES = 131072;
    public const INVALID_NROFBATCHES = 262144;
    public const UNEQUAL_GAME_WITH = 524288;

    public const ALL_INVALID = 1048575;
}