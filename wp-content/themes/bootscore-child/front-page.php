<?php
/**
 * Front Page Template - Meridian Global Finance
 */

get_header();
?>

<main id="main" class="container-x mx-auto px-3 py-4">
  <div class="row g-4">
    <div class="col-12 col-lg-8">

      <!-- Lead Section -->
      <?php
      $lead_query = new WP_Query(array('posts_per_page' => 5, 'post_status' => 'publish'));
      if ($lead_query->have_posts()) :
          $lead_posts = $lead_query->posts;
          $main_lead = array_shift($lead_posts);
          $main_lead_id = $main_lead->ID;
          $cats = get_the_category($main_lead_id);
          $kicker = !empty($cats) ? $cats[0]->name : 'GLOBAL MACRO';
      ?>
      <section class="border-bottom rule-strong pb-4">
        <div class="row g-4">
          <div class="col-12 col-md-7">
            <a href="<?php echo esc_url(get_permalink($main_lead_id)); ?>" class="d-block">
              <span class="text-accent2 fw-bold text-uppercase tracking-wider" style="font-size:11px">
                <?php echo esc_html($kicker); ?>
              </span>
              <h1 class="font-serif fw-black mt-2" style="font-size:34px;line-height:1.08;letter-spacing:-.02em">
                <?php echo esc_html($main_lead->post_title); ?>
              </h1>
              <p class="text-soft mt-2" style="font-size:16px;line-height:1.55">
                <?php echo esc_html(wp_trim_words(get_the_excerpt($main_lead_id), 24, '...')); ?>
              </p>
              <div class="mt-2 text-faint text-uppercase tracking-wide d-flex gap-3" style="font-size:11px">
                <span>By <?php echo esc_html(get_the_author_meta('display_name', $main_lead->post_author)); ?></span>
                <span><?php echo esc_html(get_the_time('M j, Y', $main_lead_id)); ?></span>
              </div>
            </a>
          </div>

          <!-- Lead List -->
          <div class="col-12 col-md-5 border-start rule ps-md-4">
            <?php echo meridian_section_head("Latest Stories"); ?>
            <?php
            foreach ($lead_posts as $sub_post) {
                $sub_cats = get_the_category($sub_post->ID);
                $sub_kicker = !empty($sub_cats) ? $sub_cats[0]->name : '';
                ?>
                <article class="headline">
                  <a href="<?php echo esc_url(get_permalink($sub_post->ID)); ?>">
                    <?php if ($sub_kicker) : ?>
                      <span class="kicker"><?php echo esc_html($sub_kicker); ?></span>
                    <?php endif; ?>
                    <h3><?php echo esc_html($sub_post->post_title); ?></h3>
                    <span class="time"><?php echo esc_html(get_the_time('g:i A', $sub_post->ID)); ?></span>
                  </a>
                </article>
                <?php
            }
            ?>
          </div>
        </div>
      </section>
      <?php wp_reset_postdata(); endif; ?>

      <div class="my-4">
        <?php echo meridian_ad_slot(728, 90); ?>
      </div>

      <!-- Markets Wrap -->
      <section class="border-bottom rule-strong py-4">
        <?php echo meridian_section_head("Markets Wrap", home_url('/?s=markets')); ?>
        <div class="row">
          <?php
          $markets_query = new WP_Query(array('posts_per_page' => 3, 'offset' => 1, 'post_status' => 'publish'));
          if ($markets_query->have_posts()) :
              while ($markets_query->have_posts()) : $markets_query->the_post();
                  $m_cats = get_the_category();
                  $m_kicker = !empty($m_cats) ? $m_cats[0]->name : 'MARKETS';
                  ?>
                  <div class="col-12 col-sm-6 col-lg-4">
                    <article class="headline">
                      <a href="<?php the_permalink(); ?>">
                        <span class="kicker"><?php echo esc_html($m_kicker); ?></span>
                        <h3><?php the_title(); ?></h3>
                        <span class="time"><?php echo get_the_time('g:i A'); ?></span>
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

      <!-- Section Grid (Categories) -->
      <div class="row g-4 mt-1">
        <?php
        $cats_to_show = get_categories(array('number' => 4, 'hide_empty' => false));
        $sec_count = 0;

        foreach ($cats_to_show as $cat) {
            $sec_count++;
            $cat_posts = get_posts(array('category' => $cat->term_id, 'posts_per_page' => 3));
            ?>
            <div class="col-12 col-sm-6">
              <section>
                <?php echo meridian_section_head($cat->name, get_category_link($cat->term_id)); ?>
                <?php
                if (!empty($cat_posts)) {
                    foreach ($cat_posts as $idx => $cp) {
                        ?>
                        <article class="headline">
                          <a href="<?php echo esc_url(get_permalink($cp->ID)); ?>">
                            <h3 class="<?php echo $idx === 0 ? 'big' : ''; ?>"><?php echo esc_html($cp->post_title); ?></h3>
                            <span class="time"><?php echo esc_html(get_the_time('M j, Y', $cp->ID)); ?></span>
                          </a>
                        </article>
                        <?php
                    }
                } else {
                    echo '<p class="text-soft" style="font-size:13px">No articles in this category yet.</p>';
                }
                ?>
              </section>
              <?php if ($sec_count === 4) : ?>
                <div class="mt-4 d-none d-sm-block">
                  <?php echo meridian_ad_slot(300, 250); ?>
                </div>
              <?php endif; ?>
            </div>
            <?php
        }
        ?>
      </div>

      <div class="my-4">
        <?php echo meridian_ad_slot(728, 90); ?>
      </div>

      <!-- Opinion + Video -->
      <div class="row g-4">
        <section class="col-12 col-sm-6 bg-card p-4">
          <?php echo meridian_section_head("Opinion & Analysis", home_url('/?s=opinion')); ?>
          <?php
          $op_posts = get_posts(array('posts_per_page' => 4, 'post_status' => 'publish'));
          foreach ($op_posts as $i => $op) {
              ?>
              <article class="headline">
                <a href="<?php echo esc_url(get_permalink($op->ID)); ?>">
                  <span class="kicker">OPINION</span>
                  <h3 class="<?php echo $i === 0 ? 'big' : ''; ?>"><?php echo esc_html($op->post_title); ?></h3>
                  <span class="time"><?php echo esc_html(get_the_time('M j, Y', $op->ID)); ?></span>
                </a>
              </article>
              <?php
          }
          ?>
        </section>

        <section class="col-12 col-sm-6">
          <?php echo meridian_section_head("Video & Audio"); ?>
          <?php
          $vid_posts = get_posts(array('posts_per_page' => 3, 'post_status' => 'publish'));
          foreach ($vid_posts as $v_post) {
              ?>
              <article class="headline">
                <a href="<?php echo esc_url(get_permalink($v_post->ID)); ?>" class="d-flex align-items-start gap-3">
                  <span class="bg-charcoal text-white fw-bold tabular px-2 py-1 flex-shrink-0" style="font-size:11px;border-radius:2px">▶ 03:45</span>
                  <span>
                    <span class="font-serif d-block fw-semibold" style="font-size:15px;line-height:1.3"><?php echo esc_html($v_post->post_title); ?></span>
                    <span class="time d-block mt-1"><?php echo esc_html(get_the_time('M j, Y', $v_post->ID)); ?></span>
                  </span>
                </a>
              </article>
              <?php
          }
          ?>
        </section>
      </div>

    </div>

    <!-- Sidebar -->
    <div class="col-12 col-lg-4">
      <?php get_sidebar(); ?>
    </div>
  </div>
</main>

<?php
get_footer();
