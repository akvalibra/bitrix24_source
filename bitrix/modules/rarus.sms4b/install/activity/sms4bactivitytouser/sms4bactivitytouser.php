<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use \Bitrix\Main\Localization\Loc;
Loc::loadLanguageFile(__FILE__);

/**
 * Class CBPSMS4BActivityToUser
 */
class CBPSMS4BActivityToUser extends CBPActivity
{
    private $senderService;
    /**
     * �����������
     *
     * @param $name string - ID ���������� ������-��������
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = [
            'smsTo' => 'sms_user_to',
            'smsText' => 'sms_text',
            'user_property' => 'user_property_phone'
        ];
        $this->senderService = new \Rarus\Sms4b\Sendings\Sender();
    }

    /**
     * ���������� ������-��������
     *
     * @return string = "Closed"
     * @throws \Bitrix\Main\ArgumentException - ���������� ���������� ����������
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \Rarus\Sms4b\Exceptions\Sms4bException
     */
    public function Execute()
    {
        if (CModule::IncludeModule('rarus.sms4b')) {
            $sms = new Csms4b();
            $sms->sms4bLog(Loc::getMessage('SMS4B_MAIN_BP_START_SEND_TO_USER'));
            $sms->sms4bLog(Loc::getMessage('SMS4B_MAIN_BP_GET_ID') . $this->smsTo);
            $sms->sms4bLog(Loc::getMessage('SMS4B_MAIN_BP_USER_PROP') . $this->user_property);

            //��������� ID �� �������� �������� ���� user_1
            $arNum = explode('_', $this->smsTo);
            if (!empty($arNum[1])) {
                $this->smsTo = $arNum[1];
            }

            $result = \Bitrix\Main\UserTable::getList([
                'filter' => ['ID' => (int)$this->smsTo],
                'select' => [$this->user_property]
            ]);
            $arRes = $result->fetch();
            $this->smsTo = $arRes[$this->user_property];

            $sms->sms4bLog(Loc::getMessage('SMS4B_MAIN_GET_PHONE') . $this->smsTo);
            try {
                $this->senderService->crmSendSms([$this->smsTo => $this->smsText], SITE_ID);
                $this->WriteToTrackingService(Loc::getMessage('SMS4B_MAIN_BP_TEXT') . $this->smsText . Loc::getMessage('SMS4B_MAIN_BP_SEND_TO_NUM') . $this->smsTo);
                $sms->sms4bLog(Loc::getMessage('SMS4B_MAIN_BP_TEXT') . $this->smsText . Loc::getMessage('SMS4B_MAIN_BP_SEND_TO_NUM') . $this->smsTo);
            } catch (\Exception $e) {
                $this->WriteToTrackingService(Loc::getMessage('SMS4B_MAIN_BP_TEXT') . $this->smsText . Loc::getMessage('SMS4B_MAIN_BP_NOT_SEND_TO_NUM') . $this->smsTo);
                $sms->sms4bLog(Loc::getMessage('SMS4B_MAIN_BP_TEXT') . $this->smsText . Loc::getMessage('SMS4B_MAIN_BP_NOT_SEND_TO_NUM') . $this->smsTo);
            }
            $sms->sms4bLog(Loc::getMessage('SMS4B_MAIN_BP_END_SEND_TO_USER') . "\n");

        }
        return CBPActivityExecutionStatus::Closed;
    }

    /**
     * �������� ����������
     *
     * @param $arTestProperties array - ������ ����������� ��������
     * @param $user - ������ ������������ CBPWorkflowTemplateUser
     *
     * @return array - ������ � �������� ��� ������
     */
    public static function ValidateProperties($arTestProperties = [], CBPWorkflowTemplateUser $user = null)
    {
        $arErrors = [];
        if (!array_key_exists('smsTo', $arTestProperties) || strlen($arTestProperties['smsTo']) <= 0) {
            $arErrors[] = [
                'code' => 'NotExist',
                'parameter' => 'smsTo',
                'message' => Loc::getMessage('SMS4B_MAIN_BPMA_EMPTY_PROP1')
            ];
        }
        if (!array_key_exists('smsText', $arTestProperties) || strlen($arTestProperties['smsText']) <= 0) {
            $arErrors[] = [
                'code' => 'NotExist',
                'parameter' => 'smsText',
                'message' => Loc::getMessage('SMS4B_MAIN_BPMA_EMPTY_PROP2')
            ];
        }

        if (!array_key_exists('user_property', $arTestProperties) || strlen($arTestProperties['user_property']) <= 0) {
            $arErrors[] = [
                'code' => 'NotExist',
                'parameter' => 'user_property',
                'message' => Loc::getMessage('SMS4B_MAIN_BPMA_EMPTY_PROP3')
            ];
        }

        return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
    }

