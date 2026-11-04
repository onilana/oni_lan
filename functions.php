<?php
/**
 * Oni Lana Theme functions.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ONI_LANA_VERSION', '1.8.12' );
define( 'ONI_LANA_DIR', get_template_directory() );
define( 'ONI_LANA_URI', get_template_directory_uri() );

function oni_lana_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo', array(
        'height'      => 100,
        'width'       => 320,
        'flex-height' => true,
        'flex-width'  => true,
    ) );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'align-wide' );

    register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'oni-lana' ),
        'footer'  => __( 'Footer Menu', 'oni-lana' ),
    ) );
}
add_action( 'after_setup_theme', 'oni_lana_setup' );

require_once ONI_LANA_DIR . '/inc/contact-validator.php';
require_once ONI_LANA_DIR . '/inc/admissions.php';
require_once ONI_LANA_DIR . '/inc/pwa.php';

function oni_lana_assets() {
    wp_enqueue_style( 'oni-lana-main', ONI_LANA_URI . '/assets/css/main.css', array(), ONI_LANA_VERSION );
    $primary_color   = sanitize_hex_color( get_theme_mod( 'ol_primary_color', '#173f73' ) );
    $secondary_color = sanitize_hex_color( get_theme_mod( 'ol_secondary_color', '#d6a84f' ) );
    $primary_color   = $primary_color ? $primary_color : '#173f73';
    $secondary_color = $secondary_color ? $secondary_color : '#d6a84f';
    wp_add_inline_style(
        'oni-lana-main',
        ':root{--primary:' . esc_attr( $primary_color ) . ';--secondary:' . esc_attr( $secondary_color ) . ';}'
    );
    wp_enqueue_script( 'oni-lana-main', ONI_LANA_URI . '/assets/js/main.js', array(), ONI_LANA_VERSION, true );
    wp_enqueue_style( 'oni-lana-header-search', ONI_LANA_URI . '/assets/css/header-search.css', array( 'oni-lana-main' ), ONI_LANA_VERSION );
    wp_enqueue_script( 'oni-lana-header-search', ONI_LANA_URI . '/assets/js/header-search.js', array(), ONI_LANA_VERSION, true );

    // Dedicated gallery assets are loaded only where gallery UI can appear.
    if ( is_page_template( 'page-gallery.php' ) || is_front_page() || is_singular( 'post' ) ) {
        wp_enqueue_style( 'oni-lana-gallery', ONI_LANA_URI . '/assets/css/gallery.css', array( 'oni-lana-main' ), ONI_LANA_VERSION );
        wp_enqueue_script( 'oni-lana-gallery', ONI_LANA_URI . '/assets/js/gallery.js', array(), ONI_LANA_VERSION, true );
    }

    // Dedicated contact stylesheet is loaded only on the Contact template.
    if ( is_page_template( 'page-contact.php' ) ) {
        wp_enqueue_style( 'oni-lana-contact', ONI_LANA_URI . '/assets/css/contact.css', array( 'oni-lana-main' ), ONI_LANA_VERSION );
    }

    if ( is_page_template( 'page-admission.php' ) ) {
        wp_enqueue_style( 'oni-lana-admission', ONI_LANA_URI . '/assets/css/admission.css', array( 'oni-lana-main' ), ONI_LANA_VERSION );
        wp_enqueue_script( 'oni-lana-admission', ONI_LANA_URI . '/assets/js/admission.js', array(), ONI_LANA_VERSION, true );
    }

    wp_localize_script( 'oni-lana-main', 'oniLanaTheme', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'oni_lana_nonce' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'oni_lana_assets' );

/**
 * Return the ordered image IDs assigned to a gallery album.
 *
 * The featured image and the old single-image field are retained as fallbacks
 * so albums created with earlier versions of the theme continue to work.
 */
function oni_lana_get_gallery_image_ids( $post_id, $limit = 10 ) {
    $raw_ids = get_post_meta( $post_id, '_ol_gallery_image_ids', true );
    $ids     = array_filter( array_map( 'absint', preg_split( '/[,\s]+/', (string) $raw_ids ) ) );

    if ( ! $ids ) {
        $legacy_id = absint( get_post_meta( $post_id, '_ol_gallery_image_id', true ) );
        if ( $legacy_id ) {
            $ids[] = $legacy_id;
        }
    }

    if ( ! $ids ) {
        $thumbnail_id = get_post_thumbnail_id( $post_id );
        if ( $thumbnail_id ) {
            $ids[] = $thumbnail_id;
        }
    }

    $ids = array_values( array_unique( $ids ) );

    return array_slice( $ids, 0, max( 1, absint( $limit ) ) );
}

/**
 * Find the published page using the Gallery template.
 */
function oni_lana_get_gallery_page_url() {
    $pages = get_posts( array(
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_key'       => '_wp_page_template',
        'meta_value'     => 'page-gallery.php',
        'no_found_rows'  => true,
    ) );

    return $pages ? get_permalink( $pages[0] ) : home_url( '/gallery/' );
}

