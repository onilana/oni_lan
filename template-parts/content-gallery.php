<?php
/**
 * Homepage gallery carousel.
 */

$latest_photos = get_posts( array(
    'post_type'      => 'attachment',
    'post_status'    => 'inherit',
    'post_mime_type' => 'image',
    'posts_per_page' => 10,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'no_found_rows'  => true,
) );

$gallery_url = oni_lana_get_gallery_page_url();
?>
<section class="container section home-gallery" aria-labelledby="home-gallery-title">
    <div class="section-heading gallery-section-heading">
        <div>
            <div class="section-meta"><?php esc_html_e('Latest moments', 'oni-lana'); ?></div>
            <h2 id="home-gallery-title"><?php esc_html_e('Photo Gallery', 'oni-lana'); ?></h2>
            <p><?php esc_html_e('A look at the latest photos from our community.', 'oni-lana'); ?></p>
        </div>
        <a class="gallery-all-link" href="<?php echo esc_url($gallery_url); ?>"><?php esc_html_e('View all albums', 'oni-lana'); ?><span aria-hidden="true"> →</span></a>
    </div>

    <?php if ($latest_photos): ?>
        <div class="home-gallery-carousel" data-gallery-carousel aria-roledescription="carousel" aria-label="<?php esc_attr_e('Latest gallery photos', 'oni-lana'); ?>">
            <div class="home-gallery-viewport">
                <div class="home-gallery-track">
                    <?php foreach ($latest_photos as $index => $photo):
                        $photo_id = $photo->ID;
                        $alt = get_post_meta($photo_id, '_wp_attachment_image_alt', true) ?: get_the_title($photo_id);
                        ?>
                        <div class="home-gallery-slide">
                            <?php echo wp_get_attachment_image($photo_id, 'large', false, array(
                                'loading'       => 0 === $index ? 'eager' : 'lazy',
                                'fetchpriority' => 0 === $index ? 'high' : 'auto',
                                'alt'           => $alt,
                            )); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (count($latest_photos) > 1): ?>
                <div class="gallery-carousel-controls" data-carousel-controls data-label="<?php esc_attr_e('Show photo group %d', 'oni-lana'); ?>" role="group" aria-label="<?php esc_attr_e('Choose a group of photos', 'oni-lana'); ?>">
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p class="gallery-empty"><?php esc_html_e('The latest photos will appear here automatically after images are uploaded.', 'oni-lana'); ?></p>
    <?php endif; ?>
</section>
