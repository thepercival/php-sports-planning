-- PRE PRE PRE doctrine-update =============================================================


-- POST POST POST doctrine-update ===========================================================
-- php bin/console.php app:send-create-default-orchestrations --placesRange="2->4" --sendCreatePlanningMessage=true

-- CUSTOM IMPORT =============================
-- mysqldump -u fctoernooi_a_dba -p fctoernooiacc planninginputs plannings planningsports planningfields planningpoules planningplaces planningreferees planninggames planninggameplaces > planninginputs.sql
-- mysql -u fctoernooi_dba -p fctoernooi < planninginputs.sql
