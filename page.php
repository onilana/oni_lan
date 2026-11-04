<?php get_header(); ?>
<div class="container content-layout">
    <article class="content-area">
        <?php while (have_posts()): the_post(); ?>
            <header class="page-header"><h1><?php the_title(); ?></h1></header>
            <div class="entry-content"><?php the_content(); ?></div>
        <?php endwhile; ?>
    </article>
</div>
<?php get_footer(); ?>
