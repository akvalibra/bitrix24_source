<?php

use Bitrix\Crm\ActivityTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Rarus\Sms4b\DateTimeConverter;
use Rarus\Sms4b\Encoding\Encoding;
use Rarus\Sms4b\Encoding\Service;
use Rarus\Sms4b\Sendings\Exceptions\Sms4bValidationError;
use Rarus\Sms4b\Exceptions\Sms4bException;
use Rarus\Sms4b\Template;
use Rarus\Sms4b\Sendings\Sender;
use Rarus\Sms4b\Debug;
use Rarus\Sms4b\Config;
use Rarus\Sms4b\Address;
use Rarus\Sms4b\RemoteService;
use Rarus\Sms4b\Order;
use Rarus\Sms4b\Task;
use Rarus\Sms4b\Crm;
use Rarus\Sms4b\Balance;

Loc::loadLanguageFile(__FILE__);

class Csms4b
{
    use DateTimeConverter;
    /**
     * @const TEST_SENDER string - ��� ��� ��������� �������
     */
    const TEST_SENDER = 'SMS4B-Test';
    /**
     * @const PHONE_PATTERN string - ������ ����������� ������
     */
    const PHONE_PATTERN = "/^[+]?[78]?[(]?([93]{1}\d{2})[)]?(\d{7})$/";

    private Template\Service $templateService;
    private Sender $sender;
    private Debug\Service $debugService;
    private Config\Service $configService;
    private Address\Service $addressService;
    private RemoteService\Service $remoteService;
    private Order\Service $orderService;
    private Task\Service $taskService;
    private Crm\Service $crmService;
    private Balance\Service $balanceService;

    /**
     * ���������� ������� ������
     */
    public function __construct()
    {
        session_start();

        $this->templateService = new Template\Service();
        $this->sender = new Sender();
        $this->debugService = new Debug\Service();
        $this->configService = new Config\Service();
        $this->addressService = new Address\Service();
        $this->remoteService = new RemoteService\Service();
        $this->orderService = new Order\Service();
        $this->taskService = new Task\Service();
        $this->crmService = new Crm\Service();
        $this->balanceService = new Balance\Service();
    }

    /**
     * ��������� ��������� ���
     *
     * @param string $message - ����� ���
     * @param string $to      - ������� ����������
     * @param string $sender  - ���������� ���
     *
     * @throws Sms4bException
     */
    public function sendSingleSms(string $message, string $to, string $sender): void
    {
        try {
            $this->sender->customSendSms([$to => $message], $sender);
        } catch (Sms4bValidationError $e) {
            throw new Sms4bException(Loc::getMessage('SMS4B_VALIDATION_ERROR'), $e->getCode(), $e);
        }
    }

    /**
     * ��������� �������� ��������
     *
     * @param array  $arPhonesMessages - ������ c ���������� � ������� ��� ���� array("PHONE"=>"MESSAGE")
     * @param string $sender           - ���������� ���
     *
     * @throws Sms4bException
     */
    public function sendMultiSms(array $arPhonesMessages, string $sender): void
    {
        try {
            $this->sender->customSendSms($arPhonesMessages, $sender);
        } catch (Sms4bValidationError $e) {
            throw new Sms4bException(Loc::getMessage('SMS4B_VALIDATION_ERROR'), $e->getCode(), $e);
        }
    }

    /**
     * ��������� ���������� �������� �� ��������� �������
     *
     * @param array    $arPhonesMessages - ������ c ���������� � ������� ��� ���� array("PHONE"=>"MESSAGE")
     * @param string   $sender           - ���������� ���
     * @param DateTime $dateStart        - ���� ������ ��������
     * @param DateTime $dateActual       - ���� ������������ ��������
     *
     * @throws Sms4bException
     */
    public function sendDelayedSms(
        array $arPhonesMessages,
        string $sender,
        \DateTime $dateStart,
        \DateTime $dateActual
    ): void {
        try {
            $this->sender->delaySendSms($arPhonesMessages, $sender, $dateStart, $dateActual);
        } catch (Sms4bValidationError $e) {
            throw new Sms4bException(Loc::getMessage('SMS4B_VALIDATION_ERROR'), $e->getCode(), $e);
        }
    }

    /**
     * ���������� ������ � ����������� �������
     *
     * @return array - ���������� ����� �����������
     * @throws Sms4bException
     */
    public function getAllSenders(): array
    {
        return $this->addressService->getAllAddresses();
    }

    /**
     * �������� ������ ��������
     *
     * @return float - ���������� ����� �����������
     * @throws Sms4bException
     */
    public function getBalance(): float
    {
        return $this->balanceService->getBalance();
    }

    /**
     * �������� ����� �������� � ������� �������, � ������ ������ ���������� false
     *
     * @param $number  - ����� ��������
     *
     * @return bool|string
     */
    public function normalizePhoneNumber($number): bool|string
    {
        $number = preg_replace('/[\D]/', '', $number);

        if (preg_match(self::PHONE_PATTERN, $number)) {
            if (strlen($number) == 10) {
                $number = '7' . $number;
            } else {
                $number[0] = '7';
            }
            return $number;
        } else {
            return false;
        }
    }

    /**
     * ���������� ID �����, �� ������� ��� ������ �����
     *
     * @param int $id - id ������
     *
     * @return string - ID �����
     * @throws Sms4bException
     */
    public function GetOrderLid(int $id): string
    {
        return $this->orderService->getOrderLid($id);
    }

    /**
     * ���������� ����� �������� ������
     *
     * @param int    $orderId - id ������
     * @param string $site    - ������������� �����
     *
     * @return string|bool - ����� �������� �� ������ ��� false ���� ����� �� ������
     * @throws Sms4bException
     */
    public function GetPhoneOrder(int $orderId, string $site)
    {
        return $this->orderService->getPhoneOrder($orderId, $site);
    }

    /**
     * ���������� ������ ��������� �������
     *
     * @param string $id_template - ��� ��������� �������
     * @param string $site        - ������������� �����
     * @param string $from        - ���� "�� ����"
     *
     * @return array|bool - ������ � ������� ��������� ������� ���� false ���� ������ �� ������
     */
    public function GetEventTemplate(string $id_template, string $site, string $from = ''): bool|array
    {
        try {
            return $this->templateService->getCustomSmsTemplate($id_template, $site, $from);
        } catch (Sms4bException $e) {
            return false;
        }
    }

    /**
     * ���������� ����� ��������� ������� �� ID
     *
     * @param int $id - ID ��������� �������
     *
     * @return string - ����� ��������� �������
     * @throws Sms4bException
     */
    public function GetEventTemplateFromId(int $id = 0): string
    {
        return $this->templateService->getTemplateTextById($id);
    }

    /**
     * ���������� ������ ������� ���������������
     *
     * @param string $site - ID �����
     *
     * @return array - ������ ������� ���������������
     * @throws Sms4bException
     */
    public function GetAdminPhones(string $site): array
    {
        $result = [];
        $phones = $this->configService->getSettingByName('admin_phone', $site);
        $phones = str_replace(',', ';', $phones);
        $arr = explode(';', $phones);
        foreach ($arr as $phone) {
            if ($this->normalizePhoneNumber($phone)) {
                $result[] = $phone;
            }
        }
        return $result;
    }

    /**
     * ���������� �������� ��������� ������ �� ��
     *
     * @param string $option - �������� ���������
     * @param string $site   - ������������� �����
     *
     * @return string - �������� ��������� ������ �� ��
     * @throws Sms4bException
     */
    public function GetCurrentOption(string $option, string $site = ''): string
    {
        return $this->configService->getSettingByName($option, $site);
    }

    /**
     * �������� ����������� ������� � ������
     * �������� ������� ��-��������� � ������
     *
     * @param string $text - �������� ����� ��� ������
     *
     * @return string - ����� � ����������� ������������ ���������
     */
    public function replaceDefaultMakros(string $text): string
    {
        return $this->templateService->replaceDefaultMakros($text);
    }

