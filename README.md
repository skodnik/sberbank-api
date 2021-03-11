Обработчик [уведомлений обратного вызова платёжного шлюза СберБанка](https://securepayments.sberbank.ru/wiki/doku.php/integration:api:callback:start)

## Требования

- PHP 7.4 и выше
- [SSL-сертификат](https://securepayments.sberbank.ru/wiki/doku.php/integration:api:callback:start#требования_к_ssl-сертификатам_сайта_продавца)
- `php-json` расширение
- `ext-openssl` расширение

## Установка

```bash
$ composer require 'vlsv/sberbank-api'
```

## Использование

#### Уведомления без контрольной суммы

Данный тип уведомлений содержит только сведения о заказе - потенциально продавец рискует принять уведомление, отправленное злоумышленником, за подлинное.

```php
<?php

use Vlsv\SberBankApi\CallbackNotification;

require __DIR__ . '/vendor/autoload.php';

$payload_json = $_GET;

try {
    $notification = new CallbackNotification($payload_json);
} catch (\UnexpectedValueException $e) {
    $exception_message = $e->getMessage();
}
$order_number = $notification->getOrderNumber();

if ($notification->isDeposited()) {
    echo 'Проведена полная авторизация суммы заказа ' . $order_number;
}

if ($notification->isRefunded()) {
    echo 'По заказу ' . $order_number . ' была проведена операция возврата ';
}
```

#### Уведомления с контрольной суммой

Такие уведомления, помимо сведений о заказе, содержат аутентификационный код. Аутентификационный код представляет собой контрольную сумму сведений о заказе. Эта контрольная сумма позволяет убедиться, что callback-уведомление действительно было отправлено платёжным шлюзом.

Существует два способа реализации callback-уведомлений с контрольной суммой:

##### Симметричный приватный ключ
```php
$symmetric_private_key = 'symmetric private key';

if ($notification->isSymmetricKeyValidationSuccessful($symmetric_private_key)) {
    echo 'Подлинность данных подтверждена';
}
```

##### Асимметричный публичный ключ
```php
$asymmetric_public_key = 'asymmetric public key';

if ($notification->isAsymmetricKeyValidationSuccessful($asymmetric_public_key)) {
    echo 'Подлинность данных подтверждена';
}
```

[Подробнее про типы уведомлений](https://securepayments.sberbank.ru/wiki/doku.php/integration:api:callback:start#типы_уведомлений)
