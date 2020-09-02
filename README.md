# Simple task tracker php api

author: Бизяев Сергей   
email: SpawNBK@yandex.ru  
version: 1  
---


##### Установка:   
+ Установить apache, mod_rewrite, php7, MySQL
+ Добавить в .htaccess файл дерективы для переадресации запросов на api.   
```
Options +FollowSymLinks
IndexIgnore */*
<IfModule mod_rewrite.c>
RewriteEngine on
# Перенаправление с ДОМЕН на ДОМЕН/api
RewriteCond %{REQUEST_URI} ^/$
RewriteRule ^(.*)$ /api/$1 [R=301]

#Если URI начинается с api/ то перенаправлять все запросы на index.php
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ /index.php
</IfModule>
```   
+ ```git clone https://github.com/SpawNBK/task_tracker.git```
+ Отредактировать файл настроек config/Db.php для доступа к MySQL
```
    private $host = "localhost"; //адрес хоста

    private $db_name = "";  //Имя базы данных. База создается автоматически

    private $username = ""; //Имя пользователя

    private $password = ""; //Пароль
```
+ Сделать GET запрос на адрес
```
  http://site/api/install
```
```
//Пример cUrl
curl http://site/api/install \
  -X GET
```   
```
//Пример ответа   
{ 
  "success": true
}
```   
+ + Произойдет создание базы данных и таблиц.  
  + Будет создан администратиный пользователь admin с паролем admin.
   
На этом установка закончена. 

---
   
## Справочник по API   

API возвращает json на каждый запрос, включая ошибки.

Для использования API, необходимо авторизоваться.
Для использования запроса регистрации, авторизация не требуется.


## API Errors   
API испозьует HTTP статус коды для индикации состояния запросов.

**200 OK** все работает, как нужно.   
**401 Unauthorized** вы не отправили валидный токен.   
**404 Not Found** запрашиваемая точка входа не найдена.  
**405 Method Not Allowed** выбран не верный метод отправки данных.   
**422 Validation failed** отправленные параметры не прошли валидацию.   
**500 Internal Server Error** что то пошло не так.   
На ошибку сервер отправляет json файл.   
```
{ 
  "success": false,
  "error": {
     "message": "Failed update user.",
     "code": 422
  }
  "data": {
     "message": "params password, email could not be blank"
}
```
### API авторизация   
#####:POST login
Для авторизации отправляются данные пользователя на адрес   
```
site/api/login 
```   
```
//Пример cUrl
curl http://site/api/login \
  -d "username=admin&password=admin" \
  -X POST
```   
```
//Пример ответа   
{ 
  "success": true,
  "data": {
     "token": "82cfb03f237588c74138ba3df73dd746"
}
```
### API Methods    
Запросы к API должны быть вида   
```
   //"v1" - версия API
  site/api/v1/$method 
```
#### *method /users*   

##### :GET /users   
Получает список всех пользователей
##### parameters   
 *self = 1* to return your user 
   
```
//Пример cUrl
curl http://site/api/v1/users \
  -d "token=82cfb03f237588c74138ba3df73dd746" \
  -X GET
```   
```
//Пример ответа   
{ 
  "success": true,
  "data": [
    {
        "id":1,
        "username":"admin",
        "email":"test@mail.ru",
        "admin":true
    },
    {
        "id":2,
        "username":"spawn",
        "email":"sp@ya.ru",
        "admin":false
    }
    ...
  ]
}
```
```
//Пример cUrl
curl http://site/api/v1/users \
  -d "token=82cfb03f237588c74138ba3df73dd746" \
  -d "self=1" \
  -X GET
```   
```
//Пример ответа   
{ 
  "success": true,
  "data": 
    {
        "id":1,
        "username":"admin",
        "email":"test@mail.ru",
        "admin":true
    }
}
```
---   

##### :GET /users/:id   
Получает информацию о пользователе по его ID
   
