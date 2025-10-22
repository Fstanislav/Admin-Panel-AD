# AD Control Panel — Панель управления пользователями Active Directory

Современная веб-панель (PHP + JS) для просмотра списка пользователей AD и смены паролей через всплывающее модальное окно.

## Возможности
- Авторизация администратора (локальный учёт admin/admin по умолчанию, пароль хранится в bcrypt)
- Дашборд с ссылкой на список пользователей
- Список включённых пользователей AD (LDAP), сортировка по ФИО
- Смена пароля пользователя в AD через popup (unicodePwd)
- (Упрощённый режим) Без CSRF, валидация пароля, экранирование вывода
- Логирование действий в `logs/actions.log`
- Адаптивный современный UI без фреймворков

## Структура
```
/ad-panel/
├── index.php             → редирект на login
├── login.php             → страница входа
├── dashboard.php         → главная после авторизации
├── users.php             → список пользователей + popup
├── password_change.php   → эндпоинт смены пароля (JSON)
├── logout.php            → выход
├── config/
│   └── ldap.php          → настройки AD и админ-пароль
├── assets/
│   ├── css/style.css
│   ├── js/main.js
│   └── img/
├── includes/
│   ├── functions.php     → утилиты: сессии, csrf, валидаторы, лог
│   ├── auth.php          → авторизация сессии
│   └── ldap.php          → LDAP-функции
└── logs/
    └── actions.log
```

## Требования
- PHP 8.0+
- Расширение LDAP: `php-ldap`
- Веб-сервер (Nginx/Apache) с поддержкой PHP
- Доступ к контроллеру домена AD (порт 389/636)

## Установка
1. Скопируйте каталог `ad-panel` на ваш сервер (например, `/var/www/html/ad-panel`).
2. Убедитесь, что PHP имеет расширение LDAP.
3. Выдайте права на запись лога (минимально):
   ```bash
   chown -R www-data:www-data /var/www/html/ad-panel/logs
   chmod 750 /var/www/html/ad-panel/logs
   ```
4. Откройте и настройте `ad-panel/config/ldap.php` (упрощённая схема):
   - `ldap.host` = адрес контроллера домена (можно IP, например `ldap://90.156.198.61`)
   - `ldap.port` = 389 (или используйте `ldaps://` и 636)
   - `ldap.admin_username` = логин администратора AD (лучше UPN: `user@example.local`, можно `DOMAIN\\user` или просто `user`)
   - `ldap.admin_password` = пароль администратора AD
   - `ldap.domain` = (опционально) домен для автоопределения Base DN, если имя пользователя без домена
   - `ldap.base_dn` = (опционально) можно оставить пустым — вычислится из `ldap.domain`
   - `admin.username` и `admin.password_hash` (измените пароль локального входа в панель)
   Пример генерации bcrypt-хэша:
   ```php
   <?php echo password_hash('NEW_ADMIN_PASSWORD', PASSWORD_BCRYPT); ?>
   ```
5. (Рекомендуется) Включите HTTPS. Приложение автоматически ставит secure/httponly/samesite для cookie сессии.

## Настройка веб-сервера
- Nginx (пример):
  ```nginx
  location /ad-panel/ {
    index index.php;
    try_files $uri $uri/ /ad-panel/index.php?$query_string;
  }
  location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock; # поправьте под вашу версию
  }
  ```

## Использование
- Перейдите на `/ad-panel/login.php`
- Введите логин/пароль (по умолчанию: admin/admin)
- На странице пользователей нажмите «Редактировать», в модальном окне введите новый пароль и сохраните

## Безопасность
- Пароль локального администратора панели хранится в виде bcrypt-хэша в `config/ldap.php`.
- (Упрощённый режим) CSRF отключён по запросу — не размещайте панель в общедоступной сети без дополнительной защиты.
- Экранирование вывода функцией `e()`.
- Валидация пароля на фронте и бэке (мин. 8 символов, буквы и цифры).
- Смена пароля в AD выполняется от имени указанной учётной записи через `ldap_mod_replace` атрибута `unicodePwd` (UTF-16LE, в кавычках).

## Логи
- Все попытки входа и смены паролей пишутся в `logs/actions.log` в формате:
  ```
  2025-01-01T12:00:00+00:00	login	success	admin	203.0.113.5	{"extra":"..."}
  ```

## Отладка
- Если список пользователей не грузится, проверьте соединение с AD, `ldap.base_dn`, `bind_dn`, права сервисной учётной записи.
- Для LDAPS используйте `ldap.use_tls = true` и слушайте порт 389 с StartTLS (или 636 с ldaps:// в `ldap.host`).

## Лицензия
MIT
