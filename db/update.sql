-- PRE PRE PRE doctrine-update =============================================================
delete
from planningSchedules
where sportsConfigName like '%},{%';
-- remove multiple sports
-- should be 0
select count(*)
from planningSchedules s
where (select count(*) from planningSportSchedules ss where ss.scheduleId = s.id) > 1;

-- POST POST POST doctrine-update ===========================================================
update planningInputs
set recreatedAt = null;
update planningInputs
set seekingPercentage = -1;

alter table planningSchedules
    drop gamePlaceStrategy;
alter table planningInputs
    drop gamePlaceStrategy;

-- update planninginputs set gameMode = 2;
-- update planningInputs set sportConfig = replace(sportConfig, '2}]', concat( '2,"gameAmount": ', nrOfHeadtohead, '}]') );
-- update planningInputs set sportConfig = replace(sportConfig, 'Places":2', 'Places":4') , gameMode = 2 where teamup = true;


-- php bin/console.php app:create-default-planning-input --placesRange=2-4 --sendCreatePlanningMessage=true

-- CUSTOM IMPORT =============================
-- mysqldump -u fctoernooi_a_dba -p fctoernooiacc planninginputs plannings planningsports planningfields planningpoules planningplaces planningreferees planninggames planninggameplaces > planninginputs.sql
-- mysql -u fctoernooi_dba -p fctoernooi < planninginputs.sql
