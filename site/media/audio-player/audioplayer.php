<?php
	if ( !defined("HQ") || !defined("HQ") )
	{
		echo "Hazaa! Denied.";
		exit;
	}
	
	hook( "js_frontend", "audioplayerJs" );
	
	function audioplayerJs()
	{
		$self		=	HQ_URL . "site/media/audio-player/";
		$playerUrl	=	$self . "player.swf";
		
		echo requireJs( "swfobject.js", true );
		echo requireJs( $self . "audio-player.js" );
		
		// Options - Edit these!
		$js= <<<JS
			<script type="text/javascript">  
				AudioPlayer.setup("{$playerUrl}",
				{  
					width: 290,
					bg: 'ffffff',
					leftbg: 'eeeeee',
					lefticon: '333333',
					voltrack: 'f2f2f2',
					volslider: '666666',
					rightbg: 'b4b4b4b',
					rightbghover: '999999',
					righticon: '333333',
					righticonhover: 'ffffff',
					loader: '009900',
					track: 'ffffff',
					tracker: 'dddddd',
					border: 'cccccc',
					skip: '666666',
					text: '333333',
				}); 
			</script>
JS;

		echo $js;
	}
	
	function audioplayer( $file, $project )
	{	
		static $playerId= 0;
		$playerId++;
		
		$soundFile	=	PROJECTS_URL . $project['slug'] . '/' . $file['file'];
		
		$embed= <<<JS
			<div id="audioplayer_{$playerId}">Audio</div>
			<script type="text/javascript">  
				AudioPlayer.embed("audioplayer_{$playerId}", 
					{
						soundFile: "{$soundFile}",
						titles:	"{$file['title']}",
						artists: "{$file['caption']}"
					});  
			</script>
JS;

		return $embed;
	}
?>