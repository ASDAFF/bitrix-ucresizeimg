<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?if (empty($arResult["ERROR"])) :
    ?><img alt="<?=$arResult["DESCRIPTION"]
        ?>" src="<?=$arResult["SRC"]
        ?>" width="<?=$arResult["WIDTH"]
        ?>" height="<?=$arResult["HEIGHT"]?>" /><?
else :
    ?><div class="error"><?=$arResult["ERROR"]?></div><?
endif?>
