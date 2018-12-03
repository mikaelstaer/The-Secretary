<?php
	hook( "pageTextModify", "codeBlock", "", 0 );
	hook( "blogPostModify", "codeBlock", "", 0 );
	hook( "textblockModify", "codeBlock", "", 0 );

	function codeBlock( $text )
	{
		$pattern= "@{code}(.*?){/code}@s";
		$subject= html_entity_decode( $text['original'] );

		preg_match_all( $pattern, $subject, $matches );
		$replace= preg_replace( $pattern, "{code}", $subject );
		$replace= nl2p( $replace );

		$count= 0;
		foreach ( $matches[1] as $match )
		{
			$d= preg_replace( "@^\n@", "", $match );
			$replace= preg_replace( "@({code})@s", $d, $replace, 1 );
		}

		$text['modified']= $replace;

		return $text;
	}
?>
