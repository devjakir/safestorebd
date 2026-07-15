<?php get_header(); ?>
<main id="content" class="site-content">
    <section class="entry-content">
        <?php
        if (have_posts()) :
            while (have_posts()) : the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <?php
                    if (is_singular()) {
                        the_title('<h1>', '</h1>');
                    } else {
                        the_title(sprintf('<h2><a href="%s">', esc_url(get_permalink())), '</a></h2>');
                    }
                    ?>
                    <div class="entry-inner">
                        <?php the_content(); ?>
                    </div>
                </article>
            <?php endwhile;
        else : ?>
            <article class="no-results">
                <h2><?php esc_html_e('Nothing found', 'safestore-minimal'); ?></h2>
                <p><?php esc_html_e('Please try another search or check back later.', 'safestore-minimal'); ?></p>
            </article>
        <?php endif; ?>
    </section>
</main>
<?php get_footer(); ?>
