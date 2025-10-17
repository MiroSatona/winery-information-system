-- users
INSERT INTO `USER` (`ID`, `NAME`, `SURNAME`, `USERNAME`, `EMAIL`, `PSWD_HASH`, `PSWD_SALT`, `PHONE_NUMBER`, `CITY`, `POST_CODE`, `STREET`, `HOUSE_NUMBER`, `REGISTRATION_TIMESTAMP`, `ROLE_USER`, `ROLE_WORKER`, `ROLE_VINTNER`, `ROLE_ADMIN`) VALUES
(1, 'Andrej', 'Kokožka', 'andrej', 'kokozka@vinarstvi.cz', '$argon2id$v=19$m=65536,t=4,p=3$aDBSY0UxNjVoOU42alFodw$eboCEGjKnAp9IlqcUcaV7O+DR4vM/cb4sQDVQpIe/18', 'aa1dab2f594a43cbb3926cc7f8963474', '111111111', 'Znojmo', '12345', 'Vinařská', '99', '2025-10-16 14:40:30', 1, 1, 1, 1),
(2, 'Josef', 'Navrátil', 'navratil', 'navratil@vinarstvi.cz', '$argon2id$v=19$m=65536,t=4,p=3$TWtCN0F4SVFZS3Rsd05aUg$HUciIOgyHJXTmTh9p0uX7MPaXIXgCYzKtZ9vJvv+mNs', 'f7b641e6d5fa5f05b578e2ec2463f017', '222222222', 'Vinařská Nová Ves', '74581', 'Sudařská', '999', '2025-10-16 14:43:27', 1, 1, 1, 0),
(3, 'Kamil', 'Pečinka', 'pecinka', 'pecinka@vinarstvi.cz', '$argon2id$v=19$m=65536,t=4,p=3$ZWgxN0Ntb0hiMDFPNC4zbQ$i63r58BNKLbpAw5+qChy9jJh2H4kuSocdccsXuj2PXY', 'bebc5de3871b5bba95adb0555c29de70', '333333333', 'Znojmo', '45632', 'Dlouhá', '9999', '2025-10-16 14:45:30', 1, 1, 0, 0),
(4, 'Bohumil', 'Klobása', 'klobasa', 'klobas.bohumil@atlas.cz', '$argon2id$v=19$m=65536,t=4,p=3$QmtpTk93am1qMnRDR25kaw$S9pwKYOUnJYq9JYpWN6uBlEEbxtPNkbTVRn80Gw21I8', 'bfc45138b62f36a79266e804e6257f66', '555555555', 'Krátká', '14587', 'Brno', '999999', '2025-10-16 14:47:08', 1, 0, 0, 0);

-- task table
INSERT INTO TASK (ID, NAME, STATE, CREATION_TIMESTAMP, LAST_CHANGE_TIMESTAMP, PLANNED_EXECUTION_TIMESTAMP, EXECUTION_TIMESTAMP, NOTE, ID_TASK_ISSUER, ID_TASK_CONFIRMER)
VALUES
(1, 'Harvest Grapes', 'PLANNED', '2023-10-01 10:00:00', '2023-10-01 10:00:00', '2023-10-05 08:00:00', NULL, 'Harvest the grapes in the vineyard', 1, NULL),
(2, 'Bottle Wine', 'FINISHED', '2023-10-02 11:00:00', '2023-10-02 12:00:00', '2023-10-07 10:00:00', '2023-10-07 09:00:00', 'Bottling the wine from last harvest', 2, 3),
(3, 'Wine Quality Check', 'CANCELED', '2023-10-03 15:00:00', '2023-10-03 16:00:00', '2023-10-10 10:00:00', NULL, 'Quality check canceled due to logistics issues', NULL, NULL);

-- picture table
INSERT INTO PICTURE (ID, IMAGE_NAME, LINK, DESCRIPTION)
VALUES
(1, 'Irsai Oliver', 'irsaiOliver.webp', 'Obrázek odrůdy Irsai Oliver'),
(2, 'Modrý Portugal', 'modryPortugal.jpg', 'Obrázek odrůdy Modrý Portugal'),
(3, 'Tramín Červený', 'traminCerveny.jpg', 'Obrázek odrůdy Tramín Červený'),
(4, 'Zlaté vinohrady', 'vinohrad1.jpg', 'Obrázek vinohradu Zlaté vinohrady'),
(5, 'Slunečné vinohrady', 'vinohrad2.jpg', 'Obrázek vinohradu Slunečné vinohrady'),
(6, 'Říční vinohrady', 'vinohrad3.jpg', 'Obrázek vinohradu Říční vinohrady'),
(7, 'Irsai Oliver', 'irsaiOliverVino.png', 'Obrázek láhve Irsai Oliver'),
(8, 'Modrý Portugal', 'modryPortugalVino.jpeg', 'Obrázek láhve Modrý Portugal'),
(9, 'Tramín Červený', 'traminCervenyVino.png', 'Obrázek láhve Tramín Červený');

