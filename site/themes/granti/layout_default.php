<?php include_once "header.php"; ?>
					<?php
						$pageText= pageText();
						if ( !empty( $pageText ) ):
					?>
					<div class="page-text">
						<?php echo pageText(); ?>
					</div>
					<?php endif; ?>
					<div class="page-content">
						<?php
							if ( $_GET['project_tags'] )
								echo projects();
							else
								echo pageContent();
						?>
					</div>

<?php include_once "footer.php"; ?>