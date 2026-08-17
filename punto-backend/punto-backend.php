<?php
/**
 * Plugin Name: FrixPOS — Backend
 * Description: Servidor de FrixPOS: negocios, códigos de activación, cuentas de dueño de negocio (registro/login/recuperar contraseña/perfil/prueba gratis de 60 días), Catálogo Digital público en /p/{slug} (gratis para todos), respaldo de ventas/catálogo/clientes con fotos (solo pago), catálogo maestro compartido, contabilidad mensual, stock sincronizado entre varias cajas (solo pago) y sincronización en tiempo real vía Ably entre dispositivos de una misma cuenta premium, con bitácora de cambios como red de seguridad para cuando un dispositivo estuvo desconectado (solo pago). Expone /activar, /sync, /registro, /login, /recuperar, /perfil, /catalogo-publico, /respaldo, /respaldo/foto, /catalogo-maestro, /contabilidad-mensual, /stock, /ably-token y /cambios, que la PWA del POS ya consume, y un panel en wp-admin para generar códigos, revisar negocios y el catálogo maestro.
 * Version: 1.28
 * Author: FrixPOS
 * Requires PHP: 7.4
 *
 * ============================================================================
 * POR QUÉ ESTO ES UN PLUGIN Y NO PARTE DEL TEMA
 * ============================================================================
 * Si esto viviera en el tema hijo, cambiar de tema borraría el backend de todos
 * los negocios. Los datos y la API van en un plugin; el tema es solo apariencia.
 *
 * ============================================================================
 * EL CONTRATO NO SE NEGOCIA
 * ============================================================================
 * El cliente (page-pos.php, dentro del tema) YA está escrito y desplegado en
 * teléfonos. Este plugin se adapta a él, no al revés. Los 2 endpoints tienen
 * que responder EXACTAMENTE con estas formas:
 *
 *   POST /wp-json/punto/v1/activar
 *     body:  { "codigo": "FRIXPOS-AB12-CD34", "nombre_negocio": "Bodega Los Pinos" }
 *     200:   { "token": "<cadena larga>", "negocio_id": 7 }
 *     error: cualquier status != 200, con { "message": "..." }
 *
 *   POST /wp-json/punto/v1/sync      (header: Authorization: Bearer <token>)
 *     body:  { "ventas": [ { "uuid": "...", "payload": {...}, "fecha": "...", "monto_bs": 856 } ] }
 *     200:   { "sincronizadas_uuids": ["..."] }
 *
 * El cliente marca como sincronizada SOLO la venta cuyo uuid vuelva en
 * sincronizadas_uuids. Si el servidor guarda 3 de 5, las otras 2 se reintentan
 * solas en el próximo sync. Por eso este endpoint nunca devuelve 500 por una
 * venta mala: guarda las que puede y calla las que no.
 * ============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // acceso directo al archivo, no.
}

define( 'PUNTO_DB_VERSION', '1.9' );
define( 'PUNTO_BACKEND_DIR', plugin_dir_path( __FILE__ ) );

/* ==========================================================================
 * 1. TABLAS
 * ==========================================================================
 * Tablas propias con $wpdb, no Custom Post Types. Razón: una bodega puede
 * generar miles de filas de ventas, y meter eso en wp_posts + wp_postmeta
 * (que es lo que hace un CPT) hace que la base se ponga lenta y que consultar
 * "cuánto vendió este negocio en marzo" requiera joins absurdos. El catálogo
 * compartido SÍ va a ser un CPT más adelante — ahí sí conviene, porque son
 * pocos miles de registros que se editan a mano y quieren Biblioteca de Medios.
 */
function punto_tablas() {
	global $wpdb;
	return array(
		'negocios'         => $wpdb->prefix . 'punto_negocios',
		'ventas'           => $wpdb->prefix . 'punto_ventas',
		'codigos'          => $wpdb->prefix . 'punto_codigos',
		'respaldos'        => $wpdb->prefix . 'punto_respaldos',
		'contab_mensual'   => $wpdb->prefix . 'punto_contab_mensual',
		'catalogo_maestro' => $wpdb->prefix . 'punto_catalogo_maestro',
		'stock'            => $wpdb->prefix . 'punto_stock',
		'catalogo_publico' => $wpdb->prefix . 'punto_catalogo_publico',
		'solicitudes'      => $wpdb->prefix . 'punto_solicitudes',
		'cambios'          => $wpdb->prefix . 'punto_cambios',
	);
}

register_activation_hook( __FILE__, 'punto_instalar_tablas' );

function punto_instalar_tablas() {
	global $wpdb;
	$t       = punto_tablas();
	$collate = $wpdb->get_charset_collate();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// token_hash y no el token en claro: si alguien se lleva un volcado de la base,
	// no se lleva las llaves de sincronización de todos los negocios. El token en
	// claro solo existe una vez, en la respuesta de /activar, y vive en el teléfono.
	// wp_user_id (v1.4) — vincula el negocio con la cuenta que lo activó, si la había.
	// Queda NULL para negocios activados sin cuenta (sigue siendo válido, el código es la
	// credencial) y para los que ya existían antes de que este vínculo se pudiera hacer.
	dbDelta( "CREATE TABLE {$t['negocios']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		nombre VARCHAR(191) NOT NULL DEFAULT '',
		token_hash CHAR(64) NOT NULL DEFAULT '',
		activo TINYINT(1) NOT NULL DEFAULT 1,
		fecha_alta DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
		ultimo_sync DATETIME NULL,
		wp_user_id BIGINT UNSIGNED NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY token_hash (token_hash),
		KEY wp_user_id (wp_user_id)
	) $collate;" );

	// La clave única (negocio_id, uuid) ES la idempotencia: reintentar un sync no
	// duplica ventas. El cliente reintenta constantemente cuando vuelve la señal,
	// así que esto no es una precaución teórica, pasa todos los días.
	dbDelta( "CREATE TABLE {$t['ventas']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		negocio_id BIGINT UNSIGNED NOT NULL,
		uuid VARCHAR(64) NOT NULL,
		payload LONGTEXT NOT NULL,
		monto_bs DECIMAL(18,2) NOT NULL DEFAULT 0,
		fecha_venta DATETIME NULL,
		fecha_sync DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY  (id),
		UNIQUE KEY negocio_uuid (negocio_id, uuid),
		KEY negocio_fecha (negocio_id, fecha_venta)
	) $collate;" );

	dbDelta( "CREATE TABLE {$t['codigos']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		codigo VARCHAR(32) NOT NULL,
		usado TINYINT(1) NOT NULL DEFAULT 0,
		negocio_id BIGINT UNSIGNED NULL,
		nota VARCHAR(191) NOT NULL DEFAULT '',
		fecha_generado DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
		fecha_usado DATETIME NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY codigo (codigo)
	) $collate;" );

	// respaldos (v1.5) — un respaldo por cuenta por día (UNIQUE wp_user_id+fecha), por eso
	// POST /respaldo puede usar INSERT ... ON DUPLICATE KEY UPDATE para sobrescribir el de
	// hoy en vez de acumular uno por cada cierre de turno o toque de "Respaldar ahora".
	// payload = el estado completo (catálogo/stock/clientes/pedidos del último mes/cajón/
	// config, sin fotos); resumen = conteos y totales livianos, para mostrar sin pagar.
	dbDelta( "CREATE TABLE {$t['respaldos']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		wp_user_id BIGINT UNSIGNED NOT NULL,
		fecha DATE NOT NULL,
		payload LONGTEXT NOT NULL,
		resumen LONGTEXT NOT NULL,
		creado_en DATETIME NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY user_fecha (wp_user_id, fecha),
		KEY wp_user_id (wp_user_id)
	) $collate;" );

	// contab_mensual (v1.6) — una foto por cuenta por MES calendario (no por día: Contabilidad
	// se declara mensual, y el respaldo normal de arriba solo cubre los últimos 30 días, así
	// que un mes ya cerrado se cae de esa ventana antes de que alguien piense en respaldarlo).
	// resumen = los números ya calculados por contabData() (ganancia, IVA débito/crédito,
	// etc.); detalle = las ventas y egresos de ESE mes exacto, para poder rearmar el Libro de
	// Ventas/Compras si hace falta. El servidor no calcula nada de esto, solo lo guarda.
	dbDelta( "CREATE TABLE {$t['contab_mensual']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		wp_user_id BIGINT UNSIGNED NOT NULL,
		mes CHAR(7) NOT NULL,
		resumen LONGTEXT NOT NULL,
		detalle LONGTEXT NOT NULL,
		creado_en DATETIME NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY user_mes (wp_user_id, mes),
		KEY wp_user_id (wp_user_id)
	) $collate;" );

	// catalogo_maestro (v1.7) — catálogo compartido entre TODOS los negocios, no uno solo.
	// A propósito NUNCA lleva costo ni precio — eso es privado y competitivo de cada negocio,
	// esto es solo identidad del producto (nombre/marca/sku/foto/categoría). 'pendiente' es la
	// cola de sugerencias que mandan los negocios al guardar un producto sin match; 'aprobado'
	// es lo único que devuelve la búsqueda. nombre_normalizado (sin tildes/mayúsculas/signos)
	// existe para que la búsqueda encuentre "Harina PAN" escribiendo "harina pan" o "harina p.a.n.".
	// foto_pendiente (v1.11) — la foto que trae la sugerencia, en base64, SOLO para que quien
	// revisa la vea antes de decidir. Nunca sale por /buscar. Al aprobar, se sube de verdad a la
	// Biblioteca de Medios y pasa a foto_url; foto_pendiente se limpia.
	dbDelta( "CREATE TABLE {$t['catalogo_maestro']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		nombre VARCHAR(191) NOT NULL,
		nombre_normalizado VARCHAR(191) NOT NULL DEFAULT '',
		marca VARCHAR(120) NOT NULL DEFAULT '',
		sku VARCHAR(100) NOT NULL DEFAULT '',
		categoria VARCHAR(60) NOT NULL DEFAULT '',
		unidad_tipo VARCHAR(20) NOT NULL DEFAULT 'unidad',
		unidad_valor_base DECIMAL(18,4) NULL,
		foto_url VARCHAR(500) NOT NULL DEFAULT '',
		foto_pendiente LONGTEXT NULL,
		estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
		wp_user_id BIGINT UNSIGNED NULL,
		creado_en DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY estado (estado),
		KEY nombre_normalizado (nombre_normalizado)
	) $collate;" );

	// stock multi-caja (v1.8) — UNA fila por producto por negocio, con el conteo YA sumado de
	// todas las cajas que reportaron. producto_id es el id del producto tal como lo genera el
	// POS (uuidLite desde v1.8, ver page-pos.php) — por eso tiene que ser único de verdad entre
	// dispositivos, no un contador local por teléfono. No hay costo/precio acá, solo el número.
	dbDelta( "CREATE TABLE {$t['stock']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		negocio_id BIGINT UNSIGNED NOT NULL,
		producto_id VARCHAR(64) NOT NULL,
		stock BIGINT NOT NULL DEFAULT 0,
		actualizado_en DATETIME NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY negocio_producto (negocio_id, producto_id),
		KEY negocio_id (negocio_id)
	) $collate;" );

	// catalogo_publico (v1.13) — la vitrina pública /p/{slug}. A propósito por wp_user_id (la
	// CUENTA), no por negocio_id: el Catálogo Digital es gratis para todos, incluso sin código
	// de activación, y una cuenta sin negocio activo no tiene fila en {$t['negocios']} — atarlo
	// ahí habría dejado a cualquiera que no pagó sin poder publicar. Reemplazo completo en cada
	// publicación (DELETE + INSERT por wp_user_id), no upsert incremental: el catálogo público
	// es chico (una bodega real, unos cientos de productos como mucho) y así el teléfono nunca
	// tiene que mandar un diff — manda el catálogo visible tal cual está ahora y listo.
	dbDelta( "CREATE TABLE {$t['catalogo_publico']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		wp_user_id BIGINT UNSIGNED NOT NULL,
		producto_id VARCHAR(64) NOT NULL,
		nombre VARCHAR(191) NOT NULL,
		precio_usd DECIMAL(10,2) NULL,
		foto_url VARCHAR(500) NOT NULL DEFAULT '',
		categoria VARCHAR(100) NOT NULL DEFAULT '',
		orden INT NOT NULL DEFAULT 0,
		actualizado_en DATETIME NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY user_producto (wp_user_id, producto_id),
		KEY wp_user_id (wp_user_id)
	) $collate;" );

	// solicitudes (v1.8) — leads del sitio de marketing: alguien llena el formulario público
	// (shortcode [punto_formulario]) pidiendo su código o preguntando algo de soporte, y queda
	// acá para que Jonathan lo vea y le escriba. No tiene relación con las cuentas de la app
	// (wp_users) — quien llena esto puede ni siquiera tener cuenta todavía, por eso son campos
	// sueltos (nombre/teléfono/correo), no una referencia a un usuario.
	dbDelta( "CREATE TABLE {$t['solicitudes']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		nombre VARCHAR(191) NOT NULL,
		telefono VARCHAR(60) NOT NULL DEFAULT '',
		correo VARCHAR(191) NOT NULL DEFAULT '',
		motivo VARCHAR(30) NOT NULL DEFAULT 'comprar',
		atendida TINYINT(1) NOT NULL DEFAULT 0,
		creado_en DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY atendida (atendida)
	) $collate;" );

	// cambios (v1.9 / plugin v1.26) — red de seguridad de la sincronización en tiempo real
	// (Ably): el dispositivo que publica un cambio TAMBIÉN lo guarda aquí; si el que lo iba a
	// recibir estaba apagado/sin señal, se pone al día pidiendo "qué pasó desde el cursor X" en
	// vez de perder el cambio para siempre. Cursor = id autoincremental, nunca la fecha (dos
	// filas pueden compartir el mismo segundo). 'origen' identifica el DISPOSITIVO que publicó
	// (no la cuenta — dos teléfonos de la misma cuenta son orígenes distintos), para que ese
	// mismo dispositivo se salte su propio cambio al ponerse al día. No es historial permanente:
	// se poda a los últimos 500 por negocio (ver punto_api_cambios_guardar).
	dbDelta( "CREATE TABLE {$t['cambios']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		negocio_id BIGINT UNSIGNED NOT NULL,
		origen VARCHAR(64) NOT NULL DEFAULT '',
		tipo VARCHAR(40) NOT NULL DEFAULT '',
		payload LONGTEXT NOT NULL,
		creado_en DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY negocio_id (negocio_id, id)
	) $collate;" );

	update_option( 'punto_db_version', PUNTO_DB_VERSION );
	// v1.13 — la regla de /p/{slug} (ver más abajo, "CATÁLOGO DIGITAL") recién se registra en
	// este mismo request vía add_action('init', ...); hay que refrescarla para que WordPress
	// la reconozca ya, sin que Jonathan tenga que ir a Ajustes → Enlaces permanentes a mano.
	flush_rewrite_rules();
}

// Si el plugin se actualiza por FTP (sin desactivar/activar), el hook de activación
// no corre. Este chequeo barato en cada carga del admin cubre ese caso.
add_action( 'admin_init', function () {
	if ( get_option( 'punto_db_version' ) !== PUNTO_DB_VERSION ) {
		punto_instalar_tablas();
	}
} );

/* ==========================================================================
 * 2. UTILIDADES
 * ========================================================================== */

function punto_hash_token( $token ) {
	return hash( 'sha256', $token );
}

/**
 * "Harina PAN", "harina pan", "Harina P.A.N." → "harina pan". remove_accents() es de
 * WordPress core (tildes, ñ, etc.); el resto colapsa mayúsculas/puntuación/espacios para
 * que la búsqueda del catálogo maestro encuentre lo mismo sin importar cómo se escribió.
 */
function punto_normalizar_texto( $texto ) {
	$texto = remove_accents( (string) $texto );
	$texto = strtolower( $texto );
	$texto = preg_replace( '/[^a-z0-9]+/', ' ', $texto );
	return trim( preg_replace( '/\s+/', ' ', $texto ) );
}

/**
 * Genera un código con el formato que el POS muestra de ejemplo: FRIXPOS-AB12-CD34.
 * Alfabeto sin I, O, 0 ni 1 a propósito: estos códigos se dictan por WhatsApp o
 * por teléfono, y confundir un cero con una O es garantía de soporte perdido.
 */
function punto_generar_codigo() {
	$abc = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
	$out = '';
	for ( $i = 0; $i < 8; $i++ ) {
		if ( $i === 4 ) {
			$out .= '-';
		}
		$out .= $abc[ random_int( 0, strlen( $abc ) - 1 ) ];
	}
	return 'FRIXPOS-' . $out;
}

/**
 * Lee el token del header Authorization.
 * Ojo: en muchos Apache compartidos (Hostinger incluido, según la configuración)
 * el header Authorization no llega a PHP porque mod_php lo filtra. WordPress ya
 * intenta recuperarlo, pero si el POS reporta "no autorizado" con un token bueno,
 * la causa casi siempre es esa — ver la nota de .htaccess en el README del plugin.
 */
function punto_token_de_request( $request ) {
	$auth = $request->get_header( 'authorization' );
	if ( ! $auth && isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
		$auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
	}
	if ( ! $auth && isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
		$auth = $_SERVER['HTTP_AUTHORIZATION'];
	}
	if ( ! $auth || stripos( $auth, 'bearer ' ) !== 0 ) {
		return '';
	}
	return trim( substr( $auth, 7 ) );
}

/**
 * Resuelve el negocio de una petición a partir del token del header.
 *
 * Acepta DOS clases de token, y esto es esencial, no una comodidad:
 *  1. El token de NEGOCIO que devolvió /activar (el de siempre).
 *  2. El token de CUENTA, si esa cuenta tiene un negocio activo.
 *
 * Sin el caso 2, un cliente que paga y pierde el teléfono quedaba trancado: el token de
 * negocio solo existe en claro una vez (en la base solo vive su sha256), así que el
 * teléfono nuevo nunca lo recupera, y su código ya está marcado como usado — o sea que no
 * podía reactivar ni sincronizar nunca más. Con esto, iniciar sesión en el teléfono nuevo
 * alcanza: la cuenta ya prueba de quién es el negocio. Es el mismo criterio que /stock ya
 * usaba ("dos teléfonos con la misma cuenta son el mismo negocio").
 */
function punto_negocio_por_token( $token ) {
	global $wpdb;
	if ( ! $token ) {
		return null;
	}
	$t = punto_tablas();

	$negocio = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$t['negocios']} WHERE token_hash = %s AND activo = 1",
		punto_hash_token( $token )
	) );
	if ( $negocio ) {
		return $negocio;
	}

	$cuenta = punto_cuenta_por_token( $token );
	if ( ! $cuenta ) {
		return null;
	}
	return $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$t['negocios']} WHERE wp_user_id = %d AND activo = 1 ORDER BY id DESC LIMIT 1",
		$cuenta->ID
	) );
}

/* ==========================================================================
 * 3. LA API
 * ========================================================================== */

/**
 * v1.20 — NINGUNA respuesta de /wp-json/punto/v1/* se puede cachear, en ningún punto de la
 * cadena (LiteSpeed, cualquier proxy, el navegador). No es opcional acá: casi todos estos
 * endpoints van con `Authorization: Bearer <token>` y devuelven datos privados de UNA cuenta
 * (su respaldo, su perfil, su negocio) — un cache que guarde la respuesta por URL sin fijarse
 * en ese header podría devolverle a la cuenta B lo que en realidad le pertenece a la cuenta A.
 * Antes ningún endpoint mandaba Cache-Control, así que todo dependía de que WordPress y el
 * cache de turno se comportaran bien solos — ahora se fuerza acá, una sola vez, para las
 * rutas de punto/v1 completas (no hay que repetirlo en cada función ni acordarse de ponerlo
 * en las que se agreguen después).
 */
add_filter( 'rest_pre_serve_request', function ( $served, $result, $request ) {
	if ( 0 === strpos( $request->get_route(), '/punto/v1/' ) ) {
		header( 'Cache-Control: no-store, no-cache, must-revalidate, private' );
		header( 'Pragma: no-cache' );
	}
	return $served;
}, 10, 3 );

