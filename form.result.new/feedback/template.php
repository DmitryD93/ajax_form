<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arResult
 * @var array $templateFolder
 */

// Подключаем скрипты
CJSCore::Init(array("ajax"));
$asset = \Bitrix\Main\Page\Asset::getInstance();
$asset->addJs($templateFolder . "/smartcaptcha.js");
$asset->addJs($templateFolder . "/validateForms.js");

?>

<?if ($arResult["isFormErrors"] == "Y"):?><?=$arResult["FORM_ERRORS_TEXT"];?><?endif;?>
<?= $arResult["FORM_NOTE"] ?? '' ?>
<?if ($arResult["isFormNote"] === "Y"):?>

<?else:?>
<?=$arResult["FORM_HEADER"]?>
    <div class="error-msg"></div>
    <div class="home-form__top">
<?
if ($arResult["isFormDescription"] == "Y" || $arResult["isFormTitle"] == "Y" || $arResult["isFormImage"] == "Y")
{
?>
	<?
if ($arResult["isFormTitle"])
{
?>
<?
}
	if ($arResult["isFormImage"] == "Y")
	{
	?>
	<a href="<?=$arResult["FORM_IMAGE"]["URL"]?>" target="_blank" alt="<?=GetMessage("FORM_ENLARGE")?>"><img src="<?=$arResult["FORM_IMAGE"]["URL"]?>" <?if($arResult["FORM_IMAGE"]["WIDTH"] > 300):?>width="300" <?elseif($arResult["FORM_IMAGE"]["HEIGHT"] > 200):?>height="200"<?else:?><?=$arResult["FORM_IMAGE"]["ATTR"]?><?endif;?> hspace="3" vscape="3" border="0" alt=""/></a>
	<?
	}
	?>
	<?
}
	?>
        <div class="home-form__labels">
	<?
	foreach ($arResult["QUESTIONS"] as $FIELD_SID => $arQuestion):?>
    <?if($arQuestion["STRUCTURE"][0]["FIELD_TYPE"] !== "checkbox"):?>
    <label class="home-form__label form__label">
            <?=$arQuestion["HTML_CODE"]?>
        </label>
        <?endif;?>
	<? endforeach; ?>
            <input style="display: none;" <?=(intval($arResult["F_RIGHT"]) < 10 ? "disabled=\"disabled\"" : "");?> type="submit" name="web_form_submit" value="<?=htmlspecialcharsbx(trim($arResult["arForm"]["BUTTON"]) == '' ? GetMessage("FORM_ADD") : $arResult["arForm"]["BUTTON"]);?>" />

        </div>
        <div id="smartcaptcha-feedback-form"></div>
    <button class="home-form__btn btn btn-light js-home-form-accept">
        Отправить форму
    </button>
    </div>

    <div class="home-form__bottom">
        <label class="form__checkbox">
            <?= $arResult["QUESTIONS"]["AGREE"]["HTML_CODE"];?>
            <span class="form__checkbox-text">
                <?= $arResult["QUESTIONS"]["AGREE"]["CAPTION"];?>
                            </span>
        </label>
    </div>
<?=$arResult["FORM_FOOTER"]?>
<? endif;?>

<script>
        let formAction = '<?=$templateFolder?>/ajax_form.php'
</script>