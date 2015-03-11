SELECT * FROM t1


ALTER TABLE t1 ADD COLUMN  ` mid` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `id`

ALTER TABLE `bigtwo_members_gameinfo0` ADD `chips` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `money` 

SELECT * FROM sort

SELECT IF(sort=28,1,0) AS sort FROM sort WHERE id=22


CREATE TABLE IF NOT EXISTS `bigtwo_language` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `sid` SMALLINT(4) UNSIGNED NOT NULL DEFAULT '13' COMMENT '平台',
  `client_desc` VARCHAR(255) NOT NULL DEFAULT '''' COMMENT '前端显示',
  `admin_desc` VARCHAR(255) NOT NULL DEFAULT '''' COMMENT '后台显示',
  `intr` VARCHAR(255) NOT NULL DEFAULT '''' COMMENT '翻译备注',
  `mod` SMALLINT(4) UNSIGNED NOT NULL DEFAULT '0' COMMENT '模块',
  PRIMARY KEY (`id`,`sid`)
) ENGINE=MYISAM  DEFAULT CHARSET=utf8 COMMENT='语言包' AUTO_INCREMENT=289

INSERT INTO bigtwo_language(sid,client_desc,admin_desc,intr,`mod`) VALUES(14,'a','b','\'',0)

SELECT * FROM bigtwo_language

SELECT EXTRACT(DAY FROM NOW())

SELECT DAYOFMONTH(NOW())

/*查询上月第一天*/
SELECT DATE_SUB(DATE_SUB(DATE_FORMAT(NOW(),'%y-%m-%d'),INTERVAL EXTRACT(DAY FROM NOW())-1 DAY),INTERVAL 1 MONTH)

/**上月最后一天*/
SELECT DATE_SUB(DATE_SUB(DATE_FORMAT(NOW(),'%y-%m-%d'),INTERVAL EXTRACT(  
   DAY FROM NOW()) DAY),INTERVAL 0 MONTH) AS DATE
   
/*本月第一天*/
 SELECT DATE_SUB(DATE_SUB(DATE_FORMAT(NOW(),'%y-%m-%d'),INTERVAL DAYOFMONTH(NOW())-1 DAY),INTERVAL 0 MONTH)

SELECT MONTH(FROM_UNIXTIME(firstptime)) FROM bigtwo_payment_stat LIMIT 10

SHOW GLOBAL STATUS LIKE 'innodb_db%'
SELECT * FROM t1

ALTER TABLE bigtwo_log.bigtwo_winlog MODIFY wmode SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0'

ALTER TABLE t1 MODIFY sid SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0'


SELECT CONCAT(ROUND(SUM(DATA_LENGTH/1024/1024),2),'MB') FROM TABLES


SELECT CONNECTION_ID()

DELIMITER $$
CREATE DEFINER=`root`@`192.168.%` PROCEDURE `LogoutUpdateMoneyExp`(IN in_uid INT, IN mymoney INT, IN in_turncoin INT, IN in_exp INT, IN in_level INT, IN wins INT, IN losts INT) 
BEGIN
UPDATE bigtwo_members SET money = IF(money + mymoney < 0, 0, money + mymoney), coin = IF(coin + in_turncoin < 0, 0, coin + in_turncoin), EXP = in_exp, LEVEL = in_level, losetimes = losts, wintimes = wins WHERE bigtwo_members.mid = in_uid;
END$$

DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`192.168.%` PROCEDURE `WritePokerLog`(IN in_content TEXT, IN in_pidlog TEXT, IN in_tid INT, IN in_type INT, IN in_landscore INT, IN in_basechip INT, IN in_result CHAR,
IN in_uid1 INT, IN in_money1 INT, IN in_turnmoney1 INT, IN in_sid1 INT, IN in_psid1 TEXT,
IN in_uid2 INT, IN in_money2 INT, IN in_turnmoney2 INT, IN in_sid2 INT, IN in_psid2 TEXT,
IN in_uid3 INT, IN in_money3 INT, IN in_turnmoney3 INT, IN in_sid3 INT, IN in_psid3 TEXT,
IN in_uid4 INT, IN in_money4 INT, IN in_turnmoney4 INT, IN in_sid4 INT, IN in_psid4 TEXT)
BEGIN

SET @CURTIME = UNIX_TIMESTAMP();
SET @tax = -(in_turnmoney1 + in_turnmoney2 + in_turnmoney3 + in_turnmoney4);

INSERT INTO bigtwo_logtable VALUES ('', in_tid, in_type, in_content, @tax, @CURTIME, in_landscore, in_basechip, in_result, in_pidlog);
SET @lastId = LAST_INSERT_ID();

INSERT INTO bigtwo_logmember VALUES ('', @lastId, in_uid1, in_tid, in_turnmoney1, in_money1, @CURTIME, in_psid1, in_basechip), 
('', @lastId, in_uid2, in_tid, in_turnmoney2, in_money2, @CURTIME, in_psid2, in_basechip),
('', @lastId, in_uid3, in_tid, in_turnmoney3, in_money3, @CURTIME, in_psid3, in_basechip),
('', @lastId, in_uid4, in_tid, in_turnmoney4, in_money4, @CURTIME, in_psid4, in_basechip);

IF in_money1 < 200 THEN 
INSERT INTO bigtwo_logbankrupt VALUES ('', in_uid1, in_basechip, in_type, in_sid1, @CURTIME, in_psid1);
END IF;

IF in_money2 < 200 THEN 
INSERT INTO bigtwo_logbankrupt VALUES ('', in_uid2, in_basechip, in_type, in_sid2, @CURTIME, in_psid2);
END IF;
IF in_money3 < 200 THEN 
INSERT INTO bigtwo_logbankrupt VALUES ('', in_uid3, in_basechip, in_type, in_sid3, @CURTIME, in_psid3);
END IF;

IF in_money4 < 200 THEN 
INSERT INTO bigtwo_logbankrupt VALUES ('', in_uid4, in_basechip, in_type, in_sid4, @CURTIME, in_psid4);
END IF;

END$$




DELIMITER ;

GRANT ALL PRIVILEGES ON bigtwo  TO  'monitor'@'localhost' 