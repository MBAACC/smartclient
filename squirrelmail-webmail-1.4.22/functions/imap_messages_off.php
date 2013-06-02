<?php


if (! defined('SM_PATH')) define('SM_PATH','../');
include_once(SM_PATH . 'class/Instr.class.php');
include_once(SM_PATH . 'functions/more_constants_off.php');

/**
 * imap_messages_off.php
 *
 * offline version of functions in functions/imap_messages.php
 *
 */

function sqimap_toggle_flag_off($id, $flag, $set, $handle_errors, $mailbox/*, $newinstr*/) {
	// add new instr 
    $newinstr = new Instr();
    $newinstr->instr_name = 'sqimap_toggle_flag';
	array_push($newinstr->param_list, $id, $flag, $set, $handle_errors, $mailbox);
	if(!$newinstr->append()){
		return false;	 	
	}
	
	// process local data
	global $path_off;
	$mailbox_path = $path_off[$mailbox];
	$ret = true;
	foreach($id as $cur_id){
		// file path
		$src_file_path = $mailbox_path . $cur_id;
		
		// check src file existing
		if(!file_exists($src_file_path)){
			echo "$id not exist <br />";
			continue;
		}
		
		$fp = fopen($src_file_path, "r+");
		if(flock($fp, LOCK_EX)){ 
			// read
			$message = unserialize(fread($fp, 8192));
			// flag
			if($flag == '\\Seen'){
				$message->is_seen = $set;
			} else if($flag == '\\Deleted'){
				$message->is_deleted = $set;
			} 
			// other flags: reply, etc.

			// write back
			rewind($fp);
			fwrite($fp, serialize($message));
			fflush($fp); 
			flock($fp, LOCK_UN); 
		}
		fclose($fp); 
	}
		
	return $ret;	
}


function sqimap_msgs_list_delete_off($mailbox, $id) {
	// add new instr 
    $newinstr = new Instr();
    $newinstr->instr_name = 'sqimap_msgs_list_delete';
	array_push($newinstr->param_list, $mailbox, $id);
	if(!$newinstr->append()){
		return false;	 	
	}
	
    global $move_to_trash, $trash_folder, $uid_support;
	// get local $mailbox path
	global $path_off;
	$mailbox_path = $path_off[$mailbox];
	$trash_path = $path_off[$trash_folder];
	$ret = true;

    if (($move_to_trash == true) /*&& (sqimap_mailbox_exists($imap_stream, $trash_folder)*/ && ($mailbox != $trash_folder)/*)*/) {
	/*
	 * copy local messages to trash folder
	 */ 
	// copy each msg
	foreach($id as $cur_id){
		// file path
		$src_file_path = $mailbox_path . $cur_id;
		$dst_file_path = $trash_path . $cur_id;
		
		// check src file existing
		if(!file_exists($src_file_path)){
			echo "$id not exist <br />";
			continue;
		}

		// copy
		// NOTE: may be unlocked
		if(!copy($src_file_path, $dst_file_path)){
			echo "failed to copy $cur_id <br />";
			$ret = false;
		}
	} // end of foreach
	} // end of if

	// flag messages as '\\Deleted'
	foreach($id as $cur_id){
		// file path
		$src_file_path = $mailbox_path . $cur_id;
		
		// check src file existing
		if(!file_exists($src_file_path)){
			echo "$id not exist <br />";
			continue;
		}
		
		$fp = fopen($src_file_path, "r+");
		if(flock($fp, LOCK_EX)){ 
			// read
			$message = unserialize(fread($fp, 8192));
			// flag 
			$message->is_deleted = 1;
			// write back
			rewind($fp);
			fwrite($fp, serialize($message));
			fflush($fp); 
			flock($fp, LOCK_UN); 
		}
		fclose($fp); 
	} // end of foreach

	return $ret;
}

