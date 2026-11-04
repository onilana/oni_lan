<section class="section about-section">
    <div class="container about-grid">
        <div class="about-copy">
            <span class="eyebrow"><?php esc_html_e('About Us','oni-lana'); ?></span>
            <h2><?php echo esc_html(get_theme_mod('ol_about_title','About Our College')); ?></h2>
            <div class="lead"><?php echo wp_kses_post(get_theme_mod('ol_about_text','Write a short introduction to your institution here.')); ?></div>
            <?php if ($url=get_theme_mod('ol_about_url')): ?><a class="button button-outline" href="<?php echo esc_url($url); ?>">Read More</a><?php endif; ?>
        </div>
        <?php $about_image_id = absint(get_theme_mod('ol_about_image', 0)); ?>
        <?php if ($about_image_id): ?>
            <div class="about-card has-image">
                <?php echo wp_get_attachment_image($about_image_id, 'large', false, array('class' => 'about-card-image')); ?>
            </div>
        <?php else: ?>
            <div class="about-card" role="img" aria-label="<?php esc_attr_e('Image placeholder', 'oni-lana'); ?>"></div>
        <?php endif; ?>
    </div>
</section>
