-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db:3306
-- Generation Time: Nov 19, 2025 at 03:58 PM
-- Server version: 9.5.0
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `iis_winery`
--

CREATE TABLE USER (
    ID INTEGER AUTO_INCREMENT,
    NAME VARCHAR(30) NOT NULL,
    ACTIVE BOOLEAN NOT NULL DEFAULT(1),
    SURNAME VARCHAR(30) NOT NULL,
    USERNAME VARCHAR(30) NOT NULL UNIQUE,
    EMAIL VARCHAR(255) NOT NULL UNIQUE,
    PSWD_HASH VARCHAR(256) NOT NULL UNIQUE,
    PSWD_SALT VARCHAR(256) NOT NULL UNIQUE,
    PHONE_NUMBER VARCHAR(9),
    CITY VARCHAR(30),
    POST_CODE VARCHAR(5),
    STREET VARCHAR(30),
    HOUSE_NUMBER VARCHAR(10),
    REGISTRATION_TIMESTAMP TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ROLE_USER BOOLEAN NOT NULL DEFAULT(0),
    ROLE_WORKER BOOLEAN NOT NULL DEFAULT(0),
    ROLE_VINTNER BOOLEAN NOT NULL DEFAULT(0),
    ROLE_ADMIN BOOLEAN NOT NULL DEFAULT(0),
    CONSTRAINT PK_USER PRIMARY KEY (ID),
    CONSTRAINT CHECK_USER_PHONE_NUMBER
        CHECK (
            PHONE_NUMBER IS NULL OR REGEXP_LIKE(PHONE_NUMBER, '^[0-9]{9}$')
        ),
    CONSTRAINT CHECK_USER_EMAIL
        CHECK (
            REGEXP_LIKE(EMAIL, '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$')
        ),
    CONSTRAINT CHECK_USER_POST_CODE
        CHECK (
            POST_CODE IS NULL OR REGEXP_LIKE(POST_CODE, '^[0-9]{5}$')
        )
)
CHARACTER SET utf8mb4
COLLATE utf8mb4_czech_ci;

CREATE TABLE TASK (
    ID INTEGER AUTO_INCREMENT,
    NAME VARCHAR(30) NOT NULL,
    STATE ENUM('PLANNED', 'FINISHED', 'CANCELED') NOT NULL DEFAULT('PLANNED'),
    CREATION_TIMESTAMP TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    LAST_CHANGE_TIMESTAMP TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PLANNED_EXECUTION_TIMESTAMP TIMESTAMP,
    EXECUTION_TIMESTAMP TIMESTAMP,
    NOTE TEXT,
    ID_TASK_ISSUER INTEGER,
    ID_TASK_CONFIRMER INTEGER,
    CONSTRAINT PK_TASK PRIMARY KEY (ID),
    CONSTRAINT
        FK_ID_TASK_ISSUER
        FOREIGN KEY (ID_TASK_ISSUER)
        REFERENCES USER (ID)
        ON DELETE SET NULL,
    CONSTRAINT
        FK_ID_TASK_CONFIRMER
        FOREIGN KEY (ID_TASK_CONFIRMER)
        REFERENCES USER (ID)
        ON DELETE SET NULL,
    CONSTRAINT CHECK_LAST_CHANGE_TIMESTAMP
        CHECK (
            LAST_CHANGE_TIMESTAMP >= CREATION_TIMESTAMP
        ),
    CONSTRAINT CHECK_EXECUTION_TIMESTAMP
        CHECK (
            (EXECUTION_TIMESTAMP IS NULL AND STATE IN ('PLANNED', 'CANCELED'))
            OR 
            EXECUTION_TIMESTAMP IS NOT NULL
        )
)
CHARACTER SET utf8mb4
COLLATE utf8mb4_czech_ci;

