<?php

/**
 * sync.php
 *
 */

/** This is the sync page */
define('PAGE_NAME', 'sync');

/* Path for SquirrelMail required files. */
define('SM_PATH','../');

echo "hello<br /><br />";

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/global.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'class/Instr.class.php');
require_once(SM_PATH . 'functions/fetch_all_message.php');

global $compose_new_win;

if ( !sqgetGlobalVar('composesession', $composesession, SQ_SESSION) ) {
  $composesession = 0;
}


/* get globals */

sqgetGlobalVar('key',       $key,           SQ_COOKIE);
sqgetGlobalVar('username',  $username,      SQ_SESSION);
sqgetGlobalVar('onetimepad',$onetimepad,    SQ_SESSION);
sqgetGlobalVar('delimiter', $delimiter,     SQ_SESSION);
sqgetGlobalVar('base_uri',  $base_uri,      SQ_SESSION);

sqgetGlobalVar('mailbox', $mailbox);
sqgetGlobalVar('startMessage', $startMessage);
sqgetGlobalVar('msg', $msg);

sqgetGlobalVar('msgs',              $msgs,              SQ_SESSION);
sqgetGlobalVar('composesession',    $composesession,    SQ_SESSION);
sqgetGlobalVar('lastTargetMailbox', $lastTargetMailbox, SQ_SESSION);

sqgetGlobalVar('moveButton',      $moveButton,      SQ_POST);
sqgetGlobalVar('expungeButton',   $expungeButton,   SQ_POST);
sqgetGlobalVar('targetMailbox',   $targetMailbox,   SQ_POST);
sqgetGlobalVar('expungeButton',   $expungeButton,   SQ_POST);
sqgetGlobalVar('undeleteButton',  $undeleteButton,  SQ_POST);
sqgetGlobalVar('markRead',        $markRead,        SQ_POST);
sqgetGlobalVar('markUnread',      $markUnread,      SQ_POST);
sqgetGlobalVar('attache',         $attache,         SQ_POST);
sqgetGlobalVar('location',        $location,        SQ_POST);

//if (!sqgetGlobalVar('smtoken',$submitted_token, SQ_POST)) {
//    $submitted_token = '';
//}
/* end of get globals */

// security check
//sm_validate_security_token($submitted_token, 3600, TRUE);


/* 
 * read local_data/async/instr
 */
 
// array of instrs stored in local_data/async/instr
$instrs = array();
// read & write
$file_path = SM_PATH . "local_data/async/instr";
$fp = fopen($file_path, "r+");
// shared lock
if(flock($fp, LOCK_EX)){
	$tmpStr = '';
	$curLine = '';
	// read each instr
	while(!feof($fp)){
		// read cur instr
		while(!feof($fp)){
			$curLine = fgets($fp);
			// "\n"
			if(strlen($curLine) == 1) {
				break;
			}

			$tmpStr = $tmpStr . $curLine; 
		}
		// not EOF
		if(strlen($curLine) != 0){
			// append
			$instrs[] = unserialize($tmpStr);
		}
		// reset $tmpStr 
		$tmpStr = '';
	}

	// clear instr file
	file_put_contents($file_path, '');	

	// release lock
	flock($fp, LOCK_UN);
}
// close file
fclose($fp);

/*  test
echo "<br />test<br /><br />";
print_r($instrs);
$cnt = count($instrs);
for($i=0; $i<$cnt; $i++){
	echo "$i:<br />";
	echo $instrs[$i]->instr_name;
	print_r($instrs[$i]->param_list);
	echo "<br /><br />";
}
*/


/*
 * Execute each instr
 */
