<?php

namespace SportsPlanning\Planning;

final class PlanningValidity
{
    public const int NOT_VALIDATED = -1;
    public const int VALID = 0;
    public const int NO_GAMES = 1;
    public const int EMPTY_PLACE = 2;
    public const int EMPTY_REFEREE = 8;
    public const int EMPTY_REFEREEPLACE = 16;
    public const int UNEQUAL_GAME_HOME_AWAY = 32;
    public const int UNEQUAL_GAME_AGAINST = 64;
    public const int NOT_EQUALLY_ASSIGNED_PLACES = 128;
    public const int TOO_MANY_GAMES_IN_A_ROW = 256;
    public const int MULTIPLE_ASSIGNED_FIELDS_IN_BATCH = 512;
    public const int MULTIPLE_ASSIGNED_REFEREES_IN_BATCH = 1024;
    public const int MULTIPLE_ASSIGNED_PLACES_IN_BATCH = 2048;
    public const int UNEQUALLY_ASSIGNED_FIELDS = 4096;
    public const int UNEQUALLY_ASSIGNED_REFEREES = 8192;
    public const int UNEQUALLY_ASSIGNED_REFEREEPLACES = 16384;
    public const int INVALID_ASSIGNED_REFEREEPLACE = 32768;
    public const int UNEQUAL_PLACE_NROFHOMESIDES = 65536;
    public const int INVALID_REFEREESELF_AND_REFEREES = 131072;
    public const int INVALID_NROFBATCHES = 262144;
    public const int UNEQUAL_GAME_WITH = 524288;

    public const int ALL_INVALID = 1048575;
}