CREATE TABLE PICTURE (
    ID INTEGER AUTO_INCREMENT,
    IMAGE_NAME VARCHAR(30) NOT NULL,
    LINK TEXT NOT NULL,
    DESCRIPTION TEXT NOT NULL,
    CONSTRAINT PK_PICTURE PRIMARY KEY (ID)
)
CHARACTER SET utf8mb4
COLLATE utf8mb4_czech_ci;

CREATE TABLE WINE (
    ID INTEGER AUTO_INCREMENT,
    WINE_NAME VARCHAR(30) NOT NULL,
    ID_LIQUIDATOR INTEGER,
    ID_SELL_APPROVER INTEGER,
    ID_PICTURE INTEGER,
    YEAR SMALLINT NOT NULL,
    ALCOHOL_PERCENTAGE DECIMAL(5,2) NOT NULL DEFAULT(13.0),
    TYPE ENUM('DRY', 'HALF_DRY', 'HALF_SWEET', 'SWEET') NOT NULL DEFAULT('DRY'),
    COLOR ENUM('WHITE', 'RED', 'ROSE') NOT NULL DEFAULT('WHITE'),
    GRADE ENUM('NONE', 'CABINET', 'LATE', 'GRAPE_SELECTION', 'BERRY_SELECTION', 'ICE', 'STRAW') NOT NULL DEFAULT('NONE'),
    STATE ENUM('PRODUCTION', 'LIQUIDATED', 'BOTTLED', 'SALE', 'SOLD') NOT NULL DEFAULT ('PRODUCTION'),
    VOLUME SMALLINT NOT NULL,
    BOTTLE_PRICE DECIMAL(10,2),
    CURRENT_PRICE DECIMAL(10,2),
    BOTTLES_LEFT SMALLINT,
    LEFT_SUGAR DECIMAL(5,2),
    ACID_CONTENTS DECIMAL(5,2),
    SALES_DESCRIPTION TEXT,
    PRODUCTION_NOTE TEXT,
    CONSTRAINT PK_WINE PRIMARY KEY (ID),
    CONSTRAINT
        FK_ID_LIQUIDATOR
        FOREIGN KEY (ID_LIQUIDATOR)
        REFERENCES USER (ID)
        ON DELETE SET NULL,
    CONSTRAINT
        FK_ID_SELL_APPROVER
        FOREIGN KEY (ID_SELL_APPROVER)
        REFERENCES USER (ID)
        ON DELETE SET NULL,
    CONSTRAINT
        FK_ID_PICTURE
        FOREIGN KEY (ID_PICTURE)
        REFERENCES PICTURE (ID)
        ON DELETE SET NULL,
    CONSTRAINT CHECK_ALCOHOL_PERCENTAGE
        CHECK (ALCOHOL_PERCENTAGE >= 0.0 AND ALCOHOL_PERCENTAGE <= 100.0),
    CONSTRAINT CHECK_VOLUME
        CHECK (VOLUME > 0)
)
CHARACTER SET utf8mb4
COLLATE utf8mb4_czech_ci;

CREATE TABLE VINEYARD(
    ID INTEGER AUTO_INCREMENT,
    ACTIVE BOOLEAN NOT NULL DEFAULT(1),
    ID_PICTURE INTEGER,
    NAME VARCHAR(30) NOT NULL UNIQUE,
    LOCATION TEXT NOT NULL,
    DESCRIPTION TEXT,
    YEAR SMALLINT NOT NULL,
    CONSTRAINT PK_VINEYARD PRIMARY KEY (ID),
    CONSTRAINT
        FK_ID_VINEYARD_PICTURE
        FOREIGN KEY (ID_PICTURE)
        REFERENCES PICTURE (ID)
        ON DELETE SET NULL
)
CHARACTER SET utf8mb4
COLLATE utf8mb4_czech_ci;

