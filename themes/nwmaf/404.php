<?php get_header(); ?>
	<div class="entry">
		<article>
			<div class="post-padding-container error-msg">
				<div class="post-header error--header">
					<h1 class='post-title error-msg--title'>Well, this is awkward</h1>
				</div>
				<div class="post-content error-msg--container">
					<div class="error-msg--msg">
						<p>We can't seem to find what you're looking for.</p>
						<p>Double-check for typos or try the search form below.</p>
					</div>

					<!-- <?php //_e('We can\'t find that. Double-check for typos or try the search form below to find what you were looking for.', 'nwmaf'); ?> -->
				</div>

				<?php get_search_form(); ?>

			</div>
		</article>
	</div>
<?php get_footer(); ?>
