<?php if ( ! defined('ABSPATH') ) exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php if (get_theme_mod('ol_preloader', true)): ?>
<div id="oni-lana-preloader" class="preloader preloader-<?php echo esc_attr(get_theme_mod('ol_preloader_style','spinner')); ?>">
    <div class="preloader-inner" aria-label="Loading"><span></span><span></span><span></span></div>
</div>
<?php endif; ?>

<?php get_template_part('template-parts/content-announcement'); ?>
<?php get_template_part('template-parts/content-topbar'); ?>

<header class="site-header">
    <div class="container header-inner">
        <div class="branding">
            <?php if (has_custom_logo()) { the_custom_logo(); } ?>
            <div class="brand-text">
                <a class="site-title" href="<?php echo esc_url(home_url('/')); ?>">
                    <?php echo esc_html(get_theme_mod('ol_college_name', get_bloginfo('name'))); ?>
                </a>
                <?php if ($school_tagline = get_theme_mod('ol_school_tagline', get_bloginfo('description'))): ?><span class="site-tagline"><?php echo esc_html($school_tagline); ?></span><?php endif; ?>
            </div>
        </div>
        <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false"><span></span><span></span><span></span><b class="screen-reader-text">Menu</b></button>
        <nav class="primary-nav" aria-label="<?php esc_attr_e('Primary Menu','oni-lana'); ?>">
            <?php wp_nav_menu(array(
                'theme_location'=>'primary',
                'menu_id'=>'primary-menu',
                'container'=>false,
                'fallback_cb'=>false,
                'depth'=>3,
            )); ?>
        </nav>
    </div>
</header>
<main id="primary" class="site-main">