CREATE TABLE WINE_VARIETY(
    ID INTEGER AUTO_INCREMENT,
    ID_PICTURE INTEGER,
    NAME VARCHAR(20) NOT NULL UNIQUE,
    COLOR ENUM('WHITE', 'RED', 'PINK', 'GREY', 'BLUE') NOT NULL DEFAULT('WHITE'),
    DESCRIPTION TEXT NOT NULL,
    CONSTRAINT PK_WINE_VARIETY PRIMARY KEY (ID),
    CONSTRAINT
        FK_ID_WINE_VARIETY_PICTURE
        FOREIGN KEY (ID_PICTURE)
        REFERENCES PICTURE (ID)
        ON DELETE SET NULL
)
CHARACTER SET utf8mb4
COLLATE utf8mb4_czech_ci;

CREATE TABLE WINE_ROW (
    CODE INTEGER,
    ACTIVE BOOLEAN NOT NULL DEFAULT(1),
    ID_VINEYARD INTEGER,
    ID_WINE_VARIETY INTEGER,
    HEAD_COUNT SMALLINT NOT NULL,
    PLANTING_YEAR SMALLINT NOT NULL,
    CONSTRAINT PK_WINE_ROW PRIMARY KEY (ID_VINEYARD, CODE),
    CONSTRAINT FK_WINE_ROW_VINEYARD
        FOREIGN KEY (ID_VINEYARD)
        REFERENCES VINEYARD (ID)
        ON DELETE CASCADE,

    CONSTRAINT FK_WINE_ROW_WINE_VARIETY
        FOREIGN KEY (ID_WINE_VARIETY)
        REFERENCES WINE_VARIETY (ID)
        ON DELETE CASCADE,

    CONSTRAINT CHK_WINE_ROW_HEAD_COUNT
        CHECK (HEAD_COUNT > 0)
)
CHARACTER SET utf8mb4
COLLATE utf8mb4_czech_ci;

CREATE TABLE TREATMENT(
    ID_TASK INT PRIMARY KEY,
    ID_VINEYARD INTEGER NOT NULL,
    TYPE ENUM('SPRAYING', 'FERTILIZING', 'CUTTING', 'WEEDING', 'OTHER') NOT NULL DEFAULT('SPRAYING'),
    TREAT_NAME VARCHAR(20) NOT NULL,
    QUANTITY DECIMAL(10,2),
    UNIT VARCHAR(10),
    CONSTRAINT FK_TREATMENT_TASK
        FOREIGN KEY (ID_TASK) REFERENCES TASK(ID) 
        ON DELETE CASCADE,
    CONSTRAINT FK_TREATMENT_VINEYARD
        FOREIGN KEY (ID_VINEYARD) REFERENCES VINEYARD(ID)
        ON DELETE CASCADE,
    CONSTRAINT CHECK_TREATMENT_QUANTITY
        CHECK (QUANTITY > 0)
    
)
CHARACTER SET utf8mb4
COLLATE utf8mb4_czech_ci;

CREATE TABLE HARVEST(
    ID_TASK INT PRIMARY KEY,
    ID_VINEYARD INTEGER NOT NULL,
    ID_WINE_VARIETY INTEGER NOT NULL,
    ID_HARVEST_CONFIRMER INTEGER,
    ID_WINE INTEGER,
    WEIGHT DECIMAL(10,2),
    SUGAR_CONTENT DECIMAL(5,2),
    CONSTRAINT FK_HARVEST_TASK
        FOREIGN KEY (ID_TASK) REFERENCES TASK(ID) 
        ON DELETE CASCADE,
    CONSTRAINT FK_HARVEST_VINEYARD
        FOREIGN KEY (ID_VINEYARD) REFERENCES VINEYARD(ID) 
        ON DELETE CASCADE,
    CONSTRAINT FK_HARVEST_WINE_VARIETY
        FOREIGN KEY (ID_WINE_VARIETY) REFERENCES WINE_VARIETY(ID) 
        ON DELETE CASCADE,
    CONSTRAINT FK_HARVEST_WINE_CONFIRMER
        FOREIGN KEY (ID_HARVEST_CONFIRMER) REFERENCES USER(ID) 
        ON DELETE SET NULL,
    CONSTRAINT FK_HARVEST_WINE
        FOREIGN KEY (ID_WINE) REFERENCES WINE(ID) 
        ON DELETE SET NULL
)
CHARACTER SET utf8mb4
COLLATE utf8mb4_czech_ci;

