-- phpMyAdmin SQL Dump
-- version 3.5.2.2
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Фев 02 2018 г., 10:00
-- Версия сервера: 5.6.34-log
-- Версия PHP: 5.6.28-pl0-gentoo

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `cascade`
--

-- --------------------------------------------------------

--
-- Структура таблицы `access_objects`
--

CREATE TABLE IF NOT EXISTS `access_objects` (
  `object_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор объекта',
  `type` int(10) unsigned NOT NULL COMMENT 'Тип объекта',
  `name` char(32) NOT NULL COMMENT 'Наименование объекта доступа',
  `desc` char(255) NOT NULL COMMENT 'Описание',
  `is_lock` int(1) unsigned NOT NULL COMMENT 'Признак того, что объект заблокирован',
  `min_access_level` int(10) unsigned NOT NULL COMMENT 'Минимальное значение access_level у пользователя для получения доступа к этому объекту',
  `for_all_companies` int(1) unsigned NOT NULL COMMENT 'Признак, указывающий что объект может использоваться только для всех организаций',
  PRIMARY KEY (`object_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Объекты доступа' AUTO_INCREMENT=63 ;

-- --------------------------------------------------------

--
-- Структура таблицы `access_roles`
--

CREATE TABLE IF NOT EXISTS `access_roles` (
  `parent_id` int(11) NOT NULL COMMENT 'Родительский объект - контейнер',
  `object_id` int(11) NOT NULL COMMENT 'Объект, входящий в родительский объект',
  PRIMARY KEY (`parent_id`,`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251 COMMENT='Группировки объектов доступа в роли';

-- --------------------------------------------------------

--
-- Структура таблицы `assistants`
--

CREATE TABLE IF NOT EXISTS `assistants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `employer_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор пользователя, которого замещают',
  `assistant_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор пользователя - заместителя',
  `from_date` date NOT NULL COMMENT 'Дата (включительно), c которой заместителю дано право замещать пользователя',
  `to_date` date NOT NULL COMMENT 'Дата (включительно), до которой заместителю дано право замещать пользователя',
  `timestamp` datetime NOT NULL COMMENT 'Время добавления комментария',
  `submitter_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор сотрудника, добавившего запись',
  PRIMARY KEY (`id`),
  KEY `employer_id` (`employer_id`,`from_date`,`to_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Таблица пользователей, замещающих гейткиперов' AUTO_INCREMENT=9 ;

-- --------------------------------------------------------

--
-- Структура таблицы `assistants_hist`
--

CREATE TABLE IF NOT EXISTS `assistants_hist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `employer_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор пользователя, которого замещают',
  `assistant_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор пользователя - заместителя',
  `from_date` date NOT NULL COMMENT 'Дата (включительно), c которой заместителю дано право замещать пользователя',
  `to_date` date NOT NULL COMMENT 'Дата (включительно), до которой заместителю дано право замещать пользователя',
  `timestamp` datetime NOT NULL COMMENT 'Время добавления комментария',
  `submitter_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор сотрудника, добавившего запись',
  PRIMARY KEY (`id`),
  KEY `employer_id` (`employer_id`),
  KEY `assistant_id` (`assistant_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Таблица пользователей, замещающих гейткиперов' AUTO_INCREMENT=9 ;

-- --------------------------------------------------------

--
-- Структура таблицы `companies`
--

CREATE TABLE IF NOT EXISTS `companies` (
  `company_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор организации',
  `code` char(16) NOT NULL COMMENT 'Код организации',
  `short_name` char(128) NOT NULL COMMENT 'Сокрещенное наименование (для печатной формы)',
  `full_name` char(255) NOT NULL COMMENT 'Полное наименование (для WEB-интерфейса)',
  `is_lock` int(1) unsigned NOT NULL COMMENT 'Признак блокировки объекта, 1 - не отображать объект нигде в интерфейсе, кроме соответствующего списка',
  PRIMARY KEY (`company_id`),
  KEY `code` (`code`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Перечень организаций' AUTO_INCREMENT=47 ;

-- --------------------------------------------------------

--
-- Структура таблицы `company_posts`
--

CREATE TABLE IF NOT EXISTS `company_posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор должности в организации',
  `post_uid` bigint(20) unsigned NOT NULL COMMENT 'Уникальный идентификатор должности 1 00000 (5 - ID организации) 0000000 (7 - ID должности) 0000000 (7 - ID руководителя)',
  `boss_uid` bigint(20) unsigned NOT NULL COMMENT 'Уникальный идентификатор должности руководителя',
  `company_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор организации',
  `post_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор должности',
  `boss_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор руководителя по данной должности, если 0 - сотрудник на данной должности в указанной организации никому не подчиняется',
  `pos_x` int(11) NOT NULL COMMENT 'Положение объекта в организационной диаграмме по X',
  `pos_y` int(11) NOT NULL COMMENT 'Положение объекта в организационной диаграмме по Y',
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`,`post_id`,`boss_id`),
  KEY `uid` (`post_uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Перечень должностей в организациях' AUTO_INCREMENT=4777 ;

-- --------------------------------------------------------

--
-- Структура таблицы `complete_roles`
--

CREATE TABLE IF NOT EXISTS `complete_roles` (
  `employer_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор сотрудника',
  `iresource_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор информационного ресурса',
  `irole_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор привелегии доступа',
  `ir_type` int(10) unsigned NOT NULL COMMENT 'Тип доступа',
  PRIMARY KEY (`employer_id`,`iresource_id`,`irole_id`),
  KEY `employer_id` (`employer_id`),
  KEY `iresource_id` (`iresource_id`),
  KEY `irole_id` (`irole_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251 COMMENT='Таблица перечня доступов сотрудников';

-- --------------------------------------------------------

--
-- Структура таблицы `complete_roles_full`
--

CREATE TABLE IF NOT EXISTS `complete_roles_full` (
  `crole_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `employer_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор сотрудника',
  `company_id` int(10) unsigned NOT NULL COMMENT 'Организация сотрудника на момент получения доступа',
  `post_uid` bigint(20) unsigned NOT NULL COMMENT 'Идентификатор должности сотрудника, в рамках которой был получен доступ',
  `request_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор заявки, на основании которой был предоставлен доступ',
  `iresource_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор информационного ресурса',
  `irole_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор привелегии доступа',
  `ir_type` int(10) unsigned NOT NULL COMMENT 'Тип доступа',
  `timestamp` datetime NOT NULL COMMENT 'Время добавления записи',
  PRIMARY KEY (`crole_id`),
  KEY `employer_id` (`employer_id`),
  KEY `post_uid` (`post_uid`),
  KEY `iresource_id` (`iresource_id`),
  KEY `irole_id` (`irole_id`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Таблица перечня доступов сотрудников - полная информация' AUTO_INCREMENT=27 ;

-- --------------------------------------------------------

--
-- Структура таблицы `employers`
--

CREATE TABLE IF NOT EXISTS `employers` (
  `employer_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор пользователя',
  `status` int(10) unsigned NOT NULL COMMENT 'Статус пользователя (0 - блокирован, 1 - активен, 2 - в отпуске)',
  `access_level` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Уровень доступа с правами администратора (0 - не администратор)',
  `username` char(32) NOT NULL COMMENT 'Логин',
  `password` char(64) NOT NULL COMMENT 'Пароль',
  `change_password` int(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Признак смены временного пароля',
  `last_ip_addr` char(15) NOT NULL COMMENT 'IP адрес последнего входа',
  `last_ip_real` char(15) NOT NULL COMMENT 'IP адрес последнего входа',
  `last_login_time` datetime NOT NULL COMMENT 'Дата и время последнего входа',
  `last_login_type` char(16) NOT NULL COMMENT 'Тип входа',
  `search_name` char(128) NOT NULL COMMENT 'Имя для поиска (стандартно, ФИО сотрудника)',
  `first_name` char(32) NOT NULL COMMENT 'Имя',
  `last_name` char(32) NOT NULL COMMENT 'Фамилия',
  `middle_name` char(32) NOT NULL COMMENT 'Отчество',
  `birth_date` date NOT NULL COMMENT 'Дата рождения',
  `phone` char(64) NOT NULL COMMENT 'Контактный телефон',
  `email` char(64) NOT NULL COMMENT 'Контактный email',
  `work_name` char(255) NOT NULL COMMENT 'Место работы (название организации)',
  `work_address` char(255) NOT NULL COMMENT 'Адрес работы (адрес офиса)',
  `work_post` char(255) NOT NULL COMMENT 'Должность',
  `work_phone` char(30) NOT NULL COMMENT 'Рабочий телефон',
  `create_date` date NOT NULL COMMENT 'Дата создания записи в базе',
  `language` char(2) NOT NULL DEFAULT 'ru' COMMENT 'Язык интерфейса',
  `theme` char(32) NOT NULL DEFAULT 'default' COMMENT 'Тема интерфейса',
  `anket_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор анкеты сотрудника',
  `never_assistant` int(1) unsigned NOT NULL COMMENT 'Запретить другим сотрудникам назначать данного сотрудника в качестве своего заместителя',
  `notice_me_requests` int(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Уведомлять по email о ходе согласования заявки где сотрудник: заявитель',
  `notice_curator_requests` int(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Уведомлять по email о ходе согласования заявки где сотрудник: куратор',
  `notice_gkemail_1` int(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Уведомлять по email при поступлении заявки на согласование где сотрудник: гейткипер',
  `notice_gkemail_2` int(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Уведомлять по email при поступлении заявки на утверждение где сотрудник: гейткипер',
  `notice_gkemail_3` int(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Уведомлять по email при поступлении заявки на исполнение где сотрудник: гейткипер',
  `notice_gkemail_4` int(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Уведомлять по email при поступлении заявки для просмотра где сотрудник: гейткипер',
  `ignore_pin` int(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Признак запрета проверки PIN кода, достаточно сертификата',
  `pin_code` char(64) NOT NULL DEFAULT '0000' COMMENT 'SHA-1 Pin-кода',
  `pin_fails_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Количество неправильных попыток ввода PIN кода',
  `acl_update` int(1) unsigned NOT NULL COMMENT 'Признак необходимости обновления прав доступа сотрудника',
  PRIMARY KEY (`employer_id`),
  KEY `search_name` (`search_name`),
  KEY `username` (`username`,`password`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Перечень пользователей домена' AUTO_INCREMENT=67 ;

-- --------------------------------------------------------

--
-- Структура таблицы `employer_access`
--

CREATE TABLE IF NOT EXISTS `employer_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `employer_id` int(11) NOT NULL COMMENT 'Идентификатор сотрудника',
  `company_id` int(11) NOT NULL COMMENT 'Идентификатор организации, для которой актуален доступ (0 - любая организация)',
  `object_id` int(11) NOT NULL COMMENT 'Идентификатор объекта доступа',
  `is_restrict` int(11) NOT NULL COMMENT 'Признак явного запрета на доступ к данному объекту и всем вложенным объектам',
  PRIMARY KEY (`id`),
  KEY `employer_id` (`employer_id`,`object_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Список объектов, доступных сотрудникам' AUTO_INCREMENT=53 ;

-- --------------------------------------------------------

--
-- Структура таблицы `employer_ankets`
--

CREATE TABLE IF NOT EXISTS `employer_ankets` (
  `anket_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор анкеты',
  `anket_type` int(11) NOT NULL COMMENT 'Тип анкеты (1 - новая анкета, 2 - анкета отклонена, 3 - анкета одобрена)',
  `approved_time` datetime NOT NULL COMMENT 'Время обработки записи',
  `employer_id` int(10) unsigned NOT NULL COMMENT 'Присвоенный новому сотруднику идентификатор (проставляется после одобрения заявки администратором)',
  `curator_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор сотрудника, написавшего заявку',
  `company_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор организации, в которой будет работать новый сотрудник',
  `post_uid` bigint(20) unsigned NOT NULL COMMENT 'Идентификатор должности, которую будет занимать новый сотрудник',
  `order_no` char(64) NOT NULL COMMENT 'Номер приказа о приеме на работу',
  `post_from` date NOT NULL COMMENT 'Дата начала работы',
  `first_name` char(32) NOT NULL COMMENT 'Имя',
  `last_name` char(32) NOT NULL COMMENT 'Фамилия',
  `middle_name` char(32) NOT NULL COMMENT 'Отчество',
  `birth_date` date NOT NULL COMMENT 'Дата рождения',
  `phone` char(64) NOT NULL COMMENT 'Контактный телефон',
  `email` char(64) NOT NULL COMMENT 'Контактный email',
  `work_computer` int(1) unsigned NOT NULL COMMENT 'Признак, что сотрудник будет работать за компьютером',
  `need_accesscard` int(1) unsigned NOT NULL COMMENT 'Признак, что сотруднику нужен пропуск в здание офиса',
  `comment` varchar(4096) NOT NULL COMMENT 'Дополнительная информация',
  `create_time` datetime NOT NULL COMMENT 'Время создания записи',
  PRIMARY KEY (`anket_id`),
  KEY `anket_checked` (`approved_time`,`curator_id`,`company_id`,`post_uid`),
  KEY `employer_id` (`employer_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Анкеты заявок для заведения новых сотрудников' AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Структура таблицы `employer_authlog`
--

CREATE TABLE IF NOT EXISTS `employer_authlog` (
  `session_uid` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'UID сессии',
  `employer_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор пользователя',
  `login_time` datetime NOT NULL COMMENT 'Дата и время последнего входа',
  `ip_addr` char(15) NOT NULL COMMENT 'IP адрес последнего входа',
  `ip_real` char(15) NOT NULL COMMENT 'IP адрес последнего входа',
  `login_type` char(16) NOT NULL COMMENT 'Тип входа',
  PRIMARY KEY (`session_uid`),
  KEY `employer_id` (`employer_id`,`login_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='История аутентификации пользователей' AUTO_INCREMENT=339 ;

-- --------------------------------------------------------

--
-- Структура таблицы `employer_certs`
--

CREATE TABLE IF NOT EXISTS `employer_certs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `employer_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор пользователя',
  `is_lock` int(1) unsigned NOT NULL COMMENT 'Признак, что данный сертификат заблокирован, вход по нему невозможен',
  `SSL_CERT_HASH` char(64) NOT NULL COMMENT 'SHA1 хеш сертификата',
  `SSL_CLIENT_M_SERIAL` char(64) NOT NULL COMMENT 'Серийный номер сертификата',
  `SSL_CLIENT_S_DN_L` char(255) NOT NULL COMMENT 'Локация согласно данным сертификата',
  `SSL_CLIENT_S_DN_O` char(255) NOT NULL COMMENT 'Организация',
  `SSL_CLIENT_S_DN_OU` char(255) NOT NULL COMMENT 'Организационная единица',
  `SSL_CLIENT_S_DN_CN` char(255) NOT NULL COMMENT 'Комы выдан сертификат',
  `SSL_CLIENT_CERT` varchar(8192) NOT NULL COMMENT 'Сертификат клиента',
  PRIMARY KEY (`id`),
  KEY `employer_id` (`employer_id`,`SSL_CERT_HASH`,`SSL_CLIENT_M_SERIAL`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Перечень сертификатов пользователей' AUTO_INCREMENT=12 ;

-- --------------------------------------------------------

--
-- Структура таблицы `employer_groups`
--

CREATE TABLE IF NOT EXISTS `employer_groups` (
  `group_id` int(11) NOT NULL COMMENT 'Идентификатор группы',
  `employer_id` int(11) NOT NULL COMMENT 'Идентификатор пользователя',
  PRIMARY KEY (`group_id`,`employer_id`),
  KEY `id` (`group_id`,`employer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251 COMMENT='Перечень групп пользователей';

-- --------------------------------------------------------

--
-- Структура таблицы `employer_posts`
--

CREATE TABLE IF NOT EXISTS `employer_posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `employer_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор пользователя',
  `post_uid` bigint(20) unsigned NOT NULL COMMENT 'Идентификатор должности в организации',
  `post_from` date NOT NULL COMMENT 'Дата вступления в должность',
  `post_to` date NOT NULL COMMENT 'Дата окончания работы на должности',
  PRIMARY KEY (`id`),
  KEY `employer_id` (`employer_id`,`post_uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Перечень должностей, занимаемых пользователями' AUTO_INCREMENT=58 ;

-- --------------------------------------------------------

--
-- Структура таблицы `employer_rights`
--

CREATE TABLE IF NOT EXISTS `employer_rights` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employer_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор пользователя',
  `company_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор организации',
  `can_add_employer` int(1) unsigned NOT NULL COMMENT 'Признак, что в указанной организации сотрудник может заводить нового сотрудника',
  `can_curator` int(1) unsigned NOT NULL COMMENT 'Признак, что сотрудник может быть куратором для сотрудников указанной организации',
  PRIMARY KEY (`id`),
  KEY `employer_id` (`employer_id`),
  KEY `company_id` (`company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Перечень прав сотрудников в организациях' AUTO_INCREMENT=24 ;

-- --------------------------------------------------------

--
-- Структура таблицы `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор группы',
  `code` char(16) NOT NULL COMMENT 'Код группы',
  `short_name` char(128) NOT NULL COMMENT 'Сокрещенное наименование (для печатной формы)',
  `full_name` char(255) NOT NULL COMMENT 'Полное наименование (для WEB-интерфейса)',
  PRIMARY KEY (`group_id`),
  KEY `code` (`code`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Перечень групп' AUTO_INCREMENT=9 ;

-- --------------------------------------------------------

--
-- Структура таблицы `iresources`
--

CREATE TABLE IF NOT EXISTS `iresources` (
  `iresource_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор информационного ресурса',
  `company_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор организации, за которой закреплен ресурс (владелец ресурса), если 0 - не определен',
  `post_uid` bigint(20) unsigned NOT NULL COMMENT 'Идентификатор владельца ресурса (0 - владелец не определен), берется из поля uid таблицы company_posts',
  `is_lock` int(1) unsigned NOT NULL COMMENT 'Признак блокировки объекта, 1 - не отображать объект нигде в интерфейсе, кроме соответствующего списка',
  `short_name` char(128) NOT NULL COMMENT 'Сокрещенное наименование (для печатной формы)',
  `full_name` char(255) NOT NULL COMMENT 'Полное наименование (для WEB-интерфейса)',
  `description` char(255) NOT NULL COMMENT 'Описание ресурса (вспомогательная информация)',
  `location` char(255) NOT NULL COMMENT 'Место расположения ресурса (вспомогательная информация)',
  `worker_group` int(10) unsigned NOT NULL COMMENT 'Идентификатор группы пользователей, отвечающих за исполнение заявки',
  `iresource_group` int(10) unsigned NOT NULL COMMENT 'Идентификатор группы информационных ресурсов igroup_id из таблицы iresources_groups',
  `techinfo` char(255) NOT NULL COMMENT 'Техническая информация по информационному ресурсу',
  PRIMARY KEY (`iresource_id`),
  KEY `company_id` (`company_id`,`post_uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Перечень информационных ресурсов' AUTO_INCREMENT=30 ;

-- --------------------------------------------------------

--
-- Структура таблицы `iresource_companies`
--

CREATE TABLE IF NOT EXISTS `iresource_companies` (
  `iresource_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор информационного ресурса',
  `company_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор организации',
  PRIMARY KEY (`iresource_id`,`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251 COMMENT='Перечень огранизаций, для которых доступны информационные ре';

-- --------------------------------------------------------

--
-- Структура таблицы `iresource_groups`
--

CREATE TABLE IF NOT EXISTS `iresource_groups` (
  `igroup_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор группы',
  `short_name` char(128) NOT NULL COMMENT 'Сокрещенное наименование (для печатной формы)',
  `full_name` char(255) NOT NULL COMMENT 'Полное наименование (для WEB-интерфейса)',
  PRIMARY KEY (`igroup_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Перечень групп информационных ресурсов' AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Структура таблицы `iroles`
--

CREATE TABLE IF NOT EXISTS `iroles` (
  `irole_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор привелегии доступа',
  `iresource_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор информационного ресурса',
  `owner_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор вышестоящей привелегии доступа (вышестоящий раздел, контейнер и т.д. -для построения иерархии), если 0 - корневой элемент',
  `short_name` char(128) NOT NULL COMMENT 'Сокращенное наименование (для печатной формы)',
  `full_name` char(255) NOT NULL COMMENT 'Полное наименование (для WEB-интерфейса)',
  `description` char(255) NOT NULL COMMENT 'Описание доступа (вспомогательная информация)',
  `is_lock` int(1) unsigned NOT NULL COMMENT 'Признак блокировки объекта, 1 - не отображать объект нигде в интерфейсе, кроме соответствующего списка',
  `is_area` int(1) unsigned NOT NULL COMMENT 'Признак, указывающий что данный объект является разделом',
  `ir_types` char(255) NOT NULL COMMENT 'Перечень типов доступа из таблицы ir_types, разделяются символом |',
  `screenshot` char(255) NOT NULL COMMENT 'Имя файла скриншота для объекта доступа',
  `weight` int(10) unsigned NOT NULL COMMENT 'Важность объекта доступа от 0 до 10',
  PRIMARY KEY (`irole_id`),
  KEY `iresource_id` (`iresource_id`,`owner_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Перечень привелегий доступа каждого конкретного информационн' AUTO_INCREMENT=1210 ;

-- --------------------------------------------------------

--
-- Структура таблицы `ir_items`
--

CREATE TABLE IF NOT EXISTS `ir_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `irole_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор привелегии доступа',
  `item_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор элемента списка',
  PRIMARY KEY (`id`),
  KEY `irole_id` (`irole_id`,`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251 COMMENT='Перечень пунктов для каждой привелегии доступа' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `ir_types`
--

CREATE TABLE IF NOT EXISTS `ir_types` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор элемента списка',
  `short_name` char(128) NOT NULL COMMENT 'Сокрещенное наименование (для печатной формы)',
  `full_name` char(255) NOT NULL COMMENT 'Полное наименование (для WEB-интерфейса)',
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Перечень пунктов в списке выбора типа доступа' AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Структура таблицы `ldap_users`
--

CREATE TABLE IF NOT EXISTS `ldap_users` (
  `username` char(255) NOT NULL,
  `displayname` char(255) NOT NULL,
  `mail` char(255) NOT NULL,
  `company` char(255) NOT NULL,
  `department` char(255) NOT NULL,
  `title` char(255) NOT NULL,
  `telephone` char(255) NOT NULL,
  `active` int(11) NOT NULL,
  `expires` bigint(20) NOT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251 COMMENT='Таблица пользователей AD, для разработки';

-- --------------------------------------------------------

--
-- Структура таблицы `mail`
--

CREATE TABLE IF NOT EXISTS `mail` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `timestamp` datetime NOT NULL COMMENT 'Временной штамп',
  `mail_to` varchar(255) NOT NULL COMMENT 'e-mail получателя',
  `subject` varchar(255) NOT NULL COMMENT 'Тема письма',
  `headers` text NOT NULL COMMENT 'Заголовки письма',
  `content` mediumtext NOT NULL COMMENT 'Контент письма',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Готовые для отправки сообщения' AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Структура таблицы `menu_map`
--

CREATE TABLE IF NOT EXISTS `menu_map` (
  `item_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор объекта меню',
  `parent_id` int(10) unsigned NOT NULL COMMENT 'Родительский объект',
  `menu_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор меню, которому принадлежит объект',
  `access_object_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор объекта доступа, привязанный к данному элементу, если 0 - отображается всегда',
  `is_lock` int(1) unsigned NOT NULL COMMENT 'Признак того, что элемент заблокирован',
  `is_folder` int(1) unsigned NOT NULL COMMENT 'Признак, что элемент является разделом меню',
  `href` char(255) NOT NULL COMMENT 'URL ссылка',
  `target` char(32) NOT NULL COMMENT 'Как открывать ссылку (текущее окно, новое окно)',
  `title` char(255) NOT NULL COMMENT 'Заголовок',
  `desc` char(255) NOT NULL COMMENT 'Описание',
  `class` char(255) NOT NULL COMMENT 'CSS класс',
  PRIMARY KEY (`item_id`),
  KEY `parent_id` (`parent_id`,`menu_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Таблица навигационного меню' AUTO_INCREMENT=38 ;

-- --------------------------------------------------------

--
-- Структура таблицы `msg_pool`
--

CREATE TABLE IF NOT EXISTS `msg_pool` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `type` int(10) unsigned NOT NULL COMMENT 'Тип сообщения: 0-undefined,1-notice,2-approve,3-decline,4-complete',
  `timestamp` datetime NOT NULL COMMENT 'Время сообщения',
  `request_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор запроса',
  `iresource_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор информационного ресурса',
  `route_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор марштура',
  `step_uid` char(40) NOT NULL COMMENT 'Идентификатор шага в маршруте',
  `gatekeeper_type` int(10) unsigned NOT NULL COMMENT 'Тип гейткипера',
  `gatekeeper_role` int(10) unsigned NOT NULL COMMENT 'Роль гейткипера',
  `gatekeeper_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор гейткипера, согласовавшего заявку',
  `gatekeepers` char(255) NOT NULL COMMENT 'Список гейткиперов, участвующих на текущем шаге согласования',
  `assistants` char(255) NOT NULL COMMENT 'Список ассистентов, участвующих на текущем шаге согласования',
  `send_employer` int(1) unsigned NOT NULL COMMENT 'Разрешение направить сообщение заявителю',
  `send_curator` int(1) unsigned NOT NULL COMMENT 'Разрешение направить сообщение куратору',
  `send_gatekeepers` int(1) unsigned NOT NULL COMMENT 'Разрешение направить сообщение гейткиперам',
  `send_assistants` int(1) unsigned NOT NULL COMMENT 'Разрешение направить сообщение ассистентам гейткиперов',
  PRIMARY KEY (`id`),
  KEY `type` (`type`,`timestamp`,`request_id`,`iresource_id`,`step_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251 COMMENT='Таблица пула сообщений для рассылки сотрудникам' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `posts`
--

CREATE TABLE IF NOT EXISTS `posts` (
  `post_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор должности',
  `code` char(16) NOT NULL COMMENT 'Код должности',
  `short_name` char(128) NOT NULL COMMENT 'Сокрещенное наименование (для печатной формы)',
  `full_name` char(255) NOT NULL COMMENT 'Полное наименование (для WEB-интерфейса)',
  PRIMARY KEY (`post_id`),
  KEY `code` (`code`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Перечень должностей' AUTO_INCREMENT=194 ;

-- --------------------------------------------------------

--
-- Структура таблицы `protocol_actions`
--

CREATE TABLE IF NOT EXISTS `protocol_actions` (
  `action_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `action_name` char(128) NOT NULL COMMENT 'Имя действия',
  `employer_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор пользователя',
  `session_uid` int(10) unsigned NOT NULL COMMENT 'Идентификатор сессии пользователя, в рамках которой было выполнено действие',
  `timestamp` datetime NOT NULL COMMENT 'Дата и время действия',
  `company_id` int(10) unsigned NOT NULL COMMENT 'ID организации в рамках которой выполняется действие',
  `acl_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор ACL объекта, в рамках которого выполняется действие',
  `acl_name` char(128) NOT NULL COMMENT 'Имя ACL объекта, в рамках которого выполняется действие',
  `primary_type` char(32) NOT NULL COMMENT 'Тип основного объекта, над которым выполняется действие',
  `primary_id` bigint(20) unsigned NOT NULL COMMENT 'Идентификатор основного объекта, над которым выполняется действие',
  `secondary_type` char(32) NOT NULL COMMENT 'Тип дополнительного объекта, над которым выполняется действие',
  `secondary_id` bigint(20) unsigned NOT NULL COMMENT 'Идентификатор дополнительного объекта, над которым выполняется действие',
  `description` char(255) NOT NULL COMMENT 'Описание действия',
  PRIMARY KEY (`action_id`),
  KEY `employer_id` (`employer_id`),
  KEY `session_uid` (`session_uid`),
  KEY `timestamp` (`timestamp`),
  KEY `company_id` (`company_id`),
  KEY `acl_id` (`acl_id`),
  KEY `primary_type` (`primary_type`),
  KEY `primary_id` (`primary_id`),
  KEY `secondary_type` (`secondary_type`),
  KEY `secondary_id` (`secondary_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Таблица протоколирования действий пользователей' AUTO_INCREMENT=994 ;

-- --------------------------------------------------------

--
-- Структура таблицы `protocol_values`
--

CREATE TABLE IF NOT EXISTS `protocol_values` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `action_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор действия',
  `value` varchar(32768) NOT NULL COMMENT 'Изменяемые значения',
  PRIMARY KEY (`id`),
  KEY `action_id` (`action_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Таблица значений действий пользователей' AUTO_INCREMENT=821 ;

-- --------------------------------------------------------

--
-- Структура таблицы `requests`
--

CREATE TABLE IF NOT EXISTS `requests` (
  `request_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор запроса',
  `request_type` int(10) unsigned NOT NULL COMMENT 'Тип заявки (1 - новый пользователь, 2 - запрос доступа)',
  `curator_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор пользователя, который оформил заявку',
  `employer_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор пользователя, для которого была оформлена заявка (если 0 - новый пользователь)',
  `company_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор организации, в которой работает пользователь, на которого оформлена заявка',
  `post_uid` bigint(20) unsigned NOT NULL COMMENT 'Идентификатор должности пользователя в организации, для которого запрошена заявка',
  `template_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор шаблона доступа, на основании которого сформирована заявка',
  `timestamp` datetime NOT NULL COMMENT 'Время создания записи',
  `phone` char(64) NOT NULL COMMENT 'Контактный телефон',
  `email` char(64) NOT NULL COMMENT 'Контактный email',
  PRIMARY KEY (`request_id`),
  KEY `route_id` (`template_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Таблица заявок' AUTO_INCREMENT=24 ;

-- --------------------------------------------------------

--
-- Структура таблицы `request_anket`
--

CREATE TABLE IF NOT EXISTS `request_anket` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `request_id` int(11) NOT NULL COMMENT 'Идентификатор запроса',
  `first_name` char(32) NOT NULL COMMENT 'Имя',
  `last_name` char(32) NOT NULL COMMENT 'Фамилия',
  `middle_name` char(32) NOT NULL COMMENT 'Отчество',
  `birth_date` date NOT NULL COMMENT 'Дата рождения',
  `phone` char(64) NOT NULL COMMENT 'Контактный телефон',
  `email` char(64) NOT NULL COMMENT 'Контактный email',
  `company_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор организации, в которой будет работать новый сотрудник',
  `cp_id` bigint(20) unsigned NOT NULL COMMENT 'Идентификатор должности сотрудника',
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251 COMMENT='Анкета пользователя для заявки (для новых пользователей)' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `request_comments`
--

CREATE TABLE IF NOT EXISTS `request_comments` (
  `comment_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `request_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор запроса',
  `iresource_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор информационного ресурса',
  `employer_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор пользователя оставившего сообщение',
  `comment` varchar(8192) NOT NULL COMMENT 'Текст комментария',
  `timestamp` datetime NOT NULL COMMENT 'Время добавления комментария',
  PRIMARY KEY (`comment_id`),
  KEY `request_id` (`request_id`,`timestamp`),
  KEY `iresource_id` (`iresource_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Таблица комментариев к заявке' AUTO_INCREMENT=18 ;

-- --------------------------------------------------------

--
-- Структура таблицы `request_iresources`
--

CREATE TABLE IF NOT EXISTS `request_iresources` (
  `rires_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `request_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор запроса',
  `iresource_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор информационного ресурса',
  `route_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор маршрута',
  `route_status` int(10) unsigned NOT NULL COMMENT 'Статус процесса согласования заявки (0 - отменена, 1 - в работе, 2 - пауза, 100 - успешно выполнена)',
  `route_status_desc` char(255) NOT NULL COMMENT 'Примечание к статусу маршрута',
  `current_step` int(10) unsigned NOT NULL COMMENT 'Текущий шаг согласования, на котором находится заявка, rstep_id из таблицы request_steps',
  PRIMARY KEY (`rires_id`),
  KEY `request_id` (`request_id`),
  KEY `iresource_id` (`iresource_id`),
  KEY `route_status` (`route_status`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Таблица информационных ресурсов запрошенных в заявке' AUTO_INCREMENT=21 ;

-- --------------------------------------------------------

--
-- Структура таблицы `request_iresources_hist`
--

CREATE TABLE IF NOT EXISTS `request_iresources_hist` (
  `rires_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор',
  `request_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор запроса',
  `iresource_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор информационного ресурса',
  `route_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор маршрута',
  `route_status` int(10) unsigned NOT NULL COMMENT 'Статус процесса согласования заявки (0 - отменена, 1 - в работе, 2 - пауза, 100 - успешно выполнена)',
  `route_status_desc` char(255) NOT NULL COMMENT 'Примечание к статусу маршрута',
  `current_step` int(10) unsigned NOT NULL COMMENT 'Текущий шаг согласования, на котором находится заявка, rstep_id из таблицы request_steps',
  PRIMARY KEY (`rires_id`),
  KEY `request_id` (`request_id`),
  KEY `iresource_id` (`iresource_id`),
  KEY `route_status` (`route_status`),
  KEY `route_id` (`route_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251 COMMENT='Таблица информационных ресурсов запрошенных в заявке, истори';

-- --------------------------------------------------------

--
-- Структура таблицы `request_roles_0`
--

CREATE TABLE IF NOT EXISTS `request_roles_0` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` int(10) unsigned NOT NULL,
  `iresource_id` int(10) unsigned NOT NULL,
  `irole_id` int(10) unsigned NOT NULL,
  `ir_type` int(10) unsigned NOT NULL,
  `ir_selected` int(10) unsigned NOT NULL,
  `gatekeeper_id` int(10) unsigned NOT NULL,
  `update_type` int(10) unsigned NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `iresource_id` (`iresource_id`),
  KEY `request_id` (`request_id`),
  KEY `irole_id` (`irole_id`),
  KEY `ir_selected` (`ir_selected`),
  KEY `ir_type` (`ir_type`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Request roles table' AUTO_INCREMENT=85 ;

-- --------------------------------------------------------

--
-- Структура таблицы `request_steps`
--

CREATE TABLE IF NOT EXISTS `request_steps` (
  `rstep_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `request_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор запроса',
  `iresource_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор информационного ресурса',
  `route_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор маршрута',
  `step_uid` char(40) NOT NULL COMMENT 'Идентификатор шага согласования',
  `gatekeeper_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор гейткипера через которого проходит маршрут на данном шаге',
  `assistant_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор лица, замещающего гейткипера (0 - не используется)',
  `step_complete` int(1) unsigned NOT NULL COMMENT 'Признак, указывающий что текущий шаг был одобрен',
  `is_approved` int(1) unsigned NOT NULL COMMENT 'Признак, указывающий что заявка на данном шаге была одобрена гейткипером, в противном случае считается что заявка была отклонена',
  `timestamp` datetime NOT NULL COMMENT 'Время одобрения записи',
  PRIMARY KEY (`rstep_id`),
  KEY `request_id` (`request_id`,`route_id`),
  KEY `iresource_id` (`iresource_id`),
  KEY `route_id` (`route_id`),
  KEY `step_uid` (`step_uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Таблица заявок' AUTO_INCREMENT=63 ;

-- --------------------------------------------------------

--
-- Структура таблицы `request_steps_hist`
--

CREATE TABLE IF NOT EXISTS `request_steps_hist` (
  `rstep_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор',
  `request_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор запроса',
  `iresource_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор информационного ресурса',
  `route_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор маршрута',
  `step_uid` char(40) NOT NULL COMMENT 'Идентификатор шага согласования',
  `gatekeeper_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор гейткипера через которого проходит маршрут на данном шаге',
  `assistant_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор лица, замещающего гейткипера (0 - не используется)',
  `step_complete` int(1) unsigned NOT NULL COMMENT 'Признак, указывающий что текущий шаг был одобрен',
  `is_approved` int(1) unsigned NOT NULL COMMENT 'Признак, указывающий что заявка на данном шаге была одобрена гейткипером, в противном случае считается что заявка была отклонена',
  `timestamp` datetime NOT NULL COMMENT 'Время одобрения записи',
  PRIMARY KEY (`rstep_id`),
  KEY `request_id` (`request_id`,`route_id`),
  KEY `iresource_id` (`iresource_id`),
  KEY `route_id` (`route_id`),
  KEY `step_uid` (`step_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251 COMMENT='Таблица этапов согласования заявки по каждому информационном';

-- --------------------------------------------------------

--
-- Структура таблицы `request_watch`
--

CREATE TABLE IF NOT EXISTS `request_watch` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `request_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор заявки',
  `iresource_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор информационного ресурса',
  `employer_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор сотрудника',
  `is_watched` int(1) unsigned NOT NULL COMMENT 'Признак, указывающий что сотрудник просмотрел данную заявку',
  `is_owner` int(1) unsigned NOT NULL COMMENT 'Признак, что сотрудник является заявителем по данной заявке',
  `is_curator` int(1) unsigned NOT NULL COMMENT 'Признак, что данная заявка была создана этим сотрудником для заявителя',
  `is_gatekeeper` int(1) unsigned NOT NULL COMMENT 'Признак, что сотрудник является гейткипером по маршруту заявки',
  `is_performer` int(1) unsigned NOT NULL COMMENT 'Признак, что сотрудник является исполнителем по маршруту заявки',
  `is_watcher` int(1) unsigned NOT NULL COMMENT 'Признак, что согласно маршруту данный сотрудник должен быть ознакомлен с заявкой',
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`,`iresource_id`,`employer_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Таблица сопоставления сотрудников и заявок, какие сотрудники' AUTO_INCREMENT=70 ;

-- --------------------------------------------------------

--
-- Структура таблицы `routes`
--

CREATE TABLE IF NOT EXISTS `routes` (
  `route_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор маршрута',
  `route_type` int(10) unsigned NOT NULL COMMENT 'Тип маршрута (1 - для заявок, 2 - для шаблонов должностей, 3 - для новых сотрудников)',
  `full_name` char(255) NOT NULL COMMENT 'Полное наименование (для WEB-интерфейса)',
  `description` char(255) NOT NULL COMMENT 'Описание (вспомогательная информация)',
  `is_lock` int(1) unsigned NOT NULL COMMENT 'Признак блокировки, 1 - не отображать нигде в интерфейсе, кроме соответствующего списка',
  `is_default` int(1) unsigned NOT NULL COMMENT 'Признак маршрута, используемого по-умолчанию, если оптимальный маршрут не найден',
  `priority` int(10) unsigned NOT NULL COMMENT 'Приоритет выбора данного маршрута по сравнению с другими маршрутами, удовлетворяющими условию',
  PRIMARY KEY (`route_id`),
  KEY `route_type` (`route_type`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Перечень маршрутов согласования' AUTO_INCREMENT=11 ;

-- --------------------------------------------------------

--
-- Структура таблицы `route_params`
--

CREATE TABLE IF NOT EXISTS `route_params` (
  `param_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `route_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор маршрута',
  `for_employer` int(10) unsigned NOT NULL COMMENT 'Идентификатор пользователя (0 - не используется)',
  `for_company` int(10) unsigned NOT NULL COMMENT 'Идентификатор организации (0 - не используется)',
  `for_post` bigint(20) unsigned NOT NULL COMMENT 'Идентификатор должности в организации (0 - не используется)',
  `for_group` int(10) unsigned NOT NULL COMMENT 'Идентификатор группы пользователей (0 - не используется)',
  `for_resource` int(10) unsigned NOT NULL COMMENT 'Идентификатор ресурса (0 - не используется)',
  PRIMARY KEY (`param_id`),
  KEY `route_id` (`route_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Перечень параметров, при которых применим данный маршрут' AUTO_INCREMENT=19 ;

-- --------------------------------------------------------

--
-- Структура таблицы `route_steps`
--

CREATE TABLE IF NOT EXISTS `route_steps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `route_id` int(10) unsigned NOT NULL COMMENT 'Идентификатор маршрута',
  `step_uid` char(40) NOT NULL COMMENT 'UID шага, вычисляется как: ID маршрута + Тип гейткипера + Роль гейткипера + ID гейткипера',
  `step_type` int(10) unsigned NOT NULL COMMENT 'Тип шага (1 - начало маршрута, 2 - гейткипер, 3 - конец маршрута ИСПОЛНЕНО, 4 - конец маршрута ОТКЛОНЕНО.)',
  `pos_x` int(11) NOT NULL COMMENT 'Положение объекта в диаграмме по X',
  `pos_y` int(11) NOT NULL COMMENT 'Положение объекта в диаграмме по Y',
  `gatekeeper_type` int(10) unsigned NOT NULL COMMENT 'Тип гейткипера (1 - конкретный пользователь (user_id), 2 - руководитель сотрудника (boss_id), 3 - руководитель организации (company_id), 4 - владелец ресурса (resource_id), 5 - группа пользователей (group_id))',
  `gatekeeper_id` bigint(20) unsigned NOT NULL COMMENT 'Идентификатор гейткипера через которого проходит маршрут на данном шаге',
  `gatekeeper_role` int(10) unsigned NOT NULL COMMENT 'Роль гейткипера в маршруте (1 - согласование, 2 - утверждение, 3 - исполнение)',
  `step_yes` char(40) NOT NULL COMMENT 'Шаг, на который переходит документ при положительном прохождении текущего шага (0 - маршрут завершен, успех)',
  `step_no` char(40) NOT NULL COMMENT 'Шаг, на который переходит документ при отрицательном прохождении текущего шага (0 - маршрут завершен, возврат на шаг 1)',
  PRIMARY KEY (`id`),
  KEY `route_id` (`route_id`),
  KEY `step_uid` (`step_uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=cp1251 COMMENT='Перечень шагов для каждого маршрута согласования' AUTO_INCREMENT=302 ;

-- --------------------------------------------------------

--
-- Структура таблицы `sys_cron`
--

CREATE TABLE IF NOT EXISTS `sys_cron` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID задачи',
  `status` int(11) NOT NULL COMMENT 'Статус запуска',
  `script` char(255) NOT NULL COMMENT 'Файл скрипта',
  `description` char(255) NOT NULL COMMENT 'Описание задачи',
  `exec_model` int(11) NOT NULL COMMENT 'Модель запуска скрипта (1 - в определенный интервал в течении суток с заданным таймаутом, 2 - в определенное время раз в сутки)',
  `exec_from` int(11) NOT NULL COMMENT 'Запускать начиная с указанного времени (сек с начала суток)',
  `exec_to` int(11) NOT NULL COMMENT 'Запускать до указанного времени (сек с начала суток)',
  `exec_timeout` int(11) NOT NULL COMMENT 'Таймаут между запусками (сек)',
  `exec_time` int(11) NOT NULL COMMENT 'Время запуска скрипта (сек с начала суток)',
  `can_overlay` int(11) NOT NULL COMMENT 'Признак возможности наложения',
  `last_exec` int(11) NOT NULL COMMENT 'Время последнего запуска (сек)',
  `max_execute` int(11) NOT NULL COMMENT 'Максимально допустимое время работы экземпляра скрипта (сек)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251 COMMENT='Менеджер задач' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `templates`
--

CREATE TABLE IF NOT EXISTS `templates` (
  `template_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор шаблона',
  `full_name` char(255) NOT NULL COMMENT 'Полное наименование (для WEB-интерфейса)',
  `description` char(255) NOT NULL COMMENT 'Описание шаблона доступа',
  `company_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Идентификатор организации, к которой применим данный шаблон, если 0 - не определен (применимо ко всем организациям)',
  `post_uid` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'Идентификатор должности в организации, если 0 - не определен (применимо ко всем должностям)',
  `is_lock` int(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Признак блокировки объекта, 1 - не отображать объект нигде в интерфейсе, кроме соответствующего списка',
  `is_for_new_employer` int(1) unsigned NOT NULL DEFAULT '0' COMMENT 'Признак того, что шаблон применим для нового сотрудника',
  PRIMARY KEY (`template_id`),
  KEY `code` (`company_id`,`post_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251 COMMENT='Перечень типовых шаблонов ролей для должностей' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `tmpl_roles`
--

CREATE TABLE IF NOT EXISTS `tmpl_roles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор',
  `template_id` int(11) unsigned NOT NULL COMMENT 'Идентификатор шаблона',
  `iresource_id` int(11) unsigned NOT NULL COMMENT 'Идентификатор информационного ресурса',
  `irole_id` int(11) unsigned NOT NULL COMMENT 'Идентификатор привелегии доступа',
  `ir_type` int(10) unsigned NOT NULL COMMENT 'Идентификатор типа доступа',
  PRIMARY KEY (`id`),
  KEY `template_id` (`template_id`,`iresource_id`,`irole_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251 COMMENT='Перечень пунктов, включенных в шаблон доступа' AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
