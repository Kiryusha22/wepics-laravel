# Wepics

Wepics — веб-сервис Image-Board, средство загрузки, обмена, хранение и просмотра альбомов изображений, социальным функционалом в виде выставления реакций-эмодзи на изображения, с функционалом категоризации и фильтрации картинок тегами и с возможностью ограничивать доступ пользователям или гостям к определённым альбомам.

## Установка (OSPanel)

Необходимо:
* Установить [git](https://git-scm.com/download/win)
* В настойках OSPanel во вкладке "Модули" установить PHP 8.1 или выше и MySQL 5.7 или выше
* В настойках OSPanel во вкладке "Сервер" установить "Свой Path + Win Path" для видимости команды `git`

Откройте консоль OSPanel и введите следующие команды (по очереди): 
```bat
cd domains
git clone https://github.com/Kiryusha22/wepics-laravel
cd wepics-laravel
composer i
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
```
После можно перезапускать OSPanel для видимости нового домена.

Для доступа к веб-сайту просто через домен в корне проекта необходимо создать файл с названием `.htaccess` и заполнить следующим содержимым:
```apacheconf
RewriteEngine on
RewriteRule (.*)? /public/$1
```
