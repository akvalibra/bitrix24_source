<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
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

if (!\Bitrix\Main\Loader::includeModule('biconnector'))
{
	ShowError(Loc::getMessage('CC_BBD_ERROR_INCLUDE_MODULE'));
	return;
}

$filter = [
	'=ID' => $arParams['DASHBOARD_ID'],
];

if (!$USER->CanDoOperation('biconnector_dashboard_view'))
{
	$filter['=PERMISSION.USER_ID'] = $USER->getId();
}

$arResult = \Bitrix\BIConnector\DashboardTable::getList([
	'filter' => $filter,
])->fetch();

if ($arResult && $arParams['SET_TITLE'] == 'Y')
{
	$APPLICATION->SetTitle(htmlspecialcharsEx($arResult['NAME']));
}

$this->IncludeComponentTemplate();