<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $this
 */

use Bitrix\Main\Localization\Loc;

if (!$USER->CanDoOperation('biconnector_dashboard_manage'))
{
	ShowError(Loc::getMessage('ACCESS_DENIED'));
	return;
}

if (!\Bitrix\Main\Loader::includeModule('biconnector'))
{
	ShowError(Loc::getMessage('CC_BBDE_ERROR_INCLUDE_MODULE'));
	return;
}

$arResult['ERRORS'] = [];

if (!\Bitrix\BIConnector\LimitManager::getInstance()->checkLimit())
{
		$arResult['ERRORS'][] = Loc::getMessage('CC_BBDE_ERROR_LIMIT_EXCEEDED');
}

if (
	$_SERVER['REQUEST_METHOD'] === 'POST'
	&& check_bitrix_sessid()
	&& $_POST['save'] === 'Y'
)
{
	$name = trim($_POST['NAME'], " \t\n\r");
	if (!$name)
	{
		$arResult['ERRORS'][] = Loc::getMessage('CC_BBDE_ERROR_EMPTY_NAME');
	}

	$url = trim($_POST['URL'], " \t\n\r");
	if (!$url)
	{
		$arResult['ERRORS'][] = Loc::getMessage('CC_BBDE_ERROR_EMPTY_URL');
	}
	else
	{
		$manager = \Bitrix\BIConnector\Manager::getInstance();
		if (!$manager->validateDashboardUrl($url))
		{
			$arResult['ERRORS'][] = Loc::getMessage('CC_BBDE_ERROR_INVALID_URL');
		}
	}

	if (empty($arResult['ERRORS']))
	{
		$data = [
			'TIMESTAMP_X' => new \Bitrix\Main\Type\DateTime(),
			'NAME' => $name,
			'URL' => $url,
		];

		$ID = intval($_POST['ID']);
		if ($ID > 0)
		{
			$updateResult = \Bitrix\BIConnector\DashboardTable::update($ID, $data);
			$updateResult->isSuccess();
		}
		else
		{
			$data['DATE_CREATE'] = new \Bitrix\Main\Type\DateTime();
			$data['CREATED_BY'] = $USER->GetId();
			$addResult = \Bitrix\BIConnector\DashboardTable::add($data);
			if ($addResult->isSuccess())
			{
				$ID = $addResult->getId();
			}
		}

		if ($ID > 0)
		{
			$usersForm = [];
			foreach (explode(',', $_POST['USERS']) as $userId)
			{
				$userId = intval(trim($userId, " \t\n\r"));
				if ($userId > 0)
				{
					$usersForm[$userId] = [
						'TIMESTAMP_X' => new \Bitrix\Main\Type\DateTime(),
						'CREATED_BY' => $USER->GetId(),
						'DASHBOARD_ID' => $ID,
						'USER_ID' => $userId,
					];
				}
			}

			$usersDb = [];
			if (!isset($addResult))
			{
				$userList = \Bitrix\BIConnector\DashboardUserTable::getList([
					'select' => ['ID', 'USER_ID'],
					'filter' => [
						'=DASHBOARD_ID' => $ID,
					]
				]);
				while ($user = $userList->fetch())
				{
					$usersDb[$user['ID']] = $user['USER_ID'];
				}
			}

			foreach ($usersForm as $userId => $user)
			{
				$found = false;
				foreach ($usersDb as $dbId => $dbUserId)
				{
					if ($dbUserId == $user['USER_ID'])
					{
						unset($usersDb[$dbId]);
						$found = true;
						break;
					}
				}

				if (!$found)
				{
					$addResult = \Bitrix\BIConnector\DashboardUserTable::add($user);
					$addResult->isSuccess();
				}
			}

			foreach ($usersDb as $dbId => $dbUserId)
			{
				$deleteResult = \Bitrix\BIConnector\DashboardUserTable::delete($dbId);
				$deleteResult->isSuccess();
			}

			$redirectUrl = str_replace('#ID#', $ID, $arParams['DASHBOARD_EDIT_URL']);
			if (($_REQUEST['IFRAME'] == 'Y') && ($_REQUEST['IFRAME_TYPE'] == 'SIDE_SLIDER'))
			{
				$redirectUrl = CHTTP::urlAddParams($redirectUrl, [
					'IFRAME' => 'Y',
					'IFRAME_TYPE' => 'SIDE_SLIDER',
					'sidePanelAction' => 'close',
				]);
			}
			LocalRedirect($redirectUrl);
		}
	}
}

$arResult['GRID_ID'] = 'biconnector_dashboard_list';
$arResult['FORM_ID'] = 'biconnector_dashboard_edit';

$arResult['FORM_DATA'] = \Bitrix\BIConnector\DashboardTable::getList([
	'select' => [
		'ID',
		'NAME',
		'URL',
	],
	'filter' => [
		'=ID' => $arParams['ID'],
	],
])->fetch();

if ($arResult['FORM_DATA'])
{
	$APPLICATION->SetTitle(Loc::getMessage('CC_BBDE_TITLE_EDIT'));
}
else
{
	$APPLICATION->SetTitle(Loc::getMessage('CC_BBDE_TITLE_NEW'));
	$arResult['FORM_DATA'] = [
		'ID' => 0,
		'NAME' => '',
		'URL' => '',
	];
}

$arResult['FORM_DATA']['USERS'] = [];
if ($arResult['FORM_DATA']['ID'] > 0)
{
	$userList = \Bitrix\BIConnector\DashboardUserTable::getList([
		'select' => ['ID', 'USER_ID'],
		'filter' => [
			'=DASHBOARD_ID' => $arResult['FORM_DATA']['ID'],
		]
	]);
	while ($user = $userList->fetch())
	{
		$arResult['FORM_DATA']['USERS'][] = $user['USER_ID'];
	}
}

if ($arResult['ERRORS'])
{
	$arResult['FORM_DATA']['NAME'] = $_POST['NAME'];
	$arResult['FORM_DATA']['URL'] = $_POST['URL'];
	$arResult['FORM_DATA']['USERS'] = [];
	foreach (explode(',', $_POST['USERS']) as $userId)
	{
		if ($userId > 0)
		{
			$arResult['FORM_DATA']['USERS'][] = $userId;
		}
	}
}

$this->IncludeComponentTemplate();