<?
// Определение файла для логирования
define('DEBUG_FILE_NAME', 'debug.txt');

// Определение обработчика делегирования заданий бизнес-процессов
AddEventHandler("bizproc", "OnTaskDelegate", "OnTaskDelegateHandler");

// Метод обработчика делегирования заданий бизнес-процессов
function OnTaskDelegateHandler($taskId, $fromUserId, $toUserId){

    // По task_id получаем параметра задания бизнес-процесса
    $task = CBPTaskService::GetList(
        [],
        ['ID' => $taskId],
        false,
        [],
        ['*']
    )->fetch();

    // Получаем url элемента CRM, по которому запущен бизнес-процесс
    $url = $task["PARAMETERS"]["DOCUMENT_URL"];
    // Парсим ссылку отдельным методом для получения entityType и entityID элемента CRM
    $parseResult = parseCrmUrl($url);

    $connection = \Bitrix\Main\Application::getConnection();
    $sqlHelper = $connection->getSqlHelper();

    // Получаем из таблицы b_bp_task WORKFLOW_ID процесса 
    $sql1 = "SELECT WORKFLOW_ID FROM b_bp_task WHERE ID = " . (int)$taskId;
    $result1 = $connection->query($sql1);
    $row1 = $result1->fetch();
    try {
        if ($row1 && $row1['WORKFLOW_ID']) {
            $workflowId = $row1['WORKFLOW_ID'];
        
            // Получаем из таблицы b_bp_task ID всех заданий по WORKFLOW_ID
            $sql2 = "SELECT ID FROM b_bp_task WHERE WORKFLOW_ID = '" . $sqlHelper->forSql($workflowId) . "'";
            $result2 = $connection->query($sql2);
        
            $taskIds = [];
            while ($row2 = $result2->fetch()) {
                $taskIds[] = $row2['ID'];
            }
    
            if (!empty($taskIds)) {
                // Получаем из таблицы b_bp_task_user по каждому активному заданию БП пользователей
                $taskIdsString = implode(',', array_map('intval', $taskIds));
                $sql3 = "SELECT DISTINCT USER_ID FROM b_bp_task_user WHERE STATUS = 0 AND TASK_ID IN (" . $taskIdsString . ")";
                $result3 = $connection->query($sql3);
                
                // Сохраняем ID пользователей 
                $userIds = [];
                while ($row3 = $result3->fetch()) {
                    $userIds[] = $row3['USER_ID'];
                }
            }
        }
    
        // Список entityType, с которыми работаем
        $dynamicIDS = ['147', '191', '142', '164', '178', '1036'];
    
        // Если смарт-процесс и есть пользователи, на ком задание заполняем соотвествующие поля
        if (($parseResult['entity_type'] == 'type') && (!empty($userIds))) {
            if ((in_array($parseResult['type_id'], $dynamicIDS))) {
                $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($parseResult['type_id']);
                $item = $factory->getItem($parseResult['entity_id']);
                if ($parseResult['type_id'] == '147') {
                    $item->set("UF_CRM_3_1723637986", $userIds);
                } else if ($parseResult['type_id'] == '191') {
                    $item->set("UF_CRM_11_1729762925", $userIds);
                } else if ($parseResult['type_id'] == '142') {
                    $item->set("UF_CRM_9_1732720725", $userIds);
                } else if ($parseResult['type_id'] == '164') {
                    $item->set("UF_CRM_6_1724946212", $userIds);
                } else if ($parseResult['type_id'] == '178') {
                    $item->set("UF_CRM_4_1732645581", $userIds);
                } else if ($parseResult['type_id'] == '1036') {
                    $item->set("UF_CRM_13_1742662194", $userIds);
                }
                $item->save();
    
            }
        }
     } catch (\Throwable $e) {
            // Логируем при ошибке
            $errorMessage = 'Ошибка: ' . print_r($e->getMessage(), true);
            $trace = print_r($e->getTraceAsString(), true);
            \Bitrix\Main\Diag\Debug::writeToFile($errorMessage, __FUNCTION__, DEBUG_FILE_NAME);
            \Bitrix\Main\Diag\Debug::writeToFile($trace, __FUNCTION__, DEBUG_FILE_NAME);
        }
}

