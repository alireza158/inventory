<?php

namespace App\Support;

class PhoneModelCatalog
{
    public static function brands(): array
    {
        return [
            'Apple (iPhone)' => [
                'iPhone 11','iPhone 11 Pro','iPhone 11 Pro Max','iPhone SE 2020',
                'iPhone 12','iPhone 12 mini','iPhone 12 Pro','iPhone 12 Pro Max',
                'iPhone 13','iPhone 13 mini','iPhone 13 Pro','iPhone 13 Pro Max','iPhone SE 2022',
                'iPhone 14','iPhone 14 Plus','iPhone 14 Pro','iPhone 14 Pro Max',
                'iPhone 15','iPhone 15 Plus','iPhone 15 Pro','iPhone 15 Pro Max',
                'iPhone 16','iPhone 16 Plus','iPhone 16 Pro','iPhone 16 Pro Max',
            ],
            'Samsung' => [
                'Galaxy A04','Galaxy A05','Galaxy A05s','Galaxy A06','Galaxy A14 4G','Galaxy A14 5G','Galaxy A15 4G','Galaxy A15 5G','Galaxy A24','Galaxy A25','Galaxy A34','Galaxy A35','Galaxy A54','Galaxy A55',
                'Galaxy M14','Galaxy M34','Galaxy M54',
                'Galaxy S21 FE','Galaxy S22','Galaxy S22 Plus','Galaxy S22 Ultra','Galaxy S23','Galaxy S23 FE','Galaxy S23 Plus','Galaxy S23 Ultra','Galaxy S24','Galaxy S24 Plus','Galaxy S24 Ultra',
                'Galaxy Z Flip4','Galaxy Z Flip5','Galaxy Z Flip6','Galaxy Z Fold4','Galaxy Z Fold5','Galaxy Z Fold6',
                'Galaxy Note 20','Galaxy Note 20 Ultra',
            ],
            'Xiaomi' => [
                'Redmi 12','Redmi 12C','Redmi 13','Redmi 13C','Redmi Note 11','Redmi Note 11S','Redmi Note 12','Redmi Note 12 5G','Redmi Note 12 Pro','Redmi Note 12 Pro 5G','Redmi Note 13 4G','Redmi Note 13 5G','Redmi Note 13 Pro 4G','Redmi Note 13 Pro 5G','Redmi Note 13 Pro Plus',
                'Poco X5','Poco X5 Pro','Poco X6','Poco X6 Pro','Poco M5','Poco M6 Pro','Poco F5','Poco F5 Pro','Poco F6','Poco F6 Pro',
                'Xiaomi 12','Xiaomi 12T','Xiaomi 12T Pro','Xiaomi 13','Xiaomi 13T','Xiaomi 13T Pro','Xiaomi 14','Xiaomi 14T','Xiaomi 14T Pro',
            ],
            'Realme' => [
                'Realme C31','Realme C33','Realme C35','Realme C51','Realme C53','Realme C55','Realme C61','Realme C67',
                'Realme 9','Realme 9 Pro','Realme 9 Pro Plus','Realme 10','Realme 10 Pro','Realme 10 Pro Plus','Realme 11','Realme 11 Pro','Realme 11 Pro Plus','Realme 12','Realme 12 Plus','Realme 12 Pro','Realme 12 Pro Plus',
                'Realme GT 2','Realme GT 2 Pro','Realme GT Neo 3','Realme GT 6',
                'Narzo 50','Narzo 60','Narzo 70',
            ],
            'Huawei' => [
                'Nova 9','Nova 10','Nova 10 SE','Nova 11i','Nova Y61','Nova Y70','Nova Y71','Nova Y90',
                'P40','P40 Pro','P50','P50 Pro','P60 Pro','Pura 70','Pura 70 Pro',
                'Mate 40 Pro','Mate 50','Mate 50 Pro','Mate 60 Pro',
                'Y5p','Y6p','Y7a','Y8p','Y9a',
            ],
            'Honor' => [
                'Honor X5','Honor X6','Honor X6a','Honor X7','Honor X8','Honor X8a','Honor X9','Honor X9a','Honor X9b',
                'Honor 70','Honor 90','Honor 90 Lite','Honor 100',
                'Honor Magic4 Pro','Honor Magic5 Lite','Honor Magic5 Pro','Honor Magic6 Pro',
                'Honor 50','Honor 50 Lite','Honor 200','Honor 200 Pro',
            ],
        ];
    }
}
