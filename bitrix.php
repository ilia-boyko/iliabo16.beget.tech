<?php
include_once __DIR__ . '/vendor/autoload.php';

use App\Bitrix24\Bitrix24API;
use App\Bitrix24\Bitrix24APIException;

try {
	$webhookURL = 'https://b24-4flvp0.bitrix24.ru/rest/1/36cnqp2qzswi5rdh/';
	$bx24 = new Bitrix24API($webhookURL);
    $contactId = $bx24->addContact([
            'NAME' => $_POST['fio'],
            'PHONE' => [
                    0 => [
                            'VALUE_TYPE' => 'WORK',
                            'VALUE' => $_POST['phone'],
                            'TYPE_ID' => 'PHONE'
                        ]
                ]
        ]);
    $datetime = date("d.m.Y H:i:s");
    $leadName = "Заявка с сайта $datetime";
    $dealId = $bx24->addDeal([
            'TITLE' => $leadName,
            'COMMENTS' => $_POST['comment'],
            'CONTACT_ID' => $contactId,
            'UF_CRM_1727191876348' => 47
        ]);
    echo "OK. DEAL_ID: {$dealId}";
} catch (Bitrix24APIException $e) {
    printf('Ошибка (%d): %s' . PHP_EOL, $e->getCode(), $e->getMessage());
} catch (Exception $e) {
    printf('Ошибка (%d): %s' . PHP_EOL, $e->getCode(), $e->getMessage());
}
?>