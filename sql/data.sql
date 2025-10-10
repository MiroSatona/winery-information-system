-- users
INSERT INTO USER (ID, NAME, SURNAME, USERNAME, EMAIL, PSWD_HASH, PSWD_SALT, PHONE_NUMBER, CITY, POST_CODE, STREET, HOUSE_NUMBER, REGISTRATION_TIMESTAMP, ROLE_USER, ROLE_WORKER, ROLE_VINTNER, ROLE_ADMIN)
VALUES
(1, 'John', 'Doe', 'johndoe', 'johndoe@email.com', 'hashed_password_1', 'salt_1', '123456789', 'New York', '10001', '5th Ave', '101', '2023-10-01 12:00:00', TRUE, FALSE, TRUE, FALSE),
(2, 'Jane', 'Smith', 'janesmith', 'janesmith@email.com', 'hashed_password_2', 'salt_2', '987654321', 'Los Angeles', '90001', 'Sunset Blvd', '202', '2023-10-02 14:00:00', TRUE, TRUE, FALSE, FALSE),
(3, 'Alice', 'Brown', 'alicebrown', 'alicebrown@email.com', 'hashed_password_3', 'salt_3', '555555555', 'Chicago', '60001', 'Lake Shore Dr', '303', '2023-10-03 16:00:00', FALSE, TRUE, FALSE, TRUE);

-- task table
INSERT INTO TASK (ID, NAME, STATE, CREATION_TIMESTAMP, LAST_CHANGE_TIMESTAMP, PLANNED_EXECUTION_TIMESTAMP, EXECUTION_TIMESTAMP, NOTE, ID_TASK_ISSUER, ID_TASK_CONFIRMER)
VALUES
(1, 'Harvest Grapes', 'PLANNED', '2023-10-01 10:00:00', '2023-10-01 10:00:00', '2023-10-05 08:00:00', NULL, 'Harvest the grapes in the vineyard', 1, NULL),
(2, 'Bottle Wine', 'FINISHED', '2023-10-02 11:00:00', '2023-10-02 12:00:00', '2023-10-07 10:00:00', '2023-10-07 09:00:00', 'Bottling the wine from last harvest', 2, 3),
(3, 'Wine Quality Check', 'CANCELED', '2023-10-03 15:00:00', '2023-10-03 16:00:00', '2023-10-10 10:00:00', NULL, 'Quality check canceled due to logistics issues', NULL, NULL);

-- picture table
INSERT INTO PICTURE (ID, NAME, LINK, DESCRIPTION)
VALUES
(1, 'Vineyard View', 'https://example.com/vineyard1.jpg', 'Beautiful vineyard view during sunset'),
(2, 'Wine Bottle', 'https://example.com/winebottle.jpg', 'A bottle of red wine ready for sale'),
(3, 'Harvesting Grapes', 'https://example.com/harvest.jpg', 'Harvesting grapes in the field');

-- wine table 
INSERT INTO WINE (ID, ID_LIQUIDATOR, ID_SELL_APPROVER, ID_PICTURE, YEAR, ALCOHOL_PERCENTAGE, TYPE, COLOR, GRADE, STATE, VOLUME, BOTTLE_PRICE, CURRENT_PRICE, BOTTLES_LEFT, LEFT_SUGAR, ACID_CONTENTS, SALES_DESCRIPTION, PRODUCTION_NOTE)
VALUES
(1, 1, 3, 2, 2021, 14, 'DRY', 'RED', 'CABINET', 'PRODUCTION', 1000, 15, 20, 500, 5, 6, 'A robust red wine perfect for dinners', 'Grown in the heart of the vineyard'),
(2, 2, 3, 1, 2020, 13, 'HALF_DRY', 'WHITE', 'LATE', 'BOTTLED', 750, 12, 18, 200, 3, 5, 'A smooth white wine with a hint of sweetness', 'Made from late-harvested grapes'),
(3, 3, 1, 3, 2019, 12, 'SWEET', 'PINK', 'NONE', 'SALE', 500, 10, 15, 300, 8, 7, 'A sweet rosé wine, perfect for light meals', 'Produced from selected varieties');

-- vineYard table 
INSERT INTO VINEYARD (ID, ID_PICTURE, NAME, LOCATION, DESCRIPTION)
VALUES
(1, 1, 'Golden Vineyards', 'Napa Valley, CA', 'A beautiful vineyard known for its rich reds'),
(2, 2, 'Sunny Vineyards', 'Sonoma, CA', 'A sunny vineyard famous for its white wines'),
(3, 3, 'Riverbank Vineyards', 'Bordeaux, France', 'A small vineyard located near the riverbank');

-- WINE_VARIETY table 
INSERT INTO WINE_VARIETY (ID, ID_PICTURE, NAME, DESCRIPTION)
VALUES
(1, 1, 'Cabernet Sauvignon', 'A full-bodied red wine with high tannins'),
(2, 2, 'Chardonnay', 'A popular white wine with fruity notes'),
(3, 3, 'Pinot Noir', 'A light-bodied red wine with earthy notes');

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