CREATE TABLE BOTTLING(
    ID_TASK INT PRIMARY KEY,
    ID_WINE INTEGER,
    BOTTLES_COUNT INT NOT NULL,
    BOTTLE_VOLUME DECIMAL(6,2) NOT NULL,
    CONSTRAINT FK_BOTTLING_TASK
        FOREIGN KEY (ID_TASK) REFERENCES TASK(ID) 
        ON DELETE CASCADE,
    CONSTRAINT FK_BOTTLING_WINE
        FOREIGN KEY (ID_WINE) REFERENCES WINE(ID) 
        ON DELETE CASCADE,
    CONSTRAINT CHK_BOTTLING_BOTTLES_COUNT
        CHECK (BOTTLES_COUNT > 0),
    CONSTRAINT CHK_BOTTLING_BOTTLE_VOLUME
        CHECK (BOTTLE_VOLUME > 0)
)
CHARACTER SET utf8mb4
COLLATE utf8mb4_czech_ci;


CREATE TABLE TASK_ROW (
    ID_VINEYARD INTEGER NOT NULL,
    CODE_ROW    INTEGER NOT NULL,  
    ID_TASK      INTEGER NOT NULL,

    CONSTRAINT PK_TASK_ROW
        PRIMARY KEY (ID_VINEYARD, CODE_ROW, ID_TASK),

    CONSTRAINT FK_TASK_ROW_TASK
        FOREIGN KEY (ID_TASK)
        REFERENCES TASK (ID)
        ON DELETE CASCADE,

    CONSTRAINT FK_TASK_ROW_WINE_ROW
        FOREIGN KEY (ID_VINEYARD, CODE_ROW)
        REFERENCES WINE_ROW (ID_VINEYARD, CODE)
        ON DELETE CASCADE
)
CHARACTER SET utf8mb4
COLLATE utf8mb4_czech_ci;

CREATE TABLE WORKER(
    ID_USER INTEGER,
    ID_TASK INTEGER,
    CONSTRAINT PK_WORKER PRIMARY KEY (ID_USER, ID_TASK),
    CONSTRAINT FK_WORKER_USER
        FOREIGN KEY (ID_USER)
        REFERENCES USER (ID)
        ON DELETE CASCADE,
    CONSTRAINT FK_WORKER_TASK
        FOREIGN KEY (ID_TASK)
        REFERENCES TASK (ID)
        ON DELETE CASCADE
)
CHARACTER SET utf8mb4
COLLATE utf8mb4_czech_ci;


CREATE TABLE CUSTOMER_ORDER (
    ID            INTEGER AUTO_INCREMENT,
    ID_CUSTOMER   INTEGER,
    TOTAL_PRICE   DECIMAL(10,2) NOT NULL CHECK (TOTAL_PRICE >= 0),
    STATE         ENUM('RESERVED','PAID','CANCELLED', 'DELIVERED') NOT NULL DEFAULT 'RESERVED',
    DELIVERY_TYPE ENUM('PICKUP','COURIER','POST') NOT NULL,
    ORDER_TIMESTAMP TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    DELIVERY_DATA TEXT NOT NULL DEFAULT(''),
    CONSTRAINT PK_CUSTOMER_ORDER PRIMARY KEY (ID),
    CONSTRAINT FK_ORDER_CUSTOMER
        FOREIGN KEY (ID_CUSTOMER) REFERENCES USER(ID)
        ON DELETE CASCADE
)
CHARACTER SET utf8mb4
COLLATE utf8mb4_czech_ci;

