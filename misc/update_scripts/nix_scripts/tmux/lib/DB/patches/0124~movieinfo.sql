ALTER TABLE movieinfo ADD COLUMN banner TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
UPDATE `tmux` set `value` = '124' where `setting` = 'sqlpatch';