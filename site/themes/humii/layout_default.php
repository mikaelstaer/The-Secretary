<?php include_once "header.php"; ?>

				<?php
					if ( is_index() ):
				?>
				
				<div class="front-slideshow">
					<?php
						echo ( viewingProjectTags() == false ) ? projects( 'template= front_slideshow.html' ) : projects();
					?>
				</div>
				
				<?php
					else:
				?>
				
				<div class="page-text">
					<?php echo pageText(); ?>
				</div>
				
				<?php echo pageContent(); ?>
				
				<?php
					endif;
				?>

<?php include_once "footer.php"; ?>