$cnt = count($instrs);
for($i=0; $i<$cnt; $i++){
	$cur_instr = $instrs[$i];
	$cur_instr_name = $cur_instr->instr_name;
    // Nex 130601
    // send
    if ($cur_instr_name == 'syncDeliverMessage') {
        syncDeliverMessage($cur_instr->param_list[0], $cur_instr->param_list[1], $cur_instr->param_list[2], $cur_instr->param_list[3], $cur_instr->param_list[4], $cur_instr->param_list[5], $cur_instr->param_list[6], $cur_instr->param_list[7]);
    }
    // Nex
	// delete 
	elseif($cur_instr_name == 'sqimap_msgs_list_delete'){
		// params
		$mailbox = $cur_instr->param_list[0];
		$id = $cur_instr->param_list[1];
		if(count($id)){
			// connect
			$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
			$mbx_response = sqimap_mailbox_select($imapConnection, $mailbox);
			// call
			$cur_instr_name($imapConnection, $mailbox, $id);
			// logout
			sqimap_logout($imapConnection);
		}
	}
	// expunge
	else if($cur_instr_name == 'sqimap_mailbox_expunge'){
		// params
		$mailbox = $cur_instr->param_list[0];
		$handle_errors = $cur_instr->param_list[1];
		$id = $cur_instr->param_list[2];
		if(count($id)){
			// connect
			$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
			$mbx_response = sqimap_mailbox_select($imapConnection, $mailbox);
			// call
			$cur_instr_name($imapConnection, $mailbox, $handle_errors, $id);
			// logout
			sqimap_logout($imapConnection);
		}
	}
	// toggle flag
	else if($cur_instr_name == 'sqimap_toggle_flag'){
		// params
		$id = $cur_instr->param_list[0];
		$flag = $cur_instr->param_list[1];
		$set = $cur_instr->param_list[2];
		$handle_errors = $cur_instr->param_list[3];
		$mailbox = $cur_instr->param_list[4];
		if(count($id)){
			// connect
			$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
			$mbx_response=sqimap_mailbox_select($imapConnection, $mailbox);
			// call
			$cur_instr_name($imapConnection, $id, $flag, $set, $handle_errors);
			// logout
			sqimap_logout($imapConnection);
		}
	}
	// other cases 
	// ...


	/* for test */
	//write_back($instrs, 1);
	//break;
}



/**
 * write back un-executed instrs to 'async/instr'
 * 
 * need to write: $unexe_instrs[$idx ... count($exe_instrs)-1]
 */
function write_back($unexe_instrs, $idx){
	$fp = fopen(SM_PATH . "local_data/async/instr", "r+");
	// excusive lock
	if(flock($fp, LOCK_EX)){
		// first read things in the file
		$ori_str = '';
		while(!feof($fp)){
			$ori_str = $ori_str . fgets($fp);
		}
		
		// reset file point
		rewind($fp);

		// write back each instr 
		$cnt = count($unexe_instrs);
		for($i=$idx; $i<$cnt; $i++){
			$cur_instr = $unexe_instrs[$i];
			fwrite($fp, serialize($cur_instr)."\n\n");	
		}
		// write back $ori_str
		fwrite($fp, $ori_str);

		fflush($fp);
		// release lock
		flock($fp, LOCK_UN);
	}
	// close file
	fclose($fp);
}


/**
 * return respense to browser, then exit
 *
 */
function ret_exit(){
	die("done");
}