CREATE TABLE ORDER_ITEM (
    ID_ORDER         INTEGER NOT NULL,
    ID_WINE          INTEGER NOT NULL,
    BOTTLES_COUNT    INT NOT NULL,
    PRICE_PER_BOTTLE DECIMAL(10,2) NOT NULL,
    CONSTRAINT PK_ORDER_ITEM PRIMARY KEY (ID_ORDER, ID_WINE),
    CONSTRAINT FK_ORDER_ITEM_ORDER
        FOREIGN KEY (ID_ORDER) REFERENCES CUSTOMER_ORDER(ID)
        ON DELETE CASCADE,
    CONSTRAINT FK_ORDER_ITEM_WINE
        FOREIGN KEY (ID_WINE)  REFERENCES WINE(ID)
        ON DELETE CASCADE
        
)
CHARACTER SET utf8mb4
COLLATE utf8mb4_czech_ci;

--
-- Dumping data for table `PICTURE`
--

INSERT INTO `PICTURE` (`ID`, `IMAGE_NAME`, `LINK`, `DESCRIPTION`) VALUES
(2, 'Irsai Oliver', 'img_20251119151639691ddf578164c2.16703650.webp', 'Obrázek odrůdy vína Irsai Oliver'),
(3, 'Modrý Portugal', 'img_20251119151711691ddf7760b936.79538680.jpeg', 'Obrázek odrůdy vína Modrý Portugal'),
(4, 'Tramín Červený', 'img_20251119151748691ddf9c56ebd0.82406862.jpeg', 'Obrázek odrůdy vína Tramín Červený'),
(5, 'Ryzlink Vlašský', 'img_20251119151846691ddfd6e489f0.14956048.webp', 'Obrázek odrůdy vína Ryzlink Vlašský'),
(6, 'Pálava', 'img_20251119151907691ddfebeb7b99.11880482.webp', 'Obrázek odrůdy vína Pálava'),
(7, 'Müller Thurgau', 'img_20251119151930691de0023db0b0.49773505.webp', 'Obrázek odrůdy vína Müller Thurgau'),
(8, 'Sauvignon', 'img_20251119151948691de014eaa415.67808293.webp', 'Obrázek odrůdy vína Sauvignon'),
(9, 'Ryzlink Rýnský', 'img_20251119152008691de028948555.68413687.webp', 'Obrázek odrůdy vína Ryzlink Rýnský'),
(10, 'Vinohrad U Zlaté studny', 'img_20251119153500691de3a44b7342.83006041.jpeg', 'Obrázek vinohradu Vinohrad U Zlaté studny'),
(11, 'Vinohrad Pod Hůrkou', 'img_20251119153852691de48c9c71b0.78425762.jpeg', 'Obrázek vinohradu Vinohrad Pod Hůrkou'),
(12, 'Vinohrad Na Stráni', 'img_20251119153931691de4b38d6d15.61980644.jpeg', 'Obrázek vinohradu Vinohrad Na Stráni');

-- --------------------------------------------------------