add_action( 'rest_api_init', function () {
	register_rest_route( 'punto/v1', '/activar', array(
		'methods'             => 'POST',
		'callback'            => 'punto_api_activar',
		'permission_callback' => '__return_true', // pública a propósito: el código de activación ES la credencial
	) );

	register_rest_route( 'punto/v1', '/sync', array(
		'methods'             => 'POST',
		'callback'            => 'punto_api_sync',
		'permission_callback' => '__return_true', // la autorización se hace adentro, con el Bearer token
	) );

	register_rest_route( 'punto/v1', '/registro', array(
		'methods'             => 'POST',
		'callback'            => 'punto_api_registro',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'punto/v1', '/login', array(
		'methods'             => 'POST',
		'callback'            => 'punto_api_login',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'punto/v1', '/recuperar', array(
		'methods'             => 'POST',
		'callback'            => 'punto_api_recuperar',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'punto/v1', '/cuenta/borrar', array(
		'methods'             => 'POST',
		'callback'            => 'punto_api_cuenta_borrar',
		'permission_callback' => '__return_true',
	) );

	register_rest_route(
		'punto/v1',
		'/respaldo',
		array(
			array(
				'methods'             => 'POST',
				'callback'            => 'punto_api_respaldo_guardar',
				'permission_callback' => '__return_true', // la autorización se hace adentro, con el Bearer token de cuenta
			),
			array(
				'methods'             => 'GET',
				'callback'            => 'punto_api_respaldo_listar',
				'permission_callback' => '__return_true',
			),
		)
	);

	register_rest_route( 'punto/v1', '/respaldo/(?P<id>\d+)/completo', array(
		'methods'             => 'GET',
		'callback'            => 'punto_api_respaldo_completo',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'punto/v1', '/respaldo/foto', array(
		'methods'             => 'POST',
		'callback'            => 'punto_api_respaldo_foto',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'punto/v1', '/stock', array(
		'methods'             => 'GET',
		'callback'            => 'punto_api_stock_listar',
		'permission_callback' => '__return_true',
	) );
	register_rest_route( 'punto/v1', '/stock/mover', array(
		'methods'             => 'POST',
		'callback'            => 'punto_api_stock_mover',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'punto/v1', '/ably-token', array(
		'methods'             => 'POST',
		'callback'            => 'punto_api_ably_token',
		'permission_callback' => '__return_true', // la autorización se hace adentro, con el Bearer token de cuenta
	) );

	register_rest_route(
		'punto/v1',
		'/cambios',
		array(
			array(
				'methods'             => 'POST',
				'callback'            => 'punto_api_cambios_guardar',
				'permission_callback' => '__return_true', // la autorización se hace adentro, con el Bearer token de cuenta
			),
			array(
				'methods'             => 'GET',
				'callback'            => 'punto_api_cambios_listar',
				'permission_callback' => '__return_true',
			),
		)
	);

	register_rest_route(
		'punto/v1',
		'/contabilidad-mensual',
		array(
			array(
				'methods'             => 'POST',
				'callback'            => 'punto_api_contab_guardar',
				'permission_callback' => '__return_true',
			),
			array(
				'methods'             => 'GET',
				'callback'            => 'punto_api_contab_listar',
				'permission_callback' => '__return_true',
			),
		)
	);

	register_rest_route( 'punto/v1', '/contabilidad-mensual/(?P<mes>\d{4}-\d{2})/completo', array(
		'methods'             => 'GET',
		'callback'            => 'punto_api_contab_completo',
		'permission_callback' => '__return_true',
	) );

	register_rest_route(
		'punto/v1',
		'/perfil',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'punto_api_perfil_ver',
				'permission_callback' => '__return_true', // la autorización se hace adentro, con el Bearer token de cuenta
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'punto_api_perfil_guardar',
				'permission_callback' => '__return_true',
			),
		)
	);

	register_rest_route( 'punto/v1', '/perfil/foto', array(
		'methods'             => 'POST',
		'callback'            => 'punto_api_perfil_foto',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'punto/v1', '/catalogo-maestro/buscar', array(
		'methods'             => 'GET',
		'callback'            => 'punto_api_catalogo_buscar',
		'permission_callback' => '__return_true', // pública a propósito: ayuda a arrancar rápido a cualquiera, con o sin código
	) );

	register_rest_route( 'punto/v1', '/catalogo-maestro/sugerir', array(
		'methods'             => 'POST',
		'callback'            => 'punto_api_catalogo_sugerir',
		'permission_callback' => '__return_true', // la autorización se hace adentro, con el Bearer token de cuenta
	) );

	register_rest_route( 'punto/v1', '/catalogo-publico', array(
		'methods'             => 'POST',
		'callback'            => 'punto_api_catalogo_publico_guardar',
		'permission_callback' => '__return_true', // la autorización se hace adentro, con el Bearer token de cuenta
	) );

	// v1.8 — pública a propósito: quien la llena viene del sitio de marketing, sin cuenta y sin
	// token. Es un formulario de contacto, no una acción sobre datos de nadie.
	register_rest_route( 'punto/v1', '/solicitud', array(
		'methods'             => 'POST',
		'callback'            => 'punto_api_solicitud_crear',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'punto/v1', '/catalogo-publico/foto', array(
		'methods'             => 'POST',
		'callback'            => 'punto_api_catalogo_publico_foto',
		'permission_callback' => '__return_true',
	) );
} );

/* ==========================================================================
 * 3b. CUENTA DEL DUEÑO DEL NEGOCIO (identidad, v1.1)
 * ==========================================================================
 * A propósito separado de /activar: el código de activación desbloquea el
 * respaldo en la nube (es pago, uno por negocio). Esta cuenta es solo la
 * puerta de entrada a la app — "quién eres" — y no cuesta nada ni requiere
 * código. Se apoya 100% en wp_users/wp_usermeta, nativo de WordPress: nada
 * de tablas propias, nada de tokens propios, y "recuperar contraseña" usa
 * el mismo envío de correo que ya trae WordPress de fábrica.
 *
 * Si más adelante el mismo dueño activa un código, /activar sigue funcionando
 * exactamente igual que hoy — las dos cosas conviven sin tocarse entre sí.
 * ========================================================================== */

/**
 * Token de cuenta (v1.3) — mismo patrón que el token de negocio (solo se guarda el hash,
 * nunca el token en claro), pero NO rota: cada /registro o /login emite un token nuevo SIN
 * invalidar los que ya tengan otros dispositivos logueados con la misma cuenta. WordPress
 * permite varias filas de user_meta con la misma clave por usuario — así se guardan varios
 * tokens válidos a la vez, uno por cada inicio de sesión, sin desconectar a nadie más.
 * Nada todavía verifica este token (eso llega con /respaldo) — por ahora solo se emite.
 */
function punto_emitir_token_cuenta( $user_id ) {
	$token = wp_generate_password( 48, false, false );
	add_user_meta( $user_id, 'punto_token_hash', hash( 'sha256', $token ), false );

	// tope simple: si alguien acumula tokens de muchos dispositivos/años, se queda solo con
	// los 5 más recientes — evita crecimiento sin límite sin desconectar el uso normal.
	$hashes = get_user_meta( $user_id, 'punto_token_hash', false );
	if ( count( $hashes ) > 5 ) {
		global $wpdb;
		$sobrantes = count( $hashes ) - 5;
		$viejos    = $wpdb->get_col( $wpdb->prepare(
			"SELECT umeta_id FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'punto_token_hash' ORDER BY umeta_id ASC LIMIT %d",
			$user_id,
			$sobrantes
		) );
		foreach ( $viejos as $umeta_id ) {
			delete_metadata_by_mid( 'user', $umeta_id );
		}
	}

	return $token;
}

/**
 * v1.8 — lista blanca de proveedores de correo reales. Antes cualquier cosa que pasara
 * is_email() servía para registrarse — incluye los dominios de correo temporal/desechable
 * que existen justo para eso (mailinator.com, tempmail, guerrillamail...). Un dueño de bodega
 * real usa Gmail o Hotmail casi siempre; esto no le estorba a nadie de verdad y le cierra la
 * puerta a cuentas basura. Para agregar un dominio nuevo, solo hay que sumarlo a esta lista.
 */
function punto_dominio_correo_permitido( $email ) {
	$dominios = array(
		'gmail.com', 'googlemail.com',
		'hotmail.com', 'hotmail.es', 'hotmail.com.mx', 'hotmail.com.ar',
		'outlook.com', 'outlook.es',
		'live.com', 'live.com.ar', 'msn.com',
		'yahoo.com', 'yahoo.es', 'ymail.com',
		'icloud.com', 'me.com', 'mac.com',
		'aol.com',
		'protonmail.com', 'proton.me',
		'gmx.com', 'gmx.es',
		'zoho.com',
		'yandex.com',
	);
	$partes = explode( '@', strtolower( trim( $email ) ) );
	$dominio = end( $partes );
	return in_array( $dominio, $dominios, true );
}

function punto_api_registro( $request ) {
	$body    = $request->get_json_params();
	$email   = isset( $body['email'] ) ? sanitize_email( $body['email'] ) : '';
	$pass    = isset( $body['password'] ) ? (string) $body['password'] : '';
	$nombre  = isset( $body['nombre'] ) ? sanitize_text_field( $body['nombre'] ) : '';
	$tipo    = ( isset( $body['tipo'] ) && 'emprendedor' === $body['tipo'] ) ? 'emprendedor' : 'negocio';
	$id_num  = isset( $body['id_number'] ) ? sanitize_text_field( $body['id_number'] ) : '';
	$phone   = isset( $body['phone'] ) ? sanitize_text_field( $body['phone'] ) : '';
	$address = isset( $body['address'] ) ? sanitize_text_field( $body['address'] ) : '';

	if ( ! is_email( $email ) ) {
		return new WP_REST_Response( array( 'message' => 'Escribe un correo válido.' ), 400 );
	}
	if ( ! punto_dominio_correo_permitido( $email ) ) {
		return new WP_REST_Response( array( 'message' => 'Usa un correo de Gmail, Hotmail, Outlook, Yahoo o similar — no aceptamos correos temporales.' ), 400 );
	}
	if ( strlen( $pass ) < 8 ) {
		return new WP_REST_Response( array( 'message' => 'La contraseña debe tener al menos 8 caracteres.' ), 400 );
	}
	if ( email_exists( $email ) ) {
		return new WP_REST_Response( array( 'message' => 'Ya existe una cuenta con ese correo.' ), 409 );
	}

	$user_id = wp_create_user( $email, $pass, $email );
	if ( is_wp_error( $user_id ) ) {
		return new WP_REST_Response( array( 'message' => 'No se pudo crear la cuenta. Intenta de nuevo.' ), 500 );
	}

	update_user_meta( $user_id, 'punto_nombre', $nombre );
	update_user_meta( $user_id, 'punto_tipo', $tipo );
	update_user_meta( $user_id, 'punto_id_number', $id_num );
	update_user_meta( $user_id, 'punto_phone', $phone );
	update_user_meta( $user_id, 'punto_address', $address );

	$token = punto_emitir_token_cuenta( $user_id );
	// v1.12 — cuenta recién creada = prueba gratis recién empezada (60 días completos).
	$trial = punto_cuenta_trial_info( get_userdata( $user_id ) );

	return new WP_REST_Response( array( 'ok' => true, 'token' => $token, 'trial_activo' => $trial['activo'], 'trial_vence' => $trial['vence'] ), 201 );
}

function punto_api_login( $request ) {
	$body  = $request->get_json_params();
	$email = isset( $body['email'] ) ? sanitize_email( $body['email'] ) : '';
	$pass  = isset( $body['password'] ) ? (string) $body['password'] : '';

	$user = wp_authenticate( $email, $pass );
	if ( is_wp_error( $user ) ) {
		return new WP_REST_Response( array( 'message' => 'Correo o contraseña incorrectos.' ), 401 );
	}

	$token = punto_emitir_token_cuenta( $user->ID );

	// El teléfono nuevo no tiene forma de saber que esta cuenta ya pagó: el token de negocio
	// no se puede devolver (en la base solo está su hash). Se le manda el ESTADO, y con eso
	// desbloquea Informes/Contabilidad y sincroniza usando su token de cuenta (ver
	// punto_negocio_por_token). Sin esto, recuperar en un teléfono nuevo te dejaba en gratis.
	$negocio_id = punto_negocio_id_de_cuenta( $user->ID );
	// v1.12 — de una vez manda el estado de la prueba gratis, para que la app no tenga que
	// esperar una segunda llamada a /perfil para saber si desbloquea Informes/Contabilidad.
	$trial = punto_cuenta_trial_info( $user );

	return new WP_REST_Response(
		array(
			'ok'             => true,
			'token'          => $token,
			'negocio_activo' => (bool) $negocio_id,
			'negocio_id'     => $negocio_id ? (int) $negocio_id : null,
			'trial_activo'   => $trial['activo'],
			'trial_vence'    => $trial['vence'],
			'negocio' => array(
				'nombre'    => get_user_meta( $user->ID, 'punto_nombre', true ),
				'tipo'      => get_user_meta( $user->ID, 'punto_tipo', true ) ? get_user_meta( $user->ID, 'punto_tipo', true ) : 'negocio',
				'id_number' => get_user_meta( $user->ID, 'punto_id_number', true ),
				'phone'     => get_user_meta( $user->ID, 'punto_phone', true ),
				'address'   => get_user_meta( $user->ID, 'punto_address', true ),
			),
		),
		200
	);
}

/**
 * Perfil de la cuenta (v1.11) — separado de /activar y de "Negocio" en el cliente. Registro
 * (v1.1) escribe estos mismos campos UNA vez, al crear la cuenta; el cliente los copiaba a la
 * Configuración del negocio y ahí se quedaban, sin forma de editarlos después ni de que un
 * teléfono nuevo los recuperara. Estos dos endpoints son ese hueco: ver y actualizar la
 * identidad de la cuenta (quién eres) en cualquier momento, desde cualquier teléfono logueado.
 *
 * A propósito NO tocan email ni password — email cambiaría a qué correo llega la recuperación
 * de contraseña (mejor no hacerlo sin verificación, fuera de alcance por ahora) y password ya
 * tiene su propio flujo en /recuperar.
 */
function punto_api_perfil_ver( $request ) {
	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );
	if ( ! $cuenta ) {
		return new WP_REST_Response( array( 'message' => 'Token de cuenta inválido. Inicia sesión de nuevo.' ), 401 );
	}

	$trial = punto_cuenta_trial_info( $cuenta );

	return new WP_REST_Response(
		array(
			'ok'             => true,
			'email'          => $cuenta->user_email,
			'nombre'         => get_user_meta( $cuenta->ID, 'punto_nombre', true ),
			'tipo'           => get_user_meta( $cuenta->ID, 'punto_tipo', true ) ? get_user_meta( $cuenta->ID, 'punto_tipo', true ) : 'negocio',
			'id_number'      => get_user_meta( $cuenta->ID, 'punto_id_number', true ),
			'phone'          => get_user_meta( $cuenta->ID, 'punto_phone', true ),
			'address'        => get_user_meta( $cuenta->ID, 'punto_address', true ),
			// v1.14 — foto de perfil. Vive en la Biblioteca de Medios como cualquier otra imagen;
			// acá solo va la URL, nunca el base64 (el que sube la foto usa /perfil/foto).
			'foto_url'       => get_user_meta( $cuenta->ID, 'punto_foto_url', true ),
			// fecha de creación de la cuenta — la manda el propio wp_users, no se puede tocar
			// desde el teléfono. FrixPOS de anclaje para cosas como un período de prueba por cuenta.
			'fecha_registro' => $cuenta->user_registered,
			// v1.12 — prueba gratis de 60 días desde que se creó la cuenta, calculada acá (ver
			// punto_cuenta_trial_info). El cliente cachea esto y deja de confiar en el cache si
			// pasa mucho tiempo sin poder reconfirmarlo — así el reloj del teléfono no manda.
			'trial_activo'   => $trial['activo'],
			'trial_vence'    => $trial['vence'],
		),
		200
	);
}

function punto_api_perfil_guardar( $request ) {
	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );
	if ( ! $cuenta ) {
		return new WP_REST_Response( array( 'message' => 'Token de cuenta inválido. Inicia sesión de nuevo.' ), 401 );
	}

	$body    = $request->get_json_params();
	$nombre  = isset( $body['nombre'] ) ? sanitize_text_field( $body['nombre'] ) : '';
	$tipo    = ( isset( $body['tipo'] ) && 'emprendedor' === $body['tipo'] ) ? 'emprendedor' : 'negocio';
	$id_num  = isset( $body['id_number'] ) ? sanitize_text_field( $body['id_number'] ) : '';
	$phone   = isset( $body['phone'] ) ? sanitize_text_field( $body['phone'] ) : '';
	$address = isset( $body['address'] ) ? sanitize_text_field( $body['address'] ) : '';

	update_user_meta( $cuenta->ID, 'punto_nombre', $nombre );
	update_user_meta( $cuenta->ID, 'punto_tipo', $tipo );
	update_user_meta( $cuenta->ID, 'punto_id_number', $id_num );
	update_user_meta( $cuenta->ID, 'punto_phone', $phone );
	update_user_meta( $cuenta->ID, 'punto_address', $address );

	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/**
 * v1.14 — foto de perfil de la persona dueña de la cuenta. Gratis para todos, igual que el
 * resto del respaldo: se sube a la Biblioteca de Medios y la URL queda en user_meta, para
 * poder verla en FrixPOS → Respaldos sin tener que abrir el payload de nadie.
 * Reemplaza la anterior: una cuenta tiene UNA foto de perfil, no un álbum.
 */
function punto_api_perfil_foto( $request ) {
	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );
	if ( ! $cuenta ) {
		return new WP_REST_Response( array( 'message' => 'Token de cuenta inválido. Inicia sesión de nuevo.' ), 401 );
	}

	$body     = $request->get_json_params();
	$data_url = isset( $body['foto_base64'] ) ? (string) $body['foto_base64'] : '';

	$url = punto_guardar_foto_base64(
		$data_url,
		'Foto de perfil FrixPOS — cuenta ' . $cuenta->ID,
		$cuenta->ID,
		array( '_punto_perfil_cuenta_id' => $cuenta->ID )
	);
	if ( ! $url ) {
		return new WP_REST_Response( array( 'message' => 'No se pudo guardar la imagen (formato no soportado, o pesa más de 4MB).' ), 400 );
	}

	update_user_meta( $cuenta->ID, 'punto_foto_url', $url );

	return new WP_REST_Response( array( 'ok' => true, 'url' => $url ), 201 );
}

/**
 * Usa retrieve_password() del núcleo de WordPress — el mismo mecanismo de
 * "olvidé mi contraseña" de wp-login.php, correo con enlace incluido. El
 * dueño del negocio termina el reseteo en la página de WordPress y vuelve
 * a la app a iniciar sesión con la contraseña nueva.
 *
 * Responde el mismo mensaje exista o no la cuenta — no delata qué correos
 * están registrados (mismo criterio que ya usa punto_api_activar).
 */
function punto_api_recuperar( $request ) {
	$body  = $request->get_json_params();
	$email = isset( $body['email'] ) ? sanitize_email( $body['email'] ) : '';

	if ( is_email( $email ) && email_exists( $email ) ) {
		// El resultado se guarda para poder diagnosticar desde wp-admin. Antes se descartaba:
		// si wp_mail() fallaba (muy común en hosting compartido sin SMTP), al dueño del negocio
		// se le decía igual "te mandamos instrucciones" y no quedaba ni rastro de que falló.
		// No se le devuelve al cliente a propósito — eso delataría qué correos existen.
		$res     = retrieve_password( $email );
		$ok      = ! is_wp_error( $res );
		$detalle = $ok ? 'enviado' : $res->get_error_message();

		update_option( 'punto_ultimo_reset', array(
			'fecha'   => current_time( 'mysql' ),
			'ok'      => $ok,
			'detalle' => $detalle,
		), false );

		if ( ! $ok ) {
			error_log( '[FrixPOS] Falló el envío de recuperación de contraseña: ' . $detalle );
		}
	}

	return new WP_REST_Response(
		array(
			'ok'      => true,
			'message' => 'Si ese correo tiene una cuenta, te mandamos instrucciones. Revisa también tu carpeta de spam.',
		),
		200
	);
}

/**
 * Borra la cuenta que llama con su PROPIO token — nunca otra. El/los negocio(s) vinculados
 * se DESACTIVAN, no se borran: las ventas ya sincronizadas siguen existiendo para auditoría,
 * pero activo=0 hace que punto_negocio_id_de_cuenta() deje de encontrarlo, así que el
 * respaldo y el stock compartido entre cajas de ese negocio se apagan solos. Los respaldos
 * sí se borran del todo — a diferencia de las ventas, no tienen ningún valor fuera de la
 * cuenta que los generó.
 */
function punto_api_cuenta_borrar( $request ) {
	global $wpdb;
	$t = punto_tablas();

	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );
	if ( ! $cuenta ) {
		return new WP_REST_Response( array( 'message' => 'Token de cuenta inválido. Inicia sesión de nuevo.' ), 401 );
	}

	$wpdb->update( $t['negocios'], array( 'activo' => 0 ), array( 'wp_user_id' => $cuenta->ID ) );
	$wpdb->delete( $t['respaldos'], array( 'wp_user_id' => $cuenta->ID ) );

	require_once ABSPATH . 'wp-admin/includes/user.php';
	wp_delete_user( $cuenta->ID );

	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/**
 * Busca la cuenta dueña de un token (ver punto_emitir_token_cuenta). Como el token nunca
 * rota, cualquier dispositivo logueado con la misma cuenta puede traer uno válido — la
 * búsqueda es por hash exacto (WP_User_Query con meta_key+meta_value hace un WHERE directo,
 * no un LIKE, aunque haya varias filas con la misma clave por usuario).
 */
function punto_cuenta_por_token( $token ) {
	if ( ! $token ) {
		return null;
	}
	$usuarios = get_users( array(
		'meta_key'   => 'punto_token_hash',
		'meta_value' => hash( 'sha256', $token ),
		'number'     => 1,
	) );
	return $usuarios ? $usuarios[0] : null;
}

/**
 * negocio_id activo vinculado a esta cuenta, o 0 si no tiene. Si una cuenta llegó a activar
 * más de un código (el esquema lo permite), se queda con el más reciente — "un usuario de WP
 * = un negocio" sigue siendo el modelo asumido en el resto del proyecto.
 */
function punto_negocio_id_de_cuenta( $wp_user_id ) {
	global $wpdb;
	$t  = punto_tablas();
	$id = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM {$t['negocios']} WHERE wp_user_id = %d AND activo = 1 ORDER BY id DESC LIMIT 1",
		$wp_user_id
	) );
	return $id ? (int) $id : 0;
}

/**
 * ¿Esta cuenta tiene al menos un negocio activo vinculado? Decide la cuota de respaldos
 * (2 gratis / ~30 pago), si puede recuperar uno, y el candado de /stock (v1.8, multi-caja).
 */
function punto_cuenta_tiene_negocio_activo( $wp_user_id ) {
	return punto_negocio_id_de_cuenta( $wp_user_id ) > 0;
}

/**
 * Prueba gratis de 60 días (v1.12) — Informes y Contabilidad completos sin necesitar código,
 * a partir de que se CREA LA CUENTA. Se calcula acá, con el reloj del servidor y
 * `user_registered` (lo pone WordPress al crear el usuario, el teléfono no lo puede tocar) —
 * nunca con la fecha del teléfono. Un dueño que atrase o adelante el reloj de su celular no
 * gana ni pierde ni un día de prueba: lo único que puede pasar offline es que la app deje de
 * poder CONFIRMAR el estado (ver TRIAL_GRACIA_DIAS del lado del cliente), nunca que invente uno.
 */
function punto_cuenta_trial_info( $wp_user ) {
	$creada  = strtotime( $wp_user->user_registered . ' UTC' );
	$vence   = $creada + ( 60 * DAY_IN_SECONDS );
	$ahora   = current_time( 'timestamp', true ); // true = UTC, para comparar contra strtotime(... 'UTC')
	return array(
		'activo' => $ahora < $vence,
		'vence'  => gmdate( 'Y-m-d H:i:s', $vence ),
	);
}

function punto_api_activar( $request ) {
	global $wpdb;
	$t = punto_tablas();

	$body   = $request->get_json_params();
	$codigo = isset( $body['codigo'] ) ? strtoupper( trim( $body['codigo'] ) ) : '';
	$nombre = isset( $body['nombre_negocio'] ) ? sanitize_text_field( $body['nombre_negocio'] ) : '';

	if ( $codigo === '' ) {
		return new WP_REST_Response( array( 'message' => 'Escribe tu código de activación.' ), 400 );
	}

	$fila = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['codigos']} WHERE codigo = %s", $codigo ) );

	// Mismo mensaje para "no existe" y para "ya usado", a propósito: distinguirlos
	// le diría a quien esté probando códigos al azar cuáles existen.
	if ( ! $fila || (int) $fila->usado === 1 ) {
		return new WP_REST_Response( array( 'message' => 'Código inválido o ya usado.' ), 403 );
	}

	$token = wp_generate_password( 48, false, false );
	$ahora = current_time( 'mysql' );

	// v1.4 — si quien activa manda su token de cuenta (Authorization: Bearer), el negocio
	// queda vinculado a esa cuenta. Es opcional: activar sin cuenta sigue funcionando
	// exactamente igual que siempre, el código sigue siendo la única credencial que importa.
	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );

	$datos_negocio = array(
		'nombre'     => $nombre !== '' ? $nombre : 'Negocio sin nombre',
		'token_hash' => punto_hash_token( $token ),
		'activo'     => 1,
		'fecha_alta' => $ahora,
	);
	if ( $cuenta ) {
		$datos_negocio['wp_user_id'] = $cuenta->ID;
	}

	$creado = $wpdb->insert( $t['negocios'], $datos_negocio );

	if ( ! $creado ) {
		return new WP_REST_Response( array( 'message' => 'No se pudo crear el negocio. Intenta de nuevo.' ), 500 );
	}
	$negocio_id = (int) $wpdb->insert_id;

	// Se marca el código como usado con la condición usado = 0 metida en el UPDATE:
	// si dos teléfonos mandan el mismo código en el mismo instante, solo uno actualiza
	// filas y el otro queda descartado, sin necesidad de una transacción explícita.
	$marcado = $wpdb->query( $wpdb->prepare(
		"UPDATE {$t['codigos']} SET usado = 1, negocio_id = %d, fecha_usado = %s WHERE id = %d AND usado = 0",
		$negocio_id, $ahora, $fila->id
	) );

	if ( ! $marcado ) {
		// Perdió la carrera: se deshace el negocio recién creado para no dejar basura.
		$wpdb->delete( $t['negocios'], array( 'id' => $negocio_id ), array( '%d' ) );
		return new WP_REST_Response( array( 'message' => 'Código inválido o ya usado.' ), 403 );
	}

	return new WP_REST_Response( array(
		'token'      => $token,
		'negocio_id' => $negocio_id,
	), 200 );
}

