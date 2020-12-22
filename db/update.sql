-- PRE PRE PRE doctrine-update =============================================================
update planninginputs set sportConfig = replace(sportConfig, '2}]', '2,"versusMode": true}]');
update planninginputs set sportConfig = replace(sportConfig, 'Places":2', 'Places":4') where teamup = true;
update sports set customId = 15 where name = 'sjoelen';

-- POST POST POST doctrine-update ===========================================================

-- php bin/console.php app:create-default-planning-input --placesRange=2-4 --sendCreatePlanningMessage=true

-- CUSTOM IMPORT =============================
-- mysqldump -u fctoernooi_a_dba -p fctoernooiacc planninginputs plannings planningsports planningfields planningpoules planningplaces planningreferees planninggames planninggameplaces > planninginputs.sql
-- mysql -u fctoernooi_dba -p fctoernooi < planninginputs.sql