INSERT INTO `WINE_VARIETY` (`ID`, `ID_PICTURE`, `NAME`, `COLOR`, `DESCRIPTION`) VALUES
(1, 2, 'Irsai Oliver', 'WHITE', 'Irsai Oliver je aromatická bílá odrůda vína původem z Maďarska, vzniklá křížením odrůd Pozsonyi Fehér (Bratislavské bílé) a Pearl of Csaba v roce 1930. Vyznačuje se intenzivní květinovo-muškátovou vůní, často připomínající bezový květ, muškátový hrozen či citrusové plody. Chuť je lehčí, svěží a harmonická, s příjemnou kyselinkou, která podtrhuje její ovocný charakter. Tato odrůda se nejčastěji používá pro výrobu suchých nebo polosuchých vín, která jsou oblíbená pro svou pitelnost a aromatickou přitažlivost už jako mladá vína. Irsai Oliver se dobře pije za tepla letního dne nebo jako aperitiv, často bývá i základem pro letní cuvée.'),
(2, 3, 'Modrý Portugal', 'BLUE', 'Modrý Portugal je tradiční červená odrůda původu střední Evropy, rozšířená především v Rakousku, České republice a na Slovensku. Je známá svou jemností, lehkostí a ovocným charakterem, díky čemuž si získala oblibu jako víno pro každodenní pití. Víno z Modrého Portugalu má světle rubínovou až granátovou barvu a vůni po červeném ovoci, zejména třešních, malinách či ostružinách. Chuť bývá měkká, vyvážená a sametová, s nižším obsahem tříslovin i kyselin, což z něj činí příjemně hladké a snadno pitelné víno.'),
(3, 4, 'Tramín Červený', 'GREY', 'Tramín červený je stará aromatická odrůda révy vinné, jejíž původ sahá až do středověku a bývá spojován s oblastí okolo jihotyrolského města Tramin (Termeno). Patří mezi nejvýraznější bílé odrůdy, i když její hrozny mají nažloutle růžové až červené slupky. Víno z Tramínu červeného je typické svou intenzivní vůní růží, koření, medu a exotického ovoce. Chuť bývá plná, bohatá a výrazně aromatická, často s nižší kyselinkou, což mu dodává hebkost a sametový dojem. Nejčastěji se vyrábí jako suché nebo polosladké víno, které vyniká svou výraznou osobitostí.'),
(4, 5, 'Ryzlink Vlašský', 'WHITE', 'Ryzlink vlašský je tradiční bílá odrůda rozšířená především na jižní Moravě, zejména v oblasti Pálavy. Vyznačuje se výraznou minerální chutí a svěží kyselinkou, která odráží vápenité podloží místních vinic. Víno má jemné aroma květin, jablek a lískových oříšků, s lehkým nádechem medu při delším zrání. Díky své vyváženosti a eleganci je oblíbené jak k okamžité konzumaci, tak pro archivaci. Ryzlink vlašský je ideální společník k rybám, lehkým sýrům a pokrmům z bílého masa.'),
(5, 6, 'Pálava', 'WHITE', 'Pálava je ušlechtilá aromatická odrůda vzniklá na Moravě křížením Tramínu červeného a Mülleru Thurgau. Je typická svou bohatou vůní muškátu, růží a tropického ovoce. Chuť bývá plná, kulatá a jemně nasládlá, s harmonickou kyselinkou. Pálava je často vyráběna jako polosuché nebo polosladké víno, které vyniká svou svůdnou vůní a dlouhou dochutí. Je to víno vhodné k dezertům, aromatickým sýrům nebo k samostatnému vychutnání při zvláštních příležitostech.'),
(6, 7, 'Müller Thurgau', 'WHITE', 'Müller Thurgau je lehká a svěží bílá odrůda, vyšlechtěná ve Švýcarsku křížením Ryzlinku rýnského a Madeleine Royale. Na Moravě je velmi rozšířená díky své ranosti a spolehlivému výnosu. Vína z této odrůdy mají jemné aroma květin a muškátu s tóny broskví či citrusů. Chuť je měkká, harmonická a příjemně ovocná, často s nižším obsahem kyselin. Je ideálním vínem pro každodenní pití, obzvláště v mladém stavu, kdy vyniká svou svěžestí a lehkostí.'),
(7, 8, 'Sauvignon', 'WHITE', 'Sauvignon je celosvětově uznávaná aromatická bílá odrůda, která se na Moravě těší velké oblibě. Je charakteristická výraznou vůní po černém rybízu, angreštu, broskví či kopřivách. Chuť bývá svěží, plná života a doprovázená výraznou kyselinkou, která vínu dodává energii a eleganci. Podle polohy vinohradu může být Sauvignon jemný a květinový, nebo naopak zemitější a minerální. Výborně se hodí k rybám, kozím sýrům i lehkým zeleninovým pokrmům.'),
(8, 9, 'Ryzlink Rýnský', 'WHITE', 'Ryzlink rýnský patří mezi nejvšestrannější a nejuznávanější bílé odrůdy světa. Na severu Čech i na Moravě poskytuje vína s výraznou kyselinkou, dlouhověkostí a komplexní aromatikou. Typické jsou tóny limetky, meruněk, lipového květu a minerality. S věkem víno získává na hloubce a rozvíjí medové a petrolejové tóny. Ryzlink rýnský je ideálním vínem k archivaci i gurmánským pokrmům – od ryb po kořeněná jídla. Každý ročník dokáže překvapit svou jedinečnou harmonií a elegancí.');


