<?php

/**
 * instr_off.class.php
 *
 */


/**
 * The object that contains an instruction used in synchronizing
 *
 */
class Instr {
    /**
	 * instruction name
	 * e.g. the function name
	 * @var string
	 */
    var $instr_name = 'ab';
	
	/**
	 * parameter list
	 * @var array
	 */
    var $param_list = array(); 
    

	/**
	 * 
	 *
	 */
    function append() {
    	$instr_file = '../local_data/async/instr';

		$flag = false;
		$fp = fopen("$instr_file", "a+");
		
		if(flock($fp, LOCK_EX)){
			fwrite($fp, serialize($this)."\n\n");	
			fflush($fp);
			flock($fp, LOCK_UN);
			$flag = true;
		}
		
		fclose($fp);
		return $flag;
	}
}


