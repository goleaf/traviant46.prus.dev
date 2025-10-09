<div class="boxes villageList production">
    <div class="boxes-tl"></div>
    <div class="boxes-tr"></div>
    <div class="boxes-tc"></div>
    <div class="boxes-ml"></div>
    <div class="boxes-mr"></div>
    <div class="boxes-mc"></div>
    <div class="boxes-bl"></div>
    <div class="boxes-br"></div>
    <div class="boxes-bc"></div>
    <div class="boxes-contents cf">
        <table id="production" cellpadding="1" cellspacing="1">
            <thead>
            <tr>
                <th colspan="4">
                    <?=T("Dorf1", "production.production per hour"); ?>:
                </th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td class="ico">
                    <div>
                        <?php if ($productionBoost[0]): ?>
                            <img src="img/x.gif" class="productionBoost"
                                 alt="<?=T("inGame", "productionBoost.woodProductionBoost"); ?>">
                        <?php endif; ?>
                        <i class="r1"></i>
                    </div>
                </td>
                <td class="res">
                    <?=T("Dorf1", "production.resources.1"); ?>:
                </td>
                <td class="num">
                    <?=number_format_x($production[0], 1e12); ?>
                </td>
            </tr>
            <tr>
                <td class="ico">
                    <div>
                        <?php if ($productionBoost[1]): ?>
                            <img src="img/x.gif" class="productionBoost"
                                 alt="<?=T("inGame", "productionBoost.woodProductionBoost"); ?>">
                        <?php endif; ?>
                        <i class="r2"></i>
                    </div>
                </td>
                <td class="res">
                    <?=T("Dorf1", "production.resources.2"); ?>:
                </td>
                <td class="num">
                    <?=number_format_x($production[1], 1e12); ?>
                </td>
            </tr>
            <tr>
                <td class="ico">
                    <div>
                        <?php if ($productionBoost[2]): ?>
                            <img src="img/x.gif" class="productionBoost"
                                 alt="<?=T("inGame", "productionBoost.woodProductionBoost"); ?>">
                        <?php endif; ?>
                        <i class="r3"></i>
                    </div>
                </td>
                <td class="res">
                    <?=T("Dorf1", "production.resources.3"); ?>:
                </td>
                <td class="num">
                    <?=number_format_x($production[2], 1e12); ?>
                </td>
            </tr>
            <tr>
                <td class="ico">
                    <div>
                        <?php if ($productionBoost[3]): ?>
                            <img src="img/x.gif" class="productionBoost"
                                 alt="<?=T("inGame", "productionBoost.woodProductionBoost"); ?>">
                        <?php endif; ?>
                        <i class="r4"></i>
                    </div>
                </td>
                <td class="res">
                    <?=T("Dorf1", "production.resources.4"); ?>:
                </td>
                <td class="num">
                    <?=number_format_x($production[3], 1e12); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <?=$goldProductionBoostButton; ?>
    </div>
</div>