--
-- Dumping data for table `USER`
--

INSERT INTO `USER` (`ID`, `NAME`, `ACTIVE`, `SURNAME`, `USERNAME`, `EMAIL`, `PSWD_HASH`, `PSWD_SALT`, `PHONE_NUMBER`, `CITY`, `POST_CODE`, `STREET`, `HOUSE_NUMBER`, `REGISTRATION_TIMESTAMP`, `ROLE_USER`, `ROLE_WORKER`, `ROLE_VINTNER`, `ROLE_ADMIN`) VALUES
(1, 'Andrej', 1, 'Sud', 'andrej', 'sud@vinarstvi.cz', '$argon2id$v=19$m=65536,t=4,p=3$aDBSY0UxNjVoOU42alFodw$eboCEGjKnAp9IlqcUcaV7O+DR4vM/cb4sQDVQpIe/18', 'aa1dab2f594a43cbb3926cc7f8963474', '111111111', 'Znojmo', '12345', 'Vinařská', '99', '2025-10-16 14:40:30', 1, 1, 1, 1),
(2, 'Josef', 1, 'Navrátil', 'navratil', 'navratil@vinarstvi.cz', '$argon2id$v=19$m=65536,t=4,p=3$TWtCN0F4SVFZS3Rsd05aUg$HUciIOgyHJXTmTh9p0uX7MPaXIXgCYzKtZ9vJvv+mNs', 'f7b641e6d5fa5f05b578e2ec2463f017', '222222222', 'Vinařská Nová Ves', '74581', 'Sudařská', '999', '2025-10-16 14:43:27', 1, 1, 1, 0),
(3, 'Kamil', 1, 'Pečinka', 'pecinka', 'pecinka@vinarstvi.cz', '$argon2id$v=19$m=65536,t=4,p=3$ZWgxN0Ntb0hiMDFPNC4zbQ$i63r58BNKLbpAw5+qChy9jJh2H4kuSocdccsXuj2PXY', 'bebc5de3871b5bba95adb0555c29de70', '333333333', 'Znojmo', '45632', 'Dlouhá', '9999', '2025-10-16 14:45:30', 1, 1, 0, 0),
(4, 'Bohumil', 1, 'Nový', 'bohumil', 'bohumil@atlas.cz', '$argon2id$v=19$m=65536,t=4,p=3$QmtpTk93am1qMnRDR25kaw$S9pwKYOUnJYq9JYpWN6uBlEEbxtPNkbTVRn80Gw21I8', 'bfc45138b62f36a79266e804e6257f66', '555555555', 'Krátká', '14587', 'Brno', '999999', '2025-10-16 14:47:08', 1, 0, 0, 0),
(5, 'Lukáš', 1, 'Vácha', 'vacha', 'vacha@gmail.com', '$argon2id$v=19$m=65536,t=4,p=3$T1BPRzZ5eUJyRk5RTzVJTA$krBo/GCAq1j1Y98PUhN7pmm5lfYPxV+ozUYSJil2QfY', '3741f21a6f815649f65d757bec5f8c17', NULL, NULL, NULL, NULL, NULL, '2025-11-19 15:13:00', 1, 0, 0, 0);

