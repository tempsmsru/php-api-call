# php-api-call
Простая базовая обертка над API сервиса tempsms.ru

# Пример использования
```php
use tempsmsru\api\ApiCall;

// API хост
$host = "http://tempsms.ru/api";
// ID приложения, полученный в личном кабинете пользователя
$app_id = -1;
// Секретный ключ, выданный при создании приложения в личном кабинете
$secret = "some_secret_from_user_cabinet";
// Создаем инстанс
$api = new ApiCall($host, $app_id, $secret);
// Дергаем тестовый метод, доступный для незарегистрированного пользователя
$is_true = $api->postBool("test/only-guest");
// Дергаем тестовый метод, доступный только для зарегистрированного пользователя
$is_true = $api->postBool("test/only-registered");
```