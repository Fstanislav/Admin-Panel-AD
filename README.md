# AD Control Panel — веб-панель управления пользователями Active Directory

Современное, безопасное и удобное веб-приложение для просмотра и управления пользователями Active Directory: авторизация, список пользователей и смена пароля через модальное окно.

## Возможности
- Авторизация администратора (логин/пароль, bcrypt)
- Дашборд с быстрым доступом к списку
- Список пользователей из AD (LDAP): ФИО, sAMAccountName, только включённые
- Смена пароля пользователя через popup (AJAX), валидация на клиенте и сервере
- Защита CSRF, XSS, безопасные заголовки, логирование действий
- Современный UI (адаптивная вёрстка), чистый PHP + JS без фреймворков

## Структура
```
/ad-panel/
├── index.php             → редирект на login
├── login.php             → страница входа
├── dashboard.php         → главная после авторизации
├── users.php             → список пользователей
├── password_change.php   → обработчик смены пароля (AJAX)
├── logout.php            → выход
├── config/
│   └── ldap.php          → настройки AD и админ-пароль
├── assets/
│   ├── css/style.css
│   ├── js/main.js
│   └── img/logo.png
├── includes/
│   ├── auth.php          → проверка авторизации + заголовки
│   ├── ldap.php          → функции работы с AD
│   └── functions.php     → сессии, CSRF, логи, валидация
└── logs/
    └── actions.log
```

## Требования окружения
- Web-сервер: Nginx или Apache
- PHP 8.0+ с расширением LDAP (php-ldap)
- Доступ к контроллеру домена по LDAP/LDAPS

## Установка
1. Скопируйте папку `ad-panel` в корень сайта или в подходящее место (`/var/www/html/ad-panel`).
2. Убедитесь, что у веб-сервера есть права на запись в `ad-panel/logs/actions.log`.
3. Установите и включите расширение LDAP для PHP (пример для Ubuntu):
   - Apache: `sudo apt install php-ldap && sudo systemctl restart apache2`
   - Nginx (php-fpm): `sudo apt install php-ldap && sudo systemctl restart php*-fpm`
4. Настройте конфигурацию в `ad-panel/config/ldap.php`:
   - `admin.username` и `admin.password_hash` (замените хеш)
   - Параметры `ldap.host`, `ldap.port`, `ldap.base_dn`, `ldap.service_dn`, `ldap.service_password`
   - Для смены паролей используйте `ldaps://` или LDAP + StartTLS

## Смена пароля администратора
- Сгенерируйте bcrypt-хеш (любым способом):
  - PHP CLI: `php -r 'echo password_hash("NEW_PASS", PASSWORD_BCRYPT), PHP_EOL;'`
  - Python (при установленном bcrypt):
    ```bash
    python3 - << 'PY'
    import bcrypt; print(bcrypt.hashpw(b'NEW_PASS', bcrypt.gensalt()).decode())
    PY
    ```
- Вставьте полученный хеш в `config/ldap.php` в поле `password_hash`.

## Конфигурация LDAP
- Рекомендуется использовать `ldaps://` на порту 636, либо `ldap://` + StartTLS
- Необходимо указать сервисную учётную запись с правами на смену паролей
- `base_dn` — базовый DN для поиска пользователей (например, `DC=example,DC=com`)

## Безопасность
- Пароль администратора хранится только в виде bcrypt-хеша
- CSRF-токены для форм, экранирование вывода, строгие заголовки
- Сессии с `httponly`, `samesite=Lax`, и переключением `secure` под HTTPS
- Простейшая защита от брутфорса: задержка 1–3 секунды при ошибках входа

## Логи
- Все важные действия пишутся в `logs/actions.log` в формате JSON (UTC)
- События: вход/выход, ошибки LDAP, смена пароля

## Развёртывание (пример Nginx)
Пример server block (замените пути и домены):
```nginx
server {
    listen 80;
    server_name ad.example.com;

    root /var/www/html;
    index index.php index.html;

    location /ad-panel/ {
        try_files $uri $uri/ /ad-panel/index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
}
```

## Использование
1. Откройте `/ad-panel/login.php`.
2. Введите логин/пароль администратора (по умолчанию `admin`/`admin`, замените в проде!).
3. Перейдите в «Настройки» и заполните параметры LDAP (host, port, base_dn, service_dn, service_password). Нажмите «Проверить подключение» и убедитесь, что статус — «подключено». Сохраните.
4. На дашборде нажмите «Список пользователей».
5. В таблице нажмите «Редактировать» → откроется модальное окно.
6. Введите новый пароль и подтвердите → «Сохранить». При успехе появится сообщение.

## Примечания
- Адаптивная таблица: на мобильных отображение карточками
- В коде нет SQL; валидация и экранирование присутствуют
- Для смены пароля в AD требуется зашифрованный канал (LDAPS или StartTLS)
