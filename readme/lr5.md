# Задание 5 - Авторизация и регистрация в StudyOn.Billing

## 0. Создание окружения
- Устанавливаем все зависимости, меняем app/config/doctrine.yaml. Проводим настройки .env и .env.test
- Генерация пользователя:
    ```
    docker compose exec php bin/console make:user
    ```
    Дополнительно с помощью `make entity` добавить поле balance типа float. [Результирующий файл](/src/Entity/User.php)
- Создание фикстуры, базы данных, миграций. Применение фикстуры.
Важно! Моя фикстура не имеет конструктора, хеширует пароль через PasswordHasherFactory.
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
- Для того, чтобы обеспечить возвращение ответа в виде json всегда, даже при 40х и 5хх ошибках, создала прослушивателя [App\EventListener\ExceptionListener](/src/EventListener/ExceptionListener.php). Его нужно зарегистрировать в /config/services.yaml
```
services:
    App\EventListener\ExceptionListener:
        tags:
            - { name: kernel.event_listener, event: kernel.exception }
```




1. Готовый класс AbstractTest:

Проблемы:
- Класс ContainerAwareInterface вероятно устарел
    [issue](https://github.com/symfony/symfony-docs/issues/18440)

    В файле Undefined type, но почему-то работает, хотя класса в vendor не нашла, на гитхабе в текущей версии компонента тоже нет класса
    [symfony repository](https://github.com/symfony/symfony/tree/7.0/src/Symfony/Component/DependencyInjection)

- Метод getClient не переопределяется с указанной сигнатурой
    ```
    protected static function getClient($reinitialize = false, array $options = [], array $server = [])
    ```
    Ошибка: Method 'App\Tests\AbstractTest::getClient()' is not compatible with method 'Symfony\Bundle\FrameworkBundle\Test\WebTestCase::getClient()'.

    Был создан метод createTestClient, рабочий, но не согласованный.

2. Пишем тесты:

Проблемы: 
- Не обнуляются SEQUENCE в БД, фикструры загружаются каждый раз с новым ID.

    Для очистки БД была загружена зависимость `dama/doctrine-test-bundle`, но проблему не решило почему-то.

    TODO для правильной работы с ID в тестах были произведелны вручную настройки сиквансов в тестовой БД: ограничила максимум по количеству уроков/курсов соотв., включила зацикливание назначения Id. Но это плохо.

    Тут есть какой-то issue на тему,наверное, но я ничего не поняла
    [issue 2](https://github.com/doctrine/orm/issues/8893)

    Создала для обнуления сиквансов команду, но из-за нее перестали запускаться тесты с очень странной ошибкой ( Не найден файл фикса для БД FixPostgreSQLDefaultSchemaListener). Команда работала, но обнуляла в БД на проде :\ Пришлось удалить, но есть коммит. 

- В какой-то момент появилась ошибка
    ```
    App\Tests\CourseFunctionaltest::testHasLinkToDetailCourse
    LogicException: Booting the kernel before calling "Symfony\Bundle\FrameworkBundle\Test\WebTestCase::createClient()" is not supported, the kernel should only be booted once.
    ```

    Она возникает на любом методе, который запускается первым... Как починить не знаю, после чего появилась - тоже не знаю (

    UPD: сиквансы зло! Ошибка пропала, после того, как в классах проверки страниц были убраны запросы курса/урока.
- Иногда бывает ошибка в классах *PagesTest, если не находится элемент по id. Но при новом запуске все живет.

## FIX от 22.04.24
Ранее для тестов была настроена тестовая БД особым образом:
- ограничение на максимальное значение сикванса
- включенение зацикливания при выдаче сиквансов.

Для ухода от этого была создана команла для обнуления сиквансов: [ResetSequencesCommand](/src/Command/ResetSequencesCommand.php)

Но она работала на продовскую БД. По совету Руководите проекта было прописано выполнение команды в тестовой среде через --env=test. Итогом стали дополнения в make-файл:

```
phpunit:
	@${PHP} bin/phpunit

reset-sequences:
	@${CONSOLE} app:reset-sequences --env=test

test:
	make reset-sequences && make phpunit
```

И это работает, но было выявлено, что обнуление сиквансов желательно выполнять перед каждым тестом, потому что сейчас оно обнуляет перед процессом, но они нарастают с каждым тестом. Поэтому на данном этапе отказаться от предыдущих настроек БД нельзя, но зато теперь снижено количество ошибок при прогоне тестов.