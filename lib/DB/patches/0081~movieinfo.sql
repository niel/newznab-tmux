ALTER TABLE  `movieinfo` ADD  `type` VARCHAR( 32 ) NOT NULL AFTER  `genre` ;
UPDATE `tmux` SET `value` = '81' WHERE `setting` = 'sqlpatch';