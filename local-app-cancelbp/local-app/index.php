<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    define("NO_KEEP_STATISTIC", "Y");
    define("NOT_CHECK_PERMISSIONS", "Y");
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
}

$config = require __DIR__ . '/configs.php';

/* ================= ПОЛУЧЕНИЕ ID ================= */
$placementOptions = $_REQUEST['PLACEMENT_OPTIONS'] ?? [];
if (is_string($placementOptions)) {
    $placementOptions = json_decode($placementOptions, true);
}

$entityId = (int)(
    $placementOptions['ENTITY_DATA']['entityId']
    ?? $placementOptions['ENTITY_VALUE_ID']
    ?? $_REQUEST['ENTITY_VALUE_ID']
    ?? 0
);

/* ================= ЗАПУСК БП ================= */
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    str_contains($contentType, 'application/json')
) {

    header('Content-Type: application/json; charset=utf-8');

    try {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            throw new Exception('Пустой или некорректный JSON');
        }

        if (empty($data['entityId'])) {
            throw new Exception('Не передан ID');
        }

        if (!CModule::IncludeModule('crm')) {
            throw new Exception('CRM не подключен');
        }
        if (!CModule::IncludeModule('bizproc')) {
            throw new Exception('Бизнес-процессы не подключены');
        }

        $eid = (int)$data['entityId'];

        // DOCUMENT_ID через конфиг
        $documentId = str_replace(
            ['{SMART_ID}', '{ELEMENT_ID}'],
            [$config['SMART_PROCESS_ID'], $eid],
            $config['DOCUMENT']['ID_TEMPLATE']
        );

        global $USER;

        $bpParams = $config['BP_PARAMETERS'];

        $bpParams['TargetUser'] =
            (!empty($bpParams['TargetUser']) && $bpParams['TargetUser'] !== 'USER_ID_PLACEHOLDER')
                ? (int)$bpParams['TargetUser']
                : (int)$USER->GetID();

        $errors = [];

        $wfId = CBPDocument::StartWorkflow(
            $config['BP_TEMPLATE_ID'],
            [
                $config['DOCUMENT']['MODULE'],
                $config['DOCUMENT']['CLASS'],
                $documentId
            ],
            $bpParams,
            $errors
        );

        if (!$wfId) {
            throw new Exception($errors ? implode('; ', $errors) : 'Ошибка БП');
        }

        echo json_encode(['success' => true, 'workflowId' => $wfId], JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

    die();
}

/* ================= UI ================= */
$buttonId = 'bp_btn_' . md5(time());
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <script src="//api.bitrix24.com/api/v1/"></script>
    <style>
        body { margin: 0; padding: 8px; font-family: Arial, sans-serif; }

        .<?= $config['BUTTON_CLASS'] ?> {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            color: #fff;
            background: <?= $config['BUTTON_COLOR'] ?>;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            display: none;
        }

        .<?= $config['BUTTON_CLASS'] ?>.visible {
            display: block;
        }

        .<?= $config['BUTTON_CLASS'] ?>.loading {
            background: #888 !important;
            cursor: default;
        }
    </style>
</head>
<body>

<button id="<?= $buttonId ?>"
        class="<?= $config['BUTTON_CLASS'] ?>"
        data-eid="<?= $entityId ?>">
    <?= htmlspecialcharsbx($config['BUTTON_TEXT']) ?>
</button>

<script>
(function() {
    const button = document.getElementById('<?= $buttonId ?>');
    const handlerUrl = location.pathname + location.search;

    const entityId = parseInt(button.dataset.eid) || 0;

    const defaultText = '<?= htmlspecialcharsbx($config['BUTTON_TEXT']) ?>';
    const loadingText = '<?= htmlspecialcharsbx($config['BUTTON_LOADING_TEXT']) ?>';

    function resizeFrame() {
        if (typeof BX24 !== 'undefined') {
            BX24.resizeWindow(300, 60);
        }
    }

    setTimeout(resizeFrame, 200);

    if (entityId > 0) {
        button.classList.add('visible');
    }

    button.addEventListener('click', async function(e) {
        e.preventDefault();

        button.disabled = true;
        button.classList.add('loading');
        button.textContent = loadingText;

        try {
            const res = await fetch(handlerUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    entityId: entityId
                })
            });

            const result = await res.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            setTimeout(() => {
                button.disabled = false;
                button.classList.remove('loading');
                button.textContent = defaultText;
                if (window.parent && window.parent !== window) {
                    window.parent.location.reload();
                } else {
                    window.location.reload();
                }
            }, 2000);

        } catch (err) {
            console.error(err);
            alert('Ошибка: ' + err.message);

            button.disabled = false;
            button.classList.remove('loading');
            button.textContent = defaultText;
        }
    });
})();
</script>

</body>
</html>