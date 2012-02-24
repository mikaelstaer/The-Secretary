<?php
	$blog				= "";
	$blog_query_details	= array();
	
	// Paths
	$paths = $clerk->getSetting( "blog_path" );
	
	define( "BLOG_PATH", $paths['data1'] );
	define( "BLOG_URL", $paths['data2'] );
	
	function blog( $options= "" )
	{
		global $clerk, $blog, $blog_query_details;
		
		if ( !is_array( $options ) )
		{
			$options= prepare_settings( $options );
		}
		
		// Defaults
		$defaults	= array(
				'id'			=>	'',
				'template'		=>	'',
				'show'			=>	'small',
				'sticky'		=>	'true',
				'order'			=>	'date',
				'orderHow'		=>	'desc',
				'limit'			=>	'',
				'func'			=>	''
		);
		
		$options= merge_settings( $options, $defaults );
		
		if ( !empty( $options['func'] ) )
		{
			if ( is_callable( $options['func'] ) )
			{
				ob_start();
				call_user_func( $options['func'], $options );
				$contents= ob_get_contents();
				ob_end_clean();

				return $contents;
			}
		}
		
		if ( !empty( $_GET[getRemappedVar("blog")] ) && $options['sticky'] == "false" )
		{
			return;
		}
		
		if ( empty( $options['template'] ) )
		{
			if ( $options['show'] == "big" )
			{
				$options['template']= "blog_view.html";
			}
			else
			{
				$options['template']= "blog_list.html";
			}
			
			if ( $options['sticky'] == "true" )
			{
				$options['template']= ( $options['show'] == "small" ) ? "blog_list.html" : "blog_view.html";
			}
		}
		
		if ( file_exists( HQ . "site/themes/" . THEME . "/templates/" . $options['template'] ) == false )
		{
			return "Oops! Looks like your theme is missing the template file, <em>" . $options['template'] . "</em><br /><br />Create this file and upload it to the \"templates\" folder in your theme's folder. Don't forget to fill it with template tags and your custom HTML!";
		}
		
		if ( !empty( $options['id'] ) )
		{
			$id= $options['id'];
			$where= "id= '$id' OR slug= '$id' OR title= '$id'";
		}
		
		$options['orderHow']= strtoupper( $options['orderHow'] );
		
		# Pagination
		$blog_query_details['limit'] = $options['limit'];
		
		if ( isset( $_GET['p'] ) )
		{
			$page				= ( !is_numeric( $_GET['p']) ) ? 1 : (int) $_GET['p'];
			$offset				= ( $page - 1 ) * $options['limit'];
			$options['limit']	= " $offset, " . $options['limit'];
		}
		
		$order= ( $options['order'] == "random" ) ? "ORDER BY rand()" : "ORDER BY " . $options['order'] . " " . $options['orderHow'];
		$limit= ( empty( $options['limit'] ) ) ? "" : "LIMIT " . $options['limit'];
		
		$where= ( empty( $where ) ) ? "WHERE status= 1" : "WHERE (" . $where . ") AND status= 1";
		
		ob_start();
		
		if ( empty( $options['id'] ) && postSelected() )
		{
			$get= $clerk->query_select( "secretary_blog", "", "$where $order $limit" );
			while ( $blog= $clerk->query_fetchArray( $get ) )
			{
				include HQ . "site/themes/" . THEME . "/templates/" . $options['template'];
			}
			
			$blog_query_details['total']= $clerk->query_countRows( $get );
		}
		elseif ( !empty( $options['id'] ) && postSelected() )
		{
			$options['template']= "blog_view.html";
			
			$get= $clerk->query_select( "secretary_blog", "", "$where $order $limit" );
			while ( $blog= $clerk->query_fetchArray( $get ) )
			{
				include HQ . "site/themes/" . THEME . "/templates/" . $options['template'];
			}
		}
		else
		{	
			$get = $clerk->query_select( "secretary_blog", "", "$where $order $limit" );
			$blog_query_details['total'] = $clerk->query_numRows( $clerk->query_select( "secretary_blog", "", $where ) );
			
			while ( $blog= $clerk->query_fetchArray( $get ) )
			{
				include HQ . "site/themes/" . THEME . "/templates/" . $options['template'];
			}
		}
		
		$contents= ob_get_contents();
		ob_end_clean();
		
		return $contents;
	}
	
	function postSelected()
	{
		return ( !empty( $_GET[getRemappedVar("blog")] ) || !empty( $_GET['id'] ) );
	}
	
	function selectedPost()
	{
		if ( !empty( $_GET['id'] ) )
			return $_GET['id'];
			
		if ( !empty( $_GET[getRemappedVar("blog")] ) )
			return $_GET[getRemappedVar("blog")];
	}
	
	function show_post()
	{
		if ( postSelected() == false )
		{
			return;
		}
		else
		{
			$settings= prepare_settings( "show= big, id= ". selectedPost() );
			echo blog( $settings );
		}
	}
	
	function postView()
	{
		return show_post();
	}
	
	function postTitle()
	{
		global $blog;
		
		return $blog['title'];
	}
	
	function postSlug()
	{
		global $blog;
		
		return $blog['slug'];
	}
	
	function postId()
	{
		global $blog;
		
		return $blog['id'];
	}
	
	function postTextFull()
	{
		return post_text();
	}
	
	function post_text()
	{
		global $blog;
		
		$post= call_anchor( "blogPostModify", array(
									'original'	=>	$blog['post'],
									'modified'	=>	textOutput( $blog['post'] )
							)
		);
		
		return str_replace( "{more}", '<a name="more"></a>', $post['modified'] );
	}
	
	function post_excerpt()
	{
		global $blog;
		
		if ( selectedPost() == postSlug() || selectedPost() == postId() )
		{
			return post_text();
		}
		else
		{
			$post= call_anchor( "blogPostModify", array(
										'original'	=>	$blog['post'],
										'modified'	=>	textOutput( $blog['post'] )
								)
			);

			$pieces= explode( "{more}", $post['modified'] );

			return $pieces[0];
		}
	}
	
	function postText()
	{
		return post_excerpt();
	}
	
	function postDate( $format= "d. F Y" )
	{
		global $blog;
		
		return date( $format, $blog['date'] );
	}
	
	function postImage()
	{
		global $blog;
		
		return ( empty( $blog['image'] ) == false ) ? '<img src="' . BLOG_URL . $blog['slug'] . "/" . $blog['image'] . '" alt="" />' : "";
	}
	
	function getPostImage( $fullUrl= false )
	{
		global $blog;
		
		return ( $fullUrl ) ? BLOG_URL . $blog['slug'] . "/" . $blog['image'] : $blog['image'];
	}
	
	function postImageThumb()
	{
		global $clerk, $blog;
		
		$extension= substr( $blog['image'], strrpos( $blog['image'], '.' ) );
		$thumb=	str_replace( $extension, "", $blog['image'] );
		
		if ( empty( $blog['image'] ) )
		{
			return '';
		}
		else
		{
			$thumbnail= $blog['image'];
			$width= $clerk->getSetting( "blog_thumbnail", 1 );
			$height= $clerk->getSetting( "blog_thumbnail", 2 );
			$intelliScaling= $clerk->getSetting( "blog_intelliscaling", 1 );
			$location= BLOG_PATH . $blog['slug'] . "/";
			
			return dynamicThumbnail( $thumbnail, $location, $width, $height, $intelliScaling );
		}
	}
	
	function getPostImageThumb( $fullUrl= false )
	{
		global $clerk, $blog;
		
		$extension= substr( $blog['image'], strrpos( $blog['image'], '.' ) );
		$thumb=	str_replace( $extension, "", $blog['image'] );
		
		if ( empty( $blog['image'] ) )
		{
			return '';
		}
		else
		{
			$thumbnail= $blog['image'];
			$width= $clerk->getSetting( "blog_thumbnail", 1 );
			$height= $clerk->getSetting( "blog_thumbnail", 2 );
			$intelliScaling= $clerk->getSetting( "blog_intelliscaling", 1 );
			$location= BLOG_PATH . $blog['slug'] . "/";
			
			return dynamicThumbnail( $thumbnail, $location, $width, $height, $intelliScaling, "small" );
		}
	}
	
	function postInfo( $id= "" )
	{
		global $clerk, $blog;
		
		if ( empty( $id ) )
		{
			return $blog;
		}
		else
		{
			return $clerk->query_fetchArray( $clerk->query_select( "secretary_blog", "", "WHERE id= '$id' OR slug= '$id'" ) );
		}
	}
	
	function post_link( $toMore= true, $id= "" )
	{
		global $clerk;
		
		$cleanUrls= (bool) $clerk->getSetting( "clean_urls", 1 );
		
		if ( empty( $id ) == false )
		{
			$info= postInfo( $id );
			$slug= $info['slug'];
		}
		else
		{
			$slug= postSlug();
		}
		
		$more= ( $toMore ) ? "#more" : "";
		
		if ( postSelected() )
		{
			return ( $cleanUrls == true ) ? $clerk->getSetting( "site", 2 ) . '/' . PAGE . '/' . $slug . $more : $clerk->getSetting( "site", 2 ) . '?' . getRemappedVar( "pages" ) . '=' . PAGE . '&amp;' . getRemappedVar( "id" ) . '=' . $slug . $more;
		}
		else
		{
			return ( $cleanUrls == true ) ? $clerk->getSetting( "site", 2 ) . '/' . PAGE . '/' . $slug . $more : $clerk->getSetting( "site", 2 ) . '?' . getRemappedVar( "pages" ) . '=' . PAGE . '&amp;' . getRemappedVar( "id" ) . '=' . $slug . $more;
		}
	}
	
	function linkToPost( $toMore= true, $id= "" )
	{
		return post_link( $toMore, $id );
	}
	
	function blog_rss()
	{
		global $clerk, $blog;
		
		$feed= new FeedWriter(RSS2);
			
		$title= $clerk->getSetting( "site", 1 );
		$feed->setTitle( $title . ' / Blog Feed' );
		$feed->setLink( linkToSite() );
		$feed->setDescription('Live feed of blog posts on ' . $title );

		$feed->setChannelElement('pubDate', date( DATE_RSS, time() ) );

		$get= $clerk->query_select( "secretary_blog", "", "ORDER BY date DESC" );
		while ( $blog= $clerk->query_fetchArray( $get ) )
		{
			$newItem= $feed->createNewItem();

			$newItem->setTitle( $blog['title'] );
			$newItem->setLink( html_entity_decode( linkToPost( false, $blog['id'] ) ) );
			$newItem->setDate( $blog['date'] );

			$desc= postImage() . '<br />' . postText();
			$desc= call_anchor( "blogRssDescription", $desc );

			$newItem->setDescription('' . $desc . '');
			$newItem->addElement( 'guid', linkToPost(), array('isPermaLink'=>'true') );

			$feed->addItem( $newItem );

			$count= 0;
			$desc= "";
		}

		$feed->genarateFeed();
	}
	
	function next_post ( $return_data = false )
	{
		global $clerk, $blog;
		
		$post_list		= array();
		$current_post	= postInfo( selectedPost() );
		$current_index	= 0;
		$count			= 0;
	
		$page 			= pageInfo( currentPage() );
		$options 		= prepare_settings( $page['content_options'] );
		$defaults 		= array(
				'order'		=> 'date',
				'orderHow'	=> 'desc'
		);
		
		$options		= merge_settings( $options, $defaults );
		$posts 			= $clerk->query_select( "secretary_blog", "", "WHERE status= 1 ORDER BY {$options['order']} {$options['orderHow']}" );
		
		while ( $post= $clerk->query_fetchArray( $posts ) )
		{
			$post_list[] = $post;
			if ( $post['id'] == $current_post['id'] ) {
				$current_index= $count;
			}
			
			$count++;
		}
		
		if ( $current_index == count( $post_list ) - 1 ) {
			$next_post= $post_list[0];
		} else {
			$next_post= $post_list[$current_index + 1];
		}
		
		if ( $return_data ) {
			$next_post['link']= post_link( false, $next_post['id'] );
			return $next_post;
		}
		
		return post_link( false, $next_post['id'] );
	}
	
	function prev_post( $return_data = false )
	{
		global $clerk, $blog;
		
		$post_list		= array();
		$current_post	= postInfo( selectedPost() );
		$current_index	= 0;
		$count			= 0;
		
		$page 			= pageInfo( currentPage() );
		$options 		= prepare_settings( $page['content_options'] );
		$defaults 		= array(
				'order'		=> 'date',
				'orderHow'	=> 'desc'
		);
		
		$options		= merge_settings( $options, $defaults );
		$posts 			= $clerk->query_select( "secretary_blog", "", "WHERE status= 1 ORDER BY {$options['order']} {$options['orderHow']}" );
		
		while ( $post= $clerk->query_fetchArray( $posts ) )
		{
			$post_list[] = $post;
			if ( $post['id'] == $current_post['id'] ) {
				$current_index= $count;
			}
			
			$count++;
		}
		
		if ( $current_index == 0 ) {
			$prev_post= $post_list[count( $post_list ) - 1];
		} else {
			$prev_post= $post_list[$current_index - 1];
		}
		
		if ( $return_data ) {
			$prev_post['link']= post_link( false, $prev_post['id'] );
			return $prev_post;
		}
		
		return post_link( false, $prev_post['id'] );
	}
	
	function blog_pagination()
	{
		global $clerk, $blog_query_details;
		
		if ( $blog_query_details['limit'] == 0 || empty( $blog_query_details['limit'] ) ) return;
		
		$clean_urls		= (bool) $clerk->getSetting( "clean_urls", 1 );
		$total			= $blog_query_details['total'];
		$total_pages 	= ceil( $total / $blog_query_details['limit'] );
		$currentpage	= ( !isset( $_GET['p'] ) || !is_numeric( $_GET['p']) ) ? 1 : (int) $_GET['p'];
		
		if ( $total_pages <= 1 ) return;
		
		for ( $i= 1; $i <= $total_pages; $i++ )
		{
			if ( $i == $currentpage ) $selected = ' class="active"';
			$links 		.= '<a href="';
			$links 		.= ( $clean_urls == true ) ? $clerk->getSetting( "site", 2 ) . '/' . PAGE . '/page/' . $i : $clerk->getSetting( "site", 2 ) . '?' . getRemappedVar( "pages" ) . '=' . PAGE . '&amp;p=' . $i;
			$links 		.= '"' . $selected . '>' . $i . '</a> ';
			$selected 	 = "";
		}
		
		return $links;
	}
	
	function blog_next_page()
	{
		global $clerk, $blog_query_details;
		
		if ( $blog_query_details['limit'] == 0 || empty( $blog_query_details['limit'] ) ) return false;
		
		$clean_urls		= (bool) $clerk->getSetting( "clean_urls", 1 );
		$total			= $blog_query_details['total'];
		$total_pages 	= ceil( $total / $blog_query_details['limit'] );
		$currentpage	= ( !isset( $_GET['p'] ) || !is_numeric( $_GET['p']) ) ? 1 : (int) $_GET['p'];
		
		if ( $total_pages <= 1 ) return false;
		
		if ( $currentpage != $total_pages )
		{
			$nextpage = $currentpage + 1;
		   	return ( $clean_urls == true ) ? $clerk->getSetting( "site", 2 ) . '/' . PAGE . '/page/' . $nextpage : $clerk->getSetting( "site", 2 ) . '?' . getRemappedVar( "pages" ) . '=' . PAGE . '&amp;p=' . $nextpage;
		}
	}
	
	function blog_prev_page()
	{
		global $clerk, $blog_query_details;
		
		if ( $blog_query_details['limit'] == 0 || empty( $blog_query_details['limit'] ) ) return false;
		
		$clean_urls		= (bool) $clerk->getSetting( "clean_urls", 1 );
		$total			= $blog_query_details['total'];
		$total_pages 	= ceil( $total / $blog_query_details['limit'] );
		$currentpage	= ( !isset( $_GET['p'] ) || !is_numeric( $_GET['p']) ) ? 1 : (int) $_GET['p'];
		
		if ( $total_pages <= 1 ) return false;
		
		if ( $currentpage > 1 )
		{
			$prevpage = $currentpage - 1;
			return ( $clean_urls == true ) ? $clerk->getSetting( "site", 2 ) . '/' . PAGE . '/page/' . $prevpage : $clerk->getSetting( "site", 2 ) . '?' . getRemappedVar( "pages" ) . '=' . PAGE . '&amp;p=' . $prevpage;
		}
	}
?>