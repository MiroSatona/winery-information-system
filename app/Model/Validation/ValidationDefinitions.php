<?php

declare(strict_types=1);

namespace App\Model\Validation;


final class ValidationDefinitions
{
    public const USERNAME_PATTERN = '^[a-zA-Z0-9_]{4,30}$';
    public const EMAIL_PATTERN = '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$';
    public const POSTAL_CODE_PATTERN = '^[0-9]{5}$';
    public const PHONE_NUMBER_PATTERN = '\+?[0-9]{9}';
    

    public const NAME_LENGTH_MIN = 1;
    public const NAME_LENGTH_MAX = 30;
    public const SURNAME_LENGTH_MIN = 1;
    public const SURNAME_LENGTH_MAX = 30;
    public const USERNAME_LENGTH_MIN = 4;
    public const USERNAME_LENGTH_MAX = 30;
    public const EMAIL_LENGTH_MAX = 255;
    public const PHONE_LENGTH_MIN = 9;
    public const PHONE_LENGTH_MAX = 9;
    public const CITY_LENGTH_MIN = 1;
    public const CITY_LENGTH_MAX = 30;
    public const STREET_LENGTH_MIN = 1;
    public const STREET_LENGTH_MAX = 30;
    public const HOUSE_NUMBER_LENGTH_MIN = 1;
    public const HOUSE_NUMBER_LENGTH_MAX = 10;
    public const POSTAL_CODE_LENGTH_MIN = 5;
    public const POSTAL_CODE_LENGTH_MAX = 5;
    public const PASSWORD_LENGTH_MIN = 8;
    public const TASK_NAME_LENGTH_MIN = 1;
    public const TASK_NAME_LENGTH_MAX = 30;
    public const PICTURE_NAME_LENGTH_MIN = 1;
    public const PICTURE_NAME_LENGTH_MAX = 30;
    public const WINE_NAME_LENGTH_MIN = 1;
    public const WINE_NAME_LENGTH_MAX = 50;
    public const VINEYARD_NAME_LENGTH_MIN = 1;
    public const VINEYARD_NAME_LENGTH_MAX = 30;
    public const YEAR_MIN = 0;
    public const YEAR_MAX = 32767;
    public const WINE_VARIETY_NAME_LENGTH_MIN = 1;
    public const WINE_VARIETY_NAME_LENGTH_MAX = 20;
    public const TREATMENT_CATEGORY_LENGTH_MIN = 1;
    public const TREATMENT_CATEGORY_LENGTH_MAX = 20;
    public const TREATMENT_NAME_LENGTH_MIN = 1;
    public const TREATMENT_NAME_LENGTH_MAX = 20;
    public const TREATMENT_UNIT_LENGTH_MIN = 1;
    public const TREATMENT_UNIT_LENGTH_MAX = 10;
    public const ALCOHOL_CONTENT_MIN = 0.0;
    public const ALCOHOL_CONTENT_MAX = 100.0;
    public const PRICE_MIN = 0.0;
    public const PRICE_MAX = 99999999.99;
    public const VOLUME_MIN = 0;
    public const VOLUME_MAX = 32767;
    public const TREATMENT_QUANTITY_MIN = 0.0;
    public const TREATMENT_QUANTITY_MAX = 99999999.99;
    public const HARVEST_WEIGHT_MIN = 0.0;
    public const HARVEST_WEIGHT_MAX = 99999999.99;
    public const SUGAR_CONTENT_MIN = 0;
    public const SUGAR_CONTENT_MAX = 999.99;
    public const ACID_CONTENTS_MIN = 0.0;
    public const ACID_CONTENTS_MAX = 999.99;
    public const BOTTLE_VOLUME_MIN = 0.0;
    public const BOTTLE_VOLUME_MAX = 9999.99;

}