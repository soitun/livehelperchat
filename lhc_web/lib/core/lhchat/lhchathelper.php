<?php

class erLhcoreClassChatHelper
{

    /**
     * Message for timeout
     */
    public static function redirectToContactForm($params)
    {
        $msg = new erLhcoreClassModelmsg();
        $msg->msg = (string) $params['user'] . ' ' . erTranslationClassLhTranslation::getInstance()->getTranslation('chat/closechatadmin', 'has redirected visitor to contact form!');
        $msg->chat_id = $params['chat']->id;
        $msg->user_id = - 1;
        
        $params['chat']->last_user_msg_time = $msg->time = time();
        erLhcoreClassChat::getSession()->save($msg);
        
        // Set last message ID
        if ($params['chat']->last_msg_id < $msg->id) {
            if ($params['chat']->status == erLhcoreClassModelChat::STATUS_PENDING_CHAT) {
                $params['chat']->status = erLhcoreClassModelChat::STATUS_ACTIVE_CHAT;
            }
            $params['chat']->last_msg_id = $msg->id;
        }
        
        if ($params['chat']->user_id == 0) {
            $params['chat']->user_id = $params['user']->id;
        }
        
        $params['chat']->support_informed = 1;
        $params['chat']->has_unread_messages = 0;

        if ($params['chat']->cls_us == 0) {
            $params['chat']->cls_us = $params['chat']->user_status_front + 1;
        }

        $params['chat']->status_sub = erLhcoreClassModelChat::STATUS_SUB_CONTACT_FORM;
        $params['chat']->updateThis();        
    }

    /**
     * Redirect user to survey form
     * */
    public static function redirectToSurvey($params)
    {
        $msg = new erLhcoreClassModelmsg();
        $msg->msg = (string) $params['user'] . ' ' . erTranslationClassLhTranslation::getInstance()->getTranslation('chat/closechatadmin', 'has redirected visitor to survey form!');
        $msg->chat_id = $params['chat']->id;
        $msg->user_id = - 1;
        
        $params['chat']->last_user_msg_time = $msg->time = time();
        erLhcoreClassChat::getSession()->save($msg);
        
        $surveyItem = erLhAbstractModelSurveyItem::findOne(array('filter' => array('chat_id' => $params['chat']->id)));
        
        // Make form temporary so user can fill a survey again
        if ($surveyItem instanceof erLhAbstractModelSurveyItem) {
            $surveyItem->status = erLhAbstractModelSurveyItem::STATUS_TEMP;
            $surveyItem->saveThis();
        }
        
        // Set last message ID
        if ($params['chat']->last_msg_id < $msg->id) {
            if ($params['chat']->status == erLhcoreClassModelChat::STATUS_PENDING_CHAT) {
                $params['chat']->status = erLhcoreClassModelChat::STATUS_ACTIVE_CHAT;
            }
            $params['chat']->last_msg_id = $msg->id;
        }
        
        if ($params['chat']->user_id == 0) {
            $params['chat']->user_id = $params['user']->id;
        }
        
        $params['chat']->support_informed = 1;
        $params['chat']->has_unread_messages = 0;
        
        // Store survey id
        if ( isset($params['survey_id']) ) {
            $subArg = $params['chat']->status_sub_arg;
            $argStore = array();

            if ($subArg != '') {
                $argStore = json_decode($subArg,true);
            }
            
            $argStore['survey_id'] = $params['survey_id'];
            
            $params['chat']->status_sub_arg = json_encode($argStore);            
        }

        if ($params['chat']->cls_us == 0) {
            $params['chat']->cls_us = $params['chat']->user_status_front + 1;
        }

        $params['chat']->status_sub = erLhcoreClassModelChat::STATUS_SUB_SURVEY_SHOW;
        $params['chat']->saveThis();

        if ($params['chat']->user_id > 0){
            erLhcoreClassChat::updateActiveChats($params['chat']->user_id);
        }
    }
    
    public static function getSubStatusArguments( $chat )
    {
        if ($chat->status_sub_arg != '') {
            $args = json_decode($chat->status_sub_arg, true);            
            reset($args);
            $string = array();

            foreach ($args as $key => $value) {
                $string [] = $key . ':' . $value ;
            }

	        return implode(':', $string);
        }
        
        return '';
    }
    
