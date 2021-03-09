-- PRE PRE PRE doctrine-update =============================================================

SET FOREIGN_KEY_CHECKS = 0;
truncate planninggameplaces;
truncate planninggames;
truncate planningfields;
truncate planningplaces;
truncate planningpoules;
truncate planningreferees;
truncate planningsports;
truncate plannings;
truncate planninginputs;
SET FOREIGN_KEY_CHECKS = 1;

alter table planningfields rename planningFields;
alter table planningplaces rename planningPlaces;
alter table planninginputs rename planningInputs;
alter table planningpoules rename planningPoules;
alter table planningreferees rename planningReferees;
alter table planningsports rename planningSports;

-- POST POST POST doctrine-update ===========================================================

-- update planninginputs set gameMode = 2;
-- update planningInputs set sportConfig = replace(sportConfig, '2}]', concat( '2,"gameAmount": ', nrOfHeadtohead, '}]') );
-- update planningInputs set sportConfig = replace(sportConfig, 'Places":2', 'Places":4') , gameMode = 2 where teamup = true;


-- php bin/console.php app:create-default-planning-input --placesRange=2-4 --sendCreatePlanningMessage=true

-- CUSTOM IMPORT =============================
-- mysqldump -u fctoernooi_a_dba -p fctoernooiacc planninginputs plannings planningsports planningfields planningpoules planningplaces planningreferees planninggames planninggameplaces > planninginputs.sql
-- mysql -u fctoernooi_dba -p fctoernooi < planninginputs.sql
