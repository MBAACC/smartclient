<?php

if (! defined('SM_PATH')) define('SM_PATH','../');
include_once(SM_PATH . 'class/Instr.class.php');

function sqimap_mailbox_expunge_off ($mailbox, $handle_errors = true, $id='') {
	// add new instr 
    $newinstr = new Instr();
    $newinstr->instr_name = 'sqimap_mailbox_expunge';
	array_push($newinstr->param_list, $mailbox, $handle_errors, $id);
	if(!$newinstr->append()){
		return false;	 	
	}
	
	// TODO: delete messages
/*
    $read = sqimap_run_command($imap_stream, 'EXPUNGE'.$id, $handle_errors,
                               $response, $message, $uid);

    $cnt = 0;

    if (is_array($read)) {
        foreach ($read as $r) {
            if (preg_match('/^\*\s[0-9]+\sEXPUNGE/AUi',$r,$regs)) {
                $cnt++;
            }
        }
    }
*/
    $cnt = 0;
    return $cnt;
}

