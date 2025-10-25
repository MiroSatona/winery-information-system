/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
-- all users have password 111111111
-- Set database character set and collation
ALTER DATABASE iis_winery CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci;

-- Things to discuss:
-- My TODOs in the code
-- Deletes
-- Varchar lengths
-- Enum values
-- Checks for some attributes?

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
    ROLE_USER BOOLEAN NOT NULL DEFAULT(0) ,
    ROLE_WORKER BOOLEAN NOT NULL DEFAULT(0) ,
    ROLE_VINTNER BOOLEAN NOT NULL DEFAULT(0) ,
    ROLE_ADMIN BOOLEAN NOT NULL DEFAULT(0) ,
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
    DESCRIPTION TEXT NOT NULL,
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
        ON DELETE CASCADE
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

-- users
INSERT INTO `USER` (`ID`, `NAME`, `SURNAME`, `USERNAME`, `EMAIL`, `PSWD_HASH`, `PSWD_SALT`, `PHONE_NUMBER`, `CITY`, `POST_CODE`, `STREET`, `HOUSE_NUMBER`, `REGISTRATION_TIMESTAMP`, `ROLE_USER`, `ROLE_WORKER`, `ROLE_VINTNER`, `ROLE_ADMIN`) VALUES
(1, 'Andrej', 'Kokožka', 'andrej', 'kokozka@vinarstvi.cz', '$argon2id$v=19$m=65536,t=4,p=3$aDBSY0UxNjVoOU42alFodw$eboCEGjKnAp9IlqcUcaV7O+DR4vM/cb4sQDVQpIe/18', 'aa1dab2f594a43cbb3926cc7f8963474', '111111111', 'Znojmo', '12345', 'Vinařská', '99', '2025-10-16 14:40:30', 1, 1, 1, 1),
(2, 'Josef', 'Navrátil', 'navratil', 'navratil@vinarstvi.cz', '$argon2id$v=19$m=65536,t=4,p=3$TWtCN0F4SVFZS3Rsd05aUg$HUciIOgyHJXTmTh9p0uX7MPaXIXgCYzKtZ9vJvv+mNs', 'f7b641e6d5fa5f05b578e2ec2463f017', '222222222', 'Vinařská Nová Ves', '74581', 'Sudařská', '999', '2025-10-16 14:43:27', 1, 1, 1, 0),
(3, 'Kamil', 'Pečinka', 'pecinka', 'pecinka@vinarstvi.cz', '$argon2id$v=19$m=65536,t=4,p=3$ZWgxN0Ntb0hiMDFPNC4zbQ$i63r58BNKLbpAw5+qChy9jJh2H4kuSocdccsXuj2PXY', 'bebc5de3871b5bba95adb0555c29de70', '333333333', 'Znojmo', '45632', 'Dlouhá', '9999', '2025-10-16 14:45:30', 1, 1, 0, 0),
(4, 'Bohumil', 'Klobása', 'klobasa', 'klobas.bohumil@atlas.cz', '$argon2id$v=19$m=65536,t=4,p=3$QmtpTk93am1qMnRDR25kaw$S9pwKYOUnJYq9JYpWN6uBlEEbxtPNkbTVRn80Gw21I8', 'bfc45138b62f36a79266e804e6257f66', '555555555', 'Krátká', '14587', 'Brno', '999999', '2025-10-16 14:47:08', 1, 0, 0, 0);

