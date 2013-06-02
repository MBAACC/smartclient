<?php

if (! defined('SM_PATH')) define('SM_PATH','../');
include_once(SM_PATH . 'class/Instr.class.php');
include_once(SM_PATH . 'functions/more_constants_off.php');



function sqimap_mailbox_expunge_off ($mailbox, $handle_errors = true, $id='') {
	// add new instr 
    $newinstr = new Instr();
    $newinstr->instr_name = 'sqimap_mailbox_expunge';
	array_push($newinstr->param_list, $mailbox, $handle_errors, $id);
	if(!$newinstr->append()){
		return false;	 	
	}
	
	// delete messages
	global $path_off;
	$mailbox_path = $path_off[$mailbox];
	$ret = true;
    $cnt = 0;
	// traverse mailbox_path
	$dirp = opendir($mailbox_path);
	while(($filename = readdir($dirp)) != false){
		if($filename == '.' || $filename == '..')
			continue;
		if($filename == 'msgs')
			continue;

		// file path
		$src_file_path = $mailbox_path . $filename;
	/*	
		// check src file existing
		if(!file_exists($src_file_path)){
			echo "$id not exist <br />";
			continue;
		}
	*/	
		
		// check flag is_deleted
		$fp = fopen($src_file_path, "r");
		// exclusive
		if(flock($fp, LOCK_EX)){ 
			// read
			$message = unserialize(fread($fp, 8192));
			// delete
			if($message->is_deleted){
				if(!unlink($src_file_path)){
					flock($fp, LOCK_UN); 
					fclose($fp); 
					echo "failed to delete $cur_id";
					$ret = false;
					continue;
				}
				$cnt++;
			}
		}// end of if
		fclose($fp); 
	}
	
	if(!$ret)
		return -1;
    return $cnt;
}