function oni_lana_widgets() {
    register_sidebar( array(
        'name'          => __( 'Main Sidebar', 'oni-lana' ),
        'id'            => 'sidebar-1',
        'description'   => __( 'Optional sidebar. Enable it from Customizer.', 'oni-lana' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
    register_sidebar( array(
        'name'          => __( 'Footer Widget 1', 'oni-lana' ),
        'id'            => 'footer-1',
        'before_widget' => '<section class="footer-widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
    register_sidebar( array(
        'name'          => __( 'Footer Widget 2', 'oni-lana' ),
        'id'            => 'footer-2',
        'before_widget' => '<section class="footer-widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
    register_sidebar( array(
        'name'          => __( 'Footer Widget 3', 'oni-lana' ),
        'id'            => 'footer-3',
        'before_widget' => '<section class="footer-widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'oni_lana_widgets' );

/**
 * Instagram Feed Widget
 * Paste any third-party embed snippet (Elfsight, LightWidget, Snapwidget, etc.)
 * into the "Embed Code" field in Appearance → Widgets.
 */
class Oni_Lana_Instagram_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'oni_lana_instagram',
            __( 'Instagram Feed', 'oni-lana' ),
            array( 'description' => __( 'Displays an Instagram feed via a third-party embed snippet.', 'oni-lana' ) )
        );
    }

    public function widget( $args, $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $code  = ! empty( $instance['embed_code'] ) ? $instance['embed_code'] : '';

        echo wp_kses_post( $args['before_widget'] );

        if ( $title ) {
            echo wp_kses_post( $args['before_title'] ) . esc_html( $title ) . wp_kses_post( $args['after_title'] );
        }

        if ( $code ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<div class="ol-instagram-feed">' . $code . '</div>';
        } else {
            echo '<p class="ol-instagram-placeholder">' . esc_html__( 'Paste your Instagram embed code in Appearance → Widgets → Instagram Feed.', 'oni-lana' ) . '</p>';
        }

        echo wp_kses_post( $args['after_widget'] );
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Follow Us on Instagram', 'oni-lana' );
        $code  = ! empty( $instance['embed_code'] ) ? $instance['embed_code'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'oni-lana' ); ?></label>
            <input class="widefat"
                   id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'embed_code' ) ); ?>"><?php esc_html_e( 'Embed Code (Elfsight / LightWidget / Snapwidget):', 'oni-lana' ); ?></label>
            <textarea class="widefat" rows="8"
                      id="<?php echo esc_attr( $this->get_field_id( 'embed_code' ) ); ?>"
                      name="<?php echo esc_attr( $this->get_field_name( 'embed_code' ) ); ?>"><?php echo esc_textarea( $code ); ?></textarea>
            <small><?php esc_html_e( 'Paste the full snippet exactly as provided by your feed service.', 'oni-lana' ); ?></small>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance               = array();
        $instance['title']      = sanitize_text_field( $new_instance['title'] );
        $instance['embed_code'] = wp_kses( $new_instance['embed_code'], array(
            'script' => array( 'src' => true, 'async' => true, 'defer' => true, 'type' => true, 'class' => true, 'id' => true ),
            'div'    => array( 'class' => true, 'id' => true, 'style' => true ),
            'iframe' => array( 'src' => true, 'width' => true, 'height' => true, 'frameborder' => true, 'scrolling' => true, 'allowtransparency' => true, 'style' => true, 'class' => true ),
            'a'      => array( 'href' => true, 'target' => true, 'rel' => true ),
            'span'   => array( 'class' => true, 'style' => true ),
        ) );
        return $instance;
    }
}
add_action( 'widgets_init', function() {
    register_widget( 'Oni_Lana_Instagram_Widget' );
} );

/* Custom post types used by the homepage. */
function oni_lana_register_cpts() {
    register_post_type( 'ol_slider', array(
        'labels' => array(
            'name' => __( 'Slides', 'oni-lana' ),
            'singular_name' => __( 'Slide', 'oni-lana' ),
            'add_new_item' => __( 'Add New Slide', 'oni-lana' ),
            'edit_item' => __( 'Edit Slide', 'oni-lana' ),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-images-alt2',
        'supports' => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
        'show_in_rest' => true,
    ) );

    register_post_type( 'ol_programme', array(
        'labels' => array(
            'name' => __( 'Programmes', 'oni-lana' ),
            'singular_name' => __( 'Programme', 'oni-lana' ),
            'add_new_item' => __( 'Add New Programme', 'oni-lana' ),
        ),
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-welcome-learn-more',
        'supports' => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
        'show_in_rest' => true,
    ) );

    register_post_type( 'ol_stat', array(
        'labels' => array(
            'name' => __( 'Stats', 'oni-lana' ),
            'singular_name' => __( 'Stat', 'oni-lana' ),
            'add_new_item' => __( 'Add New Stat', 'oni-lana' ),
        ),
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-chart-bar',
        'supports' => array( 'title', 'editor', 'page-attributes' ),
        'show_in_rest' => true,
    ) );

    register_post_type( 'ol_event', array(
        'labels' => array(
            'name' => __( 'Events', 'oni-lana' ),
            'singular_name' => __( 'Event', 'oni-lana' ),
            'add_new_item' => __( 'Add New Event', 'oni-lana' ),
        ),
        'public' => true,
        'has_archive' => true,
        'rewrite' => array( 'slug' => 'events' ),
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'show_in_rest' => true,
    ) );

    register_post_type( 'ol_notice', array(
        'labels'=>array('name'=>__('Notice Board','oni-lana'),'singular_name'=>__('Notice','oni-lana'),'add_new_item'=>__('Add New Notice','oni-lana')),
        'public'=>false,'show_ui'=>true,'menu_icon'=>'dashicons-megaphone','supports'=>array('title','editor','page-attributes'),'show_in_rest'=>true,
    ) );
    register_post_type( 'ol_gallery', array(
        'labels'=>array('name'=>__('Gallery Items','oni-lana'),'singular_name'=>__('Gallery Item','oni-lana'),'add_new_item'=>__('Add Gallery Item','oni-lana')),
        'public'=>false,'show_ui'=>true,'menu_icon'=>'dashicons-format-gallery','supports'=>array('title','editor','thumbnail','page-attributes'),'show_in_rest'=>true,
    ) );
}
add_action( 'init', 'oni_lana_register_cpts' );

/* Meta boxes for admin-managed content. */
function oni_lana_meta_boxes() {
    add_meta_box( 'ol_slider_meta', __( 'Slide Settings', 'oni-lana' ), 'oni_lana_slider_meta', 'ol_slider', 'normal', 'high' );
    add_meta_box( 'ol_programme_meta', __( 'Programme Settings', 'oni-lana' ), 'oni_lana_programme_meta', 'ol_programme', 'normal', 'high' );
    add_meta_box( 'ol_stat_meta', __( 'Stat Settings', 'oni-lana' ), 'oni_lana_stat_meta', 'ol_stat', 'normal', 'high' );
    add_meta_box( 'ol_event_meta', __( 'Event Settings', 'oni-lana' ), 'oni_lana_event_meta', 'ol_event', 'normal', 'high' );
    add_meta_box( 'ol_notice_meta', __( 'Notice Settings', 'oni-lana' ), 'oni_lana_notice_meta', 'ol_notice', 'normal', 'high' );
    add_meta_box( 'ol_gallery_meta', __( 'Gallery Photo', 'oni-lana' ), 'oni_lana_gallery_meta', 'ol_gallery', 'normal', 'high' );
    add_meta_box( 'ol_pdf_meta', __( 'PDF Attachment', 'oni-lana' ), 'oni_lana_pdf_meta', array('post','page'), 'side', 'default' );
    add_meta_box( 'ol_post_gallery_meta', __( 'Post Gallery Images', 'oni-lana' ), 'oni_lana_post_gallery_meta', 'post', 'side', 'default' );
}
add_action( 'add_meta_boxes', 'oni_lana_meta_boxes' );

function oni_lana_slider_meta( $post ) {
    wp_nonce_field( 'ol_meta_save', 'ol_meta_nonce' );
    $button = get_post_meta( $post->ID, '_ol_button', true );
    $url = get_post_meta( $post->ID, '_ol_url', true );
    $overlay = get_post_meta( $post->ID, '_ol_overlay', true );
    ?>
    <p><label>Button Text<br><input class="widefat" name="ol_button" value="<?php echo esc_attr($button); ?>"></label></p>
    <p><label>Button URL<br><input class="widefat" type="url" name="ol_url" value="<?php echo esc_attr($url); ?>"></label></p>
    <p><label><input type="checkbox" name="ol_overlay" value="1" <?php checked($overlay, '1'); ?>> Dark overlay</label></p>
    <p>Use the Featured Image as the slide background.</p>
    <?php
}
function oni_lana_programme_meta( $post ) {
    wp_nonce_field( 'ol_meta_save', 'ol_meta_nonce' );
    $url = get_post_meta( $post->ID, '_ol_url', true );
    $label = get_post_meta( $post->ID, '_ol_label', true );
    ?>
    <p><label>Programme Link<br><input class="widefat" type="url" name="ol_url" value="<?php echo esc_attr($url); ?>"></label></p>
    <p><label>Link Text<br><input class="widefat" name="ol_label" value="<?php echo esc_attr($label ?: 'View Programme'); ?>"></label></p>
    <?php
}
function oni_lana_stat_meta( $post ) {
    wp_nonce_field( 'ol_meta_save', 'ol_meta_nonce' );
    $number = get_post_meta( $post->ID, '_ol_number', true );
    $suffix = get_post_meta( $post->ID, '_ol_suffix', true );
    $icon = get_post_meta( $post->ID, '_ol_icon', true );
    ?>
    <p><label>Number<br><input class="widefat" name="ol_number" value="<?php echo esc_attr($number); ?>" placeholder="2500"></label></p>
    <p><label>Suffix<br><input class="widefat" name="ol_suffix" value="<?php echo esc_attr($suffix); ?>" placeholder="+"></label></p>
    <p><label>Dashicon class<br><input class="widefat" name="ol_icon" value="<?php echo esc_attr($icon); ?>" placeholder="dashicons-groups"></label></p>
    <?php
}
function oni_lana_event_meta( $post ) {
    wp_nonce_field( 'ol_meta_save', 'ol_meta_nonce' );
    $date = get_post_meta( $post->ID, '_ol_event_date', true );
    $time = get_post_meta( $post->ID, '_ol_event_time', true );
    $location = get_post_meta( $post->ID, '_ol_event_location', true );
    ?>
    <p><label>Date<br><input type="date" name="ol_event_date" value="<?php echo esc_attr($date); ?>"></label></p>
    <p><label>Time<br><input type="text" name="ol_event_time" value="<?php echo esc_attr($time); ?>" placeholder="10:00 AM"></label></p>
    <p><label>Location<br><input class="widefat" name="ol_event_location" value="<?php echo esc_attr($location); ?>"></label></p>
    <?php
}

function oni_lana_notice_meta( $post ) {
    wp_nonce_field('ol_meta_save','ol_meta_nonce');
    $url=get_post_meta($post->ID,'_ol_url',true); $date=get_post_meta($post->ID,'_ol_notice_date',true);
    ?><p><label>Notice Link<br><input class="widefat" type="url" name="ol_url" value="<?php echo esc_attr($url); ?>"></label></p>
    <p><label>Notice Date<br><input type="date" name="ol_notice_date" value="<?php echo esc_attr($date); ?>"></label></p><?php
}
function oni_lana_gallery_meta( $post ) {
    wp_nonce_field('ol_meta_save','ol_meta_nonce');
    $raw_ids = get_post_meta($post->ID,'_ol_gallery_image_ids',true);
    $ids = array_filter(array_map('absint', preg_split('/[,,\s]+/', (string)$raw_ids)));
    if (!$ids) { $legacy=(int)get_post_meta($post->ID,'_ol_gallery_image_id',true); if ($legacy) $ids=array($legacy); }
    $ids=array_slice(array_values(array_unique($ids)),0,10);
    $caption=get_post_meta($post->ID,'_ol_gallery_caption',true);
    ?>
    <div class="ol-gallery-admin">
        <input type="hidden" id="ol_gallery_image_ids" name="ol_gallery_image_ids" value="<?php echo esc_attr(implode(',', $ids)); ?>">
        <div id="ol_gallery_preview" class="ol-gallery-preview">
            <?php foreach($ids as $image_id): $url=wp_get_attachment_image_url($image_id,'thumbnail'); if($url): ?><span class="ol-gallery-preview-item"><img src="<?php echo esc_url($url); ?>" alt=""></span><?php endif; endforeach; ?>
        </div>
        <p><button type="button" class="button button-primary" id="ol_select_gallery_images">Select Photos (max 10)</button> <button type="button" class="button" id="ol_remove_gallery_images">Clear Photos</button></p>
        <p><label for="ol_gallery_caption"><strong>Album / Gallery Caption</strong><br><input class="widefat" type="text" id="ol_gallery_caption" name="ol_gallery_caption" value="<?php echo esc_attr($caption); ?>" placeholder="Enter album or gallery caption"></label></p>
        <p class="description">Select up to 10 photos for this Gallery Item. Media Library uploads are not added automatically.</p>
    </div>
    <?php
}

function oni_lana_post_gallery_meta( $post ) {
    wp_nonce_field('ol_meta_save','ol_meta_nonce'); $ids=get_post_meta($post->ID,'_ol_post_gallery_ids',true);
    ?><p><textarea class="widefat" rows="3" id="ol_post_gallery_ids" name="ol_post_gallery_ids" placeholder="123, 124, 125"><?php echo esc_textarea($ids); ?></textarea></p>
    <p><button type="button" class="button" id="ol_upload_gallery">Select Images</button></p><p class="description">Select multiple Media Library images. IDs are stored automatically.</p><?php
}

function oni_lana_pdf_meta( $post ) {
    wp_nonce_field('ol_meta_save','ol_meta_nonce'); $id=(int)get_post_meta($post->ID,'_ol_pdf_id',true); $url=$id?wp_get_attachment_url($id):'';
    ?><p><input type="hidden" id="ol_pdf_id" name="ol_pdf_id" value="<?php echo esc_attr($id); ?>"><input class="widefat" type="text" id="ol_pdf_url" value="<?php echo esc_attr($url); ?>" readonly></p>
    <p><button type="button" class="button" id="ol_upload_pdf">Upload / Select PDF</button> <button type="button" class="button" id="ol_remove_pdf">Remove</button></p>
    <p class="description">Attach a PDF to this post/page. It will be embedded above the content.</p><?php
}

function oni_lana_save_meta( $post_id ) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( ! isset($_POST['ol_meta_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ol_meta_nonce'])), 'ol_meta_save') ) return;
    if ( ! current_user_can('edit_post', $post_id) ) return;

    $fields = array('ol_button','ol_url','ol_label','ol_number','ol_suffix','ol_icon','ol_event_date','ol_event_time','ol_event_location','ol_notice_date');
    foreach ($fields as $field) {
        if ( isset($_POST[$field]) ) {
            update_post_meta($post_id, '_'.$field, sanitize_text_field(wp_unslash($_POST[$field])));
        }
    }
    update_post_meta($post_id, '_ol_overlay', isset($_POST['ol_overlay']) ? '1' : '0');
    if (isset($_POST['ol_pdf_id'])) update_post_meta($post_id, '_ol_pdf_id', absint($_POST['ol_pdf_id']));
    if (isset($_POST['ol_post_gallery_ids'])) update_post_meta($post_id, '_ol_post_gallery_ids', sanitize_text_field(wp_unslash($_POST['ol_post_gallery_ids'])));
    if (isset($_POST['ol_gallery_image_ids'])) {
        $ids=array_filter(array_map('absint', preg_split('/[,,\s]+/', sanitize_text_field(wp_unslash($_POST['ol_gallery_image_ids'])))));
        $ids=array_slice(array_values(array_unique($ids)),0,10);
        update_post_meta($post_id, '_ol_gallery_image_ids', implode(',', $ids));
        update_post_meta($post_id, '_ol_gallery_image_id', $ids ? $ids[0] : 0);
    } elseif (isset($_POST['ol_gallery_image_id'])) update_post_meta($post_id, '_ol_gallery_image_id', absint($_POST['ol_gallery_image_id']));
    if (isset($_POST['ol_gallery_caption'])) update_post_meta($post_id, '_ol_gallery_caption', sanitize_text_field(wp_unslash($_POST['ol_gallery_caption'])));
}
add_action( 'save_post', 'oni_lana_save_meta' );

function oni_lana_sanitize_map_embed( $value ) {
    $allowed = array(
        'iframe' => array(
            'src' => true, 'width' => true, 'height' => true, 'style' => true, 'allowfullscreen' => true,
            'loading' => true, 'referrerpolicy' => true, 'title' => true, 'frameborder' => true, 'allow' => true,
        ),
    );
    return wp_kses(wp_unslash($value), $allowed);
}

/* Small inline brand icons used anywhere the theme links to social profiles. */
function oni_lana_social_icon( $network ) {
    $icons = array(
        'facebook' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13.5 8.25V6.7c0-.72.48-1.05 1.18-1.05h1.82V2.2h-2.98C10.6 2.2 9 3.75 9 6.58v1.67H6.5v3.5H9V21.8h4.5v-10.05h3.05l.45-3.5H13.5Z"/></svg>',
        'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="3.2" y="3.2" width="17.6" height="17.6" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4.2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.7" r="1.15" fill="currentColor"/></svg>',
        'youtube' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M23 12s0-4.1-.52-6.06a3.1 3.1 0 0 0-2.18-2.2C18.36 3.2 12 3.2 12 3.2s-6.36 0-8.3.54a3.1 3.1 0 0 0-2.18 2.2C1 7.9 1 12 1 12s0 4.1.52 6.06a3.1 3.1 0 0 0 2.18 2.2c1.94.54 8.3.54 8.3.54s6.36 0 8.3-.54a3.1 3.1 0 0 0 2.18-2.2C23 16.1 23 12 23 12Zm-13.2 4.1V7.9l6.4 4.1-6.4 4.1Z"/></svg>',
        'x' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M18.9 2H22l-6.77 7.74L23.2 22h-6.24l-4.89-6.4L6.47 22H3.36l7.24-8.28L3 2h6.4l4.42 5.84L18.9 2Zm-1.1 17.8h1.73L8.48 4.1H6.62L17.8 19.8Z"/></svg>',
        'whatsapp' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20.5 3.5A11.9 11.9 0 0 0 12.05.01C5.5.01.18 5.33.18 11.88c0 2.09.55 4.13 1.59 5.92L.1 23.9l6.24-1.63a11.86 11.86 0 0 0 5.7 1.46h.01c6.54 0 11.86-5.32 11.86-11.86 0-3.17-1.23-6.15-3.41-8.37Zm-8.45 16.98h-.01a9.86 9.86 0 0 1-5.03-1.38l-.36-.21-3.7.97.99-3.61-.23-.37a9.84 9.84 0 0 1-1.51-5.25c0-5.45 4.44-9.88 9.9-9.88a9.82 9.82 0 0 1 7 2.9 9.82 9.82 0 0 1 2.89 7c0 5.45-4.44 9.88-9.88 9.88Zm5.42-7.4c-.3-.15-1.77-.87-2.04-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.25-.46-2.39-1.47-.88-.78-1.48-1.75-1.65-2.05-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.49s1.07 2.89 1.22 3.09c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.08 1.77-.72 2.02-1.42.25-.7.25-1.3.17-1.42-.07-.12-.27-.2-.57-.35Z"/></svg>',
        'linkedin' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M5.2 3.1a2.45 2.45 0 1 1 0 4.9 2.45 2.45 0 0 1 0-4.9ZM3.1 9.2h4.2v11.7H3.1V9.2Zm6.8 0h4v1.6h.06c.56-1.07 1.93-2.2 3.98-2.2 4.25 0 5.03 2.8 5.03 6.45v5.85h-4.17v-5.18c0-1.24-.02-2.84-1.73-2.84-1.73 0-1.99 1.35-1.99 2.75v5.27H9.9V9.2Z"/></svg>',
        'copy' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="8" y="8" width="12" height="12" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
    );
    if ($network === 'search') return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="10.8" cy="10.8" r="6.8" fill="none" stroke="currentColor" stroke-width="2"/><path d="m16 16 5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
    return isset( $icons[ $network ] ) ? $icons[ $network ] : '';
}

/* Customizer. */
function oni_lana_customize( $wp_customize ) {
    $wp_customize->add_panel('oni_lana_home',array('title'=>__('School Website Settings','oni-lana'),'priority'=>20));
    $sections=array(
        'oni_lana_branding'=>array('School Branding',array(
            'ol_college_name'=>array('School Name','text',get_bloginfo('name')),
            'ol_school_tagline'=>array('School Tagline','text',get_bloginfo('description')),
            'ol_primary_color'=>array('Primary Brand Color','color','#173f73'),
            'ol_secondary_color'=>array('Accent Brand Color','color','#d6a84f'),
        )),
        'oni_lana_general'=>array('General',array(
            'ol_home_sidebar_enabled'=>array('Home Page Sidebar','checkbox',false),
            'ol_blog_sidebar_enabled'=>array('Blog/Archive Sidebar','checkbox',false),
            'ol_preloader'=>array('Enable Preloader','checkbox',true),
            'ol_preloader_style'=>array('Preloader Style','select','spinner'),
        )),
        'oni_lana_topbar'=>array('Top Bar',array(
            'ol_phone'=>array('Phone','text',''),'ol_email'=>array('Email','text',''),'ol_top_text'=>array('Top Bar Text','text','Welcome to our college')
        )),
        'oni_lana_announcement'=>array('Announcement Bar',array(
            'ol_announcement_enabled'=>array('Enable Announcement Bar','checkbox',true),'ol_announcements'=>array('Announcements (one per line: Text | URL)','textarea','Welcome to our college website. | https://example.com/'), 'ol_announcement_text'=>array('Fallback Announcement','text','Welcome to our college website.')
        )),
        'oni_lana_about'=>array('About Section',array(
            'ol_about_title'=>array('Title','text','About Our College'),'ol_about_text'=>array('Description','textarea','Write a short introduction to your institution here.'),'ol_about_url'=>array('Read More URL','url','')
        )),
        'oni_lana_contact'=>array('Contact & Footer',array(
            'ol_address'=>array('Address','textarea',''),'ol_contact_email'=>array('Contact Email','text',get_option('admin_email')),'ol_contact_phone'=>array('Contact Phone','text',''),'ol_map_embed'=>array('Google Maps Embed URL or iframe code','map_embed',''),'ol_youtube'=>array('YouTube URL','url',''),'ol_facebook'=>array('Facebook URL','url',''),'ol_instagram'=>array('Instagram URL','url','')
        )),
    );
    foreach($sections as $sid=>$data){
        $wp_customize->add_section($sid,array('title'=>$data[0],'panel'=>'oni_lana_home'));
        foreach($data[1] as $key=>$v){
            $sanitize=$v[1]==='url'?'esc_url_raw':($v[1]==='color'?'sanitize_hex_color':($v[1]==='map_embed'?'oni_lana_sanitize_map_embed':($v[1]==='textarea'?'wp_kses_post':($v[1]==='checkbox'?'rest_sanitize_boolean':'sanitize_text_field'))));
            $wp_customize->add_setting($key,array('default'=>$v[2],'sanitize_callback'=>$sanitize));
            $control=array('label'=>$v[0],'section'=>$sid,'type'=>($v[1]==='map_embed'?'textarea':$v[1]));
            if($key==='ol_preloader_style') $control['choices']=array('spinner'=>'Spinner','dots'=>'Dots','bar'=>'Progress Bar');
            if($v[1]==='color'){
                $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize,$key,$control));
            }else{
                $wp_customize->add_control($key,$control);
            }
        }
    }
    $wp_customize->add_setting('ol_about_image',array('default'=>0,'sanitize_callback'=>'absint'));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize,'ol_about_image',array('label'=>'About Section Image','description'=>'Choose the image displayed beside the About section text.','section'=>'oni_lana_about','mime_type'=>'image')));
    $wp_customize->add_section('oni_lana_popup',array('title'=>'Homepage Popup','panel'=>'oni_lana_home'));
    $popup=array('ol_popup_enabled'=>array('Enable Popup','checkbox',false),'ol_popup_title'=>array('Popup Title','text','Admission 2026–2027'),'ol_popup_content'=>array('Popup Content','textarea','Applications are now open. Add your announcement, programme links and call to action here.'),'ol_popup_button'=>array('Button Text','text','Apply Now'),'ol_popup_url'=>array('Button URL','url',''));
    foreach($popup as $key=>$v){$sanitize=$v[1]==='url'?'esc_url_raw':($v[1]==='map_embed'?'oni_lana_sanitize_map_embed':($v[1]==='textarea'?'wp_kses_post':($v[1]==='checkbox'?'rest_sanitize_boolean':'sanitize_text_field')));$wp_customize->add_setting($key,array('default'=>$v[2],'sanitize_callback'=>$sanitize));$wp_customize->add_control($key,array('label'=>$v[0],'section'=>'oni_lana_popup','type'=>$v[1]));}
    $wp_customize->add_setting('ol_popup_image',array('default'=>0,'sanitize_callback'=>'absint'));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize,'ol_popup_image',array('label'=>'Popup Image','section'=>'oni_lana_popup','mime_type'=>'image')));
    $wp_customize->add_section('oni_lana_social',array('title'=>'Blog Social Sharing','panel'=>'oni_lana_home'));
    $wp_customize->add_setting('ol_social_enabled',array('default'=>true,'sanitize_callback'=>'rest_sanitize_boolean'));
    $wp_customize->add_control('ol_social_enabled',array('label'=>'Enable Social Share Buttons','section'=>'oni_lana_social','type'=>'checkbox'));
    $wp_customize->add_section('oni_lana_notices',array('title'=>'Notice Board','panel'=>'oni_lana_home'));
    $wp_customize->add_setting('ol_notice_count',array('default'=>6,'sanitize_callback'=>'absint'));
    $wp_customize->add_control('ol_notice_count',array('label'=>'Number of Notices','section'=>'oni_lana_notices','type'=>'number','input_attrs'=>array('min'=>1,'max'=>12)));
}
add_action('customize_register','oni_lana_customize');

