<?php foreach ($messages as $msg ) : ?>
<div class="message-row<?php echo $msg['user_id'] == 0 ? ' response' : ''?>"><div class="msg-date"><?php echo date('Y-m-d H:i:s',$msg['time']);?></div><span class="usr-tit"><?php echo $msg['user_id'] == 0 ? htmlspecialchars($chat->nick) : $msg['name_support'] ?>:</span> <?php echo nl2br(htmlspecialchars(trim($msg['msg'])));?></div>
<?php endforeach; ?>