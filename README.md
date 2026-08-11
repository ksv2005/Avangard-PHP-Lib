# AVANGARD PHP Client
Библиотека для интеграции с API V4 банка Авангард. Реализует основные запросы к API банка. Подробное описание API
смотрите в технической документации.

## Установка с помощью composer

1. В корне директории, где собираетесь установить библиотеку, создайте файл <b>composer.json</b> со следующим содержимым:
```json
{
    "require": {
        "avangard/api": "dev-master"
    },
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/avangardDeveloper/Avangard-PHP-Lib"
        }
    ]
}
```

2. В этой же директории выполните команду
```php
composer install
```

## Использование

### Подключение библиотеки к проекту

Чтобы использовать методы библиотеки в своём коде, необходимо подключить скрипт автозагрузки классов и создать объект
класса `ApiClient`
```php
require_once ("vendor/autoload.php");
use Avangard\ApiClient;

$apiClient = new ApiClient($shopId, $shopPassword, $shopSign, $serverSign, $boxAuth, $proxy);
```

### Параметры конструктора
- `shopId` - ID интернет-магазина в банковской системе*
- `shopPassword` - пароль интернет-магазина в банковской системе*
- `shopSign` - подпись интернет-магазина в банковской системе*
- `serverSign` - подпись ответов банка*
- `boxAuth` - объект, который содержит авторизационные данные для отправки чеков
в онлайн-кассу. Если передать `null`, то отправка чеков производиться не будет. [Подробнее](#generateBoxAuth)
- `proxy` - http url прокси сервера (если используется). По умолчанию `null`

*указанные параметры выдаются техподдержкой банка при заключении договора на интернет-эквайринг

**ВНИМАНИЕ!**  
Все методы данной библиотеки следует использовать в конструкции try/catch:
```php
try {
    // All methods here...
} catch (\Exception $e) {
    if ($debug) {
        \Avangard\Lib\Logger::log($e);
        // Your custom logging here...
    }
}
```
Метод `\Avangard\Lib\Logger::log` рекомендуется использовать с флагом `$debug` который может, например, 
задаваться в административной панели сайта. Этот метод отсылает отчёты об ошибках в telegram разработчика.

## Заказы и оплата

1. `prepareForms($order, $type)` - подготавливает параметры для формы оплаты.

Параметры:
- ```php
    $order = [
        'AMOUNT' => 'number, обязательный',                     // сумма к оплате в копейках
        'ORDER_NUMBER' =>  'string, обязательный',              // номер заказа в интернет-магазине
        'ORDER_DESCRIPTION' => 'string, обязательный',          // описание заказа в интернет-магазине
        'LANGUAGE' => 'string, обязательный, по умолчанию RU',  // язык описания заказа в интернет-магазине
        'BACK_URL' => 'string, обязательный',                   // ссылка безусловного редиректа
        'BACK_URL_OK' => 'string',                              // ссылка успешного редиректа
        'BACK_URL_FAIL' => 'string',                            // ссылка НЕуспешного редиректа
        'CLIENT_NAME' => 'string',                              // имя плательщика
        'CLIENT_ADDRESS' => 'string',                           // физический адрес плательщика
        'CLIENT_EMAIL' => 'string',                             // email плательщика
        'CLIENT_PHONE' => 'string',                             // телефон плательщика
        'CLIENT_IP' => 'string'                                 // ip-адрес плательщика  
    ];
    ```
- ```php
    $type =
        ApiClient::HOST2HOST    // Регистрирует оплату в интернет-эквайринге и возвращает TICKET-параметр для последующей оплаты заказа
        ApiClient::POSTFORM     // Подготавливает параметры для HTML формы оплаты, показываемой на стороне клиента (часто требуется для CMS)
        ApiClient::GETURL       // Регистрирует оплату в интернет-эквайринге и возвращает ссылку для последующей оплаты заказа
    ```
    
Возвращаемые значения:
- `$type = ApiClient::HOST2HOST`:
```php
[
    "URL" => "https://pay.techsitea.ru/iacq/pay",
    "METHOD" => "get",
    "INPUTS" => [
        "TICKET" => "JGceLCtt000012682687LskJXuIpbfmpgeeKgkcj"
    ]
]
```
- `$type = ApiClient::POSTFORM`:
```php
[
  "URL" => "https://pay.techsitea.ru/iacq/post",
  "METHOD" => "post",
  "INPUTS" => [
    "SHOP_ID" => "1",
    "SHOP_PASSWD" => "pass",
    "AMOUNT" => 1000,
    "ORDER_NUMBER" => "sa12",
    "ORDER_DESCRIPTION" => "My desc",
    "BACK_URL" => "http://example.ru/payments/avangard/?result=success",
    "LANGUAGE" => "RU",
    "SIGNATURE" => "1EBE4761D9B165D8FF784803686AF511",
  ]
]
```
- `$type = ApiClient::GETURL`:
```php
"https://pay.techsitea.ru/iacq/pay?ticket=JGceLCtt000012682687LskJXuIpbfmpgeeKgkcj"
```

Пример HOST2HOST/GETURL:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;

try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign',
        null
    );
    
    $order = [
        'AMOUNT' => 1000,
        'ORDER_NUMBER' => 'sa12',
        'ORDER_DESCRIPTION' => 'My desc',
        'BACK_URL' => 'http://example.ru/payments/avangard/?result=success'
    ];
    
    $result = $apiClient->request->prepareForms($order, ApiClient::HOST2HOST);
    
    print_r($result);
} catch (\Exception $e) {
    if ($debug) {
        \Avangard\Lib\Logger::log($e);
    }
}
```

Пример POSTFORM:
```php
<?php
require_once "vendor/autoload.php";