/* Announcement management: a dedicated customizer list plus shortcode support.
 * Announcements can be entered as one-per-line: Label | URL
 */
function oni_lana_announcement_shortcode() {
    $enabled = get_theme_mod('ol_announcement_enabled', true);
    if (!$enabled) return '';
    $raw = get_theme_mod('ol_announcements', '');
    if (!$raw) $raw = get_theme_mod('ol_announcement_text', 'Welcome to our college website.');
    $items = preg_split('/\r\n|\r|\n/', $raw);
    ob_start(); ?>
    <div class="announcement-track" aria-label="<?php esc_attr_e('Announcements','oni-lana'); ?>">
        <?php foreach ($items as $item):
            $parts = array_map('trim', explode('|', $item, 2));
            if (!$parts[0]) continue;
            $text = $parts[0]; $url = $parts[1] ?? '';
        ?>
            <span class="announcement-item">
                <span class="announcement-new-star" aria-hidden="true">★</span>
                <?php if ($url): ?><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($text); ?></a><?php else: ?><?php echo esc_html($text); ?><?php endif; ?>
            </span>
        <?php endforeach; ?>
    </div>
    <?php return ob_get_clean();
}
add_shortcode('oni_announcements', 'oni_lana_announcement_shortcode');

/* Generic responsive embed shortcode: PDF, YouTube, Vimeo and self-hosted iframe/video URLs. */
function oni_lana_embed_shortcode($atts) {
    $atts = shortcode_atts(array(
        'url' => '',
        'type' => 'auto',
        'height' => '600',
        'title' => 'Embedded content',
    ), $atts, 'oni_embed');
    $url = esc_url($atts['url']);
    if (!$url) return '';

    $type = sanitize_key($atts['type']);
    $host = wp_parse_url($url, PHP_URL_HOST);
    $is_pdf = $type === 'pdf' || preg_match('/\.pdf($|\?)/i', $url);
    $is_youtube = $type === 'youtube' || ($host && (strpos($host,'youtube.com') !== false || strpos($host,'youtu.be') !== false));
    if ($is_pdf) {
        return '<div class="embed-responsive pdf-embed"><iframe src="'.esc_url($url).'" title="'.esc_attr($atts['title']).'" loading="lazy"></iframe></div>';
    }
    if ($is_youtube) {
        $video_id = '';
        if (strpos($host,'youtu.be') !== false) $video_id = trim(wp_parse_url($url, PHP_URL_PATH), '/');
        else parse_str(wp_parse_url($url, PHP_URL_QUERY) ?: '', $q) && ($video_id = $q['v'] ?? '');
        if (!$video_id && preg_match('~/embed/([^/?]+)~', $url, $m)) $video_id = $m[1];
        if ($video_id) {
            $embed = 'https://www.youtube.com/embed/'.rawurlencode($video_id);
            return '<div class="video-embed"><iframe src="'.esc_url($embed).'" title="'.esc_attr($atts['title']).'" loading="lazy" allowfullscreen></iframe></div>';
        }
    }
    if ($type === 'video' || preg_match('/\.(mp4|webm|ogg)($|\?)/i', $url)) {
        return '<div class="native-video"><video controls preload="metadata"><source src="'.esc_url($url).'"></video></div>';
    }
    return '<div class="embed-responsive"><iframe src="'.esc_url($url).'" title="'.esc_attr($atts['title']).'" loading="lazy"></iframe></div>';
}
add_shortcode('oni_embed', 'oni_lana_embed_shortcode');

