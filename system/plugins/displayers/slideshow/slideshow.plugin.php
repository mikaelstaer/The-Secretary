<?php
	hook( "js_frontend", "slideshowJs" );
	
	function slideshowJs()
	{
		$self= SYSTEM_URL . "plugins/displayers/slideshow/";
		
		echo requireJs( "jquery.js", true );
		echo requireJs( $self . "jquery.cycle.js" );
	}
	
	function slideshow( $project, $files, $group )
	{
		global $clerk;
		
		$html= "";
		$slides= "";
		$totalFiles= 0;
		
		foreach ( $files as $file => $data )
		{
			if ( $data['filegroup'] == $group['num'] )
			{
				$totalFiles++;
				
				// Handle resizing of large image
				$settings		=	$clerk->getSetting( "projects_fullsizeimg" );
				$do_scale		=	(boolean) $settings['data3'];
				$intelliscale	=	(int) $settings['data2'];
				
				if ( $do_scale )
				{
					list( $width, $height )= explode( "x" , $settings['data1'] );
					$image= dynamicThumbnail( $data['file'], PROJECTS_PATH . $project['slug'] . '/', $width, $height, $intelliscale );
				}
				else
				{
					list( $width, $height )= getimagesize( PROJECTS_PATH . $project['slug'] . '/' . $data['file'] );
					$image= '<img src="' . PROJECTS_URL . $project['slug'] . '/' . $data['file'] . '" width="' . $width . '" height="' . $height . '" alt="" />';
				}
				
				switch ( $data['type'] )
				{
					case "image":
							$slides.= '<div class="file">
											'. $image;
							break;
					case "video":
							$slides.= '<div class="file">' . mediaplayer( $data, $project );
							break;
					case "audio":
							$slides.= '<div class="file">' . audioplayer( $data, $project );
							break;
				}
				
				if ( $clerk->getSetting( "projects_hideFileInfo", 1 ) == false  && ( !empty( $data['title'] ) || !empty( $data['caption'] ) ) )
				{
					$info= "";	
					$info_html= '<div class="info">
								<span class="title">' . $data['title'] . '</span>
								<span class="caption">' . html_entity_decode( $data['caption'] ) . '</span>';
					
					$info_html= call_anchor( "slideshow_info", array( 'html' => $info_html, 'file' => $data ) );
					
					$info= $info_html['html'] . '</div>';
				}
				
				$slides.= $info . '</div>';
			}
			
			$info= "";
		}
		
		if ( $totalFiles == 0 ) return;
		
		$opts= unserialize( $clerk->getSetting( "slideshow_opts", 1 ) );
		foreach( $opts as $key => $val )
		{
			$opts[$key]= html_entity_decode( $val );
		}
		
		$of= str_replace( array( "#", "total" ), array( '<span class="currentSlide">1</span>', $totalFiles ), $opts['of'] );
		
		$nav_html= <<<HTML
			<div class="slideshow-nav"><a href="#" class="prev">{$opts['prev']}</a> {$opts['divider']} <a href="#" class="next">{$opts['next']}</a> $of</div>
HTML;
		$nav= call_anchor( "slideshow_nav", array( "html" => $nav_html, "total_files" => $totalFiles ) );
		$jquery_slideshow_opts= call_anchor( "jquery_slideshow_opts", array( 'js' => '', 'project' => $project, 'group' => $group ) );
		
		if ( empty( $jquery_slideshow_opts['js'] ) == false ) $jquery_slideshow_opts['js']= "," . $jquery_slideshow_opts['js'];
		if ( empty( $opts['fx'] ) ) $opts['fx']= "fade";
		
		if ( $opts['nav_pos'] == "top" )
		{
			$html= <<<HTML
			{$nav['html']}
			<div class="slides">
				{$slides}
			</div>
HTML;
		}
		else
		{
			$html= <<<HTML
			<div class="slides">
				{$slides}
			</div>
			{$nav['html']}
HTML;
		}
		
		$html.= <<<HTML
			<script type="text/javascript" charset="utf-8">
				jQuery(window).load( function()
				{
					jQuery("#{$project['slug']}-{$group['num']} .slides").cycle(
						{
							fx: '{$opts['fx']}',
							slideExpr: '.file',
							timeout: 0,
							speed: 500,
							next: '#{$project['slug']}-{$group['num']} .next, #{$project['slug']}-{$group['num']} .file',
							prev: '#{$project['slug']}-{$group['num']} .prev',
							prevNextClick: function(isNext, index, el)
							{
								jQuery("#{$project['slug']}-{$group['num']} .currentSlide").text(index + 1);
							},
							before: function(curr, next, opts, fwd)
							{
								var ht = jQuery(next).height();

							 	jQuery(this).parent().animate({height: ht});
							}
							{$jquery_slideshow_opts['js']}
						}
					);
				});
			</script>
HTML;
		
		return $html;
	}
?>