use Avangard\ApiClient;

function getFormData($orderNumber, $orderDescription, $amount)
{
    $debug = true;
    
    try {
        $apiClient = new ApiClient(
            1,
            'shop password',
            'shop sign',
            'server sign',
            null
        );
        
        $order = [
            'AMOUNT' => $amount,
            'ORDER_NUMBER' => $orderNumber,
            'ORDER_DESCRIPTION' => $orderDescription,
            'BACK_URL' => 'http://example.ru/payments/avangard/?result=success'
        ];
        
        $result = $apiClient->request->prepareForms($order, ApiClient::POSTFORM);
        
        return $result;
    } catch (\Exception $e) {
        if ($debug) {
            \Avangard\Lib\Logger::log($e);
        }
    }
}

$orderNumber = 'sa12';
$orderDescription = 'My desc';
$amount = 1000;

$formData = getFormData($orderNumber, $orderDescription, $amount);
?>

<form id="form" action="<?=$formData['URL'];?>" method="<?=$formData['METHOD'];?>">
    <?php foreach ($formData['INPUTS'] as $name => $value):?>
        <input type="hidden" name="<?=$name;?>" value="<?=$value;?>">
    <?php endforeach;?>
    <button type="submit">Перейти к оплате</button>
</form>
```

2. `orderRegister($order)` - регистрирует оплату в интернет-эквайринге и возвращает TICKET-параметр для дальнейшей
оплаты.

Параметры:
```php
$order = [
    'AMOUNT' => 'number, обязательный',                     // сумма к оплате в копейках
    'ORDER_NUMBER' =>  'string, обязательный',              // номер заказа в интернет-магазине
    'ORDER_DESCRIPTION' => 'string, обязательный',          // описание заказа в интернет-магазине
    'LANGUAGE' => 'string, обязательный, по умолчанию RU',  // язык описания заказа в интернет-магазине
    'BACK_URL' => 'string, обязательный',                   // ссылка безусловного редиректа
    'BACK_URL_OK' => 'string',                              // ссылка успешного редиректа
    'BACK_URL_FAIL' => 'string',                            // ссылка НЕуспешного редиректа
    'CLIENT_NAME' => 'string',                              // имя плательщика
    'CLIENT_ADDRESS' => 'string',                           // физический адрес плательщика
    'CLIENT_EMAIL' => 'string',                             // email плательщика
    'CLIENT_PHONE' => 'string',                             // телефон плательщика
    'CLIENT_IP' => 'string'                                 // ip-адрес плательщика  
];
```

Возвращаемое значение:
```php
[
  "TICKET" => "xQElJQhi000012682701rKuBUpngKsIsUBKPBmfM"
]
```

Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;
    
try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign',
        null
    );
    
    $order = [
        'AMOUNT' => 1000,
        'ORDER_NUMBER' => 'sa12',
        'ORDER_DESCRIPTION' => 'My desc',
        'BACK_URL' => 'http://example.ru/payments/avangard/?result=success'
    ];
    
    $result = $apiClient->request->orderRegister($order);
    
    print_r($result);
} catch (\Exception $e) {
    if ($debug) {
        \Avangard\Lib\Logger::log($e);
    }
}
```