    /**
     * ���������� ������� OnBeforeEventAdd
     * ����� ������ ���� ����������� ������ ��� � ���� ���������� ����������
     *
     * @param string       $event_name - �������� ���� ��������� �������
     * @param array|string $site       - ������������� �����
     * @param array        $params     - ������� ��������� ��������� �������
     *
     * @return bool
     * @throws Sms4bException
     */
    public static function Events(string $event_name, array|string $site, array &$params)
    {
        $sms4b = new Csms4b();

        $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_START_SEND_EVENT', ['#EVENT#' => $event_name]));

        $idSaleStatusChanged = '';
        if (preg_match("/^SALE_STATUS_CHANGED_(.+?){1}$/", $event_name, $find)) {
            $event_name = 'SALE_STATUS_CHANGED';
            $idSaleStatusChanged = $find[1];
        }

        if (!is_array($site)) {
            $site = [$site];
        }
        foreach ($site as $siteId) {
            switch ($event_name) {
                case 'SALE_STATUS_CHANGED':
                    $b_send = $sms4b->GetCurrentOption('event_sale_status_' . $idSaleStatusChanged, $siteId);
                    $b_send_admin = $sms4b->GetCurrentOption('admin_event_sale_status_' . $idSaleStatusChanged, $siteId);
                    $orderData = $sms4b->GetOrderData([$params['ORDER_REAL_ID']]);
                    $arFieldsMacros = array_merge($orderData[$params['ORDER_REAL_ID']], $params);
                    $arFieldsMacros['PHONE_TO'] = $sms4b->GetPhoneOrder($params['ORDER_ID'], $siteId);

                    if ($b_send === 'Y') {
                        try {
                            $template = $sms4b->templateService->getUserTemplate($event_name . '_' . $idSaleStatusChanged, $siteId);
                            $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $arFieldsMacros);

                            $sms4b->sender->saleSendSms([$arFieldsMacros['PHONE_TO'] => $text], [$arFieldsMacros['ORDER_REAL_ID']], $event_name . '_' . $idSaleStatusChanged, $siteId);
                        } catch (Sms4bException | Sms4bValidationError $e) {
                            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                                ['#ERROR#' => $e->getMessage()]));
                        }

                    }
                    if ($b_send_admin === 'Y') {
                        try {
                            $template = $sms4b->templateService->getAdminTemplate($event_name . '_' . $idSaleStatusChanged, $siteId);
                            $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $arFieldsMacros);

                            $phones = $sms4b->GetAdminPhones($siteId);
                            $arPhoneMessages = [];
                            foreach ($phones as $phone) {
                                $arPhoneMessages[$phone] = $text;
                            }

                            $sms4b->sender->saleSendSms($arPhoneMessages, [$arFieldsMacros['ORDER_REAL_ID']], $event_name . '_' . $idSaleStatusChanged, $siteId);
                        } catch (Sms4bException | Sms4bValidationError $e) {
                            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                                ['#ERROR#' => $e->getMessage()]));
                        }
                    }
                    break;
                case 'SUBSCRIBE_CONFIRM':
                    $b_send = $sms4b->GetCurrentOption('event_subscribe_confirm', $siteId);
                    $b_send_admin = $sms4b->GetCurrentOption('event_subscribe_confirm', $siteId);

                    if ($b_send === 'Y') {
                        try {
                            $template = $sms4b->templateService->getUserTemplate($event_name, $siteId);
                            $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $params);
                            $defSender = $sms4b->configService->getDefaultSender($siteId);
                        } catch (Sms4bException $e) {
                            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                                ['#ERROR#' => $e->getMessage()]));
                        }

                        if (preg_match("/^([\+\-\(\)0-9]+?)@phone.sms$/i", $params['EMAIL'], $find)) {
                            $phone_num = $sms4b->normalizePhoneNumber($find[1]);
                            if ($phone_num) {
                                try {
                                    $sms4b->sender->subscriptionsSendSms([$phone_num => $text], $defSender);
                                } catch (Sms4bException | Sms4bValidationError $e) {
                                    $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                                        ['#ERROR#' => $e->getMessage()]));
                                }

                                global $APPLICATION;
                                $params['EMAIL'] = '';
                                $APPLICATION->ThrowException(Loc::getMessage('SMS4B_MAIN_CODE_SEND'));
                                return false;
                            } else {
                                $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_EMPTY_SEND_PROP'));
                            }
                        }
                    }
                    if ($b_send_admin === 'Y') {
                        try {
                            $template = $sms4b->templateService->getAdminTemplate($event_name, $siteId);
                            $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $params);
                        } catch (Sms4bException $e) {
                            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                                ['#ERROR#' => $e->getMessage()]));
                        }

                        if (is_array($text)) {
                            $phones = $sms4b->GetAdminPhones($siteId);
                            $arPhoneMessages = [];
                            foreach ($phones as $phone) {
                                $arPhoneMessages[$phone] = $text;
                            }
                            try {
                                $sms4b->sender->mailEventSendSms($arPhoneMessages, $event_name, $siteId);
                            } catch (Sms4bException | Sms4bValidationError $e) {
                                $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                                    ['#ERROR#' => $e->getMessage()]));
                            }
                            global $APPLICATION;
                            $params['EMAIL'] = '';
                            $APPLICATION->ThrowException(Loc::getMessage('SMS4B_MAIN_CODE_SEND'));
                            return false;
                        } else {
                            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_EMPTY_SEND_PROP'));
                        }
                    }
                    break;
                case 'SALE_ORDER_CANCEL':
                case 'SALE_NEW_ORDER':
                case 'SALE_ORDER_DELIVERY':
                case 'SALE_ORDER_PAID':
                    $b_send = $sms4b->GetCurrentOption('event_' . strtolower($event_name), $siteId);
                    $b_send_admin = $sms4b->GetCurrentOption('admin_event_' . strtolower($event_name), $siteId);
                    $orderData = $sms4b->GetOrderData([$params['ORDER_REAL_ID']]);
                    $arFieldsMacros = array_merge($orderData[$params['ORDER_REAL_ID']], $params);
                    $arFieldsMacros['PHONE_TO'] = $sms4b->GetPhoneOrder($params['ORDER_ID'], $siteId);

                    if ($b_send === 'Y') {
                        try {
                            $template = $sms4b->templateService->getUserTemplate($event_name, $siteId);
                            $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $arFieldsMacros);

                            $sms4b->sender->saleSendSms([$arFieldsMacros['PHONE_TO'] => $text], [$arFieldsMacros['ORDER_REAL_ID']], $event_name,
                                $siteId);
                        } catch (Sms4bException | Sms4bValidationError $e) {
                            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                                ['#ERROR#' => $e->getMessage()]));
                        }
                    }

                if ($b_send_admin === 'Y') {
                        try {
                            $template = $sms4b->templateService->getAdminTemplate($event_name, $siteId);
                            $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $arFieldsMacros);

                            $phones = $sms4b->GetAdminPhones($siteId);
                            $arPhoneMessages = [];
                            foreach ($phones as $phone) {
                                $arPhoneMessages[$phone] = $text;
                            }

                            $sms4b->sender->saleSendSms($arPhoneMessages, [$arFieldsMacros['ORDER_REAL_ID']], $event_name, $siteId);
                        } catch (Sms4bException | Sms4bValidationError $e) {
                            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                                ['#ERROR#' => $e->getMessage()]));
                        }
                    }
                    break;

                case 'TICKET_NEW_FOR_TECHSUPPORT':
                case 'TICKET_CHANGE_FOR_TECHSUPPORT':
                    $b_send = $sms4b->GetCurrentOption('event_ticket_new_for_techsupport', $siteId);
                    $b_send_admin = $sms4b->GetCurrentOption('admin_event_ticket_new_for_techsupport', $siteId);

                    if ($b_send === 'Y') {
                        //take groups id of support-group and admins
                        $sgroup = array_merge(CTicket::GetGroupsByRole('T'), CTicket::GetGroupsByRole('W'));
                        $filter = ['ACTIVE' => 'Y'];
                        if ($params['RESPONSIBLE_USER_ID'] == '') {
                            $filter['GROUPS_ID'] = $sgroup;
                            $filter['EMAIL'] = $params['SUPPORT_EMAIL'];
                        } else {
                            $filter['ID'] = $params['RESPONSIBLE_USER_ID'];
                        }
                        try {
                            $template = $sms4b->templateService->getUserTemplate($event_name, $siteId);
                            $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $params);
                        } catch (Sms4bException $e) {
                            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                                ['#ERROR#' => $e->getMessage()]));
                        }

                        $rsUsers = CUser::GetList($by = 'id', $order = 'desc', $filter);
                        while ($ob = $rsUsers->Fetch()) {
                            $phone_num = $ob['WORK_PHONE'];
                            try {
                                $sms4b->sender->supportSendSms([$phone_num => $text], $params['ID'], $event_name, $siteId);
                            } catch (Sms4bException | Sms4bValidationError $e) {
                                $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                                    ['#ERROR#' => $e->getMessage()]));
                            }
                        }
                    }
                    if ($b_send_admin === 'Y') {
                        try {
                            $template = $sms4b->templateService->getAdminTemplate($event_name, $siteId);
                            $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $params);

                            $phones = $sms4b->GetAdminPhones($siteId);
                            $arPhoneMessages = [];
                            foreach ($phones as $phone) {
                                $arPhoneMessages[$phone] = $text;
                            }

                            $sms4b->sender->supportSendSms($arPhoneMessages, $params['ID'], $event_name, $siteId);
                        } catch (Sms4bException | Sms4bValidationError $e) {
                            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                                ['#ERROR#' => $e->getMessage()]));
                        }
                    }
                    break;
                default:
                    //customised post event
                    try {
                        $userTemplate = $sms4b->templateService->getTemplateForSmsEvent($event_name, $siteId, 'SMS4B_USER');
                        $userPhone = $userTemplate['EMAIL_TO'];
                    } catch (Sms4bException $e) {
                        $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                            ['#ERROR#' => $e->getMessage()]));
                    }

                    try {
                        $adminTemplate = $sms4b->templateService->getTemplateForSmsEvent($event_name, $siteId, 'SMS4B_ADMIN');
                    } catch (Sms4bException $e) {
                        $sms4b->debugService->writeToLogFile(
                            Loc::getMessage('SMS4B_SMS_SEND_ERROR', ['#ERROR#' => $e->getMessage()])
                        );
                    }

                    if (!empty($userTemplate)) {
                        if ($sms4b->normalizePhoneNumber($userPhone)) {
                            $userPhoneSend = $sms4b->normalizePhoneNumber($userPhone);
                        } else {
                            $macro = $params[trim($userPhone, '#')];

                            if ($userPhone === '#ORDER_ID#') {
                                $userPhoneSend = $sms4b->normalizePhoneNumber($sms4b->GetPhoneOrder($params['ORDER_ID'], $siteId));
                            } else {
                                if ($sms4b->normalizePhoneNumber($macro)) {
                                    $userPhoneSend = $sms4b->normalizePhoneNumber($macro);
                                } else {
                                    $userPhoneSend = $sms4b->normalizePhoneNumber($sms4b->SearchUserPhone($macro));
                                }
                            }
                        }

                        $text = $sms4b->templateService->fillInTemplateWithMacros($userTemplate['MESSAGE'], $params);
                        try {
                            $sms4b->sender->mailEventSendSms([$userPhoneSend => $text], $event_name, $siteId);
                        } catch (Sms4bException | Sms4bValidationError $e) {
                            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                                ['#ERROR#' => $e->getMessage()]));
                        }
                    }
                    if (!empty($adminTemplate)) {
                        try {
                            $text = $sms4b->templateService->fillInTemplateWithMacros($adminTemplate['MESSAGE'], $params);
                            $phones = $sms4b->GetAdminPhones($siteId);
                            $arPhoneMessages = [];
                            foreach ($phones as $phone) {
                                $arPhoneMessages[$phone] = $text;
                            }
                            $sms4b->sender->mailEventSendSms($arPhoneMessages, $event_name, $siteId);
                        } catch (Sms4bException | Sms4bValidationError $e) {
                            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                                ['#ERROR#' => $e->getMessage()]));
                        }
                    }
                    break;
            }
        }
        $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_END_SEND_EVENT',
                ['#EVENT#' => $event_name]) . PHP_EOL);
    }

    /**
     * ����� �������� ������������
     *
     * @param mixed $value - email ��� ID ������������
     *
     * @return - ����� ��������
     * @throws Sms4bException
     */
    public function SearchUserPhone($value)
    {
        if ($this->configService->isSiteExists(SITE_ID)) {
            $siteId = SITE_ID;
        } else {
            $siteId = $this->configService->getSiteIdDefault();
        }
        $propertyPhone = $this->configService->getSettingByName('user_property_phone', $siteId);

        $filter = ['ACTIVE' => 'Y'];
        $userPhone = false;

        //��� ID?
        if (is_numeric($value)) {
            $filter['ID'] = $value;
        }//��� Email?
        elseif (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $filter['EMAIL'] = $value;
        } else {
            return $userPhone;
        }

        if (strlen(trim($propertyPhone)) > 0) {
            $rsUsers = CUser::GetList($by = 'ID', $order = 'asc', $filter, ['SELECT' => ['UF_*']]);
            if ($ob = $rsUsers->Fetch()) {
                $userPhone = $ob[$propertyPhone];
            }
        } else {
            $rsUsers = CUser::GetList($by = 'ID', $order = 'asc', $filter);
            if ($ob = $rsUsers->Fetch()) {
                if ($this->normalizePhoneNumber($ob['PERSONAL_PHONE'])) {
                    $userPhone = $this->normalizePhoneNumber($ob['PERSONAL_PHONE']);
                } elseif ($this->normalizePhoneNumber($ob['PERSONAL_MOBILE'])) {
                    $userPhone = $this->normalizePhoneNumber($ob['PERSONAL_MOBILE']);
                } elseif ($this->normalizePhoneNumber($ob['WORK_PHONE'])) {
                    $userPhone = $this->normalizePhoneNumber($ob['WORK_PHONE']);
                }
            }
        }
        return $userPhone;
    }

    /**
     * ���������� ������� OnTaskAdd
     * ����� ������ ���� ����������� ������ ��� � ���� ���������� ����������
     *
     * @param int   $ID       - ID ������
     * @param array $arFields - ������ � ������� ������
     *
     * @throws Sms4bException
     */
    public static function TaskAdded(int $ID, array $arFields)
    {
        $sms4b = new Csms4b();

        //��� ��������� ������� ������� #TASK#
        $arFields['TASK'] = $arFields['TITLE'];

        if ($sms4b->CheckTaskPriority($arFields['PRIORITY'], 'add', SITE_ID)
            === 'Y' && $sms4b->checkGroupPerm($arFields['GROUP_ID'])
        ) {
            try {
                $template = $sms4b->templateService->getUserTemplate('TASK_ADD', SITE_ID);
                $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $arFields);
                $phone_num = $sms4b->normalizePhoneNumber($sms4b->SearchUserPhone($arFields['RESPONSIBLE_ID']));
                $sms4b->sender->taskSendSms([$phone_num => $text], $ID, SITE_ID);
            } catch (Sms4bException | Sms4bValidationError $e) {
                $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                    ['#ERROR#' => $e->getMessage()]));
            }
        }
        if ($sms4b->CheckTaskPriority($arFields['PRIORITY'], 'admin_add', SITE_ID)
            === 'Y' && $sms4b->checkGroupPerm($arFields['GROUP_ID'])
        ) {
            try {
                $template = $sms4b->templateService->getAdminTemplate('TASK_ADD', SITE_ID);
                $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $arFields);
                $adminPhones = $sms4b->GetAdminPhones(SITE_ID);
                $arPhoneMessages = [];
                foreach ($adminPhones as $phone) {
                    $arPhoneMessages[$phone] = $text;
                }
                $sms4b->sender->taskSendSms($arPhoneMessages, $ID, SITE_ID);
            } catch (Sms4bException | Sms4bValidationError $e) {
                $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                    ['#ERROR#' => $e->getMessage()]));
            }
        }
    }

    /**
     * ���������� ������� OnTaskUpdate
     * ����� ������ ���� ����������� ������ ��� � ���� ���������� ����������
     *
     * @param int   $ID       - ID ������
     * @param array $arFields - ������ � ������� ������
     *
     * @return void - ��������� ����������
     * @throws Sms4bException
     */
    public static function TaskUpdated(int $ID, array $arFields): void
    {
        $sms4b = new Csms4b();
        //��� ��������� ������� ������� #TASK#
        $arFields['TASK'] = $arFields['TITLE'];

        if ($sms4b->CheckTaskPriority($arFields['PRIORITY'], 'update', SITE_ID)
            === 'Y' && $sms4b->checkGroupPerm($arFields['GROUP_ID'])
        ) {
            try {
                $phone_num = $sms4b->normalizePhoneNumber($sms4b->SearchUserPhone($arFields['RESPONSIBLE_ID']));
                $template = $sms4b->templateService->getUserTemplate('TASK_UPDATE', SITE_ID);
                $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $arFields);
                $sms4b->sender->taskSendSms([$phone_num => $text], $ID, SITE_ID);
            } catch (Sms4bException | Sms4bValidationError $e) {
                $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                    ['#ERROR#' => $e->getMessage()]));
            }
        }
        if ($sms4b->CheckTaskPriority($arFields['PRIORITY'], 'admin_update', SITE_ID)
            === 'Y' && $sms4b->checkGroupPerm($arFields['GROUP_ID'])
        ) {
            try {
                $template = $sms4b->templateService->getAdminTemplate('TASK_ADD', SITE_ID);
                $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $arFields);
                $adminPhones = $sms4b->GetAdminPhones(SITE_ID);
                $arPhoneMessages = [];
                foreach ($adminPhones as $phone) {
                    $arPhoneMessages[$phone] = $text;
                }
                $sms4b->sender->taskSendSms($arPhoneMessages, $ID, SITE_ID);
            } catch (Sms4bException | Sms4bValidationError $e) {
                $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                    ['#ERROR#' => $e->getMessage()]));
            }
        }
    }

    /**
     * ���������� ������� OnBeforeTaskDelete
     * ����� ������ ���� ����������� ������ ��� � ���� ���������� ����������
     *
     * @param int   $ID       - ID ������
     * @param array $arFields - ������ � ������� ������
     *
     * @return void - ��������� ����������
     * @throws Sms4bException
     */
    public static function BeforeTaskDeleted(int $ID, array $arFields): void
    {
        $sms4b = new Csms4b();
        //��� ��������� ������� ������� #TASK#
        $arFields['TASK'] = $arFields['TITLE'];

        if ($sms4b->CheckTaskPriority($arFields['PRIORITY'], 'delete', SITE_ID)
            === 'Y' && $sms4b->checkGroupPerm($arFields['GROUP_ID'])
        ) {
            try {
                $phone_num = $sms4b->normalizePhoneNumber($sms4b->SearchUserPhone($arFields['RESPONSIBLE_ID']));
                $template = $sms4b->templateService->getUserTemplate('TASK_DELETE', SITE_ID);
                $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $arFields);
                $sms4b->sender->taskSendSms([$phone_num => $text], $ID, SITE_ID);
            } catch (Sms4bException | Sms4bValidationError $e) {
                $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                    ['#ERROR#' => $e->getMessage()]));
            }
        }
        if ($sms4b->CheckTaskPriority($arFields['PRIORITY'], 'admin_delete', SITE_ID)
            === 'Y' && $sms4b->checkGroupPerm($arFields['GROUP_ID'])
        ) {
            try {
                $template = $sms4b->templateService->getAdminTemplate('TASK_ADD', SITE_ID);
                $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $arFields);
                $adminPhones = $sms4b->GetAdminPhones(SITE_ID);
                $arPhoneMessages = [];
                foreach ($adminPhones as $phone) {
                    $arPhoneMessages[$phone] = $text;
                }
                $sms4b->sender->taskSendSms($arPhoneMessages, $ID, SITE_ID);
            } catch (Sms4bException | Sms4bValidationError $e) {
                $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                    ['#ERROR#' => $e->getMessage()]));
            }
        }
    }

    /**
     * ���������� ������� OnAfterCommentAdd
     * ����� ������ ���� ����������� ������ ��� � ���� ���������� ����������
     *
     * @param string $eventName   - �������� �������
     * @param string $resolveFlag - �������� ����� ���������� ��������
     * @param int    $id          - ID �����������
     * @param array  $comment     - ������ � ������� �����������
     *
     * @throws ArgumentException - ���������� ���������� ����������
     * @throws LoaderException - ���������� ����������� ������
     * @throws ObjectPropertyException
     * @throws Sms4bException
     * @throws SystemException
     */
    public static function AddNewCommentTask(string $eventName, string $resolveFlag, int $id, array $comment)
    {
        $sms4b = new Csms4b();
        if ($sms4b->GetCurrentOption($resolveFlag, SITE_ID) === 'Y') {
            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_START_EVENT_ADD_COMMENT_TASKS'));

            if (!Loader::includeModule('tasks')) {
                $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_LOG_DO_NOT_INSTALL_TASKS'));
                $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_END_EVENT_ADD_COMMENT_TASKS') . PHP_EOL);
                return false;
            }
            $result = Bitrix\Tasks\TaskTable::getList([
                'select' => ['*'],
                'filter' => [
                    '=ID' => $comment['TASK_ID']
                ]
            ]);

            $res = $result->Fetch();

            if (!empty($id) && !empty($res) && !empty($comment['TASK_ID']) && $sms4b->checkGroupPerm($res['GROUP_ID'])) {
                $res['COMMENT_TEXT'] = $comment['COMMENT_TEXT'];

                $responsible = $sms4b->normalizePhoneNumber($sms4b->SearchUserPhone($res['RESPONSIBLE_ID']));

                $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_RESPONS_ID') . $res['RESPONSIBLE_ID']);
                $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_LOG_GET_PHONE') . $responsible);

                try {
                    $template = $sms4b->templateService->getUserTemplate(strtoupper($resolveFlag), SITE_ID);
                    $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $res);
                    $sms4b->sender->taskSendSms([$responsible => $text], $comment['TASK_ID'], SITE_ID);
                } catch (Sms4bException | Sms4bValidationError $e) {
                    $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                        ['#ERROR#' => $e->getMessage()]));
                }
            } else {
                $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_NO_SEARCH_TASKS'));
            }
            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_END_EVENT_ADD_COMMENT_TASKS') . PHP_EOL);
        }
    }

    /**
     * @param integer $userID - ID ������������
     *
     * @return string - ����� �������� ��� false
     * @throws Sms4bException
     * @deprecated use SearchUserPhone($value)
     *             ���������� ������� ������������ �� ��� ID
     */
    public function GetUserPhone(int $userID): string
    {
        return $this->SearchUserPhone($userID);
    }

    /**
     * �������� ����������� �������� ��������� ��� ������������ �����
     *
     * @param int    $priority - ������ ���������� ������
     * @param string $task     - �������� ��� �������
     * @param string $site     - ID �����
     *
     * @return string - ������ �� �� ('Y' ��� '')
     * @throws Sms4bException
     */
    public function CheckTaskPriority(int $priority, string $task, string $site): string
    {
        return $this->taskService->checkTaskPriority($priority, $task, $site);
    }

    /**
     * ���������� ������� BeforePostingSendMail
     * ����� ������ ���� ����������� ������ ��� � ���� ���������� ����������
     *
     * @param array $arFields - ������ � ������� ��������
     *
     * @return bool|array - ��������� ����������
     * @throws Sms4bException
     * @throws Sms4bValidationError
     */
    public static function EventsPosting(array $arFields): bool|array
    {
        $obPosting = new CPosting();

        $rsPosting = $obPosting::GetByID($arFields['POSTING_ID']);
        $arPosting = $rsPosting->Fetch();
        $rass_from = $arPosting['FROM_FIELD'];
        $rass_to = $arFields['EMAIL'];

        $sms4b = new Csms4b();
        if (preg_match("/^([\+\-\(\)0-9a-zA-Z]+?)@phone.sms$/", $rass_from,
                $find) || strtoupper($rass_from) === 'PHONE@PHONE.SMS'
        ) {
            $rass_from = strtoupper($rass_from) === 'PHONE@PHONE.SMS' ? $sms4b->configService->getDefaultSender('') : $find[1];

            preg_match("/^([\+\-\(\)0-9]+?)@phone.sms$/", $rass_to, $find);

            $phone_num = $sms4b->normalizePhoneNumber($find[1]);

            if ($rass_from && $phone_num) {
                //only if message of type "text"
                if ($arPosting['BODY_TYPE'] === 'text') {
                    $sms4b->sender->subscriptionsSendSms([$phone_num => $arFields['BODY']], $rass_from);
                }
            }
        } elseif (preg_match("/^([\+\-\(\)0-9]+?)@phone.sms$/", $rass_to, $find)) {
            return false;
        }

        return $arFields;
    }


    /**
     * @param string $message    - ����� ���������
     * @param string $to         - ������ � �������
     * @param string $sender     - �����������
     * @param int    $IDOrder    - ID ������
     * @param int    $Posting    - ID ��������
     * @param string $TypeEvents - ��� �������
     *
     * @return bool - ��������� ����������
     * @throws Sms4bException
     * @deprecated 1.4.0\Csms4b::sendSms
     */
    public function SendSMS(
        string $message,
        string $to,
        string $sender = '',
        int $IDOrder = 0,
        int $Posting = 0,
        string $TypeEvents = ''
    ): bool
    {
        /**
         * � ����� ���� ��� ������ ��������, ������� ��������� ��� ������������� ���������.
         * $IDOrder � $TypeEvents - ������ ��� ������ �������
         * $Posting - � ������ ������ �������� ������ ��� ����������� � ������
         */
        $sender = $sender ?? '';
        try {
            $this->sender->customSendSms([$to => $message], $sender);
            return true;
        } catch (Sms4bException | Sms4bValidationError $e) {
            $this->debugService->writeToLogFile($e->getMessage());
            return false;
        }
    }

    /**
     * @param string $message      - ����� ���������
     * @param mixed  $to           - ������ ��� ������ � ��������
     * @param string $sender       - �����������
     * @param string $startUp_p    - ���� ������ ��������
     * @param string $dateActual_p - ���� ������������ ��������
     * @param string $period_p     - ������ �������� � ������������ ������ �������� � ��������� �������������
     *
     * @return array - ��������� ����������
     * @throws Sms4bException
     * @deprecated 1.4.0 use \Csms4b::sendDelayedSms
     */
    public function SendSmsPack(
        string $message,
        mixed $to,
        string $sender = '',
        string $startUp_p = '',
        string $dateActual_p = '',
        string $period_p = ''
    ): array {
        try {
            $arPhonesMessages = [];
            if (is_array($to)) {
                foreach ((array)$to as $phone) {
                    $arPhonesMessages[$phone] = $message;
                }
            } else {
                $arPhonesMessages[$to] = $message;
            }

            $dateStart = empty($startUp_p) ? new \DateTime('now') : new \DateTime($startUp_p);
            $dateActual = empty($dateActual_p) ? null : new \DateTime($dateActual_p);
            $period = empty($period_p) ? null : $period_p;
            $sender = $sender ?? '';

            $sendingInQueue = $this->sender->delaySendSms($arPhonesMessages, $sender, $dateStart, $dateActual, $period);

            return [
                'WAS_SEND' => $sendingInQueue->getMessages()->count(),
                'NOT_SEND' => count($arPhonesMessages) - $sendingInQueue->getMessages()->count()
            ];
        } catch (\Exception $e) {
            $this->debugService->writeToLogFile($e->getMessage());
            return ['WAS_SEND' => 0, 'NOT_SEND' => count($arPhonesMessages)];
        }
    }

    /**
     * �������� ��������� �� ��������� �������
     *
     * @param array  $arPhonesMessages - ������ ���� array("PHONE"=>"MESSAGE")
     * @param mixed  $sender           - �����������
     * @param string $startSend        - ���� ������ ��������
     * @param string $dateActual       - ���� ������������ ��������
     * @param string $period           - ������ �������� � ������������ ������ �������� � ��������� �������������
     * @param mixed  $regular          - ���� ���������� ��������
     * @param int    $orderId          - ID ������
     * @param string $typeEvent        - �������� �������
     * @param string $posting          - ID �������� ��������
     *
     * @return array - ��������� ����������
     * @throws Sms4bException
     * @deprecated see \Csms4b::sendDelayedSms
     */
    public function SendSmsSaveGroup(
        array $arPhonesMessages,
        mixed $sender = '',
        string $startSend = '',
        string $dateActual = '',
        string $period = '',
        mixed $regular = '',
        int $orderId = 0,
        string $typeEvent = '',
        string $posting = ''
    ):array {
        //$Posting - � ������ ������ �������� ������ ��� ����������� � ������, � ����� ���� �� ������������
        //TODO ����� �������� $regular - ����������� �������� �������� (-2)
        //$regular = !empty($regular) ? -2 : -1;
        try {
            $dateStart = empty($startSend) ? new \DateTime('now') : new \DateTime($startSend);
            $dateActual = empty($dateActual) ? null : new \DateTime($dateActual);
        } catch (\Exception $e) {
            throw new Sms4bException(Loc::getMessage('SMS4B_DATE_TIME_FORMAT_ERROR'), $e->getCode(), $e);
        }
        $sender = $sender ?? '';
        $period = empty($period) ? null : $period;

        if ($this->configService->getProcessingOldEvent() === 'Y') {
            $arEvents = EventManager::getInstance()->findEventHandlers('rarus.sms4b', 'OnBeforeSmsSend');
            foreach ($arEvents as $arEvent) {
                ExecuteModuleEventEx($arEvent, [&$arPhonesMessages, $typeEvent, $sender, $orderId]);
            }
        }
        try {
            $sendingInQueue = $this->sender->delaySendSms($arPhonesMessages, $sender, $dateStart, $dateActual, $period);
        } catch (Sms4bValidationError $e) {
            throw new Sms4bException($e->getMessage(), $e->getCode(), $e);
        }
        $encodingService = new Service();
        $arParam = [];
        foreach ($sendingInQueue->getMessages() as $message) {
            $arParam[] = [
                'GUID'        => $message->getGuid(),
                'SenderName'  => $sendingInQueue->getSender(),
                'Destination' => $message->getDestination(),
                'StartSend'   => $sendingInQueue->getStartDate() === null ? new \Bitrix\Main\Type\DateTime() : \Bitrix\Main\Type\DateTime::createFromPhp($sendingInQueue->getStartDate()),
                'CodeType'    => $message->getEncoding(),
                'TextMessage' => $encodingService->decodeMessage($message->getText(),
                    Encoding::createInstanceByEncodingCode($message->getEncoding())),
            ];
        }

        return $arParam;
    }

    /**
     * ����� ��� �������� ���������� ������ ��������
     *
     * @param $number - ����� ��������
     *
     * @return string|false - ����� ��� false
     * @deprecated see \Csms4b::normalizePhoneNumber
     */
    public function is_phone($number): bool|string
    {
        return $this->normalizePhoneNumber($number);
    }

    /**
     * ���������� ���������� ��� �����������
     *
     * @return array - ���������� ����� �����������
     * @throws Sms4bException
     * @deprecated see \Csms4b::getAllSenders
     */
    public function GetSender(): array
    {
        return $this->addressService->getAllAddresses();
    }

    /**
     * ���������� ������� CRM
     * ����� ������ ���� ����������� ������ ��� � ���� ���������� ����������
     *
     * @param string $eventName   - ��� �������
     * @param string $resolveFlag - ��� ��������� � ������� b_option
     * @param mixed  $eventData   - ������ �������
     *
     * @return boolean - ��������� ����������
     * @throws Sms4bException
     */
    public static function CrmEventsHandler(string $eventName, string $resolveFlag, mixed $eventData)
    {
        $sms4b = new Csms4b();

        if (!CModule::IncludeModule('crm')) {
            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_LOG_DO_NOT_INSTALL_CRM'));
            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_END_CRM_EVENT') . $eventName . PHP_EOL);
            return false;
        }

        //�������� ��� ��������
        if ($resolveFlag === 'change_stat_lead_crm') {
            $canSend = $sms4b->GetCurrentOption($resolveFlag . '_' . $eventData['STATUS_ID'], SITE_ID);
            $canSendAdmin = $sms4b->GetCurrentOption('admin_' . $resolveFlag . '_' . $eventData['STATUS_ID'], SITE_ID);
        } else {
            if ($resolveFlag === 'change_stat_deal_crm') {
                $canSend = $sms4b->GetCurrentOption($resolveFlag . '_' . $eventData['STAGE_ID'], SITE_ID);
                $canSendAdmin = $sms4b->GetCurrentOption('admin_' . $resolveFlag . '_' . $eventData['STAGE_ID'],
                    SITE_ID);
            } else {
                $canSend = $sms4b->GetCurrentOption($resolveFlag, SITE_ID);
                $canSendAdmin = $sms4b->GetCurrentOption('admin_' . $resolveFlag, SITE_ID);
            }
        }

        if ($canSend === 'Y' || $canSendAdmin === 'Y') {
            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_START_CRM_EVENT') . $eventName);

            switch ($eventName) {
                //��� ������� �����
                case 'OnAfterCrmLeadAdd':
                    $eventData = array_merge($eventData, $sms4b->GetLeadData($eventData['ID']));
                    break;
                case 'OnAfterCrmLeadUpdate':
                    $eventData = array_merge($eventData, $sms4b->GetLeadData($eventData['ID']));
                    //��������� ������
                    if (!empty($_SESSION['SMS4B']['LEAD_BUF'][$eventData['ID']])) {
                        if ($eventData['STATUS_ID'] !== $_SESSION['SMS4B']['LEAD_BUF'][$eventData['ID']]) {
                            $eventData['OLD_STAT'] = $_SESSION['SMS4B']['LEAD_BUF'][$eventData['ID']];
                            $resolveFlag = 'change_stat_lead_crm_' . $eventData['STATUS_CODE'];
                        }
                        unset($_SESSION['SMS4B']['LEAD_BUF'][$eventData['ID']]);
                    }
                    break;
                case 'OnBeforeCrmLeadDelete':
                    $eventData = $sms4b->GetLeadData($eventData);
                    break;
                case 'OnBeforeCrmLeadUpdate':
                    $oldEventData = $sms4b->GetLeadData($eventData['ID']);
                    $_SESSION['SMS4B']['LEAD_BUF'][$eventData['ID']] = $oldEventData['STATUS_ID'];
                    return $eventData;
                    break;

                //��� ������� ���������
                case 'OnAfterCrmContactUpdate':
                case 'OnAfterCrmContactAdd':
                    $eventData = array_merge($eventData, $sms4b->GetContactData($eventData['ID']));
                    break;

                //��� ������� ������
                case 'OnAfterCrmDealAdd':
                    $eventData = array_merge($eventData, $sms4b->GetDealData($eventData['ID']));
                    break;
                case 'OnAfterCrmDealUpdate':
                    $eventData = array_merge($eventData, $sms4b->GetDealData($eventData['ID']));
                    if (!empty($_SESSION['SMS4B']['DEAL_BUF'][$eventData['ID']])) {
                        if ($eventData['STAGE_ID'] !== $_SESSION['SMS4B']['DEAL_BUF'][$eventData['ID']]) {
                            $eventData['OLD_STAGE'] = $_SESSION['SMS4B']['DEAL_BUF'][$eventData['ID']];
                            $resolveFlag = 'change_stat_deal_crm_' . $eventData['STAGE_CODE'];
                        }
                        unset($_SESSION['SMS4B']['DEAL_BUF'][$eventData['ID']]);
                    }
                    break;
                case 'OnAfterCrmDealDelete':
                    $eventData['ID'] = $eventData;
                    break;

                case 'OnBeforeCrmDealUpdate':
                    $oldEventData = $sms4b->GetDealData($eventData['ID']);
                    $_SESSION['SMS4B']['DEAL_BUF'][$eventData['ID']] = $oldEventData['STAGE_ID'];
                    return $eventData;
                    break;
            }

            //���������/����������� ����
            $eventData = self::dateToStringInArray($eventData);

            $sender = $sms4b->GetCurrentOption('defsender', SITE_ID);

            if ($canSend === 'Y') {
                $responsible = $eventData['RESPONSIBLE'];

                $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_LOG_GET_PHONE') . $responsible);

                if (!empty($responsible)) {
                    try {
                        $template = $sms4b->templateService->getUserTemplate(strtoupper($resolveFlag), SITE_ID);
                        $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $eventData);
                        $sms4b->sender->crmSendSms([$responsible => $text], SITE_ID);
                    } catch (Sms4bException | Sms4bValidationError $e) {
                        $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                            ['#ERROR#' => $e->getMessage()]));
                    }
                }
            }
            if ($canSendAdmin === 'Y') {
                try {
                    $template = $sms4b->templateService->getAdminTemplate(strtoupper($resolveFlag), SITE_ID);
                    $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $eventData);

                    $adminPhones = (array)$sms4b->GetAdminPhones(SITE_ID);
                    $arPhoneMessages = [];
                    foreach ($adminPhones as $phone) {
                        $arPhoneMessages[$phone] = $text;
                    }
                    $sms4b->sender->crmSendSms($arPhoneMessages, SITE_ID);
                } catch (Sms4bException | Sms4bValidationError $e) {
                    $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                        ['#ERROR#' => $e->getMessage()]));
                }
            }
            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_END_CRM_EVENT') . $eventName . PHP_EOL);
        }
    }

    /**
     * ���������� ������ � ���������� ����\��������
     *
     * @param int $id - ID ����\��������
     *
     * @return array|bool - ������ � ����������
     */
    public function GetPhonesLeadOrContact(int $id): bool|array
    {
        $data = $this->crmService->getPhonesLeadOrContact($id);

        return (!empty($data)) ? $data : false;

    }

    /**
     * ���������� ������ � ������� ����
     *
     * @param int $id - ID ����
     *
     * @return array - ������ � ������� ����
     * @throws Sms4bException
     */
    public function GetLeadData(int $id): array
    {
        return $this->crmService->getLeadData($id);
    }

    /**
     * ���������� ������ � ������� ��������
     *
     * @param int $id - ID ��������
     *
     * @return array - ������ � ������� ��������
     * @throws Sms4bException
     */
    public function GetContactData(int $id): array
    {
        return $this->crmService->getContactData($id);
    }

    /**
     * ������ ������
     *
     * @param mixed $destinationNumbers - ������ ��� ������ � ��������
     *
     * @return array - ������������ ������ �������
     */
    public function parse_numbers(mixed $destinationNumbers): array
    {
        $numbers = [];
        if (!is_array($destinationNumbers)) {
            $destinationNumbers = str_replace([',', "\n"], ';', trim($destinationNumbers));
            $sortNumbers = explode(';', $destinationNumbers);
        } else {
            $sortNumbers = $destinationNumbers;
        }

        foreach ($sortNumbers as $arInd) {
            $arInd = trim($arInd);

            $symbol = false;
            $specSym = ['+', '(', ')', ' ', '-', '_'];
            for ($i = 0, $iMax = strlen($arInd); $i < $iMax; $i++) {
                if (!is_numeric($arInd[$i]) && !in_array($arInd[$i], $specSym)) {
                    $symbol = true;
                }
            }

            if ($symbol) {
                $numbers[] = $arInd;
            } else {
                $arInd = str_replace($specSym, '', $arInd);

                $strlenArInd = strlen($arInd);
                if ($strlenArInd < 4 || $strlenArInd > 15) {
                    continue;
                } else {
                    if ($strlenArInd == 10 && $arInd[0] == '9') {
                        $arInd = '7' . $arInd;
                    }
                    if ($strlenArInd == 11 && $arInd[0] == '8') {
                        $arInd[0] = '7';
                    }
                    $numbers[] = $arInd;
                }
            }
        }

        return array_unique($numbers);
    }

    /**
     * ���������� ������ � ������� ������
     *
     * @param int $id - ID ������
     *
     * @return array - ������ � ������� ������
     * @throws Sms4bException
     */
    public function GetDealData(int $id): array
    {
        return $this->crmService->getDealData($id);
    }

    /**
     * ���������� ������ � ������
     *
     * @param bool|array $arEventId - ������ � ID ���
     *
     * @return array|bool - ������ � ������
     * @throws Sms4bException
     */
    public function GetActivityData(bool|array $arEventId): bool|array
    {
        try {
            if (!empty($arEventId) && Loader::includeModule('crm')) {

                $result = ActivityTable::getList([
                    'select' => ['*'],
                    'filter' => ['=ASSOCIATED_ENTITY_ID' => $arEventId]
                ]);

                if (is_object($result) && !empty($result)) {
                    $data = $result->Fetch();
                    $data['PRIORITY'] = CCrmActivityPriority::ResolveDescription($data['PRIORITY']);
                    $data['TYPE_ID'] = CCrmActivityType::ResolveDescription($data['TYPE_ID']);
                    $data['DIRECTION'] = CCrmActivityDirection::ResolveDescription($data['DIRECTION'], $data['TYPE_ID']);
                    $data['RESPONSIBLE'] = $this->normalizePhoneNumber($this->SearchUserPhone($data['RESPONSIBLE_ID']));

                    if (!empty($data['OWNER_ID'])) {
                        $contact = $this->GetPhonesLeadOrContact($data['OWNER_ID']);
                        $data['CONTACT_PHONE'] = reset($contact);
                    }

                    return self::dateToStringInArray($data);
                } else {
                    return false;
                }
            }
        } catch (\Exception $e) {
            throw new Sms4bException(Loc::getMessage('SMS4B_CRM_GET_ACTIVITY_DATA_ERROR'), $e->getCode(), $e);
        }

        return false;
    }

    /**
     * ����������� ������� ���������
     * ����� ������ ���� ����������� ������ ��� � ���� ���������� ����������
     *
     * @param string $eventName   - �������� �������
     * @param string $resolveFlag - �������� ��������� � ����
     * @param mixed  $eventData   - ������ �������
     *
     * @throws LoaderException - ���������� ����������� ������
     * @throws Sms4bException
     * @throws Sms4bValidationError
     * @internal param array $params - ������� ������
     */
    public static function OnRemindEvent(string $eventName, string $resolveFlag, mixed $eventData)
    {
        $sms4b = new Csms4b();
        $canSend = $sms4b->GetCurrentOption($resolveFlag, SITE_ID);
        $canSendAdmin = $sms4b->GetCurrentOption('admin_' . $resolveFlag, SITE_ID);

        if (($canSend === 'Y' || $canSendAdmin === 'Y')
            && $eventData['calType'] === 'user'
            && Loader::includeModule('calendar')
        ) {
            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_START_CALENDAR_EVENT') . $eventName);

            $activ = $sms4b->GetActivityData($eventData['eventId']);
            if (!empty($activ)) {
                if ($canSend === 'Y') {
                    try {
                        $template = $sms4b->templateService->getUserTemplate(strtoupper($resolveFlag), SITE_ID);
                        $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $activ);

                        $sms4b->sender->calendarSendSms([$activ['RESPONSIBLE'] => $text], SITE_ID);
                    } catch (Sms4bException | Sms4bValidationError $e) {
                        $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                            ['#ERROR#' => $e->getMessage()]));
                    }
                }
                if ($canSendAdmin === 'Y') {
                    try {
                        $template = $sms4b->templateService->getAdminTemplate(strtoupper($resolveFlag), SITE_ID);
                        $text = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $activ);
                        $adminPhones = $sms4b->GetAdminPhones(SITE_ID);
                        $arPhoneMessages = [];
                        foreach ($adminPhones as $phone) {
                            $arPhoneMessages[$phone] = $text;
                        }
                        $sms4b->sender->calendarSendSms($arPhoneMessages, SITE_ID);
                    } catch (Sms4bException | Sms4bValidationError $e) {
                        $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_SMS_SEND_ERROR',
                            ['#ERROR#' => $e->getMessage()]));
                    }
                }
                $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_SEND_ACTIV'));
            } else {
                $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_EMPTY_ACTIV'));
            }
            $sms4b->debugService->writeToLogFile(Loc::getMessage('SMS4B_MAIN_END_CALENDAR_EVENT') . $eventName . PHP_EOL);
        }
    }

    /**
     * ���������� SMS �� ���������� �������
     *
     * @param string $sender       - �����������
     * @param int    $responsible  - ����� ����������
     * @param string $templateName - �������� ������� (��� ��������� �������)
     * @param array  $eventData    - ������ � ���������
     * @param string $eventName    - �������� �������
     *
     * @throws Sms4bException
     */
    public function SendSmsByTemplate(
        string $sender,
        int $responsible,
        string $templateName,
        array $eventData,
        string $eventName
    )
    {
        try {
            $template = $this->templateService->getCustomSmsTemplate($templateName, SITE_ID);
            $text = $this->templateService->fillInTemplateWithMacros($template['MESSAGE'], $eventData);

            $this->sender->customSendSms([$responsible => $text], $sender);
        } catch (Sms4bException | Sms4bValidationError $e) {
            $this->debugService->writeToLogFile($e->getMessage());
        }
    }

    /**
     * ���������� SMS �� ���������� �������
     *
     * @param string $sender       - �����������
     * @param string $responsible  - ����� ����������
     * @param string $templateName - �������� ������� (��� ��������� �������)
     * @param array  $eventData    - ������ � ���������
     * @param string $forgottenBasketEvent
     * @param int    $orderId
     * @param string $siteId
     * @throws Sms4bException
     */
    public function sendSmsForgottenBasketByTemplate(
        string $sender,
        string $responsible,
        string $templateName,
        array $eventData,
        string $forgottenBasketEvent,
        int $orderId,
        string $siteId
    ):void {
        try {
            $template = $this->templateService->getCustomSmsTemplate($templateName, $siteId);
            $text = $this->templateService->fillInTemplateWithMacros($template['MESSAGE'], $eventData);

            $this->sender->forgottenBasketsSendSms([$responsible => $text], $sender, $forgottenBasketEvent, $orderId);
        } catch (Sms4bException | Sms4bValidationError $e) {
            $this->debugService->writeToLogFile($e->getMessage());
        }
    }

    /**
     * ������ � ���
     *
     * @param mixed $data - ������ ��� ������ � ���
     *
     * @throws Sms4bException
     */
    public function sms4bLog(mixed $data)
    {
        $this->debugService->writeToLogFile($data);
    }

    /**
     * ���������� �������� ������� SMS4B ��� ����� ������ �� ����������� �������
     *
     * @param string $pattern - ��������� TYPE_ID ��� ������
     * @param string $siteId  - ID �����, ��� �������� ���������� �������
     *
     * @return array - ������ �������� �������
     * @throws Sms4bException
     */
    public function GetAllSmsTemplates(string $pattern = '', string $siteId = ''): array
    {
        return $this->templateService->getAllSmsTemplate($pattern, $siteId);
    }

    /**
     * ���������� ������ � ���������
     *
     * @param string $entityType - ��� �������� (���\�������\��������)
     * @param bool   $needUf     - ���� ���������� ���������������� ����� � ������ � ���������
     *
     * @return string|bool - ������ � ���������\false
     */
    public function GetMacros(string $entityType, bool $needUf): bool|string
    {
        if (!CModule::IncludeModule('crm')) {
            return false;
        }
        return $this->crmService->getMacros($entityType, $needUf);
    }

    /**
     * ���������� ��� ������� ������
     *
     * @return array - ������ ������ ������� �����
     * @throws Sms4bException
     */
    public function GetSonetGroups(): array
    {
        return $this->taskService->getSonetGroups();
    }

    /**
     * ��������� �� �������� �������
     *
     * @param string - ID ������
     *
     * @return bool - ��������� ��������
     * @throws Sms4bException
     */
    public function checkGroupPerm(string $groupId): bool
    {
        return $this->taskService->checkGroupPerm($groupId);
    }

    /**
     * �������������� � ������� ���
     *
     * @param string $cyr_str - ������ ��� ��������������
     *
     * @return string - ������������������� ������
     */
    public function Translit(string $cyr_str): string
    {
        $tr = [
            Loc::getMessage('SMS4B_MAIN_a')   => 'a',
            Loc::getMessage('SMS4B_MAIN_b')   => 'b',
            Loc::getMessage('SMS4B_MAIN_v')   => 'v',
            Loc::getMessage('SMS4B_MAIN_g')   => 'g',
            Loc::getMessage('SMS4B_MAIN_d')   => 'd',
            Loc::getMessage('SMS4B_MAIN_e')   => 'e',
            Loc::getMessage('SMS4B_MAIN_yo')  => 'yo',
            Loc::getMessage('SMS4B_MAIN_zh')  => 'zh',
            Loc::getMessage('SMS4B_MAIN_z')   => 'z',
            Loc::getMessage('SMS4B_MAIN_i')   => 'i',
            Loc::getMessage('SMS4B_MAIN_j')   => 'j',
            Loc::getMessage('SMS4B_MAIN_k')   => 'k',
            Loc::getMessage('SMS4B_MAIN_l')   => 'l',
            Loc::getMessage('SMS4B_MAIN_m')   => 'm',
            Loc::getMessage('SMS4B_MAIN_n')   => 'n',
            Loc::getMessage('SMS4B_MAIN_o')   => 'o',
            Loc::getMessage('SMS4B_MAIN_p')   => 'p',
            Loc::getMessage('SMS4B_MAIN_r')   => 'r',
            Loc::getMessage('SMS4B_MAIN_s')   => 's',
            Loc::getMessage('SMS4B_MAIN_t')   => 't',
            Loc::getMessage('SMS4B_MAIN_u')   => 'u',
            Loc::getMessage('SMS4B_MAIN_f')   => 'f',
            Loc::getMessage('SMS4B_MAIN_h')   => 'h',
            Loc::getMessage('SMS4B_MAIN_c')   => 'c',
            Loc::getMessage('SMS4B_MAIN_ch')  => 'ch',
            Loc::getMessage('SMS4B_MAIN_sh')  => 'sh',
            Loc::getMessage('SMS4B_MAIN_shh') => 'shh',
            Loc::getMessage("\"")             => "\"",
            Loc::getMessage('SMS4B_MAIN_y')   => 'y',
            Loc::getMessage("'")              => "'",
            Loc::getMessage('SMS4B_MAIN_ye')  => 'ye',
            Loc::getMessage('SMS4B_MAIN_yu')  => 'yu',
            Loc::getMessage('SMS4B_MAIN_ya')  => 'ya',

            Loc::getMessage('SMS4B_MAIN_A')   => 'A',
            Loc::getMessage('SMS4B_MAIN_B')   => 'B',
            Loc::getMessage('SMS4B_MAIN_V')   => 'V',
            Loc::getMessage('SMS4B_MAIN_G')   => 'G',
            Loc::getMessage('SMS4B_MAIN_D')   => 'D',
            Loc::getMessage('SMS4B_MAIN_E')   => 'E',
            Loc::getMessage('SMS4B_MAIN_YO')  => 'YO',
            Loc::getMessage('SMS4B_MAIN_ZH')  => 'ZH',
            Loc::getMessage('SMS4B_MAIN_Z')   => 'Z',
            Loc::getMessage('SMS4B_MAIN_I')   => 'I',
            Loc::getMessage('SMS4B_MAIN_J')   => 'J',
            Loc::getMessage('SMS4B_MAIN_K')   => 'K',
            Loc::getMessage('SMS4B_MAIN_L')   => 'L',
            Loc::getMessage('SMS4B_MAIN_M')   => 'M',
            Loc::getMessage('SMS4B_MAIN_N')   => 'N',
            Loc::getMessage('SMS4B_MAIN_O')   => 'O',
            Loc::getMessage('SMS4B_MAIN_P')   => 'P',
            Loc::getMessage('SMS4B_MAIN_R')   => 'R',
            Loc::getMessage('SMS4B_MAIN_S')   => 'S',
            Loc::getMessage('SMS4B_MAIN_T')   => 'T',
            Loc::getMessage('SMS4B_MAIN_U')   => 'U',
            Loc::getMessage('SMS4B_MAIN_F')   => 'F',
            Loc::getMessage('SMS4B_MAIN_H')   => 'H',
            Loc::getMessage('SMS4B_MAIN_C')   => 'C',
            Loc::getMessage('SMS4B_MAIN_CH')  => 'CH',
            Loc::getMessage('SMS4B_MAIN_SH')  => 'SH',
            Loc::getMessage('SMS4B_MAIN_SHH') => 'SHH',
            Loc::getMessage("\"\"")           => "\"",
            Loc::getMessage('SMS4B_MAIN_Y')   => 'Y',
            Loc::getMessage("''")             => "'",
            Loc::getMessage('SMS4B_MAIN_YE')  => 'YE',
            Loc::getMessage('SMS4B_MAIN_YU')  => 'YU',
            Loc::getMessage('SMS4B_MAIN_YA')  => 'YA',

            Loc::getMessage('SMS4B_MAIN_<') => '<',
            Loc::getMessage('SMS4B_MAIN_>') => '>',
            Loc::getMessage('SMS4B_MAIN_-') => '-'
        ];

        $str = strtr($cyr_str, $tr);

        $str = str_replace(['^', '`'], "'", $str);
        $str = str_replace(['?'], "\"", $str);
        $str = str_replace(['{', '['], '(', $str);
        $str = str_replace(['}', ']'], ')', $str);
        $str = str_replace(['\\'], '/', $str);
        $str = str_replace(['_', '~'], '-', $str);
        $str = str_replace(['|'], 'i', $str);
        $str = str_replace(['?'], 'N', $str);

        return $str;
    }

    /**
     * ��������� ��������� �������� ��� � ������ �������\���������� ��������� ��������
     * ����� ������ ���� ����������� ������ ��� � ���� ���������� ����������
     *
     * @param $list - ������ �� ������ ������ CAdminList
     *
     * @return bool
     * @throws Sms4bException
     */
    public static function OnAdminListDisplayHandler(&$list)
    {
        if (!IsModuleInstalled('rarus.sms4b')) {
            return false;
        }
        $sms4b = new Csms4b();

        if (self::CheckSaleOrderPage()) {
            $arDefaultTemplates = $sms4b->GetAllSmsTemplates('SMS4B_SALE_%', $_REQUEST['filter_lang'] ?: false);
            $arCustomTemplates = $sms4b->GetAllSmsTemplates('SMS4B_USER_CUSTOM_EVENT',
                $_REQUEST['filter_lang'] ?: false);
            $customTemplates = $arCustomTemplates['SMS4B_USER_CUSTOM_EVENT'];
        } elseif (self::CheckUserAdminPage()) {
            $arCustomTemplates = $sms4b->GetAllSmsTemplates('SMS4B_USER_LIST_CUSTOM_EVENT');
            $customTemplates = $arCustomTemplates['SMS4B_USER_LIST_CUSTOM_EVENT'];
        } else {
            return false;
        }

        if (self::CheckUserAdminPage()) {
            $arSelectValue = [];
            foreach ($customTemplates as $k => $v) {
                $arSelectValue[] = [
                    'NAME'  => $v['NAME'],
                    'VALUE' => $v['ID']
                ];
            }
            $list->arActions = array_merge(['sms4b_send_sms' => ['lable' => Loc::getMessage('SMS4B_MAIN_ORDER_LIST_MENU_SEND_SMS_BRAND')]],
                $list->arActions);
            $list->arActions['sms4b_send_sms']['type'] = 'select';
            $list->arActions['sms4b_send_sms']['name'] = 'sms4b_send_sms_list_admin';
            $list->arActions['sms4b_send_sms']['items'] = $arSelectValue;
        } else {
            $html = self::GetSelectHtmlOnAdminList($arDefaultTemplates, $customTemplates);

            $list->arActions = array_merge(['sms4b_send_sms' => Loc::getMessage('SMS4B_MAIN_ORDER_LIST_MENU_SEND_SMS_BRAND')],
                $list->arActions);
            $list->arActions['sms4b_send_sms_list'] = ['type' => 'html', 'value' => $html];

            $list->arActionsParams['select_onchange'] .= "
            BX('sms4b_send_sms_list').style.display = (this.value == 'sms4b_send_sms' ? 'block':'none');
            if(this.value == 'sms4b_send_sms')
            {
                BX('form_tbl_sale_order').elements['apply'].value = '" . Loc::getMessage('SMS4B_MAIN_ORDER_LIST_MENU_SEND_SMS') . "';
            }
            else
            {
                BX('form_tbl_sale_order').elements['apply'].value = '" . Loc::getMessage('SMS4B_MAIN_ORDER_LIST_MENU_APPLY') . "';
            }
        ";
        }
        $result = $_POST['SMS4B_SEND_RESULT'];
        if ($result['RESULT'] === true) {
            $list->arActionSuccess[] = $result['DESCRIPTION'];
        } else {
            if (!empty($result['DESCRIPTION'])) {
                $list->arGroupErrors[] = [$result['DESCRIPTION']];
            }
        }
    }

    /**
     * ���������� select-� ��� �������� �� �������
     *
     * @param array $arDefaultTemplates - ������ �������� ��-���������
     * @param array $arCustomTemplates  - ������ ���������������� ��������
     *
     * @return string - html select-�
     */
    private static function GetSelectHtmlOnAdminList(
        array $arDefaultTemplates = [],
        array $arCustomTemplates = []
    ): string
    {
        $html = '<select style="display:none" id="sms4b_send_sms_list" name="sms4b_send_sms_list">';
        $html .= "<option value=''>" . Loc::getMessage('SMS4B_MAIN_ORDER_LIST_MENU_TEMPLATES') . '</option>';
        if (!empty($arDefaultTemplates)) {
            $html .= "<option value=''>" . Loc::getMessage('SMS4B_MAIN_ORDER_LIST_MENU_TEMPLATES_DEFAULT') . '</option>';
            foreach ($arDefaultTemplates as $template) {
                $html .= "<option value='" . $template[0]['ID'] . "'>" . $template[0]['NAME'] . '</option>';
            }
        }

        if (!empty($arCustomTemplates)) {
            $html .= "<option value=''>" . Loc::getMessage('SMS4B_MAIN_ORDER_LIST_MENU_TEMPLATES_CUSTOM') . '</option>';
            foreach ($arCustomTemplates as $template) {
                $html .= "<option value='" . $template['ID'] . "'>" . $template['NAME'] . '</option>';
            }
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * ���������� OnBeforeProlog - ���������� ��������� �� ������ �������
     * ����� ������ ���� ����������� ������ ��� � ���� ���������� ����������
     *
     * @throws LoaderException - ���������� ����������� ������
     * @throws Sms4bException
     * @throws SystemException
     */
    public static function OnBeforePrologHandler()
    {
        $request = Application::getInstance()->getContext()->getRequest();
        if ($request->getRequestMethod() === 'POST'
            && is_array($request->getPost('ID'))
            && Bitrix\Main\Loader::includeModule('rarus.sms4b')
            && check_bitrix_sessid()
            && ((Bitrix\Main\Loader::includeModule('sale') && self::CheckSaleOrderPage()) || self::CheckUserAdminPage())
        ) {
            if (self::CheckUserAdminPage()) {
                $sms4bSendSmsList = $request->getPost('action');
                if ($sms4bSendSmsList['action_button_tbl_user'] === 'sms4b_send_sms') {
                    if ((int)$sms4bSendSmsList['sms4b_send_sms_list_admin'] > 0) {
                        try {
                            $sms4b = new Csms4b();
                            $text = $sms4b->templateService->getTemplateTextById($sms4bSendSmsList['sms4b_send_sms_list_admin']);
                            /**
                             * @var array $arUserId
                             */
                            $arUserId = $request->getPost('ID');
                            $arData = $sms4b->GetUserData($arUserId);

                            if (!empty($text) && !empty($arData)) {
                                $arSend = [];

                                foreach ($arData as $item) {
                                    $cleanPhone = $sms4b->normalizePhoneNumber($item['PHONE_TO']);
                                    if (!empty($cleanPhone)) {
                                        $arSend[$cleanPhone] = $sms4b->templateService->fillInTemplateWithMacros($text,
                                            $item);
                                    }
                                }
                                $sms4b->sender->userSendSms($arSend, $arUserId, '', 'SMS4B_USER_CUSTOM_EVENT');
                                $_POST['SMS4B_SEND_RESULT'] = self::GetResultDescription($arSend);

                            }
                        } catch (Sms4bException | Sms4bValidationError $e) {
                            $_POST['SMS4B_SEND_RESULT'] = ['RESULT' => false, 'DESCRIPTION' => $e->getMessage()];
                        }

                    } else {
                        $_POST['SMS4B_SEND_RESULT'] = [
                            'RESULT'      => false,
                            'DESCRIPTION' => Loc::getMessage('SMS4B_MAIN_ORDER_LIST_ERROR_EMPTY_TEMPLATE')
                        ];
                    }
                }
            } elseif (Bitrix\Main\Loader::includeModule('sale') && self::CheckSaleOrderPage()
                && $request->getPost('action') === 'sms4b_send_sms') {
                $sms4bSendSmsList = $request->getPost('sms4b_send_sms_list');
                if (!empty($sms4bSendSmsList)) {
                    try {
                        $sms4b = new Csms4b();
                        $text = $sms4b->templateService->getTemplateTextById($request->getPost('sms4b_send_sms_list'));
                        /**
                         * @var array $arOrderId
                         */
                        $arOrderId = $request->getPost('ID');
                        $arData = $sms4b->orderService->getOrderData($arOrderId);

                        if (!empty($text) && !empty($arData)) {
                            $arSend = [];

                            foreach ($arData as $item) {
                                $cleanPhone = $sms4b->normalizePhoneNumber($item['PHONE_TO']);
                                if (!empty($cleanPhone)) {
                                    $arSend[$cleanPhone] = $sms4b->templateService->fillInTemplateWithMacros($text,
                                        $item);
                                }
                            }
                            $sms4b->sender->saleSendSms($arSend, $arOrderId, 'SMS4B_USER_CUSTOM_EVENT',
                                $sms4b->GetOrderLid(array_shift($arOrderId)));
                        }
                    } catch (Sms4bException | Sms4bValidationError $e) {
                        $_POST['SMS4B_SEND_RESULT'] = ['RESULT' => false, 'DESCRIPTION' => $e->getMessage()];
                    }
                } else {
                    $_POST['SMS4B_SEND_RESULT'] = [
                        'RESULT'      => false,
                        'DESCRIPTION' => Loc::getMessage('SMS4B_MAIN_ORDER_LIST_ERROR_EMPTY_TEMPLATE')
                    ];
                }
            }
        }
    }

    /**
     * ��������� ����������� �������� ��� CAdminMessage (��� �������)
     *
     * @param array $arSend - ������ � ����������� ����������� �� ��������
     *
     * @return array - ������ ���� array('RESULT' => ..., 'DESCRIPTION' => ...);
     */
    public static function GetResultDescription(array $arSend): array
    {
        if(count($arSend) > 0) {
            $result['RESULT'] = true;
            $result['DESCRIPTION'] = Loc::getMessage('SMS4B_MAIN_ORDER_LIST_SEND_SMS_FROM_NUM') .
                count($arSend) . Loc::getMessage('SMS4B_MAIN_ORDER_LIST_SMS') ;
            $result['DESCRIPTION'] .= '<br>' . Loc::getMessage('SMS4B_MAIN_ORDER_LIST_REPORT_LINK');
        } else {
            $result['RESULT'] = false;
            $result['DESCRIPTION'] = Loc::getMessage('SMS4B_MAIN_ORDER_LIST_ALL_FAIL');
        }

        return $result;
    }

    /**
     * �������� - ����������� �� ������ �� �������� �������
     *
     * @return bool - ��������� ��������
     */
    public static function CheckSaleOrderPage(): bool
    {
        if ($GLOBALS['APPLICATION']->GetCurPage() === '/bitrix/admin/sale_order.php') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * �������� - ����������� �� ������ �� �������� �������������
     *
     * @return bool - ��������� ��������
     */
    public static function CheckUserAdminPage(): bool
    {
        if ($GLOBALS['APPLICATION']->GetCurPage() === '/bitrix/admin/user_admin.php') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * ������� ��������� ����� ��� ���������
     *
     * ������� ���������� ��������� �����, � ����������� �� ������������ � ��� �����
     * ��������: 5 �������, 1 �����, 3 ������
     *
     * @param int   $value  - �����, � �������� ���������� ��������� ���������
     * @param array $status - ������ ��������� ���������
     *
     * @return mixed
     */
    public static function GetDeclNum(int $value, array $status): mixed
    {
        $array = [2, 0, 1, 1, 1, 2];
        return $status[($value % 100 > 4 && $value % 100 < 20) ? 2 : $array[($value % 10 < 5) ? $value % 10 : 5]];
    }

    /**
     * ���������� ������ �� ������� (����, ������ ������, �������� ��������)
     *
     * #ORDER_ID# - ��� ������
     * #ORDER_DATE# - ���� ������
     * #ORDER_USER# - ��������
     * #PRICE# - ����� ������
     * #PHONE_TO# - ������� ���������
     * #ORDER_LIST# - ������ ������
     * #SALE_PHONE# - ������� ������ ������
     *
     * @param array $arIds - ������ � ID �������
     *
     * @return array - ������ �� �������
     * @throws Sms4bException
     */
    public function GetOrderData(array $arIds): array
    {
        return $this->orderService->getOrderData($arIds);
    }

    /**
     * ���������� ������ �� �������������
     *
     * @param mixed $arIds - ID ��� ������ � ID �������������
     *
     * @return array - ������ �� ������������
     * @throws Sms4bException
     */
    public function GetUserData(mixed $arIds): array
    {
        $arRes = [];
        $userPhoneCode = $this->GetCurrentOption('user_property_phone');

        if (!empty($arIds)) {
            $rsUser = CUser::GetList($by = 'ID', $order = 'desc', ['ID' => implode('|', (array)$arIds)],
                ['SELECT' => ['UF_*']]);
            while ($res = $rsUser->Fetch()) {
                $res['PHONE_TO'] = $res[$userPhoneCode];
                $arRes[] = $res;
            }
        }
        return $arRes;
    }

    /**
     * ���������� ��� ���� ������������
     *
     * @return array - ���� ������������
     * @throws Sms4bException
     */
    public function GetPersonTypes(): array
    {
        return $this->orderService->getPersonTypes();
    }

    /**
     * ���������� �������� ������
     *
     * @return array - �������� ������
     * @throws Sms4bException
     */
    public function GetSaleOrderProps(): array
    {
        return $this->orderService->getSaleOrderProps();
    }

    /**
     * �������� ������ � �������� �������
     *
     * @return array ������ � �������� ������� ������� [����] => [(+����) �������]
     *
     * @author azarev
     */
    public static function getTimeZone(): array
    {
        return [
            3  => '(+3) ' . Loc::getMessage('SMS4B_MAIN_TIMEZONE_MOSCOW'),
            2  => '(+2) ' . Loc::getMessage('SMS4B_MAIN_TIMEZONE_KALININGRAD'),
            4  => '(+4) ' . Loc::getMessage('SMS4B_MAIN_TIMEZONE_SAMARA'),
            5  => '(+5) ' . Loc::getMessage('SMS4B_MAIN_TIMEZONE_EKATA'),
            6  => '(+6) ' . Loc::getMessage('SMS4B_MAIN_TIMEZONE_OMSK'),
            7  => '(+7) ' . Loc::getMessage('SMS4B_MAIN_TIMEZONE_KRASNOYARSK'),
            8  => '(+8) ' . Loc::getMessage('SMS4B_MAIN_TIMEZONE_IRKYTSK'),
            9  => '(+9) ' . Loc::getMessage('SMS4B_MAIN_TIMEZONE_YAKUTSK'),
            10 => '(+10) ' . Loc::getMessage('SMS4B_MAIN_TIMEZONE_VLADIVOSTOK'),
            11 => '(+11) ' . Loc::getMessage('SMS4B_MAIN_TIMEZONE_CHOKURDAH'),
            12 => '(+12) ' . Loc::getMessage('SMS4B_MAIN_TIMEZONE_KAMCHATKA')
        ];
    }

    /**
     * ������������
     *
     * ����� ������ ���� ����������� ������ ��� � ���� ���������� ����������
     *
     * @param array $eventData - ������ �������
     *
     * @return void - false � ������ ���������� ����������� ������ SMS4B
     * @throws LoaderException - ���������� ����������� ������
     * @throws Sms4bException
     */
    public static function AutoAnswering(array $eventData)
    {
        $sms4b = new Csms4b();
        $autoAnsweringFlag = $sms4b->GetCurrentOption('event_autoanswer', SITE_ID);
        $missedCallFlag = $sms4b->GetCurrentOption('event_missed_call', SITE_ID);

        if (($autoAnsweringFlag === 'Y' || $missedCallFlag === 'Y')
            && ($eventData['CALL_TYPE'] === CVoxImplantMain::CALL_INCOMING || $eventData['CALL_TYPE']
                === CVoxImplantMain::CALL_INCOMING_REDIRECT)
            && Bitrix\Main\Loader::includeModule('voximplant')
        ) {
            $sms4b->sms4bLog(Loc::getMessage('SMS4B_MAIN_START_AUTOANSWER'));
            //����������� ������� ��� � ������
            $eventData = self::dateToStringInArray(CVoxImplantHistory::PrepereData($eventData));
            //��������� ������, ������������, �������� �������
            $userData = $sms4b->GetUserData([$eventData['PORTAL_USER_ID']]);
            $eventData = array_merge($eventData, $userData[0]);

            //��������� ������� $arSearch � $arReplace ��� ������ ��������
            $eventData['CALL_FAILED_REASON'] = Loc::getMessage('VI_STATUS_' . $eventData['CALL_FAILED_CODE']);

            try {
                if ($autoAnsweringFlag === 'Y') {
                    $callerPhone = $eventData['PHONE_NUMBER'];
                    $template = $sms4b->templateService->getUserTemplate('AUTOANSWER', SITE_ID);
                    $arPhonesMessages[$callerPhone] = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $eventData);
                }

                if ($missedCallFlag === 'Y') {
                    $portalUserPhone = $sms4b->SearchUserPhone($eventData['PORTAL_USER_ID']);
                    $template = $sms4b->templateService->getUserTemplate('MISSED_CALL', SITE_ID);
                    $arPhonesMessages[$portalUserPhone] = $sms4b->templateService->fillInTemplateWithMacros($template['MESSAGE'], $eventData);
                }

                if (!empty($arPhonesMessages)) {
                    $sms4b->sender->telephonySendSms($arPhonesMessages);
                }
            } catch (Sms4bException | Sms4bValidationError $e) {
                $sms4b->sms4bLog($e->getMessage());
            }
            $sms4b->sms4bLog(Loc::getMessage('SMS4B_MAIN_STOP_AUTOANSWER') . PHP_EOL);
        }
    }

    /**
     * ���������� ������ �������� �������� ������������� ����
     *
     * @param string $type - ��� ������� ('O' - �����, 'D' - ��������)
     * @param string $lid  - ���
     *
     * @return array - ������ ��������
     * @throws Sms4bException
     */
    public function GetSaleStatus(string $type = '', string $lid = 'ru'): array
    {
        return $this->orderService->getSaleStatus($type, $lid);
    }

    /**
     * ���������� ������� OnStatusAdd
     * ������� ��� � ������ ��������� ������� SMS4B_SALE_STATUS_CHANGED_X ����� ���������� ������� X
     * ����� ������ ���� ����������� ������ ��� � ���� ���������� ����������
     *
     * @param $statusCode     - ������������� ������������ ������� ������
     * @param array $arFields - ������ ����� ������� ������
     */
    public static function OnStatusAddHandler($statusCode, array $arFields): void
    {
        $langs = CLanguage::GetList($by = '', $order = '');
        while ($lang = $langs->Fetch()) {
            $lid = $lang['LID'];
            IncludeModuleLangFile(__FILE__, $lid);

            CEventType::Add([
                'LID'         => $lid,
                'EVENT_NAME'  => 'SMS4B_SALE_STATUS_CHANGED_' . $arFields['ID'],
                'NAME'        => ($arFields['TYPE'] === 'O' ? Loc::getMessage('SMS4B_MAIN_CHANGING_ORDER_STATUS_TO')
                        : Loc::getMessage('SMS4B_MAIN_CHANGING_SHIPMENT_STATUS_TO')) . $arFields['ID'],
                'DESCRIPTION' => $arFields['TYPE'] === 'O' ? Loc::getMessage('SMS4B_MAIN_ORDER_STATUS_DESC')
                    : Loc::getMessage('SMS4B_MAIN_SHIPMENT_STATUS_DESC')
            ]);

            CEventType::Add([
                'LID'         => $lid,
                'EVENT_NAME'  => 'SMS4B_ADMIN_SALE_STATUS_CHANGED_' . $arFields['ID'],
                'NAME'        => ($arFields['TYPE'] === 'O' ? Loc::getMessage('SMS4B_MAIN_CHANGING_ORDER_STATUS_TO')
                        : Loc::getMessage('SMS4B_MAIN_CHANGING_SHIPMENT_STATUS_TO')) . $arFields['ID'],
                'DESCRIPTION' => $arFields['TYPE'] === 'O' ? Loc::getMessage('SMS4B_MAIN_ORDER_STATUS_DESC')
                    : Loc::getMessage('SMS4B_MAIN_SHIPMENT_STATUS_DESC')
            ]);

            $arSites = [];
            $sites = CSite::GetList($by = '', $order = '', ['LANGUAGE_ID' => $lid]);
            while ($site = $sites->Fetch()) {
                $arSites[] = $site['LID'];
            }

            if (count($arSites) > 0) {
                $obCEventMessage = new CEventMessage;

                $dbEventMessage = CEventMessage::GetList(
                    $by = '',
                    $order = '',
                    [
                        'EVENT_NAME' => 'SMS4B_SALE_STATUS_CHANGED_' . $arFields['ID'],
                        'SITE_ID'    => $arSites
                    ]
                );

                $siteWithTemplate = [];
                while ($arEventMessage = $dbEventMessage->Fetch()) {
                    $siteWithTemplate[] = $arEventMessage['SITE_ID'];
                }
                $siteWithoutTemplate = array_diff($arSites, $siteWithTemplate);

                $obCEventMessage->Add([
                    'ACTIVE'     => 'Y',
                    'EVENT_NAME' => 'SMS4B_SALE_STATUS_CHANGED_' . $arFields['ID'],
                    'LID'        => $siteWithoutTemplate,
                    'EMAIL_FROM' => '#SALE_PHONE#',
                    'EMAIL_TO'   => '#PHONE_TO#',
                    'SUBJECT'    => ($arFields['TYPE'] === 'O' ? Loc::getMessage('SMS4B_MAIN_ORDER_STATUS_SUBJ')
                            : Loc::getMessage('SMS4B_MAIN_SHIPMENT_STATUS_SUBJ')) . $arFields['ID'],
                    'MESSAGE'    => $arFields['TYPE'] === 'O' ? Loc::getMessage('SMS4B_MAIN_ORDER_STATUS_MESS')
                        : Loc::getMessage('SMS4B_MAIN_SHIPMENT_STATUS_MESS'),
                    'BODY_TYPE'  => 'text'
                ]);

                $dbEventMessage = CEventMessage::GetList(
                    $by = '',
                    $order = '',
                    [
                        'EVENT_NAME' => 'SMS4B_ADMIN_SALE_STATUS_CHANGED_' . $arFields['ID'],
                        'SITE_ID'    => $arSites
                    ]
                );

                $siteWithTemplate = [];
                while ($arEventMessage = $dbEventMessage->Fetch()) {
                    $siteWithTemplate[] = $arEventMessage['SITE_ID'];
                }
                $siteWithoutTemplate = array_diff($arSites, $siteWithTemplate);

                $obCEventMessage->Add([
                    'ACTIVE'     => 'Y',
                    'EVENT_NAME' => 'SMS4B_ADMIN_SALE_STATUS_CHANGED_' . $arFields['ID'],
                    'LID'        => $siteWithoutTemplate,
                    'EMAIL_FROM' => '#SALE_PHONE#',
                    'EMAIL_TO'   => '#PHONE_TO#',
                    'SUBJECT'    => ($arFields['TYPE'] === 'O' ? Loc::getMessage('SMS4B_MAIN_ORDER_STATUS_SUBJ')
                            : Loc::getMessage('SMS4B_MAIN_SHIPMENT_STATUS_SUBJ')) . $arFields['ID'],
                    'MESSAGE'    => $arFields['TYPE'] === 'O' ? Loc::getMessage('SMS4B_MAIN_ORDER_STATUS_MESS')
                        : Loc::getMessage('SMS4B_MAIN_SHIPMENT_STATUS_MESS'),
                    'BODY_TYPE'  => 'text'
                ]);
            }
        }

    }

    /**
     * ���������� ������� ������ SMS ��������� (Event handler SMS messaging service)
     * The method must be static because it is called statically in the kernel
     *
     * @return \Rarus\Sms4b\bxMessageServices\Sms4b[]
     */
    static function registerSms4bService()
    {
        return [
            new Rarus\Sms4b\bxMessageServices\Sms4b()
        ];
    }

}

global $SMS4B;
if (!is_object($SMS4B)) {
    $SMS4B = new Csms4b();
}
