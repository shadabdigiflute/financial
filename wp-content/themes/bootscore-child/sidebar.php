<?php
/**
 * Dynamic Sidebar Template - Right Rail
 */
?>
<aside class="d-flex flex-column gap-4">
  <div class="ad-sticky">
    <?php echo meridian_ad_slot(300, 250); ?>
  </div>

  <!-- In Brief -->
  <section class="card-box">
    <div class="hd d-flex align-items-center justify-content-between">
      In Brief
      <span class="text-faint fw-semibold text-uppercase tracking-wider" style="font-size:10px">The 5-minute wrap</span>
    </div>
    <ul class="list-unstyled m-0 px-3 py-1">
      <?php
      $brief_posts = get_posts(array('posts_per_page' => 5, 'post_status' => 'publish'));
      if (!empty($brief_posts)) {
          foreach ($brief_posts as $b_post) {
              echo '<li class="d-flex gap-2 py-2 border-bottom rule">';
              echo '<span class="flex-shrink-0 mt-2" style="width:6px;height:6px;border-radius:50%;background:var(--accent-2)"></span>';
              echo '<a href="' . esc_url(get_permalink($b_post->ID)) . '" class="text-soft" style="font-size:13px;line-height:1.35">' . esc_html(wp_trim_words($b_post->post_title, 12)) . '</a>';
              echo '</li>';
          }
      } else {
          $in_brief = array(
              "Jobless claims fall to 219k, below the 230k consensus estimate.",
              "Eurozone consumer confidence flash reading edges up to -12.8.",
              "Nvidia supplier lifts quarterly guidance, citing AI demand.",
              "Brent crude settles below $78 as OPEC+ signals more supply.",
              "Two-year Treasury yield touches a fresh four-month low of 3.58%."
          );
          foreach ($in_brief as $item) {
              echo '<li class="d-flex gap-2 py-2 border-bottom rule">';
              echo '<span class="flex-shrink-0 mt-2" style="width:6px;height:6px;border-radius:50%;background:var(--accent-2)"></span>';
              echo '<span class="text-soft" style="font-size:13px;line-height:1.35">' . esc_html($item) . '</span>';
              echo '</li>';
          }
      }
      ?>
    </ul>
  </section>

  <!-- Most Read -->
  <section>
    <?php echo meridian_section_head("Most Read"); ?>
    <ol class="list-unstyled most-read m-0">
      <?php
      $most_read_posts = get_posts(array('posts_per_page' => 5, 'orderby' => 'comment_count', 'post_status' => 'publish'));
      if (!empty($most_read_posts)) {
          $rank = 1;
          foreach ($most_read_posts as $mr_post) {
              echo '<li><span class="rank">' . $rank++ . '</span><a href="' . esc_url(get_permalink($mr_post->ID)) . '">' . esc_html($mr_post->post_title) . '</a></li>';
          }
      } else {
          $most_read = array(
              "Live markets: Stocks, bonds and the dollar react to the Fed minutes",
              "Explained: What a September rate cut means for mortgages and savers",
              "The 10 charts that define the second-half outlook",
              "Big Read: Inside the $2tn private-credit machine",
              "Lex: Why the chip cycle isn't over yet"
          );
          foreach ($most_read as $idx => $item) {
              echo '<li><span class="rank">' . ($idx + 1) . '</span><a href="#">' . esc_html($item) . '</a></li>';
          }
      }
      ?>
    </ol>
  </section>

  <!-- Today's Calendar -->
  <section class="card-box">
    <div class="hd dark">Today's Calendar</div>
    <ul class="list-unstyled m-0">
      <li class="d-flex align-items-baseline gap-3 px-3 py-2 border-bottom rule">
        <span class="text-accent fw-bold tabular" style="width:44px;font-size:12px">08:30</span>
        <span><span class="d-block fw-semibold text-ink" style="font-size:13px;line-height:1.3">US Initial Jobless Claims</span><span class="text-faint text-uppercase tracking-wide" style="font-size:11px">Weekly</span></span>
      </li>
      <li class="d-flex align-items-baseline gap-3 px-3 py-2 border-bottom rule">
        <span class="text-accent fw-bold tabular" style="width:44px;font-size:12px">10:00</span>
        <span><span class="d-block fw-semibold text-ink" style="font-size:13px;line-height:1.3">Eurozone Consumer Confidence</span><span class="text-faint text-uppercase tracking-wide" style="font-size:11px">Flash</span></span>
      </li>
      <li class="d-flex align-items-baseline gap-3 px-3 py-2 border-bottom rule">
        <span class="text-accent fw-bold tabular" style="width:44px;font-size:12px">14:00</span>
        <span><span class="d-block fw-semibold text-ink" style="font-size:13px;line-height:1.3">Fed Speakers — Regional Presidents</span><span class="text-faint text-uppercase tracking-wide" style="font-size:11px">3 events</span></span>
      </li>
      <li class="d-flex align-items-baseline gap-3 px-3 py-2 border-bottom rule">
        <span class="text-accent fw-bold tabular" style="width:44px;font-size:12px">19:50</span>
        <span><span class="d-block fw-semibold text-ink" style="font-size:13px;line-height:1.3">Japan Q2 GDP (revised)</span><span class="text-faint text-uppercase tracking-wide" style="font-size:11px">QoQ</span></span>
      </li>
    </ul>
  </section>

  <!-- Newsletter -->
  <section class="card-box p-4">
    <h2 class="font-serif fw-bold m-0" style="font-size:17px">The Opening Bell</h2>
    <p class="text-soft mt-1" style="font-size:13px;line-height:1.5">Global markets, macro and the day's must-reads — in your inbox before the open.</p>
    <form onsubmit="return false" class="d-flex flex-column gap-2 mt-2">
      <input type="email" required placeholder="you@example.com" class="form-control rounded-0 border rule-strong" style="font-size:14px">
      <button class="btn text-white bg-accent rounded-0 fw-semibold text-uppercase tracking-wide" style="font-size:14px">Subscribe</button>
    </form>
  </section>

  <!-- Trending Tags -->
  <section>
    <?php echo meridian_section_head("Trending"); ?>
    <div class="d-flex flex-wrap gap-2">
      <?php
      $tags = get_tags(array('number' => 10, 'orderby' => 'count', 'order' => 'DESC'));
      if (!empty($tags)) {
          foreach ($tags as $tag) {
              echo '<a class="chip" href="' . esc_url(get_tag_link($tag->term_id)) . '">#' . esc_html($tag->name) . '</a>';
          }
      } else {
          $trending_defaults = array("Fed Minutes", "Rate Cut", "US 10Y Yield", "Dollar Index", "OPEC+", "Gold Record", "Private Credit", "AI Capex", "Bitcoin ETF", "Yen");
          foreach ($trending_defaults as $t) {
              echo '<a class="chip" href="' . esc_url(home_url('/?s=' . urlencode($t))) . '">#' . esc_html(str_replace(' ', '', $t)) . '</a>';
          }
      }
      ?>
    </div>
  </section>

  <?php echo meridian_ad_slot(300, 600, true); ?>
</aside>