function punto_api_sync( $request ) {
	global $wpdb;
	$t = punto_tablas();

	$negocio = punto_negocio_por_token( punto_token_de_request( $request ) );
	if ( ! $negocio ) {
		return new WP_REST_Response( array( 'message' => 'Token inválido o negocio desactivado.' ), 401 );
	}

	$body   = $request->get_json_params();
	$ventas = isset( $body['ventas'] ) && is_array( $body['ventas'] ) ? $body['ventas'] : array();

	$sincronizadas = array();
	$ahora         = current_time( 'mysql' );

	foreach ( $ventas as $v ) {
		$uuid = isset( $v['uuid'] ) ? substr( sanitize_text_field( $v['uuid'] ), 0, 64 ) : '';
		if ( $uuid === '' || ! isset( $v['payload'] ) ) {
			continue; // venta sin uuid: no se puede deduplicar, se ignora en silencio y el cliente la reintenta
		}

		$payload = wp_json_encode( $v['payload'] );
		if ( $payload === false ) {
			continue;
		}

		$monto = isset( $v['monto_bs'] ) ? (float) $v['monto_bs'] : 0;
		$fecha = isset( $v['fecha'] ) ? punto_fecha_mysql( $v['fecha'] ) : null;

		// INSERT ... ON DUPLICATE KEY UPDATE: si la venta ya estaba (reintento tras una
		// respuesta que el teléfono no llegó a recibir), no duplica y tampoco falla.
		// En ambos casos el uuid se confirma, que es justo lo que el cliente necesita
		// para dejar de reintentarla.
		$sql = $wpdb->prepare(
			"INSERT INTO {$t['ventas']} (negocio_id, uuid, payload, monto_bs, fecha_venta, fecha_sync)
			 VALUES (%d, %s, %s, %f, %s, %s)
			 ON DUPLICATE KEY UPDATE payload = VALUES(payload), monto_bs = VALUES(monto_bs), fecha_venta = VALUES(fecha_venta)",
			$negocio->id, $uuid, $payload, $monto, $fecha, $ahora
		);

		if ( $wpdb->query( $sql ) !== false ) {
			$sincronizadas[] = $uuid;
		}
	}

	if ( $sincronizadas ) {
		$wpdb->update( $t['negocios'], array( 'ultimo_sync' => $ahora ), array( 'id' => $negocio->id ), array( '%s' ), array( '%d' ) );
	}

	return new WP_REST_Response( array( 'sincronizadas_uuids' => $sincronizadas ), 200 );
}

/**
 * El POS manda la fecha como ISO 8601 del navegador (2026-08-09T15:45:00.000Z).
 * MySQL no la traga tal cual, y si se guarda mal se pierde el orden cronológico
 * de las ventas — que es justo lo que hace falta para los informes.
 */
function punto_fecha_mysql( $iso ) {
	$ts = strtotime( $iso );
	if ( ! $ts ) {
		return current_time( 'mysql' );
	}
	return gmdate( 'Y-m-d H:i:s', $ts );
}

/* ==========================================================================
 * 3c. LAS CUENTAS DE FRIXPOS NO SON USUARIOS DE WORDPRESS (v1.2)
 * ==========================================================================
 * Bug real reportado por Jonathan probando en el sitio: al recuperar contraseña,
 * WordPress termina el flujo mandando a iniciar sesión en wp-login.php, y desde
 * ahí entra al panel de wp-admin — vacío y sin permisos para una cuenta punto_*,
 * pero de todas formas expone infraestructura de WordPress a un dueño de bodega
 * que solo debería ver la app. Estas cuentas existen en wp_users solo porque
 * WordPress ya trae login/registro/recuperar-clave hechos y probados; no están
 * pensadas para tocar wp-admin nunca.
 * ========================================================================== */

// Después de cambiar la contraseña, WordPress por defecto deja a la persona en
// una pantalla de wp-login.php que enlaza a iniciar sesión ahí mismo. Para las
// cuentas de FrixPOS la mandamos derecho de vuelta a la app — no tiene nada que
// hacer en wp-login.php una vez cambiada la clave.
add_action( 'after_password_reset', function ( $user ) {
	if ( ! metadata_exists( 'user', $user->ID, 'punto_nombre' ) ) {
		return; // no es una cuenta de FrixPOS (ej. el propio admin del sitio) — se deja el flujo normal de WordPress
	}
	wp_safe_redirect( home_url( '/pos/?reset=ok' ) );
	exit;
} );

// Si de todas formas alguien con una cuenta de FrixPOS llega a /wp-admin/ (por el
// enlace que WordPress deja en el correo, por costumbre, etc.), se le rebota a
// la app en vez de dejarlo ver el panel — así sea un panel vacío sin permisos.
// current_user_can('manage_options') es la señal de "esta sí es una cuenta real
// de administrador del sitio" (Jonathan) — a esas nunca se les toca el acceso.
add_action( 'admin_init', function () {
	if ( wp_doing_ajax() || current_user_can( 'manage_options' ) || ! is_user_logged_in() ) {
		return;
	}
	wp_safe_redirect( home_url( '/pos/' ) );
	exit;
} );

/* ==========================================================================
 * 3d. RESPALDO (v1.5)
 * ==========================================================================
 * El servidor NUNCA recalcula nada de esto — ni precios, ni IVA, ni ganancia.
 * `payload` es el estado tal cual lo arma el POS (JSON, sin fotos) y `resumen`
 * son conteos/totales que el POS también calculó — aquí solo se guardan y se
 * devuelven. Dos endpoints de lectura separados a propósito: /respaldo (lista
 * + resumen) es seguro de mostrar sin haber pagado — es lo que arma la
 * pantalla de "esto vas a perder" —, y /respaldo/{id}/completo (el payload
 * de verdad) exige negocio activo, porque ESO es lo que de verdad vale.
 * ========================================================================== */

/**
 * Cuántos respaldos le tocan a esta cuenta y poda los más viejos si se pasó del cupo.
 * Se llama después de cada guardado — nunca antes, porque el cupo puede haber cambiado
 * (activó un código) desde el último respaldo.
 */
function punto_respaldo_podar( $wp_user_id ) {
	global $wpdb;
	$t    = punto_tablas();
	$cupo = punto_cuenta_tiene_negocio_activo( $wp_user_id ) ? 30 : 2;

	$total = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$t['respaldos']} WHERE wp_user_id = %d",
		$wp_user_id
	) );

	if ( $total > $cupo ) {
		$sobrantes = $total - $cupo;
		$viejos    = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM {$t['respaldos']} WHERE wp_user_id = %d ORDER BY fecha ASC LIMIT %d",
			$wp_user_id,
			$sobrantes
		) );
		if ( $viejos ) {
			$ids_sql = implode( ',', array_map( 'intval', $viejos ) );
			$wpdb->query( "DELETE FROM {$t['respaldos']} WHERE id IN ($ids_sql)" );
		}
	}

	return $cupo;
}

/**
 * Decodifica una foto en base64 (data:image/...;base64,...) y la sube de verdad a la
 * Biblioteca de Medios. Devuelve la URL pública, o false si el formato/tamaño no sirve o la
 * subida falla. Compartido entre /respaldo/foto (REST, fotos del negocio) y la aprobación de
 * sugerencias del catálogo maestro en wp-admin (v1.11) — es exactamente el mismo trabajo en
 * los dos casos, solo cambia de dónde sale el base64 y quién queda como autor.
 */
function punto_guardar_foto_base64( $data_url, $titulo, $autor_id = 0, $meta = array() ) {
	if ( ! preg_match( '#^data:image/(png|jpe?g|webp);base64,(.+)$#', $data_url, $m ) ) {
		return false;
	}
	$ext = ( 'jpeg' === $m[1] ) ? 'jpg' : $m[1];
	$bin = base64_decode( $m[2] );
	if ( ! $bin || strlen( $bin ) > 4 * 1024 * 1024 ) {
		return false;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$nombre  = 'punto-' . time() . '-' . wp_generate_password( 6, false, false ) . '.' . $ext;
	$archivo = wp_upload_bits( $nombre, null, $bin );
	if ( ! empty( $archivo['error'] ) ) {
		return false;
	}

	$mime          = ( 'jpg' === $ext ) ? 'image/jpeg' : 'image/' . $ext;
	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $mime,
			'post_title'     => $titulo,
			'post_status'    => 'inherit',
			'post_author'    => $autor_id,
		),
		$archivo['file']
	);
	if ( $attachment_id && ! is_wp_error( $attachment_id ) ) {
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $archivo['file'] ) );
		foreach ( $meta as $meta_key => $meta_value ) {
			update_post_meta( $attachment_id, $meta_key, $meta_value );
		}
	}

	return $archivo['url'];
}

/**
 * v1.14 — TODAS las fotos suben, pague o no el negocio (decisión de Jonathan). Antes esto
 * exigía negocio activo, así que en la prueba gratis las fotos nunca llegaban al servidor.
 * El modelo ahora es: se guarda todo de todos; lo que se paga es poder RECUPERARLO
 * (ver punto_api_respaldo_completo, que sí sigue exigiendo negocio activo).
 * Cada foto queda etiquetada con la cuenta dueña para poder listarla en FrixPOS → Respaldos.
 */
function punto_api_respaldo_foto( $request ) {
	global $wpdb;

	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );
	if ( ! $cuenta ) {
		return new WP_REST_Response( array( 'message' => 'Token de cuenta inválido. Inicia sesión de nuevo.' ), 401 );
	}

	// tope de infraestructura, no de pago: nadie usa esto como hosting de fotos gratis.
	$actuales = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_punto_respaldo_cuenta_id' AND meta_value = %d",
		$cuenta->ID
	) );
	if ( $actuales >= 800 ) {
		return new WP_REST_Response( array( 'message' => 'Llegaste al tope de fotos respaldadas de esta cuenta.' ), 403 );
	}

	$body     = $request->get_json_params();
	$data_url = isset( $body['foto_base64'] ) ? (string) $body['foto_base64'] : '';

	$url = punto_guardar_foto_base64(
		$data_url,
		'Respaldo FrixPOS — cuenta ' . $cuenta->ID,
		$cuenta->ID,
		array( '_punto_respaldo_cuenta_id' => $cuenta->ID )
	);
	if ( ! $url ) {
		return new WP_REST_Response( array( 'message' => 'No se pudo guardar la imagen (formato no soportado, o pesa más de 4MB).' ), 400 );
	}

	return new WP_REST_Response( array( 'ok' => true, 'url' => $url ), 201 );
}

function punto_api_respaldo_guardar( $request ) {
	global $wpdb;
	$t = punto_tablas();

	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );
	if ( ! $cuenta ) {
		return new WP_REST_Response( array( 'message' => 'Token de cuenta inválido. Inicia sesión de nuevo.' ), 401 );
	}

	$body        = $request->get_json_params();
	$payload_arr = isset( $body['payload'] ) && is_array( $body['payload'] ) ? $body['payload'] : null;
	$resumen     = isset( $body['resumen'] ) ? wp_json_encode( $body['resumen'] ) : '{}';

	if ( ! $payload_arr ) {
		return new WP_REST_Response( array( 'message' => 'Falta el respaldo a guardar.' ), 400 );
	}

	// v1.14 — YA NO se le quitan las fotos a nadie. Se guarda el estado completo de todos los
	// negocios, paguen o no: el candado del plan está en RECUPERAR (punto_api_respaldo_completo),
	// no en guardar. Así, si un negocio gratis decide pagar después, su información sigue entera
	// y la puede recuperar — antes se la habíamos ido borrando por el camino sin que lo supiera.
	$payload = wp_json_encode( $payload_arr );

	$hoy   = current_time( 'Y-m-d' );
	$ahora = current_time( 'mysql' );

	// Un respaldo por día: si ya hay uno de hoy, se sobrescribe — varios cierres de turno o
	// toques de "Respaldar ahora" el mismo día no cuentan como respaldos distintos.
	$wpdb->query( $wpdb->prepare(
		"INSERT INTO {$t['respaldos']} (wp_user_id, fecha, payload, resumen, creado_en)
		 VALUES (%d, %s, %s, %s, %s)
		 ON DUPLICATE KEY UPDATE payload = VALUES(payload), resumen = VALUES(resumen), creado_en = VALUES(creado_en)",
		$cuenta->ID,
		$hoy,
		$payload,
		$resumen,
		$ahora
	) );

	$cupo     = punto_respaldo_podar( $cuenta->ID );
	$cantidad = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$t['respaldos']} WHERE wp_user_id = %d",
		$cuenta->ID
	) );

	return new WP_REST_Response( array( 'ok' => true, 'fecha' => $hoy, 'cantidad' => $cantidad, 'cupo' => $cupo ), 200 );
}

function punto_api_respaldo_listar( $request ) {
	global $wpdb;
	$t = punto_tablas();

	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );
	if ( ! $cuenta ) {
		return new WP_REST_Response( array( 'message' => 'Token de cuenta inválido. Inicia sesión de nuevo.' ), 401 );
	}

	$filas = $wpdb->get_results( $wpdb->prepare(
		"SELECT id, fecha, resumen FROM {$t['respaldos']} WHERE wp_user_id = %d ORDER BY fecha DESC",
		$cuenta->ID
	) );

	$respaldos = array_map(
		function ( $f ) {
			return array(
				'id'      => (int) $f->id,
				'fecha'   => $f->fecha,
				'resumen' => json_decode( $f->resumen ),
			);
		},
		$filas
	);

	return new WP_REST_Response(
		array(
			'ok'        => true,
			'cupo'      => punto_cuenta_tiene_negocio_activo( $cuenta->ID ) ? 30 : 2,
			'respaldos' => $respaldos,
		),
		200
	);
}

function punto_api_respaldo_completo( $request ) {
	global $wpdb;
	$t = punto_tablas();

	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );
	if ( ! $cuenta ) {
		return new WP_REST_Response( array( 'message' => 'Token de cuenta inválido. Inicia sesión de nuevo.' ), 401 );
	}

	if ( ! punto_cuenta_tiene_negocio_activo( $cuenta->ID ) ) {
		return new WP_REST_Response(
			array(
				'message'              => 'Activa tu código para recuperar el respaldo completo.',
				'requiere_activacion' => true,
			),
			403
		);
	}

	$id   = (int) $request['id'];
	$fila = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['respaldos']} WHERE id = %d", $id ) );

	if ( ! $fila || (int) $fila->wp_user_id !== (int) $cuenta->ID ) {
		return new WP_REST_Response( array( 'message' => 'Respaldo no encontrado.' ), 404 );
	}

	return new WP_REST_Response(
		array(
			'ok'      => true,
			'fecha'   => $fila->fecha,
			'payload' => json_decode( $fila->payload ),
		),
		200
	);
}

/* ==========================================================================
 * 3d-bis. STOCK MULTI-CAJA (v1.8) — Opción B: nunca bloquea una venta por falta de
 * internet, cada caja sigue vendiendo sola siempre. El servidor solo lleva la SUMA de lo
 * que cada caja le reporta (deltas, no valores absolutos) — eso es lo que hace que sumar
 * reportes de dos cajas sin coordinarse entre sí sea seguro, sin locks ni transacciones.
 * Se autentica con el token de CUENTA (no el de negocio) a propósito: dos teléfonos logueados
 * en la MISMA cuenta ya son "la misma negocio" sin tener que reactivar ningún código — el
 * candado real es punto_negocio_id_de_cuenta(), que exige negocio activo (403 si no, feature
 * paga).
 * ========================================================================== */

