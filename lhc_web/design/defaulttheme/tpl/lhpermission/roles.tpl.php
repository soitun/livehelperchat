<h1><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('permission/roles','List of roles');?></h1>
<?php
	$canEdit = $currentUser->hasAccessTo('lhpermission','edit');
	$canDelete = $currentUser->hasAccessTo('lhpermission','delete');
?>
<table class="table table-sm" cellpadding="0" cellspacing="0" ng-non-bindable>
<thead>
<tr>
    <th width="1%">ID</th>
    <th><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('permission/roles','Title');?></th>
    <?php if ($canEdit) : ?><th width="1%">&nbsp;</th><?php endif;?>
    <?php if ($canEdit) : ?><th width="1%">&nbsp;</th><?php endif;?>
    <?php if ($canDelete) : ?><th width="1%">&nbsp;</th><?php endif;?>
</tr>
</thead>
<?php foreach (erLhcoreClassRole::getRoleList() as $departament) : ?>
    <tr>
        <td><?php echo $departament['id']?></td>
        <td><?php echo htmlspecialchars($departament['name'])?></td>
        <?php if ($canEdit) : ?><td nowrap><a class="btn btn-secondary btn-xs" href="<?php echo erLhcoreClassDesign::baseurl('permission/editrole')?>/<?php echo $departament['id']?>"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('permission/roles','Edit');?></a></td><?php endif;?>
        <?php if ($canEdit) : ?><td nowrap><a class="btn btn-secondary btn-xs csfr-required" href="<?php echo erLhcoreClassDesign::baseurl('permission/clonerole')?>/<?php echo $departament['id']?>"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('permission/roles','Clone');?></a></td><?php endif;?>
        <?php if ($canDelete) : ?><td nowrap><?php if ($departament['id'] != 1 && erLhcoreClassRole::canDeleteRole($departament['id']) === true) : ?><a class="btn btn-danger btn-xs csfr-post csfr-required" data-trans="delete_confirm" href="<?php echo erLhcoreClassDesign::baseurl('permission/deleterole')?>/<?php echo $departament['id']?>"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('permission/roles','Delete a role');?></a><?php endif;?></td><?php endif;?>
    </tr>
<?php endforeach; ?>
</table>

<?php include(erLhcoreClassDesign::designtpl('lhkernel/secure_links.tpl.php')); ?>

<?php if ($currentUser->hasAccessTo('lhpermission','new')) : ?>
<a class="btn btn-sm btn-secondary" href="<?php echo erLhcoreClassDesign::baseurl('permission/newrole')?>"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('permission/roles','New role');?></a>
<?php endif;?>