3. `getOrderByTicket($ticket)` - получить информацию об оплате по TICKET-параметру.

Параметры:
- `string $ticket` - уникальный идентификатор оплаты в интернет-эквайринге банка

Пример возвращаемого массива:
```php
[
    'id' => 1234567890,
    'method_name' => 'SCR',
    'auth_code' => 'ABC123',
    'status_code' => 5,
    'status_desc' => 'Авторизация успешно завершена',
    'status_date' => '2012-04-23T12:47:00+04:00',
]
```

Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;
    
try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign',
        null
    );
    
    $result = $apiClient->request->getOrderByTicket("UWyNLGVh000012669958czZpckkboKNDpUysDhlL");
    
    print_r($result);
} catch (\Exception $e) {
    if ($debug) {
        \Avangard\Lib\Logger::log($e);
    }
}
```

## Callback запросы из банка

1. `isCorrectHash($params)` - проверяет подпись callback запроса из банка.

Параметры:
- `array $params` - массив входящих параметров запроса

Возвращаемые значения:  
`true`, если подпись верна, иначе `false`

Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;

$_REQUEST = [
    'id' => '12663423',
    'signature' => '07EB5673A9ECD4506C112B3EE3E3AF80',
    'method_name' => 'D3S',
    'shop_id' => '1',
    'ticket' => 'OWXZAkWg000012663423irlhpRKbAevpPsymgoDu',
    'status_code' => '3',
    'auth_code' => '',
    'amount' => '2000',
    'card_num' => '546938******1152',
    'order_number' => 'sa12',
    'status_desc' => 'Исполнен',
    'status_date' => '2019-11-05 10:17:17.0',
    'refund_amount' => '0',
    'exp_mm' => '09',
    'exp_yy' => '22'
];

try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign',
        null
    );
    
    $result = $apiClient->request->isCorrectHash($_REQUEST);
    
    var_dump($result); // true или false
} catch (\Exception $e) {
    if ($debug) {
        \Avangard\Lib\Logger::log($e);
    }
}
```

2. `sendResponse()` - отправляет корректный код состояния ответа на callback запрос из банка, затем завершает выполнение 
скрипта. Если вы реализуете обработку callback запросов из банка, **необходимо всегда** вызывать данный метод после
успешной обработки запроса

Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;

$_REQUEST = array (
    'id' => '12663423',
    'signature' => '07EB5673A9ECD4506C112B3EE3E3AF80',
    'method_name' => 'D3S',
    'shop_id' => '1',
    'ticket' => 'OWXZAkWg000012663423irlhpRKbAevpPsymgoDu',
    'status_code' => '3',
    'auth_code' => '',
    'amount' => '200',
    'card_num' => '546938******1152',
    'order_number' => 'sa12',
    'status_desc' => 'Исполнен',
    'status_date' => '2019-11-05 10:17:17.0',
    'refund_amount' => '0',
    'exp_mm' => '09',
    'exp_yy' => '22'
);