function punto_api_stock_mover( $request ) {
	global $wpdb;
	$t = punto_tablas();

	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );
	if ( ! $cuenta ) {
		return new WP_REST_Response( array( 'message' => 'Token de cuenta inválido. Inicia sesión de nuevo.' ), 401 );
	}
	$negocio_id = punto_negocio_id_de_cuenta( $cuenta->ID );
	if ( ! $negocio_id ) {
		return new WP_REST_Response( array( 'message' => 'Activa tu código para sincronizar el stock entre cajas.' ), 403 );
	}

	$body        = $request->get_json_params();
	$movimientos = isset( $body['movimientos'] ) && is_array( $body['movimientos'] ) ? $body['movimientos'] : array();
	$ahora       = current_time( 'mysql' );

	foreach ( $movimientos as $m ) {
		$producto_id = isset( $m['producto_id'] ) ? substr( sanitize_text_field( $m['producto_id'] ), 0, 64 ) : '';
		$delta       = isset( $m['delta'] ) ? (int) $m['delta'] : 0;
		if ( '' === $producto_id || 0 === $delta ) {
			continue;
		}
		// upsert atómico: si la fila no existe todavía para este producto, arranca en el delta
		// tal cual (equivale a "0 + delta"). ON DUPLICATE KEY suma sobre lo que ya había — así
		// dos cajas reportando al mismo tiempo no se pisan, cada UPDATE es su propia operación
		// atómica en MySQL, no hace falta ninguna transacción ni lock explícito.
		$wpdb->query( $wpdb->prepare(
			"INSERT INTO {$t['stock']} (negocio_id, producto_id, stock, actualizado_en)
			 VALUES (%d, %s, %d, %s)
			 ON DUPLICATE KEY UPDATE stock = stock + VALUES(stock), actualizado_en = VALUES(actualizado_en)",
			$negocio_id,
			$producto_id,
			$delta,
			$ahora
		) );
	}

	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

function punto_api_stock_listar( $request ) {
	global $wpdb;
	$t = punto_tablas();

	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );
	if ( ! $cuenta ) {
		return new WP_REST_Response( array( 'message' => 'Token de cuenta inválido. Inicia sesión de nuevo.' ), 401 );
	}
	$negocio_id = punto_negocio_id_de_cuenta( $cuenta->ID );
	if ( ! $negocio_id ) {
		return new WP_REST_Response( array( 'message' => 'Activa tu código para sincronizar el stock entre cajas.' ), 403 );
	}

	// 'desde' (opcional) — cursor de tiempo que el propio servidor le dio al cliente la última
	// vez (servidor_ahora, abajo), nunca el reloj del teléfono: dos dispositivos con la hora
	// mal puesta no deben poder perderse movimientos ni pedir la tabla completa por error.
	$desde = $request->get_param( 'desde' );
	if ( $desde ) {
		$filas = $wpdb->get_results( $wpdb->prepare(
			"SELECT producto_id, stock FROM {$t['stock']} WHERE negocio_id = %d AND actualizado_en > %s",
			$negocio_id,
			sanitize_text_field( $desde )
		) );
	} else {
		$filas = $wpdb->get_results( $wpdb->prepare(
			"SELECT producto_id, stock FROM {$t['stock']} WHERE negocio_id = %d",
			$negocio_id
		) );
	}

	return new WP_REST_Response(
		array(
			'ok'             => true,
			'servidor_ahora' => current_time( 'mysql' ),
			'stock'          => $filas,
		),
		200
	);
}

/* ==========================================================================
 * 3d-quater. SINCRONIZACIÓN EN TIEMPO REAL — Ably (v1.27)
 * ==========================================================================
 * Este endpoint SOLO entrega un token de Ably de corta vida (1 hora), con
 * permiso restringido al canal de UN negocio. Nunca pasa datos de negocio ni
 * la API Key completa — esa vive solo en wp_options (Punto → Tiempo real),
 * nunca en el tema ni en el cliente.
 *
 * Ably en sí NO reemplaza a /stock, /respaldo, etc.: solo avisa "algo cambió"
 * entre los dispositivos de la MISMA cuenta para que refresquen al instante
 * en vez de esperar el próximo sync manual. El dato de verdad se sigue
 * pidiendo por la API de siempre — así no hay dos caminos distintos con la
 * misma lógica de negocio, ni el cliente puede inventarse un dato con solo
 * publicar un mensaje en el canal.
 *
 * v1.27 — CAMBIO IMPORTANTE: el token se firma LOCAL (JWT, HMAC-SHA256 con
 * hash_hmac, cero librerías), no se le pide a Ably por HTTP. La v1.25
 * original llamaba a wp_remote_post() contra Ably — en hosting compartido
 * (Hostinger y similares) esa conexión SALIENTE desde PHP suele estar
 * bloqueada o no responder, y el síntoma es específico: /ably-token daba
 * 502 (Cloudflare "origin no respondió") en TODOS los casos, incluso con
 * negocio activo y API Key bien puesta — nada que ver con el dato, es la
 * conexión de salida la que nunca llegaba. Firmar el JWT acá adentro no
 * necesita red para nada: es aritmética con la API Key que ya está guardada
 * en este servidor. Ver "Ably Pub/Sub | JSON Web Tokens (JWTs)" en los docs
 * de Ably para el formato exacto (claim x-ably-capability, etc.).
 *
 * Mismo candado que /stock: exige negocio activo (feature paga). El canal es
 * "negocio-{id}", no el id de cuenta — dos teléfonos de la misma cuenta ya
 * comparten negocio_id sin tener que reactivar ningún código.
 */
function punto_base64url( $datos_binarios ) {
	return rtrim( strtr( base64_encode( $datos_binarios ), '+/', '-_' ), '=' );
}

/**
 * JWT firmado para Ably. $key_name es la parte antes de los ':' de la API Key ("appId.keyId"),
 * $key_secret la parte de después — nunca se manda ninguna de las dos al cliente, solo el JWT ya
 * firmado, que Ably puede validar pero de donde no se puede reconstruir la key.
 */
function punto_ably_jwt( $key_name, $key_secret, $capability_json, $client_id, $ttl_segundos ) {
	$ahora   = time();
	$header  = array( 'typ' => 'JWT', 'alg' => 'HS256', 'kid' => $key_name );
	$claims  = array(
		'iat'               => $ahora,
		'exp'               => $ahora + $ttl_segundos,
		'x-ably-capability' => $capability_json,
		'x-ably-clientId'   => $client_id,
	);
	$sin_firmar = punto_base64url( wp_json_encode( $header ) ) . '.' . punto_base64url( wp_json_encode( $claims ) );
	$firma      = hash_hmac( 'sha256', $sin_firmar, $key_secret, true );
	return $sin_firmar . '.' . punto_base64url( $firma );
}

function punto_api_ably_token( $request ) {
	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );
	if ( ! $cuenta ) {
		return new WP_REST_Response( array( 'message' => 'Token de cuenta inválido. Inicia sesión de nuevo.' ), 401 );
	}
	$negocio_id = punto_negocio_id_de_cuenta( $cuenta->ID );
	if ( ! $negocio_id ) {
		return new WP_REST_Response( array( 'message' => 'La sincronización en tiempo real requiere un negocio activo.', 'requiere_activacion' => true ), 403 );
	}

	$api_key = trim( (string) get_option( 'punto_ably_api_key' ) );
	if ( '' === $api_key || false === strpos( $api_key, ':' ) ) {
		return new WP_REST_Response( array( 'message' => 'Sincronización en tiempo real no configurada en el servidor.' ), 500 );
	}
	list( $key_name, $key_secret ) = explode( ':', $api_key, 2 );

	// Un solo canal por negocio, con los tres permisos que necesita: publicar sus propios
	// cambios, escuchar los de otros dispositivos de la misma cuenta, y presence (para saber
	// qué dispositivos están conectados ahora mismo).
	$canal      = 'negocio-' . $negocio_id;
	$capability = wp_json_encode( array( $canal => array( 'publish', 'subscribe', 'presence' ) ) );

	$jwt = punto_ably_jwt( $key_name, $key_secret, $capability, 'cuenta-' . $cuenta->ID, HOUR_IN_SECONDS );

	// El string PELADO, no {token: $jwt}: ably-js 2.x, al recibirlo por authUrl, revisa el
	// objeto buscando 'keyName' (TokenRequest) o 'issued' (TokenDetails) — un objeto con solo
	// 'token' no matchea ninguno de los dos y lo rechaza ("has neither a keyName nor an issued
	// field", confirmado contra el SDK real). Mandado como string suelto, WP igual lo entrega
	// como JSON válido (`"eyJ..."`, con comillas) y ably-js lo interpreta directo como el token.
	return new WP_REST_Response( $jwt, 200 );
}

/* ==========================================================================
 * 3d-quinquies. BITÁCORA DE CAMBIOS PARA TIEMPO REAL (v1.26)
 * ==========================================================================
 * Ably por sí solo es "dispara y olvida": si el otro dispositivo está apagado
 * o sin señal cuando se publica un mensaje, ese mensaje se pierde para
 * siempre. Esta bitácora es la red de seguridad: el dispositivo que publica
 * un cambio por Ably TAMBIÉN lo guarda aquí (mismo payload, sin validar ni
 * interpretar — igual filosofía que /sync y /stock/mover); cuando el otro
 * dispositivo vuelve a tener señal, pide "qué me perdí desde el cursor X" y
 * se pone al día sin importar cuánto tiempo estuvo apagado.
 *
 * Mismo candado que /stock y /ably-token: exige negocio activo.
 * ========================================================================== */

function punto_api_cambios_guardar( $request ) {
	global $wpdb;
	$t = punto_tablas();

	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );
	if ( ! $cuenta ) {
		return new WP_REST_Response( array( 'message' => 'Token de cuenta inválido. Inicia sesión de nuevo.' ), 401 );
	}
	$negocio_id = punto_negocio_id_de_cuenta( $cuenta->ID );
	if ( ! $negocio_id ) {
		return new WP_REST_Response( array( 'message' => 'La sincronización en tiempo real requiere un negocio activo.', 'requiere_activacion' => true ), 403 );
	}

	$body    = $request->get_json_params();
	$origen  = isset( $body['origen'] ) ? substr( sanitize_text_field( $body['origen'] ), 0, 64 ) : '';
	$tipo    = isset( $body['tipo'] ) ? substr( sanitize_text_field( $body['tipo'] ), 0, 40 ) : '';
	$payload = isset( $body['payload'] ) ? wp_json_encode( $body['payload'] ) : '';
	if ( '' === $tipo || '' === $payload ) {
		return new WP_REST_Response( array( 'message' => 'Faltan datos del cambio.' ), 400 );
	}

	$wpdb->insert(
		$t['cambios'],
		array(
			'negocio_id' => $negocio_id,
			'origen'     => $origen,
			'tipo'       => $tipo,
			'payload'    => $payload,
			'creado_en'  => current_time( 'mysql' ),
		),
		array( '%d', '%s', '%s', '%s', '%s' )
	);
	$id = (int) $wpdb->insert_id;

	// Poda: esto es la red de seguridad de un rato desconectado, no un historial permanente
	// (para eso ya existe /respaldo) — se quedan solo los últimos 500 cambios por negocio.
	$cupo   = 500;
	$cutoff = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM {$t['cambios']} WHERE negocio_id = %d ORDER BY id DESC LIMIT 1 OFFSET %d",
		$negocio_id,
		$cupo
	) );
	if ( $cutoff ) {
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$t['cambios']} WHERE negocio_id = %d AND id <= %d",
			$negocio_id,
			(int) $cutoff
		) );
	}

	return new WP_REST_Response( array( 'ok' => true, 'id' => $id ), 200 );
}

function punto_api_cambios_listar( $request ) {
	global $wpdb;
	$t = punto_tablas();

	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );
	if ( ! $cuenta ) {
		return new WP_REST_Response( array( 'message' => 'Token de cuenta inválido. Inicia sesión de nuevo.' ), 401 );
	}
	$negocio_id = punto_negocio_id_de_cuenta( $cuenta->ID );
	if ( ! $negocio_id ) {
		return new WP_REST_Response( array( 'message' => 'La sincronización en tiempo real requiere un negocio activo.', 'requiere_activacion' => true ), 403 );
	}

	// Sin 'desde': NO se reproduce la bitácora completa, solo se ancla el cursor de arranque
	// para la próxima vez. El estado ACTUAL ya lo trae /stock, /respaldo, etc. — esta bitácora
	// es solo para ponerse al día tras estar desconectado, no para el arranque inicial.
	$desde = $request->get_param( 'desde' );
	$filas = array();
	if ( $desde ) {
		$filas = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, origen, tipo, payload, creado_en FROM {$t['cambios']} WHERE negocio_id = %d AND id > %d ORDER BY id ASC",
			$negocio_id,
			(int) $desde
		) );
		foreach ( $filas as &$fila ) {
			$fila->id      = (int) $fila->id;
			$fila->payload = json_decode( $fila->payload );
		}
		unset( $fila );
	}

	$ultimo_id = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT MAX(id) FROM {$t['cambios']} WHERE negocio_id = %d",
		$negocio_id
	) );

	return new WP_REST_Response(
		array(
			'ok'        => true,
			'cambios'   => $filas,
			'ultimo_id' => $ultimo_id,
		),
		200
	);
}

/* ==========================================================================
 * 3e. CONTABILIDAD MENSUAL (v1.6)
 * ==========================================================================
 * Mismo patrón que el respaldo (3d), pero por MES en vez de por día, y sin
 * ventana móvil: una vez guardado un mes, se queda hasta que se pode por
 * cupo. El POS ya trae contabData() + mesCalDates() (v26_4 del proyecto
 * original) — esto no reinventa el cálculo, solo lo guarda cuando el cliente
 * lo manda. `resumen` son los números (ganancia, IVA débito/crédito, etc.),
 * `detalle` son las ventas y egresos de ese mes exacto — con eso el cliente
 * puede rearmar sus exportadores de CSV (Libro de Ventas/Compras) ya
 * existentes sin que el servidor tenga que saber nada de su formato.
 * ========================================================================== */

function punto_contab_podar( $wp_user_id ) {
	global $wpdb;
	$t    = punto_tablas();
	$cupo = punto_cuenta_tiene_negocio_activo( $wp_user_id ) ? 12 : 1;

	$total = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$t['contab_mensual']} WHERE wp_user_id = %d",
		$wp_user_id
	) );

	if ( $total > $cupo ) {
		$sobrantes = $total - $cupo;
		$viejos    = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM {$t['contab_mensual']} WHERE wp_user_id = %d ORDER BY mes ASC LIMIT %d",
			$wp_user_id,
			$sobrantes
		) );
		if ( $viejos ) {
			$ids_sql = implode( ',', array_map( 'intval', $viejos ) );
			$wpdb->query( "DELETE FROM {$t['contab_mensual']} WHERE id IN ($ids_sql)" );
		}
	}

	return $cupo;
}

function punto_api_contab_guardar( $request ) {
	global $wpdb;
	$t = punto_tablas();

	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );
	if ( ! $cuenta ) {
		return new WP_REST_Response( array( 'message' => 'Token de cuenta inválido. Inicia sesión de nuevo.' ), 401 );
	}

	$body    = $request->get_json_params();
	$mes     = isset( $body['mes'] ) ? sanitize_text_field( $body['mes'] ) : '';
	$resumen = isset( $body['resumen'] ) ? wp_json_encode( $body['resumen'] ) : null;
	$detalle = isset( $body['detalle'] ) ? wp_json_encode( $body['detalle'] ) : '{}';

	if ( ! preg_match( '/^\d{4}-\d{2}$/', $mes ) || ! $resumen ) {
		return new WP_REST_Response( array( 'message' => 'Falta el mes o el resumen a guardar.' ), 400 );
	}

	$ahora = current_time( 'mysql' );

	$wpdb->query( $wpdb->prepare(
		"INSERT INTO {$t['contab_mensual']} (wp_user_id, mes, resumen, detalle, creado_en)
		 VALUES (%d, %s, %s, %s, %s)
		 ON DUPLICATE KEY UPDATE resumen = VALUES(resumen), detalle = VALUES(detalle), creado_en = VALUES(creado_en)",
		$cuenta->ID,
		$mes,
		$resumen,
		$detalle,
		$ahora
	) );

	$cupo = punto_contab_podar( $cuenta->ID );

	return new WP_REST_Response( array( 'ok' => true, 'mes' => $mes, 'cupo' => $cupo ), 200 );
}

function punto_api_contab_listar( $request ) {
	global $wpdb;
	$t = punto_tablas();

	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );
	if ( ! $cuenta ) {
		return new WP_REST_Response( array( 'message' => 'Token de cuenta inválido. Inicia sesión de nuevo.' ), 401 );
	}

	$filas = $wpdb->get_results( $wpdb->prepare(
		"SELECT mes, resumen FROM {$t['contab_mensual']} WHERE wp_user_id = %d ORDER BY mes DESC",
		$cuenta->ID
	) );

	$meses = array_map(
		function ( $f ) {
			return array( 'mes' => $f->mes, 'resumen' => json_decode( $f->resumen ) );
		},
		$filas
	);

	return new WP_REST_Response(
		array(
			'ok'    => true,
			'cupo'  => punto_cuenta_tiene_negocio_activo( $cuenta->ID ) ? 12 : 1,
			'meses' => $meses,
		),
		200
	);
}

function punto_api_contab_completo( $request ) {
	global $wpdb;
	$t = punto_tablas();

	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );
	if ( ! $cuenta ) {
		return new WP_REST_Response( array( 'message' => 'Token de cuenta inválido. Inicia sesión de nuevo.' ), 401 );
	}

	if ( ! punto_cuenta_tiene_negocio_activo( $cuenta->ID ) ) {
		return new WP_REST_Response(
			array(
				'message'             => 'Activa tu código para recuperar el detalle completo de ese mes.',
				'requiere_activacion' => true,
			),
			403
		);
	}

	$mes  = sanitize_text_field( $request['mes'] );
	$fila = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$t['contab_mensual']} WHERE wp_user_id = %d AND mes = %s",
		$cuenta->ID,
		$mes
	) );

	if ( ! $fila ) {
		return new WP_REST_Response( array( 'message' => 'No hay contabilidad guardada para ese mes.' ), 404 );
	}

	return new WP_REST_Response(
		array(
			'ok'      => true,
			'mes'     => $fila->mes,
			'resumen' => json_decode( $fila->resumen ),
			'detalle' => json_decode( $fila->detalle ),
		),
		200
	);
}

/* ==========================================================================
 * 3f. CATÁLOGO MAESTRO (v1.7)
 * ==========================================================================
 * Compartido entre TODOS los negocios — nunca lleva costo ni precio, eso es
 * privado de cada uno. /buscar es pública a propósito (ayuda a arrancar
 * rápido a cualquiera, con o sin código); /sugerir pide cuenta para saber
 * quién mandó cada sugerencia, pero no bloquea nada si falla — el producto
 * ya se guardó local en el negocio de todas formas, esto es solo un extra.
 * ========================================================================== */

function punto_api_catalogo_buscar( $request ) {
	global $wpdb;
	$t = punto_tablas();

	$q = sanitize_text_field( (string) $request->get_param( 'q' ) );
	if ( strlen( trim( $q ) ) < 2 ) {
		return new WP_REST_Response( array( 'ok' => true, 'resultados' => array() ), 200 );
	}

	$qn   = punto_normalizar_texto( $q );
	$like = '%' . $wpdb->esc_like( $qn ) . '%';

	// prefijo primero (más relevante), el resto alfabético — sin nada más sofisticado, el
	// volumen de este catálogo (miles, no millones) no lo necesita.
	$filas = $wpdb->get_results( $wpdb->prepare(
		"SELECT id, nombre, marca, sku, categoria, unidad_tipo, unidad_valor_base, foto_url
		 FROM {$t['catalogo_maestro']}
		 WHERE estado = 'aprobado' AND nombre_normalizado LIKE %s
		 ORDER BY (nombre_normalizado LIKE %s) DESC, nombre ASC
		 LIMIT 20",
		$like,
		$wpdb->esc_like( $qn ) . '%'
	) );

	return new WP_REST_Response( array( 'ok' => true, 'resultados' => $filas ), 200 );
}

