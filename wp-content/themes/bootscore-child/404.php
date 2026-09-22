<?php
/**
 * 404 Error Template - Meridian Global Finance
 */

get_header();
?>

<main id="main" class="container-x mx-auto px-3 py-5 text-center d-flex flex-column align-items-center">
  <p class="font-serif fw-black text-accent m-0" style="font-size:80px;line-height:1">404</p>
  <h1 class="font-serif fw-black mt-2" style="font-size:30px;letter-spacing:-.02em">Page not found</h1>
  <p class="text-soft mt-2" style="font-size:15px;max-width:440px">
    The page you're looking for may have moved or is no longer available. The markets, however, never stop.
  </p>
  <a href="<?php echo esc_url(home_url('/')); ?>" class="btn text-white bg-accent rounded-0 fw-semibold text-uppercase tracking-wide mt-4 px-4 py-2">Back to the front page</a>
</main>

<?php
get_footer();
