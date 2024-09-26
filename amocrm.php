<?php
include_once __DIR__ . '/vendor/autoload.php';

use AmoCRM\Client\AmoCRMApiClient;
use Symfony\Component\Dotenv\Dotenv;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\TagModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\TagsCollection;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\NotesCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\NoteType\CommonNote;
use AmoCRM\Models\CustomFields\SelectCustomFieldModel;
use AmoCRM\Models\CustomFieldsValues\SelectCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\SelectCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\SelectCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;

$dotenv = new Dotenv;
$dotenv->load('./.env');

$apiClient = new AmoCRMApiClient($_ENV['CLIENT_ID'], $_ENV['CLIENT_SECRET'], $_ENV['CLIENT_REDIRECT_URI']);

$apiClient->setAccountBaseDomain($_ENV['ACCOUNT_DOMAIN']);

$rawToken = json_decode(file_get_contents('./token.json'), 1);
$token = new AccessToken($rawToken);

$apiClient->setAccessToken($token);

$contactName = $_POST['fio'];
$datetime = date("d.m.Y H:i:s");
$leadName = "Заявка с сайта $datetime";
$contactPhone = $_POST['phone'];
$leadComment = $_POST['comment'];

$contact = new ContactModel();
$contact->setName($contactName)
->setCustomFieldsValues(
                    (new CustomFieldsValuesCollection)->add(
                        (new MultitextCustomFieldValuesModel)
                        ->setFieldCode('PHONE')
                        ->setValues(
                            (new MultitextCustomFieldValueCollection)->add(
                                (new MultitextCustomFieldValueModel)->setValue($_POST['phone'])
                            )
                        )
                    )
                );
                
try {
    $contactModel = $apiClient->contacts()->addOne($contact);
} catch (AmoCRMApiException $e) {
    printError($e);
    die;
}

$lead = (new LeadModel)->setName($leadName)
    ->setCustomFieldsValues(
        (new CustomFieldsValuesCollection)->add(
            (new SelectCustomFieldValuesModel)->setFieldId(
                $_ENV['SOURCE_FIELD_ID']
                )->setValues(
                    (new SelectCustomFieldValueCollection)->add(
                        (new SelectCustomFieldValueModel)->setValue(
                            "Сайт"
                        )
                    )
                )
            )
        )->setTags(
            (new TagsCollection)->add(
                (new TagModel)->setName("сайт")
            )
        );

try {
    $lead = $apiClient->leads()->addOne($lead);
} catch (AmoCRMApiException $e) {
    printError($e);
    die;
}

$links = new LinksCollection();
$links->add($lead);
try {
    $apiClient->contacts()->link($contactModel, $links);
} catch (AmoCRMApiException $e) {
    printError($e);
    die;
}

$notesCollection = new NotesCollection();
$commonNote = new CommonNote();
$commonNote->setEntityId($lead->getId())->setText($_POST['comment']);
$notesCollection->add($commonNote);

try {
    $leadNotesService = $apiClient->notes(EntityTypesInterface::LEADS);
    $notesCollection = $leadNotesService->add($notesCollection);
} catch (AmoCRMApiException $e) {
    printError($e);
    die;
}

echo "OK. LEAD_ID: {$lead->getId()}";
?>