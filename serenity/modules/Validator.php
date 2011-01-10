<?php
namespace Serenity;

/**
 * Object containing validation options for a parameter 
 * @author Pete
 *
 */
class ParamDefinition
{
    const TYPE_STRING = 1;
    const TYPE_INT = 2;
    const TYPE_NUMBER = 3;
    const TYPE_EMAIL = 4;

    public $name = "";
    public $friendlyName = "";
    public $type = 0;
    public $required = false;
    public $unique = false;
    public $minLen = null;
    public $maxLen = null;
    public $minVal = null;
    public $maxVal = null;
    public $errorMessage = null;
    public $matchField = null;
    public $defaultValue = null;
    public $foreignModel = false;
    
    public $field = null;
    public $model = null;
    
    /**
     * Parses the array of strings passed and creates a ParamDefinition
     * @param array $paramArray
     * @param SerenityModel $referenceModel
     * @param SerenityField $referenceField
     * @throws SerenityException
     */
    function ParamDefinition($paramArray, $referenceModel = null, $referenceField = null)
    {
        $this->model = $referenceModel;
        $this->field = $referenceField;
        
        foreach($paramArray as $var=>$value)
        {
            switch($var)
            {
                case "minVal":
                    $this->minVal = (int)$value;
                break;
                case "maxVal":
                    $this->maxVal = (int)$value;
                break;
                case "minLen":
                    $this->minLen = (int)$value;
                break;
                case "maxLen":
                    $this->maxLen = (int)$value;
                break;
                case "required":
                        $this->required = ($value) ? true : false;
                break;
                case "friendlyName":
                    $this->friendlyName = $value;
                break;
                case "type":
                    if($value == "string")
                        $this->type = ParamDefinition::TYPE_STRING;
                    else if($value == "int")
                        $this->type = ParamDefinition::TYPE_INT;
                    else if($value == "number")
                        $this->type = ParamDefinition::TYPE_NUMBER;
                    else if($value == "email")
                        $this->type = ParamDefinition::TYPE_EMAIL;
                    else
                        throw new SerenityException("Invalid parameter type '" . $value . "'");
                break;
                case "unique":
                    $this->unique = ($value) ? true : false;
                break;
                case "matchField":
                    if($value == null)
                        throw new SerenityException("Validator unable to locate matchField for " . $this->name);

                    $this->matchField = $value;
                break;
                case "defaultValue":
                	$this->defaultValue = $value;
                break;
                case "errorMessage":
                	$this->errorMessage = $value;
                break;
                case "foreignModel":
                	$this->foreignModel = ($value) ? true : false;
                break;
            }
        }
    }

    /**
     * Returns the friendly name of a parameter
     * @return string
     */
    public function getFriendlyName()
    {
        $name = $this->name;
        if($this->friendlyName != "")
            $name = $this->friendlyName;

        return $name;
    }
}

/**
 * Main parameter validator class
 * @author Pete
 *
 */
