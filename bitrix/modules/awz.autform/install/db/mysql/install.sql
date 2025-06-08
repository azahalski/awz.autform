CREATE TABLE IF NOT EXISTS `b_awz_autform_codes` (
    ID int(18) NOT NULL AUTO_INCREMENT,
    PHONE varchar(255) NOT NULL,
    CODE varchar(25) NOT NULL,
    IP_STR varchar(64) NOT NULL,
    CREATE_DATE datetime NOT NULL,
    EXPIRED_DATE datetime NOT NULL,
    PRM longtext,
    PRIMARY KEY (`ID`),
    index IX_PHONE_DATE (PHONE, EXPIRED_DATE),
    index IX_IP_STR (IP_STR, EXPIRED_DATE),
    index IX_EXPIRED_DATE (EXPIRED_DATE)
);
CREATE TABLE IF NOT EXISTS `b_awz_autform_logs` (
    ID int(18) NOT NULL AUTO_INCREMENT,
    PHONE varchar(255) NOT NULL,
    CREATE_DATE datetime NOT NULL,
    PRM longtext,
    PRIMARY KEY (`ID`)
);