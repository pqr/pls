# Выводы из исправлений

## 2026-11-02

- При изменении HTTP-доступа к 1С проверять оба клиента: отправку счёта в `CInvoice` и диагностику `PingOneC`. Входящий `onecAuthToken` относится к импорту из 1С и не заменяет исходящую HTTP Basic Auth.
- В этом checkout нет `phpstan-unlimited.neon`: используется `phpstan.neon`. Установленный PHPStan несовместим с PHP 8.4; локальная веб-проверка также требует таблицу `pls_dev.phpsession`. HTTP 200 сам по себе не подтверждает работоспособность страницы — необходимо проверять тело ответа на fatal error.
- У `konsfin.atsystems.ru` различать внешнюю Basic Auth Apache (`realm="Restricted Area"`) и вход в базу 1С (`realm="1C:Enterprise 8.3"`). Проверка с сервера `pls` показала, что настроенные данные проходят Apache, но `/pgl18/hs/CreateCustomerInvoice` возвращает IIS 401.5 из `IsapiModule`. Возможную передачу внешнего заголовка `Authorization` внутрь 1С проверять по конфигурации прокси и публикации, не исправлять повторным перезапуском PHP-FPM.