try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign',
        null
    );
    
    if ($apiClient->request->isCorrectHash($_REQUEST)) {
    
        // Действия при получении callback запроса из банка...
    
        // Отправляем ответ, что callback запрос был успешно обработан
        $apiClient->request->sendResponse();
    }
} catch (\Exception $e) {
    if ($debug) {
        \Avangard\Lib\Logger::log($e);
    }
}
```

## Возврат средств и отмена оплаты

1. `orderRefund($ticket, $amount = null)` - производит частичное/полное возмещение денежных средств по конкретной оплате.  
Если оплата была совершена по QR коду (с помощью СБП), то после отправки запроса на возмещение денежных средств, метод
производит проверку статуса возврата, т.к. возврат по оплатам, совершённым по QR, производится асинхронно. Всего
осуществляется максимум 8 проверок статуса возврата, задержка между проверками 5 секунд

Параметры:
- `string $ticket` - уникальный идентификатор оплаты в интернет-эквайринге банка
- `number $amount` - сумма к возврату **в копейках**. Если не передавать данный параметр, то будет произведен полный
возврат денежных средств

Возвращаемое значение:
```php
[
    "transaction_id" => 124665
]
```

Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;

try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign',
        null
    );

    $result = $apiClient->request->orderRefund("UWyNLGVh000012669958czZpckkboKNDpUysDhlL", 10000);
    
    print_r($result);
} catch (\Exception $e) {
    if ($debug) {
        \Avangard\Lib\Logger::log($e);
    }
}
```

2. `orderCancel($ticket)` - отменяет ранее зарегистрированную, но ещё не оплаченную попытку оплаты. Этот
метод нужно вызывать, если по какой-то причине необходимо запретить пользователю оплату по заказу.
 
Параметры:
- `string $ticket` - уникальный идентификатор оплаты в интернет-эквайринге банка

Возвращаемое значение:  
`true`, если оплата была отменена
 
Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;

try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign',
        null
    );
    
    $order = [
        'AMOUNT' => 1000,
        'ORDER_NUMBER' => 'sa12',
        'ORDER_DESCRIPTION' => 'My desc',
        'BACK_URL' => 'http://example.ru/payments/avangard/?result=success'
    ];
    
    $registerResult = $apiClient->request->orderRegister($order);

    $cancelResult = $apiClient->request->orderCancel($registerResult['TICKET']);
    
    var_dump($cancelResult);
} catch (\Exception $e) {
    if ($debug) {
        \Avangard\Lib\Logger::log($e);
    }
}
```

## Операции по заказу

1. `getOpersByOrderNumber($order_number)` - получить список операций по номеру заказа в интернет-магазине.

Параметры:
- `string $order_number` - номер заказа в интернет-магазине

Пример возвращаемого массива:
```php
[
    [
        'id' => 1054751,
        'ticket' => '1234567890ABCDEABCDE12345678901234567890',
        'order_number' => '1',
        'status_code' => 1,
        'status_desc' => 'Обрабатывается',
        'status_date' => '2013-08-14T10:23:49+04:00',
        'amount' => 10000.0,
    ],
    [
        'id' => 1054752,
        'ticket' => '1234567890ABCDEABCDE12345678901234567811',
        'order_number' => '1',
        'status_code' => 1,
        'status_desc' => 'Обрабатывается',
        'status_date' => '2013-08-14T10:24:00+04:00',
        'amount' => 10000.0,
    ],
    [
        'id' => 1054753,
        'ticket' => '1234567890ABCDEABCDE12345678901234567822',
        'order_number' => '1',
        'method_name' => 'CVV',
        'status_code' => 2,
        'status_desc' => 'Отбракован',
        'status_date' => '2013-08-14T10:27:17+04:00',
        'amount' => 10000.0,
        'refund_amount' => 10000.0,
        'card_num' => '411111******1111',
        'exp_mm' => 12,
        'exp_yy' => 15,
    ]
]

```
 
Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;

try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign',
        null
    );

    $result = $new->request->getOpersByOrderNumber("sa12");
    
    print_r($result);
} catch (\Exception $e) {
    if ($debug) {
         \Avangard\Lib\Logger::log($e);
     }
}
```

