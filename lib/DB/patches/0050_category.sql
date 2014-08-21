DROP TABLE IF EXISTS `category`;
CREATE TABLE category
(
  `ID`                   INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `title`                VARCHAR(255)    NOT NULL,
  `parentID`             INT             NULL,
  `status`               INT             NOT NULL DEFAULT '1',
  `minsizetoformrelease` BIGINT UNSIGNED NOT NULL DEFAULT '0',
  `maxsizetoformrelease` BIGINT UNSIGNED NOT NULL DEFAULT '0',
  `description`          VARCHAR(255)    NULL,
  `disablepreview`       TINYINT(1)      NOT NULL DEFAULT '0'
)
  ENGINE =INNODB
  DEFAULT CHARACTER SET utf8
  COLLATE utf8_unicode_ci
  AUTO_INCREMENT =100000;

INSERT INTO category (ID, title) VALUES (1000, 'Console');
INSERT INTO category (ID, title) VALUES (2000, 'Movies');
INSERT INTO category (ID, title) VALUES (3000, 'Audio');
INSERT INTO category (ID, title) VALUES (4000, 'PC');
INSERT INTO category (ID, title) VALUES (5000, 'TV');
INSERT INTO category (ID, title) VALUES (6000, 'XXX');
INSERT INTO category (ID, title) VALUES (7000, 'Books');
INSERT INTO category (ID, title) VALUES (8000, 'Other');

INSERT INTO category (ID, title, parentID) VALUES (1010, 'NDS', 1000);
INSERT INTO category (ID, title, parentID) VALUES (1020, 'PSP', 1000);
INSERT INTO category (ID, title, parentID) VALUES (1030, 'Wii', 1000);
INSERT INTO category (ID, title, parentID) VALUES (1040, 'Xbox', 1000);
INSERT INTO category (ID, title, parentID) VALUES (1050, 'Xbox 360', 1000);
INSERT INTO category (ID, title, parentID) VALUES (1060, 'WiiWare/VC', 1000);
INSERT INTO category (ID, title, parentID) VALUES (1070, 'XBOX 360 DLC', 1000);
INSERT INTO category (ID, title, parentID) VALUES (1080, 'PS3', 1000);
INSERT INTO category (ID, title, parentID) VALUES (1090, 'Other', 1000);
INSERT INTO category (ID, title, parentID) VALUES (1110, '3DS', 1000);
INSERT INTO category (ID, title, parentID) VALUES (1120, 'PS Vita', 1000);
INSERT INTO category (ID, title, parentID) VALUES (1130, 'WiiU', 1000);
INSERT INTO category (ID, title, parentID) VALUES (1140, 'Xbox One', 1000);
INSERT INTO category (ID, title, parentID) VALUES (1180, 'PS4', 1000);

INSERT INTO category (ID, title, parentID) VALUES (2010, 'Foreign', 2000);
INSERT INTO category (ID, title, parentID) VALUES (2020, 'Other', 2000);
INSERT INTO category (ID, title, parentID) VALUES (2030, 'SD', 2000);
INSERT INTO category (ID, title, parentID) VALUES (2040, 'HD', 2000);
INSERT INTO category (ID, title, parentID) VALUES (2050, '3D', 2000);
INSERT INTO category (ID, title, parentID) VALUES (2060, 'BluRay', 2000);
INSERT INTO category (ID, title, parentID) VALUES (2070, 'DVD', 2000);

INSERT INTO category (ID, title, parentID) VALUES (3010, 'MP3', 3000);
INSERT INTO category (ID, title, parentID) VALUES (3020, 'Video', 3000);
INSERT INTO category (ID, title, parentID) VALUES (3030, 'Audiobook', 3000);
INSERT INTO category (ID, title, parentID) VALUES (3040, 'Lossless', 3000);
INSERT INTO category (ID, title, parentID) VALUES (3050, 'Other', 3000);
INSERT INTO category (ID, title, parentID) VALUES (3060, 'Foreign', 3000);

INSERT INTO category (ID, title, parentID) VALUES (4010, '0day', 4000);
INSERT INTO category (ID, title, parentID) VALUES (4020, 'ISO', 4000);
INSERT INTO category (ID, title, parentID) VALUES (4030, 'Mac', 4000);
INSERT INTO category (ID, title, parentID) VALUES (4040, 'Mobile-Other', 4000);
INSERT INTO category (ID, title, parentID) VALUES (4050, 'Games', 4000);
INSERT INTO category (ID, title, parentID) VALUES (4060, 'Mobile-iOS', 4000);
INSERT INTO category (ID, title, parentID) VALUES (4070, 'Mobile-Android', 4000);

INSERT INTO category (ID, title, parentID) VALUES (5010, 'WEB-DL', 5000);
INSERT INTO category (ID, title, parentID) VALUES (5020, 'Foreign', 5000);
INSERT INTO category (ID, title, parentID) VALUES (5030, 'SD', 5000);
INSERT INTO category (ID, title, parentID) VALUES (5040, 'HD', 5000);
INSERT INTO category (ID, title, parentID) VALUES (5050, 'Other', 5000);
INSERT INTO category (ID, title, parentID) VALUES (5060, 'Sport', 5000);
INSERT INTO category (ID, title, parentID) VALUES (5070, 'Anime', 5000);
INSERT INTO category (ID, title, parentID) VALUES (5080, 'Documentary', 5000);

INSERT INTO category (ID, title, parentID) VALUES (6010, 'DVD', 6000);
INSERT INTO category (ID, title, parentID) VALUES (6020, 'WMV', 6000);
INSERT INTO category (ID, title, parentID) VALUES (6030, 'XviD', 6000);
INSERT INTO category (ID, title, parentID) VALUES (6040, 'x264', 6000);
INSERT INTO category (ID, title, parentID) VALUES (6050, 'Pack', 6000);
INSERT INTO category (ID, title, parentID) VALUES (6060, 'ImgSet', 6000);
INSERT INTO category (ID, title, parentID) VALUES (6070, 'Other', 6000);

INSERT INTO category (ID, title, parentID) VALUES (7010, 'Mags', 7000);
INSERT INTO category (ID, title, parentID) VALUES (7020, 'Ebook', 7000);
INSERT INTO category (ID, title, parentID) VALUES (7030, 'Comics', 7000);
INSERT INTO category (ID, title, parentID) VALUES (7040, 'Technical', 7000);
INSERT INTO category (ID, title, parentID) VALUES (7050, 'Other', 7000);
INSERT INTO category (ID, title, parentID) VALUES (7060, 'Foreign', 7000);

INSERT INTO category (ID, title, parentID) VALUES (8010, 'Misc', 8000);
INSERT INTO category (ID, title, parentID) VALUES (8020, 'Hashed', 8000);

UPDATE `tmux` SET `value` = '50' WHERE `setting` = 'sqlpatch';