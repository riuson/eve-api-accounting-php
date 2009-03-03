-- phpMyAdmin SQL Dump
-- version 3.1.2
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Мар 03 2009 г., 20:58
-- Версия сервера: 5.0.67
-- Версия PHP: 5.2.6-2ubuntu4.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `sleephost_ea`
--

-- --------------------------------------------------------

--
-- Структура таблицы `api_account_balance`
--

CREATE TABLE IF NOT EXISTS `api_account_balance` (
  `recordId` char(40) character set utf8 collate utf8_unicode_ci NOT NULL,
  `accountId` char(40) character set utf8 collate utf8_unicode_ci NOT NULL,
  `accountKey` bigint(20) NOT NULL,
  `balance` double NOT NULL,
  `balanceUpdated` timestamp NOT NULL default '0000-00-00 00:00:00' on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`recordId`),
  UNIQUE KEY `accountId` (`accountId`,`accountKey`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `api_alliances`
--

CREATE TABLE IF NOT EXISTS `api_alliances` (
  `name` varchar(100) collate utf8_unicode_ci NOT NULL,
  `shortName` varchar(100) collate utf8_unicode_ci NOT NULL,
  `allianceId` bigint(20) unsigned NOT NULL,
  `executorCorpId` bigint(20) unsigned NOT NULL,
  `memberCount` bigint(20) unsigned NOT NULL,
  `startDate` date NOT NULL,
  `updateFlag` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`allianceId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='список альянсов';

-- --------------------------------------------------------

--
-- Структура таблицы `api_assets`
--

CREATE TABLE IF NOT EXISTS `api_assets` (
  `recordId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `parentId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `locationId` bigint(20) NOT NULL default '0',
  `itemId` bigint(20) NOT NULL,
  `typeId` bigint(20) NOT NULL,
  `quantity` int(11) NOT NULL,
  `flag` int(11) NOT NULL,
  `singleton` int(11) NOT NULL,
  `hasChilds` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`recordId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `api_assets_monitor`
--

CREATE TABLE IF NOT EXISTS `api_assets_monitor` (
  `recordId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `locationId` bigint(20) NOT NULL,
  `typeId` bigint(20) NOT NULL,
  `quantityMinimum` int(11) NOT NULL,
  `quantityNormal` int(11) NOT NULL,
  PRIMARY KEY  (`recordId`),
  UNIQUE KEY `accountId` (`accountId`,`locationId`,`typeId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='список слежения за ресурсами assets';

-- --------------------------------------------------------

--
-- Структура таблицы `api_cache`
--

CREATE TABLE IF NOT EXISTS `api_cache` (
  `recordId` char(40) collate utf8_unicode_ci NOT NULL,
  `accountId` char(40) collate utf8_unicode_ci NOT NULL,
  `uri` varchar(200) collate utf8_unicode_ci NOT NULL,
  `cached` datetime NOT NULL COMMENT 'когда запись обновлена',
  `cachedUntil` datetime NOT NULL COMMENT 'когда её можно будет обновить в следующий раз',
  `cachedValue` mediumtext collate utf8_unicode_ci NOT NULL COMMENT 'закешированный ответ сервера на запрос',
  PRIMARY KEY  (`recordId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Дублирующая структура для представления `api_cache_last`
--
CREATE TABLE IF NOT EXISTS `api_cache_last` (
`recordId` char(40)
,`accountId` char(40)
,`uri` varchar(200)
,`cached` datetime
,`cachedUntil` datetime
,`cachedValue` mediumtext
);
-- --------------------------------------------------------

--
-- Структура таблицы `api_corporations`
--

CREATE TABLE IF NOT EXISTS `api_corporations` (
  `corporationId` bigint(20) unsigned NOT NULL,
  `startDate` datetime NOT NULL,
  `allianceId` bigint(20) NOT NULL,
  `updateFlag` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`corporationId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='перечень корпораций eve';

-- --------------------------------------------------------

--
-- Структура таблицы `api_emails_src`
--

CREATE TABLE IF NOT EXISTS `api_emails_src` (
  `recordId` varchar(40) character set utf8 collate utf8_unicode_ci NOT NULL,
  `accountId` varchar(40) character set utf8 collate utf8_unicode_ci NOT NULL,
  `refId` bigint(20) NOT NULL,
  `emailTime` datetime NOT NULL,
  `emailType` varchar(20) character set utf8 collate utf8_unicode_ci NOT NULL COMMENT 'тип сообщения',
  `issuedTime` datetime NOT NULL,
  `expiredTime` datetime NOT NULL,
  `shipTypeName` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  `shipName` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  `insuranceLevel` double NOT NULL,
  `insuranceISK` double NOT NULL,
  `emailText` varchar(2000) character set utf8 collate utf8_unicode_ci NOT NULL,
  `hashtext` varchar(40) character set utf8 collate utf8_unicode_ci NOT NULL COMMENT 'md5 хеш текст письма',
  PRIMARY KEY  (`recordId`),
  KEY `hashtext` (`hashtext`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='список писем по поводу страховки';

-- --------------------------------------------------------

--
-- Структура таблицы `api_errors`
--

CREATE TABLE IF NOT EXISTS `api_errors` (
  `errorCode` bigint(20) NOT NULL,
  `errorText` varchar(255) collate utf8_unicode_ci NOT NULL,
  `updateFlag` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`errorCode`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='список ошибок eve api';

-- --------------------------------------------------------

--
-- Структура таблицы `api_facwartopstats`
--

CREATE TABLE IF NOT EXISTS `api_facwartopstats` (
  `forWho` varchar(40) collate utf8_unicode_ci NOT NULL,
  `statName` varchar(40) collate utf8_unicode_ci NOT NULL,
  `id` bigint(20) NOT NULL,
  `name` varchar(40) collate utf8_unicode_ci NOT NULL,
  `value` bigint(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='топ 100 фракционных войн';

-- --------------------------------------------------------

--
-- Структура таблицы `api_industry_jobs`
--

CREATE TABLE IF NOT EXISTS `api_industry_jobs` (
  `recordId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `jobId` int(11) NOT NULL,
  `assemblyLineId` int(11) NOT NULL,
  `containerId` int(11) NOT NULL,
  `installedItemId` int(11) NOT NULL,
  `installedItemLocationId` int(11) NOT NULL,
  `installedItemQuantity` int(11) NOT NULL,
  `installedItemProductivityLevel` int(11) NOT NULL,
  `installedItemMaterialLevel` int(11) NOT NULL,
  `installedItemLicensedProductionRunsRemaining` int(11) NOT NULL,
  `outputLocationId` int(11) NOT NULL,
  `installerId` int(11) NOT NULL,
  `runs` int(11) NOT NULL,
  `licensedProductionRuns` int(11) NOT NULL,
  `installedInSolarSystemId` int(11) NOT NULL,
  `containerLocationId` int(11) NOT NULL,
  `materialMultiplier` float NOT NULL,
  `charMaterialMultiplier` float NOT NULL,
  `timeMultiplier` float NOT NULL,
  `charTimeMultiplier` float NOT NULL,
  `installedItemTypeId` int(11) NOT NULL,
  `outputTypeId` int(11) NOT NULL,
  `containerTypeId` int(11) NOT NULL,
  `installedItemCopy` int(11) NOT NULL,
  `completed` int(11) NOT NULL,
  `completedSuccessfully` int(11) NOT NULL,
  `installedItemFlag` int(11) NOT NULL,
  `outputFlag` int(11) NOT NULL,
  `activityId` int(11) NOT NULL,
  `completedStatus` int(11) NOT NULL,
  `installTime` datetime NOT NULL,
  `beginProductionTime` datetime NOT NULL,
  `endProductionTime` datetime NOT NULL,
  `pauseProductionTime` datetime NOT NULL,
  PRIMARY KEY  (`recordId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Структура таблицы `api_insurance_emails`
--

CREATE TABLE IF NOT EXISTS `api_insurance_emails` (
  `recordId` varchar(40) character set utf8 collate utf8_unicode_ci NOT NULL,
  `accountId` varchar(40) character set utf8 collate utf8_unicode_ci NOT NULL,
  `refId` bigint(20) NOT NULL,
  `emailTime` datetime NOT NULL,
  `emailType` varchar(20) character set utf8 collate utf8_unicode_ci NOT NULL COMMENT 'тип сообщения',
  `issuedTime` datetime NOT NULL,
  `expiredTime` datetime NOT NULL,
  `shipTypeName` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  `shipName` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  `insuranceLevel` double NOT NULL,
  `insuranceISK` double NOT NULL,
  `emailText` varchar(2000) character set utf8 collate utf8_unicode_ci NOT NULL,
  `hashtext` varchar(40) character set utf8 collate utf8_unicode_ci NOT NULL COMMENT 'md5 хеш текст письма',
  PRIMARY KEY  (`recordId`),
  UNIQUE KEY `accountId` (`accountId`,`hashtext`),
  KEY `hashtext` (`hashtext`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='список писем по поводу страховки';

-- --------------------------------------------------------

--
-- Структура таблицы `api_insurance_list`
--

CREATE TABLE IF NOT EXISTS `api_insurance_list` (
  `recordId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `refId` bigint(20) NOT NULL,
  `status` varchar(100) collate utf8_unicode_ci NOT NULL,
  `insuranceStart` datetime NOT NULL,
  `insuranceEnd` datetime NOT NULL,
  `insuranceISK` double NOT NULL,
  `shipTypeName` varchar(100) collate utf8_unicode_ci NOT NULL,
  `shipName` varchar(100) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`recordId`),
  UNIQUE KEY `accountId` (`accountId`,`refId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='список застрахованных кораблей';

-- --------------------------------------------------------

--
-- Структура таблицы `api_kills`
--

CREATE TABLE IF NOT EXISTS `api_kills` (
  `recordId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `killId` bigint(20) NOT NULL,
  `solarSystemId` bigint(20) NOT NULL,
  `killTime` datetime NOT NULL,
  `moonId` bigint(20) NOT NULL,
  PRIMARY KEY  (`recordId`),
  UNIQUE KEY `accountId` (`accountId`,`killId`,`killTime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='список киллов корпы';

-- --------------------------------------------------------

--
-- Структура таблицы `api_kills_attackers`
--

CREATE TABLE IF NOT EXISTS `api_kills_attackers` (
  `recordId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `recordKillId` varchar(40) collate utf8_unicode_ci NOT NULL COMMENT 'id записи килла',
  `characterId` bigint(20) NOT NULL,
  `characterName` varchar(255) collate utf8_unicode_ci NOT NULL,
  `corporationId` bigint(20) NOT NULL,
  `corporationName` varchar(255) collate utf8_unicode_ci NOT NULL,
  `allianceId` bigint(20) NOT NULL,
  `allianceName` varchar(255) collate utf8_unicode_ci NOT NULL,
  `securityStatus` double NOT NULL,
  `damageDone` int(11) NOT NULL,
  `finalBlow` int(11) NOT NULL,
  `weaponTypeId` bigint(20) NOT NULL,
  `shipTypeId` bigint(20) NOT NULL,
  PRIMARY KEY  (`recordId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='атакующие в киллах';

-- --------------------------------------------------------

--
-- Структура таблицы `api_kills_items`
--

CREATE TABLE IF NOT EXISTS `api_kills_items` (
  `recordId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `recordKillId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `parentId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `typeId` bigint(20) NOT NULL,
  `flag` int(11) NOT NULL default '0',
  `qtyDropped` int(11) NOT NULL default '0',
  `qtyDestroyed` int(11) NOT NULL default '0',
  `hasChilds` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`recordId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='лут с киллов';

-- --------------------------------------------------------

--
-- Структура таблицы `api_kills_victims`
--

CREATE TABLE IF NOT EXISTS `api_kills_victims` (
  `recordId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `recordKillId` varchar(40) collate utf8_unicode_ci NOT NULL COMMENT 'id записи килла',
  `characterId` bigint(20) NOT NULL,
  `characterName` varchar(255) collate utf8_unicode_ci NOT NULL,
  `corporationId` bigint(20) NOT NULL,
  `corporationName` varchar(255) collate utf8_unicode_ci NOT NULL,
  `allianceId` bigint(20) NOT NULL,
  `allianceName` varchar(255) collate utf8_unicode_ci NOT NULL,
  `damageTaken` int(11) NOT NULL,
  `shipTypeId` bigint(20) NOT NULL,
  PRIMARY KEY  (`recordId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='жертвы киллов';

-- --------------------------------------------------------

--
-- Структура таблицы `api_log`
--

CREATE TABLE IF NOT EXISTS `api_log` (
  `recordId` bigint(20) unsigned NOT NULL auto_increment,
  `_date_` datetime NOT NULL default '0000-00-00 00:00:00',
  `message` mediumtext collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`recordId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='лог событий' AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Структура таблицы `api_market_orders`
--

CREATE TABLE IF NOT EXISTS `api_market_orders` (
  `recordId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `orderId` int(11) NOT NULL,
  `charId` int(11) NOT NULL,
  `stationId` int(11) NOT NULL,
  `volEntered` int(11) NOT NULL,
  `volRemaining` int(11) NOT NULL,
  `minVolume` int(11) NOT NULL,
  `orderState` tinyint(4) NOT NULL,
  `typeId` int(11) NOT NULL,
  `range` int(11) NOT NULL,
  `accountKey` int(11) NOT NULL,
  `duration` int(11) NOT NULL,
  `escrow` decimal(10,0) NOT NULL,
  `price` decimal(10,0) NOT NULL,
  `bid` tinyint(1) NOT NULL,
  `issued` datetime NOT NULL,
  PRIMARY KEY  (`recordId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `api_member_security`
--

CREATE TABLE IF NOT EXISTS `api_member_security` (
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `characterId` bigint(20) NOT NULL,
  `characterName` varchar(255) collate utf8_unicode_ci NOT NULL,
  `roles` bigint(20) NOT NULL default '0',
  `grantableRoles` bigint(20) NOT NULL default '0',
  `rolesAtHQ` bigint(20) NOT NULL default '0',
  `grantableRolesAtHQ` bigint(20) NOT NULL default '0',
  `rolesAtBase` bigint(20) NOT NULL default '0',
  `grantableRolesAtBase` bigint(20) NOT NULL default '0',
  `rolesAtOther` bigint(20) NOT NULL default '0',
  `grantableRolesAtOther` bigint(20) NOT NULL default '0',
  `titles` bigint(20) NOT NULL default '0',
  KEY `characterId` (`characterId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='роли и титлы';

-- --------------------------------------------------------

--
-- Структура таблицы `api_member_tracking`
--

CREATE TABLE IF NOT EXISTS `api_member_tracking` (
  `recordId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `characterId` bigint(20) NOT NULL,
  `name` varchar(255) collate utf8_unicode_ci NOT NULL,
  `startDateTime` datetime NOT NULL,
  `baseId` bigint(20) NOT NULL,
  `base` varchar(255) collate utf8_unicode_ci NOT NULL,
  `title` varchar(255) collate utf8_unicode_ci NOT NULL,
  `logonDateTime` datetime NOT NULL,
  `logoffDateTime` datetime NOT NULL,
  `locationId` bigint(20) NOT NULL,
  `location` varchar(255) collate utf8_unicode_ci NOT NULL,
  `shipTypeId` bigint(20) NOT NULL,
  `shipType` varchar(255) collate utf8_unicode_ci NOT NULL,
  `roles` bigint(20) NOT NULL,
  `grantableRoles` bigint(20) NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`recordId`),
  UNIQUE KEY `accountId` (`accountId`,`characterId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='member tracking';

-- --------------------------------------------------------

--
-- Структура таблицы `api_outposts`
--

CREATE TABLE IF NOT EXISTS `api_outposts` (
  `stationId` bigint(20) unsigned NOT NULL,
  `stationName` varchar(255) collate utf8_unicode_ci NOT NULL,
  `stationTypeId` bigint(20) unsigned NOT NULL,
  `solarSystemId` bigint(20) unsigned NOT NULL,
  `corporationId` bigint(20) unsigned NOT NULL,
  `corporationName` varchar(255) collate utf8_unicode_ci NOT NULL,
  `updateFlag` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`stationId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='аутпосты';

-- --------------------------------------------------------

--
-- Структура таблицы `api_reftypes`
--

CREATE TABLE IF NOT EXISTS `api_reftypes` (
  `refTypeId` bigint(20) NOT NULL,
  `refTypeName` varchar(255) collate utf8_unicode_ci NOT NULL,
  `updateFlag` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`refTypeId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='типы переводов';

-- --------------------------------------------------------

--
-- Структура таблицы `api_sessions`
--

CREATE TABLE IF NOT EXISTS `api_sessions` (
  `recordId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `sessionId` varchar(255) collate utf8_unicode_ci NOT NULL,
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `address` varchar(16) collate utf8_unicode_ci NOT NULL,
  `expiredTime` datetime NOT NULL,
  PRIMARY KEY  (`recordId`),
  UNIQUE KEY `sessionId` (`sessionId`,`address`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='сессии аутентификации';

-- --------------------------------------------------------

--
-- Структура таблицы `api_sovereignty`
--

CREATE TABLE IF NOT EXISTS `api_sovereignty` (
  `solarSystemId` int(11) NOT NULL,
  `allianceId` int(11) NOT NULL,
  `constellationSovereignty` int(11) NOT NULL,
  `sovereigntyLevel` int(11) NOT NULL,
  `factionId` int(11) NOT NULL,
  `solarSystemName` varchar(255) collate utf8_unicode_ci NOT NULL,
  `updateFlag` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`solarSystemId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `api_standings`
--

CREATE TABLE IF NOT EXISTS `api_standings` (
  `recordId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `id` bigint(20) NOT NULL,
  `name` varchar(255) collate utf8_unicode_ci NOT NULL,
  `standings` float NOT NULL,
  `standsOf` tinyint(4) NOT NULL default '0' COMMENT 'чьи стенды: 1 - corporationStandings, 2 - allianceStandings',
  `fromTo` tinyint(4) NOT NULL default '0' COMMENT 'стенды "к" или "от": 1 - standingsTo, 2 - standingsFrom',
  `target` tinyint(4) NOT NULL default '0' COMMENT 'обьект назначения: 1 - пилоты, 2 - корпорации, 3 - альянсы, 4 - агенты, 5 - нпц корпорации, 6 - фракции',
  PRIMARY KEY  (`recordId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='стендинги';

-- --------------------------------------------------------

--
-- Структура таблицы `api_starbases`
--

CREATE TABLE IF NOT EXISTS `api_starbases` (
  `recordId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `itemId` int(11) NOT NULL,
  `typeId` int(11) NOT NULL,
  `locationId` int(11) NOT NULL,
  `moonId` int(11) NOT NULL,
  `state` int(11) NOT NULL,
  `stateTimestamp` datetime NOT NULL,
  `onlineTimestamp` datetime NOT NULL,
  `details` mediumtext collate utf8_unicode_ci NOT NULL,
  `endTimestamp` datetime NOT NULL COMMENT 'время конца топлива',
  `updateFlag` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`recordId`),
  UNIQUE KEY `accountId` (`accountId`,`itemId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='ПОСы';

-- --------------------------------------------------------

--
-- Структура таблицы `api_starbase_fuel`
--

CREATE TABLE IF NOT EXISTS `api_starbase_fuel` (
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `itemId` int(11) NOT NULL,
  `time1` datetime NOT NULL,
  `time2` datetime NOT NULL,
  `ozone1` int(11) NOT NULL,
  `ozone2` int(11) NOT NULL,
  `water1` int(11) NOT NULL,
  `water2` int(11) NOT NULL,
  `refuelling` datetime NOT NULL,
  UNIQUE KEY `accountId` (`accountId`,`itemId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='наблюдение за расходом топлива';

-- --------------------------------------------------------

--
-- Структура таблицы `api_subscribes`
--

CREATE TABLE IF NOT EXISTS `api_subscribes` (
  `recordId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `email` varchar(255) collate utf8_unicode_ci NOT NULL,
  `modes` mediumtext collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`recordId`),
  UNIQUE KEY `accountId` (`accountId`,`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='подписки на обновление данных';

-- --------------------------------------------------------

--
-- Структура таблицы `api_titles`
--

CREATE TABLE IF NOT EXISTS `api_titles` (
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL,
  `titleId` bigint(20) NOT NULL,
  `titleName` varchar(255) collate utf8_unicode_ci NOT NULL,
  UNIQUE KEY `accountId` (`accountId`,`titleId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='титлы, получаемые из member security';

-- --------------------------------------------------------

--
-- Структура таблицы `api_users`
--

CREATE TABLE IF NOT EXISTS `api_users` (
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL COMMENT 'id записи',
  `login` varchar(40) collate utf8_unicode_ci NOT NULL COMMENT 'логин пользователя',
  `password` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'пароль в md5',
  `email` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'email',
  `master` varchar(40) collate utf8_unicode_ci NOT NULL COMMENT 'логин мастер-аккаунта. если пусто - он сам является мастером',
  `userId` bigint(20) unsigned NOT NULL default '0' COMMENT 'ид акка eve online',
  `apiKey` varchar(255) collate utf8_unicode_ci NOT NULL default '0' COMMENT 'апи ключ с полным доступом',
  `characterId` bigint(20) unsigned NOT NULL default '0' COMMENT 'ид персонажа, с которого будут идти запросы',
  `characterName` varchar(255) collate utf8_unicode_ci NOT NULL default 'unknown' COMMENT 'имя персонажа',
  `access` text collate utf8_unicode_ci NOT NULL COMMENT 'перечень режимов, к которым открыт доступ',
  PRIMARY KEY  (`accountId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='пользователи сервиса';

-- --------------------------------------------------------

--
-- Структура таблицы `api_users_reserve`
--

CREATE TABLE IF NOT EXISTS `api_users_reserve` (
  `recordId` bigint(20) unsigned NOT NULL auto_increment,
  `accountId` varchar(40) collate utf8_unicode_ci NOT NULL COMMENT 'id аккаунта, для которого здесь хранится резервный ключ',
  `userId` bigint(20) NOT NULL,
  `apiKey` varchar(255) collate utf8_unicode_ci NOT NULL,
  `characterId` bigint(20) NOT NULL,
  `valid` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`recordId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `api_visitors`
--

CREATE TABLE IF NOT EXISTS `api_visitors` (
  `recordId` bigint(20) unsigned NOT NULL auto_increment,
  `_date_` datetime NOT NULL,
  `address` varchar(20) collate utf8_unicode_ci NOT NULL,
  `agent` varchar(255) collate utf8_unicode_ci NOT NULL,
  `login` varchar(255) collate utf8_unicode_ci NOT NULL,
  `uri` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`recordId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='список посетителей' AUTO_INCREMENT=3335 ;

-- --------------------------------------------------------

--
-- Структура таблицы `api_wallet_journal`
--

CREATE TABLE IF NOT EXISTS `api_wallet_journal` (
  `recordId` char(40) character set utf8 collate utf8_unicode_ci NOT NULL,
  `accountId` char(40) character set utf8 collate utf8_unicode_ci NOT NULL,
  `accountKey` bigint(20) NOT NULL,
  `refId` bigint(20) NOT NULL,
  `_date_` datetime NOT NULL,
  `refTypeId` bigint(20) NOT NULL,
  `ownerName1` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  `ownerId1` bigint(20) NOT NULL,
  `ownerName2` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  `ownerId2` bigint(20) NOT NULL,
  `argName1` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  `argId1` bigint(20) NOT NULL,
  `amount` double NOT NULL,
  `balance` double NOT NULL,
  `reason` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL default '-',
  PRIMARY KEY  (`recordId`),
  UNIQUE KEY `unique_record` (`accountId`,`refId`,`_date_`,`accountKey`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `api_wallet_transactions`
--

CREATE TABLE IF NOT EXISTS `api_wallet_transactions` (
  `recordId` char(40) character set utf8 collate utf8_unicode_ci NOT NULL,
  `accountId` char(40) character set utf8 collate utf8_unicode_ci NOT NULL,
  `accountKey` bigint(20) NOT NULL,
  `transId` bigint(20) NOT NULL,
  `_date_` datetime NOT NULL,
  `quantity` bigint(20) NOT NULL,
  `typeName` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  `typeId` bigint(20) NOT NULL,
  `price` double NOT NULL,
  `clientId` bigint(20) NOT NULL,
  `clientName` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  `characterId` bigint(20) NOT NULL,
  `characterName` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  `stationId` bigint(20) NOT NULL,
  `stationName` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  `transactionType` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  `transactionFor` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`recordId`),
  UNIQUE KEY `unique_record` (`accountId`,`transId`,`accountKey`,`_date_`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура для представления `api_cache_last`
--
DROP TABLE IF EXISTS `api_cache_last`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `api_cache_last` AS select `api_cache`.`recordId` AS `recordId`,`api_cache`.`accountId` AS `accountId`,`api_cache`.`uri` AS `uri`,`api_cache`.`cached` AS `cached`,`api_cache`.`cachedUntil` AS `cachedUntil`,`api_cache`.`cachedValue` AS `cachedValue` from `api_cache` order by `api_cache`.`cached` desc limit 20;