class ParameterValidator
{
    /**
     * Validate parameter. Returns an error message or an empty string
     * on success
     * @param string $paramValue
     * @param ParamDefinition $paramDefinition
     * @throws SerenityException
     * @return string
     */
    public function validate($paramValue, $paramDefinition, $model = null)
    {
        if($paramDefinition == null)
            return;

        $isUpdate = false;
        if($model && $model->getPrimaryKeyValue() != "")
        	$isUpdate = true;
                    
        $paramName = $paramDefinition->getFriendlyName();

        // Required field (must be first)
        if($paramDefinition->required == true)
        {
        	if(!$isUpdate || ($isUpdate && $paramDefinition->type != "form"))
        	{	
	            if($paramValue == "" || $paramValue == null)
	            {
	                if($paramDefinition->errorMessage === null)
	                    $errorMessage = $paramName . " is a required field.";
	                else
	                    $errorMessage = $paramDefinition->errorMessage;
	
	                return $errorMessage;
	            }
        	}
        }
        else if($paramValue == "" || $paramValue == null)
        	return "";

        // Compare to another field
        if($paramDefinition->matchField != null)
        {
            $matchFieldValue = $paramDefinition->matchField->getValue();
            if($matchFieldValue != $paramValue)
            {
                if($paramDefinition->errorMessage === null)
                    $errorMessage = $paramName . " must match " . $paramDefinition->matchField->getFriendlyName() . ".";
                else
                    $errorMessage = $paramDefinition->errorMessage;

                return $errorMessage;
            }
        }

        // Check for unique
        if($paramDefinition->unique == true)
        {
            $referenceModel = $model;
            if($referenceModel == null)
                throw new SerenityException("Unable to set field '" . $paramName . "' to unique without reference model.");

            $query = $referenceModel->query()->addWhere($paramDefinition->name . "='" . mysql_escape_string($paramValue) . "'");
            
            if($isUpdate)
            {
            	$query->addWhere($referenceModel->getPrimaryKey() . "<>'" . $referenceModel->getPrimaryKeyValue() . "'");
            }
           	
            $existingModel = $query->fetchOne();
            
            if($existingModel != null)
            {
                if($paramDefinition->errorMessage === null)
                    $errorMessage = $paramName . " is already in use, please choose another.";
                else
                    $errorMessage = $paramDefinition->errorMessage;

                return $errorMessage;
            }
        }

        // String
        if($paramDefinition->type == ParamDefinition::TYPE_EMAIL)
        {
            if(!$this->isValidEmailAddress($paramValue))
            {
                if($paramDefinition->errorMessage === null)
                    $errorMessage = "Email address is invalid.";
                else
                    $errorMessage = $paramDefinition->errorMessage;

                return $errorMessage;
            }
        }

        // String
        if($paramDefinition->type == ParamDefinition::TYPE_STRING)
        {
            if($paramDefinition->minLen !== null)
            {
                if(strlen($paramValue) < $paramDefinition->minLen)
                {
                    if($paramDefinition->errorMessage === null)
                        $errorMessage = $paramName . " is too short. Must be at least " . $paramDefinition->minLen . " characers long.";
                    else
                        $errorMessage = $paramDefinition->errorMessage;

                    return $errorMessage;
                }
            }

            if($paramDefinition->maxLen !== null)
            {
                if(strlen($paramValue) > $paramDefinition->maxLen)
                {
                    if($paramDefinition->errorMessage === null)
                        $errorMessage = $paramName . " is too long. Must be less than " . $paramDefinition->maxLen . " characers long.";
                    else
                        $errorMessage = $paramDefinition->errorMessage;

                    return $errorMessage;
                }
            }
        }

        // Number
        if($paramDefinition->type == ParamDefinition::TYPE_NUMBER)
        {
            if(!is_numeric($paramValue))
            {
                if($paramDefinition->errorMessage === null)
                    $errorMessage = $paramName . " must be numeric.";
                else
                    $errorMessage = $paramDefinition->errorMessage;

                return $errorMessage;
            }
        }


        // Integer
        if($paramDefinition->type == ParamDefinition::TYPE_INT)
        {
            if(preg_match('/^\d*$/', $paramValue) != 1)
            {
                if($paramDefinition->errorMessage === null)
                    $errorMessage = $paramName . " must be an integer.";
                else
                    $errorMessage = $paramDefinition->errorMessage;

                return $errorMessage;
            }
        }

        if($paramDefinition->type == ParamDefinition::TYPE_INT || $paramDefinition->type == ParamDefinition::TYPE_NUMBER)
        {
            if($paramDefinition->minVal !== null)
            {
                if($paramValue < $paramDefinition->minVal)
                {
                    if($paramDefinition->errorMessage === null)
                        $errorMessage = $paramName . " out of range. Must be greater than " . $paramDefinition->minVal . ".";
                    else
                        $errorMessage = $paramDefinition->errorMessage;

                    return $errorMessage;
                }
            }

            if($paramDefinition->maxVal !== null)
            {
                if($paramValue > $paramDefinition->maxVal)
                {
                    if($paramDefinition->errorMessage === null)
                        $errorMessage = $paramName . " out of range. Must be less than " . $paramDefinition->maxVal . ".";
                    else
                        $errorMessage = $paramDefinition->errorMessage;

                    return $errorMessage;
                }
            }
        }
        
		// Check foreign model key
		if($paramDefinition->foreignModel)
		{
			if($paramDefinition->field == null)
				throw new SerenityException('Error accessing field for foreign model validator');
			
			$foreignModel = sp::app()->getModel($paramDefinition->field->foreignModel);
			if($foreignModel == null)
				throw new SerenityException("Validator unable to access model'" . $paramDefinition->field->foreignModel . "'");
			
			$retModel = $foreignModel->query(mysql_escape_string($paramValue))->fetchOne();

			if($retModel == null)	
			{
				if($paramDefinition->errorMessage === null)
                	$errorMessage = $paramName . " is not a valid entry.";
                else
                	$errorMessage = $paramDefinition->errorMessage;

                return $errorMessage;				
			}
		}      	
        	        

        return "";
    }


