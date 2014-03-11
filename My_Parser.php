<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
* MY Parser Extended Library with simple
* if statement parsing
* 
* @library      Parser
* @author       Ketan Mistry
* @link 		http://dubbedcreative.com
* @version		1.1
* @created		6 March 2014
*/

class MY_Parser extends CI_Parser{

    function parse($template, $data, $return=FALSE)
    {    	

        $CI =& get_instance();
        $template = $CI->load->view($template, $data, TRUE);
 
		if ($template == '') {
			return FALSE;
		}

		foreach ($data as $key => $val) {
			if (is_array($val))
			{
				$template = $this->_parse_pair($key, $val, $template);
			}
			else
			{
				$template = $this->_parse_single($key, (string)$val, $template);
			}
		}

        // Check for conditional statements
        $template = $this->_parse_conditionals($template, $data);

		if ($return == FALSE)
		{
			$CI =& get_instance();
			$CI->output->append_output($template);
		}

        return $template;

    }

	#------------------------------------------------------
	# This function checks for simple {if}{else}{/if}
	# statements and parses them accordingly.
	#------------------------------------------------------
	function _parse_conditionals($template, $data) {
		
		$CI =& get_instance();
		
		// Some settings
		$currency = "&pound;";

		// Now we'll check for conditionals
		preg_match_all('#{if (.+)}(.+){/if}#sU', $template, $conditionals, PREG_SET_ORDER); //Works
		
		#echo "<pre>".print_r($conditionals, true)."</pre>"; // For testing
		
		// Loop through the conditionals we found above. We don't want to 
		// use eval() here due to security and server config issues.
		foreach ($conditionals as $conditional) {
		
			// Remove the raw code from the template
			$code = $conditional[0];
			
			// This is the content we want to output if the conditional is satisfied
			$output = $conditional[2]; 
			
			#echo "<pre>$output</pre>"; // For testing
		
			// Dissect the if statement to get the comparison values and operator and remove
			// any surrounding quotes as we can ignore them. Also remove any currency characters.
			$statement = str_replace($currency, '', $conditional[1]);
			preg_match('@(.+\s?)(>|>=|<>|!=|==|<=|<)(.+\s?)@', $statement, $comparison);
			#print_r($comparison); // For testing
			$a = (trim($comparison[1]) != "") ? str_replace('"', '', trim($comparison[1])) : FALSE;
			$b = (trim($comparison[3]) != "") ? str_replace('"', '', trim($comparison[3])) : FALSE;
			$operator = trim($comparison[2]);
			
			// Check for true/false values and convert them to a booleans for
			// better parser comparison
			if ($a == "true" or $a == "TRUE") {
				$a = 1;
			} elseif ($a == "false" or $a == "FALSE") {
				$a = 0;
			}
	
			if ($b == "true" or $b == "TRUE") {
				$b = 1;
			} elseif ($b == "false" or $b == "FALSE") {
				$b = 0;
			}
			
			#echo "$a - $b"; // For testing
			
			switch($operator) {
				case '>':
					$output = ($a > $b) ? $output : "";
					break;
				case '>=':
					$output = ($a >= $b) ? $output : "";
					break;
				case '<>':
					$output = ($a <> $b) ? $output : "";
					break;
				case '!=':
					$output = ($a != $b) ? $output : "";
					break;
				case '==':
					$output = ($a == $b) ? $output : "";
					break;
				case '<=':
					$output = ($a <= $b) ? $output : "";
					break;
				case '<':
					$output = ($a < $b) ? $output : "";
					break;
			}
			
			// If $output above is empty, then let's check for an {else}
			$else = preg_split('@{else}@', $conditional[2]);
			
			// If $output is empty, it means the condition in the above
			// switch was not met, so if an {else} does exist use the
			// second part of the statement
			if ($output == "" and count($else) > 0) {
				$output = $else[1];
			// Otherwise the switch condition was met, so if an {else}
			// exists use the first part of the statement
			} elseif($output != "" and count($else) > 0) {
				$output = $else[0];
			}
			
			// Replace the template code with the output
			// we want to display
			$template = str_replace($code, $output, $template);
		
		}
		
		// Output the formatted content
		return $template;
		
	}
    
} 