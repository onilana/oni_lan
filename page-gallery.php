<?php
/* Template Name: Gallery */
get_header();
?>
<section class="container section gallery-page">
    <div class="section-heading">
        <div class="section-meta">Explore</div>
        <h1><?php the_title(); ?></h1>
        <p>Browse the selected photos from each gallery album.</p>
    </div>
    <div class="gallery-albums gallery-albums-grid">
        <?php
        $q = new WP_Query(array(
            'post_type'      => 'ol_gallery',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => array('menu_order' => 'ASC', 'date' => 'DESC'),
        ));
        if ($q->have_posts()):
            while ($q->have_posts()): $q->the_post();
                $image_ids = oni_lana_get_gallery_image_ids(get_the_ID());
                $photos = array();
                foreach ($image_ids as $image_id) {
                    if (!wp_attachment_is_image($image_id)) {
                        continue;
                    }
                    $src = wp_get_attachment_image_url($image_id, 'full');
                    if ($src) {
                        $photos[] = array(
                            'id'  => $image_id,
                            'src' => $src,
                        );
                    }
                }
                $caption = get_post_meta(get_the_ID(), '_ol_gallery_caption', true) ?: get_the_title();
                $album_id = 'album-' . get_the_ID();
                $photo_count = count($photos);
                ?>
                <article class="gallery-album gallery-album-grid-item">
                    <?php if ($photos): ?>
                        <a class="gallery-album-cover" href="<?php echo esc_url($photos[0]['src']); ?>" data-gallery-open="<?php echo esc_attr($album_id); ?>" aria-label="<?php echo esc_attr(sprintf(__('Open the %s album', 'oni-lana'), get_the_title())); ?>">
                            <?php echo wp_get_attachment_image($photos[0]['id'], 'large', false, array('loading' => 'lazy')); ?>
                            <span class="gallery-album-overlay">
                                <span class="section-meta"><?php esc_html_e('Album', 'oni-lana'); ?></span>
                                <span class="gallery-album-title"><?php the_title(); ?></span>
                                <span class="gallery-photo-count"><?php echo esc_html(sprintf(_n('%s photo', '%s photos', $photo_count, 'oni-lana'), number_format_i18n($photo_count))); ?></span>
                            </span>
                        </a>
                        <div class="gallery-album-items" hidden>
                            <?php foreach ($photos as $photo):
                                $image_id = $photo['id'];
                                $image_caption = wp_get_attachment_caption($image_id) ?: $caption;
                                $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true) ?: get_the_title($image_id);
                                ?>
                                <a href="<?php echo esc_url($photo['src']); ?>" data-gallery-item data-gallery-group="<?php echo esc_attr($album_id); ?>" data-caption="<?php echo esc_attr($image_caption); ?>" data-alt="<?php echo esc_attr($image_alt); ?>"></a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="gallery-album-cover gallery-album-empty">
                            <span class="gallery-album-overlay">
                                <span class="section-meta"><?php esc_html_e('Album', 'oni-lana'); ?></span>
                                <span class="gallery-album-title"><?php the_title(); ?></span>
                                <span class="gallery-photo-count"><?php esc_html_e('No photos yet', 'oni-lana'); ?></span>
                            </span>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endwhile;
            wp_reset_postdata();
        else:
            echo '<p class="gallery-empty">No gallery albums have been added yet. Add a Gallery Item from the WordPress dashboard.</p>';
        endif;
        ?>
    </div>
</section>
<?php get_footer(); ?>
