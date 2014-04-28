ALTER TABLE `releases` ADD COLUMN `nzbstatus` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `releases` ADD COLUMN `nzb_guid` VARCHAR(50) NULL;
ALTER TABLE `releases` DROP INDEX `ix_releases_status`;
ALTER TABLE `releases` ADD INDEX `ix_releases_status` (`nzbstatus`, `iscategorized`, `isrenamed`, `nfostatus`, `ishashed`, `passwordstatus`, `dehashstatus`, `releasenfoID`, `musicinfoID`, `consoleinfoID`, `bookinfoID`, `haspreview`, `categoryID`, `imdbID`, `rageID`);
CREATE INDEX `ix_releases_nzb_guid` ON `releases` (`nzb_guid`);

UPDATE `releases` SET nzbstatus = 1 WHERE nzbstatus = 0;



UPDATE `tmux` set `value` = '29' where `setting` = 'sqlpatch';