ALTER TABLE history_data ADD COLUMN  `Education` longtext DEFAULT NULL,
    ADD COLUMN `Household__income` longtext DEFAULT NULL,
    ADD COLUMN `Healthcare` longtext DEFAULT NULL,
    ADD COLUMN `Housing` longtext DEFAULT NULL,
    ADD COLUMN `Food` longtext DEFAULT NULL,
    ADD COLUMN `Utilities` text DEFAULT NULL,
    ADD COLUMN `Race` text DEFAULT NULL,
    ADD COLUMN `Water` text DEFAULT NULL,
    ADD COLUMN `Gas` text DEFAULT NULL,
    ADD COLUMN `Electricity` text DEFAULT NULL,
    ADD COLUMN `Oil` text DEFAULT NULL,
    ADD COLUMN `Occupation` text DEFAULT NULL;
