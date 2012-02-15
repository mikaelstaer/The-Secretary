<?php
	if ( !defined("HQ") || !defined("HQ") )
	{
		echo "Whhap! Ninja blocked.";
		exit;
	}
	
	hook( "js_frontend", "mediaplayerJs" );
	
	function mediaplayerJs()
	{	
		$playerUrl	=	HQ_URL . "site/media/media-player/player.swf";
		
		echo requireJs( "jquery.js", true );
		echo requireJs( "swfobject.js", true );	
		echo requireJs( "jquery.media.js", true );
		
		$js= <<<JS
			<script type="text/javascript" charset="utf-8">
				jQuery( function($) {
					$.fn.media.defaults.flvPlayer = '{$playerUrl}';
				});
			</script>
JS;
		echo $js;
	}
	
	function mediaplayer( $file, $project )
	{	
		static $playerId= 0;
		$playerId++;
		
		$self		=	HQ_URL . "site/media/media-player/";
		$playerUrl	=	$self. "player.swf";
		
		$mediaFile	=	PROJECTS_URL . $project['slug'] . '/' . $file['file'];
		
		$width		=	( $file['width'] == 0 ) ? "" : "width: " . $file['width'] . ",";
		$height		=	( $file['height'] == 0 ) ? "" : "height: " . $file['height'] . ",";
		
		$embed= <<<HTML
			<a id="{$project['slug']}-{$file['id']}" href="{$mediaFile}"></a>
			<script type="text/javascript" charset="utf-8">
				jQuery( function($) {
					$('#{$project['slug']}-{$file['id']}').media(
						{
							{$width}
							{$height}
							flashvars: {
								file: '{$mediaFile}'
							},
							params: {
								wmode: 'transparent'
							}
						}
					);
				});
			</script>
HTML;

		return $embed;
	}
?>