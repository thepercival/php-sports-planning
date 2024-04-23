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
-- php bin/console.php app:create-default-planning-input --placesRange=2-4 --sendCreatePlanningMessage=true

-- CUSTOM IMPORT =============================
-- mysqldump -u fctoernooi_a_dba -p fctoernooiacc planninginputs plannings planningsports planningfields planningpoules planningplaces planningreferees planninggames planninggameplaces > planninginputs.sql
-- mysql -u fctoernooi_dba -p fctoernooi < planninginputs.sql
