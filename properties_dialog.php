<?php

declare(strict_types=1);

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;
?>
<tr>
    <td class='adm-detail-content-cell-l' align='right' width='40%'><?=Loc::getMessage('DICE_FIELD_NUMBER')?>:</td>
    <td class='adm-detail-content-cell-r' width='60%' valign='top'>
        <textarea rows="1" cols="40" name='Number'><?=htmlspecialcharsbx($arCurrentValues['Number']) ?></textarea>
    </td>
</tr>