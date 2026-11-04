<?php
/**
 * Progressive Web App manifest and offline fallback.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function oni_lana_pwa_head() {
    $manifest_url = add_query_arg( 'oni_lana_manifest', '1', home_url( '/' ) );
    $theme_color  = sanitize_hex_color( get_theme_mod( 'ol_primary_color', '#173f73' ) );
    $theme_color  = $theme_color ? $theme_color : '#173f73';

    echo '<link rel="manifest" href="' . esc_url( $manifest_url ) . '">' . "\n";
    echo '<meta name="theme-color" content="' . esc_attr( $theme_color ) . '">' . "\n";

    if ( ! is_ssl() || is_admin() || is_user_logged_in() ) {
        return;
    }

    $service_worker_url = add_query_arg( 'oni_lana_sw', '1', home_url( '/' ) );
    $scope              = home_url( '/' );
    ?>
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(
                <?php echo wp_json_encode( $service_worker_url ); ?>,
                { scope: <?php echo wp_json_encode( $scope ); ?> }
            ).catch(function (error) {
                console.error('The school website app could not be enabled:', error);
            });
        });
    }
    </script>
    <?php
}
add_action( 'wp_head', 'oni_lana_pwa_head', 1 );

function oni_lana_pwa_endpoint() {
    if ( isset( $_GET['oni_lana_manifest'] ) ) {
        $name       = trim( wp_strip_all_tags( get_theme_mod( 'ol_college_name', get_bloginfo( 'name' ) ) ) );
        $name       = $name ? $name : get_bloginfo( 'name' );
        $short_name = wp_html_excerpt( $name, 12, '' );
        $scope      = home_url( '/' );
        $icons      = array();

        foreach ( array( 192, 512 ) as $size ) {
            $icon_url = get_site_icon_url( $size );
            if ( $icon_url ) {
                $icons[] = array(
                    'src'     => esc_url_raw( $icon_url ),
                    'sizes'   => $size . 'x' . $size,
                    'type'    => 'image/png',
                    'purpose' => 'any',
                );
            }
        }

        nocache_headers();
        header( 'Content-Type: application/manifest+json; charset=utf-8' );
        header( 'X-Content-Type-Options: nosniff' );
        echo wp_json_encode(
            array(
                'id'              => $scope,
                'name'            => $name,
                'short_name'      => $short_name,
                'description'     => get_bloginfo( 'description' ),
                'start_url'       => $scope,
                'scope'           => $scope,
                'display'         => 'standalone',
                'background_color'=> '#ffffff',
                'theme_color'     => sanitize_hex_color( get_theme_mod( 'ol_primary_color', '#173f73' ) ) ?: '#173f73',
                'icons'           => $icons,
            ),
            JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    if ( isset( $_GET['oni_lana_sw'] ) ) {
        $scope_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
        $scope_path = $scope_path ? trailingslashit( $scope_path ) : '/';
        $script     = sprintf(
            "const CACHE_NAME = %1\$s;\nconst OFFLINE_URL = %2\$s;\n" .
            "self.addEventListener('install', function (event) {\n" .
            "  event.waitUntil(caches.open(CACHE_NAME).then(function (cache) { return cache.add(OFFLINE_URL); }));\n" .
            "  self.skipWaiting();\n" .
            "});\n" .
            "self.addEventListener('activate', function (event) {\n" .
            "  event.waitUntil(caches.keys().then(function (keys) { return Promise.all(keys.filter(function (key) { return key.indexOf('oni-lana-offline-') === 0 && key !== CACHE_NAME; }).map(function (key) { return caches.delete(key); })); }));\n" .
            "  self.clients.claim();\n" .
            "});\n" .
            "self.addEventListener('fetch', function (event) {\n" .
            "  const request = event.request;\n" .
            "  const url = new URL(request.url);\n" .
            "  if (request.method !== 'GET' || request.mode !== 'navigate' || url.origin !== self.location.origin || url.pathname.indexOf('/wp-admin/') !== -1 || url.pathname.endsWith('/wp-login.php')) { return; }\n" .
            "  event.respondWith(fetch(request).catch(function () { return caches.match(OFFLINE_URL); }));\n" .
            "});\n",
            wp_json_encode( 'oni-lana-offline-' . ONI_LANA_VERSION ),
            wp_json_encode( add_query_arg( 'oni_lana_offline', '1', home_url( '/' ) ) )
        );

        nocache_headers();
        header( 'Content-Type: application/javascript; charset=utf-8' );
        header( 'Service-Worker-Allowed: ' . $scope_path );
        header( 'X-Content-Type-Options: nosniff' );
        echo $script;
        exit;
    }

    if ( isset( $_GET['oni_lana_offline'] ) ) {
        $name = trim( wp_strip_all_tags( get_theme_mod( 'ol_college_name', get_bloginfo( 'name' ) ) ) );
        $name = $name ? $name : get_bloginfo( 'name' );

        nocache_headers();
        header( 'Content-Type: text/html; charset=utf-8' );
        header( 'X-Content-Type-Options: nosniff' );
        ?>
        <!doctype html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="theme-color" content="#ffffff">
            <title><?php echo esc_html( $name ); ?></title>
            <style>
                body{display:grid;min-height:100vh;margin:0;padding:24px;place-items:center;box-sizing:border-box;background:#f7f9fc;color:#102033;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;text-align:center}
                main{max-width:32rem}h1{font-size:1.75rem}p{line-height:1.6;color:#526174}a{display:inline-block;margin-top:12px;padding:12px 20px;border-radius:8px;background:#173f73;color:#fff;text-decoration:none}
            </style>
        </head>
        <body>
            <main>
                <h1><?php echo esc_html( $name ); ?></h1>
                <p><?php esc_html_e( 'You are offline. Connect to the internet and try again to view the latest website content.', 'oni-lana' ); ?></p>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Try again', 'oni-lana' ); ?></a>
            </main>
        </body>
        </html>
        <?php
        exit;
    }
}
add_action( 'template_redirect', 'oni_lana_pwa_endpoint', 0 );