2. `getOpersByDate($date)` - получить список операций за определённую дату.

Параметры:
 - `string $date` - дата
 
Возвращаемое значение:  
Возвращаемый массив полностью аналогичен методу `getOpersByOrderNumber`
 
Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;

$debug = true;

try {
    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign',
        null
    );

    $result = $apiClient->request->getOpersByDate("2019-11-06");
    
    print_r($result);
} catch (\Exception $e) {
    if ($debug) {
         \Avangard\Lib\Logger::log($e);
     }
}
```

## Отправка чеков в онлайн-кассу
Библиотека позволяет отправлять чеки в онлайн-кассу. На данный момент реализована интеграция с кассами АТОЛ Онлайн 
ФФД 1.05, АТОЛ Онлайн ФФД 1.2 и OrangeData.

### <a id="generateBoxAuth">Генерация авторизационных данных касс</a>

Конфигурацию для подключения к онлайн-кассе следует хранить в БД в виде JSON строки. Чтобы создать валидный JSON,
вы можете воспользоваться генератором авторизационных данных касс, входящим в состав данной библиотеки. Он расположен
по пути `vendor/avangard/api/src/generateBoxAuth/index.php`

1. `BoxAuthFactory::createBoxAuth($boxJson)` - возвращает объект с авторизационными данными для кассы `$boxAuth` для
его передачи в конструктор класса `ApiClient`

Параметры:
- `$boxJson` - JSON объект авторизационных данных для кассы

Возвращаемые значения:  
В зависимости от выбранной кассы:
- `AtolonlineV4` для АТОЛ Онлайн ФФД 1.05;
- `AtolonlineV5` для АТОЛ Онлайн ФФД 1.2;
- `Orangedata` для OrangeData;
- `null`, если касса не выбрана или не существует;

Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;
use Avangard\Lib\Box\BoxAuthFactory;

$debug = true;

try {
    $boxJson = $db->getBoxJson(); // Ваш метод получения JSON строки с авторизационными данными для кассы
    $boxAuth = BoxAuthFactory::createBoxAuth($boxJson);

    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign',
        $boxAuth
    );
    
    var_dump($apiClient->request->isBox()); // true, в случае успешного подключения к кассе
} catch (\Exception $e) {
    if ($debug) {
         \Avangard\Lib\Logger::log($e);
     }
}
```

**ВНИМАНИЕ!**  
Если объект класса `ApiCLient` был создан с параметром `$boxAuth`, отличным от `null`, то в конструкторе класса
производится попытка установки соединения с кассой. Если подключиться к кассе не удалось, то выбрасывается `Exception`, 
и дальнейшая работа скрипта прекращается

### Подготовка чека для отправки в онлайн кассу

Чек для отправки в онлайн кассу представляет собой объект класса `ReceiptEntity`
```php
$receipt = new ReceiptEntity($id, $time);
```

Параметры конструктора:
- `string $id` - номер заказа в интернет-магазине
- `int $time` - текущее время в виде timestamp

Другие параметры класса:
- `ClientEntity $client` - объект с информацией о покупателе
- `ReceiptItemEntity[] $items` - массив объектов с информацией по каждой позиции в чеке
- `float $total` - общая сумма покупки, включая доставку

Информация о покупателе представлена в виде объекта класса `ClientEntity`
```php
$client = new ClientEntity($name);
```

Параметры конструктора:
- `string $name` - ФИО покупателя

Другие параметры класса:
- `string $phone` - телефон покупателя
- `string $email` - email покупателя

Информация о позиции в чеке представлена в виде объекта класса `ReceiptItemEntity`
```php
$receiptItem = new ReceiptItemEntity($name, $price, $quantity, $sum);
```

Параметры конструктора:
- `string $name` - название товара
- `float $price` - цена товара
- `float $quantity` - количество товара
- `float $sum` - общая стоимость товаров (обычно, количество*цена)

