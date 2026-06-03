# Доработка с полем 'У кого задание?'

[Исходный код (с комментариями)](/field-user-bptask/task-field-users.php)

[Ссылка на обработчик на портале](https://bitrix.octobergroup.ru/bitrix/admin/fileman_file_view.php?path=%2Flocal%2Fphp_interface%2Fhandlers%2Ftask-field-users.php&site=s1&lang=ru)

[Подключение доработки в файл init.php](#подключение-обработчика-в-initphp)

[Дополнение списка сущностей (и полей), которые учавствуют в доработке](#дополнение-списка-сущностей-и-полей-которые-учавствуют-в-доработке)

Доработка состоит из трех обработчиков:
- Обработчик на завершение задания бизнес-процесса
- Обработчик на добавления задания бизнес-процесса
- Обработчик на делегирования задания бизнес-процесса


# Дополнение списка сущностей (и полей), которые учавствуют в доработке

- Для добавления новых смарт-процессов для работы обработчиков на добавление/делегирование задания бизнес-процесса:
    - Обработчик на добавление задания бизнес-процесса (метод OnTaskAddHandler):
     1. дополняем `$dynamicIDS = ['147', '191', '142', '164', '178', '1036', 'new_id']` c ID смарт-процесса;
     2. в проверки по аналоги добавляем условие 
        ```
        else if ($parseResult['type_id'] == 'new_id') {
            $item->set("id_поля", $userIds);
        }
        ```
    - Обработчик на делегирование задания бизнес-процесса (метод OnTaskDelegateHandler) - аналогично:
     1. дополняем `$dynamicIDS = ['147', '191', '142', '164', '178', '1036', 'new_id']` c ID смарт-процесса;
     2. в проверки по аналоги добавляем условие 
        ```
        else if ($parseResult['type_id'] == 'new_id') {
            $item->set("id_поля", $userIds);
        }
        ```
    - Обработчик на завершение задания бизнес-процесса (метод OnTaskMarkCompletedHandler):
     1. дополняем c ID смарт-процесса и ID поля;
        ```
        $entityFieldConfig = [
            '1036' => 'UF_CRM_13_1742662194',
            '178'  => 'UF_CRM_4_1732645581',
            '191'  => 'UF_CRM_11_1729762925',
            'new_id' => 'id_поля',
        ]; 
        ```





# Подключение обработчика в init.php

Подключение идет через файл [/local/php_interface/init.php](https://bitrix.octobergroup.ru/bitrix/admin/fileman_file_view.php?path=%2Flocal%2Fphp_interface%2Finit.php&site=s1&lang=ru)

Подключением файла task-field-users.php:
`require_once __DIR__ . '/handlers/task-field-users.php';`
Для отключения закомментируйте строку:
`// require_once __DIR__ . '/handlers/task-field-users.php';`