-- task table
INSERT INTO TASK (ID, NAME, STATE, CREATION_TIMESTAMP, LAST_CHANGE_TIMESTAMP, PLANNED_EXECUTION_TIMESTAMP, EXECUTION_TIMESTAMP, NOTE, ID_TASK_ISSUER, ID_TASK_CONFIRMER)
VALUES
(1, 'Sklizeň hroznů', 'PLANNED', '2023-10-01 10:00:00', '2023-10-01 10:00:00', '2023-10-05 08:00:00', NULL, 'Sklidit hrozny na vinohradu', 1, NULL),
(2, 'Sklizeň hroznů', 'FINISHED', '2023-10-02 11:00:00', '2023-10-02 12:00:00', '2023-10-07 10:00:00', '2023-10-07 09:00:00', 'Sklizeň hroznů z poslední sklizně', 2, 3),
(3, 'Sklizeň hroznů', 'CANCELED', '2023-10-03 15:00:00', '2023-10-03 16:00:00', '2023-10-10 10:00:00', NULL, 'Sklizeň hroznů zrušena kvůli logistickým problémům', NULL, NULL),
(4, 'Sklizeň hroznů', 'PLANNED', '2023-11-01 10:00:00', '2023-11-01 10:00:00', '2023-11-05 08:00:00', NULL, 'Sklizeň hroznů na vinohradu - druhý úkol', 1, NULL),
(5, 'Sklizeň hroznů', 'FINISHED', '2023-11-02 11:00:00', '2023-11-02 12:00:00', '2023-11-07 10:00:00', '2023-11-07 09:00:00', 'Sklizeň hroznů na vinohradu - třetí úkol', 2, 3),
(6, 'Sklizeň hroznů', 'FINISHED', '2024-05-01 09:00:00', '2024-05-10 07:00:00', '2024-05-06 07:00:00', '2024-05-10 07:00:00', 'Sklizeň hroznů na vinohradu - fáze 4', 3, NULL),
(7, 'Lahvování vína', 'FINISHED', '2024-05-11 10:00:00', '2024-05-15 12:00:00', '2024-05-20 09:00:00', '2024-05-15 12:00:00', 'Lahvování vína z poslední sklizně', 1, 2),
(8, 'Lahvování vína', 'FINISHED', '2024-06-01 10:00:00', '2024-06-05 12:00:00', '2024-06-10 09:00:00', '2024-06-05 12:00:00', 'Lahvování vína z poslední sklizně - druhé kolo', 2, 3),
(9, 'Lahvování vína', 'PLANNED', '2024-06-10 10:00:00', '2024-06-10 10:00:00', '2024-06-15 09:00:00', NULL, 'Lahvování vína z poslední sklizně - plánované', 3, NULL),
(10, 'Ošetření vinice', 'PLANNED', '2024-06-15 10:00:00', '2024-06-15 10:00:00', '2024-06-20 09:00:00', NULL, 'Ošetření vínice před sezónou', 1, NULL),
(11, 'Ošetření vinice', 'FINISHED', '2024-06-16 11:00:00', '2024-06-16 12:00:00', '2024-06-21 10:00:00', '2024-06-21 09:00:00', 'Ošetření vínice - dokončeno', 2, 3),
(12, 'Ošetření vinice', 'CANCELED', '2024-06-17 15:00:00', '2024-06-17 16:00:00', '2024-06-22 10:00:00', NULL, 'Ošetření vínice zrušeno kvůli nepříznivému počasí', NULL, NULL),
(13, 'Sklizeň hroznů', 'FINISHED', '2024-06-18 09:00:00', '2024-06-20 07:00:00', '2024-06-19 07:00:00', '2024-06-20 07:00:00', 'Sklizeň hroznů na vinohradu - fáze 5', 1, 2),
(14, 'Sklizeň hroznů', 'FINISHED', '2024-06-21 09:00:00', '2024-06-23 07:00:00', '2024-06-22 07:00:00', '2024-06-23 07:00:00', 'Sklizeň hroznů na vinohradu - fáze 6', 2, 3);
-- picture table
INSERT INTO PICTURE (ID, IMAGE_NAME, LINK, DESCRIPTION)
VALUES
(1, 'Irsai Oliver', 'irsaiOliver.webp', 'Obrázek odrůdy Irsai Oliver'),
(2, 'Modrý Portugal', 'modryPortugal.jpg', 'Obrázek odrůdy Modrý Portugal'),
(3, 'Tramín Červený', 'traminCerveny.jpg', 'Obrázek odrůdy Tramín Červený'),
(4, 'Vinohrad U Zlaté studny', 'vinohrad1.jpg', 'Obrázek vinohradu U Zlaté studny'),
(5, 'Vinohrad Pod Hůrkou', 'vinohrad2.jpg', 'Obrázek vinohradu Pod Hůrkou'),
(6, 'Vinohrad Na Stráni', 'vinohrad3.jpg', 'Obrázek vinohradu Na Stráni'),
(7, 'Irsai Oliver', 'irsaiOliverVino.png', 'Obrázek láhve Irsai Oliver'),
(8, 'Modrý Portugal', 'modryPortugalVino.jpeg', 'Obrázek láhve Modrý Portugal'),
(9, 'Tramín Červený', 'traminCervenyVino.png', 'Obrázek láhve Tramín Červený'),
(10, 'Ryzlink Vlašský', 'ryzlinkVlassky.jpg', 'Obrázek odrůdy Ryzlink Vlašský'),
(11, 'Pálava', 'palava.png', 'Obrázek odrůdy Pálava'),
(12, 'Müller Thurgau', 'mueller_thurgau.jpg', 'Obrázek odrůdy Müller Thurgau'),
(13, 'Sauvignon', 'Sauvignon.webp', 'Obrázek odrůdy Sauvignon'),
(14, 'Ryzlink Rýnský', 'Ryzlink-rynsky.jpg', 'Obrázek odrůdy Ryzlink Rýnský');

