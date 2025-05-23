<?php


erLhcoreClassRestAPIHandler::setHeaders();

if (!empty($_GET) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $payload = $_GET;
} else {
    $payload = json_decode(file_get_contents('php://input'),true);
}

if (isset($payload['vid_id']) && is_numeric($payload['vid_id'])) {
    $onlineUser = erLhcoreClassModelChatOnlineUser::fetch($payload['vid_id']);
} else {
    $onlineUser = erLhcoreClassModelChatOnlineUser::fetchByVid($payload['vid']);
}

if (!($onlineUser instanceof erLhcoreClassModelChatOnlineUser) || $onlineUser->vid != $payload['vid']) {
    erLhcoreClassRestAPIHandler::outputResponse(array('status' => false));
    exit;
}

if (isset($payload['invitation']) && is_numeric($payload['invitation']) && $payload['invitation'] > 0) {
    $invitationData = erLhAbstractModelProactiveChatInvitation::setInvitation($onlineUser, (int)$payload['invitation']);
}

// Make conversion if any exists
if ($onlineUser->conversion_id > 0) {
    $conversionUser = erLhAbstractModelProactiveChatCampaignConversion::fetch($onlineUser->conversion_id);
    if ($conversionUser instanceof erLhAbstractModelProactiveChatCampaignConversion && $conversionUser->invitation_status != erLhAbstractModelProactiveChatCampaignConversion::INV_SHOWN) {
        $conversionUser->invitation_status = erLhAbstractModelProactiveChatCampaignConversion::INV_SHOWN;
        $conversionUser->con_time = time();
        $conversionUser->saveThis();
    }
}

$outputResponse = array('status' => true);

if (($user = $onlineUser->operator_user) !== false) {

    $outputResponse['invitation_name'] = $outputResponse['name_support'] = $user->name_support;
    $outputResponse['extra_profile'] = $user->job_title != '' ? htmlspecialchars($user->job_title) : erTranslationClassLhTranslation::getInstance()->getTranslation('chat/startchat','Personal assistant');

    if (isset($onlineUser->online_attr_system_array['lhc_full_widget'])) {
        $outputResponse['full_widget'] = 1;
    }

    if ($user->has_photo) {
        $outputResponse['photo'] = $user->photo_path;
        $outputResponse['photo_title'] = $user->name_support;
    }

} else {
    $outputResponse['name_support'] = $onlineUser->operator_user !== false ? htmlspecialchars($onlineUser->operator_user->name_support) : (!empty($onlineUser->operator_user_proactive) ? htmlspecialchars($onlineUser->operator_user_proactive) : erTranslationClassLhTranslation::getInstance()->getTranslation('chat/startchat','Live Support'));
}

if (isset($onlineUser->online_attr_system_array['lhcinv_exp'])) {
    $outputResponse['inv_expires'] = $onlineUser->online_attr_system_array['lhcinv_exp'] - time();
}

$outputResponse['invitation_id'] = $onlineUser->invitation_id;

$outputResponse['qinv'] = isset($onlineUser->online_attr_system_array['qinv']);

$outputResponse['message'] = erLhcoreClassBBCode::make_clickable(htmlspecialchars($onlineUser->operator_message_front));

$outputResponse['play_sound'] = erLhcoreClassModelChatConfig::fetch('sound_invitation')->current_value == 1;

$outputResponse['bubble'] = false;

if (isset($payload['theme']) && ($themeId = erLhcoreClassChat::extractTheme($payload['theme'])) !== false) {

    $theme = erLhAbstractModelWidgetTheme::fetch($themeId);

    if ($theme instanceof erLhAbstractModelWidgetTheme)
    {
        $theme->translate();

        if (isset($theme->bot_configuration_array['bubble_style_profile']) && $theme->bot_configuration_array['bubble_style_profile'] == 1) {
            $outputResponse['bubble'] = true;
        }

        if (!isset($outputResponse['photo']) && $theme->operator_image_url !== false) {
            $outputResponse['photo'] = $theme->operator_image_url;
        }

        if ($theme->intro_operator_text != '') {
            $outputResponse['extra_profile'] = $theme->intro_operator_text;
        }

    }
}

