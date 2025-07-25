<h1><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('genericbot/list','Commands list')?></h1>

<?php if (isset($items)) : ?>

    <table class="table" cellpadding="0" cellspacing="0" width="100%" ng-non-bindable>
        <thead>
        <tr>
            <th><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('user/userlist','Command');?></th>
            <th><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('user/userlist','Bot');?></th>
            <th><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('user/userlist','Trigger');?></th>
            <th><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('user/userlist','Shortcut');?></th>
            <th><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('user/userlist','Position');?></th>
            <th width="1%">&nbsp;</th>
            <th width="1%">&nbsp;</th>
        </tr>
        </thead>
        <?php foreach ($items as $item) : ?>
            <tr>
                <td>
                    <?php if ($item->enabled_display == 1) : ?><span title="<?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('user/userlist','Visible in the chat dropdown menu');?>" class="material-icons">visibility</span><?php endif;?>
                    <a href="<?php echo erLhcoreClassDesign::baseurl('genericbot/editcommand')?>/<?php echo $item->id?>">!<?php echo htmlspecialchars($item->command)?></a><?php if ($item->name != '') : ?> <span class="text-muted fs14">(<?php echo htmlspecialchars($item->name);?>)</span><?php endif;?>
                </td>
                <td><?php echo htmlspecialchars($item->bot)?></td>
                <td><?php echo htmlspecialchars($item->trigger)?></td>
                <td>
                    <?php if ($item->shortcut_1 != '' && $item->shortcut_2 != '') : ?>
                        <?php echo htmlspecialchars($item->shortcut_1 . '+' . $item->shortcut_2)?>
                    <?php endif; ?>
                </td>
                <td><?php echo $item->position?></td>
                <td><a class="btn btn-secondary btn-xs" href="<?php echo erLhcoreClassDesign::baseurl('genericbot/editcommand')?>/<?php echo $item->id?>"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('user/userlist','Edit');?></a></td>
                <td><a class="btn btn-danger btn-xs csfr-post csfr-required" data-trans="delete_confirm" href="<?php echo erLhcoreClassDesign::baseurl('genericbot/deletecommand')?>/<?php echo $item->id?>"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('user/userlist','Delete');?></a></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php include(erLhcoreClassDesign::designtpl('lhkernel/secure_links.tpl.php')); ?>

    <?php if (isset($pages)) : ?>
        <?php include(erLhcoreClassDesign::designtpl('lhkernel/paginator.tpl.php')); ?>
    <?php endif;?>

<?php endif; ?>

<a class="btn btn-secondary" href="<?php echo erLhcoreClassDesign::baseurl('genericbot/newcommand')?>"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('genericbot/list','New')?></a>
