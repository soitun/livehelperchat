<?php

$Module = array( "name" => "Users, groups management");

$ViewList = array();

$ViewList['login'] = array(
    'params' => array(),
    'uparams' => array('r','external_request','noaccess'),
);

$ViewList['autologin'] = array(
    'params' => array('hash'),
    'uparams' => array('r','u','l','t'),
);

$ViewList['autologinuser'] = array(
    'params' => array('hash'),
    'uparams' => array(),
);

$ViewList['logout'] = array(
    'params' => array(),
    'uparams' => array('csfr')
);

$ViewList['loginas'] = array(
    'params' => array('id'),
    'functions' => array( 'loginas' )
);

$ViewList['loginasuser'] = array(
    'params' => array('id'),
    'uparams' => array('hash', 'ts', 'showlogin')
);

$ViewList['account'] = array(
    'params' => array(),
    'uparams' => array('msg','action','csfr','tab','title','message','fmsg'),
    'functions' => array( 'selfedit' )
);

$ViewList['editdepartment'] = array(
    'params' => array('user_id','dep_id'),
    'uparams' => array('csfr','action','mode','editor'),
    'functions' => array( 'selfedit' )
);

$ViewList['newdepartment'] = array(
    'params' => array('user_id'),
    'uparams' => array('csfr','mode','editor'),
    'functions' => array( 'selfedit' )
);

$ViewList['userdepartments'] = array(
    'params' => array('user_id'),
    'uparams' => array('editor'),
    'functions' => array( 'selfedit' )
);

$ViewList['avatarbuilder'] = array(
    'params' => array('id'),
    'uparams' => array(),
    'functions' => array( 'selfedit' )
);

$ViewList['userlist'] = array(
    'params' => array(),
    'uparams' => array('email' , 'name' , 'username' , 'surname', 'group_ids', 'disabled', 'export','timefrom','timeto','timefrom_minutes','timefrom_hours','timeto_hours','timeto_minutes'),
    'functions' => array( 'userlist' ),
    'multiple_arguments' => array('group_ids')
);

$ViewList['grouplist'] = array(
    'params' => array(),
    'functions' => array( 'grouplist' )
);

$ViewList['edit'] = array(
    'params' => array('user_id'),
    'uparams' => array('tab', 'category',
        'timefrom','timefrom_hours','timefrom_seconds','timefrom_minutes',
        'timeto', 'timeto_minutes', 'timeto_seconds', 'timeto_hours'
    ),
    'functions' => array( 'edituser' ),
    'multiple_arguments' => array('category')
);

$ViewList['delete'] = array(
    'params' => array('user_id'),
    'uparams' => array('csfr'),
    'functions' => array( 'deleteuser' )
);

$ViewList['new'] = array(
    'params' => array(),
    'uparams' => array('tab'),
    'functions' => array( 'createuser' )
);

$ViewList['import'] = array(
    'params' => array(),
    'uparams' => array(),
    'functions' => array( 'import' )
);

$ViewList['newgroup'] = array(
    'params' => array(),
    'functions' => array( 'creategroup', 'editgroup' )
);

$ViewList['editgroup'] = array(
    'params' => array('group_id'),
    'functions' => array( 'editgroup' )
    );

$ViewList['clonegroup'] = array(
    'params' => array('group_id'),
    'uparams' => array('csfr'),
    'functions' => array( 'editgroup' )
);

$ViewList['groupassignuser'] = array(
    'params' => array('group_id'),
    'functions' => array( 'groupassignuser' )
    );

$ViewList['deletegroup'] = array(
    'params' => array('group_id'),
	'uparams' => array('csfr'),
    'functions' => array( 'deletegroup' )
    );

$ViewList['forgotpassword'] = array(
    'params' => array(),
);

$ViewList['remindpassword'] = array(
    'params' => array('hash'),
);

$ViewList['setsetting'] = array (
		'params' => array('identifier','value'),
        'uparams' => array('csfr')
);

$ViewList['setsettingajax'] = array (
        'params' => array('identifier','value'),
        'uparams' => array('indifferent')
);

$ViewList['setsettingajaxraw'] = array (
		'params' => array('identifier')
);

$ViewList['setoffline'] = array (
		'functions' => array( 'changeonlinestatus' ),
		'params' => array('status')
);

$ViewList['setalwaysonline'] = array (
		'functions' => array( 'changealwaysonline' ),
		'params' => array('status')
);

$ViewList['setinactive'] = array (
    'functions' => array( 'changeonlinestatus' ),
    'params' => array('status')
);

$ViewList['wentinactive'] = array (
    'functions' => array( 'changeonlinestatus' ),
    'params' => array()
);

$ViewList['setinvisible'] = array (
		'functions' => array( 'changevisibility' ),
		'params' => array('status')
);

$ViewList['autologinconfig'] = array(
    'params' => array(),
    'uparams' => array('csfr'),
    'functions' => array( 'userautologinconfig' )
);

$ViewList['passwordrequirements'] = array(
    'params' => array(),
    'uparams' => array('csfr'),
    'functions' => array( 'pswdsecurity' )
);

$ViewList['updatepassword'] = array(
    'params' => array('user_id','ts','hash'),
    'uparams' => array('csfr'),
    'functions' => array( )
);

