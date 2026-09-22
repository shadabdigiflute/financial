<?php
/**
 * Main Index Template - Meridian Global Finance
 */

if (file_exists(get_stylesheet_directory() . '/front-page.php')) {
    include get_stylesheet_directory() . '/front-page.php';
} else {
    get_header();
    ?>
    <main id="main" class="container-x mx-auto px-3 py-4">
      <div class="row g-4">
        <div class="col-12 col-lg-8">
          <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article class="headline">
              <a href="<?php the_permalink(); ?>">
                <h3><?php the_title(); ?></h3>
                <span class="time"><?php echo get_the_time('M j, Y'); ?></span>
              </a>
            </article>
          <?php endwhile; endif; ?>
        </div>
        <div class="col-12 col-lg-4">
          <?php get_sidebar(); ?>
        </div>
      </div>
    </main>
    <?php
    get_footer();
}