-- wine table 
INSERT INTO WINE (ID, WINE_NAME, ID_LIQUIDATOR, ID_SELL_APPROVER, ID_PICTURE, YEAR, ALCOHOL_PERCENTAGE, TYPE, COLOR, GRADE, STATE, VOLUME, BOTTLE_PRICE, CURRENT_PRICE, BOTTLES_LEFT, LEFT_SUGAR, ACID_CONTENTS, SALES_DESCRIPTION, PRODUCTION_NOTE)
VALUES
(1, 'Irsai Oliver', 1, 3, 7, 2021, 14, 'HALF_DRY', 'WHITE', 'LATE', 'PRODUCTION', 1000, 120, 90, 100, 10, 6, 'Robustní červené víno perfektní k večeři', 'Pěstované v srdci vinohradu'),
(2, 'Modrý Portugal', 2, 3, 8, 2020, 13, 'DRY', 'RED', 'LATE', 'BOTTLED', 750, 12, 18, 200, 3, 5, 'Jemné bílé víno s nádechem sladkosti', 'Vyrobené z pozdně sklízených hroznů'),
(3, 'Tramín Červený', 3, 1, 9, 2019, 12, 'SWEET', 'ROSE', 'NONE', 'SALE', 500, 10, 15, 150, 8, 7, 'Sladké rosé víno, ideální k lehkým jídlům', 'Vyrobené z vybraných odrůd');

-- vineYard table 
INSERT INTO VINEYARD (ID, ID_PICTURE, NAME, LOCATION, DESCRIPTION, YEAR)
VALUES
(1, 4, 'Vinohrad U Zlaté studny', 'Pavlov, Česká republika', 'Tento vinohrad leží na jihovýchodních svazích Pálavy s výhledem na Novomlýnské nádrže. Díky vápenité půdě a teplému mikroklimatu zde vznikají vína s výjimečnou minerální stopou a plnou aromatikou. Réva je pěstována šetrně, s důrazem na ruční práci a nízké výnosy, což zaručuje vysokou kvalitu hroznů. Typickými odrůdami jsou Ryzlink vlašský, Pálava a Tramín červený, které zde získávají elegantní vůni květů a medu. Vinaři se zaměřují na harmonická, vyvážená vína s dlouhou dochutí, ideální k archivaci i k okamžitému vychutnání.', 2005),
(2, 5, 'Vinohrad Pod Hůrkou', 'Mikulčice, Česká republika', 'Menší rodinný vinohrad se rozkládá na svazích nad obcí Mikulčice v Dolnomoravském úvalu. Písčitohlinitá půda a teplé proudění od jihu dávají vznik vínům jemným, ovocným a svěžím, s výraznou kyselinkou. Pěstují se zde především Irsai Oliver, Müller Thurgau a Sauvignon, které vynikají svou lehkostí a aromatičností. Vinohrad je veden s respektem k přírodě – bez chemických postřiků a s důrazem na biodiverzitu. Výsledná vína odrážejí jedinečný charakter jižní Moravy a každý ročník přináší jemné nuance vůní i chutí.', 2010),
(3, 6, 'Vinohrad Na Stráni', 'Velké Žernoseky, Česká republika', 'Vinohrad Na Stráni se nachází na úbočí Českého středohoří, kde sopečné podloží a chladnější klima dávají vínům zcela odlišný charakter než na Moravě. Hrozny dozrávají pomaleji, díky čemuž si zachovávají intenzivní aroma a svěží kyselinu. Dominantními odrůdami jsou Ryzlink rýnský a Modrý Portugal, které se vyznačují čistotou, elegancí a minerálním závěrem. Vinohrad hospodaří s respektem k tradici i moderním postupům, s důrazem na ruční sklizeň a precizní zpracování. Výsledkem jsou vína s charakterem, která dokonale vystihují severní terroir Čech.', 1998);


