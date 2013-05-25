<?php


if (! defined('SM_PATH')) define('SM_PATH','../');
include_once(SM_PATH . 'class/Instr.class.php');
//require_once(SM_PATH . 'class/mime/Rfc822Header.class.php');
/**
 * imap_messages_off.php
 *
 * offline version of functions in functions/imap_messages.php
 *
 */

function sqimap_toggle_flag_off($id, $flag, $set, $handle_errors/*, $newinstr*/) {
	// add new instr 
    $newinstr = new Instr();
    $newinstr->instr_name = 'sqimap_toggle_flag';
	array_push($newinstr->param_list, $id, $flag, $set, $handle_errors);
	if(!$newinstr->append()){
		return false;	 	
	}
	return true;	
	// process local data
	// TODO: parse flag	
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
    //$msgs_id = sqimap_message_list_squisher($id);
    if (($move_to_trash == true) /*&& (sqimap_mailbox_exists($imap_stream, $trash_folder)*/ && ($mailbox != $trash_folder)/*)*/) {

	// TODO: copy local messages to trash folder
	// PHP copy()
	/*
        $read = sqimap_run_command ($imap_stream, "COPY $msgs_id \"$trash_folder\"", false, $response, $message, $uid_support);
    */
	}

	// TODO: flag messages as '\\Deleted'
/*
    $read = sqimap_run_command ($imap_stream, "STORE $msgs_id +FLAGS (\\Deleted)", true, $response, $message, $uid_support);
*/
}

