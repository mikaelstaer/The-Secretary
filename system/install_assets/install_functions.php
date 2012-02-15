<?php

	// From http://www.webtoolkit.info/php-random-password-generator.html
	function generatePassword($length= 9, $strength= 0)
	{
	    $vowels = 'aeiouy';
	    $consonants = 'bdghjmnpqrstvz';
	    
		if ($strength & 1)
		{
	        $consonants .= 'BDGHJLMNPQRSTVWXZ';
	    }
	    
		if ($strength & 2)
		{
	        $vowels .= "AEIOUY";
	    }
	    
		if ($strength & 4)
		{
	        $consonants .= '23456789';
	    }
	    
		if ($strength & 8)
		{
	        $consonants .= '@#$%';
	    }

	    $password = '';
	    $alt = time() % 2;
	    
		for ( $i = 0; $i < $length; $i++ )
		{
	        if ($alt == 1)
			{
	            $password .= $consonants[( rand() % strlen($consonants) )];
	            $alt = 0;
	        }
			else
			{
	            $password .= $vowels[( rand() % strlen($vowels) )];
	            $alt = 1;
	        }
	    }
	    return $password;
	}
?>