```
//Пример cUrl
curl http://site/api/v1/users/:id \
  -d "token=82cfb03f237588c74138ba3df73dd746" \
  -X GET
```   
```
//Пример ответа   
{ 
  "success": true,
  "data": 
    {
        "id":1,
        "username":"admin",
        "email":"test@mail.ru",
        "admin":true
    }
}
```
--- 
  
##### :PUT /users/:id   
Обновляет информацию о пользователе по его ID.   
**Администратор** - обновит информацию любого пользователя.   
**Обычный пользователь** - обновит свою информацию.   
**Важное замечание!** изменение логина не предусмотрено!   
**Важное замечание2!** обычный пользователь не может изменить permission   
##### parameters   
 *password*   
 *email*  
 *permission = 0|1*  не обязательный  
      
```
//Пример cUrl
curl http://site/api/v1/users/:id \
  -d "token=82cfb03f237588c74138ba3df73dd746" \
  -d "password=123qweQWE" \
  -d "email=sp@ya.ru" \
  -d "permission=0" \
  -X PUT
```   
```
//Пример ответа   
{ 
  "success": true,
  "data": 
    {
        "id":1,
        "username":"admin",
        "email":"sp@ya.ru",
        "admin":false
    }
}
```
---  
##### :DELETE /users/:id   
Удаляет пользователя по его ID.   
**Администратор** - удалит любого пользователя.   
**Обычный пользователь** - удалит себя, если верно указан ID.         
      
```
//Пример cUrl
curl http://site/api/v1/users/:id \
  -d "token=82cfb03f237588c74138ba3df73dd746" \
  -X DELETE
```   
```
//Пример ответа   
{ 
  "success": true,
  "data": 
    {
        "message":"User has been deleted"
    }
}
```
---  

##### :POST /users/   
Создает нового пользователя  
**Администратор** - может создать администратора и обычного пользователя.   
**Все остальные** - могут согдать обычного пользователя.         

##### parameters   
 *username*   
 *password*   
 *email*  
 *permission = 0|1* не обязательный      
```
//Пример cUrl
curl http://site/api/v1/users/ \
  -d "username=newuser" \
  -d "password=123qweQWE" \
  -d "email=sp@ya.ru" \
  -d "permission=1" \
  -X POST
```   
```
//Пример ответа   
{ 
  "success": true,
  "data": 
    {
        "id": 20,
        "username": "newuser",
        "email": "sp@ya.ru",
        "admin": true
    }
}
```
---  
#### *method /tasks*   

##### :GET /tasks   
Получает список задач, в которых пользователь является создателем или исполнителем.   
##### parameters   
 *filter*  - не обязательный переметр. Выполняет сортировку по статусам записей.
 может быть *working | archived | finished*   
 *search*  - не обязательный параметр. Выполняет поиск по заголовкам и тексту записей и фильтрует найденные. 
   
```
//Пример cUrl
curl http://site/api/v1/tasks \
  -d "token=82cfb03f237588c74138ba3df73dd746" \
  -X GET
```   
```
//Пример ответа   
{
    "success": true,
    "data": [
        {
            "id": 6,
            "created": "2020-09-01 13:50:49",
            "author": 2,
            "title": "Создание БД",
            "content": "Создать базу данных из имеющегося набора данных",
            "end_date": 2020-09-03 00:00:00,
            "status": "working",
            "workers": [
                2,
                3
            ],
            "edited": [
                {
                    "user_id": 2,
                    "modify_date": "2020-09-01 13:50:49"
                }
            ]
        }
    ...
}
```
```
//Пример cUrl с фильтром и поиском
curl http://site/api/v1/tasks \
  -d "token=82cfb03f237588c74138ba3df73dd746" \
  -d "filter=working" \
  -d "search=данных" \
  -X GET
```   
```
//Пример ответа   
{
    "success": true,
    "data": [
        {
            "id": 6,
            "created": "2020-09-01 13:50:49",
            "author": 2,
            "title": "Создание БД",
            "content": "Создать базу данных из имеющегося набора данных",
            "end_date": 2020-09-03 00:00:00,
            "status": "working",
            "workers": [
                2,
                3
            ],
            "edited": [
                {
                    "user_id": 2,
                    "modify_date": "2020-09-01 13:50:49"
                }
            ]
        }
}
```
---   
##### :GET /tasks/:id   
Получает задание по ID.   
   
