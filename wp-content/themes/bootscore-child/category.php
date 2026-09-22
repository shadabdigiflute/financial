<?php
/**
 * Category & Archive Template - Meridian Global Finance
 */

get_header();

$cat_title = is_category() ? single_cat_title('', false) : get_the_archive_title();
$cat_desc = is_category() ? category_description() : get_the_archive_description();
?>

<main id="main" class="container-x mx-auto px-3 py-4">
  <!-- Breadcrumb -->
  <nav aria-label="Breadcrumb" class="mb-3 text-uppercase tracking-wide text-faint" style="font-size:11px">
    <a href="<?php echo esc_url(home_url('/')); ?>" class="text-faint">Home</a>
    <span class="mx-2">/</span>
    <span class="text-ink"><?php echo esc_html($cat_title); ?></span>
  </nav>

  <!-- Category Header -->
  <header class="pb-3" style="border-bottom:2px solid var(--ink)">
    <h1 class="font-serif fw-black" style="font-size:38px;line-height:1;letter-spacing:-.02em"><?php echo esc_html($cat_title); ?></h1>
    <?php if ($cat_desc) : ?>
      <div class="text-soft mt-2" style="font-size:15px;line-height:1.5;max-width:640px"><?php echo wp_kses_post($cat_desc); ?></div>
    <?php else : ?>
      <p class="text-soft mt-2" style="font-size:15px;line-height:1.5;max-width:640px">
        Coverage and analysis of <?php echo esc_html(strtolower($cat_title)); ?>, macro developments, and market trends.
      </p>
    <?php endif; ?>
  </header>

  <div class="row g-4 mt-1">
    <div class="col-12 col-lg-8">
      <?php if (have_posts()) : ?>

        <!-- Lead Story -->
        <?php
        the_post();
        $cats = get_the_category();
        $kicker = !empty($cats) ? $cats[0]->name : 'SECTION';
        ?>
        <article class="border-bottom rule-strong pb-4">
          <a href="<?php the_permalink(); ?>" class="d-block">
            <span class="kicker"><?php echo esc_html($kicker); ?></span>
            <h2 class="font-serif fw-black mt-1" style="font-size:28px;line-height:1.12;letter-spacing:-.01em"><?php the_title(); ?></h2>
            <p class="text-soft mt-2" style="font-size:15px;line-height:1.5"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 30)); ?></p>
            <span class="time d-block mt-2"><?php echo get_the_time('F j, Y'); ?> · By <?php the_author(); ?></span>
          </a>
        </article>

        <div class="my-4">
          <?php echo meridian_ad_slot(728, 90); ?>
        </div>

        <!-- Category Grid -->
        <div class="row">
          <?php
          $post_count = 0;
          while (have_posts()) : the_post();
              $post_count++;
              $p_cats = get_the_category();
              $p_kicker = !empty($p_cats) ? $p_cats[0]->name : '';
              ?>
              <div class="col-12 col-sm-6">
                <article class="headline">
                  <a href="<?php the_permalink(); ?>">
                    <?php if ($p_kicker) : ?>
                      <span class="kicker"><?php echo esc_html($p_kicker); ?></span>
                    <?php endif; ?>
                    <h3><?php the_title(); ?></h3>
                    <span class="time"><?php echo get_the_time('F j, Y'); ?></span>
                  </a>
                </article>
                <?php if ($post_count === 6) : ?>
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
              'class'     => 'd-flex gap-2 pagination justify-content-center'
          ));
          ?>
        </div>

      <?php else : ?>
        <div class="text-center py-5">
          <p class="text-accent2 fw-bold text-uppercase tracking-wider" style="font-size:11px">Section</p>
          <h1 class="font-serif fw-black mt-2" style="font-size:30px">No stories found in this section</h1>
          <a href="<?php echo esc_url(home_url('/')); ?>" class="fw-semibold text-accent mt-3 d-inline-block">← Back to the front page</a>
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
