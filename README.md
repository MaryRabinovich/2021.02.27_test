<p align="center"><img src="https://res.cloudinary.com/dtfbvvkyp/image/upload/v1566331377/laravel-logolockup-cmyk-red.svg" width="400"></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/license.svg" alt="License"></a>
</p>

#Запуск Docker
```
docker-compose build
docker-compose up
http://localhost:8000
```
#Запуск тестов
```
./vendor/bin/phpunit
```

#Задание
Сделать модуль подбора лекарств по задаваемым действующим веществам.
Часть администрирования:
1. CRUD добавления возможных вариантов действующих веществ (например "ромашка", "спирт", "меланин" и пр.).
2. CRUD создания лекарств из этих веществ (как комбинация веществ).
Админ может сделать невидимым одно из вещечтв, в этом случае лекарства, составленные с использованием этого вещества, тоже скрываются (становятся недоступны)
Часть пользователя:
Пользователь задает до 5-ти действующих веществ для формирования лекарства, модуль строит запрос, в результате которого:
1. Если созданы (и не скрыты) лекарства с полным совпадением действующих веществ выводятся только они.
2. Если созданы (и не скрыты) лекарства с частичным совпадением веществ, то они выводятся в порядке уменьшения числа совпадающих веществ, но только те, у которых совпадает не менее 2-х веществ.
3. Если созданы (и не скрыты) лекарства с совпадением только одного вещества или нет таких лекарств вовсе, то выводить "не найдено лекарств"
4. Если пользователь выбрал менее 2-х веществ, то поиск не производить, выдавать "не ленись, добавь веществ"