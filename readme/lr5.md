# Задание 5 - Авторизация и регистрация в StudyOn.Billing

## 0. Создание окружения
- Устанавливаем все зависимости, меняем app/config/doctrine.yaml. Проводим настройки .env и .env.test
- Генерация пользователя:
    ```
    docker compose exec php bin/console make:user
    ```
    Дополнительно с помощью `make entity` добавить поле balance типа float. [Результирующий файл](/src/Entity/User.php)
- Создание фикстуры, базы данных, миграций. Применение фикстуры.
! Важно. Моя фикстура не имеет конструктора, хеширует пароль через PasswordHasherFactory.
- Создание тестовой БД, применение миграций.
    ```
    docker compose exec php bin/console doctrine:database:create --env=test
    docker compose exec php bin/console doctrine:migrations:migrate --env=test
    ```

## 1. Разработка метода авторизации
- Устанавливаем зависимости
- Создаем контроллер для обработки роутов авторизации. 
- Создаем метод:
```
#[Route('/auth', name: 'api_auth', methods: ['POST'])]
public function auth() : void
    {
    }
```
- В /config/packages/security.yaml добавляем то, что написано [вот тут](https://github.com/lexik/LexikJWTAuthenticationBundle/blob/2.x/Resources/doc/index.rst#symfony-53-and-higher)
- Для проверки API в этом проекте используется Postman, [набор реквестов тут](/Study-on.postman_collection.json). Должен вернуться токен. 

## 2. Разработка метода регистрации
- Устанавливаем зависимости
- Создаем метод для роута /api/v1/register. Его придется написать самостоятельно.
```
#[Route('/register', name: 'api_register', methods: ['POST'])]
public function register(
    Request $request,
    EntityManagerInterface $em,
    JWTTokenManagerInterface $jwtManager,
) : JsonResponse {}
```
- Создаем класс UserDTO для обработки и валидации данных регистрации. В нем с помощью аннотаций задаем правила валидации. 
! Важно. Пробовала создать валидацию на уникальность поля username. Делалось это с использованием EntityManager или ManagerRegistry, но они не инициализировались в конструкторе. Код закомментирован, [смотреть без смс и регистрации](/src/DTO/UserDto.php)
- В класс User добавить статический метод convertDtoToUser (или fromDTO)

## 3. Разработка метода получения текущего пользователя
- В /config/packages/security.yaml прописываем правила доступности роутов
```
security:
    access_control:
        - { path: ^/api/v1//auth,     roles: PUBLIC_ACCESS }
        - { path: ^/api/v1/register,  roles: PUBLIC_ACCESS }
        - { path: ^/api/v1,           roles: IS_AUTHENTICATED_FULLY }
```
- Создаем метод GET /api/v1/users/current в контроллере
- ! Важно. Для того, чтобы обеспечить возвращение ответа в виде json всегда, даже при 40х и 5хх ошибках, создала прослушивателя [App\EventListener\ExceptionListener](/src/EventListener/ExceptionListener.php). Его нужно зарегистрировать в /config/services.yaml
```
services:
    App\EventListener\ExceptionListener:
        tags:
            - { name: kernel.event_listener, event: kernel.exception }
```
## 4. Документирование API
- делаем все по плану занятий
- ! Важно. После изменения config/routes/nelmio_api_doc.yaml моя страница с докой все равно была недоступна  (404). Это не было связано с созданным ранее прослушивателем, для исправления в config/routes.yaml пришлось прописать:
```
app.swagger_ui:
    path: /api/v1/doc
    methods: GET
    defaults: { _controller: nelmio_api_doc.controller.swagger_ui }
```

## 5. Тестирование
- Копируем AbstractTest из проекта studyOn. Ошибки, которые возникли у меня, совпадают с описанными в lr4.md
- Создаем файлик теста
- Тест-кейсы:
    * Удачная авторизация
    * Неудачная авторизация (несуществующий логин, неверный пароль)
    * Успешная регистрация
    * Неудачная регистрация (существующая почта, пустые поля, пароль короче 6 сим.)
    * Успешное получение текущего пользователя
    * Неудачное получение текущего пользователя (невалидный токен, запрос без токена)
