<?php
/**
 * Tema hijo de Astra para FrixPOS.
 * Carga el CSS del tema padre (Astra) y luego el de este hijo,
 * para poder agregar/reescribir estilos sin tocar el original.
 */

add_action( 'wp_enqueue_scripts', 'punto_child_enqueue_styles' );

function punto_child_enqueue_styles() {
	wp_enqueue_style(
		'astra-parent-style',
		get_template_directory_uri() . '/style.css'
	);

	wp_enqueue_style(
		'punto-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'astra-parent-style' ),
		wp_get_theme()->get( 'Version' )
	);
}

/**
 * ============================================================================
 * FrixPOS — PWA (v5)
 * ============================================================================
 * Sirve /manifest.json y /sw.js en la RAÍZ del sitio (no en la carpeta del
 * tema) para que el service worker pueda controlar cualquier página del
 * dominio, sin importar el slug que le pongas a la página que use la
 * plantilla "FrixPOS (App)" (page-pos.php).
 *
 * A propósito NO usa el sistema de rewrite rules de WordPress (add_rewrite_rule)
 * para no depender de que alguien vaya a Ajustes → Enlaces permanentes → Guardar
 * después de instalar esto — intercepta la petición directo por su URL en
 * 'init', que corre en cada carga sin importar la configuración de permalinks.
 *
 * Si tu WordPress vive en una subcarpeta (ej. tudominio.com/tienda/) en vez de
 * en la raíz del dominio, hay que ajustar las comparaciones de $path abajo.
 * ============================================================================
 */
add_action( 'init', 'punto_pwa_intercept', 0 );

function punto_pwa_intercept() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '';

	if ( $path === '/manifest.json' ) {
		punto_pwa_manifest();
		exit;
	}
	if ( $path === '/sw.js' ) {
		punto_pwa_service_worker();
		exit;
	}
}

function punto_pwa_manifest() {
	$icon_dir = trailingslashit( get_stylesheet_directory_uri() ) . 'pos/';

	// ⚠️ start_url/id/scope asumen que la página del POS vive en "/pos/" — si le pones
	// otro slug a la página con la plantilla "FrixPOS (App)", cambia estas 3 rutas
	// para que coincidan (si no coinciden, el navegador igual instala la PWA pero puede
	// abrir la página equivocada al tocar el ícono desde la pantalla de inicio).
	$manifest = array(
		'name'             => 'FrixPOS',
		'short_name'       => 'FrixPOS',
		'description'      => 'FrixPOS — punto de venta para bodegas y negocios venezolanos.',
		'start_url'        => '/pos/',
		'scope'            => '/',
		'id'               => '/pos/',
		'display'          => 'standalone',
		'orientation'      => 'portrait-primary',
		'background_color' => '#0A0B10',
		'theme_color'      => '#0A0B10',
		'lang'             => 'es',
		'icons'            => array(
			array( 'src' => $icon_dir . 'icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any' ),
			array( 'src' => $icon_dir . 'icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any' ),
			array( 'src' => $icon_dir . 'icon-512-maskable.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable' ),
		),
	);

	header( 'Content-Type: application/manifest+json; charset=utf-8' );
	header( 'Cache-Control: no-cache' );
	echo wp_json_encode( $manifest );
}

function punto_pwa_service_worker() {
	$path = get_stylesheet_directory() . '/pos/sw.js';
	if ( ! file_exists( $path ) ) {
		status_header( 404 );
		return;
	}
	header( 'Content-Type: application/javascript; charset=utf-8' );
	// obligatorio: sw.js vive físicamente bajo /wp-content/themes/.../pos/, pero necesita
	// poder controlar TODO el sitio (scope "/") — este header es lo que se lo permite.
	header( 'Service-Worker-Allowed: /' );
	header( 'Cache-Control: no-cache' );
	readfile( $path );
}

/**
 * ============================================================================
 * Que un plugin de cache de página completa (LiteSpeed Cache, WP Rocket, el
 * que sea) nunca sirva una versión vieja del shell del POS (v5.37)
 * ============================================================================
 * page-pos.php SÍ es cacheable por sí solo (es HTML+JS estático, no hay nada
 * personalizado por PHP ahí adentro) — el problema es justo lo contrario: cada
 * vez que se sube una versión nueva, el HTML de esta página cambia, y un cache
 * de página completa puesto en frente de WordPress no tiene forma de saberlo
 * solo. Ya se resolvió esto mismo para sw.js con Cache-Control: no-cache (ver
 * arriba); esto es lo mismo, para la página que carga ese service worker.
 * ============================================================================ */
add_action( 'template_redirect', function () {
	if ( is_page_template( 'page-pos.php' ) ) {
		header( 'Cache-Control: no-cache, must-revalidate' );
	}
} );

/**
 * ============================================================================
 * Un mínimo de marca en wp-login.php (v5.2)
 * ============================================================================
 * El paso de "escribe tu nueva contraseña" del reseteo TIENE que vivir en
 * wp-login.php — es el mecanismo de enlace-con-token seguro de WordPress, no
 * conviene reconstruirlo a mano. Esto no lo convierte en pantalla de FrixPOS,
 * solo le quita el logo/wordmark de WordPress y pone "FrixPOS" en su lugar, y
 * hace que el logo lleve de vuelta a /pos/ en vez de a wordpress.org.
 *
 * Si más adelante se quiere que el reseteo se sienta 100% parte de la app
 * (mismo fondo, mismo look que el auth-gate de page-pos.php), eso es un
 * endpoint propio (ej. /wp-json/punto/v1/reset) con su propia pantalla dentro
 * de page-pos.php — más trabajo, no entra en este cambio.
 * ============================================================================
 */
add_filter( 'login_headerurl', function () {
	return home_url( '/pos/' );
} );
add_filter( 'login_headertext', function () {
	return 'FrixPOS';
} );
add_action( 'login_enqueue_scripts', function () {
	echo '<style>
		body.login #login h1 a{
			background-image:none;
			width:auto;height:auto;
			font-family:Georgia,serif;font-weight:700;font-size:28px;
			color:#0A0B10;text-indent:0;
		}
	</style>';
} );
