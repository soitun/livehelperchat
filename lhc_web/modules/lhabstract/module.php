<?php

$Module = array( "name" => "Abstract module");

$ViewList = array();

$ViewList['new'] = array(
    'functions' => array( 'use' ),
    'params' => array('identifier'),
    'uparams' => array('extension')
);

$ViewList['list'] = array(
    'functions' => array( 'use' ),
    'params' => array('identifier'),
    'uparams' => array(
        'extension',
        'name',
        'object_id',
        'user_id',
        'category',
        'source',
        'message',
        'timefrom','timefrom_hours','timefrom_seconds','timefrom_minutes',
        'timeto', 'timeto_minutes', 'timeto_seconds', 'timeto_hours',
        )
);

$ViewList['downloadbinnary'] = array(
    'functions' => array( 'use' ),
    'params' => array('identifier','object_id')
);

$ViewList['copyautoresponder'] = array(
    'functions' => array( 'use' ),
    'params' => array('id')
);

$ViewList['edit'] = array(
    'functions' => array( 'use' ),
    'params' => array('identifier','object_id'),
    'uparams' => array('extension')
);

$ViewList['delete'] = array(
    'functions' => array( 'use' ),
    'params' => array('identifier','object_id'),
    'uparams' => array('csfr','extension')
);

$ViewList['index'] = array(
    'functions' => array( 'use' ),
    'params' => array()
);

$ViewList['testmasking'] = array(
    'functions' => array( 'use' ),
    'params' => array()
);

$FunctionList = array();
$FunctionList['use'] = array('explain' => 'Allow to use abstract module');

?>