<?php
/**
 * Fallback template: WP uses it when nothing more specific matches
 * (blog posts, archives, search). The site only has pages for now,
 * so it looks like a text page with a list of posts.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
	<main class="flex-auto">
		<div class="flex flex-col gap-15 lg:gap-20 pt-7 pb-30 lg:py-20">
			<div class="ui-container">
				<div class="max-w-190">
					<?php if ( is_singular() ) : ?>
						<?php while ( have_posts() ) : ?>
							<?php the_post(); ?>
							<h1 class="t-h2-m lg:t-display-m text-dark mb-6"><?php the_title(); ?></h1>
							<div class="ui-content"><?php the_content(); ?></div>
						<?php endwhile; ?>
					<?php else : ?>
						<h1 class="t-h2-m lg:t-display-m text-dark mb-6">
							<?php echo esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?>
						</h1>
						<div class="ui-content">
							<?php if ( have_posts() ) : ?>
								<ul>
									<?php while ( have_posts() ) : ?>
										<?php the_post(); ?>
										<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
									<?php endwhile; ?>
								</ul>
								<?php the_posts_pagination(); ?>
							<?php else : ?>
								<p>Nothing found.</p>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</main>
<?php
get_footer();