    public static function cleanupOnClose($chatId) {
        $db = ezcDbInstance::get();

        foreach ([
                     'lh_abstract_auto_responder_chat',
                     'lh_generic_bot_repeat_restrict',
                     'lh_generic_bot_chat_event',
                     'lh_generic_bot_pending_event',
                     'lh_chat_voice_video',
                 ] as $table) {
            $q = $db->createDeleteQuery();
            $q->deleteFrom($table)->where( $q->expr->eq( 'chat_id', $chatId ) );
            $stmt = $q->prepare();
            $stmt->execute();
        }

        $q = $db->createDeleteQuery();
        $q->deleteFrom("lh_transfer")->where(
            $q->expr->eq( 'chat_id', $chatId ),
            $q->expr->eq( 'transfer_scope', 0 )
        );
        $stmt = $q->prepare();
        $stmt->execute();

        // Close by support chat
        erLhcoreClassModelGroupChat::closeByChatId($chatId);

        // Remove temporary chat files
        foreach (erLhcoreClassModelChatFile::getList(['filter' => [
            'tmp' => 1, 
            'user_id' => 0, 
            'chat_id' => $chatId]]) as $file) {
            $file->removeThis();
        }
     
    }

    public static function closeChat($params)
    {
        if ($params['chat']->status != erLhcoreClassModelChat::STATUS_CLOSED_CHAT) {
            
            $db = ezcDbInstance::get();
            $db->beginTransaction();

                if ($params['chat']->cls_us == 0) {
                    $params['chat']->cls_us = $params['chat']->user_status_front + 1;
                }

                if (in_array($params['chat']->status,[erLhcoreClassModelChat::STATUS_ACTIVE_CHAT,erLhcoreClassModelChat::STATUS_BOT_CHAT]) && $params['chat']->auto_responder !== false) {
                    $params['chat']->auto_responder->chat = $params['chat'];
                    $params['chat']->auto_responder->processClose();
                }

                $params['chat']->status = erLhcoreClassModelChat::STATUS_CLOSED_CHAT;

                if ($params['chat']->wait_time == 0) {
                    $params['chat']->wait_time = time() - ($params['chat']->pnd_time > 0 ? $params['chat']->pnd_time : $params['chat']->time);
                }

                $params['chat']->cls_time = time();

                \LiveHelperChat\Helpers\ChatDuration::setChatTimes($params['chat']);

                $params['chat']->has_unread_messages = 0;
                $params['chat']->operation_admin = '';

                $msg = new erLhcoreClassModelmsg();
                $msg->chat_id = $params['chat']->id;
                $msg->user_id = - 1;

                $user_id = 0;
                if (!(isset($params['bot']) && $params['bot'] == true) && is_object($params['user'])) {
                    $msg->name_support = (string)$params['user']->name_support;
                    $user_id = $params['user']->id;
                }

                if (empty($msg->name_support)) {
                    $msg->name_support = erTranslationClassLhTranslation::getInstance()->getTranslation('chat/startchat','Live Support');
                }

                \LiveHelperChat\Models\Departments\UserDepAlias::getAlias(array('scope' => 'msg', 'msg' => & $msg, 'chat' => & $params['chat'], 'user_id' => $user_id));
                erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.before_msg_admin_saved', array('msg' => & $msg, 'chat' => & $params['chat'], 'user_id' => $user_id));

                $msg->msg = ((isset($params['bot']) && $params['bot'] == true) ? erTranslationClassLhTranslation::getInstance()->getTranslation('chat/closechatadmin', 'Bot') : (string) $msg->name_support) . ' ' . erTranslationClassLhTranslation::getInstance()->getTranslation('chat/closechatadmin', 'has closed the chat!');

                if (isset($params['append_message'])) {
                    $msg->msg .= $params['append_message'];
                }
                
                $params['chat']->last_user_msg_time = $msg->time = time();


                erLhcoreClassChat::getSession()->save($msg);

                $params['chat']->updateThis();

                self::cleanupOnClose($params['chat']->id);

            $db->commit();

            if (!isset($params['bot']) || $params['bot'] == false) {
                erLhcoreClassChat::updateActiveChats($params['chat']->user_id);
            }

            if ($params['chat']->department !== false) {
                erLhcoreClassChat::updateDepartmentStats($params['chat']->department);
            }
            
            // Execute callback for close chat
            erLhcoreClassChat::closeChatCallback($params['chat'], (isset($params['user']) ? $params['user'] : false));
        }
    }
    
