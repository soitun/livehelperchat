<?php

$tpl = erLhcoreClassTemplate::getInstance('lhchat/adminchat.tpl.php');

$db = ezcDbInstance::get();
$db->beginTransaction();

$chat = erLhcoreClassModelChat::fetchAndLock($Params['user_parameters']['chat_id']);

if ($chat instanceof erLhcoreClassModelChat && erLhcoreClassChat::hasAccessToRead($chat) )
{
	$userData = $currentUser->getUserData();
    $see_sensitive_information = $currentUser->hasAccessTo('lhchat','see_sensitive_information');

	if (($userData->invisible_mode == 0 || $chat->user_id == $userData->id) && erLhcoreClassChat::hasAccessToWrite($chat)) {
	    try {

            if (($chat->status == erLhcoreClassModelChat::STATUS_PENDING_CHAT &&
                    $chat->user_id != $userData->id &&
                    !$currentUser->hasAccessTo('lhchat','open_all')) &&
                ($chat->user_id != 0 || !$currentUser->hasAccessTo('lhchat','open_unassigned_chat')))
            {
                throw new Exception('You do not have permission to open all pending chats.');
            }

    		$operatorAccepted = false;
    		$chatDataChanged = false;

            $previousUserId = $chat->user_id;

    	    if ($chat->user_id == 0 && $chat->status != erLhcoreClassModelChat::STATUS_BOT_CHAT && $chat->status != erLhcoreClassModelChat::STATUS_CLOSED_CHAT) {
    	        $currentUser = erLhcoreClassUser::instance();
    	        $chat->user_id = $currentUser->getUserID();

    	        // Change sub status only if visitor has not left a chat
    	        if (!in_array($chat->status_sub, array(erLhcoreClassModelChat::STATUS_SUB_SURVEY_COMPLETED, erLhcoreClassModelChat::STATUS_SUB_USER_CLOSED_CHAT, erLhcoreClassModelChat::STATUS_SUB_SURVEY_SHOW, erLhcoreClassModelChat::STATUS_SUB_CONTACT_FORM))) {
                    $chat->status_sub = erLhcoreClassModelChat::STATUS_SUB_OWNER_CHANGED;
                }

    	        $chatDataChanged = true;
    	    }

    	    // If status is pending change status to active
    	    if ($chat->status == erLhcoreClassModelChat::STATUS_PENDING_CHAT) {
    	    	$chat->status = erLhcoreClassModelChat::STATUS_ACTIVE_CHAT;

    	    	$chat->wait_time = time() - ($chat->pnd_time > 0 ? $chat->pnd_time : $chat->time);
    	    	$chat->user_id = $currentUser->getUserID();

                if ($previousUserId > 0 && $chat->user_id == $previousUserId) {
                    $previousUserId = 0;
                }

                // Change sub status only if visitor has not left a chat
                if (!in_array($chat->status_sub, array(erLhcoreClassModelChat::STATUS_SUB_SURVEY_COMPLETED, erLhcoreClassModelChat::STATUS_SUB_USER_CLOSED_CHAT, erLhcoreClassModelChat::STATUS_SUB_SURVEY_SHOW, erLhcoreClassModelChat::STATUS_SUB_CONTACT_FORM))) {
                    $chat->status_sub = erLhcoreClassModelChat::STATUS_SUB_OWNER_CHANGED;
                }

    	    	// User status in event of chat acceptance
    	    	$chat->usaccept = $userData->hide_online;

    	    	$operatorAccepted = true;
    	    	$chatDataChanged = true;
    	    }

    	    // Check does chat transfer record exists if operator opened chat directly
    	    if ($chat->transfer_uid > 0) {
                $operatorAcceptedBeforeTransfer = $operatorAccepted;
                erLhcoreClassTransfer::handleTransferredChatOpen($chat, $currentUser->getUserID(), erLhcoreClassModelTransfer::SCOPE_CHAT, $operatorAccepted);
                if ($operatorAcceptedBeforeTransfer == false && $operatorAccepted == true) {
                    $operatorAccepted = false;
                    erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.chat_transfer_accepted',array('chat' => & $chat));

                    // Store meta message
                    $msg = new erLhcoreClassModelmsg();
                    $msg->name_support = $userData->name_support;

                    \LiveHelperChat\Models\Departments\UserDepAlias::getAlias(array('scope' => 'msg', 'msg' => & $msg, 'chat' => & $chat, 'user_id' => $userData->id));
                    erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.before_msg_admin_saved', array('msg' => & $msg, 'chat' => & $chat, 'user_id' => $userData->id));

                    $msg->msg = (string)$msg->name_support.' '.erTranslationClassLhTranslation::getInstance()->getTranslation('chat/accepttrasnfer','has accepted a transferred chat!');
                    $msg->chat_id = $chat->id;
                    $msg->user_id = -1;
                    $msg->time = time();
                    $msg->meta_msg_array = ['content' => ['accept_action' => ['user_id' => $userData->id, 'name_support' => $msg->name_support]]];
                    $msg->meta_msg = json_encode($msg->meta_msg_array);

                    erLhcoreClassChat::getSession()->save($msg);

                    if ($chat->last_msg_id < $msg->id) {
                        $chat->last_msg_id = $msg->id;
                    }

                }
            }

    	    if ($chat->support_informed == 0 || $chat->has_unread_messages == 1 ||  $chat->unread_messages_informed == 1) {
    	    	$chatDataChanged = true;
    	    }
    	    
    	    $tpl->set('arg', $Params['user_parameters_unordered']['arg']);

    	    // Store who has acceped a chat so other operators will be able easily indicate this
    	    if ($operatorAccepted == true) {

                // If chat is transferred to pending state we don't want to process any old events
                erLhcoreClassGenericBotWorkflow::removePreviousEvents($chat->id);

    	        $msg = new erLhcoreClassModelmsg();
                $msg->name_support = $userData->name_support;

                \LiveHelperChat\Models\Departments\UserDepAlias::getAlias(array('scope' => 'msg', 'msg' => & $msg, 'chat' => & $chat, 'user_id' => $userData->id));
                erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.before_msg_admin_saved', array('msg' => & $msg, 'chat' => & $chat, 'user_id' => $userData->id));

                $msg->msg = (string)$msg->name_support.' '.erTranslationClassLhTranslation::getInstance()->getTranslation('chat/adminchat','has accepted the chat!');
    	        $msg->chat_id = $chat->id;
    	        $msg->user_id = -1;
    	        $msg->time = time();
                $msg->meta_msg_array = ['content' => ['accept_action' => ['puser_id' => $previousUserId, 'ol' => $Params['user_parameters_unordered']['ol'], 'user_id' => $userData->id, 'name_support' => $msg->name_support]]];
                $msg->meta_msg = json_encode($msg->meta_msg_array);

                erLhcoreClassChat::getSession()->save($msg);

    	        if ($chat->last_msg_id < $msg->id) {
    	            $chat->last_msg_id = $msg->id;
    	        }

    	    }

            if (is_array($Params['user_parameters_unordered']['arg']) && in_array('background',$Params['user_parameters_unordered']['arg']) && $chat->user_id > 0 && $chat->user_id != $currentUser->getUserID()) {
                // Avoid loading chat in the background if user is not chat owner
                exit();
            }

    	    // Update general chat attributes
            if ($chat->user_id == $currentUser->getUserID() || $chat->user_id == 0 || $chat->status == erLhcoreClassModelChat::STATUS_CLOSED_CHAT) {
                $chat->support_informed = 1;
                $chat->has_unread_messages = 0;
                $chat->unread_messages_informed = 0;
            }

    	    if ($chat->unanswered_chat == 1 && ($chat->user_status_front == 0 || $chat->user_status_front == 2))
    	    {
    	        $chat->unanswered_chat = 0;
    	    }

            $chat->updateThis();

    	    $db->commit();

            $db->beginTransaction();

    	    session_write_close();

    	    if ($chatDataChanged == true) {
    	    	erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.data_changed',array('chat' => & $chat,'user' => $currentUser));
    	    }

    	    if ($operatorAccepted == true) {
    	    	erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.accept',array('chat' => & $chat,'user' => $currentUser));	    	
    	    	erLhcoreClassChat::updateActiveChats($chat->user_id);

                // Other operator accepts sometimes assigned chat manually so we have to update previous chat owner stats
                if ($previousUserId != $chat->user_id && $previousUserId > 0) {
                    erLhcoreClassChat::updateActiveChats($previousUserId);
                }

    	    	if ($chat->department !== false) {
    	    	    erLhcoreClassChat::updateDepartmentStats($chat->department);
    	    	}

                if ($chat->auto_responder !== false) {
                    $chat->auto_responder->chat = $chat;
                    $chat->auto_responder->processAccept();
                }

    	    	erLhcoreClassChatWorkflow::presendCannedMsg($chat);
    	    	$options = $chat->department->inform_options_array;
    	    	erLhcoreClassChatWorkflow::chatAcceptedWorkflow(array('department' => $chat->department,'options' => $options),$chat);

    	    	// Just update if some extension modified data and forgot to update.
                // Also this is solving strange issue after chat assignment it's assignment got reset.
                // So this should help if not we will need something more.
                $chat->updateThis();
    	    };
    	    $db->commit();
    	    
    	    $tpl->set('chat',$chat);
            $tpl->set('canEditChat',true);
            $tpl->set('see_sensitive_information',$see_sensitive_information);

    	    echo $tpl->fetch();
    	        	    
	    } catch (Exception $e) {
	        $db->rollback();
            $tpl->setFile( 'lhchat/errors/adminchatnopermission.tpl.php');
            $tpl->set('show_close_button',true);
            $tpl->set('auto_close_dialog',true);
            $tpl->set('chat_id',(int)$Params['user_parameters']['chat_id']);
            $tpl->set('chat',$chat);
            echo $tpl->fetch();
            exit;
	    }
	} else {
        $db->rollback();
	    $tpl->set('canEditChat',erLhcoreClassChat::hasAccessToWrite($chat));
	    $tpl->set('chat',$chat);
        $tpl->set('see_sensitive_information',$see_sensitive_information);
	    echo $tpl->fetch();
	}

    if (!$currentUser->hasAccessTo('lhaudit','ignore_view_actions')) {
        erLhcoreClassLog::write(0,
            ezcLog::SUCCESS_AUDIT,
            array(
                'source' => 'lhc',
                'category' => 'chat_open',
                'line' => __LINE__,
                'file' => __FILE__,
                'object_id' => $chat->id,
                'user_id' => $currentUser->getUserID()
            )
        );
    }
    exit;

} else {
    $tpl->setFile( 'lhchat/errors/adminchatnopermission.tpl.php');
    $tpl->set('show_close_button',true);
    $tpl->set('auto_close_dialog',true);
    $tpl->set('chat_id',(int)$Params['user_parameters']['chat_id']);
    echo $tpl->fetch();
    exit;
}



?>