-- --------------------------------------------------------
--
-- Dumping data for table `VINEYARD`
--

INSERT INTO `VINEYARD` (`ID`, `ACTIVE`, `ID_PICTURE`, `NAME`, `LOCATION`, `DESCRIPTION`, `YEAR`) VALUES
(1, 1, 10, 'Vinohrad U Zlaté studny', 'Pavlov, Česká republika', 'Tento vinohrad leží na jihovýchodních svazích Pálavy s výhledem na Novomlýnské nádrže. Díky vápenité půdě a teplému mikroklimatu zde vznikají vína s výjimečnou minerální stopou a plnou aromatikou. Réva je pěstována šetrně, s důrazem na ruční práci a nízké výnosy, což zaručuje vysokou kvalitu hroznů. Typickými odrůdami jsou Ryzlink vlašský, Pálava a Tramín červený, které zde získávají elegantní vůni květů a medu. Vinaři se zaměřují na harmonická, vyvážená vína s dlouhou dochutí, ideální k archivaci i k okamžitému vychutnání.', 2005),
(2, 1, 11, 'Vinohrad Pod Hůrkou', 'Mikulčice, Česká republika', 'Menší rodinný vinohrad se rozkládá na svazích nad obcí Mikulčice v Dolnomoravském úvalu. Písčitohlinitá půda a teplé proudění od jihu dávají vznik vínům jemným, ovocným a svěžím, s výraznou kyselinkou. Pěstují se zde především Irsai Oliver, Müller Thurgau a Sauvignon, které vynikají svou lehkostí a aromatičností. Vinohrad je veden s respektem k přírodě – bez chemických postřiků a s důrazem na biodiverzitu. Výsledná vína odrážejí jedinečný charakter jižní Moravy a každý ročník přináší jemné nuance vůní i chutí.', 2010),
(3, 1, 12, 'Vinohrad Na Stráni', 'Velké Žernoseky, Česká republika', 'Vinohrad Na Stráni se nachází na úbočí Českého středohoří, kde sopečné podloží a chladnější klima dávají vínům zcela odlišný charakter než na Moravě. Hrozny dozrávají pomaleji, díky čemuž si zachovávají intenzivní aroma a svěží kyselinu. Dominantními odrůdami jsou Ryzlink rýnský a Modrý Portugal, které se vyznačují čistotou, elegancí a minerálním závěrem. Vinohrad hospodaří s respektem k tradici i moderním postupům, s důrazem na ruční sklizeň a precizní zpracování. Výsledkem jsou vína s charakterem, která dokonale vystihují severní terroir Čech.', 1998);

-- --------------------------------------------------------

--
-- Dumping data for table `WINE_ROW`
--

INSERT INTO `WINE_ROW` (`CODE`, `ACTIVE`, `ID_VINEYARD`, `ID_WINE_VARIETY`, `HEAD_COUNT`, `PLANTING_YEAR`) VALUES
(1, 1, 1, 1, 100, 2005),
(2, 1, 1, 1, 20, 2010),
(3, 1, 1, 1, 180, 2012),
(4, 1, 1, 5, 900, 2020),
(5, 1, 1, 5, 100, 2015),
(6, 1, 1, 3, 400, 2024),
(1, 1, 2, 7, 200, 2010),
(2, 1, 2, 7, 100, 2012),
(3, 1, 2, 8, 180, 2014),
(4, 1, 2, 8, 400, 2020),
(1, 1, 3, 2, 200, 1998),
(2, 1, 3, 2, 800, 2000),
(3, 1, 3, 4, 100, 2004),
(4, 1, 3, 4, 100, 2006),
(5, 1, 3, 6, 400, 2025);

-- --------------------------------------------------------

--
-- Dumping data for table `WINE_VARIETY`
--



/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;