-- WINE_VARIETY table 
INSERT INTO WINE_VARIETY (ID, ID_PICTURE, COLOR, NAME, DESCRIPTION)
VALUES
(1, 1, 'WHITE', 'Irsai Oliver', 'Irsai Oliver je aromatická bílá odrůda vína původem z Maďarska, vzniklá křížením odrůd Pozsonyi Fehér (Bratislavské bílé) a Pearl of Csaba v roce 1930. Vyznačuje se intenzivní květinovo-muškátovou vůní, často připomínající bezový květ, muškátový hrozen či citrusové plody. Chuť je lehčí, svěží a harmonická, s příjemnou kyselinkou, která podtrhuje její ovocný charakter. Tato odrůda se nejčastěji používá pro výrobu suchých nebo polosuchých vín, která jsou oblíbená pro svou pitelnost a aromatickou přitažlivost už jako mladá vína. Irsai Oliver se dobře pije za tepla letního dne nebo jako aperitiv, často bývá i základem pro letní cuvée.'),
(2, 2, 'BLUE', 'Modrý Portugal', 'Modrý Portugal je tradiční červená odrůda původu střední Evropy, rozšířená především v Rakousku, České republice a na Slovensku. Je známá svou jemností, lehkostí a ovocným charakterem, díky čemuž si získala oblibu jako víno pro každodenní pití. Víno z Modrého Portugalu má světle rubínovou až granátovou barvu a vůni po červeném ovoci, zejména třešních, malinách či ostružinách. Chuť bývá měkká, vyvážená a sametová, s nižším obsahem tříslovin i kyselin, což z něj činí příjemně hladké a snadno pitelné víno'),
(3, 3, 'GREY', 'Tramín Červený', 'Tramín červený je stará aromatická odrůda révy vinné, jejíž původ sahá až do středověku a bývá spojován s oblastí okolo jihotyrolského města Tramin (Termeno). Patří mezi nejvýraznější bílé odrůdy, i když její hrozny mají nažloutle růžové až červené slupky. Víno z Tramínu červeného je typické svou intenzivní vůní růží, koření, medu a exotického ovoce. Chuť bývá plná, bohatá a výrazně aromatická, často s nižší kyselinkou, což mu dodává hebkost a sametový dojem. Nejčastěji se vyrábí jako suché nebo polosladké víno, které vyniká svou výraznou osobitostí.'),
(4, 10, 'WHITE', 'Ryzlink Vlašský', 'Ryzlink vlašský je tradiční bílá odrůda rozšířená především na jižní Moravě, zejména v oblasti Pálavy. Vyznačuje se výraznou minerální chutí a svěží kyselinkou, která odráží vápenité podloží místních vinic. Víno má jemné aroma květin, jablek a lískových oříšků, s lehkým nádechem medu při delším zrání. Díky své vyváženosti a eleganci je oblíbené jak k okamžité konzumaci, tak pro archivaci. Ryzlink vlašský je ideální společník k rybám, lehkým sýrům a pokrmům z bílého masa.'),
(5, 11, 'WHITE', 'Pálava', 'Pálava je ušlechtilá aromatická odrůda vzniklá na Moravě křížením Tramínu červeného a Mülleru Thurgau. Je typická svou bohatou vůní muškátu, růží a tropického ovoce. Chuť bývá plná, kulatá a jemně nasládlá, s harmonickou kyselinkou. Pálava je často vyráběna jako polosuché nebo polosladké víno, které vyniká svou svůdnou vůní a dlouhou dochutí. Je to víno vhodné k dezertům, aromatickým sýrům nebo k samostatnému vychutnání při zvláštních příležitostech.'),
(6, 12, 'WHITE', 'Müller Thurgau', 'Müller Thurgau je lehká a svěží bílá odrůda, vyšlechtěná ve Švýcarsku křížením Ryzlinku rýnského a Madeleine Royale. Na Moravě je velmi rozšířená díky své ranosti a spolehlivému výnosu. Vína z této odrůdy mají jemné aroma květin a muškátu s tóny broskví či citrusů. Chuť je měkká, harmonická a příjemně ovocná, často s nižším obsahem kyselin. Je ideálním vínem pro každodenní pití, obzvláště v mladém stavu, kdy vyniká svou svěžestí a lehkostí.'),
(7, 13, 'WHITE', 'Sauvignon', 'Sauvignon je celosvětově uznávaná aromatická bílá odrůda, která se na Moravě těší velké oblibě. Je charakteristická výraznou vůní po černém rybízu, angreštu, broskví či kopřivách. Chuť bývá svěží, plná života a doprovázená výraznou kyselinkou, která vínu dodává energii a eleganci. Podle polohy vinohradu může být Sauvignon jemný a květinový, nebo naopak zemitější a minerální. Výborně se hodí k rybám, kozím sýrům i lehkým zeleninovým pokrmům.'),
(8, 14, 'WHITE', 'Ryzlink Rýnský', 'Ryzlink rýnský patří mezi nejvšestrannější a nejuznávanější bílé odrůdy světa. Na severu Čech i na Moravě poskytuje vína s výraznou kyselinkou, dlouhověkostí a komplexní aromatikou. Typické jsou tóny limetky, meruněk, lipového květu a minerality. S věkem víno získává na hloubce a rozvíjí medové a petrolejové tóny. Ryzlink rýnský je ideálním vínem k archivaci i gurmánským pokrmům – od ryb po kořeněná jídla. Každý ročník dokáže překvapit svou jedinečnou harmonií a elegancí.');