function punto_api_catalogo_sugerir( $request ) {
	global $wpdb;
	$t = punto_tablas();

	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );

	$body    = $request->get_json_params();
	$nombre  = isset( $body['nombre'] ) ? sanitize_text_field( $body['nombre'] ) : '';
	$marca   = isset( $body['marca'] ) ? sanitize_text_field( $body['marca'] ) : '';
	$sku     = isset( $body['sku'] ) ? sanitize_text_field( $body['sku'] ) : '';
	$cat     = isset( $body['categoria'] ) ? sanitize_text_field( $body['categoria'] ) : '';
	$unidad  = isset( $body['unidad_tipo'] ) ? sanitize_key( $body['unidad_tipo'] ) : 'unidad';
	$unidadV = isset( $body['unidad_valor_base'] ) && $body['unidad_valor_base'] !== '' ? (float) $body['unidad_valor_base'] : null;

	// v1.11 — la foto que el negocio ya tenía puesta en su producto, para que quien revisa la
	// vea y decida si aprueba tal cual o la cambia por una mejor. Se valida formato/tamaño pero
	// NO se sube a Media todavía — mientras está pendiente es solo una vista previa; subir de
	// verdad archivos de gente sin revisar sería aceptar contenido a ciegas.
	$foto_base64    = isset( $body['foto_base64'] ) ? (string) $body['foto_base64'] : '';
	$foto_pendiente = null;
	if ( $foto_base64 && preg_match( '#^data:image/(png|jpe?g|webp);base64,(.+)$#', $foto_base64, $mf ) ) {
		$bin = base64_decode( $mf[2] );
		if ( $bin && strlen( $bin ) <= 4 * 1024 * 1024 ) {
			$foto_pendiente = $foto_base64;
		}
	}

	if ( strlen( trim( $nombre ) ) < 2 ) {
		return new WP_REST_Response( array( 'message' => 'Falta el nombre del producto.' ), 400 );
	}

	$nombre_normalizado = punto_normalizar_texto( $nombre );

	// evita sugerencias duplicadas: si ya hay una (aprobada o pendiente) con el mismo nombre
	// normalizado, no se mete otra fila — de nada sirve una cola con "harina pan" cien veces.
	// El catálogo maestro crece SIN duplicados, esa es la regla.
	$existente = $wpdb->get_row( $wpdb->prepare(
		"SELECT id, foto_url, foto_pendiente FROM {$t['catalogo_maestro']} WHERE nombre_normalizado = %s LIMIT 1",
		$nombre_normalizado
	) );
	if ( $existente ) {
		// v1.14 — pero si el que ya está NO tiene foto (típico de los importados por CSV) y este
		// negocio sí trae una, se le engancha para revisión en vez de botarla. Antes la foto se
		// perdía en silencio y el producto se quedaba sin imagen para siempre.
		if ( $foto_pendiente && ! $existente->foto_url && ! $existente->foto_pendiente ) {
			$wpdb->update(
				$t['catalogo_maestro'],
				array( 'foto_pendiente' => $foto_pendiente ),
				array( 'id' => (int) $existente->id )
			);
			return new WP_REST_Response( array( 'ok' => true, 'ya_existia' => true, 'foto_agregada' => true ), 200 );
		}
		return new WP_REST_Response( array( 'ok' => true, 'ya_existia' => true ), 200 );
	}

	$wpdb->insert(
		$t['catalogo_maestro'],
		array(
			'nombre'             => $nombre,
			'nombre_normalizado' => $nombre_normalizado,
			'marca'              => $marca,
			'sku'                => $sku,
			'categoria'          => $cat,
			'unidad_tipo'        => $unidad,
			'unidad_valor_base'  => $unidadV,
			'foto_pendiente'     => $foto_pendiente,
			'estado'             => 'pendiente',
			'wp_user_id'         => $cuenta ? $cuenta->ID : null,
			'creado_en'          => current_time( 'mysql' ),
		)
	);

	return new WP_REST_Response( array( 'ok' => true ), 201 );
}

/* ==========================================================================
 * 3g. CATÁLOGO DIGITAL (v1.13)
 * ==========================================================================
 * Vitrina pública en tudominio.com/p/{slug} — gratis para TODA cuenta, tenga o
 * no negocio activado (código pagado). Por eso todo esto se ata a wp_user_id
 * (la CUENTA), nunca a negocio_id: una cuenta sin código activado no tiene fila
 * en wp_punto_negocios, y el catálogo digital tiene que funcionar igual para
 * ella — es marketing/alcance para todos, no una ventaja de quien paga.
 *
 * El "nombre de usuario" de la URL (slug) es un campo SEPARADO del login (que
 * sigue siendo el correo) — se guarda en user_meta, único entre TODAS las
 * cuentas. Visibilidad general y "mostrar precios" también son user_meta:
 * configuración de la cuenta, no de cada producto.
 * ========================================================================== */

function punto_slug_valido( $slug ) {
	return (bool) preg_match( '/^[a-z0-9]([a-z0-9-]{1,38}[a-z0-9])?$/', (string) $slug );
}

/** wp_user_id dueño de un slug, o 0 si nadie lo tiene tomado. */
function punto_cuenta_id_por_slug( $slug ) {
	global $wpdb;
	if ( ! $slug ) {
		return 0;
	}
	$user_id = $wpdb->get_var( $wpdb->prepare(
		"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'punto_catalogo_slug' AND meta_value = %s LIMIT 1",
		$slug
	) );
	return $user_id ? (int) $user_id : 0;
}

// /p/{slug} — registrado en 'init' (se ejecuta en cada carga) y flusheado al instalar/
// actualizar la base (ver flush_rewrite_rules() en punto_instalar_tablas). Sin esto, WordPress
// respondería 404 aunque la ruta REST funcione perfecto: son dos sistemas de URL distintos.
add_action( 'init', function () {
	add_rewrite_tag( '%punto_slug%', '([^&/]+)' );
	add_rewrite_rule( '^p/([^/]+)/?$', 'index.php?punto_slug=$matches[1]', 'top' );
} );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'punto_slug';
	return $vars;
} );

// Sirve la vitrina directo desde el plugin (no desde el tema) A PROPÓSITO: esta página no
// existe sin el plugin — es 100% datos del plugin — así que no tiene sentido depender de que
// el tema activo traiga la plantilla. El resto de la app (el POS) sí es "solo apariencia" del
// tema; esta página pública es la excepción, porque es puro dato de servidor, no UI del POS.
add_filter( 'template_include', function ( $template ) {
	$slug = get_query_var( 'punto_slug' );
	if ( $slug ) {
		return PUNTO_BACKEND_DIR . 'templates/catalogo-publico.php';
	}
	return $template;
} );

function punto_api_catalogo_publico_guardar( $request ) {
	global $wpdb;
	$t = punto_tablas();

	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );
	if ( ! $cuenta ) {
		return new WP_REST_Response( array( 'message' => 'Token de cuenta inválido. Inicia sesión de nuevo.' ), 401 );
	}

	$body      = $request->get_json_params();
	$slug      = isset( $body['slug'] ) ? strtolower( sanitize_title( (string) $body['slug'] ) ) : '';
	$visible   = ! empty( $body['visible'] );
	$mostrar   = ! empty( $body['mostrar_precios'] );
	$logo_url  = isset( $body['logo_url'] ) ? esc_url_raw( (string) $body['logo_url'] ) : '';
	$productos = isset( $body['productos'] ) && is_array( $body['productos'] ) ? $body['productos'] : array();

	if ( $visible ) {
		if ( ! $slug || ! punto_slug_valido( $slug ) ) {
			return new WP_REST_Response( array( 'message' => 'El nombre de tu catálogo debe tener entre 3 y 40 letras minúsculas, números o guiones, sin empezar ni terminar en guion.' ), 400 );
		}
		$dueno_actual = punto_cuenta_id_por_slug( $slug );
		if ( $dueno_actual && (int) $dueno_actual !== (int) $cuenta->ID ) {
			return new WP_REST_Response( array( 'message' => 'Ese nombre ya lo está usando otro negocio. Prueba con otro.' ), 409 );
		}
	}

	update_user_meta( $cuenta->ID, 'punto_catalogo_slug', $slug );
	update_user_meta( $cuenta->ID, 'punto_catalogo_visible', $visible ? '1' : '0' );
	update_user_meta( $cuenta->ID, 'punto_catalogo_mostrar_precios', $mostrar ? '1' : '0' );
	update_user_meta( $cuenta->ID, 'punto_catalogo_logo_url', $logo_url );

	// reemplazo completo: se borra lo de antes y se inserta el catálogo tal como está ahora. El
	// catálogo público es chico (una bodega real, unos cientos de productos como mucho), así
	// que el teléfono nunca necesita mandar un diff — manda lo visible ahora mismo y listo.
	$wpdb->delete( $t['catalogo_publico'], array( 'wp_user_id' => $cuenta->ID ), array( '%d' ) );

	$ahora = current_time( 'mysql' );
	$orden = 0;
	foreach ( $productos as $p ) {
		$producto_id = isset( $p['producto_id'] ) ? sanitize_text_field( (string) $p['producto_id'] ) : '';
		$nombre      = isset( $p['nombre'] ) ? sanitize_text_field( (string) $p['nombre'] ) : '';
		if ( '' === $producto_id || '' === $nombre ) {
			continue; // sin id o sin nombre no sirve de nada — se salta en silencio, no rompe el resto
		}
		$precio    = ( isset( $p['precio_usd'] ) && is_numeric( $p['precio_usd'] ) ) ? round( (float) $p['precio_usd'], 2 ) : null;
		$foto      = isset( $p['foto_url'] ) ? esc_url_raw( (string) $p['foto_url'] ) : '';
		$categoria = isset( $p['categoria'] ) ? sanitize_text_field( (string) $p['categoria'] ) : '';
		$wpdb->insert(
			$t['catalogo_publico'],
			array(
				'wp_user_id'     => $cuenta->ID,
				'producto_id'    => $producto_id,
				'nombre'         => $nombre,
				'precio_usd'     => $precio,
				'foto_url'       => $foto,
				'categoria'      => $categoria,
				'orden'          => $orden,
				'actualizado_en' => $ahora,
			),
			array( '%d', '%s', '%s', '%f', '%s', '%s', '%d', '%s' )
		);
		++$orden;
	}

	return new WP_REST_Response(
		array(
			'ok'                  => true,
			'url'                 => $slug ? home_url( '/p/' . $slug ) : '',
			'productos_guardados' => $orden,
		),
		200
	);
}

/**
 * Sube UNA foto para el catálogo digital. A propósito NO exige negocio activo (el catálogo es
 * gratis para todos) — el único límite es de infraestructura: 500 fotos propias por cuenta,
 * contadas por el meta que deja punto_guardar_foto_base64() en cada adjunto. Ninguna bodega
 * real se acerca a eso; existe para que nadie use esto como hosting de fotos gratuito.
 */
function punto_api_catalogo_publico_foto( $request ) {
	global $wpdb;

	$cuenta = punto_cuenta_por_token( punto_token_de_request( $request ) );
	if ( ! $cuenta ) {
		return new WP_REST_Response( array( 'message' => 'Token de cuenta inválido. Inicia sesión de nuevo.' ), 401 );
	}

	$actuales = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_punto_catalogo_cuenta_id' AND meta_value = %d",
		$cuenta->ID
	) );
	if ( $actuales >= 500 ) {
		return new WP_REST_Response( array( 'message' => 'Llegaste a las 500 fotos propias en tu catálogo público. Si el producto ya está en el catálogo maestro, usa esa foto en vez de subir una nueva.' ), 403 );
	}

	$body     = $request->get_json_params();
	$data_url = isset( $body['foto_base64'] ) ? (string) $body['foto_base64'] : '';

	$url = punto_guardar_foto_base64(
		$data_url,
		'Catálogo FrixPOS — cuenta ' . $cuenta->ID,
		$cuenta->ID,
		array( '_punto_catalogo_cuenta_id' => $cuenta->ID )
	);
	if ( ! $url ) {
		return new WP_REST_Response( array( 'message' => 'No se pudo guardar la imagen (formato no soportado, o pesa más de 4MB).' ), 400 );
	}

	return new WP_REST_Response( array( 'ok' => true, 'url' => $url ), 201 );
}

/* ==========================================================================
 * 3h. SOLICITUDES (v1.8) — leads del sitio de marketing
 * ==========================================================================
 * El formulario público (shortcode [punto_formulario], ver más abajo) manda nombre, teléfono,
 * correo y motivo (comprar/soporte). Sin autenticación — quien llena esto casi nunca tiene
 * cuenta todavía, es justo lo que está pidiendo. Anti-spam liviano con honeypot: un campo
 * oculto que un humano nunca llena y un bot casi siempre sí — si viene lleno, se guarda igual
 * como éxito de cara al bot (para no delatar el truco) pero no se inserta en la base.
 * ========================================================================== */
function punto_api_solicitud_crear( $request ) {
	global $wpdb;
	$t = punto_tablas();

	$body   = $request->get_json_params();
	$panal  = isset( $body['sitio_web'] ) ? trim( (string) $body['sitio_web'] ) : ''; // honeypot
	if ( '' !== $panal ) {
		return new WP_REST_Response( array( 'ok' => true ), 201 ); // bot: se le dice que sí, no se guarda
	}

	$nombre   = isset( $body['nombre'] ) ? sanitize_text_field( $body['nombre'] ) : '';
	$telefono = isset( $body['telefono'] ) ? sanitize_text_field( $body['telefono'] ) : '';
	$correo   = isset( $body['correo'] ) ? sanitize_email( $body['correo'] ) : '';
	$motivo   = ( isset( $body['motivo'] ) && 'soporte' === $body['motivo'] ) ? 'soporte' : 'comprar';

	if ( strlen( $nombre ) < 2 ) {
		return new WP_REST_Response( array( 'message' => 'Escribe tu nombre.' ), 400 );
	}
	if ( '' === $telefono && '' === $correo ) {
		return new WP_REST_Response( array( 'message' => 'Déjanos tu teléfono o tu correo para poder responderte.' ), 400 );
	}

	$wpdb->insert(
		$t['solicitudes'],
		array(
			'nombre'    => $nombre,
			'telefono'  => $telefono,
			'correo'    => $correo,
			'motivo'    => $motivo,
			'atendida'  => 0,
			'creado_en' => current_time( 'mysql' ),
		),
		array( '%s', '%s', '%s', '%s', '%d', '%s' )
	);

	return new WP_REST_Response( array( 'ok' => true ), 201 );
}

function punto_admin_solicitudes() {
	global $wpdb;
	$t = punto_tablas();

	if ( isset( $_POST['punto_sol_toggle'] ) && check_admin_referer( 'punto_solicitudes' ) ) {
		$id = (int) $_POST['sol_id'];
		$wpdb->query( $wpdb->prepare( "UPDATE {$t['solicitudes']} SET atendida = 1 - atendida WHERE id = %d", $id ) );
	}
	if ( isset( $_POST['punto_sol_eliminar'] ) && check_admin_referer( 'punto_solicitudes' ) ) {
		$wpdb->delete( $t['solicitudes'], array( 'id' => (int) $_POST['sol_id'] ) );
		echo '<div class="notice notice-success is-dismissible"><p>Eliminada.</p></div>';
	}

	$ver  = ( isset( $_GET['sol_ver'] ) && 'atendidas' === $_GET['sol_ver'] ) ? 'atendidas' : 'pendientes';
	$where = 'pendientes' === $ver ? 'WHERE atendida = 0' : 'WHERE atendida = 1';
	$filas = $wpdb->get_results( "SELECT * FROM {$t['solicitudes']} $where ORDER BY creado_en DESC LIMIT 300" );
	$pendientes_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['solicitudes']} WHERE atendida = 0" );
	?>
	<div class="wrap">
		<h1>Solicitudes</h1>
		<p class="description">Lo que llega del formulario público del sitio (comprar código o soporte). No están relacionadas con ninguna cuenta — quien escribe puede no tener una todavía.</p>

		<h2 class="nav-tab-wrapper">
			<a href="?page=punto-solicitudes&sol_ver=pendientes" class="nav-tab <?php echo 'pendientes' === $ver ? 'nav-tab-active' : ''; ?>">Pendientes<?php echo $pendientes_count ? ' (' . $pendientes_count . ')' : ''; ?></a>
			<a href="?page=punto-solicitudes&sol_ver=atendidas" class="nav-tab <?php echo 'atendidas' === $ver ? 'nav-tab-active' : ''; ?>">Atendidas</a>
		</h2>

		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th>Nombre</th><th>Motivo</th><th>Teléfono</th><th>Correo</th><th style="width:150px">Fecha</th><th style="width:200px"></th></tr></thead>
			<tbody>
			<?php if ( ! $filas ) : ?>
				<tr><td colspan="6">Nada acá todavía.</td></tr>
			<?php endif; ?>
			<?php foreach ( (array) $filas as $s ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $s->nombre ); ?></strong></td>
					<td><?php echo 'soporte' === $s->motivo ? 'Soporte' : 'Quiere su código'; ?></td>
					<td><?php if ( $s->telefono ) : ?><a href="https://wa.me/<?php echo esc_attr( preg_replace( '/\D+/', '', $s->telefono ) ); ?>" target="_blank"><?php echo esc_html( $s->telefono ); ?></a><?php endif; ?></td>
					<td><?php if ( $s->correo ) : ?><a href="mailto:<?php echo esc_attr( $s->correo ); ?>"><?php echo esc_html( $s->correo ); ?></a><?php endif; ?></td>
					<td><?php echo esc_html( $s->creado_en ); ?></td>
					<td>
						<form method="post" style="display:inline">
							<?php wp_nonce_field( 'punto_solicitudes' ); ?>
							<input type="hidden" name="sol_id" value="<?php echo (int) $s->id; ?>">
							<button class="button button-small" name="punto_sol_toggle" value="1"><?php echo $s->atendida ? 'Marcar pendiente' : 'Marcar atendida'; ?></button>
							<button class="button button-small" name="punto_sol_eliminar" value="1" onclick="return confirm('¿Eliminar esta solicitud?')">Eliminar</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * v1.8 — [punto_formulario motivo="comprar|soporte"]. Autocontenido: CSS con prefijo propio
 * (.punto-form-*) para que se vea igual lo pegues donde lo pegues, sin heredar ni pelear con
 * el estilo de la página. JS vanilla, sin dependencias — el sitio de marketing no carga jQuery
 * ni nada por el estilo.
 */
function punto_shortcode_formulario( $atts ) {
	$atts   = shortcode_atts( array( 'motivo' => 'comprar' ), $atts, 'punto_formulario' );
	$motivo = ( 'soporte' === $atts['motivo'] ) ? 'soporte' : 'comprar';
	$titulo = 'soporte' === $motivo ? '¿En qué te ayudamos?' : 'Pide tu código';
	$boton  = 'soporte' === $motivo ? 'Enviar' : 'Quiero mi código';
	$uid    = 'pf' . wp_rand( 1000, 999999 ); // varias instancias del shortcode en la misma página no chocan

	ob_start();
	?>
	<div class="punto-form-wrap" id="<?php echo esc_attr( $uid ); ?>">
		<style>
			#<?php echo esc_attr( $uid ); ?> { --pf-bg:#15131c; --pf-card:#1c1a26; --pf-border:#302c3d; --pf-text:#f2f0f7; --pf-faint:#948fa3; --pf-accent:#ff6b3d; --pf-ok:#3ddc84; --pf-err:#ff6b6b; }
			#<?php echo esc_attr( $uid ); ?> * { box-sizing:border-box; }
			#<?php echo esc_attr( $uid ); ?> .pf-card { background:var(--pf-card); border:1px solid var(--pf-border); border-radius:18px; padding:28px 26px; max-width:420px; margin:0 auto; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; }
			#<?php echo esc_attr( $uid ); ?> .pf-h { color:var(--pf-text); font-size:1.25rem; font-weight:700; margin:0 0 6px; }
			#<?php echo esc_attr( $uid ); ?> .pf-sub { color:var(--pf-faint); font-size:.88rem; margin:0 0 18px; line-height:1.5; }
			#<?php echo esc_attr( $uid ); ?> label { display:block; color:var(--pf-faint); font-size:.78rem; font-weight:600; margin:14px 0 6px; }
			#<?php echo esc_attr( $uid ); ?> input { width:100%; background:var(--pf-bg); border:1px solid var(--pf-border); border-radius:10px; padding:12px 14px; color:var(--pf-text); font-size:.92rem; font-family:inherit; }
			#<?php echo esc_attr( $uid ); ?> input:focus { outline:2px solid var(--pf-accent); outline-offset:1px; }
			#<?php echo esc_attr( $uid ); ?> .pf-hp { position:absolute; left:-9999px; width:1px; height:1px; overflow:hidden; }
			#<?php echo esc_attr( $uid ); ?> button { width:100%; margin-top:20px; background:var(--pf-accent); color:#1b1710; border:none; border-radius:999px; padding:14px; font-size:.95rem; font-weight:700; font-family:inherit; cursor:pointer; }
			#<?php echo esc_attr( $uid ); ?> button:disabled { opacity:.6; cursor:default; }
			#<?php echo esc_attr( $uid ); ?> .pf-msg { margin-top:14px; font-size:.85rem; text-align:center; min-height:1.2em; }
			#<?php echo esc_attr( $uid ); ?> .pf-msg.ok { color:var(--pf-ok); }
			#<?php echo esc_attr( $uid ); ?> .pf-msg.err { color:var(--pf-err); }
		</style>
		<div class="pf-card">
			<div class="pf-h"><?php echo esc_html( $titulo ); ?></div>
			<div class="pf-sub"><?php echo 'soporte' === $motivo
				? 'Cuéntanos qué pasó y te escribimos.'
				: 'Déjanos tus datos y te mandamos tu código de activación.'; ?></div>
			<form data-motivo="<?php echo esc_attr( $motivo ); ?>">
				<label>Nombre</label>
				<input type="text" name="nombre" required>
				<label>Teléfono (WhatsApp)</label>
				<input type="tel" name="telefono" placeholder="0414-1234567">
				<label>Correo (opcional)</label>
				<input type="email" name="correo">
				<input class="pf-hp" type="text" name="sitio_web" tabindex="-1" autocomplete="off">
				<button type="submit"><?php echo esc_html( $boton ); ?></button>
				<div class="pf-msg"></div>
			</form>
		</div>
	</div>
	<script>
	(function(){
		var wrap=document.getElementById(<?php echo wp_json_encode( $uid ); ?>);
		var form=wrap.querySelector('form'), btn=form.querySelector('button'), msg=form.querySelector('.pf-msg');
		form.addEventListener('submit', function(e){
			e.preventDefault();
			var datos={
				motivo:form.dataset.motivo,
				nombre:form.nombre.value.trim(),
				telefono:form.telefono.value.trim(),
				correo:form.correo.value.trim(),
				sitio_web:form.sitio_web.value
			};
			btn.disabled=true; btn.textContent='Enviando…';
			fetch('<?php echo esc_url_raw( rest_url( 'punto/v1/solicitud' ) ); ?>',{
				method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(datos)
			}).then(function(r){ return r.json().then(function(d){ return {ok:r.ok, data:d}; }); })
			.then(function(res){
				btn.disabled=false;
				if(res.ok && res.data && res.data.ok){
					btn.textContent=<?php echo wp_json_encode( $boton ); ?>;
					msg.className='pf-msg ok';
					msg.textContent='Listo, ya lo recibimos. Te escribimos pronto.';
					form.reset();
				} else {
					btn.textContent=<?php echo wp_json_encode( $boton ); ?>;
					msg.className='pf-msg err';
					msg.textContent=(res.data&&res.data.message)||'No se pudo enviar. Intenta de nuevo.';
				}
			}).catch(function(){
				btn.disabled=false; btn.textContent=<?php echo wp_json_encode( $boton ); ?>;
				msg.className='pf-msg err';
				msg.textContent='No se pudo conectar. Revisa tu internet e intenta de nuevo.';
			});
		});
	})();
	</script>
	<?php
	return ob_get_clean();
}
add_shortcode( 'punto_formulario', 'punto_shortcode_formulario' );

