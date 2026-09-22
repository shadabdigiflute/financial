  <footer class="bg-paper-alt" style="border-top:2px solid var(--ink)">
    <div class="container-x mx-auto px-3 py-5">
      <div class="mb-4">
        <span class="font-serif d-block fw-bold" style="font-size:26px;letter-spacing:-.02em"><?php echo get_bloginfo('name') ? esc_html(get_bloginfo('name')) : 'MERIDIAN'; ?></span>
        <span class="masthead-sub d-block"><?php echo get_bloginfo('description') ? esc_html(get_bloginfo('description')) : 'Global Finance'; ?></span>
      </div>
      <div class="row">
        <?php
        $footer_links = array(
            'Markets'  => array("World Markets", "Equities", "Bonds", "FX", "Commodities", "Crypto", "Rates"),
            'Sectors'  => array("Financials", "Technology", "Energy", "Healthcare", "Industrials", "Consumer"),
            'Regions'  => array("Americas", "Europe", "Asia-Pacific", "Middle East", "Africa", "Emerging Markets"),
            'Analysis' => array("Opinion", "Lex", "Big Read", "Markets Insight", "Newsletters", "Podcasts"),
            'Products' => array("Terminal", "Data Feeds", "API", "Research", "Alerts", "Mobile App"),
            'Company'  => array("About", "Advertise", "Careers", "Terms of Use", "Privacy Policy", "Contact")
        );

        foreach ($footer_links as $head => $links) {
            echo '<div class="col-6 col-sm-4 col-lg-2 mb-3">';
            echo '<h4 class="text-ink fw-bold text-uppercase tracking-wider mb-3" style="font-size:12px">' . esc_html($head) . '</h4>';
            echo '<ul class="list-unstyled d-flex flex-column gap-2 m-0">';
            foreach ($links as $l) {
                echo '<li><a class="text-soft" style="font-size:13px" href="' . esc_url(home_url('/?s=' . urlencode($l))) . '">' . esc_html($l) . '</a></li>';
            }
            echo '</ul></div>';
        }
        ?>
      </div>
      <div class="border-top rule-strong pt-4 mt-4 text-faint" style="font-size:11px;line-height:1.6">
        <p class="mb-1">Data is indicative and delayed. Nothing on this page constitutes investment advice. Figures shown are illustrative for design purposes.</p>
        <span>© <?php echo date('Y'); ?> <?php echo get_bloginfo('name') ? esc_html(get_bloginfo('name')) : 'Meridian Global Finance'; ?>. All rights reserved.</span>
      </div>
    </div>
  </footer>

  <?php wp_footer(); ?>
</body>
</html>