/* Contact form. Validation and sanitization live in inc/contact-validator.php. */
function oni_lana_contact_form_shortcode() {
    $result = array('success'=>false,'errors'=>array(),'values'=>array());

    if (isset($_POST['ol_contact_submit'])) {
        $result = oni_lana_validate_contact_submission($_POST);
        if ($result['success']) {
            $to = sanitize_email(get_theme_mod('ol_contact_email', get_option('admin_email')));
            $name = $result['values']['name'];
            $email = $result['values']['email'];
            $subject = $result['values']['subject'] ?: 'College website enquiry';
            $message = $result['values']['message'];
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'Reply-To: ' . $name . ' <' . $email . '>',
            );
            $body = '<p><strong>Name:</strong> '.esc_html($name).'</p>'
                . '<p><strong>Email:</strong> '.esc_html($email).'</p>'
                . '<p><strong>Subject:</strong> '.esc_html($subject).'</p>'
                . '<p><strong>Message:</strong><br>'.nl2br(esc_html($message)).'</p>';
            if (!$to || !is_email($to)) {
                $result['success'] = false;
                $result['errors'][] = 'The website contact email is not configured correctly.';
            } elseif (!wp_mail($to, $subject, $body, $headers)) {
                $result['success'] = false;
                $result['errors'][] = 'Unable to send the message right now. Please try again later.';
            }
        }
    }

    ob_start();
    if ($result['success']) {
        echo '<div class="form-message success" role="status">Thank you. Your message has been sent successfully.</div>';
    } elseif (!empty($result['errors'])) {
        echo '<div class="form-message error" role="alert"><strong>Please correct the following:</strong><ul>';
        foreach ($result['errors'] as $error) echo '<li>'.esc_html($error).'</li>';
        echo '</ul></div>';
    }
    $values = $result['values'];
    ?>
    <form class="oni-lana-contact-form" method="post" novalidate>
        <?php wp_nonce_field('ol_contact_form','ol_contact_nonce'); ?>
        <div class="contact-honeypot" aria-hidden="true">
            <label>Leave this field empty<input type="text" name="ol_website" tabindex="-1" autocomplete="off"></label>
        </div>
        <div class="form-grid">
            <label for="ol-name">Name *<input id="ol-name" type="text" name="ol_name" maxlength="100" required value="<?php echo esc_attr($values['name'] ?? ''); ?>"></label>
            <label for="ol-email">Email *<input id="ol-email" type="email" name="ol_email" maxlength="160" required value="<?php echo esc_attr($values['email'] ?? ''); ?>"></label>
        </div>
        <label for="ol-subject">Subject<input id="ol-subject" type="text" name="ol_subject" maxlength="180" value="<?php echo esc_attr($values['subject'] ?? ''); ?>"></label>
        <label for="ol-message">Message *<textarea id="ol-message" name="ol_message" rows="7" maxlength="5000" required><?php echo esc_textarea($values['message'] ?? ''); ?></textarea></label>
        <button class="button" type="submit" name="ol_contact_submit" value="1">Send Message</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('oni_contact_form', 'oni_lana_contact_form_shortcode');