```
//Пример cUrl
curl http://site/api/v1/tasks/:id \
  -d "token=82cfb03f237588c74138ba3df73dd746" \
  -X GET
```   
```
//Пример ответа   
{
    "success": true,
    "data": [
        {
            "id": 6,
            "created": "2020-09-01 13:50:49",
            "author": 2,
            "title": "Создание БД",
            "content": "Создать базу данных из имеющегося набора данных",
            "end_date": 2020-09-03 00:00:00,
            "status": "working",
            "workers": [
                2,
                3
            ],
            "edited": [
                {
                    "user_id": 2,
                    "modify_date": "2020-09-01 13:50:49"
                }
            ]
        }
}
```
--- 
##### :POST /tasks/   
Создает новое задание          

##### parameters   
 *title*   
 *content*   
 *enddate*  - не обязательный. Формат, желательно, dd-mm-yyyy 
```
//Пример cUrl
curl http://site/api/v1/tasks/ \
  -d "title=Редактирование шапки сайта" \
  -d "content=Заменить картинку и надписи в шапке сайта." \
  -d "enddate=05-09-2020"
  -X POST
```   
```
//Пример ответа   
{
    "success": true,
    "data": {
        "id": 7,
        "created": "2020-09-02 07:09:22",
        "author": 2,
        "title": "Редактирование шапки сайта",
        "content": "Заменить картинку и надписи в шапке сайта.",
        "end_date": "2020-09-05 00:09:00",
        "status": "working",
        "workers": [
            "2"
        ],
        "edited": [
            {
                "user_id": 2,
                "modify_date": "2020-09-02 07:09:22"
            }
        ]
    }
}
```


##### :PUT /tasks/:id   
Обновляет данные задания по ID.   
  
##### parameters   
 *title*   
 *content*
 *enddate*   - не обязательный. Формат, желательно, dd-mm-yyyy
      
```
//Пример cUrl
curl http://site/api/v1/tasks/:id \
  -d "token=82cfb03f237588c74138ba3df73dd746" \
  -d "title=Редактирование всего сайта" \
  -d "content=Проект нового дизайна" \
  -d "enddate=30-09-2020"
  -X PUT
```   
```
//Пример ответа   
{
    "success": true,
    "data": {
        "id": 7,
        "created": "2020-09-02 07:09:22",
        "author": 2,
        "title": "Редактирование всего сайта",
        "content": "Проект нового дизайна",
        "end_date": "2020-09-30 00:09:00",
        "status": "working",
        "workers": [
            2
        ],
        "edited": [
            {
                "user_id": 2,
                "modify_date": "2020-09-02 08:16:22"
            },
            {
                "user_id": 2,
                "modify_date": "2020-09-02 07:09:51"
            }
        ]
    }
}
```
---  

##### :PUT /tasks/:id/enable   
Меняет статус задания на working по ID.    
      
```
//Пример cUrl
curl http://site/api/v1/tasks/:id/enable \
  -d "token=82cfb03f237588c74138ba3df73dd746" \
  -X PUT
```   
```
//Пример ответа   
{
    "success": true,
    "data": {
        "message": "Task 7 has been successful enabled"
    }
}
```
---  

##### :PUT /tasks/:id/finish   
Меняет статус задания на finished по ID.    
      
```
//Пример cUrl
curl http://site/api/v1/tasks/:id/finish \
  -d "token=82cfb03f237588c74138ba3df73dd746" \
  -X PUT
```   
```
//Пример ответа   
{
    "success": true,
    "data": {
        "message": "Task 7 has been successful finished"
    }
}
```
---  