// Nex 130601
function syncDeliverMessage(&$composeMessage, $draft, $action, $passed_id, $passed_ent_id, $useSendmail, $domain, $mailbox) {
    global $username, $data_dir, $color, $default_move_to_sent, $move_to_sent;
    global $imapServerAddress, $imapPort, $sent_folder, $key;

    if ($action == 'reply' || $action == 'reply_all') {
        $reply_id = $passed_id;
        $reply_ent_id = $passed_ent_id;
    } else {
        $reply_id = '';
        $reply_ent_id = '';
    }

    /* Here you can modify the message structure just before we hand
       it over to deliver */
    $hookReturn = do_hook('compose_send', $composeMessage);
    /* Get any changes made by plugins to $composeMessage. */
    if ( is_object($hookReturn[1]) ) {
        $composeMessage = $hookReturn[1];
    }

    if (!$useSendmail && !$draft) {
        require_once(SM_PATH . 'class/deliver/Deliver_SMTP.class.php');
        $deliver = new Deliver_SMTP();
        global $smtpServerAddress, $smtpPort, $pop_before_smtp, $pop_before_smtp_host;

        $authPop = (isset($pop_before_smtp) && $pop_before_smtp) ? true : false;

        $user = '';
        $pass = '';
        if (empty($pop_before_smtp_host))
            $pop_before_smtp_host = $smtpServerAddress;

        get_smtp_user($user, $pass);

        $stream = $deliver->initStream($composeMessage,$domain,0,
            $smtpServerAddress, $smtpPort, $user, $pass, $authPop, $pop_before_smtp_host);
    } elseif (!$draft) {
        require_once(SM_PATH . 'class/deliver/Deliver_SendMail.class.php');
        global $sendmail_path, $sendmail_args;
        // Check for outdated configuration
        if (!isset($sendmail_args)) {
            if ($sendmail_path=='/var/qmail/bin/qmail-inject') {
                $sendmail_args = '';
            } else {
                $sendmail_args = '-i -t';
            }
        }
        $deliver = new Deliver_SendMail(array('sendmail_args'=>$sendmail_args));
        $stream = $deliver->initStream($composeMessage,$sendmail_path);
    } elseif ($draft) {
        global $draft_folder;
        $imap_stream = sqimap_login($username, $key, $imapServerAddress,
            $imapPort, 0);
        if (sqimap_mailbox_exists ($imap_stream, $draft_folder)) {
            require_once(SM_PATH . 'class/deliver/Deliver_IMAP.class.php');
            $imap_deliver = new Deliver_IMAP();
            $succes = $imap_deliver->mail($composeMessage, $imap_stream, $reply_id, $reply_ent_id, $imap_stream, $draft_folder);
            sqimap_logout($imap_stream);
            unset ($imap_deliver);
            $composeMessage->purgeAttachments();
            return $succes;
        } else {
            $msg  = '<br />'.sprintf(_("Error: Draft folder %s does not exist."),
                htmlspecialchars($draft_folder));
            plain_error_message($msg, $color);
            return false;
        }
    }
    $succes = false;
    if ($stream) {
        $deliver->mail($composeMessage, $stream, $reply_id, $reply_ent_id);
        $succes = $deliver->finalizeStream($stream);
    }
    if (!$succes) {
        $msg  = _("Message not sent.") .' '.  _("Server replied:") .
            "\n<blockquote>\n" . $deliver->dlv_msg . '<br />' .
            $deliver->dlv_ret_nr . ' ' .
            $deliver->dlv_server_msg . "</blockquote>\n\n";
        plain_error_message($msg, $color);
    } else {
        unset ($deliver);
        $imap_stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);


        // mark original message as having been replied to if applicable
        if ($action == 'reply' || $action == 'reply_all') {
            sqimap_mailbox_select ($imap_stream, $mailbox);
            sqimap_messages_flag ($imap_stream, $passed_id, $passed_id, 'Answered', false);
        }


        // copy message to sent folder
        $move_to_sent = getPref($data_dir,$username,'move_to_sent');
        if (isset($default_move_to_sent) && ($default_move_to_sent != 0)) {
            $svr_allow_sent = true;
        } else {
            $svr_allow_sent = false;
        }

        if (isset($sent_folder) && (($sent_folder != '') || ($sent_folder != 'none'))
            && sqimap_mailbox_exists( $imap_stream, $sent_folder)) {
            $fld_sent = true;
        } else {
            $fld_sent = false;
        }

        if ((isset($move_to_sent) && ($move_to_sent != 0)) || (!isset($move_to_sent))) {
            $lcl_allow_sent = true;
        } else {
            $lcl_allow_sent = false;
        }

        if (($fld_sent && $svr_allow_sent && !$lcl_allow_sent) || ($fld_sent && $lcl_allow_sent)) {
            require_once(SM_PATH . 'class/deliver/Deliver_IMAP.class.php');
            $imap_deliver = new Deliver_IMAP();
            $imap_deliver->mail($composeMessage, $imap_stream, $reply_id, $reply_ent_id, $imap_stream, $sent_folder);
            unset ($imap_deliver);
        }
        $composeMessage->purgeAttachments();
        sqimap_logout($imap_stream);
    }
    return $succes;
}

