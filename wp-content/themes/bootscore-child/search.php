<?php
/**
 * Search Results Template - Meridian Global Finance
 */

get_header();

$search_query = get_search_query();
?>

<main id="main" class="container-x mx-auto px-3 py-4">
  <header class="pb-3" style="border-bottom:2px solid var(--ink)">
    <p class="text-accent fw-bold text-uppercase tracking-wider" style="font-size:11px">Search</p>
    <form class="d-flex align-items-center gap-2 border-bottom rule-ink pb-1 mt-2" method="get" action="<?php echo esc_url(home_url('/')); ?>">
      <input name="s" type="search" class="form-control border-0 bg-transparent shadow-none font-serif fw-bold p-0"
             style="font-size:24px" placeholder="Search markets, companies and topics…" value="<?php echo esc_attr($search_query); ?>" />
      <button class="btn text-white bg-accent rounded-0 fw-semibold text-uppercase tracking-wide flex-shrink-0" style="font-size:13px">Search</button>
    </form>
  </header>

  <div class="row g-4 mt-1">
    <div class="col-12 col-lg-8">
      <?php if (have_posts()) : ?>
        <p class="text-faint text-uppercase tracking-wide mb-3" style="font-size:13px">
          <?php
          global $wp_query;
          echo esc_html($wp_query->found_posts) . ' result' . ($wp_query->found_posts === 1 ? '' : 's') . ' for "' . esc_html($search_query) . '"';
          ?>
        </p>
        <div class="row">
          <?php
          $s_count = 0;
          while (have_posts()) : the_post();
              $s_count++;
              $s_cats = get_the_category();
              $s_kicker = !empty($s_cats) ? $s_cats[0]->name : '';
              ?>
              <div class="col-12 col-sm-6">
                <article class="headline">
                  <a href="<?php the_permalink(); ?>">
                    <?php if ($s_kicker) : ?>
                      <span class="kicker"><?php echo esc_html($s_kicker); ?></span>
                    <?php endif; ?>
                    <h3><?php the_title(); ?></h3>
                    <span class="time"><?php echo get_the_time('M j, Y'); ?></span>
                  </a>
                </article>
                <?php if ($s_count === 6) : ?>
                  <div class="my-3 d-none d-sm-block">
                    <?php echo meridian_ad_slot(300, 250); ?>
                  </div>
                <?php endif; ?>
              </div>
          <?php endwhile; ?>
        </div>

        <div class="mt-4">
          <?php
          the_posts_pagination(array(
              'mid_size'  => 2,
              'prev_text' => __('« Previous', 'bootscore-child'),
              'next_text' => __('Next »', 'bootscore-child'),
          ));
          ?>
        </div>
      <?php else : ?>
        <div class="col-12">
          <div class="text-center p-5 bg-card border border-dashed rule-strong">
            <p class="font-serif fw-bold mb-1" style="font-size:20px">No matching stories</p>
            <p class="text-soft" style="font-size:14px">Try a broader term, or browse a section.</p>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="fw-semibold text-accent mt-2 d-inline-block">← Back to the front page</a>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="col-12 col-lg-4">
      <?php get_sidebar(); ?>
    </div>
  </div>
</main>

<?php
get_footer();