    /**
     * Validate an email address format
     * @param string $email
     * @return boolean
     */
    function isValidEmailAddress($email)
    {

        $no_ws_ctl = "[\\x01-\\x08\\x0b\\x0c\\x0e-\\x1f\\x7f]";
        $alpha  = "[\\x41-\\x5a\\x61-\\x7a]";
        $digit  = "[\\x30-\\x39]";
        $cr  = "\\x0d";
        $lf  = "\\x0a";
        $crlf  = "(?:$cr$lf)";

        $obs_char = "[\\x00-\\x09\\x0b\\x0c\\x0e-\\x7f]";
        $obs_text = "(?:$lf*$cr*(?:$obs_char$lf*$cr*)*)";
        $text  = "(?:[\\x01-\\x09\\x0b\\x0c\\x0e-\\x7f]|$obs_text)";

        $text  = "(?:$lf*$cr*$obs_char$lf*$cr*)";
        $obs_qp  = "(?:\\x5c[\\x00-\\x7f])";
        $quoted_pair = "(?:\\x5c$text|$obs_qp)";

        $wsp  = "[\\x20\\x09]";
        $obs_fws = "(?:$wsp+(?:$crlf$wsp+)*)";
        $fws  = "(?:(?:(?:$wsp*$crlf)?$wsp+)|$obs_fws)";
        $ctext  = "(?:$no_ws_ctl|[\\x21-\\x27\\x2A-\\x5b\\x5d-\\x7e])";
        $ccontent = "(?:$ctext|$quoted_pair)";
        $comment = "(?:\\x28(?:$fws?$ccontent)*$fws?\\x29)";
        $cfws  = "(?:(?:$fws?$comment)*(?:$fws?$comment|$fws))";


        $outer_ccontent_dull = "(?:$fws?$ctext|$quoted_pair)";
        $outer_ccontent_nest = "(?:$fws?$comment)";
        $outer_comment  = "(?:\\x28$outer_ccontent_dull*(?:$outer_ccontent_nest$outer_ccontent_dull*)+$fws?\\x29)";

        $atext  = "(?:$alpha|$digit|[\\x21\\x23-\\x27\\x2a\\x2b\\x2d\\x2f\\x3d\\x3f\\x5e\\x5f\\x60\\x7b-\\x7e])";
        $atom  = "(?:$cfws?(?:$atext)+$cfws?)";

        $qtext  = "(?:$no_ws_ctl|[\\x21\\x23-\\x5b\\x5d-\\x7e])";
        $qcontent = "(?:$qtext|$quoted_pair)";
        $quoted_string = "(?:$cfws?\\x22(?:$fws?$qcontent)*$fws?\\x22$cfws?)";

        $quoted_string = "(?:$cfws?\\x22(?:$fws?$qcontent)+$fws?\\x22$cfws?)";
        $word  = "(?:$atom|$quoted_string)";

        $obs_local_part = "(?:$word(?:\\x2e$word)*)";
        $obs_domain = "(?:$atom(?:\\x2e$atom)*)";

        $dot_atom_text = "(?:$atext+(?:\\x2e$atext+)*)";
        $dot_atom = "(?:$cfws?$dot_atom_text$cfws?)";

        $dtext  = "(?:$no_ws_ctl|[\\x21-\\x5a\\x5e-\\x7e])";
        $dcontent = "(?:$dtext|$quoted_pair)";
        $domain_literal = "(?:$cfws?\\x5b(?:$fws?$dcontent)*$fws?\\x5d$cfws?)";


        $local_part = "(($dot_atom)|($quoted_string)|($obs_local_part))";
        $domain  = "(($dot_atom)|($domain_literal)|($obs_domain))";
        $addr_spec = "$local_part\\x40$domain";


        if (strlen($email) > 256) return 0;

        $email = $this->rfc3696_strip_comments($outer_comment, $email, "(x)");


        if (!preg_match("!^$addr_spec$!", $email, $m)){

            return 0;
        }

        $bits = array(
         'local'   => isset($m[1]) ? $m[1] : '',
         'local-atom'  => isset($m[2]) ? $m[2] : '',
         'local-quoted'  => isset($m[3]) ? $m[3] : '',
         'local-obs'  => isset($m[4]) ? $m[4] : '',
         'domain'  => isset($m[5]) ? $m[5] : '',
         'domain-atom'  => isset($m[6]) ? $m[6] : '',
         'domain-literal' => isset($m[7]) ? $m[7] : '',
         'domain-obs'  => isset($m[8]) ? $m[8] : '',
        );

        $bits['local'] = $this->rfc3696_strip_comments($comment, $bits['local']);
        $bits['domain'] = $this->rfc3696_strip_comments($comment, $bits['domain']);


        if (strlen($bits['local']) > 64) return 0;
        if (strlen($bits['domain']) > 255) return 0;

        if (strlen($bits['domain-literal'])){

            $Snum   = "(\d{1,3})";
            $IPv4_address_literal = "$Snum\.$Snum\.$Snum\.$Snum";

            $IPv6_hex  = "(?:[0-9a-fA-F]{1,4})";

            $IPv6_full  = "IPv6\:$IPv6_hex(:?\:$IPv6_hex){7}";

            $IPv6_comp_part  = "(?:$IPv6_hex(?:\:$IPv6_hex){0,5})?";
            $IPv6_comp  = "IPv6\:($IPv6_comp_part\:\:$IPv6_comp_part)";

            $IPv6v4_full  = "IPv6\:$IPv6_hex(?:\:$IPv6_hex){5}\:$IPv4_address_literal";

            $IPv6v4_comp_part = "$IPv6_hex(?:\:$IPv6_hex){0,3}";
            $IPv6v4_comp  = "IPv6\:((?:$IPv6v4_comp_part)?\:\:(?:$IPv6v4_comp_part\:)?)$IPv4_address_literal";

            if (preg_match("!^\[$IPv4_address_literal\]$!", $bits['domain'], $m)){

                if (intval($m[1]) > 255) return 0;
                if (intval($m[2]) > 255) return 0;
                if (intval($m[3]) > 255) return 0;
                if (intval($m[4]) > 255) return 0;

            }else{


                while (1){

                    if (preg_match("!^\[$IPv6_full\]$!", $bits['domain'])){
                        break;
                    }

                    if (preg_match("!^\[$IPv6_comp\]$!", $bits['domain'], $m)){
                        list($a, $b) = explode('::', $m[1]);
                        $folded = (strlen($a) && strlen($b)) ? "$a:$b" : "$a$b";
                        $groups = explode(':', $folded);
                        if (count($groups) > 6) return 0;
                        break;
                    }

                    if (preg_match("!^\[$IPv6v4_full\]$!", $bits['domain'], $m)){

                        if (intval($m[1]) > 255) return 0;
                        if (intval($m[2]) > 255) return 0;
                        if (intval($m[3]) > 255) return 0;
                        if (intval($m[4]) > 255) return 0;
                        break;
                    }

                    if (preg_match("!^\[$IPv6v4_comp\]$!", $bits['domain'], $m)){
                        list($a, $b) = explode('::', $m[1]);
                        $b = substr($b, 0, -1); # remove the trailing colon before the IPv4 address
                        $folded = (strlen($a) && strlen($b)) ? "$a:$b" : "$a$b";
                        $groups = explode(':', $folded);
                        if (count($groups) > 4) return 0;
                        break;
                    }

                    return 0;
                }
            }
        }else{

            $labels = explode('.', $bits['domain']);

            if (count($labels) == 1) return 0;


            foreach ($labels as $label){

                if (strlen($label) > 63) return 0;
                if (substr($label, 0, 1) == '-') return 0;
                if (substr($label, -1) == '-') return 0;
            }


            if (preg_match('!^[0-9]+$!', array_pop($labels))) return 0;
        }


        return 1;
    }

    function rfc3696_strip_comments($comment, $email, $replace=''){

        while (1){
            $new = preg_replace("!$comment!", $replace, $email);
            if (strlen($new) == strlen($email)){
                return $email;
            }
            $email = $new;
        }
    }

}

// Create singleton
$validator = new ParameterValidator();
sp::$parameterValidator = $validator;
?>
