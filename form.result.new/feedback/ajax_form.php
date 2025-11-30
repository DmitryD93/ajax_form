<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

CModule::IncludeModule("form");
CModule::IncludeModule("main");

if (check_bitrix_sessid()) {

    // Проверка Яндекс капчи
    $smartCaptchaValid = false;
    $smartCaptchaError = '';

    $ip = $_SERVER['REMOTE_ADDR'];
    $url = 'https://smartcaptcha.yandexcloud.net/validate';
    $secret_key = "<SERVER_KEY от яндекса>";
    $user_token = $_REQUEST['smart-token'] ?? '';

    // Проверяем только если токен передан
    if (!empty($user_token)) {
        $data = [
            'secret' => $secret_key,
            'token' => $user_token,
            'ip' => $ip
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
                'timeout' => 10
            ]
        ];

        try {
            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);
            $response_data = json_decode($response, true);

            if ($response_data && $response_data["status"] === "ok") {
                $smartCaptchaValid = true;
            } else {
                $error_msg = $response_data["message"] ?? "Неизвестная ошибка капчи";
                $smartCaptchaError = "Ошибка капчи: " . $error_msg;
            }
        } catch (Exception $e) {
            $smartCaptchaError = "Ошибка проверки капчи: " . $e->getMessage();
        }
    } else {
        $smartCaptchaError = "Капча не пройдена";
    }
    // Тут вернем ошибку, если капча не пройдена

    if (!$smartCaptchaValid) {
        echo json_encode([
            'success' => false,
            'errors' => ['smartcaptcha' => $smartCaptchaError]
        ]);
        require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
        exit();
    }

    // Проверка формы Bitrix
    $formErrors = CForm::Check($_POST['WEB_FORM_ID'], $_REQUEST, false, "Y", 'Y');

    // Добавляем ошибку капчи к общим ошибкам формы, если нужно
    if (!$smartCaptchaValid && !empty($smartCaptchaError)) {
        $formErrors['smartcaptcha'] = $smartCaptchaError;
    }

    if (count($formErrors)) {
        echo json_encode(['success' => false, 'errors' => $formErrors]);
    } elseif ($RESULT_ID = CFormResult::Add($_POST['WEB_FORM_ID'], $_REQUEST)) {

        // Получаем информацию о форме
        $rsForm = CForm::GetByID($_POST['WEB_FORM_ID']);
        if ($arForm = $rsForm->Fetch()) {
            $FORM_NAME = $arForm["NAME"];
            $FORM_SID = $arForm["SID"];
        } else {
            $FORM_NAME = "Неизвестная форма";
            $FORM_SID = "UNKNOWN";
        }

        // Получаем данные формы
        $arAnswer = array();
        CFormResult::GetDataByID($RESULT_ID, array(), $arFields, $arAnswer);

        // Формируем поля для письма
        $arEventFields = array();
        $arEventFields["FORM_NAME"] = $FORM_NAME;
        $arEventFields["FORM_SID"] = $FORM_SID;
        $arEventFields["RESULT_ID"] = $RESULT_ID;
        $arEventFields["DATE_CREATE"] = date("d.m.Y H:i:s");
        $arEventFields["SITE_NAME"] = $_SERVER['SERVER_NAME'];

        foreach ($arAnswer as $FIELD_SID => $arField) {
            $value = '';
            foreach ($arField as $fieldData) {
                if (!empty($fieldData["USER_TEXT"])) {
                    $value = $fieldData["USER_TEXT"];
                } elseif (!empty($fieldData["ANSWER_VALUE"])) {
                    $value = $fieldData["ANSWER_VALUE"];
                } elseif (!empty($fieldData["VALUE"])) {
                    $value = $fieldData["VALUE"];
                }
            }

            if (!empty($value)) {
                $arEventFields[$FIELD_SID] = $value;
                $arEventFields[strtoupper($FIELD_SID)] = $value;
            }
        }

        $eventNames = [
            "FORM_FILLING_" . $FORM_SID,
            "WEB_FORM_" . $_POST['WEB_FORM_ID'],
            "FORM_FILLING",
            "NEW_FORM_FILLING"
        ];

        $selectedEvent = null;
        foreach ($eventNames as $eventName) {
            $eventMessage = CEventMessage::GetList($by = "id", $order = "desc",
                array("EVENT_NAME" => $eventName, "ACTIVE" => "Y"));
            if ($eventMessage->Fetch()) {
                $selectedEvent = $eventName;
                break;
            }
        }

        if (!$selectedEvent) {
            $selectedEvent = "FORM_FILLING";
        }

        CEvent::Send($selectedEvent, SITE_ID, $arEventFields);

        CFormCRM::onResultAdded($_POST['WEB_FORM_ID'], $RESULT_ID);
        CFormResult::SetEvent($RESULT_ID);

        echo json_encode(['success' => true, 'errors' => []]);
    } else {
        echo json_encode(['success' => false, 'errors' => $GLOBALS["strError"]]);
    }
} else {
    echo json_encode(['success' => false, 'errors' => ['sessid' => 'Не верная сессия. Попробуйте обновить страницу']]);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';