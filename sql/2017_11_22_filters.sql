/* 12:29:26 localhost basis4_dev */ ALTER TABLE `user_filter_history` ADD `FiltersJSON` MEDIUMTEXT  NOT NULL  AFTER `FilterValuesMD5`;
/* 12:30:08 localhost basis4_dev */ ALTER TABLE `user_filter_history` ADD `Description` TEXT  NOT NULL  AFTER `FiltersJSON`;

/* 13:03:22 localhost basis4_dev */ ALTER TABLE `user_filter_saved` ADD `FiltersJSON` MEDIUMTEXT  NOT NULL  AFTER `FilterValues`;

DROP INDEX CreatedBy_2 ON user_filter_saved;
CREATE UNIQUE INDEX CreatedByTableIDName ON user_filter_saved (CreatedBy, TableID, Name);

/* 13:16:42 localhost basis4_dev */ TRUNCATE TABLE `user_filter_history`;

/* 15:12:22 localhost basis_test */ ALTER TABLE `user_filter_history` DROP `FilterValues`;
/* 15:12:42 localhost basis_test */ ALTER TABLE `user_filter_saved` DROP `FilterValues`;

UPDATE user_filter_settings SET Invisible = '';
