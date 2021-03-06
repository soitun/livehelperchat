<?php

$cfgSite = erConfigClassLhConfig::getInstance();

if ($cfgSite->conf->getSetting( 'site', 'installed' ) == true)
{
    $Params['module']['functions'] = array('install');
    include_once('modules/lhkernel/nopermission.php'); 
     
    $Result['pagelayout'] = 'install';
    $Result['path'] = array(array('title' => 'Live helper chat installation'));
    return $Result;
    
    exit;
}

$tpl = new erLhcoreClassTemplate( 'lhinstall/install1.tpl.php');

switch ((int)$Params['user_parameters']['step_id']) {
    
	case '1':
		$Errors = array();		
		if (!is_writable("cache/cacheconfig/settings.ini.php"))
	       $Errors[] = "cache/cacheconfig/settings.ini.php is not writable";	
	              
		if (!is_writable("cache/translations"))
	       $Errors[] = "cache/translations is not writable"; 
	       	           
		if (!is_writable("cache/userinfo"))
	       $Errors[] = "cache/userinfo is not writable";
	       
		if (!extension_loaded ('pdo_mysql' ))
	       $Errors[] = "php-pdo extension not detected. Please install php extension";	
	      	       
	       if (count($Errors) == 0)
	           $tpl->setFile('lhinstall/install2.tpl.php');	              
	  break;
	  
	  case '2':
		$Errors = array();	
			
		$definition = array(
            'DatabaseUsername' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::REQUIRED, 'string'
            ),
            'DatabasePassword' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::REQUIRED, 'string'
            ),
            'DatabaseHost' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::REQUIRED, 'string'
            ),
            'DatabasePort' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::REQUIRED, 'int'
            ),
            'DatabaseDatabaseName' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::REQUIRED, 'string'
            ),
        );
	     	       
	   $form = new ezcInputForm( INPUT_POST, $definition ); 
	      
	   
	   if ( !$form->hasValidData( 'DatabaseUsername' ) || $form->DatabaseUsername == '' )
       {
           $Errors[] = 'Please enter database username';
       }   
	   
	   if ( !$form->hasValidData( 'DatabasePassword' ) || $form->DatabasePassword == '' )
       {
           $Errors[] = 'Please enter database password';
       } 
       
	   if ( !$form->hasValidData( 'DatabaseHost' ) || $form->DatabaseHost == '' )
       {
           $Errors[] = 'Please enter database host';
       }  
       
	   if ( !$form->hasValidData( 'DatabasePort' ) || $form->DatabasePort == '' )
       {
           $Errors[] = 'Please enter database post';
       }
       
	   if ( !$form->hasValidData( 'DatabaseDatabaseName' ) || $form->DatabaseDatabaseName == '' )
       {
           $Errors[] = 'Please enter database name';
       }
       
       if (count($Errors) == 0)
       { 
           try {
           $db = ezcDbFactory::create( "mysql://{$form->DatabaseUsername}:{$form->DatabasePassword}@{$form->DatabaseHost}:{$form->DatabasePort}/{$form->DatabaseDatabaseName}" );
           } catch (Exception $e) {     
                  $Errors[] = 'Cannot login with provided logins. Returned message: <br/>'.$e->getMessage();
           }
       }
	    
	       if (count($Errors) == 0){
	           
	           $cfgSite = erConfigClassLhConfig::getInstance();
	           $cfgSite->conf->setSetting( 'db', 'host', $form->DatabaseHost);
	           $cfgSite->conf->setSetting( 'db', 'user', $form->DatabaseUsername);
	           $cfgSite->conf->setSetting( 'db', 'password', $form->DatabasePassword);
	           $cfgSite->conf->setSetting( 'db', 'database', $form->DatabaseDatabaseName);
	           $cfgSite->conf->setSetting( 'db', 'port', $form->DatabasePort);
	           
	           $cfgSite->conf->setSetting( 'site', 'secrethash', substr(md5(time() . ":" . mt_rand()),0,10));
	           
	           $cfgSite->save();
	                 
	           $tpl->setFile('lhinstall/install3.tpl.php');	
	       } else {
	           
	          $tpl->set('db_username',$form->DatabaseUsername);
	          $tpl->set('db_password',$form->DatabasePassword);
	          $tpl->set('db_host',$form->DatabaseHost);
	          $tpl->set('db_port',$form->DatabasePort);
	          $tpl->set('db_name',$form->DatabaseDatabaseName);
	          
	          $tpl->set('errors',$Errors);
	          $tpl->setFile('lhinstall/install2.tpl.php');	  
	       }           
	  break;

	case '3':
	    
	    $Errors = array();	

	    if ($_SERVER['REQUEST_METHOD'] == 'POST')
	    {	
    		$definition = array(
                'AdminUsername' => new ezcInputFormDefinitionElement(
                    ezcInputFormDefinitionElement::REQUIRED, 'string'
                ),
                'AdminPassword' => new ezcInputFormDefinitionElement(
                    ezcInputFormDefinitionElement::REQUIRED, 'string'
                ),
                'AdminPassword1' => new ezcInputFormDefinitionElement(
                    ezcInputFormDefinitionElement::REQUIRED, 'string'
                ),
                'AdminEmail' => new ezcInputFormDefinitionElement(
                    ezcInputFormDefinitionElement::REQUIRED, 'validate_email'
                ),
                'AdminName' => new ezcInputFormDefinitionElement(
                    ezcInputFormDefinitionElement::OPTIONAL, 'string'
                ),
                'AdminSurname' => new ezcInputFormDefinitionElement(
                    ezcInputFormDefinitionElement::OPTIONAL, 'string'
                ),
                'DefaultDepartament' => new ezcInputFormDefinitionElement(
                    ezcInputFormDefinitionElement::REQUIRED, 'string'
                )
            );
    	
    	    $form = new ezcInputForm( INPUT_POST, $definition ); 
    
    	        
    	    if ( !$form->hasValidData( 'AdminUsername' ) || $form->AdminUsername == '')
            {
                $Errors[] = 'Please enter admin username';
            }  
            
            if ($form->hasValidData( 'AdminUsername' ) && $form->AdminUsername != '' && strlen($form->AdminUsername) > 10)
            {
                $Errors[] = 'Maximum 10 characters for admin username';
            }
               
    	    if ( !$form->hasValidData( 'AdminPassword' ) || $form->AdminPassword == '')
            {
                $Errors[] = 'Please enter admin password';
            }    
            
    	    if ($form->hasValidData( 'AdminPassword' ) && $form->AdminPassword != '' && strlen($form->AdminPassword) > 10)
            {
                $Errors[] = 'Maximum 10 characters for admin password';
            }        
                    
    	    if ($form->hasValidData( 'AdminPassword' ) && $form->AdminPassword != '' && strlen($form->AdminPassword) <= 10 && $form->AdminPassword1 != $form->AdminPassword)
            {
                $Errors[] = 'Passwords missmatch';
            } 
           
                   
    	    if ( !$form->hasValidData( 'AdminEmail' ) )
            {
                $Errors[] = 'Wrong email address';
            } 
          
            
            if ( !$form->hasValidData( 'DefaultDepartament' ) || $form->DefaultDepartament == '')
            {
                $Errors[] = 'Please enter default departament name';
            } 
            
            if (count($Errors) == 0) {
                
               $tpl->set('admin_username',$form->AdminUsername);               
               if ( $form->hasValidData( 'AdminEmail' ) ) $tpl->set('admin_email',$form->AdminEmail);                      
    	       $tpl->set('admin_name',$form->AdminName);
    	       $tpl->set('admin_surname',$form->AdminSurname);	       
    	       $tpl->set('admin_departament',$form->DefaultDepartament);
    	       
    	       /*DATABASE TABLES SETUP*/
    	       $db = ezcDbInstance::get();
    	       
        	   $db->query("CREATE TABLE IF NOT EXISTS `lh_chat` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `nick` varchar(50) NOT NULL,
                  `status` int(11) NOT NULL DEFAULT '0',
                  `time` int(11) NOT NULL,
                  `user_id` int(11) NOT NULL,
                  `hash` varchar(40) NOT NULL,
                  `referrer` text NOT NULL,
                  `ip` varchar(100) NOT NULL,
                  `dep_id` int(11) NOT NULL,
                  `user_status` int(11) NOT NULL DEFAULT '0',
                  `support_informed` int(11) NOT NULL DEFAULT '0',
                  `email` varchar(100) NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `status` (`status`),
                  KEY `user_id` (`user_id`),
                  KEY `dep_id` (`dep_id`)
                )");
           
        	   //Default departament
        	   $db->query("CREATE TABLE IF NOT EXISTS `lh_departament` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(100) NOT NULL,
                  PRIMARY KEY (`id`)
                )");

        	   $Departament = new erLhcoreClassModelDepartament();
               $Departament->name = $form->DefaultDepartament;    
               erLhcoreClassDepartament::getSession()->save($Departament);
               
               //Administrators group
               $db->query("CREATE TABLE IF NOT EXISTS `lh_group` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(50) NOT NULL,
                  PRIMARY KEY (`id`)
                )");
               
               $GroupData = new erLhcoreClassModelGroup();
               $GroupData->name    = "Administrators";
               erLhcoreClassUser::getSession()->save($GroupData);
               
               //Administrators role
               $db->query("CREATE TABLE IF NOT EXISTS `lh_role` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(50) NOT NULL,
                  PRIMARY KEY (`id`)
                )");
               $Role = new erLhcoreClassModelRole();
               $Role->name = 'Administrators';
               erLhcoreClassRole::getSession()->save($Role);

               
               //Assing group role
               $db->query("CREATE TABLE IF NOT EXISTS `lh_grouprole` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `group_id` int(11) NOT NULL,
                  `role_id` int(11) NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `group_id` (`role_id`,`group_id`)
                )");

               $GroupRole = new erLhcoreClassModelGroupRole();        
               $GroupRole->group_id =$GroupData->id;
               $GroupRole->role_id = $Role->id;        
               erLhcoreClassRole::getSession()->save($GroupRole);
        
               // Users
               $db->query("CREATE TABLE IF NOT EXISTS `lh_users` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `username` varchar(40) NOT NULL,
                      `password` varchar(40) NOT NULL,
                      `email` varchar(100) NOT NULL,
                      `lastactivity` int(11) NOT NULL,
                      `name` varchar(100) NOT NULL,
                      `surname` varchar(100) NOT NULL,
                      PRIMARY KEY (`id`)
                    )");
               
                $UserData = new erLhcoreClassModelUser();

                $UserData->setPassword($form->AdminPassword);
                $UserData->email   = $form->AdminEmail;
                $UserData->name    = $form->AdminName;
                $UserData->surname = $form->AdminSurname;
                $UserData->username = $form->AdminUsername;
        
                erLhcoreClassUser::getSession()->save($UserData);
        
                //User departaments
                $db->query("CREATE TABLE IF NOT EXISTS `lh_userdep` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `user_id` int(11) NOT NULL,
                  `dep_id` int(11) NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `user_id` (`user_id`)
                )"); 

                // Transfer chat
                $db->query("CREATE TABLE IF NOT EXISTS `lh_transfer` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `chat_id` int(11) NOT NULL,
                  `user_id` int(11) NOT NULL,
                  PRIMARY KEY (`id`)
                )");
                
                // Chat messages
                $db->query("CREATE TABLE IF NOT EXISTS `lh_msg` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `msg` text NOT NULL,
                  `status` int(11) NOT NULL DEFAULT '0',
                  `time` int(11) NOT NULL,
                  `chat_id` int(11) NOT NULL DEFAULT '0',
                  `user_id` int(11) NOT NULL DEFAULT '0',
                  `name_support` varchar(100) NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `chat_id` (`chat_id`),
                  KEY `id` (`id`,`chat_id`),
                  KEY `status` (`status`,`chat_id`)
                )");
                
                // Forgot password table
                $db->query("CREATE TABLE IF NOT EXISTS `lh_forgotpasswordhash` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` INT NOT NULL ,
                `hash` VARCHAR( 40 ) NOT NULL ,
                `created` INT NOT NULL,
                PRIMARY KEY (`id`)
                )");
                
                // User groups table
                $db->query("CREATE TABLE IF NOT EXISTS `lh_groupuser` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `group_id` int(11) NOT NULL,
                  `user_id` int(11) NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `group_id` (`group_id`),
                  KEY `user_id` (`user_id`),
                  KEY `group_id_2` (`group_id`,`user_id`)
                )");

                $GroupUser = new erLhcoreClassModelGroupUser();        
                $GroupUser->group_id = $GroupData->id;
                $GroupUser->user_id = $UserData->id;        
                erLhcoreClassUser::getSession()->save($GroupUser);
                
                //Assign default role functions
                $db->query("CREATE TABLE IF NOT EXISTS `lh_rolefunction` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `role_id` int(11) NOT NULL,
                  `module` varchar(100) NOT NULL,
                  `function` varchar(100) NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `role_id` (`role_id`)
                )");
                
                
                $RoleFunction = new erLhcoreClassModelRoleFunction();
                $RoleFunction->role_id = $Role->id;
                $RoleFunction->module = '*';
                $RoleFunction->function = '*';                
                erLhcoreClassRole::getSession()->save($RoleFunction);
                   
               $cfgSite = erConfigClassLhConfig::getInstance();
	           $cfgSite->conf->setSetting( 'site', 'installed', true);	     
	           $cfgSite->save();
	           
    	       $tpl->setFile('lhinstall/install4.tpl.php');
    	       
            } else {      
                
               $tpl->set('admin_username',$form->AdminUsername);               
               if ( $form->hasValidData( 'AdminEmail' ) ) $tpl->set('admin_email',$form->AdminEmail);                      
    	       $tpl->set('admin_name',$form->AdminName);
    	       $tpl->set('admin_surname',$form->AdminSurname);	       
    	       $tpl->set('admin_departament',$form->DefaultDepartament);
    	       
    	       $tpl->set('errors',$Errors);
    	            
    	       
    	       $tpl->setFile('lhinstall/install3.tpl.php');
            }
	    } else {
	        $tpl->setFile('lhinstall/install3.tpl.php');
	    }
	    	
	    break;
	    
	case '4':
	    $tpl->setFile('lhinstall/install4.tpl.php');
	    break;
	    
	default:
	    $tpl->setFile('lhinstall/install1.tpl.php');
		break;
}



$Result['content'] = $tpl->fetch();
$Result['pagelayout'] = 'install';
$Result['path'] = array(array('title' => 'Live helper chat installation'))

?>