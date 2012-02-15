<?php
	hook( "js_frontend", "popJs" );
	hook( "css_frontend", "popCss" );
	
	function popCss()
	{
		$self= SYSTEM_URL . "plugins/displayers/pop/";
		echo requireCss( $self . "pop.css" );
	}
	
	function popJs()
	{
		$self= SYSTEM_URL . "plugins/displayers/pop/";

		echo requireJs( "jquery.js", true );
		echo requireJs( $self . "pop.js" );
	}
	
	function pop( $project, $files, $group )
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
				$intelliscale	=	$settings['data2'];
				
				if ( $do_scale )
				{
					list( $width, $height )= explode( "x" , $settings['data1'] );
					
					$fullsize= dynamicThumbnail( $data['file'], PROJECTS_PATH . $project['slug'] . '/', $width, $height, $intelliscale, "short" );
					if ( dynamic_thumb_saved( $data['file'], PROJECTS_PATH . $project['slug'] . '/', $width, $height, $intelliscale ) )
						list( $width, $height )= getimagesize( get_dynamic_thumb( $data['file'], PROJECTS_PATH . $project['slug'] . '/', $width, $height, $intelliscale ) );
					else
					{
						$width= ( $width == 0 ) ? "auto" : $width;
						$height= ( $height == 0 ) ? "auto" : $height;
					}
						
				}
				else
				{
					list( $width, $height )= getimagesize( PROJECTS_PATH . $project['slug'] . '/' . $data['file'] );
					$fullsize= PROJECTS_URL . $project['slug'] . '/' . $data['file'];
				}
				
				// Handle thumbnail
				$thumbFile		= $data['file'];
				$thumbWidth		= $clerk->getSetting( "projects_filethumbnail", 1 );
				$thumbHeight	= $clerk->getSetting( "projects_filethumbnail", 2 );
				$intelliScaling	= $clerk->getSetting( "projects_intelliscaling", 1 );
				$location		= PROJECTS_PATH . $project['slug'] . "/";
				
				$thumbnail		= dynamicThumbnail( $thumbFile, $location, $thumbWidth, $thumbHeight, $intelliScaling, "short" );
								
				switch ( $data['type'] )
				{
					case "image":
							$thumbWidth= ( $thumbWidth == 0 ) ? "auto" : $thumbWidth;
							$thumbHeight= ( $thumbHeight == 0 ) ? "auto" : $thumbHeight;
							
							$slides.= '<div class="file" id="file' . $data['id'] . '">
											<a class="popper" onclick="popper(\''. $data['id'] . '\', \'' . $width . '\', \'' . $height . '\');return false;" href="#"><img src="' . $thumbnail . '" width="' . $thumbWidth . '" height="' . $thumbHeight . '" alt="' . $fullsize . '" alt="" /></a>';
							break;
					case "video":
							$title= ( empty( $data['title'] ) ) ? "Video" : $data['title'];
							$mediaThumb= ( empty( $data['thumbnail'] ) ) ? $title : '<img src="' . $thumbnail . '" width="' . $thumbWidth . '" height="' . $thumbHeight . '" />';

							$slides.= '<div class="file" id="file' . $data['id'] . '"><a class="popper" href="#" onclick="popper(\''. $data['id'] . '\', \'' . $width . '\', \'' . $height . '\', true);return false;">' . $mediaThumb . '</a><div class="popcontent">' . mediaplayer( $data, $project ) . '</div>';
							break;
					case "audio":
							$title= ( empty( $data['title'] ) ) ? "Audio" : $data['title'];
							$mediaThumb= ( empty( $data['thumbnail'] ) ) ? $title : '<img src="' . $thumbnail . '" width="' . $thumbWidth . '" height="' . $thumbHeight . '" />';
							
							$slides.= '<div class="file" id="file' . $data['id'] . '"><a class="popper" href="#" onclick="popper(\''. $data['id'] . '\', \'' . $width . '\', \'' . $height . '\', true);return false;">' . $mediaThumb . '</a><div class="popcontent">' . audioplayer( $data, $project ) . '</div>';
							break;
				}
				
				if ( $clerk->getSetting( "projects_hideFileInfo", 1 ) == false  && ( !empty( $data['title'] ) || !empty( $data['caption'] ) ) )
				{
					$info_html= '<div class="info">
								<span class="title">' . $data['title'] . '</span>
								<span class="caption">' . html_entity_decode( $data['caption'] ) . '</span>';
					
					$info_html= call_anchor( "pop_info", array( 'html' => $info_html, 'file' => $data ) );
					
					$info= $info_html['html'] . '</div>';
				}
				
				$slides.= $info . '</div>';
			}
			
			$info= "";
		}
		
		return $slides;
	}
?>