-- WINE_ROW
INSERT INTO WINE_ROW (CODE, ID_VINEYARD, ID_WINE_VARIETY, HEAD_COUNT, PLANTING_YEAR)
VALUES
(1, 1, 4, 185, 2006),
(2, 1, 5, 172, 2010),
(3, 1, 3, 156, 2012),
(4, 1, 4, 198, 2016),
(5, 1, 5, 145, 2018),
(1, 2, 1, 210, 2009),
(2, 2, 6, 198, 2011),
(3, 2, 7, 165, 2014),
(4, 2, 1, 230, 2018),
(5, 2, 6, 187, 2020),
(1, 3, 8, 175, 2000),
(2, 3, 2, 162, 2002),
(3, 3, 8, 189, 2008),
(4, 3, 2, 143, 2013),
(5, 3, 8, 168, 2019);



-- Treatment table 
INSERT INTO TREATMENT (ID_TASK, ID_VINEYARD, TYPE, TREAT_NAME, QUANTITY, UNIT)
VALUES
(10, 1, 'SPRAYING', 'Fungicid Mancozeb', 2.5, 'ml/litr'),
(11, 2, 'FERTILIZING', 'TTurbo Hnojivo XL Super', 3.0, 'kg/m²'),
(12, 3, 'SPRAYING', 'Insekticid Spinosad', 1.5, 'ml/litr');

-- Harvest Table
INSERT INTO HARVEST (ID_TASK, ID_VINEYARD, ID_WINE_VARIETY, ID_HARVEST_CONFIRMER, ID_WINE, WEIGHT, SUGAR_CONTENT)
VALUES
(1, 1, 1, NULL, NULL, NULL, NULL),
(2, 2, 2, 2, 2, 850.0, 22.1),
(3, 3, 3, NULL, NULL, NULL, NULL),
(4, 1, 1, NULL, NULL, NULL, NULL),
(5, 2, 2, 2, NULL, 900.0, 23.0),
(6, 3, 3, NULL, NULL, NULL, NULL),
(13, 1, 1, 1, 1, 950.0, 21.5),
(14, 3, 3, 2, 3, 800.0, 22.8);

-- TASK_ROW table
INSERT INTO TASK_ROW (ID_VINEYARD, CODE_ROW, ID_TASK)
VALUES
(1, 1, 1),
(2, 1, 2),
(3, 2, 3),
(1, 3, 4),
(2, 4, 5),
(3, 5, 6),
(1, 1, 10),
(2, 2, 11),
(3, 3, 12),
(1, 2, 13),
(3, 4, 14);

-- BOTTLING table 
INSERT INTO BOTTLING (ID_TASK, ID_WINE, BOTTLES_COUNT, BOTTLE_VOLUME)
VALUES
(7, 3, 150, 750),
(8, 2, 200, 750),
(9, 1, 150, 750);


-- CUSTOMER_ORDER table 
INSERT INTO CUSTOMER_ORDER (ID, ID_CUSTOMER, TOTAL_PRICE, STATE, DELIVERY_TYPE, ORDER_TIMESTAMP, DELIVERY_DATA)
VALUES
(1, 1, 150.00, 'PAID', 'COURIER', '2023-10-05 10:00:00', '123 Main St, New York, NY'),
(2, 2, 200.00, 'RESERVED', 'PICKUP', '2023-10-06 12:00:00', '456 Sunset Blvd, LA, CA');


-- ORDER_ITEM
INSERT INTO ORDER_ITEM (ID_ORDER, ID_WINE, BOTTLES_COUNT, PRICE_PER_BOTTLE)
VALUES
(1, 1, 10, 15.00),
(1, 2, 5, 18.00),
(2, 1, 15, 12.00),
(2, 3, 10, 20.00);

-- Restore MySQL settings
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;