/* ==========================================================================
 * 4. PANEL EN WP-ADMIN
 * ========================================================================== */

add_action( 'admin_menu', function () {
	add_menu_page( 'FrixPOS', 'FrixPOS', 'manage_options', 'punto', 'punto_admin_negocios', 'dashicons-store', 30 );
	add_submenu_page( 'punto', 'Negocios', 'Negocios', 'manage_options', 'punto', 'punto_admin_negocios' );
	add_submenu_page( 'punto', 'Códigos de activación', 'Códigos', 'manage_options', 'punto-codigos', 'punto_admin_codigos' );
	add_submenu_page( 'punto', 'Ventas sincronizadas', 'Ventas', 'manage_options', 'punto-ventas', 'punto_admin_ventas' );
	add_submenu_page( 'punto', 'Catálogo maestro', 'Catálogo maestro', 'manage_options', 'punto-catalogo', 'punto_admin_catalogo' );
	add_submenu_page( 'punto', 'Respaldos de negocios', 'Respaldos', 'manage_options', 'punto-respaldos', 'punto_admin_respaldos' );
	add_submenu_page( 'punto', 'Limpiar datos de prueba', 'Limpiar datos', 'manage_options', 'punto-limpiar', 'punto_admin_limpiar' );
	add_submenu_page( 'punto', 'Solicitudes', 'Solicitudes', 'manage_options', 'punto-solicitudes', 'punto_admin_solicitudes' );
	add_submenu_page( 'punto', 'Diagnóstico de correo', 'Correo', 'manage_options', 'punto-correo', 'punto_admin_correo' );
	add_submenu_page( 'punto', 'Sincronización en tiempo real', 'Tiempo real', 'manage_options', 'punto-tiempo-real', 'punto_admin_tiempo_real' );
} );

/**
 * Diagnóstico de correo. El síntoma que lo motivó: "pedí recuperar contraseña y el correo no
 * llegó a Gmail" — sin esto no había forma de saber si WordPress ni siquiera intentó enviarlo,
 * si lo intentó y falló, o si salió bien y Gmail lo filtró. Son tres problemas distintos.
 */
function punto_admin_correo() {
	$aviso = '';

	if ( isset( $_POST['punto_test_mail'] ) && check_admin_referer( 'punto_correo' ) ) {
		$destino = sanitize_email( wp_unslash( $_POST['punto_test_mail'] ) );
		if ( ! is_email( $destino ) ) {
			$aviso = '<div class="notice notice-error"><p>Escribe un correo válido.</p></div>';
		} else {
			$error_mail = '';
			$capturar   = function ( $wp_error ) use ( &$error_mail ) {
				$error_mail = $wp_error->get_error_message();
			};
			add_action( 'wp_mail_failed', $capturar );
			$enviado = wp_mail( $destino, 'Prueba de correo de FrixPOS', 'Si estás leyendo esto, tu WordPress sí puede mandar correos.' );
			remove_action( 'wp_mail_failed', $capturar );

			$aviso = $enviado
				? '<div class="notice notice-success"><p>WordPress aceptó el envío a <code>' . esc_html( $destino ) . '</code>. Si no llega en unos minutos, revisa spam: el problema es de entrega (falta SMTP), no del plugin.</p></div>'
				: '<div class="notice notice-error"><p>WordPress <b>no pudo enviar</b> el correo. ' . esc_html( $error_mail ) . '</p></div>';
		}
	}

	$ultimo = get_option( 'punto_ultimo_reset' );
	?>
	<div class="wrap">
		<h1>Diagnóstico de correo</h1>
		<?php echo $aviso; // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<h2>Último intento de recuperar contraseña</h2>
		<?php if ( ! $ultimo ) : ?>
			<p>Todavía nadie ha pedido recuperar su contraseña desde la app.</p>
		<?php else : ?>
			<table class="widefat striped" style="max-width:640px">
				<tr><td><b>Fecha</b></td><td><?php echo esc_html( $ultimo['fecha'] ); ?></td></tr>
				<tr><td><b>Resultado</b></td>
					<td><?php echo $ultimo['ok']
						? '<span style="color:#127a3d;font-weight:600">WordPress lo entregó al servidor de correo</span>'
						: '<span style="color:#b32d2e;font-weight:600">Falló</span>'; ?></td></tr>
				<tr><td><b>Detalle</b></td><td><code><?php echo esc_html( $ultimo['detalle'] ); ?></code></td></tr>
			</table>
			<?php if ( $ultimo['ok'] ) : ?>
				<p>Si dice que lo entregó y aun así no llegó a Gmail, el problema <b>no está en el plugin</b>: es entrega de correo.
				Hostinger manda desde una dirección genérica del dominio, sin SPF ni DKIM configurados, y Gmail lo descarta en silencio.
				La solución es instalar un plugin de SMTP (por ejemplo WP Mail SMTP) y conectarlo a un buzón real.</p>
			<?php endif; ?>
		<?php endif; ?>

		<h2>Probar el envío ahora</h2>
		<form method="post">
			<?php wp_nonce_field( 'punto_correo' ); ?>
			<input type="email" name="punto_test_mail" class="regular-text" placeholder="tucorreo@gmail.com" required>
			<button class="button button-primary">Enviar prueba</button>
		</form>
	</div>
	<?php
}

/**
 * Sincronización en tiempo real (Ably) — v1.25. La API Key completa se guarda SOLO aquí
 * (wp_options), nunca en el tema hijo ni en el cliente: page-pos.php solo recibe, vía
 * /ably-token, un token de corta vida acotado al canal de un único negocio (ver
 * punto_api_ably_token). Un campo vacío al guardar NO borra la key ya configurada —
 * es la forma de dejar este formulario sin valor visible después de guardarla una vez.
 */
function punto_admin_tiempo_real() {
	$aviso = '';

	if ( isset( $_POST['punto_ably_guardar'] ) && check_admin_referer( 'punto_ably' ) ) {
		$valor = trim( wp_unslash( $_POST['punto_ably_api_key'] ) );
		if ( '' === $valor ) {
			// campo vacío = no tocar lo que ya había guardado
		} elseif ( false === strpos( $valor, ':' ) ) {
			$aviso = '<div class="notice notice-error"><p>Esa no parece una API Key de Ably válida — debe tener el formato <code>appId.keyId:keySecret</code>, tal como la muestra el panel de Ably.</p></div>';
		} else {
			update_option( 'punto_ably_api_key', $valor );
			$aviso = '<div class="notice notice-success"><p>Guardado.</p></div>';
		}
	}

	$actual = trim( (string) get_option( 'punto_ably_api_key' ) );
	$oculto = $actual ? ( substr( $actual, 0, 6 ) . '••••••••' . substr( $actual, -4 ) ) : '';
	?>
	<div class="wrap">
		<h1>Sincronización en tiempo real (Ably)</h1>
		<?php echo $aviso; // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<p>Permite que el POS de una cuenta <b>premium</b> (con negocio activo) se mantenga
		sincronizado al instante entre varios dispositivos — PC, tablet, teléfono — en vez de
		esperar a la próxima sincronización manual. Solo aplica a cuentas con código activado;
		sin código, el POS sigue funcionando exactamente igual que ahora.</p>

		<p>La API Key completa de Ably se guarda aquí, en el servidor, y <b>nunca</b> se manda
		al teléfono ni al navegador: el plugin solo entrega tokens temporales (1 hora) con
		permiso sobre el canal de un único negocio a la vez — se firma en este mismo servidor,
		sin llamar a Ably por internet (así funciona aunque tu hosting bloquee conexiones
		salientes, algo común en hosting compartido). Consíguela en
		<a href="https://ably.com/accounts" target="_blank" rel="noopener">tu cuenta de Ably</a>
		→ tu app → API Keys (la que tenga los permisos Publish/Subscribe/Presence).</p>

		<?php if ( $actual ) : ?>
			<p>Configurada actualmente: <code><?php echo esc_html( $oculto ); ?></code></p>
		<?php else : ?>
			<p><em>Todavía no hay ninguna key guardada — la sincronización en tiempo real está
			apagada hasta que pegues una aquí.</em></p>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'punto_ably' ); ?>
			<input type="text" name="punto_ably_api_key" class="regular-text" style="font-family:monospace" placeholder="appId.keyId:keySecret" autocomplete="off">
			<button class="button button-primary" name="punto_ably_guardar" value="1">Guardar</button>
		</form>
	</div>
	<?php
}

function punto_admin_negocios() {
	global $wpdb;
	$t = punto_tablas();

	if ( isset( $_POST['punto_toggle_negocio'] ) && check_admin_referer( 'punto_negocios' ) ) {
		$id = (int) $_POST['punto_toggle_negocio'];
		$wpdb->query( $wpdb->prepare( "UPDATE {$t['negocios']} SET activo = 1 - activo WHERE id = %d", $id ) );
		echo '<div class="notice notice-success is-dismissible"><p>Negocio actualizado.</p></div>';
	}

	$filas = $wpdb->get_results(
		"SELECT n.*, (SELECT COUNT(*) FROM {$t['ventas']} v WHERE v.negocio_id = n.id) AS n_ventas,
		        (SELECT COALESCE(SUM(v.monto_bs),0) FROM {$t['ventas']} v WHERE v.negocio_id = n.id) AS total_bs
		 FROM {$t['negocios']} n ORDER BY n.id DESC"
	);
	?>
	<div class="wrap">
		<h1>Negocios</h1>
		<p class="description">Cada fila es un negocio que activó FrixPOS con un código. El token no se muestra porque no se guarda en claro — si un negocio lo pierde, se desactiva aquí y se le da un código nuevo.</p>
		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th>ID</th><th>Nombre</th><th>Cuenta</th><th>Alta</th><th>Último sync</th><th>Ventas</th><th>Total Bs</th><th>Estado</th><th></th></tr></thead>
			<tbody>
			<?php if ( ! $filas ) : ?>
				<tr><td colspan="9">Todavía no hay negocios activados.</td></tr>
			<?php endif; ?>
			<?php foreach ( (array) $filas as $n ) : ?>
				<?php $cuenta = $n->wp_user_id ? get_userdata( $n->wp_user_id ) : false; ?>
				<tr>
					<td><?php echo (int) $n->id; ?></td>
					<td><strong><?php echo esc_html( $n->nombre ); ?></strong></td>
					<td><?php echo $cuenta ? esc_html( $cuenta->user_email ) : '<em>sin cuenta</em>'; ?></td>
					<td><?php echo esc_html( $n->fecha_alta ); ?></td>
					<td><?php echo $n->ultimo_sync ? esc_html( $n->ultimo_sync ) : '<em>nunca</em>'; ?></td>
					<td><?php echo (int) $n->n_ventas; ?></td>
					<td><?php echo esc_html( number_format_i18n( (float) $n->total_bs, 2 ) ); ?></td>
					<td><?php echo $n->activo ? '<span style="color:#2b8a3e">Activo</span>' : '<span style="color:#c92a2a">Desactivado</span>'; ?></td>
					<td>
						<form method="post" style="margin:0">
							<?php wp_nonce_field( 'punto_negocios' ); ?>
							<input type="hidden" name="punto_toggle_negocio" value="<?php echo (int) $n->id; ?>">
							<button class="button button-small"><?php echo $n->activo ? 'Desactivar' : 'Reactivar'; ?></button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

function punto_admin_codigos() {
	global $wpdb;
	$t = punto_tablas();

	if ( isset( $_POST['punto_generar'] ) && check_admin_referer( 'punto_codigos' ) ) {
		$cuantos = max( 1, min( 50, (int) $_POST['punto_cuantos'] ) );
		$nota    = isset( $_POST['punto_nota'] ) ? sanitize_text_field( $_POST['punto_nota'] ) : '';
		$nuevos  = array();
		for ( $i = 0; $i < $cuantos; $i++ ) {
			$codigo = punto_generar_codigo();
			$hecho  = $wpdb->insert( $t['codigos'], array(
				'codigo'         => $codigo,
				'usado'          => 0,
				'nota'           => $nota,
				'fecha_generado' => current_time( 'mysql' ),
			), array( '%s', '%d', '%s', '%s' ) );
			if ( $hecho ) {
				$nuevos[] = $codigo;
			}
		}
		if ( $nuevos ) {
			echo '<div class="notice notice-success"><p><strong>' . count( $nuevos ) . ' código(s) generado(s).</strong> Cópialos ahora para mandarlos:</p><p><code style="font-size:14px">' . esc_html( implode( '   ', $nuevos ) ) . '</code></p></div>';
		}
	}

	$filas = $wpdb->get_results(
		"SELECT c.*, n.nombre AS negocio FROM {$t['codigos']} c
		 LEFT JOIN {$t['negocios']} n ON n.id = c.negocio_id
		 ORDER BY c.id DESC LIMIT 200"
	);
	?>
	<div class="wrap">
		<h1>Códigos de activación</h1>
		<p class="description">Flujo manual, a propósito: el negocio paga por Zelle o transferencia, tú generas un código aquí y se lo mandas. No hay pasarela de pago ni WooCommerce de por medio.</p>

		<form method="post" style="background:#fff;border:1px solid #ccd0d4;padding:16px;margin:16px 0;max-width:520px">
			<?php wp_nonce_field( 'punto_codigos' ); ?>
			<h2 style="margin-top:0">Generar códigos</h2>
			<p><label>¿Cuántos? <input type="number" name="punto_cuantos" value="1" min="1" max="50" style="width:80px"></label></p>
			<p><label>Nota (opcional, para acordarte de quién es)<br><input type="text" name="punto_nota" class="regular-text" placeholder="Ej: Bodega Los Pinos — pagó 12/03"></label></p>
			<p><button class="button button-primary" name="punto_generar" value="1">Generar</button></p>
		</form>

		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th>Código</th><th>Estado</th><th>Negocio</th><th>Nota</th><th>Generado</th><th>Usado</th></tr></thead>
			<tbody>
			<?php if ( ! $filas ) : ?>
				<tr><td colspan="6">Todavía no has generado códigos.</td></tr>
			<?php endif; ?>
			<?php foreach ( (array) $filas as $c ) : ?>
				<tr>
					<td><code><?php echo esc_html( $c->codigo ); ?></code></td>
					<td><?php echo $c->usado ? '<span style="color:#868e96">Usado</span>' : '<strong style="color:#2b8a3e">Disponible</strong>'; ?></td>
					<td><?php echo $c->negocio ? esc_html( $c->negocio ) : '—'; ?></td>
					<td><?php echo esc_html( $c->nota ); ?></td>
					<td><?php echo esc_html( $c->fecha_generado ); ?></td>
					<td><?php echo $c->fecha_usado ? esc_html( $c->fecha_usado ) : '—'; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

function punto_admin_ventas() {
	global $wpdb;
	$t = punto_tablas();

	$negocio_id = isset( $_GET['negocio_id'] ) ? (int) $_GET['negocio_id'] : 0;
	$pagina     = max( 1, isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1 );
	$por_pagina = 50;
	$offset     = ( $pagina - 1 ) * $por_pagina;

	$where  = $negocio_id ? $wpdb->prepare( 'WHERE v.negocio_id = %d', $negocio_id ) : '';
	$total  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['ventas']} v $where" );
	$filas  = $wpdb->get_results( $wpdb->prepare(
		"SELECT v.*, n.nombre AS negocio FROM {$t['ventas']} v
		 LEFT JOIN {$t['negocios']} n ON n.id = v.negocio_id
		 $where ORDER BY v.fecha_venta DESC, v.id DESC LIMIT %d OFFSET %d",
		$por_pagina, $offset
	) );
	$negocios = $wpdb->get_results( "SELECT id, nombre FROM {$t['negocios']} ORDER BY nombre" );
	?>
	<div class="wrap">
		<h1>Ventas sincronizadas</h1>
		<p class="description">Cada fila es una venta que llegó desde el teléfono de un negocio. El payload es la orden completa tal cual la generó el POS — todavía no se revalida contra los precios del servidor (pendiente conocido, ver el .md).</p>

		<form method="get" style="margin:16px 0">
			<input type="hidden" name="page" value="punto-ventas">
			<select name="negocio_id">
				<option value="0">Todos los negocios</option>
				<?php foreach ( (array) $negocios as $n ) : ?>
					<option value="<?php echo (int) $n->id; ?>" <?php selected( $negocio_id, $n->id ); ?>><?php echo esc_html( $n->nombre ); ?></option>
				<?php endforeach; ?>
			</select>
			<button class="button">Filtrar</button>
			<span style="margin-left:12px;color:#666"><?php echo (int) $total; ?> venta(s)</span>
		</form>

		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th style="width:150px">Fecha venta</th><th style="width:160px">Negocio</th><th style="width:110px">Monto Bs</th><th style="width:150px">Sincronizada</th><th>Detalle</th></tr></thead>
			<tbody>
			<?php if ( ! $filas ) : ?>
				<tr><td colspan="5">Ninguna venta sincronizada todavía.</td></tr>
			<?php endif; ?>
			<?php foreach ( (array) $filas as $v ) : ?>
				<tr>
					<td><?php echo esc_html( $v->fecha_venta ); ?></td>
					<td><?php echo esc_html( $v->negocio ); ?></td>
					<td><?php echo esc_html( number_format_i18n( (float) $v->monto_bs, 2 ) ); ?></td>
					<td><?php echo esc_html( $v->fecha_sync ); ?></td>
					<td>
						<details>
							<summary style="cursor:pointer">Ver orden completa</summary>
							<pre style="white-space:pre-wrap;word-break:break-all;max-height:300px;overflow:auto;background:#f6f7f7;padding:8px;font-size:11px"><?php
								echo esc_html( wp_json_encode( json_decode( $v->payload ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
							?></pre>
							<p style="color:#666;font-size:11px">uuid: <code><?php echo esc_html( $v->uuid ); ?></code></p>
						</details>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<?php
		$paginas = (int) ceil( $total / $por_pagina );
		if ( $paginas > 1 ) {
			echo '<div class="tablenav"><div class="tablenav-pages">';
			echo paginate_links( array(
				'base'    => add_query_arg( 'paged', '%#%' ),
				'format'  => '',
				'current' => $pagina,
				'total'   => $paginas,
			) );
			echo '</div></div>';
		}
		?>
	</div>
	<?php
}

/* ==========================================================================
 * 4b. ADMIN DEL CATÁLOGO MAESTRO (v1.7)
 * ========================================================================== */

/**
 * Sube el archivo del campo 'cm_foto' a la Biblioteca de Medios y devuelve su URL, o usa
 * 'cm_foto_url' si prefirieron pegar el enlace de una foto que ya existe. Vacío si no hay
 * ninguna de las dos — no es obligatorio tener foto para agregar/aprobar un producto.
 */
function punto_cm_procesar_foto() {
	if ( ! empty( $_FILES['cm_foto']['tmp_name'] ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		$attachment_id = media_handle_upload( 'cm_foto', 0 );
		if ( ! is_wp_error( $attachment_id ) ) {
			return wp_get_attachment_url( $attachment_id );
		}
	}
	if ( ! empty( $_POST['cm_foto_url'] ) ) {
		return esc_url_raw( wp_unslash( $_POST['cm_foto_url'] ) );
	}
	return '';
}

/**
 * CSV sin encabezado obligatorio: nombre, marca, sku, categoría, tipo de unidad, valor de
 * unidad, foto (URL, opcional — v1.24) — solo nombre es obligatorio. Se salta filas cuyo
 * nombre normalizado ya exista (aprobado o pendiente), para no duplicar si se corre el mismo
 * archivo dos veces. La columna de foto es nueva: si un producto YA existe y esta fila trae
 * una URL, se le actualiza la foto en vez de saltarse la fila entera — así una segunda
 * pasada del mismo CSV (por ejemplo, después de emparejar fotos) también sirve para
 * completar fotos de lo que ya se había importado.
 */
function punto_cm_importar_csv( $tmp_path ) {
	global $wpdb;
	$t         = punto_tablas();
	$agregados = 0;
	$con_foto  = 0;

	$fh = fopen( $tmp_path, 'r' );
	if ( ! $fh ) {
		return array( 'agregados' => 0, 'con_foto' => 0 );
	}

	while ( ( $fila = fgetcsv( $fh, 0, ',' ) ) !== false ) {
		$nombre = isset( $fila[0] ) ? sanitize_text_field( $fila[0] ) : '';
		if ( '' === $nombre || 'nombre' === strtolower( $nombre ) ) {
			continue; // fila vacía o el encabezado, si trae uno
		}
		$nombre_normalizado = punto_normalizar_texto( $nombre );
		$foto_url            = ( ! empty( $fila[6] ) ) ? esc_url_raw( trim( $fila[6] ) ) : '';

		$existe = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$t['catalogo_maestro']} WHERE nombre_normalizado = %s LIMIT 1",
			$nombre_normalizado
		) );
		if ( $existe ) {
			if ( $foto_url ) {
				$wpdb->update(
					$t['catalogo_maestro'],
					array( 'foto_url' => $foto_url ),
					array( 'id' => (int) $existe )
				);
				$con_foto++;
			}
			continue;
		}

		$wpdb->insert(
			$t['catalogo_maestro'],
			array(
				'nombre'             => $nombre,
				'nombre_normalizado' => $nombre_normalizado,
				'marca'              => isset( $fila[1] ) ? sanitize_text_field( $fila[1] ) : '',
				'sku'                => isset( $fila[2] ) ? sanitize_text_field( $fila[2] ) : '',
				'categoria'          => isset( $fila[3] ) ? sanitize_text_field( $fila[3] ) : '',
				'unidad_tipo'        => ( ! empty( $fila[4] ) ) ? sanitize_key( $fila[4] ) : 'unidad',
				'unidad_valor_base'  => ( isset( $fila[5] ) && '' !== $fila[5] ) ? (float) $fila[5] : null,
				'foto_url'           => $foto_url,
				'estado'             => 'aprobado',
				'creado_en'          => current_time( 'mysql' ),
			)
		);
		$agregados++;
		if ( $foto_url ) {
			$con_foto++;
		}
	}
	fclose( $fh );

	return array( 'agregados' => $agregados, 'con_foto' => $con_foto );
}

/**
 * v1.24 — actualizar SOLO la foto de productos que ya existen, por nombre. Formato: 2
 * columnas, nombre;url, separado por PUNTO Y COMA (para no chocar con comas dentro de
 * nombres de producto). Empareja por nombre_normalizado exacto — pensado para subir el
 * resultado de un cruce hecho por fuera (nombre de archivo de fotos vs. nombre de producto),
 * no para adivinar coincidencias parciales acá.
 */
function punto_cm_actualizar_fotos_csv( $tmp_path ) {
	global $wpdb;
	$t             = punto_tablas();
	$actualizados  = 0;
	$no_encontrado = array();

	$fh = fopen( $tmp_path, 'r' );
	if ( ! $fh ) {
		return array( 'actualizados' => 0, 'no_encontrados' => array() );
	}

	while ( ( $fila = fgetcsv( $fh, 0, ';' ) ) !== false ) {
		$nombre = isset( $fila[0] ) ? sanitize_text_field( $fila[0] ) : '';
		$url    = isset( $fila[1] ) ? esc_url_raw( trim( $fila[1] ) ) : '';
		if ( '' === $nombre || '' === $url || 'nombre' === strtolower( $nombre ) ) {
			continue;
		}
		$nombre_normalizado = punto_normalizar_texto( $nombre );
		$id                 = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$t['catalogo_maestro']} WHERE nombre_normalizado = %s LIMIT 1",
			$nombre_normalizado
		) );
		if ( ! $id ) {
			$no_encontrado[] = $nombre;
			continue;
		}
		$wpdb->update( $t['catalogo_maestro'], array( 'foto_url' => $url ), array( 'id' => (int) $id ) );
		$actualizados++;
	}
	fclose( $fh );

	return array( 'actualizados' => $actualizados, 'no_encontrados' => $no_encontrado );
}