$ViewList['setopstatus'] = array(
    'params' => array('user_id'),
    'functions' => array('setopstatus' )
);

$FunctionList['groupassignuser'] = array('explain' => 'Allow user to assign user to group');
$FunctionList['editgroup'] = array('explain' => 'Allow user to edit group');
$FunctionList['creategroup'] = array('explain' => 'Allow user to create group');
$FunctionList['deletegroup'] = array('explain' => 'Allow user to delete group');
$FunctionList['createuser'] = array('explain' => 'Allow user to create another user');
$FunctionList['deleteuser'] = array('explain' => 'Allow user to delete another user');
$FunctionList['edituser'] = array('explain' => 'Allow user to edit another user');
$FunctionList['editusergroupall'] = array('explain' => 'Allow user to edit other user groups even if they are not a member of it.');
$FunctionList['grouplist'] = array('explain' => 'Allow user to list group');
$FunctionList['userlist'] = array('explain' => 'Allow user to list users');
$FunctionList['selfedit'] = array('explain' => 'Allow user to edit their own data');


// All department option
$FunctionList['self_all_departments'] = array('explain' => 'Allow user to assign themself to all departments option');
$FunctionList['edit_all_departments'] = array('explain' => 'Allow user to assign other users to all departments option');

// Edit mode user
$FunctionList['assign_all_department_individual'] = array('explain' => 'Allow user edit other users all individual departments');
$FunctionList['assign_all_department_group'] = array('explain' => 'Allow user to assign other users to all department groups');

$FunctionList['assign_to_own_department_individual'] = array('explain' => 'Allow user to change other users individual departments (only if operator belong to them)');
$FunctionList['assign_to_own_department_group'] = array('explain' => 'Allow user to change other users departments groups (only if operator belong to them)');

$FunctionList['see_user_assigned_departments'] = array('explain' => 'Allow user to see to other user assigned departments');
$FunctionList['see_user_assigned_departments_groups'] = array('explain' => 'Allow user to see to other user assigned departments groups');

// Self account
$FunctionList['see_assigned_departments'] = array('explain' => 'Allow user to see departments assigned to them');
$FunctionList['see_assigned_departments_groups'] = array('explain' => 'Allow user to see departments groups assigned to them');
$FunctionList['editdepartaments'] = array('explain' => 'Allow user to edit their own responsible departments/departments groups');


$FunctionList['userlistonline'] = array('explain' => 'Allow user to see logged operators list, only from their department');
$FunctionList['userlistonlineall'] = array('explain' => 'Allow user to see logged operators list, not only from their department');
$FunctionList['changeonlinestatus'] = array('explain' => 'Allow user to change their online status');
$FunctionList['changeskypenick'] = array('explain' => 'Allow user to change/enter their skype nick');
$FunctionList['personalcannedmsg'] = array('explain' => 'Allow user to have personal canned messages');
$FunctionList['personalautoresponder'] = array('explain' => 'Allow user to have personal auto responder messages');
$FunctionList['changevisibility'] = array('explain' => 'Allow user to change their visibility mode');
$FunctionList['change_visibility_list'] = array('explain' => 'Allow user to choose what list should be visible to them, pending/active/unread/closed');
$FunctionList['allowtochoosependingmode'] = array('explain' => 'Allow user to choose what pending chats they can see, only assigned to them or all.');
$FunctionList['receivepermissionrequest'] = array('explain' => 'Allow user to choose should if they should receive other operator\'s permission requests');
$FunctionList['userautologin'] = array('explain' => 'Allow user to use autologin');
$FunctionList['userautologinconfig'] = array('explain' => 'Allow user to configure autologin');
$FunctionList['canseedepartmentstats'] = array('explain' => 'Allow user to see departments statistic');
$FunctionList['canseealldepartmentstats'] = array('explain' => 'Allow user to see all departments statistic, not only from their departments');
$FunctionList['import'] = array('explain' => 'Allow user to import users');
$FunctionList['loginas'] = array('explain' => 'Allow user to login as other user');
$FunctionList['passwordsecurity'] = array('explain' => 'Allow user to set password security requirements');
$FunctionList['see_all'] = array('explain' => 'Allow user see all users/groups not only from their group/groups');
$FunctionList['see_all_group_users'] = array('explain' => 'Allow user see all group users they belongs to.'); // should this be "user groups" rather than "group users"?
$FunctionList['changealwaysonline'] = array('explain' => 'Allow user to change always online mode');
$FunctionList['setopstatus'] = array('explain' => 'Allow user to change other user online status from online operators widget');
$FunctionList['change_chat_nickname'] = array('explain' => 'Allow user to change their own chat nickname');
$FunctionList['changephoto'] = array('explain' => 'Allow user to change their own photo/avatar');
$FunctionList['change_job_title'] = array('explain' => 'Allow user to change their own job title');
$FunctionList['change_core_attributes'] = array('explain' => 'Allow user to change their own username/password/e-mail/XMPP Usernautoame');
$FunctionList['change_name_surname'] = array('explain' => 'Allow user to change their own name/surname');
$FunctionList['change_password'] = array('explain' => 'Allow user to change their password');
$FunctionList['pswdsecurity'] = array('explain' => 'Allow user to manage password requirements');
$FunctionList['largeactivitytimeout'] = array('explain' => 'Allow user to choose a large inactivity timeout');

?>
