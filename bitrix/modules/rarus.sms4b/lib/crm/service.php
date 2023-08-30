<?php

namespace Rarus\Sms4b\Crm;

use Bitrix\Main\Localization\Loc;
use CAllUserTypeEntity;
use CCrmStatus;
use \Rarus\Sms4b\Config;
use Rarus\Sms4b\Exceptions\Sms4bException;

class Service
{
    private $config;

    public function __construct()
    {
        $this->config = new Config\Service();
    }

    /**
     * ���������� ������ � ���������� ����\��������
     *
     * @param int $id - ID ����\��������
     *
     * @return array - ������ � ����������
     */
    public function getPhonesLeadOrContact($id): array
    {
        $data = [];
        $dbResult = \CCrmFieldMulti::GetList(
            ['ID' => 'asc'],
            [
                'TYPE_ID'    => 'PHONE',
                'ELEMENT_ID' => (int)$id
            ]
        );
        $arTempFm = [];
        while ($fields = $dbResult->Fetch()) {
            $arTempFm[$fields['COMPLEX_ID']][] = $fields['VALUE'];
        }

        foreach ($arTempFm as $key => $val) {
            foreach ($val as $key2 => $val2) {
                $data[$key . '_' . ($key2 + 1)] = $val2;
            }
        }

        return $data;
    }

    /**
     * ���������� ������ � ������� ����
     *
     * @param int $id - ID ����
     *
     * @return array - ������ � ������� ����
     * @throws Sms4bException
     */
    public function getLeadData($id): array
    {
        try {
            $result = \Bitrix\Crm\LeadTable::getList([
                'select' => ['*'],
                'filter' => ['=ID' => $id]
            ]);
            $data = $result->fetch();

            $result = \Bitrix\Crm\ProductRowTable::getList([
                'select' => ['IBLOCK_ELEMENT'],
                'filter' => ['=OWNER_ID' => $data['ID']]
            ]);

            while ($arProd = $result->fetch()) {
                $data['PRODUCTS'][] = $arProd['CRM_PRODUCT_ROW_IBLOCK_ELEMENT_NAME'];
            }
            $data['PRODUCTS'] = implode(', ', (array)$data['PRODUCTS']);
            $data['STATUS_CODE'] = $data['STATUS_ID'];
            $data['STATUS_ID'] = $this->getNameStatusId($data['STATUS_ID']);
            $data['OLD_STAT'] = $this->getNameStatusId($data['OLD_STAT']);

            if (!empty($data['SOURCE_ID'])) {
                $data['SOURCE_ID'] = $this->getSourceName($id);
            }

            $arPhones = $this->getPhonesLeadOrContact($data['ID']);
            if (!empty($arPhones) && is_array($arPhones)) {
                $data['RESPONSIBLE'] = reset($arPhones);
                $data = array_merge($data, $arPhones);
            }

            return $data;
        } catch (\Exception $e) {
            throw new Sms4bException(Loc::getMessage('SMS4B_CRM_LEAD_ERROR'), $e->getCode(), $e);
        }
    }

    /**
     * ���������� ������ � ������� ��������
     *
     * @param int $id - ID ��������
     *
     * @return array - ������ � ������� ��������
     * @throws Sms4bException
     */
    public function getContactData($id): array
    {
        try {
            $result = \Bitrix\Crm\ContactTable::getList([
                'filter' => ['=ID' => $id]
            ]);
            $data = $result->Fetch();

            if (!empty($data['SOURCE_ID'])) {
                $data['SOURCE_ID'] = $this->getSourceName($id);
            }

            $arPhones = $this->getPhonesLeadOrContact($data['ID']);
            if (!empty($arPhones) && is_array($arPhones)) {
                $data['RESPONSIBLE'] = reset($arPhones);
                $data = array_merge($data, $arPhones);
            }

            return $data;
        } catch (\Exception $e) {
            throw new Sms4bException(Loc::getMessage('SMS4B_CRM_CONTACT_ERROR'), $e->getCode(), $e);
        }
    }

    /**
     * ���������� ������ � ������� ������
     *
     * @param int $id - ID ������
     *
     * @return array - ������ � ������� ������
     * @throws Sms4bException
     */
    public function getDealData($id): array
    {
        try {
            $result = \Bitrix\Crm\DealTable::getList([
                'filter' => ['=ID' => $id]
            ]);
            $data = $result->Fetch();

            $arDescDealStage = CCrmStatus::GetStatusListEx('DEAL_STAGE');
            $data['STAGE_CODE'] = $data['STAGE_ID'];
            $data['STAGE_ID'] = $arDescDealStage[$data['STAGE_ID']];

            if (!empty($data['OLD_STAGE'])) {
                $data['OLD_STAGE'] = $arDescDealStage[$data['OLD_STAGE']];
            }

            if (!empty($data['CONTACT_ID'])) {
                $arPhones = $this->getPhonesLeadOrContact($data['CONTACT_ID']);
                if (!empty($arPhones) && is_array($arPhones)) {
                    $data['RESPONSIBLE'] = reset($arPhones);
                    $data = array_merge($data, $arPhones);
                }
            }

            return $data;
        } catch (\Exception $e) {
            throw new Sms4bException(Loc::getMessage('SMS4B_CRM_DEAL_ERROR'), $e->getCode(), $e);
        }
    }

    /**
     * ���������� ������ � ���������
     *
     * @param string $entityType - ��� �������� (���\�������\��������)
     * @param bool   $needUf     - ���� ���������� ���������������� ����� � ������ � ���������
     *
     * @return string - ������ � ���������
     */
    public function getMacros($entityType, $needUf = false): string
    {
        $arMacros = [];
        $dbResult = \CCrmFieldMulti::GetList(
            ['ID' => 'asc'],
            [
                'TYPE_ID'   => 'PHONE',
                'ENTITY_ID' => $entityType
            ]
        );
        while ($fields = $dbResult->Fetch()) {
            $arMacros[] = $fields['COMPLEX_ID'];
        }
        $macrString = "\n";
        foreach (array_unique($arMacros) as $val) {
            $macrString .= "#$val" . '_1# - ' . Loc::getMessage($val) . "_1\n";
            $macrString .= "#$val" . '_2# - ' . Loc::getMessage($val) . "_2\n";
        }

        if (!empty($needUf)) {
            global $USER_FIELD_MANAGER;
            $fields = $USER_FIELD_MANAGER->GetUserFields('CRM_' . $entityType);

            foreach ($fields as $val) {
                $desc = CAllUserTypeEntity::GetByID($val['ID']);
                $macrString .= '#' . $val['FIELD_NAME'] . '# - ' . $desc['EDIT_FORM_LABEL']['ru'] . "\n";
            }
        }

        return $macrString;
    }

    /**
     * ���������� �������� ������� �� ��� ID
     *
     * @param int $statusId - ID �������
     *
     * @return string|null - �������� �������
     */
    private function getNameStatusId($statusId): ?string
    {
        $arDescStat = CCrmStatus::GetStatusListEx('STATUS');
        return $arDescStat[$statusId];
    }

    /**
     * ���������� �������� ��������� ��������\���� �� ��� ID
     *
     * @param int $id - ID ��������� ��������\����
     *
     * @return mixed - �������� ��������� ��������\���� ��� false
     */
    private function getSourceName($id)
    {
        $arDescSource = CCrmStatus::GetStatusListEx('SOURCE');
        if (!empty($id)) {
            return $arDescSource[$id];
        } else {
            return false;
        }
    }
}