// Bot message as full widget body
if ($outputResponse['invitation_id'] > 0) {

    $invitation = isset($invitationData['variation']) ? $invitationData['variation'] : null;

    if ($invitation instanceof erLhAbstractModelProactiveChatInvitation && isset($invitation->design_data_array['append_bot']) && $invitation->design_data_array['append_bot'] == 1 && $invitation->bot_id > 0 && $invitation->trigger_id > 0) {

        if ($onlineUser->has_message_from_operator == false) {
            $outputResponse['qinv'] = true;
        }

        $bot = erLhcoreClassModelGenericBotBot::fetch($invitation->bot_id);

        if ($bot instanceof erLhcoreClassModelGenericBotBot)
        {
            if ($bot->has_photo) {
                $outputResponse['photo'] = $bot->photo_path;
            }

            $outputResponse['name_support'] = $bot->nick;
        }

        $tpl = new erLhcoreClassTemplate('lhchat/part/render_intro.tpl.php');

        if (isset($theme)) {
            $tpl->set('theme',$theme);
        }

        $chat = new erLhcoreClassModelChat();
        $chat->bot = $bot;
        $chat->gbot_id = $bot->id;
        $chat->additional_data_array = $onlineUser->online_attr_array;
        $chat->chat_variables_array = $onlineUser->chat_variables_array;
        if ($onlineUser->dep_id > 0) {
            $chat->dep_id = $onlineUser->dep_id;
        }
        $tpl->set('chat',$chat);
        $tpl->set('react',true);
        $tpl->set('no_wrap_intro',true);
        $tpl->set('no_br',true);
        $tpl->set('triggerMessageId',$invitation->trigger_id);
        $tpl->set('additionalDataArray', $onlineUser->online_attr_array );
        $tpl->set('variablesDataArray', $onlineUser->chat_variables_array );

        $outputResponse['message_full'] = trim($tpl->fetch());

        if (isset($invitation->design_data_array['append_intro_bot']) && $invitation->design_data_array['append_intro_bot'] == 1) {
            $outputResponse['bot_intro'] = true;
        }
    }

    if ($invitation instanceof erLhAbstractModelProactiveChatInvitation) {

        if (isset($invitation->design_data_array['close_above_msg']) && $invitation->design_data_array['close_above_msg'] == 1) {
            $outputResponse['close_above_msg'] = true;
        }

        if (isset($invitation->design_data_array['hide_on_open']) && $invitation->design_data_array['hide_on_open'] == true) {
            $outputResponse['hide_on_open'] = true;
        }

        if (isset($invitation->design_data_array['custom_on_click']) && $invitation->design_data_array['custom_on_click'] != '') {
            $outputResponse['on_click'] = [
                'src' => erLhcoreClassSystem::getHost() . erLhcoreClassDesign::baseurldirect('widgetrestapi/proactiveonclick') .'/' . $invitation->id . '?ts='. md5($invitation->design_data_array['custom_on_click']),
                'id' => md5($invitation->design_data_array['custom_on_click'])
            ];
        }

        if (isset($invitation->design_data_array['full_on_invitation']) && $invitation->design_data_array['full_on_invitation'] == true) {
            $outputResponse['full_widget'] = true;
        }

        if (isset($invitation->design_data_array['photo_left_column']) && $invitation->design_data_array['photo_left_column'] == true) {
            $outputResponse['photo_left_column'] = true;
        }
        
        if (isset($invitation->design_data_array['hide_op_name']) && $invitation->design_data_array['hide_op_name'] == true) {
            $outputResponse['hide_op_name'] = true;
        }

        if (isset($invitation->design_data_array['hide_op_img']) && $invitation->design_data_array['hide_op_img'] == true && isset($outputResponse['photo'])) {
            unset($outputResponse['photo']);
        }

        if (isset($invitation->design_data_array['message_width']) && is_numeric($invitation->design_data_array['message_width']) && $invitation->design_data_array['message_width'] > 0) {
            $outputResponse['message_width'] = (int)$invitation->design_data_array['message_width'];
        }

        if (isset($invitation->design_data_array['message_bottom']) && is_numeric($invitation->design_data_array['message_bottom']) && $invitation->design_data_array['message_bottom'] > 0) {
            $outputResponse['message_bottom'] = (int)$invitation->design_data_array['message_bottom'];
        }

        if (isset($invitation->design_data_array['message_right']) && is_numeric($invitation->design_data_array['message_right']) && $invitation->design_data_array['message_right'] > 0) {
            $outputResponse['message_right'] = (int)$invitation->design_data_array['message_right'];
        }

        if (isset($invitation->design_data_array['std_header']) && $invitation->design_data_array['std_header'] == true) {
            $outputResponse['std_header'] = true;
        }

        if (isset($invitation->design_data_array['play_sound']) && $invitation->design_data_array['play_sound'] == true) {
            $outputResponse['play_sound'] = true;
        } else {
            $outputResponse['play_sound'] = false;
        }

        // Replace images
        if (strpos($outputResponse['message'],'{proactive_img_') !== false) {
            $replaceStyleArray = [];
            for ($i = 1; $i < 5; $i++) {
                $replaceStyleArray['{proactive_img_' . $i . '}'] = erLhcoreClassSystem::getHost() . $invitation->{'design_data_img_' . $i . '_url'};
            }
            $outputResponse['message'] = str_replace(array_keys($replaceStyleArray), array_values($replaceStyleArray), $outputResponse['message']);
        }

        if (isset($invitation->design_data_array['mobile_style']) && $invitation->design_data_array['mobile_style'] != '') {
            $replaceStyleArray = array();
            for ($i = 1; $i < 5; $i++) {
                $replaceStyleArray['{proactive_img_' . $i . '}'] = erLhcoreClassSystem::getHost() . $invitation->{'design_data_img_' . $i . '_url'};
            }
            $contentCSS = str_replace(array_keys($replaceStyleArray), array_values($replaceStyleArray), $invitation->design_data_array['mobile_style']);
            $contentCSS = str_replace(array("\n", "\r"), '', $contentCSS);
            $outputResponse['site_css'] = $contentCSS;
            $outputResponse['site_css_id'] = md5($contentCSS);
        }

        $outputResponse['invitation_name'] = $invitation->name;
    }

} else if (isset($onlineUser->online_attr_system_array['lhc_start_chat']) && $onlineUser->chat_id > 0) {

    $onlineAttrSystem = $onlineUser->online_attr_system_array;
    unset($onlineAttrSystem['lhc_start_chat']);

    $onlineUser->online_attr_system_array = $onlineAttrSystem;
    $onlineUser->online_attr_system = json_encode($onlineAttrSystem);
    $onlineUser->updateThis(array('update' => array('online_attr_system')));

    $chat = $onlineUser->chat;
    if ($chat instanceof erLhcoreClassModelChat && in_array($chat->status,array(erLhcoreClassModelChat::STATUS_ACTIVE_CHAT,erLhcoreClassModelChat::STATUS_PENDING_CHAT))) {
        $outputResponse['chat_id'] = $onlineUser->chat_id;
        $outputResponse['chat_hash'] = $onlineUser->chat->hash;
        $message_id_first = (int)erLhcoreClassModelmsg::getCount(['limit' => 1, 'sort' => 'id ASC', 'filter' => ['user_id' => 0, 'chat_id' => $onlineUser->chat_id]],'count','id','id');
        if ($message_id_first == 0) { // Sometimes action can be a button click without saving visitor message, in this scenario there is no visitor message
            $message_id_first = (int)erLhcoreClassModelmsg::getCount(['limit' => 1, 'offset' => 1, 'sort' => 'id ASC', 'filter' => ['chat_id' => $onlineUser->chat_id]],'count','id','id');
        }
        $outputResponse['message_id_first'] = $message_id_first;
    }
}