    public static function changeStatus($params)
    {
        $changeStatus = $params['status'];
        $chat = $params['chat'];
        $userData = $params['user'];
        $allowCloseRemote = $params['allow_close_remote'];
                
        if ($changeStatus == erLhcoreClassModelChat::STATUS_ACTIVE_CHAT) {

            // If chat is transferred to pending state we don't want to process any old events
            erLhcoreClassGenericBotWorkflow::removePreviousEvents($chat->id);

            if ($chat->status != erLhcoreClassModelChat::STATUS_ACTIVE_CHAT) {
                if ($chat->status == erLhcoreClassModelChat::STATUS_BOT_CHAT) {
                    $chat->pnd_time = time();
                    $chat->wait_time = 1;
                } elseif ($chat->status == erLhcoreClassModelChat::STATUS_PENDING_CHAT) {
                    if ($chat->wait_time == 0) {
                        $chat->wait_time = time() - ($chat->pnd_time > 0 ? $chat->pnd_time : $chat->time);
                    }
                } else {
                    $chat->last_user_msg_time = time()-1;
                    $chat->last_op_msg_time = time();
                }
                $chat->status = erLhcoreClassModelChat::STATUS_ACTIVE_CHAT;
            }

            if ($chat->user_id == 0)
            {
                $chat->user_id = $userData->id;
            }

            $chat->updateThis();
             
        } elseif ($changeStatus == erLhcoreClassModelChat::STATUS_PENDING_CHAT) {

            // If chat is changed to pending reset assigned operator
            erLhcoreClassChat::updateActiveChats($chat->user_id);
            
            $chat->user_id = 0;
            $chat->status = erLhcoreClassModelChat::STATUS_PENDING_CHAT;
            $chat->support_informed = 0;
            $chat->has_unread_messages = 1;
            $chat->pnd_time = time();

            // Store system message
            $msg = new erLhcoreClassModelmsg();
            $msg->chat_id = $chat->id;
            $msg->user_id = -1;
            $chat->last_user_msg_time = $msg->time = time();
            $msg->name_support = $userData->name_support;

            \LiveHelperChat\Models\Departments\UserDepAlias::getAlias(array('scope' => 'msg', 'msg' => & $msg, 'chat' => & $chat, 'user_id' => $userData->id));
            erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.before_msg_admin_saved', array('msg' => & $msg, 'chat' => & $chat, 'user_id' => $userData->id));

            $msg->msg = (string)$msg->name_support.' '.erTranslationClassLhTranslation::getInstance()->getTranslation('chat/closechatadmin','has changed chat status to pending!');
            erLhcoreClassChat::getSession()->save($msg);

            $chat->last_msg_id = $msg->id;
            $chat->updateThis();
        
            
        } elseif ($changeStatus == erLhcoreClassModelChat::STATUS_CLOSED_CHAT && ($chat->user_id == $userData->id || $allowCloseRemote == true)) {
        
            if ($chat->status != erLhcoreClassModelChat::STATUS_CLOSED_CHAT) {
                $chat->status = erLhcoreClassModelChat::STATUS_CLOSED_CHAT;
                \LiveHelperChat\Helpers\ChatDuration::setChatTimes($chat);
                $chat->cls_time = time();

                $msg = new erLhcoreClassModelmsg();
                $msg->chat_id = $chat->id;
                $msg->user_id = -1;
                $chat->last_user_msg_time = $msg->time = time();
                $msg->name_support = $userData->name_support;

                \LiveHelperChat\Models\Departments\UserDepAlias::getAlias(array('scope' => 'msg', 'msg' => & $msg, 'chat' => & $chat, 'user_id' => $userData->id));
                erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.before_msg_admin_saved', array('msg' => & $msg, 'chat' => & $chat, 'user_id' => $userData->id));

                $msg->msg = (string)$msg->name_support.' '.erTranslationClassLhTranslation::getInstance()->getTranslation('chat/closechatadmin','has closed the chat!');

                erLhcoreClassChat::getSession()->save($msg);

                $chat->last_msg_id = $msg->id;
                $chat->updateThis();
        
                // Execute callback for close chat
                erLhcoreClassChat::closeChatCallback($chat,$userData);
            }
            	
        } elseif ($changeStatus == erLhcoreClassModelChat::STATUS_CHATBOX_CHAT) {
            $chat->status = erLhcoreClassModelChat::STATUS_CHATBOX_CHAT;
            $chat->updateThis(array('update' => array('status')));
        } elseif ($changeStatus == erLhcoreClassModelChat::STATUS_OPERATORS_CHAT) {
            $chat->status = erLhcoreClassModelChat::STATUS_OPERATORS_CHAT;
            $chat->updateThis(array('update' => array('status')));
        } elseif ($changeStatus == erLhcoreClassModelChat::STATUS_BOT_CHAT) {
            // If chat is changed to pending reset assigned operator
            erLhcoreClassChat::updateActiveChats($chat->user_id);

            $chat->user_id = 0;
            $chat->status = erLhcoreClassModelChat::STATUS_BOT_CHAT;

            // Store system message
            $msg = new erLhcoreClassModelmsg();
            $msg->chat_id = $chat->id;
            $msg->user_id = -1;
            $chat->last_user_msg_time = $msg->time = time();
            $msg->name_support = $userData->name_support;

            \LiveHelperChat\Models\Departments\UserDepAlias::getAlias(array('scope' => 'msg', 'msg' => & $msg, 'chat' => & $chat, 'user_id' => $userData->id));
            erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.before_msg_admin_saved', array('msg' => & $msg, 'chat' => & $chat, 'user_id' => $userData->id));

            $msg->msg = (string)$msg->name_support.' '.erTranslationClassLhTranslation::getInstance()->getTranslation('chat/closechatadmin','has changed chat status to bot!');
            erLhcoreClassChat::getSession()->save($msg);
            $chat->last_msg_id = $msg->id;

            $chat->updateThis(array('update' => array('status','user_id','last_user_msg_time','last_msg_id')));
        }
        
        erLhcoreClassChat::updateActiveChats($chat->user_id);
         
        if ($chat->department !== false) {
            erLhcoreClassChat::updateDepartmentStats($chat->department);
        }
    }
    
