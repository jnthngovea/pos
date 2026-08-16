<?php
/**
 * Vitrina pública del Catálogo Digital — tudominio.com/p/{slug}
 *
 * Servida directo por el plugin (ver template_include en la sección "3g. CATÁLOGO DIGITAL"
 * de punto-backend.php) — a propósito NO usa get_header()/get_footer() del tema: es una
 * página 100% de datos del plugin, sin depender de que el tema activo la sepa dibujar, y así
 * carga rápido y sin arrastrar los estilos del POS. Sí llama wp_head()/wp_footer() (no las
 * versiones de plantilla) para que analítica/SEO instalados a nivel de sitio sigan funcionando.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$t = punto_tablas();

$slug       = get_query_var( 'punto_slug' );
$wp_user_id = punto_cuenta_id_por_slug( $slug );
$visible    = $wp_user_id ? ( '1' === get_user_meta( $wp_user_id, 'punto_catalogo_visible', true ) ) : false;

if ( ! $wp_user_id || ! $visible ) {
	status_header( 404 );
	?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Catálogo no encontrado — Punto</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
</head>
<body style="margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0b0b12;color:#eee;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;text-align:center;padding:24px">
	<div>
		<h1 style="font-size:1.25rem;margin:0 0 8px">Este catálogo no existe o no está publicado</h1>
		<p style="color:#9a9aa6;margin:0">Si eres el dueño del negocio, revisa Configuración → Catálogo Digital en tu app Punto.</p>
	</div>
</body>
</html>
	<?php
	exit;
}

// v1.16 — cache corto a propósito: esta página SÍ conviene que la sirva rápido un cache de
// página completa (LiteSpeed, etc.) para el cliente que la abre desde WhatsApp con mala señal,
// pero el dueño puede republicar precios/fotos en cualquier momento — 5 minutos es buen punto
// medio entre "rápido" y "no se queda viendo un precio de hace tres días".
header( 'Cache-Control: public, max-age=300' );

$nombre_negocio = get_user_meta( $wp_user_id, 'punto_nombre', true );
$nombre_negocio = $nombre_negocio ? $nombre_negocio : 'Catálogo';
$logo_url       = get_user_meta( $wp_user_id, 'punto_catalogo_logo_url', true );
$mostrar_precio = ( '1' === get_user_meta( $wp_user_id, 'punto_catalogo_mostrar_precios', true ) );
$telefono_raw   = get_user_meta( $wp_user_id, 'punto_phone', true );

// Normaliza a formato wa.me: solo dígitos, y si empieza en '0' (celular venezolano típico,
// ej. 0414-1234567) se cambia el 0 inicial por el código de país 58. Best-effort — si el
// dueño guardó el teléfono en otro formato, igual se manda tal cual en dígitos.
$digitos = preg_replace( '/\D+/', '', (string) $telefono_raw );
if ( $digitos && '0' === substr( $digitos, 0, 1 ) ) {
	$digitos = '58' . substr( $digitos, 1 );
}

$productos_db = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT producto_id, nombre, precio_usd, foto_url, categoria FROM {$t['catalogo_publico']} WHERE wp_user_id = %d ORDER BY orden ASC",
		$wp_user_id
	),
	ARRAY_A
);

$categorias = array();
foreach ( $productos_db as $p ) {
	if ( $p['categoria'] && ! in_array( $p['categoria'], $categorias, true ) ) {
		$categorias[] = $p['categoria'];
	}
}

$datos_js = array(
	'nombre'        => $nombre_negocio,
	'mostrarPrecio' => $mostrar_precio,
	'whatsapp'      => $digitos,
	'categorias'    => $categorias,
	'productos'     => array_map(
		function ( $p ) {
			return array(
				'id'     => $p['producto_id'],
				'nombre' => $p['nombre'],
				'precio' => ( null === $p['precio_usd'] || '' === $p['precio_usd'] ) ? null : (float) $p['precio_usd'],
				'foto'   => $p['foto_url'],
				'cat'    => $p['categoria'],
			);
		},
		$productos_db
	),
);
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title><?php echo esc_html( $nombre_negocio ); ?> — Catálogo</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Catálogo de <?php echo esc_attr( $nombre_negocio ); ?>">
<meta name="robots" content="index, follow">
<?php wp_head(); ?>
<style>
	:root{--bg:#0b0b12;--card:#15151f;--text:#f2f2f5;--text-faint:#9a9aa6;--accent:#7c6cff;--border:#26263a;--wa:#25D366}
	*{box-sizing:border-box}
	/* v37 — fondo con profundidad en vez de negro plano: tres luces suaves fijas (no se mueven
	   con el scroll), sin fuentes ni imágenes externas — nada que pese o tarde en cargar para
	   un cliente entrando desde datos móviles. Los cards siguen siendo --card sólido encima,
	   así el fondo da ambiente sin robarle legibilidad a nada. */
	body{
		margin:0;
		background:
			radial-gradient(ellipse 900px 560px at 12% -8%, rgba(124,108,255,.30), transparent 60%),
			radial-gradient(ellipse 650px 480px at 108% 10%, rgba(255,138,92,.14), transparent 58%),
			radial-gradient(ellipse 800px 620px at 50% 115%, rgba(61,220,132,.09), transparent 62%),
			var(--bg);
		background-attachment:fixed;
		background-repeat:no-repeat;
		color:var(--text);
		font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
	}
	header{position:sticky;top:0;z-index:5;background:rgba(11,11,18,.75);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border-bottom:1px solid var(--border);padding:14px 16px}
	.top-row{display:flex;align-items:center;gap:10px;max-width:920px;margin:0 auto}
	.logo{width:40px;height:40px;border-radius:10px;object-fit:cover;background:var(--card);flex-shrink:0}
	.biz-name{font-weight:700;font-size:1.05rem;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
	.search{max-width:920px;margin:10px auto 0}
	.search input{width:100%;padding:10px 14px;border-radius:10px;border:1px solid var(--border);background:var(--card);color:var(--text);font-size:.95rem}
	.search input:focus{outline:2px solid var(--accent);outline-offset:-1px}
	.cats{max-width:920px;margin:10px auto 0;display:flex;gap:8px;overflow-x:auto;padding-bottom:2px;scrollbar-width:none}
	.cats::-webkit-scrollbar{display:none}
	.chip{flex-shrink:0;padding:7px 14px;border-radius:999px;border:1px solid var(--border);background:var(--card);color:var(--text-faint);font-size:.82rem;cursor:pointer;white-space:nowrap}
	.chip.on{background:var(--accent);color:#fff;border-color:var(--accent)}
	main{max-width:920px;margin:0 auto;padding:16px}
	.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
	@media(min-width:720px){.grid{grid-template-columns:repeat(4,1fr)}}
	.card{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;display:flex;flex-direction:column}
	.card img{width:100%;aspect-ratio:1/1;object-fit:cover;background:#1c1c2a;display:block}
	.card-body{padding:10px 12px 12px;display:flex;flex-direction:column;gap:6px;flex:1}
	.card-name{font-size:.86rem;font-weight:600;line-height:1.25}
	.card-price{font-size:.84rem;color:var(--text-faint)}
	.wa-btn{margin-top:auto;display:flex;align-items:center;justify-content:center;gap:6px;background:var(--wa);color:#04140a;font-weight:700;border:none;border-radius:9px;padding:8px 10px;font-size:.8rem;text-decoration:none}
	.empty{text-align:center;color:var(--text-faint);padding:40px 20px}
	.more-btn{display:block;margin:18px auto 0;padding:10px 22px;border-radius:10px;border:1px solid var(--border);background:var(--card);color:var(--text);font-size:.85rem;cursor:pointer}
	footer{text-align:center;color:var(--text-faint);font-size:.75rem;padding:26px 16px 40px}
	footer a{color:var(--text-faint)}
</style>
</head>
<body>
<header>
	<div class="top-row">
		<?php if ( $logo_url ) : ?><img class="logo" src="<?php echo esc_url( $logo_url ); ?>" alt=""><?php endif; ?>
		<div class="biz-name"><?php echo esc_html( $nombre_negocio ); ?></div>
	</div>
	<div class="search"><input id="pSearch" type="text" placeholder="Buscar producto…" autocomplete="off"></div>
	<div class="cats" id="pCats"></div>
</header>
<main>
	<div class="grid" id="pGrid"></div>
	<div class="empty" id="pEmpty" style="display:none">No encontramos productos con esa búsqueda.</div>
	<button class="more-btn" id="pMore" style="display:none">Ver más</button>
</main>
<footer>Catálogo hecho con <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">Punto</a></footer>
<script>
var DATA = <?php echo wp_json_encode( $datos_js ); ?>;
(function(){
	var PAGE=8, shown=0, catActiva='', query='';
	// escapa texto para meterlo como HTML de forma segura, apoyándose en el propio navegador
	// (textContent -> innerHTML), sin depender de ninguna librería.
	function esc(s){ var d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }
	function norm(s){
		s=(s||'').toLowerCase().normalize('NFD');
		var out='';
		for(var i=0;i<s.length;i++){
			var c=s.charCodeAt(i);
			if(c<768||c>879) out+=s.charAt(i); // fuera de las marcas diacriticas combinables (tildes, etc.)
		}
		return out;
	}
	function filtrados(){
		return DATA.productos.filter(function(p){
			if(catActiva && p.cat!==catActiva) return false;
			if(query && norm(p.nombre).indexOf(norm(query))===-1) return false;
			return true;
		});
	}
	function fmtPrecio(p){
		if(!DATA.mostrarPrecio || p.precio==null) return 'Consultar precio';
		return '$'+p.precio.toFixed(2);
	}
	function waLink(p){
		var precioTxt = (DATA.mostrarPrecio && p.precio!=null) ? ' ($'+p.precio.toFixed(2)+')' : '';
		var msg = 'Vi esto en tu catálogo: '+p.nombre+precioTxt+'. ¿Tienen disponibilidad?';
		return 'https://wa.me/'+DATA.whatsapp+'?text='+encodeURIComponent(msg);
	}
	function cardHtml(p){
		var img = '<img src="'+esc(p.foto||'')+'" alt="" loading="lazy">';
		var boton = DATA.whatsapp ? '<a class="wa-btn" href="'+waLink(p)+'" target="_blank" rel="noopener">WhatsApp</a>' : '';
		return '<div class="card">'+img+'<div class="card-body"><div class="card-name">'+esc(p.nombre)+'</div><div class="card-price">'+esc(fmtPrecio(p))+'</div>'+boton+'</div></div>';
	}
	function render(reset){
		if(reset) shown=0;
		var list=filtrados();
		var grid=document.getElementById('pGrid'), empty=document.getElementById('pEmpty'), more=document.getElementById('pMore');
		if(reset) grid.innerHTML='';
		var next=list.slice(shown, shown+PAGE);
		next.forEach(function(p){ grid.insertAdjacentHTML('beforeend', cardHtml(p)); });
		shown+=next.length;
		empty.style.display = list.length===0 ? '' : 'none';
		more.style.display = shown<list.length ? '' : 'none';
	}
	function renderCats(){
		var wrap=document.getElementById('pCats');
		var html='<button class="chip'+(catActiva===''?' on':'')+'" data-c="">Todas</button>';
		DATA.categorias.forEach(function(c){ html+='<button class="chip'+(catActiva===c?' on':'')+'" data-c="'+esc(c)+'">'+esc(c)+'</button>'; });
		wrap.innerHTML=html;
	}
	document.getElementById('pSearch').addEventListener('input', function(e){ query=e.target.value; render(true); });
	document.getElementById('pCats').addEventListener('click', function(e){
		var b=e.target.closest('.chip'); if(!b) return;
		catActiva=b.dataset.c; renderCats(); render(true);
	});
	document.getElementById('pMore').addEventListener('click', function(){ render(false); });
	renderCats();
	render(true);
})();
</script>
<?php wp_footer(); ?>
</body>
</html>