##### :PUT /tasks/:id/archive   
Меняет статус задания на archived по ID.    
      
```
//Пример cUrl
curl http://site/api/v1/tasks/:id/archive \
  -d "token=82cfb03f237588c74138ba3df73dd746" \
  -X PUT
```   
```
//Пример ответа   
{
    "success": true,
    "data": {
        "message": "Task 7 has been successful archived"
    }
}
```
---  

##### :PUT /tasks/:id/workers   
Добавляет исполнителя задания по ID.   
  
##### parameters   
 *id* - user id   
      
```
//Пример cUrl
curl http://site/api/v1/tasks/:id/workers \
  -d "token=82cfb03f237588c74138ba3df73dd746" \
  -d "id=3" \
  -X PUT
```   
```
//Пример ответа   
{
    "success": true,
    "data": {
        "id": 7,
        "created": "2020-09-02 09:09:22",
        "author": 2,
        "title": "Редактирование всего сайта",
        "content": "Проект нового дизайна",
        "end_date": "2020-09-30 00:09:00",
        "status": "archived",
        "finished": "2020-09-02 08:32:27",
        "workers": [
            2,
            3
        ],
        "edited": [
            {
                "user_id": 2,
                "modify_date": "2020-09-02 08:34:49"
            },
            {
                "user_id": 2,
                "modify_date": "2020-09-02 08:32:27"
            },
            ...
        ]
    }
}
```
---  

##### :PATCH /tasks/:id
Обновляет данные задания по ID 
Можно использовать 1 или несколько параметров        

##### parameters   
 *title*   
 *content*   
 *enddate*  Формат, желательно, dd-mm-yyyy 
```
//Пример cUrl
curl http://site/api/v1/tasks/:id \
  -d "title=Задание по сайту" \
  -X PATCH
```   
```
//Пример ответа   
{
    "success": true,
    "data": {
        "id": 7,
        "created": "2020-09-02 07:09:22",
        "author": 2,
        "title": "Задание по сайту",
        "content": "Проект нового дизайна",
        "end_date": "2020-09-30 00:09:00",
        "status": "archived",
        "finished": "2020-09-02 08:32:27",
        "workers": [
            2,
            3
        ],
        "edited": [
            {
                "user_id": 2,
                "modify_date": "2020-09-02 08:34:49"
            },
            {
                "user_id": 2,
                "modify_date": "2020-09-02 08:32:27"
            },
            ...
        ]
    }
}
```

##### :DELETE /tasks/:id   
Удаляет задание по ID.           
      
```
//Пример cUrl
curl http://site/api/v1/tasks/:id \
  -d "token=82cfb03f237588c74138ba3df73dd746" \
  -X DELETE
```   
```
//Пример ответа   
{
    "success": true,
    "data": {
        "message": "Task has been deleted"
    }
}
```
---  

##### :DELETE /tasks/:id/workers/:id  
Удаляет испольнителя задания по ID.           
      
```
//Пример cUrl
curl http://site/api/v1/tasks/:id/workers/:id \
  -d "token=82cfb03f237588c74138ba3df73dd746" \
  -X DELETE
```   
```
//Пример ответа   
{
    "success": true,
    "data": {
        "id": 7,
        "created": "2020-09-02 08:16:22",
        "author": 2,
        "title": "Задание по сайту",
        "content": "Проект нового дизайна",
        "end_date": "2020-09-30 00:09:00",
        "status": "archived",
        "finished": "2020-09-02 08:32:27",
        "workers": [
            2
        ],
        "edited": [
            {
                "user_id": 2,
                "modify_date": "2020-09-02 08:40:17"
            },
            {
                "user_id": 2,
                "modify_date": "2020-09-02 08:34:49"
            },
            ...
        ]
    }
}
```
--- 





    
