<?php
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'functions/mailbox_display.php');


function fetch_message_to_local($imap_stream) {
	$boxes = sqimap_mailbox_list($imap_stream);
	for($i=0; $i < count($boxes); $i++) {
		//find mailbox name
		$mailbox = $boxes[$i]['unformatted'];
		//if(preg_match('/HasChildren/', $boxes[$i]['raw'])) continue;
		if($mailbox != "INBOX") continue;
		$numMessages = sqimap_get_num_messages($imap_stream, $mailbox);
		if(!$numMessages) continue;
		$startMessage = 1;
		$mbxresponse = sqimap_mailbox_select($imap_stream, $mailbox);
		//get msg array
		$msgs = getSelfSortMessages($imap_stream, $startMessage, $show_num, $numMessages, $sort, $mbxresponse);
		foreach($msgs as $msg) {
			//find uid
			$uid = $msg['ID'];
			//find message object
			$message = sqimap_get_message($imap_stream, $uid, $mailbox);
			$ent_ar = $message->findDisplayEntity(array(), array('text/plain'));
			$wrap_at = getPref( $data_dir, $username, 'wrap_at', 86 );
			if ($wrap_at < 15) {
				$wrap_at = 15;
			}
			//text of email
			$messagebody = '';
			for ($j = 0; $j < count($ent_ar); $j++) {
				$messagebody .= formatBody($imap_stream, $message, $color, $wrap_at, $ent_ar[$i], $uid, $mailbox);
				if ($j != count($ent_ar)-1) {
					$messagebody .= '<hr noshade size=1>';
				}
			}
			//write to file
			$inbox_file = "../local_data/inbox/$uid";
			$fp = fopen("$inbox_file", "w+");
			if(flock($fp, LOCK_EX)){
				fwrite($fp, serialize($message)."\n\n");
				fwrite($fp, $messagebody."\n\n");
				fflush($fp);
				flock($fp, LOCK_UN);
			}
			fclose($fp);
		}
	}
}

function fetch_message_object($file_name, $delimiter = "\n") {
	$fp = fopen("$file_name", "r");
	$msg_obj = null;
	if(flock($fp, LOCK_EX)) {
		$buffer = stream_get_line($fp, 4096, $delimiter);
		$msg_obj = unserialize($buffer);
		fflush($fp);
		flock($fp, LOCK_UN);
	}
	fclose($fp);
	return $msg_obj;
}

function fetch_message_body($file_name, $delimiter = "\n") {
	$fp = fopen("$file_name", "r");
	$body = "";
	$line = 0;
	if(flock($fp, LOCK_EX)) {
		while(!feof($fp)) {
			$line = $line + 1;
			$buffer = stream_get_line($fp, 4096, $delimiter);
			if($line < 3) continue;
			$body = $body . $buffer . "\n";
		}
		fflush($fp);
		flock($fp, LOCK_UN);	
	}
	return $body;
}