// Show previous messages for invitation also
if (isset($theme) && isset($theme->bot_configuration_array['prev_msg']) && $theme->bot_configuration_array['prev_msg'] == true) {
    $previousChat = erLhcoreClassModelChat::findOne(array('sort' => 'id DESC', 'limit' => 1, 'filter' => array('online_user_id' => $onlineUser->id)));

    if ($previousChat instanceof erLhcoreClassModelChat) {

        if ($previousChat->has_unread_op_messages == 1) {
            $previousChat->unread_op_messages_informed = 0;
            $previousChat->has_unread_op_messages = 0;
            $previousChat->unanswered_chat = 0;
            $previousChat->updateThis(array('update' => array('unread_op_messages_informed','has_unread_op_messages','unanswered_chat')));
        }

        $tpl = erLhcoreClassTemplate::getInstance( 'lhchat/previous_chat.tpl.php');
        $tpl->set('messages', erLhcoreClassChat::getPendingMessages((int)$previousChat->id,  0));
        $tpl->set('chat',$previousChat);
        $tpl->set('sync_mode','');
        $tpl->set('async_call',true);
        $tpl->set('theme',$theme);
        $tpl->set('react',true);
        $outputResponse['prev_msg'] = $tpl->fetch();
    }
}


if (
    strpos($outputResponse['message'],'{operator}') !== false ||
    strpos($outputResponse['message'],'{operator_name}') !== false
) {
    $outputResponse['message'] = str_replace('{operator}',$outputResponse['name_support'], $outputResponse['message']);

    // Update operator message so once chat is started it will have correct message.
    $onlineUser->operator_message = str_replace('{operator}', $outputResponse['name_support'], $onlineUser->operator_message);

    if ($onlineUser->operator_user !== false) {
        $outputResponse['message'] = str_replace('{operator_name}', $onlineUser->operator_user->name, $outputResponse['message']);
        $onlineUser->operator_message = str_replace('{operator_name}', $onlineUser->operator_user->name, $onlineUser->operator_message);
    } else {
        $outputResponse['message'] = str_replace('{operator_name}', '', $outputResponse['message']);
        $onlineUser->operator_message = str_replace('{operator_name}', '', $onlineUser->operator_message);
    }

    $onlineUser->updateThis(['update' => ['operator_message']]);
}




if (!isset($outputResponse['invitation_name'])) {
    $outputResponse['invitation_name'] = 'Manual';
}

erLhcoreClassChatEventDispatcher::getInstance()->dispatch('widgetrestapi.getinvitation',array('output' => & $outputResponse, 'ou' => $onlineUser, 'theme' => (isset($theme) ? $theme : null)));

erLhcoreClassRestAPIHandler::outputResponse($outputResponse);
exit;
?>