-- wine table 
INSERT INTO WINE (ID, WINE_NAME, ID_LIQUIDATOR, ID_SELL_APPROVER, ID_PICTURE, YEAR, ALCOHOL_PERCENTAGE, TYPE, COLOR, GRADE, STATE, VOLUME, BOTTLE_PRICE, CURRENT_PRICE, BOTTLES_LEFT, LEFT_SUGAR, ACID_CONTENTS, SALES_DESCRIPTION, PRODUCTION_NOTE)
VALUES
(1, 'Irsai Oliver', 1, 3, 7, 2021, 14, 'DRY', 'RED', 'CABINET', 'PRODUCTION', 1000, 15, 20, 500, 5, 6, 'Robustní červené víno perfektní k večeři', 'Pěstované v srdci vinohradu'),
(2, 'Modrý Portugal', 2, 3, 8, 2020, 13, 'HALF_DRY', 'WHITE', 'LATE', 'BOTTLED', 750, 12, 18, 200, 3, 5, 'Jemné bílé víno s nádechem sladkosti', 'Vyrobené z pozdně sklízených hroznů'),
(3, 'Tramín Červený', 3, 1, 9, 2019, 12, 'SWEET', 'PINK', 'NONE', 'SALE', 500, 10, 15, 300, 8, 7, 'Sladké rosé víno, ideální k lehkým jídlům', 'Vyrobené z vybraných odrůd');

-- vineYard table 
INSERT INTO VINEYARD (ID, ID_PICTURE, NAME, LOCATION, DESCRIPTION)
VALUES
(1, 4, 'Zlaté vinohrady', 'Znojmo, Česká republika', 'Krásný vinohrad známý svými bohatými červenými víny'),
(2, 5, 'Slunečné vinohrady', 'Moravské Slovácko, ČR', 'Slunný vinohrad proslulý svými bílými víny'),
(3, 6, 'Říční vinohrady', 'Velké Bílovice, ČR', 'Malý vinohrad umístěný u řeky');

-- WINE_VARIETY table 
INSERT INTO WINE_VARIETY (ID, ID_PICTURE, COLOR, NAME, DESCRIPTION)
VALUES
(1, 1, 'WHITE', 'Irsai Oliver', 'Aromatic white grape variety known for its floral and fruity notes.'),
(2, 2, 'BLUE', 'Modrý Portugal', 'A red grape variety producing light and fruity wines with a hint of spice.'),
(3, 3, 'GREY', 'Tramín Červený', 'A pink-skinned grape variety used to make aromatic wines with a spicy character.');

-- WINE_ROW
INSERT INTO WINE_ROW (CODE, ID_VINEYARD, ID_WINE_VARIETY, HEAD_COUNT, PLANTING_YEAR)
VALUES
(1, 1, 1, 200, 2015),
(2, 2, 2, 150, 2018),
(3, 3, 3, 100, 2017);

-- Treatment table 
INSERT INTO TREATMENT (ID_TASK, CATEGORY, NAME, QUANTITY, UNIT)
VALUES
(1, 'Pesticide', 'Insecticide Spray', 10.5, 'liters'),
(2, 'Fertilizer', 'Organic Fertilizer', 15.2, 'kg'),
(3, 'Herbicide', 'Weed Control', 5.0, 'liters');

-- Harvest Table
INSERT INTO HARVEST (ID_TASK, ID_VINEYARD, ID_WINE_VARIETY, ID_WINEGROWER, ID_WINE, WEIGHT, SUGAR_CONTENT)
VALUES
(1, 1, 1, 3, 1, 1200.5, 24.3),
(2, 2, 2, 2, 2, 850.0, 22.1),
(3, 3, 3, 1, 3, 1100.3, 20.5);

-- BOTTLING table 
INSERT INTO BOTTLING (ID_TASK, ID_WINE, BOTTLES_COUNT, BOTTLE_VOLUME)
VALUES
(2, 1, 100, 0.75),
(3, 2, 200, 0.75),
(1, 3, 150, 0.75);


-- CUSTOMER_ORDER table 
INSERT INTO CUSTOMER_ORDER (ID, ID_CUSTOMER, TOTAL_PRICE, STATE, DELIVERY_TYPE, ORDER_TIMESTAMP, DELIVERY_DATA, BILLING_INFO)
VALUES
(1, 1, 150.00, 'PAID', 'COURIER', '2023-10-05 10:00:00', '123 Main St, New York, NY', 'Visa ending in 1234'),
(2, 2, 200.00, 'RESERVED', 'PICKUP', '2023-10-06 12:00:00', '456 Sunset Blvd, LA, CA', 'MasterCard ending in 5678');


-- ORDER_ITEM
INSERT INTO ORDER_ITEM (ID_ORDER, ID_ITEM, ID_WINE, BOTTLES_COUNT, PRICE_PER_BOTTLE)
VALUES
(1, 1, 1, 10, 15.00),
(1, 2, 2, 5, 18.00),
(2, 1, 1, 15, 12.00),
(2, 2, 3, 10, 20.00);

-- Restore MySQL settings
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;