    /**
     * 
     * Converts old online visitor data to new online visitor data
     * 
     * @param array $data
     * 
     * @throws Exception
     */
    public static function mergeVid($data, $background = false)
    {

    	if (!isset($data['vid'])) {
    		throw new Exception('Old vid not provided');
    	}

    	if (!isset($data['new'])) {
    		throw new Exception('New vid not provided');
    	}

        if ($background == false && class_exists('erLhcoreClassExtensionLhcphpresque')) {
            $inst_id = class_exists('erLhcoreClassInstance') ? \erLhcoreClassInstance::$instanceChat->id : 0;
            erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionLhcphpresque')->enqueue('lhc_rest_webhook', 'erLhcoreClassChatWebhookResque', array('inst_id' => $inst_id, 'event_type' => 'merge_vid', 'old_vid' => $data['vid'], 'new_vid' => $data['new']));
            return;
        }

    	$old = erLhcoreClassModelChatOnlineUser::fetchByVid($data['vid']);
    	$new = erLhcoreClassModelChatOnlineUser::fetchByVid($data['new']);

    	if ($old === false) {
    	    throw new Exception('Invalid VID value');
    	}

    	if ($new === false && $old !== false) {
    		// If new record not found just update old vid to new vid hash
    		$old->vid = $data['new'];
    		$old->saveThis();
    	} else if ($new !== false && $old !== false) {
    		$db = ezcDbInstance::get();

    		$stmt = $db->prepare('UPDATE lh_chat_online_user_footprint SET online_user_id = :new_online_user_id WHERE online_user_id = :old_online_user_id');
    		$stmt->bindValue(':new_online_user_id',$new->id,PDO::PARAM_INT);
    		$stmt->bindValue(':old_online_user_id',$old->id,PDO::PARAM_INT);
    		$stmt->execute();

    		$stmt = $db->prepare('UPDATE lh_chat SET online_user_id = :new_online_user_id WHERE online_user_id = :old_online_user_id');
    		$stmt->bindValue(':new_online_user_id',$new->id,PDO::PARAM_INT);
    		$stmt->bindValue(':old_online_user_id',$old->id,PDO::PARAM_INT);
    		$stmt->execute();

    		$stmt = $db->prepare('UPDATE lh_cobrowse SET online_user_id = :new_online_user_id WHERE online_user_id = :old_online_user_id');
    		$stmt->bindValue(':new_online_user_id',$new->id,PDO::PARAM_INT);
    		$stmt->bindValue(':old_online_user_id',$old->id,PDO::PARAM_INT);
    		$stmt->execute();

    		$stmt = $db->prepare('UPDATE lh_chat_file SET online_user_id = :new_online_user_id WHERE online_user_id = :old_online_user_id');
    		$stmt->bindValue(':new_online_user_id',$new->id,PDO::PARAM_INT);
    		$stmt->bindValue(':old_online_user_id',$old->id,PDO::PARAM_INT);
    		$stmt->execute();
    		
    		// count pages count to new
    		$new->pages_count += $old->pages_count;
    		$new->tt_pages_count += $old->tt_pages_count;
    		$new->first_visit = $old->first_visit;
    		$new->notes = $new->notes . trim("\n" . $old->notes);
    		$new->total_visits += $old->total_visits;
    		$new->invitation_count += $old->invitation_count;
    		$new->time_on_site += $old->time_on_site;
    		$new->tt_time_on_site += $old->tt_time_on_site;
    		$new->referrer = $old->referrer;
    		
    		$new->saveThis();
    		    		
    		$old->removeThis();
    	}
    }
}

?>