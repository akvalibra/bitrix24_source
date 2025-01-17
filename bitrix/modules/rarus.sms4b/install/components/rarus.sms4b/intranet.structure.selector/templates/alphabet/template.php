<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if ($arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_LAST_NAME'])
{
	if ($key = array_search($arParams['FILTER_NAME'].'_LAST_NAME', $arResult['FILTER_PARAMS'], true))
	{
		unset($arResult['FILTER_PARAMS'][$key]);
	}
}

$arParams['LIST_URL'] .= !str_contains($arParams['LIST_URL'], '?') ? '?' : '&';
$arExtraVars = ['current_view' => $arParams['CURRENT_VIEW'], 'current_filter' => $arParams['CURRENT_FILTER']];
?>

<div class="alphabet">
	<div class = "decoration-div"></div>
    <?php
	$alph = GetMessage('ISS_TPL_ALPH');
    
	for ($i = 0; $i < mb_strlen($alph); $i++)
	{
		$symbol = mb_substr($alph, $i, 1);
		$bCurrent = $arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_LAST_NAME'] == $symbol.'%';
	?>
		   
			<a href="<?=$arParams['LIST_URL']?>set_filter_<?=$arParams['FILTER_NAME']?>=Y&<?=$arParams['FILTER_NAME']?>_LAST_NAME=<?=urlencode($symbol.'%')?><?=GetFilterParams($arResult['FILTER_PARAMS'], true, $arExtraVars)?>">

                <?php if ($bCurrent):?>
				    <div class = "selected">
				    	<?=$bCurrent ? '<b>' : ''?><?=$symbol?><?=$bCurrent ? '</b>' : ''?>	
				    </div>
                <?php else:?>
					<div>
						<?=$bCurrent ? '<b>' : ''?><?=$symbol?><?=$bCurrent ? '</b>' : ''?>
					</div>
                <?php endif;?>
			</a>
        <?php
	}
	?>
	<div>
		<a href="<?=$arParams['LIST_URL']?>set_filter_<?=$arParams['FILTER_NAME']?>=Y<?=GetFilterParams($arResult['FILTER_PARAMS'], true, $arExtraVars)?>"><?=GetMessage('ALL')?></a>
	</div>
</div>