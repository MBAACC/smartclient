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
	// delete 
	if($cur_instr_name == 'sqimap_msgs_list_delete'){
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



