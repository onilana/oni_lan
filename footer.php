</main>
<footer class="site-footer">
    <div class="container footer-grid">

        <section class="footer-widget footer-about">
            <?php if (has_custom_logo()) { the_custom_logo(); } ?>
            <h3 class="widget-title"><?php echo esc_html(get_theme_mod('ol_college_name', get_bloginfo('name'))); ?></h3>
            <?php if ($tagline = get_theme_mod('ol_school_tagline', get_bloginfo('description'))): ?>
            <p><?php echo esc_html($tagline); ?></p>
            <?php endif; ?>
             <?php if ($address = get_theme_mod('ol_address')): ?><p><?php echo nl2br(esc_html($address)); ?></p><?php endif; ?>
        </section>

        <section class="footer-widget">
            <h3 class="widget-title"><?php esc_html_e('Explore', 'oni-lana'); ?></h3>
            <ul>
                <li><a href="<?php echo esc_url(home_url('/about-us')); ?>"><?php esc_html_e('About Us', 'oni-lana'); ?></a></li>
                <li><a href="<?php echo esc_url(home_url('/academics')); ?>"><?php esc_html_e('Academics', 'oni-lana'); ?></a></li>
                <li><a href="<?php echo esc_url(home_url('/campus-life')); ?>"><?php esc_html_e('Campus Life', 'oni-lana'); ?></a></li>
                <li><a href="<?php echo esc_url(home_url('/news-events')); ?>"><?php esc_html_e('News & Events', 'oni-lana'); ?></a></li>
            </ul>
        </section>

        <section class="footer-widget">
            <h3 class="widget-title"><?php esc_html_e('Resources', 'oni-lana'); ?></h3>
            <ul>
                <li><a href="<?php echo esc_url(home_url('/student-portal')); ?>"><?php esc_html_e('Student Portal', 'oni-lana'); ?></a></li>
                <li><a href="<?php echo esc_url(home_url('/faculty-staff')); ?>"><?php esc_html_e('Faculty & Staff', 'oni-lana'); ?></a></li>
                <li><a href="<?php echo esc_url(home_url('/parent-resources')); ?>"><?php esc_html_e('Parent Resources', 'oni-lana'); ?></a></li>
                <li><a href="<?php echo esc_url(home_url('/library')); ?>"><?php esc_html_e('Library', 'oni-lana'); ?></a></li>
            </ul>
        </section>

        <section class="footer-widget footer-contact">
            <h3 class="widget-title"><?php esc_html_e('Contact Us', 'oni-lana'); ?></h3>
            <?php if ($address = get_theme_mod('ol_address')): ?><p><?php echo nl2br(esc_html($address)); ?></p><?php endif; ?>
            <?php if ($phone = get_theme_mod('ol_contact_phone')): ?><p><a href="tel:<?php echo esc_attr(preg_replace('/\D+/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a></p><?php endif; ?>
            <?php if ($email = get_theme_mod('ol_contact_email', get_option('admin_email'))): ?><p><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></p><?php endif; ?>
        </section>

    </div>
    <div class="footer-bottom">
        <div class="container">
            <span>&copy; <?php echo esc_html(date_i18n('Y')); ?> <?php echo esc_html(get_theme_mod('ol_college_name', get_bloginfo('name'))); ?>. <?php esc_html_e('All rights reserved.', 'oni-lana'); ?></span>
            <span class="footer-credit"><?php esc_html_e('Designed by', 'oni-lana'); ?> &nbsp;|&nbsp; <a href="https://onilana.com" target="_blank" rel="noopener">Oni Lana</a></span>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
