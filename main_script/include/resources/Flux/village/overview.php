<map name="rx" id="rx">
    <?php use Core\Session;

    $index = 0; ?>
    <?php foreach ($areas as $area):++$index; ?>
        <?php $area['title'] = htmlspecialchars($area['title']); ?>
        <?php $area['alt'] = htmlspecialchars($area['alt']); ?>
        <area data-index="<?= $index; ?>"
              href="build.php?id=<?=$index; ?><?= Session::getInstance()->fastUpgradeActive() ? '&fastUP=1' : null; ?>"
              coords="<?=$area['coordinates']; ?>" shape="circle"
              title="<?=$area['title']; ?>" alt="<?=$area['alt']; ?>"/>
    <?php endforeach; ?>
    <area href="dorf2.php" coords="197,191,32" shape="circle" title="<?=T("inGame", "Navigation.Buildings"); ?>">
</map>
<img id="resfeld" usemap="#rx" src="img/x.gif" alt=""/>
<div id="village_map" class="f<?=$fieldType; ?>">
    <?php if ($showColored): ?>
        <?php $index = 0; ?>
        <?php foreach ($maps as $index => $map):++$index; ?>
            <div class="level colorLayer <?=$map['color']; ?> gid<?=$map['item_id']; ?> level<?=$map['level']; ?> <?=$map['upgradeState'] ? ('aid' . $index . ' underConstruction') : 'aid' . ($index); ?>"
                 style="<?=$map['style']; ?>">
                <div class="labelLayer"><?=$map['level'] > 0 ? $map['level'] : ''; ?></div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <?php $index = 0; ?>
        <?php foreach ($maps as $map):++$index; ?>
            <div class="level <?=$map['color']; ?> gid<?=$map['item_id']; ?> level<?=$map['level']; ?> <?=$map['upgradeState'] ? ('aid' . $index . ' underConstruction') : 'aid' . ($index); ?>"
                 style="<?=$map['style']; ?>">
                <?=$map['level'] > 0 ? $map['level'] : ''; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<div id="map_details">
    <div class="movements">
        <?=$movements; ?>
    </div>
    <?=$resourceProduction; ?>
    <div class="boxes villageList units">
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
            <table id="troops" cellpadding="1" cellspacing="1">
                <thead>
                <tr>
                    <th colspan="3">
                        <?=T("Dorf1", "units"); ?>:
                    </th>
                </tr>
                </thead>
                <tbody>
                <?php if ($unitsSummary <= 0): ?>
                    <tr>
                        <td><?=T("Dorf1", "none"); ?>.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($units as $key => $num): ?>
                        <tr>
                            <td class="ico">
                                <a href="build.php?gid=16&tt=1#td">
                                    <img class="unit u<?=$key == 98 ? 'hero' : $key; ?>" src="img/x.gif"
                                         alt="<?=T("Troops", ($key == 98 ? 'hero' : $key) . '.title'); ?>">
                                </a>
                            </td>
                            <td class="num"><?=number_format_x($num, 1e12); ?></td>
                            <td class="un"><?=T("Troops", ($key == 98 ? 'hero' : $key) . '.title'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($heroOnly): ?>
                        <tr>
                            <td><?=T("Dorf1", "none"); ?>.</td>
                        </tr>
                    <?php endif; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="clear"></div>
</div>
<?=$buildingQueue; ?>