Другие параметры класса:
- `string $payment_object` - объект расчёта

Чтобы добавить в чек доставку, воспользуйтесь методом `ReceiptItemEntity::delivery`
```php
$deliveryReceiptItem = ReceiptItemEntity::delivery($name, $price, $quantity, $sum);
```

Параметры метода аналогичны используемым в конструкторе `ReceiptItemEntity`. Отличие этого метода в том, что
в нём устанавливается `$payment_object = 'service'`, что соответствует объекту расчёта "Услуга". 

По умолчанию объект расчёта для каждой позиции в чеке берётся из JSON объекта с авторизационными данными для кассы, 
но если вам нужно добавить в чек позицию с иным объектом рассчёта, то после создания объекта `ReceiptItemEntity`
вызовите метод `setPaymentObject($paymentObject)` и передайте строковое значение объекта расчёта, как того требует
документация вашей онлайн кассы
```php
$receiptItem = new ReceiptItemEntity($name, $price, $quantity, $sum);

$receiptItem->setPaymentObject('commodity')
```

Чтобы подготовить чек для отправки в онлайн кассу, необходимо заполнить данные о компании, информацию по каждой позиции
в чеке и общую сумму покупки.

### Отправка чека в онлайн кассу

1. `sendBill($data)` - отправляет чек о покупке в онлайн кассу

Параметры:
- `ReceiptEntity $data` - подготовленный к отправке в онлайн кассу чек
 
Пример:
```php
<?php
require_once "vendor/autoload.php";
use Avangard\ApiClient;
use Avangard\Lib\Box\BoxAuthFactory;
use Box\DataObjects\ClientEntity;
use Box\DataObjects\ReceiptEntity;
use Box\DataObjects\ReceiptItemEntity;

$debug = true;

try {
    $boxJson = $db->getBoxJson(); // Ваш метод получения JSON строки с авторизационными данными для кассы
    $boxAuth = BoxAuthFactory::createBoxAuth($boxJson);

    $apiClient = new ApiClient(
        1,
        'shop password',
        'shop sign',
        'server sign',
        $boxAuth
    );
    
    // Перед отправкой чека необходимо проверять, есть ли подключение к кассе 
    if ($apiClient->request->isBox()) {
        $orderData = $_REQUEST['order'];
        
        // Создаём чек с номером заказа и текущим временем
        $receipt = new ReceiptEntity((string)$orderData['id'], time());
        
        // Создаём и заполняем объект данных о покупателе
        $client = new ClientEntity($orderData['client_firstname'] . ' ' . $orderData['client_lastname']);
        
        // Одно из двух полей phone или email должно быть обязательно заполнено
        $client->setPhone($order['phone']);
        $client->setEmail($order['email']);
        
        // Добавляем данные о покупателе в чек 
        $receipt->addClient($client);

        // Добавляем общую сумму заказа в чек
        $receipt->addTotal($order['total']);

        // Заполняем позиции в чеке
        foreach ($orderData['items'] as $product) {
            $receipt->addReceiptItem(
                new ReceiptItemEntity(
                    $product['name'],
                    $product['price'],
                    $product['quantity'],
                    $product['total']
                )
            );
        }

        // Если есть платная доставка, добавляем её в чек
        if (!empty($orderData['delivery'])) {
            $receipt->addReceiptItem(
                ReceiptItemEntity::delivery(
                    $orderData['delivery']['name'],
                    round($orderData['delivery']['price']),
                    1,
                    round($orderData['delivery']['price'])
                )
            );
        }

        // Отправляем чек в кассу
        $this->client->request->sendBill($receipt);
    }
} catch (\Exception $e) {
    if ($debug) {
         \Avangard\Lib\Logger::log($e);
     }
}
```

2. `refundBill($data)` - отправляет чек о возврате денежных средств в онлайн кассу

Параметры:
- `ReceiptEntity $data` - подготовленный к отправке в онлайн кассу чек

Использовать аналогично методу `sendBill($data)`.
