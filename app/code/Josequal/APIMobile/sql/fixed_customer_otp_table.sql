-- Fixed customer_otp table creation
-- This fixes the BLOB/TEXT column issue by using VARCHAR instead of TEXT

DROP TABLE IF EXISTS `customer_otp`;

CREATE TABLE `customer_otp` (
    `otp_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'OTP ID',
    `customer_id` int(10) unsigned NOT NULL COMMENT 'Customer ID',
    `otp` varchar(10) NOT NULL COMMENT 'OTP Code',
    `expires_at` datetime NOT NULL COMMENT 'Expiration Date',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created At',
    PRIMARY KEY (`otp_id`),
    KEY `CUSTOMER_OTP_CUSTOMER_ID` (`customer_id`),
    KEY `CUSTOMER_OTP_OTP` (`otp`),
    KEY `CUSTOMER_OTP_EXPIRES_AT` (`expires_at`),
    CONSTRAINT `CUSTOMER_OTP_CUSTOMER_ID_CUSTOMER_ENTITY_ENTITY_ID` FOREIGN KEY (`customer_id`) REFERENCES `customer_entity` (`entity_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Customer OTP Table';
