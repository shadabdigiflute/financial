<?php
/**
 * Single Post Details Template - Meridian Global Finance
 */

get_header();

if (have_posts()) : while (have_posts()) : the_post();
    $cats = get_the_category();
    $main_cat = !empty($cats) ? $cats[0] : null;
    $kicker = $main_cat ? $main_cat->name : 'ANALYSIS';
?>

<main id="main" class="container-x mx-auto px-3 py-4">
  <!-- Breadcrumb -->
  <nav aria-label="Breadcrumb" class="mb-3 text-uppercase tracking-wide text-faint" style="font-size:11px">
    <a href="<?php echo esc_url(home_url('/')); ?>" class="text-faint">Home</a>
    <?php if ($main_cat) : ?>
      <span class="mx-2">/</span>
      <a href="<?php echo esc_url(get_category_link($main_cat->term_id)); ?>" class="text-faint"><?php echo esc_html($main_cat->name); ?></a>
    <?php endif; ?>
  </nav>

  <div class="row g-4">
    <article class="col-12 col-lg-8">
      <header class="border-bottom rule-strong pb-4">
        <div class="d-flex flex-wrap align-items-center gap-2">
          <span class="kicker"><?php echo esc_html($kicker); ?></span>
        </div>

        <h1 class="font-serif fw-black mt-2" style="font-size:38px;line-height:1.08;letter-spacing:-.02em">
          <?php the_title(); ?>
        </h1>

        <?php if (has_excerpt()) : ?>
          <p class="font-serif text-soft mt-3" style="font-size:19px;line-height:1.45;max-width:720px">
            <?php echo get_the_excerpt(); ?>
          </p>
        <?php endif; ?>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 border-top rule pt-3 mt-3">
          <div class="text-uppercase tracking-wide text-faint" style="font-size:12px">
            <span class="fw-semibold text-ink">By <?php the_author(); ?></span>
            <span class="mx-2">·</span>
            <span>Published <?php echo get_the_time('F j, Y'); ?></span>
          </div>
          <div class="d-flex align-items-center gap-3 text-accent fw-semibold text-uppercase tracking-wide" style="font-size:12px">
            <button type="button" class="btn btn-link p-0 text-accent text-decoration-none">Save</button>
            <button type="button" class="btn btn-link p-0 text-accent text-decoration-none">Gift</button>
            <button type="button" class="btn btn-link p-0 text-accent text-decoration-none">Share</button>
          </div>
        </div>
      </header>

      <?php if (has_post_thumbnail()) : ?>
        <div class="my-4">
          <?php the_post_thumbnail('large', array('class' => 'img-fluid w-100 border rule')); ?>
          <?php if (get_the_post_thumbnail_caption()) : ?>
            <span class="text-faint d-block mt-2" style="font-size:12px"><?php the_post_thumbnail_caption(); ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Article Body -->
      <div class="article-body mt-4">
        <?php the_content(); ?>
      </div>

      <div class="my-4 article-body">
        <?php echo meridian_ad_slot(728, 90); ?>
      </div>

      <!-- Related Stories -->
      <section class="border-top rule-strong pt-4">
        <?php echo meridian_section_head("Related Stories"); ?>
        <div class="row">
          <?php
          $related_args = array(
              'posts_per_page' => 4,
              'post__not_in'   => array(get_the_ID()),
              'post_status'    => 'publish'
          );
          if ($main_cat) {
              $related_args['category__in'] = array($main_cat->term_id);
          }
          $related_query = new WP_Query($related_args);
          if ($related_query->have_posts()) :
              while ($related_query->have_posts()) : $related_query->the_post();
                  $r_cats = get_the_category();
                  $r_kicker = !empty($r_cats) ? $r_cats[0]->name : '';
                  ?>
                  <div class="col-12 col-sm-6">
                    <article class="headline">
                      <a href="<?php the_permalink(); ?>">
                        <?php if ($r_kicker) : ?>
                          <span class="kicker"><?php echo esc_html($r_kicker); ?></span>
                        <?php endif; ?>
                        <h3><?php the_title(); ?></h3>
                        <span class="time"><?php echo get_the_time('M j, Y'); ?></span>
                      </a>
                    </article>
                  </div>
                  <?php
              endwhile;
              wp_reset_postdata();
          endif;
          ?>
        </div>
      </section>

      <!-- Comments template -->
      <?php
      if (comments_open() || get_comments_number()) :
          comments_template();
      endif;
      ?>
    </article>

    <!-- Sidebar -->
    <aside class="col-12 col-lg-4">
      <?php get_sidebar(); ?>
    </aside>
  </div>
</main>

<?php
endwhile; endif;

get_footer();
