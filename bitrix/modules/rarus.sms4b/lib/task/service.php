<?php
declare(strict_types=1);

namespace Rarus\Sms4b\Task;

use Bitrix\Main\Localization\Loc;
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
     * �������� ����������� �������� ��������� ��� ������������ �����
     *
     * @param int    $priority - ������ ���������� ������
     * @param string $task     - �������� ��� �������
     * @param string $site     - ID �����
     *
     * @return string - ������ �� �� ('Y' ��� '')
     * @throws Sms4bException
     */
    public function checkTaskPriority($priority, $task, $site): string
    {
        $result = '';
        if ((int)$priority === 0 || empty($priority)) {
            $result = $this->config->getSettingByName($task . '_low_task', $site);
        } elseif ((int)$priority === 1) {
            $result = $this->config->getSettingByName($task . '_middle_task', $site);
        } elseif ((int)$priority === 2) {
            $result = $this->config->getSettingByName($task . '_hight_task', $site);
        }
        return $result;
    }

    /**
     * ���������� ��� ������� ������
     *
     * @return array - ������ ������ ������� �����
     * @throws Sms4bException
     */
    public function getSonetGroups(): array
    {
        try {
            $arWorkGroups = [];
            if (\Bitrix\Main\Loader::includeModule('socialnetwork')) {
                $result = \Bitrix\Socialnetwork\WorkgroupTable::getList([
                    'select' => ['ID', 'NAME']
                ]);
                $arWorkGroups[] = ['ID' => 'NO_GROUP', 'NAME' => Loc::getMessage('SMS4B_MAIN_TASKS_NO_GROUP')];
                while ($res = $result->fetch()) {
                    $arWorkGroups[] = $res;
                }
            }
            return $arWorkGroups;
        } catch (\Exception $e) {
            throw new Sms4bException(Loc::getMessage('SMS4B_TASK_GROUP_ERROR'), $e->getCode(), $e);
        }
    }

    /**
     * ��������� �� �������� �������
     *
     * @param string - ID ������
     *
     * @return bool - ��������� ��������
     * @throws Sms4bException
     */
    public function checkGroupPerm($groupId): bool
    {
        $arSonetGroups = $this->getPermGroups();

        if (\in_array($groupId, $arSonetGroups, false) || (empty($groupId) && \in_array('NO_GROUP', $arSonetGroups,
                    false))
        ) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * ���������� �� ������� ������, ��� ������� ��������� ��������
     *
     * @return array - ������ � ID ������� �����, ��� ������� ��������� ��������
     * @throws Sms4bException
     */
    private function getPermGroups(): array
    {
        return (array)unserialize($this->config->getSettingByName('serialize_work_groups', SITE_ID));
    }
}