    /**
     * �������� ������ � ������
     *
     * @param $documentType         array - ��� ���������
     * @param $activityName         string - �������� ��������
     * @param $arWorkflowTemplate   array - ��������� �������
     * @param $arWorkflowParameters array - ���� ���������
     * @param $arWorkflowVariables  array - ����������
     * @param $arCurrentValues      array|null - ������� ������ �� �������� �� �����
     * @param $formName             string - ��� �����
     *
     * @return string - �������� �������
     */
    public static function GetPropertiesDialog(
        array $documentType,
        string $activityName,
        array $arWorkflowTemplate,
        array $arWorkflowParameters,
        array $arWorkflowVariables,
        array $arCurrentValues = null,
        string $formName = ''
    ): string {
        $runtime = CBPRuntime::GetRuntime();
        if (!is_array($arCurrentValues)) {
            $arCurrentValues = ['sms_user_to' => '', 'sms_text' => ''];
            $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
            if (is_array($arCurrentActivity['Properties'])) {
                $arCurrentValues['sms_user_to'] = $arCurrentActivity['Properties']['smsTo'];
                $arCurrentValues['sms_text'] = $arCurrentActivity['Properties']['smsText'];
                $arCurrentValues['user_property_phone'] = $arCurrentActivity['Properties']['user_property'];
            }
        }

        //�������� �������� ������������, ��� ����� ��������� �������
        $rsUser = CUser::GetList(($by = 'ID'), ($order = 'desc'), ['ID' => 1], ['SELECT' => ['UF_*']]);
        $arUser = $rsUser->Fetch();
        foreach ($arUser as $index => $value) {
            $pattern = '/(PERSONAL|WORK|UF)/';
            if (preg_match($pattern, $index)) {
                $arCurrentValues['UserPhone'][] = $index;
            }
        }

        return $runtime->ExecuteResourceFile(
            __FILE__,
            'properties_dialog.php',
            [
                'arCurrentValues' => $arCurrentValues,
                'formName' => $formName
            ]
        );
    }

    /**
     * ��������� ������ �������
     *
     * @param $documentType         array - ��� ���������
     * @param $activityName         string - �������� ��������
     * @param $arWorkflowTemplate   array - ��������� �������
     * @param $arWorkflowParameters array - ���� ���������
     * @param $arWorkflowVariables  array - ����������
     * @param $arCurrentValues      array - ������� ������ �� �������� �� �����
     * @param $arErrors             array - ������ ������
     *
     * @return bool - ���������
     */
    public static function GetPropertiesDialogValues(
        array $documentType,
        string $activityName,
        array &$arWorkflowTemplate,
        array &$arWorkflowParameters,
        array &$arWorkflowVariables,
        array $arCurrentValues,
        array &$arErrors
    ): bool {
        $arMap = [
            'sms_user_to' => 'smsTo',
            'sms_text' => 'smsText',
            'user_property_phone' => 'user_property'
        ];
        $arProperties = [];
        foreach ($arMap as $key => $value) {
            $arProperties[$value] = $arCurrentValues[$key];
        }
        $arErrors = self::ValidateProperties($arProperties,
            new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
        if (count($arErrors) > 0) {
            return false;
        }
        $arProperties['assigned_user_email'] = $arProperties['ASSIGNED_BY_EMAIL'];
        $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
        $arCurrentActivity['Properties'] = $arProperties;
        return true;
    }
}
