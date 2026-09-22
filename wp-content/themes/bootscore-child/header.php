<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

  <a href="#main" class="visually-hidden-focusable position-fixed top-0 start-0 m-2 btn btn-sm text-white bg-accent" style="z-index:1060">Skip to main content</a>

  <!-- Utility bar -->
  <div class="bg-paper-alt border-bottom rule text-soft">
    <div class="container-x mx-auto d-flex align-items-center justify-content-between gap-3 px-3 py-1 util-bar">
      <div class="d-flex align-items-center gap-3 overflow-x-auto no-scrollbar">
        <span class="text-uppercase flex-shrink-0"><?php echo date_i18n('l, j F Y'); ?></span>
        <span class="d-none d-lg-inline" style="width:1px;height:12px;background:var(--rule-strong)"></span>
        <ul class="d-none d-lg-flex align-items-center gap-3 list-unstyled m-0">
          <li class="d-flex align-items-center gap-1 flex-shrink-0"><span class="dot dot-open"></span><span class="fw-semibold">New York</span><span class="text-faint tabular">11:42</span></li>
          <li class="d-flex align-items-center gap-1 flex-shrink-0"><span class="dot dot-closing"></span><span class="fw-semibold">London</span><span class="text-faint tabular">16:42</span></li>
          <li class="d-flex align-items-center gap-1 flex-shrink-0"><span class="dot dot-closed"></span><span class="fw-semibold">Tokyo</span><span class="text-faint tabular">00:42</span></li>
          <li class="d-flex align-items-center gap-1 flex-shrink-0"><span class="dot dot-closed"></span><span class="fw-semibold">Hong Kong</span><span class="text-faint tabular">23:42</span></li>
        </ul>
      </div>
      <div class="d-flex align-items-center gap-3 flex-shrink-0">
        <div class="d-none d-sm-flex align-items-center gap-1">
          <span class="text-faint">Edition:</span>
          <button type="button" class="btn btn-link p-0 px-1 fw-semibold text-accent text-decoration-underline" style="font-size:11px">US</button>
          <button type="button" class="btn btn-link p-0 px-1 fw-semibold text-soft" style="font-size:11px">UK</button>
          <button type="button" class="btn btn-link p-0 px-1 fw-semibold text-soft" style="font-size:11px">Asia</button>
        </div>
        <a href="<?php echo esc_url(home_url('/?s=markets')); ?>" class="d-none d-md-inline text-soft">Markets Data</a>
        <a href="#" class="d-none d-md-inline text-soft">e-Paper</a>
        <a href="<?php echo esc_url(wp_login_url()); ?>" class="fw-semibold text-accent">Sign In</a>
        <a href="#" class="d-none d-sm-inline-block btn btn-sm text-white bg-accent px-2 py-1 fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.04em">Subscribe</a>
      </div>
    </div>
  </div>

  <!-- Masthead -->
  <header class="border-bottom rule-strong">
    <div class="container-x mx-auto d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 px-3 py-4">
      <a href="<?php echo esc_url(home_url('/')); ?>" class="text-center text-md-start">
        <span class="masthead-logo d-block"><?php echo get_bloginfo('name') ? esc_html(get_bloginfo('name')) : 'MERIDIAN'; ?></span>
        <span class="masthead-sub d-block mt-1"><?php echo get_bloginfo('description') ? esc_html(get_bloginfo('description')) : 'Global Finance'; ?></span>
      </a>
      <div class="d-flex align-items-center gap-3 w-100 w-md-auto">
        <form role="search" class="d-flex align-items-center border-bottom rule-ink pb-1 w-100" style="max-width:260px" action="<?php echo esc_url(home_url('/')); ?>" method="get">
          <svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="text-faint flex-shrink-0">
            <circle cx="9" cy="9" r="6"></circle><path d="M14 14l4 4" stroke-linecap="round"></path>
          </svg>
          <input name="s" type="search" class="form-control border-0 bg-transparent shadow-none px-2 py-0" style="font-size:13px" placeholder="Search markets, news…" value="<?php echo get_search_query(); ?>">
        </form>
        <div class="d-none d-lg-block">
          <?php echo meridian_ad_slot(468, 60); ?>
        </div>
      </div>
    </div>
  </header>

  <!-- Primary nav -->
  <nav class="mnav" aria-label="Primary">
    <div class="container-x mx-auto px-2">
      <ul class="d-flex list-unstyled m-0 overflow-x-auto no-scrollbar">
        <li><a class="<?php echo (is_front_page() || is_home()) ? 'active' : ''; ?>" href="<?php echo esc_url(home_url('/')); ?>">Home</a></li>
        <?php
        $categories = get_categories(array('orderby' => 'name', 'parent' => 0, 'hide_empty' => false));
        foreach ($categories as $category) {
            $is_active = is_category($category->term_id) ? 'active' : '';
            echo '<li><a class="' . esc_attr($is_active) . '" href="' . esc_url(get_category_link($category->term_id)) . '">' . esc_html($category->name) . '</a></li>';
        }
        ?>
      </ul>
    </div>
  </nav>

  <!-- Secondary nav -->
  <div class="bg-paper-alt border-bottom rule">
    <div class="container-x mx-auto px-2">
      <ul class="d-flex align-items-center gap-1 list-unstyled m-0 py-1 overflow-x-auto no-scrollbar">
        <?php
        $subnav_items = array("Americas", "Europe", "Asia-Pacific", "Rates", "Credit", "ETFs", "Private Equity", "Sustainable Finance");
        foreach ($subnav_items as $sub) {
            echo '<li><a class="px-2 py-1 text-soft" style="font-size:12px;font-weight:500;white-space:nowrap" href="' . esc_url(home_url('/?s=' . urlencode($sub))) . '">' . esc_html($sub) . '</a></li>';
        }
        ?>
      </ul>
    </div>
  </div>

  <!-- Market board -->
  <div class="board" aria-label="Market snapshot">
    <div class="container-x mx-auto d-flex align-items-center gap-3 px-2">
      <div class="d-none d-lg-flex align-items-center gap-2 flex-shrink-0 py-2 pe-3" style="border-right:1px solid rgba(255,255,255,.15)">
        <span class="live-dot green"></span>
        <span class="fw-bold text-uppercase tracking-wider" style="color:#3ddc97;font-size:11px">Markets Open</span>
        <span class="text-uppercase" style="color:rgba(255,255,255,.45);font-size:10px">as of <?php echo date_i18n('H:i T'); ?></span>
      </div>
      <ul class="d-flex align-items-center gap-4 list-unstyled m-0 py-2 overflow-x-auto no-scrollbar flex-grow-1">
        <li class="quote d-flex align-items-baseline gap-2 flex-shrink-0"><span class="name">S&P 500</span><span class="fw-bold tabular">5,642.18</span><span class="fw-semibold tabular up">▲ +0.74%</span></li>
        <li class="quote d-flex align-items-baseline gap-2 flex-shrink-0"><span class="name">NASDAQ</span><span class="fw-bold tabular">18,204.70</span><span class="fw-semibold tabular up">▲ +1.12%</span></li>
        <li class="quote d-flex align-items-baseline gap-2 flex-shrink-0"><span class="name">DOW</span><span class="fw-bold tabular">41,388.10</span><span class="fw-semibold tabular up">▲ +0.31%</span></li>
        <li class="quote d-flex align-items-baseline gap-2 flex-shrink-0"><span class="name">FTSE 100</span><span class="fw-bold tabular">8,312.40</span><span class="fw-semibold tabular down">▼ -0.18%</span></li>
        <li class="quote d-flex align-items-baseline gap-2 flex-shrink-0"><span class="name">DAX</span><span class="fw-bold tabular">18,720.90</span><span class="fw-semibold tabular up">▲ +0.42%</span></li>
        <li class="quote d-flex align-items-baseline gap-2 flex-shrink-0"><span class="name">NIKKEI 225</span><span class="fw-bold tabular">38,451.20</span><span class="fw-semibold tabular down">▼ -0.63%</span></li>
        <li class="quote d-flex align-items-baseline gap-2 flex-shrink-0"><span class="name">EUR/USD</span><span class="fw-bold tabular">1.0872</span><span class="fw-semibold tabular down">▼ -0.09%</span></li>
        <li class="quote d-flex align-items-baseline gap-2 flex-shrink-0"><span class="name">GOLD</span><span class="fw-bold tabular">$2,528</span><span class="fw-semibold tabular up">▲ +0.6%</span></li>
        <li class="quote d-flex align-items-baseline gap-2 flex-shrink-0"><span class="name">WTI CRUDE</span><span class="fw-bold tabular">$74.62</span><span class="fw-semibold tabular down">▼ -1.3%</span></li>
      </ul>
    </div>
  </div>

  <!-- Sector strip -->
  <div class="sectors" aria-label="Sector performance">
    <div class="container-x mx-auto d-flex align-items-center gap-4 px-2">
      <span class="d-none d-md-inline text-faint fw-bold text-uppercase tracking-wider py-2 flex-shrink-0" style="font-size:10px">Sectors</span>
      <ul class="d-flex align-items-center gap-4 list-unstyled m-0 py-2 overflow-x-auto no-scrollbar flex-grow-1">
        <li class="s d-flex align-items-baseline gap-1 flex-shrink-0"><span class="fw-semibold text-soft">Financials</span><span class="fw-bold tabular text-up">+1.24%</span></li>
        <li class="s d-flex align-items-baseline gap-1 flex-shrink-0"><span class="fw-semibold text-soft">Technology</span><span class="fw-bold tabular text-up">+0.98%</span></li>
        <li class="s d-flex align-items-baseline gap-1 flex-shrink-0"><span class="fw-semibold text-soft">Energy</span><span class="fw-bold tabular text-down">-0.72%</span></li>
        <li class="s d-flex align-items-baseline gap-1 flex-shrink-0"><span class="fw-semibold text-soft">Health Care</span><span class="fw-bold tabular text-up">+0.41%</span></li>
        <li class="s d-flex align-items-baseline gap-1 flex-shrink-0"><span class="fw-semibold text-soft">Industrials</span><span class="fw-bold tabular text-up">+0.55%</span></li>
        <li class="s d-flex align-items-baseline gap-1 flex-shrink-0"><span class="fw-semibold text-soft">Utilities</span><span class="fw-bold tabular text-up">+0.87%</span></li>
      </ul>
    </div>
  </div>

  <!-- Ticker (Dynamic Latest Posts) -->
  <div class="ticker d-flex align-items-center" role="region" aria-label="Breaking news">
    <span class="tag flex-shrink-0 align-self-stretch">Live</span>
    <div class="position-relative flex-grow-1 overflow-hidden">
      <div class="marquee text-ink">
        <?php
        $ticker_posts = get_posts(array('posts_per_page' => 6, 'post_status' => 'publish'));
        if (!empty($ticker_posts)) {
            foreach ($ticker_posts as $t_post) {
                echo '<a href="' . esc_url(get_permalink($t_post->ID)) . '" class="text-ink me-3">' . esc_html($t_post->post_title) . '</a>';
                echo '<span class="text-accent2 mx-3">●</span> ';
            }
        } else {
            echo 'Fed officials signal a September cut is on the table as labour market cools <span class="text-accent2 mx-3">●</span> ';
            echo 'ECB holds rates, trims growth forecast for the euro area <span class="text-accent2 mx-3">●</span> ';
            echo 'Dollar softens against the yen ahead of BOJ decision <span class="text-accent2 mx-3">●</span> ';
        }
        ?>
      </div>
    </div>
  </div>
