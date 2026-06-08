<?php
return [
    // ID смарт-процесса
    'SMART_PROCESS_ID' => 147,

    // ID шаблона бизнес-процесса
    'BP_TEMPLATE_ID' => 770,

    // Описание документа БП (полностью централизовано)
    'DOCUMENT' => [
        'MODULE' => 'crm',
        'CLASS' => 'Bitrix\\Crm\\Integration\\BizProc\\Document\\Dynamic',

        // Шаблон DOCUMENT_ID
        // {SMART_ID} — ID смарт-процесса
        // {ELEMENT_ID} — ID элемента
        'ID_TEMPLATE' => 'DYNAMIC_{SMART_ID}_{ELEMENT_ID}',
    ],

    // Кнопка
    'BUTTON_CLASS' => 'start-bp-btn',
    'BUTTON_TEXT' => 'Отменить согласование',

    // Параметры БП
    'BP_PARAMETERS' => [
        'TargetUser' => 1
    ],

    'BUTTON_COLOR' => '#9f1a1a',
    'BUTTON_LOADING_TEXT' => 'Запущена отмена согласования',
]; 