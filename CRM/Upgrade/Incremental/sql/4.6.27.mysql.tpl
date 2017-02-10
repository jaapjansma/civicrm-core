{* file to handle db changes in 4.6.27 during upgrade *}

-- CRM-19934: create the neccesary tables (civicrm_acl_contacts)
CREATE TABLE `civicrm_acl_contacts` (
    `domain_id` int unsigned NOT NULL   COMMENT 'Which Domain is this match entry for',
    `user_id` int unsigned NOT NULL   COMMENT 'FK to civicrm_contact',
    `contact_id` int unsigned NOT NULL   COMMENT 'FK to civicrm_contact',
    `operation_type` int unsigned NOT NULL   COMMENT 'What operation does this user have permission on?',
    PRIMARY KEY (`user_id`,`operation_type`,`contact_id`,`domain_id`),
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;

-- CRM-19934: create the neccesary tables (civicrm_acl_contacts_validity)
CREATE TABLE `civicrm_acl_contacts_validity` (
    `domain_id` int unsigned NOT NULL   COMMENT 'Which Domain is this match entry for',
    `user_id` int unsigned NOT NULL   COMMENT 'FK to civicrm_contact',
    `operation_type` int unsigned NOT NULL   COMMENT 'What operation does this user have permission on?',
    `modified_date` timestamp NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When was this record for the last modified',
    PRIMARY KEY (`user_id`,`operation_type`,`domain_id`),
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;

-- CRM-19934: And finally drop the old civicrm_acl_contact_cache table
DROP TABLE `civicrm_acl_contact_cache`;
--