<?php
$tpl = new erLhcoreClassTemplate( 'lhchat/chat.tpl.php');

try {
          
    $Chat = erLhcoreClassChat::getSession()->load( 'erLhcoreClassModelChat', $Params['user_parameters']['chat_id']);  
    
    if ($Chat->hash == $Params['user_parameters']['hash'])
    {  
        $tpl->set('chat_id',$Params['user_parameters']['chat_id']);
        $tpl->set('hash',$Params['user_parameters']['hash']);
        $tpl->set('chat',$Chat);
        
        // User online
        $Chat->user_status = 0;        
        $Chat->support_informed = 0;        
        erLhcoreClassChat::getSession()->update($Chat);
        
    } else {
        $tpl->setFile( 'lhchat/errors/chatnotexists.tpl.php');  
    }

} catch(Exception $e) {
   $tpl->setFile('lhchat/errors/chatnotexists.tpl.php');      
}



$Result['content'] = $tpl->fetch();
$Result['pagelayout'] = 'userchat';

$Result['path'] = array(array('title' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/chat','Chat started')))


?>