// Определение обработчика добавления заданий бизнес-процессов
AddEventHandler("bizproc", "OnTaskAdd", "OnTaskAddHandler");
// Метод обработчика добавления заданий бизнес-процессов
function OnTaskAddHandler($taskId, $UserId){
    // По task_id получаем параметра задания бизнес-процесса
    $task = CBPTaskService::GetList(
        [],
        ['ID' => $taskId],
        false,
        [],
        ['*']
    )->fetch();

    // Получаем url элемента CRM, по которому запущен бизнес-процесс
    $url = $task["PARAMETERS"]["DOCUMENT_URL"];
    // Парсим ссылку отдельным методом для получения entityType и entityID элемента CRM
    $parseResult = parseCrmUrl($url);

    $connection = \Bitrix\Main\Application::getConnection();
    $sqlHelper = $connection->getSqlHelper();
    // Получаем из таблицы b_bp_task WORKFLOW_ID процесса 
    $sql1 = "SELECT WORKFLOW_ID FROM b_bp_task WHERE ID = " . $taskId;
    $result1 = $connection->query($sql1);
    $row1 = $result1->fetch();

    if ($row1 && $row1['WORKFLOW_ID']) {
        $workflowId = $row1['WORKFLOW_ID'];
    
        // Получаем из таблицы b_bp_task ID всех заданий по WORKFLOW_ID
        $sql2 = "SELECT ID FROM b_bp_task WHERE WORKFLOW_ID = '" . $sqlHelper->forSql($workflowId) . "'";
        $result2 = $connection->query($sql2);
    
        $taskIds = [];
        while ($row2 = $result2->fetch()) {
            $taskIds[] = $row2['ID'];
        }
    
        if (!empty($taskIds)) {
    
            $taskIdsString = implode(',', array_map('intval', $taskIds));
            // Получаем из таблицы b_bp_task_user по каждому активному заданию БП пользователей
            $sql3 = "SELECT DISTINCT USER_ID FROM b_bp_task_user WHERE STATUS = 0 AND TASK_ID IN (" . $taskIdsString . ")";
            $result3 = $connection->query($sql3);
            // Сохраняем ID пользователей 
            $userIds = [];
            while ($row3 = $result3->fetch()) {
                $userIds[] = $row3['USER_ID'];
            }
        }
    }
    // Список entityType, с которыми работаем
    $dynamicIDS = ['147', '191', '142', '164', '178', '1036'];

    // Если смарт-процесс и есть пользователи, на ком задание заполняем соотвествующие поля
    if (($parseResult['entity_type'] == 'type') && (!empty($userIds))) {
        if ((in_array($parseResult['type_id'], $dynamicIDS))) {
            $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($parseResult['type_id']);
            $item = $factory->getItem($parseResult['entity_id']);
            if ($parseResult['type_id'] == '147') {
                $item->set("UF_CRM_3_1723637986", $userIds);
            } else if ($parseResult['type_id'] == '191') {
                $item->set("UF_CRM_11_1729762925", $userIds);
            } else if ($parseResult['type_id'] == '142') {
                $item->set("UF_CRM_9_1732720725", $userIds);
            } else if ($parseResult['type_id'] == '164') {
                $item->set("UF_CRM_6_1724946212", $userIds);
            } else if ($parseResult['type_id'] == '178') {
                $item->set("UF_CRM_4_1732645581", $userIds);
            } else if ($parseResult['type_id'] == '1036') {
                $item->set("UF_CRM_13_1742662194", $userIds);
            }
            $item->save();

        }
    }
}
// Определение обработчика завершения заданий бизнес-процессов
AddEventHandler("bizproc", "OnTaskMarkCompleted", "OnTaskMarkCompletedHandler");
// Метод обработчика завершения заданий бизнес-процессов
function OnTaskMarkCompletedHandler($taskId, $userId, $status){
    $connection = \Bitrix\Main\Application::getConnection();
    $sqlHelper = $connection->getSqlHelper();

    // Получаем из таблицы b_bp_task параметры задания бизнес-процесса
    $sql1 = "SELECT DISTINCT PARAMETERS FROM b_bp_task WHERE ID = " . $taskId . "";
    $result1 = $connection->query($sql1);

    $row1 = $result1->fetch();
    $parameters = unserialize($row1['PARAMETERS']);
    // Выделяем из параметров url элемента CRM, по которому было задание бизнес-процесса 
    if (isset($parameters['DOCUMENT_URL'])) {
        $uri = $parameters['DOCUMENT_URL'];
        //$path = $uri->getPath(); // deprecated - ранее нужно было

        // Парсим ссылку отдельным методом для получения entityType и entityID элемента CRM
        if ($uri) {
            $parseResult = parseCrmUrl($uri);
        }
        // Соответствие смарт-процессов и полей с пользователями
        $entityFieldConfig = [
            '1036' => 'UF_CRM_13_1742662194',
            '178'  => 'UF_CRM_4_1732645581',
            '191'  => 'UF_CRM_11_1729762925',
        ];

        // При наличие соответсвия в конфиге выше и подходящими параметрами задания убираем из поля userId
        if (($parseResult['entity_type'] == 'type') && (!empty($userId))) {
            $typeId = $parseResult['type_id'];
            if (isset($entityFieldConfig[$typeId])) {
                $fieldName = $entityFieldConfig[$typeId];
                $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($typeId);
                $item = $factory->getItem($parseResult['entity_id']);
                $currentValues = $item->get($fieldName);

                if (!empty($currentValues)) {
                    if (is_string($currentValues)) {
                        $valuesArray = explode(',', $currentValues);
                    } elseif (is_array($currentValues)) {
                        $valuesArray = $currentValues;
                    } else {
                        $valuesArray = [$currentValues];
                    }

                    $key = array_search($userId, $valuesArray);
                    if ($key !== false) {
                        unset($valuesArray[$key]);
                    }

                    if (empty($valuesArray)) {
                        $item->set($fieldName, null);
                    } else {
                        if (is_string($currentValues)) {
                            $item->set($fieldName, implode(',', $valuesArray));
                        } else {
                            $item->set($fieldName, $valuesArray);
                        }
                    }

                    $item->save();
                }
            }
            
        }
    }
}

// Метод парсинга для получения entity_type (type_id) и entity_id разных сущностей CRM
function parseCrmUrl($url) {
    if (preg_match('#/type/(\d+)/[a-z]+/(\d+)/#', $url, $matches)) {
        return [
            'entity_type' => 'type',
            'type_id'     => $matches[1], 
            'entity_id'   => $matches[2]  
        ];
    }
    elseif (preg_match('#/crm/([a-z]+)/(?:[a-z]+/)?(\d+)/#', $url, $matches)) {
        return [
            'entity_type' => $matches[1], 
            'entity_id'   => $matches[2] 
        ];
    }

    return null;
}

?>