function oni_lana_admin_assets($hook){
    if(!in_array($hook,array('post.php','post-new.php'),true)) return;
    wp_enqueue_media(); wp_enqueue_script('oni-lana-admin',ONI_LANA_URI.'/assets/js/admin.js',array('jquery'),ONI_LANA_VERSION,true);
}
add_action('admin_enqueue_scripts','oni_lana_admin_assets');
function oni_lana_pdf_shortcode($atts){$atts=shortcode_atts(array('id'=>0,'url'=>''),$atts,'oni_pdf');$url=$atts['url']?esc_url($atts['url']):($atts['id']?wp_get_attachment_url(absint($atts['id'])):'');if(!$url)return '';return '<div class="embed-responsive pdf-embed"><iframe src="'.esc_url($url).'" title="PDF document" loading="lazy"></iframe></div>';}
add_shortcode('oni_pdf','oni_lana_pdf_shortcode');

/* Allow safe embed URLs in pages/posts via WordPress's normal oEmbed and shortcode system. */
function oni_lana_content_width() { $GLOBALS['content_width'] = apply_filters('oni_lana_content_width', 1200); }
add_action('after_setup_theme', 'oni_lana_content_width', 0);

/* Login page styling. */
function oni_lana_login_assets() {
    wp_enqueue_style( 'oni-lana-login', ONI_LANA_URI . '/assets/css/login.css', array(), ONI_LANA_VERSION );

    $logo_id  = absint( get_theme_mod( 'custom_logo', 0 ) );
    $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
    $logo_css = $logo_url
        ? '#login h1 a{background-image:url("' . esc_url( $logo_url ) . '")!important;}'
        : '';

    if ( $logo_css ) {
        wp_add_inline_style( 'oni-lana-login', $logo_css );
    }
}
add_action('login_enqueue_scripts', 'oni_lana_login_assets');
function oni_lana_login_message( $message ) {
    $design = '<div class="oni-login-design-message">'
        . '<div class="oni-login-heading"><span class="oni-login-welcome">' . esc_html__( 'Welcome back', 'oni-lana' ) . '</span><h2>' . esc_html__( 'Login your account', 'oni-lana' ) . '</h2></div>'
        . '<div class="oni-login-art-copy" aria-hidden="true"><strong>' . esc_html( get_bloginfo( 'name' ) ) . '</strong><span>' . esc_html__( 'Sign in to continue manage your website.', 'oni-lana' ) . '</span></div>'
        . '</div>';

    return $design . $message;
}
add_filter( 'login_message', 'oni_lana_login_message' );
function oni_lana_login_logo_url() { return home_url('/'); }
add_filter('login_headerurl', 'oni_lana_login_logo_url');
function oni_lana_login_logo_title() { return get_theme_mod('ol_college_name', get_bloginfo('name')); }
add_filter('login_headertext', 'oni_lana_login_logo_title');

function custom_excerpt_length( $length ) {
    return 30;
}
add_filter( 'excerpt_length', 'custom_excerpt_length', 999 );