/**
 * v1.14 — FrixPOS → Respaldos. La pantalla que faltaba: ver QUÉ hay guardado de cada negocio,
 * sin tener que entrar a la base de datos a mano.
 *
 * Vista lista: una fila por cuenta con respaldo — correo, nombre, si paga o no, cuándo fue el
 * último, cuánto ocupa y los conteos que el propio POS calculó (productos/pedidos/clientes).
 * Vista detalle (?cuenta=ID): el resumen por día, más lo que trae el respaldo más reciente
 * abierto de verdad — datos del negocio, tasas, métodos de cobro y las fotos de sus productos.
 *
 * A propósito NO deja editar ni borrar nada: es una pantalla de consulta. Tocar el respaldo de
 * alguien desde acá sería la forma más fácil de romperle la recuperación sin darse cuenta.
 */
function punto_admin_respaldos() {
	global $wpdb;
	$t = punto_tablas();

	$cuenta_id = isset( $_GET['cuenta'] ) ? (int) $_GET['cuenta'] : 0;

	if ( $cuenta_id ) {
		punto_admin_respaldo_detalle( $cuenta_id );
		return;
	}

	$filas = $wpdb->get_results(
		"SELECT wp_user_id,
		        COUNT(*)            AS cuantos,
		        MAX(fecha)          AS ultima,
		        SUM(LENGTH(payload)) AS bytes
		   FROM {$t['respaldos']}
		  GROUP BY wp_user_id
		  ORDER BY ultima DESC"
	);
	?>
	<div class="wrap">
		<h1>Respaldos de negocios</h1>
		<p class="description">Todo lo que cada negocio tiene guardado en este servidor: su catálogo, sus clientes, sus ventas del último mes, su configuración y sus tasas. <strong>Se guarda de todos</strong>, paguen o no — la diferencia es que solo un negocio con código activo puede recuperarlo desde la app.</p>

		<?php if ( ! $filas ) : ?>
			<p>Todavía no hay respaldos. Aparecen acá en cuanto un negocio cierre turno o toque «Respaldar ahora» en su app.</p>
		<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead><tr>
				<th style="width:52px">Foto</th>
				<th>Cuenta</th>
				<th>Plan</th>
				<th>Último respaldo</th>
				<th style="width:70px">Días</th>
				<th>Contenido</th>
				<th style="width:90px">Tamaño</th>
				<th style="width:110px"></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $filas as $f ) :
				$u = get_userdata( $f->wp_user_id );
				if ( ! $u ) {
					continue;
				}
				$ultimo  = $wpdb->get_row( $wpdb->prepare(
					"SELECT resumen FROM {$t['respaldos']} WHERE wp_user_id = %d ORDER BY fecha DESC LIMIT 1",
					$f->wp_user_id
				) );
				$res     = $ultimo ? json_decode( $ultimo->resumen, true ) : array();
				$paga    = punto_cuenta_tiene_negocio_activo( $f->wp_user_id );
				$trial   = punto_cuenta_trial_info( $u );
				$foto    = get_user_meta( $f->wp_user_id, 'punto_foto_url', true );
				$nombre  = get_user_meta( $f->wp_user_id, 'punto_nombre', true );
				?>
				<tr>
					<td><?php if ( $foto ) : ?><img src="<?php echo esc_url( $foto ); ?>" style="width:40px;height:40px;object-fit:cover;border-radius:50%" alt=""><?php endif; ?></td>
					<td>
						<strong><?php echo esc_html( $nombre ? $nombre : '(sin nombre)' ); ?></strong><br>
						<span class="description"><?php echo esc_html( $u->user_email ); ?></span>
					</td>
					<td>
						<?php if ( $paga ) : ?>
							<span style="color:#00733f;font-weight:600">Pago</span>
						<?php elseif ( $trial['activo'] ) : ?>
							<span style="color:#8a6100">Prueba</span>
						<?php else : ?>
							<span style="color:#a00">Gratis</span>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $f->ultima ); ?></td>
					<td><?php echo (int) $f->cuantos; ?></td>
					<td class="description">
						<?php echo (int) ( $res['productos'] ?? 0 ); ?> productos ·
						<?php echo (int) ( $res['pedidos'] ?? 0 ); ?> pedidos ·
						<?php echo (int) ( $res['clientes'] ?? 0 ); ?> clientes
					</td>
					<td><?php echo esc_html( size_format( (int) $f->bytes ) ); ?></td>
					<td><a class="button button-small" href="?page=punto-respaldos&cuenta=<?php echo (int) $f->wp_user_id; ?>">Ver todo</a></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>
	<?php
}

