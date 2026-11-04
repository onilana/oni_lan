<?php get_header(); ?>
<div class="container content-layout">
    <article class="content-area single-post">
        <?php while (have_posts()): the_post(); ?>
            <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a><span aria-hidden="true">›</span><a href="<?php echo esc_url(get_permalink(get_option('page_for_posts')) ?: home_url('/')); ?>">Blog</a><span aria-hidden="true">›</span><span><?php echo esc_html(get_the_title()); ?></span></nav>
            <header class="page-header"><div class="post-meta"><?php echo esc_html(get_the_date()); ?></div><h1><?php the_title(); ?></h1></header>
            <?php if (has_post_thumbnail()) the_post_thumbnail('large'); ?>
            <?php if($pdf_id=(int)get_post_meta(get_the_ID(),'_ol_pdf_id',true)): ?><div class="post-pdf"><h2>Document</h2><?php echo do_shortcode('[oni_pdf id="'.absint($pdf_id).'"]'); ?></div><?php endif; ?>
            <div class="entry-content"><?php the_content(); ?></div>
            <?php $gallery_ids=get_post_meta(get_the_ID(),'_ol_post_gallery_ids',true); if($gallery_ids){ $gallery_ids=array_filter(array_map('absint',preg_split('/[,\s]+/',$gallery_ids))); if($gallery_ids): ?>
            <div class="post-gallery"><h2>Gallery</h2><div class="gallery-grid"><?php foreach($gallery_ids as $gid): $src=wp_get_attachment_image_url($gid,'full'); if($src): ?><a class="gallery-item" href="<?php echo esc_url($src); ?>" data-gallery-item><?php echo wp_get_attachment_image($gid,'large'); ?></a><?php endif; endforeach; ?></div></div>
            <?php endif; } ?>
            <?php if(get_theme_mod('ol_social_enabled',true)): ?><div class="social-share" aria-label="Share this post">
                <strong class="share-label">Share:</strong>
                <a class="social-icon" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode(get_permalink()); ?>" aria-label="Share on Facebook" title="Share on Facebook"><?php echo oni_lana_social_icon('facebook'); ?></a>
                <a class="social-icon" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?url=<?php echo rawurlencode(get_permalink()); ?>&text=<?php echo rawurlencode(get_the_title()); ?>" aria-label="Share on X" title="Share on X"><?php echo oni_lana_social_icon('x'); ?></a>
                <a class="social-icon" target="_blank" rel="noopener" href="https://api.whatsapp.com/send?text=<?php echo rawurlencode(get_the_title().' '.get_permalink()); ?>" aria-label="Share on WhatsApp" title="Share on WhatsApp"><?php echo oni_lana_social_icon('whatsapp'); ?></a>
                <a class="social-icon" target="_blank" rel="noopener" href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo rawurlencode(get_permalink()); ?>" aria-label="Share on LinkedIn" title="Share on LinkedIn"><?php echo oni_lana_social_icon('linkedin'); ?></a>
                <button class="social-icon" type="button" data-copy-url="<?php echo esc_attr(get_permalink()); ?>" aria-label="Copy link" title="Copy link"><?php echo oni_lana_social_icon('copy'); ?></button>
            </div><?php endif; ?>
        <?php endwhile; ?>
    </article>
</div>
<?php get_footer(); ?>
