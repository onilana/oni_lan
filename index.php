<?php get_header(); ?>
<div class="container content-layout">
    <section class="content-area">
        <?php if (have_posts()): while (have_posts()): the_post(); ?>
            <article <?php post_class('post-card'); ?>>
                <?php if (has_post_thumbnail()): ?><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('large'); ?></a><?php endif; ?>
                <div class="post-card-body">
                    <div class="post-meta"><?php echo esc_html(get_the_date()); ?></div>
                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <?php the_excerpt(); ?>
                </div>
            </article>
        <?php endwhile; the_posts_pagination(); else: ?><p>No content found.</p><?php endif; ?>
    </section>
</div>
<?php get_footer(); ?>