/** Detalle de UNA cuenta: su perfil, sus respaldos por día, y qué trae el más reciente. */
function punto_admin_respaldo_detalle( $cuenta_id ) {
	global $wpdb;
	$t = punto_tablas();

	$u = get_userdata( $cuenta_id );
	if ( ! $u ) {
		echo '<div class="wrap"><h1>Respaldos</h1><p>Esa cuenta ya no existe.</p></div>';
		return;
	}

	$respaldos = $wpdb->get_results( $wpdb->prepare(
		"SELECT id, fecha, resumen, LENGTH(payload) AS bytes FROM {$t['respaldos']} WHERE wp_user_id = %d ORDER BY fecha DESC",
		$cuenta_id
	) );
	$ultimo  = $wpdb->get_row( $wpdb->prepare(
		"SELECT payload FROM {$t['respaldos']} WHERE wp_user_id = %d ORDER BY fecha DESC LIMIT 1",
		$cuenta_id
	) );
	$p       = $ultimo ? json_decode( $ultimo->payload, true ) : array();
	$cfg     = isset( $p['cfg'] ) && is_array( $p['cfg'] ) ? $p['cfg'] : array();
	$prods   = isset( $p['products'] ) && is_array( $p['products'] ) ? $p['products'] : array();
	$foto    = get_user_meta( $cuenta_id, 'punto_foto_url', true );
	$trial   = punto_cuenta_trial_info( $u );
	$paga    = punto_cuenta_tiene_negocio_activo( $cuenta_id );
	$dato    = function ( $k, $def = '—' ) use ( $cfg ) {
		return ( isset( $cfg[ $k ] ) && '' !== $cfg[ $k ] ) ? $cfg[ $k ] : $def;
	};
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_user_meta( $cuenta_id, 'punto_nombre', true ) ?: $u->user_email ); ?></h1>
		<p><a href="?page=punto-respaldos">&larr; Volver a todos los respaldos</a></p>

		<div style="display:flex;gap:20px;flex-wrap:wrap;margin:16px 0">

			<div style="background:#fff;border:1px solid #ccd0d4;padding:16px;flex:1 1 320px">
				<h2 style="margin-top:0">La persona</h2>
				<?php if ( $foto ) : ?>
					<p><img src="<?php echo esc_url( $foto ); ?>" style="width:84px;height:84px;object-fit:cover;border-radius:50%" alt=""></p>
				<?php endif; ?>
				<table class="widefat striped"><tbody>
					<tr><td>Correo</td><td><?php echo esc_html( $u->user_email ); ?></td></tr>
					<tr><td>Nombre</td><td><?php echo esc_html( get_user_meta( $cuenta_id, 'punto_nombre', true ) ); ?></td></tr>
					<tr><td>Tipo</td><td><?php echo esc_html( get_user_meta( $cuenta_id, 'punto_tipo', true ) ); ?></td></tr>
					<tr><td>Cédula / RIF</td><td><?php echo esc_html( get_user_meta( $cuenta_id, 'punto_id_number', true ) ); ?></td></tr>
					<tr><td>Teléfono</td><td><?php echo esc_html( get_user_meta( $cuenta_id, 'punto_phone', true ) ); ?></td></tr>
					<tr><td>Dirección</td><td><?php echo esc_html( get_user_meta( $cuenta_id, 'punto_address', true ) ); ?></td></tr>
					<tr><td>Cuenta creada</td><td><?php echo esc_html( $u->user_registered ); ?></td></tr>
					<tr><td>Plan</td><td><?php echo $paga ? 'Pago (puede recuperar)' : ( $trial['activo'] ? 'Prueba gratis, vence ' . esc_html( $trial['vence'] ) : 'Gratis (NO puede recuperar)' ); ?></td></tr>
					<tr><td>Catálogo público</td><td><?php
						$slug = get_user_meta( $cuenta_id, 'punto_catalogo_slug', true );
						echo $slug ? '<a href="' . esc_url( home_url( '/p/' . $slug ) ) . '" target="_blank">/p/' . esc_html( $slug ) . '</a>' : '—';
					?></td></tr>
				</tbody></table>
			</div>

			<div style="background:#fff;border:1px solid #ccd0d4;padding:16px;flex:1 1 320px">
				<h2 style="margin-top:0">Cómo tiene configurado su FrixPOS</h2>
				<p class="description">Del respaldo más reciente. Es la configuración real con la que está vendiendo.</p>
				<table class="widefat striped"><tbody>
					<tr><td>Negocio</td><td><?php echo esc_html( $dato( 'bizName' ) ); ?></td></tr>
					<tr><td>RIF</td><td><?php echo esc_html( $dato( 'bizRif' ) ); ?></td></tr>
					<tr><td>Teléfono</td><td><?php echo esc_html( $dato( 'bizPhone' ) ); ?></td></tr>
					<tr><td>Tasa BCV</td><td><?php echo esc_html( $dato( 'bcv', '0' ) ); ?></td></tr>
					<tr><td>Tasa Paralelo</td><td><?php echo esc_html( $dato( 'paralelo', '0' ) ); ?></td></tr>
					<tr><td>IVA general</td><td><?php echo esc_html( $dato( 'ivaGeneral', '0' ) ); ?>%</td></tr>
					<tr><td>Gastos operativos por defecto</td><td><?php echo esc_html( $dato( 'gastosPctDefault', '0' ) ); ?>%</td></tr>
					<tr><td>Otras monedas</td><td><?php
						$mons = isset( $cfg['monedas'] ) && is_array( $cfg['monedas'] ) ? $cfg['monedas'] : array();
						if ( ! $mons ) { echo '—'; }
						else {
							$txt = array();
							foreach ( $mons as $m ) { $txt[] = esc_html( ( $m['nombre'] ?? '?' ) . ' @ ' . ( $m['tasa'] ?? 0 ) ); }
							echo implode( ', ', $txt );
						}
					?></td></tr>
					<tr><td>Clientes / Pedidos guardados</td><td><?php
						echo (int) count( (array) ( $p['clients'] ?? array() ) ) . ' / ' . (int) count( (array) ( $p['orders'] ?? array() ) );
					?></td></tr>
				</tbody></table>
			</div>

		</div>

		<h2>Respaldos guardados</h2>
		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th>Fecha</th><th>Contenido</th><th style="width:110px">Tamaño</th></tr></thead>
			<tbody>
			<?php if ( ! $respaldos ) : ?>
				<tr><td colspan="3">Sin respaldos.</td></tr>
			<?php endif; ?>
			<?php foreach ( (array) $respaldos as $r ) :
				$res = json_decode( $r->resumen, true ); ?>
				<tr>
					<td><strong><?php echo esc_html( $r->fecha ); ?></strong></td>
					<td class="description">
						<?php echo (int) ( $res['productos'] ?? 0 ); ?> productos ·
						<?php echo (int) ( $res['pedidos'] ?? 0 ); ?> pedidos ·
						<?php echo (int) ( $res['clientes'] ?? 0 ); ?> clientes
						<?php if ( ! empty( $res['porCobrarBs'] ) ) : ?> · <?php echo esc_html( number_format( (float) $res['porCobrarBs'], 0, ',', '.' ) ); ?> Bs por cobrar<?php endif; ?>
					</td>
					<td><?php echo esc_html( size_format( (int) $r->bytes ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<h2>Su catálogo en el último respaldo <span class="description">(<?php echo count( $prods ); ?> productos)</span></h2>
		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th style="width:56px">Foto</th><th>Producto</th><th style="width:110px">Stock</th><th style="width:130px">Costo (USD)</th><th style="width:110px">Ganancia</th></tr></thead>
			<tbody>
			<?php if ( ! $prods ) : ?>
				<tr><td colspan="5">El último respaldo no trae productos.</td></tr>
			<?php endif; ?>
			<?php
			// v1.18 — a propósito NO se carga la imagen acá (antes sí, con <img src="...">): son
			// las fotos privadas de cada negocio, no hace falta verlas para administrar el
			// servidor, y cargar cientos de <img> por cuenta (muchas en base64, sin comprimir)
			// pesa la página del admin para nada. Solo se dice SI tiene foto o no.
			foreach ( array_slice( $prods, 0, 200 ) as $pr ) :
				$tiene_foto = ! empty( $pr['photo'] ); ?>
				<tr>
					<td><?php echo $tiene_foto ? '<span style="color:#00733f" title="Tiene foto">&#10003;</span>' : '<span style="color:#ccc">&mdash;</span>'; ?></td>
					<td><?php echo esc_html( $pr['name'] ?? '' ); ?></td>
					<td><?php echo esc_html( $pr['stock'] ?? 0 ); ?></td>
					<td><?php echo esc_html( number_format( (float) ( $pr['costBcv'] ?? 0 ), 2 ) ); ?></td>
					<td><?php echo esc_html( $pr['margin'] ?? 0 ); ?>%</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php if ( count( $prods ) > 200 ) : ?>
			<p class="description">Mostrando los primeros 200 de <?php echo count( $prods ); ?>.</p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * v1.15 — FrixPOS → Limpiar datos. Para dejar el servidor en cero antes del lanzamiento real,
 * sin tener que entrar a phpMyAdmin.
 *
 * Cada bloque se borra por separado y con doble confirmación (checkbox "sí, estoy seguro" +
 * el diálogo del navegador), porque son operaciones sin vuelta atrás. A propósito NO hay un
 * botón de "borrar todo de una": el día que alguien se equivoque, que se equivoque en una
 * tabla, no en las siete.
 *
 * Las cuentas de usuario NO se tocan desde acá — borrar usuarios de WordPress tiene sus
 * propias consecuencias (autoría de adjuntos, etc.) y para eso ya existe Usuarios en wp-admin.
 */
function punto_admin_limpiar() {
	global $wpdb;
	$t = punto_tablas();

	$bloques = array(
		'ventas'    => array( 'tabla' => $t['ventas'],           'label' => 'Ventas sincronizadas',      'nota' => 'Todo lo que los teléfonos han subido con /sync.' ),
		'respaldos' => array( 'tabla' => $t['respaldos'],         'label' => 'Respaldos de negocios',     'nota' => 'Las copias completas de cada cuenta. No borra las fotos de la Biblioteca de Medios.' ),
		'contab'    => array( 'tabla' => $t['contab_mensual'],    'label' => 'Contabilidad mensual',      'nota' => 'Las fotos por mes cerrado de cada cuenta.' ),
		'stock'     => array( 'tabla' => $t['stock'],             'label' => 'Stock compartido',          'nota' => 'El conteo por producto que comparten varias cajas del mismo negocio.' ),
		'catpub'    => array( 'tabla' => $t['catalogo_publico'],  'label' => 'Catálogos digitales',       'nota' => 'Las vitrinas públicas /p/slug. El nombre del enlace queda en el perfil de cada cuenta.' ),
		'maestro'   => array( 'tabla' => $t['catalogo_maestro'],  'label' => 'Catálogo maestro',          'nota' => 'El catálogo compartido entre todos los negocios, aprobados y por revisar.' ),
		'codigos'   => array( 'tabla' => $t['codigos'],           'label' => 'Códigos de activación',     'nota' => 'Los códigos generados, usados y sin usar.' ),
		'negocios'  => array( 'tabla' => $t['negocios'],          'label' => 'Negocios',                  'nota' => 'Los negocios activados. Ojo: sus ventas quedan huérfanas si no las borras también.' ),
		'solicitudes' => array( 'tabla' => $t['solicitudes'],     'label' => 'Solicitudes del formulario', 'nota' => 'Los leads de "comprar código" y "soporte" del sitio de marketing.' ),
	);

	if ( isset( $_POST['punto_limpiar'] ) && check_admin_referer( 'punto_limpiar' ) ) {
		$k = sanitize_key( $_POST['punto_limpiar'] );
		if ( isset( $bloques[ $k ] ) && ! empty( $_POST['confirmo'] ) ) {
			$wpdb->query( 'TRUNCATE TABLE ' . $bloques[ $k ]['tabla'] );
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html( $bloques[ $k ]['label'] ) . '</strong>: borrado y contador reiniciado.</p></div>';
		} elseif ( empty( $_POST['confirmo'] ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>No se borró nada: falta marcar la casilla de confirmación.</p></div>';
		}
	}

	if ( isset( $_POST['punto_limpiar_meta'] ) && check_admin_referer( 'punto_limpiar' ) && ! empty( $_POST['confirmo'] ) ) {
		$borradas = $wpdb->query(
			"DELETE FROM {$wpdb->usermeta}
			  WHERE meta_key IN ('punto_catalogo_slug','punto_catalogo_visible','punto_catalogo_mostrar_precios','punto_catalogo_logo_url','punto_foto_url')"
		);
		echo '<div class="notice notice-success is-dismissible"><p>Preferencias de catálogo y fotos de perfil: ' . (int) $borradas . ' registro(s) borrado(s).</p></div>';
	}
	?>
	<div class="wrap">
		<h1>Limpiar datos de prueba</h1>
		<p class="description" style="max-width:70ch">Para dejar el servidor en cero antes de salir a producción. <strong>Nada de esto se puede deshacer</strong> — haz una copia de la base de datos antes si tienes alguna duda. Las cuentas de usuario no se tocan desde acá: eso se hace en <a href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>">Usuarios</a>.</p>

		<form method="post">
			<?php wp_nonce_field( 'punto_limpiar' ); ?>
			<p style="background:#fff;border-left:4px solid #d63638;padding:12px 16px;max-width:70ch">
				<label><input type="checkbox" name="confirmo" value="1"> <strong>Sí, entiendo que lo que borre acá no se puede recuperar.</strong></label><br>
				<span class="description">Esta casilla aplica a todos los botones de abajo. Si no está marcada, no se borra nada.</span>
			</p>

			<table class="wp-list-table widefat fixed striped" style="max-width:900px">
				<thead><tr><th style="width:220px">Qué</th><th style="width:90px">Cuántos</th><th>Detalle</th><th style="width:120px"></th></tr></thead>
				<tbody>
				<?php foreach ( $bloques as $k => $b ) :
					$n = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $b['tabla'] ); ?>
					<tr>
						<td><strong><?php echo esc_html( $b['label'] ); ?></strong></td>
						<td><?php echo $n; ?></td>
						<td class="description"><?php echo esc_html( $b['nota'] ); ?></td>
						<td>
							<button class="button" name="punto_limpiar" value="<?php echo esc_attr( $k ); ?>"
								onclick="return confirm('Vas a borrar: <?php echo esc_attr( $b['label'] ); ?>. Esto no se puede deshacer. ¿Seguro?')"
								<?php disabled( 0 === $n ); ?>>Borrar</button>
						</td>
					</tr>
				<?php endforeach; ?>
					<tr>
						<td><strong>Preferencias de catálogo y fotos de perfil</strong></td>
						<td>—</td>
						<td class="description">El nombre del enlace público, si estaba publicado, si mostraba precios, y la foto de perfil de cada cuenta.</td>
						<td>
							<button class="button" name="punto_limpiar_meta" value="1"
								onclick="return confirm('Vas a borrar las preferencias de catálogo y las fotos de perfil de TODAS las cuentas. ¿Seguro?')">Borrar</button>
						</td>
					</tr>
				</tbody>
			</table>
		</form>

		<h2 style="margin-top:28px">Lo que esto NO borra</h2>
		<ul style="max-width:70ch;list-style:disc;padding-left:20px">
			<li><strong>Las cuentas de los usuarios</strong> (correo y contraseña) — se borran desde Usuarios en wp-admin.</li>
			<li><strong>Las imágenes de la Biblioteca de Medios.</strong> Quedan huérfanas al borrar respaldos o el catálogo maestro; si quieres limpiarlas, hazlo desde Medios (puedes filtrar por el autor de cada cuenta).</li>
			<li><strong>Los datos en el teléfono de cada negocio.</strong> Viven en el navegador de ellos, no en tu servidor — el dueño los borra desde su app, en Configuración → Negocio.</li>
		</ul>
	</div>
	<?php
}

function punto_admin_catalogo() {
	global $wpdb;
	$t = punto_tablas();

	if ( isset( $_POST['punto_cm_agregar'] ) && check_admin_referer( 'punto_catalogo' ) ) {
		$nombre = sanitize_text_field( wp_unslash( $_POST['cm_nombre'] ?? '' ) );
		if ( '' !== $nombre ) {
			$wpdb->insert(
				$t['catalogo_maestro'],
				array(
					'nombre'             => $nombre,
					'nombre_normalizado' => punto_normalizar_texto( $nombre ),
					'marca'              => sanitize_text_field( wp_unslash( $_POST['cm_marca'] ?? '' ) ),
					'sku'                => sanitize_text_field( wp_unslash( $_POST['cm_sku'] ?? '' ) ),
					'categoria'          => sanitize_text_field( wp_unslash( $_POST['cm_categoria'] ?? '' ) ),
					'unidad_tipo'        => 'unidad',
					'foto_url'           => punto_cm_procesar_foto(),
					'estado'             => 'aprobado',
					'creado_en'          => current_time( 'mysql' ),
				)
			);
			echo '<div class="notice notice-success is-dismissible"><p>Producto agregado.</p></div>';
		}
	}

	if ( isset( $_POST['punto_cm_aprobar'] ) && check_admin_referer( 'punto_catalogo' ) ) {
		$id     = (int) $_POST['cm_id'];
		$nombre = sanitize_text_field( wp_unslash( $_POST['cm_nombre'] ?? '' ) );
		$campos = array(
			'nombre'             => $nombre,
			'nombre_normalizado' => punto_normalizar_texto( $nombre ),
			'marca'              => sanitize_text_field( wp_unslash( $_POST['cm_marca'] ?? '' ) ),
			'sku'                => sanitize_text_field( wp_unslash( $_POST['cm_sku'] ?? '' ) ),
			'categoria'          => sanitize_text_field( wp_unslash( $_POST['cm_categoria'] ?? '' ) ),
			'estado'             => 'aprobado',
			'foto_pendiente'     => null, // aprobado o no, deja de ser "pendiente de revisar"
		);
		$foto_url = punto_cm_procesar_foto();
		if ( $foto_url ) {
			// se subió un archivo nuevo o se pegó una URL en el propio formulario: esa manda.
			$campos['foto_url'] = $foto_url;
		} elseif ( ! empty( $_POST['cm_usar_foto_pendiente'] ) ) {
			// "usar esta foto" — la que trajo el negocio, recién ahora se sube de verdad a
			// Media (mientras estaba pendiente era solo una vista previa en base64).
			$fila_actual = $wpdb->get_row( $wpdb->prepare( "SELECT foto_pendiente FROM {$t['catalogo_maestro']} WHERE id = %d", $id ) );
			if ( $fila_actual && $fila_actual->foto_pendiente ) {
				$url_subida = punto_guardar_foto_base64( $fila_actual->foto_pendiente, 'Catálogo maestro — ' . $nombre );
				if ( $url_subida ) {
					$campos['foto_url'] = $url_subida;
				}
			}
		}
		$wpdb->update( $t['catalogo_maestro'], $campos, array( 'id' => $id ) );
		echo '<div class="notice notice-success is-dismissible"><p>Producto aprobado.</p></div>';
	}

	if ( isset( $_POST['punto_cm_eliminar'] ) && check_admin_referer( 'punto_catalogo' ) ) {
		$wpdb->delete( $t['catalogo_maestro'], array( 'id' => (int) $_POST['cm_id'] ) );
		echo '<div class="notice notice-success is-dismissible"><p>Eliminado.</p></div>';
	}

	if ( isset( $_POST['punto_cm_importar'] ) && check_admin_referer( 'punto_catalogo' ) && ! empty( $_FILES['cm_csv']['tmp_name'] ) ) {
		$r   = punto_cm_importar_csv( $_FILES['cm_csv']['tmp_name'] );
		$msg = (int) $r['agregados'] . ' producto(s) nuevo(s) importado(s)';
		$msg .= $r['con_foto'] ? ' (' . (int) $r['con_foto'] . ' con foto)' : '';
		$msg .= ' — se saltaron los que ya existían.';
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
	}

	if ( isset( $_POST['punto_cm_actualizar_fotos'] ) && check_admin_referer( 'punto_catalogo' ) && ! empty( $_FILES['cm_fotos_csv']['tmp_name'] ) ) {
		$r   = punto_cm_actualizar_fotos_csv( $_FILES['cm_fotos_csv']['tmp_name'] );
		$msg = (int) $r['actualizados'] . ' foto(s) actualizada(s).';
		if ( $r['no_encontrados'] ) {
			$msg .= ' ' . count( $r['no_encontrados'] ) . ' nombre(s) no se encontraron en el catálogo: ' . implode( ', ', array_slice( $r['no_encontrados'], 0, 15 ) );
			if ( count( $r['no_encontrados'] ) > 15 ) {
				$msg .= '…';
			}
		}
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
	}

	$estado_ver = ( isset( $_GET['cm_estado'] ) && 'pendiente' === $_GET['cm_estado'] ) ? 'pendiente' : 'aprobado';
	$buscar     = isset( $_GET['cm_buscar'] ) ? sanitize_text_field( wp_unslash( $_GET['cm_buscar'] ) ) : '';
	if ( '' !== $buscar ) {
		$like  = '%' . $wpdb->esc_like( $buscar ) . '%';
		$filas = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$t['catalogo_maestro']} WHERE estado = %s AND (nombre LIKE %s OR categoria LIKE %s) ORDER BY id DESC LIMIT 300",
			$estado_ver, $like, $like
		) );
	} else {
		$filas = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$t['catalogo_maestro']} WHERE estado = %s ORDER BY id DESC LIMIT 300",
			$estado_ver
		) );
	}
	$pendientes_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['catalogo_maestro']} WHERE estado = 'pendiente'" );
	?>
	<div class="wrap">
		<h1>Catálogo maestro</h1>
		<p class="description">Productos compartidos entre todos los negocios que usan FrixPOS — nombre, marca, SKU, foto y categoría. Nunca lleva costo ni precio, eso es privado de cada negocio. Lo que agregas aquí entra directo aprobado; lo que sugieren los negocios desde la app cae en "Por revisar" hasta que lo mires.</p>

		<h2 class="nav-tab-wrapper">
			<a href="?page=punto-catalogo&cm_estado=aprobado<?php echo $buscar ? '&cm_buscar=' . urlencode( $buscar ) : ''; ?>" class="nav-tab <?php echo 'aprobado' === $estado_ver ? 'nav-tab-active' : ''; ?>">Aprobados</a>
			<a href="?page=punto-catalogo&cm_estado=pendiente<?php echo $buscar ? '&cm_buscar=' . urlencode( $buscar ) : ''; ?>" class="nav-tab <?php echo 'pendiente' === $estado_ver ? 'nav-tab-active' : ''; ?>">Por revisar<?php echo $pendientes_count ? ' (' . $pendientes_count . ')' : ''; ?></a>
		</h2>

		<form method="get" style="margin:14px 0;display:flex;gap:8px;align-items:center;max-width:420px">
			<input type="hidden" name="page" value="punto-catalogo">
			<input type="hidden" name="cm_estado" value="<?php echo esc_attr( $estado_ver ); ?>">
			<input type="search" name="cm_buscar" value="<?php echo esc_attr( $buscar ); ?>" class="regular-text" placeholder="Buscar por nombre o categoría…" style="flex:1 1 auto">
			<button class="button">Buscar</button>
			<?php if ( $buscar ) : ?><a class="button" href="?page=punto-catalogo&cm_estado=<?php echo esc_attr( $estado_ver ); ?>">Quitar filtro</a><?php endif; ?>
		</form>
		<?php if ( $buscar ) : ?>
			<p class="description"><?php echo count( $filas ); ?> resultado(s) para "<?php echo esc_html( $buscar ); ?>".</p>
		<?php endif; ?>

		<div style="display:flex;gap:24px;margin:16px 0;flex-wrap:wrap">
			<form method="post" enctype="multipart/form-data" style="background:#fff;border:1px solid #ccd0d4;padding:16px;max-width:420px;flex:1 1 380px">
				<?php wp_nonce_field( 'punto_catalogo' ); ?>
				<h2 style="margin-top:0">Agregar producto</h2>
				<p><label>Nombre<br><input type="text" name="cm_nombre" class="regular-text" required></label></p>
				<p><label>Marca (opcional)<br><input type="text" name="cm_marca" class="regular-text"></label></p>
				<p><label>SKU o código de barras (opcional)<br><input type="text" name="cm_sku" class="regular-text"></label></p>
				<p><label>Categoría (opcional)<br><input type="text" name="cm_categoria" class="regular-text"></label></p>
				<p><label>Foto — sube un archivo<br><input type="file" name="cm_foto" accept="image/*"></label></p>
				<p><label>...o pega la URL de una foto que ya existe<br><input type="url" name="cm_foto_url" class="regular-text" placeholder="https://"></label></p>
				<p><button class="button button-primary" name="punto_cm_agregar" value="1">Agregar</button></p>
			</form>

			<form method="post" enctype="multipart/form-data" style="background:#fff;border:1px solid #ccd0d4;padding:16px;max-width:420px;flex:1 1 320px">
				<?php wp_nonce_field( 'punto_catalogo' ); ?>
				<h2 style="margin-top:0">Importar por CSV</h2>
				<p class="description">Columnas en este orden, separadas por coma, sin encabezado obligatorio: <code>nombre, marca, sku, categoría, tipo de unidad, valor de unidad, foto (URL, opcional)</code>. Solo <code>nombre</code> es obligatorio. Si el nombre ya existe, no se duplica — pero si esa fila trae una foto y todavía no tenía, se le agrega. Puedes correr el mismo archivo varias veces sin miedo.</p>
				<p><input type="file" name="cm_csv" accept=".csv"></p>
				<p><button class="button button-primary" name="punto_cm_importar" value="1">Importar</button></p>
			</form>

			<form method="post" enctype="multipart/form-data" style="background:#fff;border:1px solid #ccd0d4;padding:16px;max-width:420px;flex:1 1 320px">
				<?php wp_nonce_field( 'punto_catalogo' ); ?>
				<h2 style="margin-top:0">Actualizar fotos por CSV</h2>
				<p class="description">Solo para productos que <b>ya existen</b>. 2 columnas separadas por <b>punto y coma</b>: <code>nombre;url</code> — el nombre tiene que coincidir con el que ya está guardado (mayúsculas/tildes no importan). Útil después de subir muchas fotos a un CDN y cruzarlas por nombre de archivo.</p>
				<p><input type="file" name="cm_fotos_csv" accept=".csv"></p>
				<p><button class="button button-primary" name="punto_cm_actualizar_fotos" value="1">Actualizar fotos</button></p>
			</form>
		</div>

		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th style="width:56px">Foto</th><th>Nombre</th><th>Marca</th><th>SKU</th><th>Categoría</th><th>Sugerido por</th><th style="width:280px"></th></tr></thead>
			<tbody>
			<?php if ( ! $filas ) : ?>
				<tr><td colspan="7">Nada acá todavía.</td></tr>
			<?php endif; ?>
			<?php foreach ( (array) $filas as $p ) : ?>
				<?php $sugerido_por = $p->wp_user_id ? get_userdata( $p->wp_user_id ) : false; ?>
				<tr>
					<td>
						<?php if ( $p->foto_url ) : ?>
							<img src="<?php echo esc_url( $p->foto_url ); ?>" style="width:44px;height:44px;object-fit:cover;border-radius:6px" alt="">
						<?php elseif ( ! empty( $p->foto_pendiente ) ) : ?>
							<img src="<?php echo esc_attr( $p->foto_pendiente ); ?>" style="width:44px;height:44px;object-fit:cover;border-radius:6px;border:2px solid #dba617" alt="" title="Foto que subió el negocio — todavía sin aprobar">
						<?php endif; ?>
					</td>
					<td><strong><?php echo esc_html( $p->nombre ); ?></strong></td>
					<td><?php echo esc_html( $p->marca ); ?></td>
					<td><?php echo esc_html( $p->sku ); ?></td>
					<td><?php echo esc_html( $p->categoria ); ?></td>
					<td><?php echo $sugerido_por ? esc_html( $sugerido_por->user_email ) : '<em>tú</em>'; ?></td>
					<td>
						<?php $es_pendiente = 'pendiente' === $estado_ver; ?>
						<details>
							<summary style="cursor:pointer" class="button button-small"><?php echo $es_pendiente ? 'Aprobar / editar' : 'Editar'; ?></summary>
							<form method="post" enctype="multipart/form-data" style="margin-top:8px;min-width:260px">
								<?php wp_nonce_field( 'punto_catalogo' ); ?>
								<input type="hidden" name="cm_id" value="<?php echo (int) $p->id; ?>">
								<p style="margin:4px 0"><input type="text" name="cm_nombre" value="<?php echo esc_attr( $p->nombre ); ?>" class="regular-text" placeholder="Nombre"></p>
								<p style="margin:4px 0"><input type="text" name="cm_marca" value="<?php echo esc_attr( $p->marca ); ?>" class="regular-text" placeholder="Marca"></p>
								<p style="margin:4px 0"><input type="text" name="cm_sku" value="<?php echo esc_attr( $p->sku ); ?>" class="regular-text" placeholder="SKU"></p>
								<p style="margin:4px 0"><input type="text" name="cm_categoria" value="<?php echo esc_attr( $p->categoria ); ?>" class="regular-text" placeholder="Categoría"></p>
								<?php if ( ! empty( $p->foto_pendiente ) ) : ?>
									<p style="margin:8px 0;display:flex;align-items:center;gap:8px">
										<img src="<?php echo esc_attr( $p->foto_pendiente ); ?>" style="width:60px;height:60px;object-fit:cover;border-radius:6px;border:2px solid #dba617" alt="">
										<label><input type="checkbox" name="cm_usar_foto_pendiente" value="1" checked> Usar esta foto (la que subió el negocio)</label>
									</p>
									<p class="description" style="margin:0 0 8px">Desmárcala y sube o pega otra abajo si prefieres una mejor.</p>
								<?php endif; ?>
								<?php if ( $p->foto_url ) : ?>
									<p style="margin:8px 0;display:flex;align-items:center;gap:8px">
										<img src="<?php echo esc_url( $p->foto_url ); ?>" style="width:60px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #ccd0d4" alt="">
										<span class="description">Foto actual — si no se ve la imagen arriba, revisa que esta URL cargue bien en una pestaña nueva</span>
									</p>
								<?php endif; ?>
								<p style="margin:4px 0"><label>Subir archivo<br><input type="file" name="cm_foto" accept="image/*"></label></p>
								<p style="margin:4px 0"><label>...o pega/edita la URL de la foto (ej. la de tu CDN)<br><input type="url" name="cm_foto_url" value="<?php echo esc_attr( $p->foto_url ); ?>" class="regular-text" placeholder="https://"></label></p>
								<p style="margin:4px 0"><button class="button button-primary" name="punto_cm_aprobar" value="1"><?php echo $es_pendiente ? 'Aprobar' : 'Guardar cambios'; ?></button></p>
							</form>
						</details>
						<form method="post" style="display:inline-block;margin-top:6px" onsubmit="return confirm('¿Eliminar este producto del catálogo maestro?');">
							<?php wp_nonce_field( 'punto_catalogo' ); ?>
							<input type="hidden" name="cm_id" value="<?php echo (int) $p->id; ?>">
							<button class="button button-small" name="punto_cm_eliminar" value="1">Eliminar</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}
