# BaksDev Delivery Transport

[![Version](https://img.shields.io/badge/version-7.2.7-blue)](https://github.com/baks-dev/delivery-transport/releases)
![php 8.4+](https://img.shields.io/badge/php-min%208.4-red.svg)

Модуль парка автомобилей доставки заказов

## Установка

``` bash
$ composer require baks-dev/delivery-transport
```

## Дополнительно

Установка конфигурации и файловых ресурсов:

``` bash
$ php bin/console baks:assets:install
```

Изменения в схеме базы данных с помощью миграции

``` bash
$ php bin/console doctrine:migrations:diff

$ php bin/console doctrine:migrations:migrate
```

## Тестирование

``` bash
$ php bin/phpunit --group=delivery-transport
```

## Лицензия ![License](https://img.shields.io/badge/MIT-green)

The MIT License (MIT). Обратитесь к [Файлу лицензии](LICENSE.md) за дополнительной информацией.
