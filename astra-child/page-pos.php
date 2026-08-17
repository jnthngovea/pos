<?php
/**
 * Template Name: FrixPOS (App)
 *
 * FrixPOS — plantilla de página que sirve la app del POS completa directo desde WordPress,
 * sin el header/footer de Astra — la página se dibuja a sí misma de punta a punta, igual
 * que ya hace la portada (body.home) desde antes. Por eso NO se llama get_header()/
 * get_footer() aquí a propósito.
 *
 * v5 — motor de precios de modelo único (se eliminó "margen sobre costo": el bolívar sale
 * siempre a BCV) y monedas personalizadas: el negocio crea las que necesite, con su tasa
 * contra el dólar paralelo, y cada una trae su método de cobro en efectivo y su gaveta en el
 * arqueo del Cajón. Además: orden del inventario, grilla de hasta 6 columnas en escritorio,
 * acordeón y edición de egresos en Contabilidad.
 *
 * v4 — persistencia local que de verdad se nota (rehidratación de pantalla + sesión guardada),
 * comprobante con fecha/hora real y datos fiscales, cierre de turno unificado, gasto extra por
 * lote o por unidad, y borrado total de datos locales. El cliente de activación/sync contra
 * /wp-json/punto/v1/activar y /sync sigue igual desde v3 — ver el .md del tema.
 */
?><!DOCTYPE html>
<html lang="es">
<head>
<!-- FRIXPOS:HEAD-INICIO -->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0A0B10">
<title>FrixPOS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#0A0B10;--bg-elevated:#0D0F16;
    --glow-a:rgba(62,123,250,.24);--glow-b:rgba(155,92,246,.16);
    --surface:#15171F;--surface-2:#1C1F29;--surface-hover:#212430;
    --border:rgba(255,255,255,.07);--border-strong:rgba(255,255,255,.15);
    --text:#F2F4F8;--text-dim:#8D93A3;--text-faint:#565C6B;
    --accent-a:#3E7BFA;--accent-b:#9B5CF6;
    --gradient:linear-gradient(135deg,var(--accent-a),var(--accent-b));
    --green:#33D690;--red:#FF4D5E;--amber:#FFB443;
    --r-sm:10px;--r-md:14px;--r-lg:20px;--r-xl:26px;
    --shadow:0 1px 2px rgba(0,0,0,.5),0 20px 44px -14px rgba(0,0,0,.55);
    --font-d:'Space Grotesk',sans-serif;--font-b:'Manrope',sans-serif;
  }
  body.light{
    --bg:#F3F4F8;--bg-elevated:#FFFFFF;
    --glow-a:rgba(62,123,250,.07);--glow-b:rgba(155,92,246,.06);
    --surface:#FFFFFF;--surface-2:#EEF0F5;--surface-hover:#E4E7EF;
    --border:rgba(15,23,42,.08);--border-strong:rgba(15,23,42,.16);
    --text:#12141B;--text-dim:#5B6272;--text-faint:#9CA3B0;
    --shadow:0 1px 2px rgba(15,23,42,.06),0 16px 36px -16px rgba(15,23,42,.14);
  }
  html{font-size:15px}
  *{box-sizing:border-box}
  html,body{margin:0;height:100%}
  body{background:var(--bg);color:var(--text);font-family:var(--font-b);-webkit-font-smoothing:antialiased;min-height:100vh;overflow-x:hidden;transition:background .2s,color .2s}
  body::before{content:"";position:fixed;inset:0;z-index:0;pointer-events:none;
    background:radial-gradient(600px 440px at 90% -8%,var(--glow-a),transparent 60%),radial-gradient(520px 440px at 4% 6%,var(--glow-b),transparent 60%)}
  button{font-family:inherit;border:none;background:none;color:inherit;cursor:pointer}
  input,select{font-family:inherit}
  :focus-visible{outline:2px solid var(--accent-a);outline-offset:2px;border-radius:8px}
  ::-webkit-scrollbar{width:6px;height:6px}
  ::-webkit-scrollbar-thumb{background:var(--surface-2);border-radius:6px}

  /* shell */
  .shell{display:flex;min-height:100vh}
  .sidebar{width:72px;flex:0 0 72px;background:var(--bg-elevated);border-right:1px solid var(--border);display:flex;flex-direction:column;z-index:60}
  @media(max-width:767px){
    .sidebar{position:fixed;top:0;left:0;bottom:0;transform:translateX(-100%);transition:transform .35s cubic-bezier(.16,1,.3,1);box-shadow:20px 0 60px -20px rgba(0,0,0,.5);width:72px}
    .sidebar.open{transform:translateX(0)}
  }
  @media(min-width:768px){
    .sidebar{position:sticky;top:0;align-self:flex-start;height:100vh}
  }
  .sidebar-brand{display:flex;flex-direction:column;align-items:center;gap:6px;padding:16px 6px 12px;cursor:pointer}
  .mark{width:34px;height:34px;border-radius:10px;background:var(--gradient);background-position:center;background-size:cover;
    display:flex;align-items:center;justify-content:center;font-family:var(--font-d);font-weight:700;font-size:15px;color:#0A0B10;
    box-shadow:0 4px 14px -2px rgba(62,123,250,.55);flex:0 0 auto}
  .sidebar-brand .nm{font-family:var(--font-d);font-weight:700;font-size:8.5px;letter-spacing:.06em;text-transform:uppercase;color:var(--text-faint);text-align:center;line-height:1.1;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .nav{flex:1 1 auto;overflow-y:auto;overscroll-behavior:contain;padding:4px 6px;display:flex;flex-direction:column;gap:1px}
  .nav-item{display:flex;flex-direction:column;align-items:center;gap:4px;padding:9px 2px;border-radius:var(--r-sm);position:relative;color:var(--text-faint);transition:.15s}
  .nav-item svg{width:18px;height:18px}
  .nav-item .lbl{font-size:8.5px;font-weight:700;text-align:center;line-height:1.15}
  .nav-item:hover{background:var(--surface-2);color:var(--text-dim)}
  .nav-item.on{background:var(--surface-2);color:var(--text)}
  .nav-item.on::before{content:"";position:absolute;left:-6px;top:8px;bottom:8px;width:3px;border-radius:3px;background:var(--gradient)}
  .nav-item .ndot{position:absolute;top:3px;right:5px;min-width:14px;height:14px;padding:0 3px;border-radius:999px;background:var(--amber);color:#2b1600;font-size:8px;font-weight:800;display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 2px var(--bg-elevated)}
  .sidebar-foot{padding:10px 6px;border-top:1px solid var(--border);text-align:center}
  .sidebar-foot .out{font-size:8.5px;color:var(--red);font-weight:700}
  .nav-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:55;opacity:0;pointer-events:none;transition:opacity .3s}
  .nav-backdrop.show{opacity:1;pointer-events:auto}
  @media(min-width:768px){.nav-backdrop{display:none}}
  body.drawer-open{overflow:hidden}

  .workspace{flex:1 1 auto;min-width:0}
  .app{display:grid;grid-template-columns:minmax(0,1fr);min-height:100vh;min-height:100dvh}
  @media(min-width:960px){.app{grid-template-columns:minmax(0,1fr) 380px}}

  /* topbar */
  header.topbar{grid-column:1/-1;position:sticky;top:0;z-index:30;display:flex;align-items:center;gap:10px;padding:10px 16px;
    background:rgba(10,11,16,.72);backdrop-filter:blur(20px) saturate(140%);border-bottom:1px solid var(--border);
    /* FIX v24_1 — el sticky + backdrop-filter deja una banda en blanco arriba del encabezado en el
       navegador de Android cuando el documento se hace más corto de golpe (pasar de Pedidos, que es
       largo, a Por cobrar, que casi no tiene contenido). Forzar capa propia de composición evita el
       rastro de pintado. No cambia nada del diseño. */
    transform:translateZ(0);will-change:transform;-webkit-backface-visibility:hidden;backface-visibility:hidden}
  body.light header.topbar{background:rgba(243,244,248,.78)}
  .menu-btn{width:34px;height:34px;flex:0 0 auto;border-radius:9px;background:var(--surface);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-dim)}
  @media(min-width:768px){.menu-btn{display:none}}
  .tb-mark{width:28px;height:28px;border-radius:8px;font-size:13px;flex:0 0 auto}
  .section-title{font-family:var(--font-d);font-weight:700;font-size:1.1rem;letter-spacing:-.01em;white-space:nowrap}
  .topbar .search{flex:1 1 auto;min-width:0}
  .search input{width:100%;padding:8px 12px 8px 36px;border-radius:var(--r-md);border:1px solid var(--border);background:var(--surface);color:var(--text);font-size:.9rem;font-weight:500}
  .search input::placeholder{color:var(--text-faint)}
  .search input:focus{outline:none;border-color:var(--border-strong)}
  .search svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-faint);pointer-events:none;width:15px;height:15px}
  .search{position:relative}
  .status{display:flex;align-items:center;gap:14px;margin-left:auto;flex:0 0 auto}
  .status .live{display:flex;align-items:center;gap:6px;font-size:.8rem;color:var(--text-dim);font-weight:700}
  .status .dot{width:7px;height:7px;border-radius:50%;background:var(--green);box-shadow:0 0 0 3px rgba(51,214,144,.16)}
  .rates{display:none;gap:10px;font-size:.733rem;color:var(--text-faint);font-weight:600;font-variant-numeric:tabular-nums}
  @media(min-width:640px){.rates{display:flex}}

  /* main */
  main{padding:14px 16px 130px}
  .view{display:none}.view.on{display:block}
  .chips{display:flex;gap:7px;overflow-x:auto;padding-bottom:4px;margin-bottom:14px;scrollbar-width:none}
  .chips::-webkit-scrollbar{display:none}
  .chip{flex:0 0 auto;padding:8px 14px;border-radius:999px;border:1px solid var(--border);background:var(--surface);color:var(--text-dim);font-size:.867rem;font-weight:700;transition:.15s}
  .chip.on{background:var(--gradient);color:#fff;border-color:transparent}

  .grid{display:grid;gap:10px}
  /* v1.11 — "1" solo tiene sentido en teléfono (columnas por dispositivo, ver JS) */
  .grid[data-d="1"]{grid-template-columns:repeat(1,1fr)}
  .grid[data-d="1"] .card .nprow{padding:16px 18px 18px}
  .grid[data-d="1"] .card .nm{font-size:1.1rem}
  .grid[data-d="1"] .card .pr{font-size:1.2rem}
  .grid[data-d="1"] .card .avatar{font-size:32px}
  .grid[data-d="2"]{grid-template-columns:repeat(2,1fr)}
  .grid[data-d="3"]{grid-template-columns:repeat(3,1fr)}
  .grid[data-d="4"]{grid-template-columns:repeat(4,1fr)}
  /* v5 — 5 y 6 columnas para pantallas grandes: en una laptop caben muchos más productos y
     tener que bajar tanto en una bodega con 300 items es un fastidio. Las tarjetas se
     compactan (menos padding, letra más chica) para que quepan sin verse apretadas. */
  .grid[data-d="5"]{grid-template-columns:repeat(5,1fr)}
  .grid[data-d="6"]{grid-template-columns:repeat(6,1fr)}
  .grid[data-d="5"] .card .nprow,.grid[data-d="6"] .card .nprow{padding:8px 9px 10px}
  .grid[data-d="5"] .card .nm{font-size:.76rem}
  .grid[data-d="6"] .card .nm{font-size:.72rem}
  .grid[data-d="5"] .card .pr{font-size:.84rem}
  .grid[data-d="6"] .card .pr{font-size:.79rem}
  .grid[data-d="5"] .card .avatar,.grid[data-d="6"] .card .avatar{font-size:18px}
  /* en pantallas medianas y en el teléfono, 5 y 6 no caben: se degradan solas */
  @media(max-width:1100px){.grid[data-d="6"]{grid-template-columns:repeat(4,1fr)}.grid[data-d="5"]{grid-template-columns:repeat(4,1fr)}}
  @media(max-width:520px){.grid[data-d="4"],.grid[data-d="5"],.grid[data-d="6"]{grid-template-columns:repeat(3,1fr)}.grid[data-d="3"]{grid-template-columns:repeat(2,1fr)}}

  .card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r-md);text-align:left;
    display:flex;flex-direction:column;transition:transform .15s,border-color .15s,background .15s;overflow:hidden;
    animation:rise .45s cubic-bezier(.16,1,.3,1) backwards}
  .card:hover{border-color:var(--border-strong);background:var(--surface-hover);transform:translateY(-2px)}
  .card:active{transform:scale(.97)}
  .card .avatar{width:100%;aspect-ratio:1;display:flex;align-items:center;justify-content:center;font-family:var(--font-d);font-weight:700;position:relative}
  .card .avatar .initxt{position:relative;z-index:1}
  /* v47 — antes nombre y precio iban lado a lado en la misma fila, compitiendo por ancho: con
     tarjetas de 2-3 columnas en el teléfono, el nombre quedaba con ~50px y una sola línea con
     "…" cortaba casi todos los nombres reales ("Caraotas Rojas Mary" se veía "Cara…"). Ahora el
     nombre va en su propia fila (usa el ancho COMPLETO de la tarjeta) y el precio + la moneda
     secundaria van debajo — igual de apretado no importa cuán largo sea "$1.51 USD oferta".
     2 líneas con clamp cubre la gran mayoría de nombres completos; lo poquísimo que siga sin
     caber ahí sí corta, en vez de cortar SIEMPRE. */
  .card .nprow{display:flex;flex-direction:column;gap:4px;padding:10px 12px 12px}
  .card .nm{font-weight:700;line-height:1.28;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
  .card .pr-wrap{display:flex;align-items:baseline;flex-wrap:wrap;gap:2px 6px}
  .card .pr{font-family:var(--font-d);font-weight:700;font-variant-numeric:tabular-nums}
  .card .pr-sub{font-family:var(--font-b);font-weight:600;color:var(--text-faint);font-size:.72em}
  .grid[data-d="2"] .card .nprow{padding:14px 16px 16px}
  .grid[data-d="2"] .card .nm{font-size:1rem}
  .grid[data-d="2"] .card .pr{font-size:1.1rem}
  .grid[data-d="2"] .card .avatar{font-size:28px}
  .grid[data-d="3"] .card .nm{font-size:.867rem}
  .grid[data-d="3"] .card .pr{font-size:.967rem}
  .grid[data-d="3"] .card .avatar{font-size:22px}
  .grid[data-d="4"] .card .nprow{padding:8px 10px 10px}
  .grid[data-d="4"] .card .nm{font-size:.767rem}
  .grid[data-d="4"] .card .pr{font-size:.867rem}
  .grid[data-d="4"] .card .avatar{font-size:18px}
  @keyframes rise{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}

  /* placeholder */
  .placeholder{display:flex;flex-direction:column;align-items:center;text-align:center;gap:14px;
    padding:60px 24px;border:1px dashed var(--border-strong);border-radius:var(--r-xl);background:var(--surface);max-width:420px;margin:24px auto}
  .placeholder .picon{width:52px;height:52px;border-radius:14px;background:var(--surface-2);display:flex;align-items:center;justify-content:center;color:var(--text-dim)}
  .placeholder .picon svg{width:26px;height:26px}
  .placeholder h3{margin:0;font-family:var(--font-d);font-size:1.1rem;font-weight:700}
  .placeholder p{margin:0;font-size:.867rem;color:var(--text-dim);line-height:1.6}
  .tag-soon{display:inline-flex;padding:5px 12px;border-radius:999px;background:var(--surface-2);color:var(--amber);font-size:.7rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase}

  /* config */
  .cfg-block{background:var(--surface);border:1px solid var(--border);border-radius:var(--r-lg);padding:20px;margin-bottom:14px;max-width:520px}
  .cfg-block h4{margin:0 0 4px;font-family:var(--font-d);font-size:1rem;font-weight:700}
  .cfg-block .desc{font-size:.8rem;color:var(--text-faint);margin-bottom:14px;line-height:1.55}
  /* nota expandible "Ver más" de cada bloque de Configuración — sin JS, nativo con <details> */
  .cfg-block .note{margin:-8px 0 14px}
  .cfg-block .note.note-row{margin:10px 0 4px;padding-top:2px}
  .cfg-block .note summary{list-style:none;cursor:pointer;display:inline-flex;align-items:center;gap:4px;font-size:.767rem;font-weight:700;color:var(--accent-a)}
  .cfg-block .note summary::-webkit-details-marker{display:none}
  .cfg-block .note .n-open{display:none}
  .cfg-block .note[open] .n-closed{display:none}
  .cfg-block .note[open] .n-open{display:inline-flex;align-items:center;gap:4px}
  .cfg-block .note-body{margin-top:9px;padding-top:10px;border-top:1px dashed var(--border);font-size:.8rem;color:var(--text-faint);line-height:1.65}
  .cfg-input{width:100%;padding:10px 12px;border-radius:var(--r-md);background:var(--surface-2);border:1px solid var(--border);color:var(--text);font-size:.867rem;font-weight:600}
  .cfg-input::placeholder{color:var(--text-faint);font-weight:500}
  select.cfg-input{cursor:pointer}
  .cfg-file{display:flex;align-items:center;gap:12px;padding:10px 14px;border-radius:var(--r-md);background:var(--surface-2);border:1px solid var(--border);cursor:pointer;width:fit-content}
  .cfg-file .logo-preview{width:30px;height:30px;flex:0 0 30px;aspect-ratio:1;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:8px;font-size:12px}
  /* fotos insertadas por JS (miniatura de producto, tarjeta de venta, logo): sin esto, mantener
     presionado en el navegador del teléfono abre su propio visor de imagen encima de la app */
  .avatar img,.avatar-round img,.logo-preview img{-webkit-touch-callout:none;-webkit-user-select:none;user-select:none;-webkit-user-drag:none;pointer-events:none}
  .cfg-file-text{font-size:.833rem;font-weight:700;color:var(--text-dim)}
  .cfg-row+.cfg-row{margin-top:14px}
  .cfg-row label{display:block;font-size:.733rem;font-weight:700;color:var(--text-dim);margin-bottom:5px}
  .swatches{display:flex;gap:10px;flex-wrap:wrap}
  .swatch-wrap{display:flex;flex-direction:column;align-items:center}
  .swatch{width:40px;height:40px;border-radius:50%;border:2px solid transparent;position:relative;transition:.15s;flex:0 0 auto}
  .swatch.on{border-color:var(--text);transform:scale(1.08)}
  .swatch.on::after{content:"✓";position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;font-weight:800;text-shadow:0 1px 3px rgba(0,0,0,.4)}
  .swatch-name{font-size:.667rem;color:var(--text-faint);text-align:center;margin-top:5px;width:40px}
  .seg{display:flex;gap:5px;background:var(--surface-2);border-radius:999px;padding:3px}
  .seg button{flex:1;padding:8px 4px;border-radius:999px;font-size:.8rem;font-weight:800;color:var(--text-dim);letter-spacing:.01em;text-align:center;transition:.15s}
  .seg button.on{background:var(--gradient);color:#fff}
  .seg button:disabled{opacity:.4;cursor:not-allowed}
  .cur-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px solid var(--border)}
  /* separador fuerte entre grupos del formulario de producto — la línea fina de .cur-row se perdía
     y todos los campos parecían un solo bloque corrido (feedback de Jonathan, v22) */
  .pay-hint{font-size:.72rem;color:var(--text-faint);line-height:1.45;margin:8px 2px 0}
  .caja-cerrada{background:var(--surface);border:1px solid var(--amber);border-radius:var(--r-lg);padding:18px;display:flex;flex-direction:column;gap:6px;align-items:flex-start;margin-bottom:4px}
  .caja-cerrada b{font-size:1rem;color:var(--amber)}
  .caja-cerrada span{font-size:.82rem;color:var(--text-faint);line-height:1.5}
  .caja-cerrada button{margin-top:8px}
  .group-sep{margin:26px 0 0;padding-top:18px;border-top:2px solid var(--border-strong)}
  .group-sep>.nm{font-size:.95rem;font-weight:800;letter-spacing:-.01em}
  .group-sep>.sub{font-size:.78rem;color:var(--text-faint);margin-top:3px;line-height:1.5}
  .cur-row:last-child{border-bottom:0}
  .cur-row .nm{font-size:.867rem;font-weight:600}
  .cur-row .sub{font-size:.733rem;color:var(--text-faint);margin-top:2px}
  .cur-row input{width:100px;text-align:right}
  .pay-cfg{display:flex;gap:7px;flex-wrap:wrap}
  .pay-cfg button{flex:1 1 calc(50% - 4px);padding:10px 8px;border-radius:var(--r-md);border:1px solid var(--border);background:var(--surface);font-size:.8rem;font-weight:700;color:var(--text-dim);transition:.15s}
  .pay-cfg button.on{border-color:var(--accent-a);color:var(--text);background:var(--surface-2);box-shadow:0 0 0 1px var(--accent-a) inset}

  /* Configuración: grupos colapsables (v16) — cada hero-label + sus cfg-block quedan dentro de
     un <details> con nota corta; solo se ve el título + de qué trata hasta que se toca. */
  .cfg-group{max-width:520px;margin-bottom:18px;border:1px solid var(--border);border-radius:var(--r-lg);background:var(--surface);overflow:hidden}
  .cfg-group>summary{list-style:none;cursor:pointer;padding:16px 18px;display:flex;align-items:center;gap:10px}
  .cfg-group>summary::-webkit-details-marker{display:none}
  .cfg-group>summary .cg-tt{flex:1 1 auto}
  .cfg-group>summary .cg-name{font-family:var(--font-d);font-weight:700;font-size:.967rem}
  .cfg-group>summary .cg-note{font-size:.767rem;color:var(--text-faint);margin-top:2px}
  .cfg-group>summary .chev{color:var(--text-faint);transition:transform .2s;flex:0 0 auto}
  .cfg-group[open]>summary .chev{transform:rotate(90deg)}
  .cfg-group>summary:hover .cg-name{color:var(--accent-a)}
  .cfg-group-body{padding:4px 18px 18px}
  .cfg-group .cfg-block{max-width:none}
  .cfg-group .cfg-block:last-of-type{margin-bottom:16px}
  .cfg-save-row{display:flex;align-items:center;gap:10px;padding-top:2px}
  .cfg-save-row .saved-tag{font-size:.767rem;font-weight:700;color:var(--green);opacity:0;transition:opacity .25s}
  .cfg-save-row .saved-tag.show{opacity:1}
  .cfg-save-btn{width:100%}

  /* Stock: botón de inventario bajo (v16) */
  .lowstock-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:999px;background:var(--red);color:#fff;font-size:.8rem;font-weight:800;margin-top:10px;border:0}
  .lowstock-btn:active{transform:scale(.97)}
  .ndot{background:var(--red)}

  /* recibo imprimible (v16): oculto en pantalla, solo visible en @media print */
  #printArea{display:none}
  @media print{
    body *{visibility:hidden}
    #printArea,#printArea *{visibility:visible}
    #printArea{display:block;position:absolute;top:0;left:0;width:100%;padding:24px;font-family:var(--font-b);color:#000;white-space:pre-wrap;font-size:13px;line-height:1.6}
  }

  /* pricing mode info */
  .example{padding:12px 14px;border-radius:var(--r-md);background:var(--surface-2);font-size:.8rem;color:var(--text-dim);font-variant-numeric:tabular-nums;line-height:1.85}

  /* cart */
  .mbar{position:fixed;left:16px;right:16px;bottom:calc(72px + env(safe-area-inset-bottom));z-index:45;display:flex;align-items:center;gap:12px;padding:14px 20px;border-radius:999px;
    background:var(--gradient);box-shadow:0 14px 34px -8px rgba(62,123,250,.55);transform:translateY(120%);transition:transform .3s cubic-bezier(.16,1,.3,1)}
  .mbar.show{transform:translateY(0)}
  .mbar .n{width:22px;height:22px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-size:.767rem;font-weight:800;flex:0 0 auto}
  .mbar .lbl{font-size:.867rem;font-weight:700;color:#0A0B10}
  .mbar .amt{margin-left:auto;font-family:var(--font-d);font-weight:700;font-size:1rem;color:#0A0B10;font-variant-numeric:tabular-nums}
  @media(min-width:960px){.mbar{display:none}}
  body.hide-cart .mbar,body.hide-cart aside.cart{display:none!important}

  .backdrop{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:44;opacity:0;pointer-events:none;transition:opacity .3s}
  .backdrop.show{opacity:1;pointer-events:auto}
  @media(min-width:960px){.backdrop{display:none}}

  aside.cart{position:fixed;left:0;right:0;bottom:0;z-index:50;background:var(--bg-elevated);border-radius:24px 24px 0 0;border-top:1px solid var(--border);
    max-height:88vh;display:flex;flex-direction:column;transform:translateY(100%);transition:transform .4s cubic-bezier(.16,1,.3,1);box-shadow:0 -24px 60px -20px rgba(0,0,0,.5)}
  aside.cart.open{transform:translateY(0)}
  @media(min-width:960px){aside.cart{position:sticky;top:0;transform:none;border-radius:0;border-top:0;border-left:1px solid var(--border);max-height:none;height:100vh}}
  .cart-head{display:flex;align-items:center;gap:12px;padding:14px 18px 10px;flex:0 0 auto;position:relative}
  .handle{position:absolute;top:9px;left:50%;transform:translateX(-50%);width:36px;height:4px;border-radius:4px;background:var(--border-strong)}
  @media(min-width:960px){.handle{display:none}}
  .cart-title{font-family:var(--font-d);font-weight:700;font-size:1.067rem;letter-spacing:-.01em}
  .cart-title span{color:var(--text-dim);font-weight:600;font-size:.9rem;margin-left:4px}
  .icon-btn{margin-left:auto;width:30px;height:30px;border-radius:50%;background:var(--surface-2);display:flex;align-items:center;justify-content:center;color:var(--text-dim)}
  @media(min-width:960px){.cart-head .icon-btn{display:none}}
  .cart-items{flex:0 0 auto;max-height:44vh;overflow-y:auto;padding:4px 18px;display:flex;flex-direction:column;gap:8px}
  .cart-empty{padding:36px 10px;text-align:center;color:var(--text-faint);font-size:.867rem;line-height:1.6}
  .cart-empty b{display:block;color:var(--text-dim);font-size:.933rem;margin-bottom:4px;font-weight:700}
  .item{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--border)}
  .item:last-child{border-bottom:0}
  .item .inm{flex:1 1 auto;font-size:.867rem;font-weight:600;line-height:1.3}
  .item .isub{font-size:.733rem;color:var(--text-faint);margin-top:2px;font-variant-numeric:tabular-nums}
  .stepper{display:flex;align-items:center;gap:7px;background:var(--surface-2);border-radius:999px;padding:3px}
  .stepper button{width:22px;height:22px;border-radius:50%;color:var(--text-dim);font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center}
  .stepper button:hover{background:var(--surface-hover)}
  .stepper .q{min-width:14px;text-align:center;font-size:.833rem;font-weight:700;font-variant-numeric:tabular-nums}
  .iprice{width:60px;text-align:right;font-family:var(--font-d);font-weight:700;font-size:.867rem;font-variant-numeric:tabular-nums}
  .cart-summary{flex:1 1 auto;min-height:0;overflow-y:auto;padding:14px 18px calc(20px + env(safe-area-inset-bottom));border-top:1px solid var(--border);background:var(--bg-elevated)}
  .curr-toggle{display:flex;gap:5px;background:var(--surface-2);border-radius:999px;padding:3px;margin-bottom:14px}
  .curr-toggle button{flex:1;padding:7px 0;border-radius:999px;font-size:.8rem;font-weight:800;color:var(--text-dim);letter-spacing:.02em}
  .curr-toggle button.on{background:var(--gradient);color:#fff}
  .hero{text-align:center;margin-bottom:14px}
  .hero-label{font-size:.733rem;color:var(--text-faint);font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px}
  .hero-value{font-family:var(--font-d);font-weight:700;font-size:2.4rem;letter-spacing:-.02em;font-variant-numeric:tabular-nums;
    background:var(--gradient);-webkit-background-clip:text;background-clip:text;color:transparent}
  .hero-sub{font-size:.8rem;color:var(--text-faint);margin-top:4px;font-variant-numeric:tabular-nums}
  .pay-methods{display:grid;grid-template-columns:repeat(2,1fr);gap:7px;margin-bottom:10px}
  .pm-btn{padding:10px 8px;border-radius:var(--r-md);border:1px solid var(--border);background:var(--surface);font-size:.8rem;font-weight:700;color:var(--text-dim);transition:.15s}
  .pm-btn.on{border-color:var(--accent-a);color:var(--text);background:var(--surface-2);box-shadow:0 0 0 1px var(--accent-a) inset}
  .pay-amount-row{display:flex;gap:8px;margin-bottom:10px}
  .pay-amount-row input{flex:1 1 auto;min-width:0;padding:11px 12px;border-radius:var(--r-md);border:1px solid var(--border);background:var(--surface-2);color:var(--text);font-size:1.05rem;font-weight:700;text-align:right;font-variant-numeric:tabular-nums}
  .pay-exact-btn{flex:0 0 auto;padding:0 16px;border-radius:var(--r-md);background:var(--surface-2);border:1px solid var(--border);color:var(--text-dim);font-size:.8rem;font-weight:800;white-space:nowrap}
  .pay-add-btn{width:100%;padding:11px;border-radius:var(--r-md);border:1.5px dashed var(--border-strong);color:var(--accent-a);font-size:.833rem;font-weight:700;margin-bottom:10px}
  .pay-list-item{display:flex;align-items:center;gap:8px;padding:8px 11px;background:var(--surface-2);border-radius:var(--r-md);margin-bottom:6px;font-size:.8rem;font-weight:600}
  .pay-list-item span{flex:1 1 auto;min-width:0}
  .pay-list-item b{font-variant-numeric:tabular-nums}
  .pay-list-item button{flex:0 0 auto;color:var(--text-faint);font-size:15px;font-weight:800}
  .checkout{width:100%;padding:14px;border-radius:var(--r-lg);background:var(--gradient);color:#fff;font-family:var(--font-d);font-weight:700;font-size:1rem;letter-spacing:-.01em;
    box-shadow:0 14px 34px -10px rgba(62,123,250,.55);transition:.2s;margin-top:14px}
  .checkout:disabled{background:var(--surface-2);color:var(--text-faint);box-shadow:none;cursor:not-allowed}
  .checkout.success{background:linear-gradient(135deg,var(--green),#1FAE73)}

  /* clientes / por cobrar / pedidos */
  .view-toolbar{display:flex;gap:10px;align-items:center;margin-bottom:14px;flex-wrap:wrap;max-width:560px}
  .view-toolbar .search{flex:1 1 220px;min-width:180px}
  .btn-primary{padding:10px 16px;border-radius:999px;background:var(--gradient);color:#fff;font-weight:800;font-size:.8rem;white-space:nowrap;flex:0 0 auto}
  .btn-secondary{padding:10px 16px;border-radius:999px;background:var(--surface);border:1px solid var(--border);color:var(--text-dim);font-weight:700;font-size:.8rem;white-space:nowrap;flex:0 0 auto}
  .btn-secondary:hover{border-color:var(--border-strong);color:var(--text)}
  .form-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:4px}
  .client-card{display:block;width:100%;text-align:left;background:var(--surface);border:1px solid var(--border);border-radius:var(--r-lg);padding:14px 16px;margin-bottom:10px;max-width:560px;transition:.15s;animation:rise .4s cubic-bezier(.16,1,.3,1) backwards}
  .client-card:hover{border-color:var(--border-strong);background:var(--surface-hover)}
  .cc-row{display:flex;align-items:center;gap:12px;width:100%;text-align:left}
  .cc-mid{flex:1 1 auto;min-width:0}
  .cc-name{font-weight:700;font-size:.9rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .cc-sub{font-size:.767rem;color:var(--text-faint);margin-top:1px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .cc-saldo{flex:0 0 auto;text-align:right;font-size:.7rem;color:var(--text-faint);font-weight:700;line-height:1.5}
  /* v1.20 — en pantalla angosta, un nombre/etiqueta al lado de un monto en 4 monedas no entra:
     como .cc-saldo no se encogía (flex:0 0 auto), le quitaba casi todo el ancho a .cc-mid y
     el nombre se veía cortado a un pedazo de letra suelta en vez del texto completo. Con
     flex-wrap el monto (que ahora pide el 100% del ancho de fila) baja solo a su propia línea
     — el avatar y el nombre, cuando los hay, se quedan juntos arriba en vez de apilarse todos. */
  @media(max-width:520px){
    .cc-row{flex-wrap:wrap}
    .cc-mid{min-width:140px}
    .cc-name,.cc-sub{white-space:normal;overflow:visible;text-overflow:clip}
    .cc-saldo{flex:1 1 100%;text-align:left;margin-top:6px}
  }
  /* v36 — acciones del catálogo. En pantalla ancha van a la derecha, en la misma línea; en
     teléfono bajan a una fila propia y se reparten el ancho, para que el nombre del producto
     no quede aplastado a "Ha…" por dos botones que no se encogen. */
  .cc-actions{flex:0 0 auto;display:flex;gap:6px;margin-left:4px}
  @media(max-width:600px){
    .cc-row-prod{flex-wrap:wrap}
    .cc-row-prod .cc-actions{flex:1 1 100%;margin-left:0;margin-top:10px}
    .cc-row-prod .cc-actions button{flex:1 1 0}
  }
  .cc-saldo b{display:block;font-family:var(--font-d);font-size:.9rem;color:var(--text);font-weight:700}
  .cc-saldo.ok{color:var(--green);font-weight:800}
  .sem-row{display:flex;gap:10px;align-items:flex-start;padding:10px 0;border-bottom:1px solid var(--border)}
  .sem-row:last-child{border-bottom:0}
  .sem-dot{flex:0 0 auto;width:10px;height:10px;border-radius:50%;margin-top:5px}
  .sem-dot.ok{background:var(--green);box-shadow:0 0 0 3px rgba(51,214,144,.16)}
  .sem-dot.amber{background:var(--amber);box-shadow:0 0 0 3px rgba(255,180,67,.16)}
  .sem-dot.red{background:var(--red);box-shadow:0 0 0 3px rgba(255,77,94,.16)}
  .sem-t{font-weight:800;font-size:.86rem}
  .sem-s{font-size:.76rem;color:var(--text-faint);line-height:1.5;margin-top:2px}
  .chev{flex:0 0 auto;color:var(--text-faint);transition:transform .2s}
  .date-badge{width:38px;height:38px;border-radius:10px;background:var(--surface-2);display:flex;flex-direction:column;align-items:center;justify-content:center;flex:0 0 auto}
  .date-badge span{font-family:var(--font-d);font-weight:700;font-size:.9rem;line-height:1}
  .date-badge small{font-size:.6rem;color:var(--text-faint);text-transform:uppercase;line-height:1}
  .avatar-round{width:38px;height:38px;border-radius:50%;background:var(--gradient);color:#fff;display:flex;align-items:center;justify-content:center;font-family:var(--font-d);font-weight:700;font-size:.9rem;flex:0 0 auto}
  .detail-block{padding-top:10px;margin-top:10px;border-top:1px dashed var(--border);width:100%}
  .order-row{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:6px 0;font-size:.8rem;color:var(--text-dim)}
  .debt-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 0;border-top:1px solid var(--border);flex-wrap:wrap}
  .or-top{font-size:.833rem;font-weight:600;color:var(--text)}
  .or-sub{font-size:.733rem;color:var(--text-faint);margin-top:2px}
  .cc-row-btn{display:block;width:100%;text-align:left}
  .date-sep{font-family:var(--font-d);font-weight:700;font-size:.7rem;letter-spacing:.06em;text-transform:uppercase;color:var(--text-faint);margin:18px 2px 8px}
  .date-sep:first-child{margin-top:0}
  .status-chip{display:inline-flex;padding:2px 8px;border-radius:999px;font-size:.633rem;font-weight:800;letter-spacing:.03em;text-transform:uppercase;margin-bottom:2px}
  .status-chip.pagado{background:rgba(51,214,144,.14);color:var(--green)}
  .status-chip.fiado{background:rgba(255,180,67,.16);color:var(--amber)}
  .pc-summary-box{max-width:560px;margin-bottom:16px}
  .pc-total{font-family:var(--font-d);font-weight:700;font-size:1.7rem}
  .pc-total.ok{color:var(--green)}
  .pc-sub{font-size:.8rem;color:var(--text-dim);margin-top:2px}
  .client-pick{display:flex;flex-direction:column;gap:6px;max-height:150px;overflow-y:auto;margin-bottom:10px}
  .client-pick button{display:flex;align-items:center;gap:8px;padding:9px 11px;border-radius:var(--r-md);background:var(--surface);border:1px solid var(--border);text-align:left;font-size:.8rem;font-weight:600;color:var(--text)}
  .client-pick button:hover{background:var(--surface-hover)}
  .client-pick button.new{color:var(--accent-a)}
  .client-pick button span{color:var(--text-faint);font-weight:500;margin-left:auto}
  .sel-chip{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:var(--surface-2);border:1px solid var(--border-strong);font-size:.8rem;font-weight:700;margin-bottom:10px}
  .sel-chip button{color:var(--text-faint);font-weight:800;font-size:.75rem}

  /* pagos mixtos */
  .paymix-row{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:7px 0;border-bottom:1px solid var(--border)}
  .paymix-row:last-child{border-bottom:0}
  .paymix-row .pm-label{font-size:.8rem;font-weight:600;flex:1 1 auto;min-width:0}
  .pm-input-wrap{display:flex;align-items:center;gap:6px;flex:0 0 auto}
  .pm-input-wrap input{width:88px;text-align:right;padding:8px 9px}
  .pm-input-wrap .pm-unit{font-size:.7rem;font-weight:800;color:var(--text-faint);width:30px}
  .paymix-summary{margin-top:10px;padding:10px 12px;border-radius:var(--r-md);background:var(--surface-2);font-size:.8rem;font-weight:700;color:var(--text-dim);text-align:center}
  .paymix-summary.falta{color:var(--amber);background:rgba(255,180,67,.12)}
  .paymix-summary.cambio{color:var(--accent-a);background:rgba(62,123,250,.12)}
  .paymix-summary.ok{color:var(--green);background:rgba(51,214,144,.12)}
  .abono-wrap{margin-top:2px}
  .abono-form{margin-top:12px;padding-top:12px;border-top:1px dashed var(--border)}
  .abono-nota{margin-top:6px;padding:7px 10px;background:var(--surface-2);border-left:3px solid var(--accent-a);border-radius:8px;font-size:.72rem;line-height:1.5;color:var(--text-dim)}
  .ab-seg button{flex:1 1 0}

  /* confirmación */
  /* z-index por encima de .auth-gate (200): askConfirm() también se usa DESDE la pantalla de
     identidad (ej. "Empezar de cero" al restaurar). Con z-index:80 el diálogo se dibujaba
     detrás del gate, que tiene fondo opaco — el botón parecía no hacer nada. */
  .confirm-overlay{position:fixed;inset:0;z-index:250;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;pointer-events:none;transition:opacity .2s}
  .confirm-overlay.show{opacity:1;pointer-events:auto}
  /* bloque de lo que desbloquea el código — se usa en Activación y en los avisos de Informes/Contabilidad */
  .premium-box{border:1px solid var(--accent-a);border-radius:14px;padding:14px;
    background:linear-gradient(135deg,rgba(62,123,250,.10),rgba(155,92,246,.10))}
  .premium-head{margin-bottom:10px}
  .premium-tag{display:inline-block;font-size:.7rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;
    color:#fff;background:linear-gradient(135deg,var(--accent-a),var(--accent-b));padding:4px 10px;border-radius:999px}
  .premium-list>div{position:relative;padding-left:18px;font-size:.84rem;line-height:1.5;margin-bottom:5px}
  .premium-list>div::before{content:"";position:absolute;left:2px;top:.55em;width:6px;height:6px;border-radius:50%;
    background:linear-gradient(135deg,var(--accent-a),var(--accent-b))}
  .premium-cta{display:block;width:100%;text-align:center;margin-top:10px;padding:11px 14px;border-radius:12px;
    font-weight:700;font-size:.85rem;color:#fff;text-decoration:none;
    background:linear-gradient(135deg,var(--accent-a),var(--accent-b))}
  /* variante compacta, para no repetir la caja completa dos veces en la misma pantalla */
  .premium-nota{margin-top:10px;padding-left:12px;border-left:3px solid var(--accent-a)}
  .premium-link{display:inline-block;margin-top:8px;font-size:.82rem;font-weight:700;
    color:var(--accent-a);text-decoration:none}
  .confirm-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.6)}
  .confirm-box{position:relative;background:var(--bg-elevated);border:1px solid var(--border-strong);border-radius:var(--r-lg);padding:22px;max-width:340px;width:100%;box-shadow:var(--shadow);text-align:center}
  .confirm-box p{margin:0 0 18px;font-size:.9rem;font-weight:600;line-height:1.5;color:var(--text)}
  .confirm-box .form-actions{justify-content:center}
  #cropStage{position:relative;width:280px;height:280px;max-width:100%;margin:0 auto;border-radius:12px;overflow:hidden;background:#000;touch-action:none;cursor:grab;user-select:none}
  #cropStage:active{cursor:grabbing}
  #cropImg{position:absolute;left:0;top:0;transform-origin:0 0;pointer-events:none;-webkit-user-drag:none}
  #cropMask{position:absolute;inset:0;box-shadow:inset 0 0 0 2px rgba(255,255,255,.5);border-radius:12px;pointer-events:none}

  /* informes — chips de orden secundarios + gráfica de tendencia */
  .chip-sm{padding:6px 11px;font-size:.72rem}
  .inf-legend{display:flex;gap:14px;font-size:.7rem;color:var(--text-dim);font-weight:700;margin-bottom:6px}
  .inf-legend b{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:5px}
  .inf-chart-wrap{overflow-x:auto}
  .inf-chart-wrap svg{display:block}
  .inf-line-ventas{fill:none;stroke:var(--accent-a);stroke-width:2}
  .inf-line-ganancia{fill:none;stroke:var(--green);stroke-width:2}
  .inf-area-ganancia{fill:var(--green);opacity:.16}
  .inf-axis-label{font-size:9px;fill:var(--text-faint)}
  /* v50 — Tendencia pasó de línea a barras (pedido de Jonathan), con barra "período anterior" en
     gris apagado y "período actual" en el azul de siempre, para comparar de un vistazo. */
  .inf-bar-cur{fill:var(--accent-a)}
  .inf-bar-prev{fill:var(--text-faint);opacity:.55}

  /* identidad — pantalla de bienvenida / iniciar sesión / crear cuenta (v15) */
  .auth-gate{position:fixed;inset:0;z-index:200;background:var(--bg);display:flex;align-items:center;justify-content:center;padding:24px;overflow-y:auto}
  .auth-gate::before{content:"";position:fixed;inset:0;z-index:0;pointer-events:none;
    background:radial-gradient(600px 440px at 90% -8%,var(--glow-a),transparent 60%),radial-gradient(520px 440px at 4% 6%,var(--glow-b),transparent 60%)}
  /* v5 — acordeón de Contabilidad: mismo gesto que los grupos de Configuración. Antes las
     listas largas (Salud fiscal, ¿Dónde está la plata?) se abrían de golpe y la sección
     quedaba interminable en un teléfono. */
  details.cfg-acc>summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:10px}
  details.cfg-acc>summary::-webkit-details-marker{display:none}
  details.cfg-acc>summary h4{margin:0}
  details.cfg-acc .acc-chev{color:var(--text-faint);font-size:1.3rem;line-height:1;transition:transform .18s}
  details.cfg-acc[open] .acc-chev{transform:rotate(90deg)}
  .auth-gate.hide{display:none}
  /* v4 — mientras initState() lee IndexedDB no sabemos todavía si hay sesión guardada. El fondo
     del gate se queda tapando la app (para no mostrarla a quien no ha entrado) pero su contenido
     va invisible, así quien SÍ tenía sesión no ve un parpadeo de la pantalla de login. */
  .auth-gate.booting > *{visibility:hidden}
  .auth-card{position:relative;z-index:1;width:100%;max-width:380px;margin:auto}
  .auth-brand{display:flex;align-items:center;gap:10px;margin-bottom:30px}
  .auth-brand .mark{width:38px;height:38px;border-radius:11px;font-size:1.1rem}
  .auth-brand .nm{font-family:var(--font-d);font-weight:700;font-size:1.267rem}
  .auth-h1{font-family:var(--font-d);font-weight:700;font-size:1.6rem;letter-spacing:-.02em;margin-bottom:6px}
  .auth-sub{font-size:.867rem;color:var(--text-dim);line-height:1.55;margin-bottom:26px}
  .auth-actions{display:flex;flex-direction:column;gap:10px;margin-top:8px}
  .auth-actions .btn-primary,.auth-actions .btn-secondary,.auth-card>.btn-primary{width:100%;text-align:center;padding:13px 16px;font-size:.867rem;justify-content:center;display:flex}
  .auth-back{display:inline-flex;align-items:center;gap:6px;color:var(--text-dim);font-size:.8rem;font-weight:700;margin-bottom:18px}
  .auth-back svg{width:14px;height:14px}
  .auth-foot{margin-top:18px;text-align:center;font-size:.8rem;color:var(--text-faint)}
  .auth-foot button,.auth-foot a{color:var(--accent-a);font-weight:700}
  /* los enlaces de ayuda (activar código / soporte) no tenían estilo propio: salían del color
     por defecto del navegador, que en tema oscuro se lee casi negro sobre negro. */
  .desc a{color:var(--accent-a);font-weight:700;text-decoration:underline}
  .auth-steps{display:flex;gap:5px;margin-bottom:22px}
  .auth-steps span{height:3px;flex:1;border-radius:3px;background:var(--surface-2)}
  .auth-steps span.on{background:var(--gradient)}
  .auth-err{font-size:.8rem;color:var(--red);margin:-10px 0 14px;display:none}
  .auth-err.show{display:block}
  .auth-pass-wrap{position:relative}
  .auth-pass-wrap .cfg-input{padding-right:44px}
  .auth-pass-toggle{position:absolute;right:6px;top:50%;transform:translateY(-50%);width:32px;height:32px;display:flex;align-items:center;justify-content:center;color:var(--text-dim);background:none;border:none}
  .auth-pass-toggle svg{width:19px;height:19px}
  .cm-resultados{margin:-6px 0 12px;border:1px solid var(--border);border-radius:var(--r-md);background:var(--surface-2);max-height:220px;overflow-y:auto}
  .cm-resultados button{display:flex;align-items:center;gap:10px;width:100%;padding:8px 10px;text-align:left;background:none;border:none;border-bottom:1px solid var(--border)}
  .cm-resultados button:last-child{border-bottom:none}
  .cm-resultados img{width:30px;height:30px;border-radius:6px;object-fit:cover;flex-shrink:0}
  .cm-resultados .ph{width:30px;height:30px;border-radius:6px;background:var(--surface-3,var(--border));flex-shrink:0}
  .cm-resultados .nm{font-size:.8rem;font-weight:700}
  .cm-resultados .sub{font-size:.72rem;color:var(--text-dim)}

  /* guía paso a paso — cerrable, aparece tras crear cuenta (v15) */
  .tour-overlay{position:fixed;inset:0;z-index:90;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;pointer-events:none;transition:opacity .2s}
  .tour-overlay.show{opacity:1;pointer-events:auto}

  /* ===== Guía guiada (coach marks) — el recuadro que señala UN elemento a la vez =====
     El "apagado" del resto NO se hace con blur: se hace con una sombra gigante alrededor del
     recuadro (box-shadow de 9999px). Es una sola capa que se pinta sin filtros, mientras que
     un blur real con hueco necesita backdrop-filter + máscara — y este proyecto ya tiene
     historial de que backdrop-filter deja rastros de pintado en el WebView de Android
     (ver el fix del topbar). Se ve igual de claro y no arriesga el teléfono de nadie. */
  .coach-hole{position:absolute;z-index:210;border-radius:12px;pointer-events:none;
    box-shadow:0 0 0 9999px rgba(0,0,0,.72), 0 0 0 3px var(--accent-a), 0 0 22px 4px rgba(62,123,250,.55);
    transition:top .25s ease,left .25s ease,width .25s ease,height .25s ease}
  .coach-card{position:absolute;z-index:220;width:min(320px,calc(100vw - 32px));
    background:var(--bg-elevated);border:1px solid var(--border);border-radius:16px;padding:16px;
    box-shadow:0 20px 50px rgba(0,0,0,.5)}
  .coach-card h4{font-family:var(--font-d);font-size:1rem;font-weight:800;margin:0 0 6px}
  .coach-card p{font-size:.85rem;line-height:1.55;color:var(--text-dim);margin:0 0 14px}
  .coach-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
  .coach-count{font-size:.72rem;font-weight:700;letter-spacing:.04em;color:var(--text-faint)}
  .coach-skip{font-size:.75rem;font-weight:700;color:var(--text-faint);background:none;padding:4px 2px}
  .coach-actions{display:flex;gap:8px}
  .coach-actions button{flex:1;padding:10px 12px;border-radius:11px;font-weight:700;font-size:.82rem}
  .coach-blocker{position:fixed;inset:0;z-index:205}
  .tour-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.6)}
  .tour-box{position:relative;background:var(--bg-elevated);border:1px solid var(--border-strong);border-radius:var(--r-lg);padding:24px;max-width:360px;width:100%;box-shadow:var(--shadow)}
  .tour-close{position:absolute;top:14px;right:14px;width:28px;height:28px;border-radius:50%;background:var(--surface-2);display:flex;align-items:center;justify-content:center;color:var(--text-dim);font-size:13px}
  .tour-dots{display:flex;gap:5px;margin-bottom:18px}
  .tour-dots span{height:5px;width:5px;border-radius:50%;background:var(--surface-2)}
  .tour-dots span.on{width:16px;border-radius:3px;background:var(--gradient)}
  .tour-icon{width:44px;height:44px;border-radius:13px;background:var(--surface-2);display:flex;align-items:center;justify-content:center;margin-bottom:14px;font-size:1.3rem}
  .tour-box h3{font-family:var(--font-d);font-weight:700;font-size:1.15rem;margin:0 0 8px;letter-spacing:-.01em}
  .tour-box p{margin:0 0 20px;font-size:.867rem;color:var(--text-dim);line-height:1.6}
  .tour-actions{display:flex;gap:8px;align-items:center}
  .tour-skip{color:var(--text-faint);font-size:.8rem;font-weight:700;margin-right:auto}
</style>
<!-- FRIXPOS:HEAD-FIN -->
<link rel="manifest" href="/manifest.json">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="FrixPOS">
<link rel="apple-touch-icon" href="<?php echo esc_url( get_stylesheet_directory_uri() . '/pos/icon-192.png' ); ?>">
</head>
<body>
<!-- FRIXPOS:BODY-INICIO -->

<div class="auth-gate booting" id="authGate">
  <div class="auth-card" id="authCard"></div>
</div>

<div class="tour-overlay" id="tourOverlay">
  <div class="tour-backdrop"></div>
  <div class="tour-box" id="tourBox"></div>
</div>

<div id="coachLayer" style="display:none">
  <div class="coach-blocker" id="coachBlocker"></div>
  <div class="coach-hole" id="coachHole"></div>
  <div class="coach-card" id="coachCard"></div>
</div>

<div class="nav-backdrop" id="navBackdrop"></div>
<div class="shell">
  <nav class="sidebar" id="sidebar">
    <div class="sidebar-brand" id="sidebarBrand"><div class="mark brand-mark" id="sidebarMark">F</div><div class="nm brand-name">FrixPOS</div></div>
    <div class="nav" id="nav"></div>
    <!-- v1.14 — respaldo a un toque desde el propio menú, sin entrar a ningún lado. Pedido de
         Jonathan: era lo más importante de la app y estaba escondido en Configuración. -->
    <button class="nav-item" id="navRespaldoBtn" style="width:100%">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.6-6.4"/><path d="M21 3v6h-6"/></svg>
      <div class="lbl">Respaldar</div>
    </button>
    <div class="desc" id="navRespaldoAviso" style="display:none;padding:0 8px 6px;font-size:9px;text-align:center;line-height:1.3"></div>
    <div class="sidebar-foot"><button class="out">Salir</button></div>
  </nav>
  <div class="workspace">
    <div class="app">
      <header class="topbar">
        <button class="menu-btn" id="menuBtn" aria-label="Menú"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button>
        <div class="tb-mark mark brand-mark" id="tbMark" style="cursor:pointer">F</div>
        <div class="section-title" id="sectionTitle" style="display:none">Configuración</div>
        <div class="search" id="tbSearchWrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path></svg>
          <input id="search" type="text" placeholder="Buscar producto…" autocomplete="off">
        </div>
        <div class="status"><div class="rates" id="rates"></div><div class="live"><span class="dot"></span>En línea</div></div>
      </header>

      <main>
        <div class="view on" id="view-venta">
          <div class="chips" id="chips"></div>
          <div class="grid" id="grid" data-d="5"></div>
        </div>
        <div class="view" id="view-contabilidad">
          <div id="contabilidadBody" style="max-width:640px"></div>
        </div>
        <div class="view" id="view-placeholder"></div>
        <div class="view" id="view-clientes">
          <div class="view-toolbar">
            <div class="search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path></svg><input id="clientSearch" type="text" placeholder="Buscar cliente…" autocomplete="off"></div>
            <button class="btn-primary" id="newClientBtn">+ Nuevo cliente</button>
          </div>
          <div class="cfg-block" id="newClientForm" style="display:none;max-width:420px">
            <h4>Nuevo cliente</h4>
            <div class="cfg-row"><label>Nombre</label><input class="cfg-input" id="newClientName" type="text" placeholder="Nombre y apellido"></div>
            <div class="cfg-row"><label>Teléfono</label><input class="cfg-input" id="newClientPhone" type="text" placeholder="Ej: 0414-1234567"></div>
            <div class="cfg-row"><label>Cédula o RIF (opcional)</label><input class="cfg-input" id="newClientIdNum" type="text" placeholder="Ej: V-12345678"></div>
            <div class="cfg-row"><label>Dirección (opcional)</label><input class="cfg-input" id="newClientAddress" type="text" placeholder="Ej: Calle 5, Machiques"></div>
            <div class="form-actions"><button class="btn-secondary" id="newClientCancel">Cancelar</button><button class="btn-primary" id="newClientSave">Guardar</button></div>
          </div>
          <div id="clientList"></div>
        </div>
        <div class="view" id="view-porcobrar">
          <div class="example pc-summary-box" id="pcSummary"></div>
          <div id="pcList"></div>
        </div>
        <div class="view" id="view-pedidos">
          <div class="chips" id="pedidosChips"></div>
          <div id="pedidosList"></div>
        </div>
        <div class="view" id="view-catalogo">
          <div class="view-toolbar">
            <div class="search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path></svg><input id="catalogoSearch" type="text" placeholder="Buscar producto…" autocomplete="off"></div>
            <button class="btn-primary" id="newProductBtn">+ Nuevo producto</button>
          </div>
          <div id="catalogoSimpleAviso"></div>
          <div class="cfg-block" id="productForm" style="display:none;max-width:520px">
            <h4 id="productFormTitle">Nuevo producto</h4>
            <div class="desc">Así se arma tu precio: tu costo + gastos del negocio + tu ganancia = precio antes de impuestos (Base Imponible). Si el producto lleva IVA, se le suma encima para el precio final que paga el cliente (USD oferta). El bolívar se calcula a la tasa Paralelo (o BCV si no la tienes cargada).</div>
            <div class="cfg-row"><label>Foto</label><label class="cfg-file" for="prodPhoto"><span class="logo-preview" id="prodPhotoWrap" style="border-radius:10px"></span><span class="cfg-file-text" id="prodPhotoText">Subir imagen</span><input type="file" id="prodPhoto" accept="image/*" style="display:none"></label></div>
            <div class="cfg-row"><label>Nombre</label><input class="cfg-input" id="prodName" type="text" placeholder="Ej: Harina PAN 1kg" autocomplete="off"></div>
            <div id="prodNameResultados" class="cm-resultados" style="display:none"></div>
            <div class="cfg-row" id="prodBrandRow"><label>Marca (opcional)</label><input class="cfg-input" id="prodBrand" type="text" placeholder="Ej: Vatel, PAN, Polar…"></div>
            <div class="cfg-row"><label>Categoría</label><div style="display:flex;gap:8px;flex:1 1 auto"><select class="cfg-input" id="prodCat" style="flex:1 1 auto"></select><button class="btn-secondary" id="prodCatAdd" type="button" style="flex:0 0 auto">＋</button></div></div>
            <div class="cfg-row" id="prodSkuRow"><label>SKU o código de barras (opcional)</label><input class="cfg-input" id="prodSku" type="text" placeholder="Escríbelo, o déjalo para leerlo con la cámara más adelante"></div>
            <div class="cfg-row" id="prodStockRow"><label>Stock inicial</label><input class="cfg-input" id="prodStock" type="number" inputmode="decimal" placeholder="Ej: 24" value="0"></div>
            <div class="cur-row"><div><div class="nm">¿Se vende pesado en el mostrador?</div><div class="sub">Ej: queso, embutidos, carne — se pesa cada vez que se vende, el precio de abajo es POR KILOGRAMO</div></div></div>
            <div class="seg" id="prodVentaPeso" style="margin:8px 0 12px"><button data-v="no" class="on">No</button><button data-v="si">Sí</button></div>
            <div id="prodComoSeVendeWrap">
              <div class="group-sep"><div class="nm">Cómo se vende</div><div class="sub">Por unidad, o por peso/volumen con el tamaño del envase</div></div>
              <div class="seg" id="prodUnitType" style="margin:8px 0 10px"><button data-v="unidad" class="on">Unidad</button><button data-v="peso">Peso</button><button data-v="volumen">Volumen</button></div>
              <div class="cfg-row" id="prodUnitValueRow" style="display:none"><label id="prodUnitValueLabel">Contenido</label><input class="cfg-input" id="prodUnitValue" type="text" placeholder="Ej: 750 g, 1 kg, 1.5 Kg"><div class="desc" style="margin:6px 0 0" id="prodUnitHint"></div></div>
            </div>
            <div class="desc" id="prodVentaPesoNota" style="display:none;margin:-4px 0 12px">Este producto se pesa al vender — no aplica lo de "Cómo se vende" de arriba, que es para el tamaño de un envase fijo.</div>
            <div class="cfg-row" id="prodOfferPriceRow"><label>Precio de oferta en $ (opcional)</label><input class="cfg-input" id="prodOfferPrice" type="number" inputmode="decimal" placeholder="Déjalo vacío si no está en oferta"></div>
            <div id="prodCatalogoWrap">
              <div class="cur-row"><div><div class="nm">¿Mostrar en tu Catálogo Digital?</div><div class="sub">La página pública que puedes compartir por WhatsApp — ver Configuración → Catálogo Digital</div></div></div>
              <div class="seg" id="prodEnCatalogo" style="margin:8px 0 12px"><button data-v="si" class="on">Sí</button><button data-v="no">No</button></div>
            </div>

            <div id="prodImpuestosWrap">
              <div class="group-sep"><div class="nm">Impuestos</div><div class="sub">Si el producto lleva IVA</div></div>
              <div class="cur-row"><div><div class="nm">¿Sujeto a IVA?</div><div class="sub">La mayoría de víveres (harina, arroz, leche, granos) están exentos. Actívalo para cosas como refrescos, licores, limpieza o cuidado personal, si el SENIAT te obliga a cobrarles IVA</div></div></div>
              <div class="seg" id="prodIvaSubject" style="margin:8px 0 12px"><button data-v="no" class="on">No</button><button data-v="si">Sí</button></div>
            </div>

            <!-- v49 — el interruptor "Modelo de precio: Simple/Personalizado" que vivía acá se quitó:
                 antes era una decisión por producto, ahora el modo (Simple/Medio BCV/Avanzado) se
                 elige una sola vez para todo el negocio en Configuración → Precios y tasas
                 (#businessModeToggle) y aplica solo con applyBusinessModeToProductForm(). -->
            <div id="prodSimpleWrap">
              <div class="cfg-row"><label id="prodSimplePrecioLabel">Precio final</label><input class="cfg-input" id="prodSimplePrecio" type="number" inputmode="decimal" placeholder="Ej: 100"></div>
              <div class="cur-row"><div><div class="nm">¿En qué moneda escribiste ese precio?</div></div></div>
              <div class="seg" id="prodSimpleMoneda" style="margin:8px 0 12px"><button data-v="paralelo" class="on">$ Paralelo</button><button data-v="bcv">$ BCV</button><button data-v="bs">Bolívares</button></div>
              <div id="prodSimpleIvaInclWrap" style="display:none">
                <div class="cur-row"><div><div class="nm">¿Ese precio ya incluye el IVA?</div><div class="sub">Si es lo que le cobras al cliente tal cual, marca "Sí". Si es el precio SIN IVA y falta sumárselo, marca "No"</div></div></div>
                <div class="seg" id="prodSimpleIvaIncl" style="margin:8px 0 12px"><button data-v="si" class="on">Sí</button><button data-v="no">No</button></div>
              </div>
              <div class="desc" id="prodSimpleNota" style="margin:-4px 0 12px"></div>
            </div>

            <div id="prodPersonalizadoWrap">
              <div class="group-sep"><div class="nm">Cómo lo compras</div><div class="sub">De aquí sale tu costo: cómo te lo vende el proveedor y en qué moneda le pagas</div></div>
              <div class="seg" id="prodBuyMode" style="margin:8px 0 10px"><button data-v="unidad" class="on">Por unidad</button><button data-v="bulto">Por bulto/caja</button></div>
              <div class="cur-row"><div><div class="nm">Moneda de compra</div><div class="sub">En qué moneda pagas al proveedor</div></div></div>
              <div class="seg" id="prodBuyCur" style="margin:8px 0 12px"><button data-v="usd" class="on">USD</button><button data-v="bs">Bs</button></div>
              <div class="cfg-row" id="prodUnitCostRow"><label id="prodUnitCostLabel">Costo por unidad</label><input class="cfg-input" id="prodUnitCost" type="number" inputmode="decimal" placeholder="Ej: 1.09"></div>
              <div id="prodBulkRows" style="display:none">
                <div class="cfg-row"><label>Costo del bulto/caja</label><input class="cfg-input" id="prodBulkCost" type="number" inputmode="decimal" placeholder="Ej: 18.00"></div>
                <div class="cfg-row"><label>Unidades por bulto</label><input class="cfg-input" id="prodBulkUnits" type="number" inputmode="decimal" placeholder="Ej: 24"></div>
              </div>
              <div id="prodCostIvaWrap" style="display:none">
                <div class="cur-row"><div><div class="nm">¿El costo que escribiste ya trae el IVA?</div><div class="sub">Algunos proveedores te facturan el monto totalizado (con IVA adentro) y otros te lo ponen aparte. Mira tu factura</div></div></div>
                <div class="seg" id="prodCostIvaIncl" style="margin:8px 0 12px"><button data-v="no" class="on">No, es sin IVA</button><button data-v="si">Sí, ya viene totalizado</button></div>
                <div class="desc" id="prodCostIvaNota" style="margin:-6px 0 12px"></div>
              </div>
              <div class="group-sep" id="prodGastosHead"><div class="nm">Gastos operativos</div><div class="sub">Qué parte de la luz, el alquiler, las bolsas y los sueldos carga este producto</div></div>
              <div class="cfg-row" id="prodGastosRow"><label>Gastos (%)</label><input class="cfg-input" id="prodGastosPct" type="number" inputmode="decimal" placeholder="Ej: 4"></div>

              <div class="group-sep"><div class="nm">Cuánto quieres ganar</div><div class="sub" id="prodMarginNote">Se calcula sobre el costo base, aparte de los gastos operativos</div></div>
              <div class="seg" id="prodMarginMode" data-hide-in-gastos="1" style="margin:8px 0 10px"><button data-v="costo" class="on">Sobre costo</button><button data-v="venta">Sobre venta</button></div>
              <div class="cfg-row"><label>Ganancia (%)</label><input class="cfg-input" id="prodMarginPct" type="number" inputmode="decimal" placeholder="Ej: 30"></div>
            </div>

            <div class="desc" style="margin:14px 0 8px">Así se vería en la tienda:</div>
            <div class="example" id="prodPreview"></div>
            <div class="form-actions" style="margin-top:14px"><button class="btn-secondary" id="prodCancel">Cancelar</button><button class="btn-primary" id="prodSave">Guardar producto</button></div>
          </div>
          <div id="catalogoList"></div>
        </div>
        <div class="view" id="view-stock">
          <div class="view-toolbar">
            <div class="search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path></svg><input id="stockSearch" type="text" placeholder="Buscar producto…" autocomplete="off"></div>
          </div>
          <div class="example" id="stockSummary" style="max-width:560px;margin-bottom:16px"></div>
          <div id="stockList"></div>
        </div>
        <div class="view" id="view-cajon">
          <div id="cajonBody" style="max-width:600px"></div>
        </div>
        <!-- v37 — Tasas al menú: BCV/Paralelo/monedas propias en un toque, sin entrar a
             Configuración. Los mismos campos (cfg.bcv/cfg.paralelo/cfg.monedas) que Configuración
             → Precios y tasas — no hay dos fuentes de verdad, solo dos sitios para tocarlos. -->
        <div class="view" id="view-tasas">
          <div id="tasasBody" style="max-width:480px"></div>
        </div>
        <div class="view" id="view-informes">
          <div id="informesBody" style="max-width:640px"></div>
        </div>
        <div class="view" id="view-config">
          <details class="cfg-group" data-group="negocio">
            <summary><div class="cg-tt"><div class="cg-name">Negocio</div><div class="cg-note">Nombre, logo, datos fiscales y administración</div></div><svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><path d="m9 6 6 6-6 6"/></svg></summary>
            <div class="cfg-group-body">
              <div class="cfg-block" id="identidadBlock">
                <h4>Identidad del negocio</h4>
                <div class="desc">Nombre y logo que se ven en toda la app.</div>
                <details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">Este nombre y logo son solo para tu copia de la app — no se comparten con nadie. Aparecen en el menú, en Cajón y en los comprobantes que envías por WhatsApp, así que conviene que sea el nombre por el que tus clientes te conocen.</div></details>
                <div class="cfg-row"><label>Nombre</label><input class="cfg-input" id="bizName" type="text" value="FrixPOS" placeholder="Nombre de tu negocio"></div>
                <div class="cfg-row"><label>Logo</label><label class="cfg-file" for="logoUpload"><span class="logo-preview mark brand-mark" id="logoPreviewWrap">F</span><span class="cfg-file-text">Subir imagen</span><input type="file" id="logoUpload" accept="image/*" style="display:none"></label></div>
                <button class="btn-secondary" id="tourReplayBtn" style="width:100%;text-align:center;margin-top:14px">↻ Ver la guía de nuevo</button>
                <button class="btn-secondary" id="coachReplayBtn" style="width:100%;text-align:center;margin-top:8px">Guíame paso a paso por la app</button>
              </div>
              <div class="cfg-block" id="activacionBlock">
                <h4>Activación</h4>
                <details class="note" style="margin-top:0"><summary><span class="n-closed">¿Cómo funciona? ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">El código de activación te lo da quien te vendió FrixPOS, y se usa una sola vez: queda amarrado a tu cuenta. Al activarlo, tus ventas se van subiendo solas cuando hay internet — si no hay señal, todo sigue funcionando igual y se sube después. Nunca se pierde nada por no tener conexión.</div></details>
                <div id="activacionEstado"></div>
              </div>
              <div class="cfg-block" id="respaldoBlock">
                <h4>Respaldo</h4>
                <div class="desc">Guarda una copia de tu catálogo, stock, clientes, pedidos del último mes, cajón y configuración — para recuperarla si pierdes el teléfono. Necesita una cuenta (Identidad), y funciona aunque no hayas activado ningún código.</div>
                <details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">Sin código de activación tienes 2 respaldos guardados (el más viejo se reemplaza al hacer uno nuevo), sin fotos de producto. Con el código activo, hasta 30, y las fotos también quedan incluidas. Se guarda solo al cerrar turno, o cuando toques "Respaldar ahora".</div></details>
                <div id="respaldoEstado"></div>
              </div>
              <div class="cfg-block" id="fiscalBlock">
                <h4>Datos fiscales del negocio</h4>
                <div class="desc">Se agregan al comprobante que compartes, junto al nombre.</div>
                <div class="cfg-row"><label>RIF o NIT (opcional)</label><input class="cfg-input" id="bizRif" type="text" placeholder="Ej: J-12345678-9"></div>
                <div class="cfg-row"><label>Dirección (opcional)</label><input class="cfg-input" id="bizAddress" type="text" placeholder="Ej: Av. Principal, Machiques"></div>
                <div class="cfg-row"><label>Teléfono (opcional)</label><input class="cfg-input" id="bizPhone" type="text" placeholder="Ej: 0414-1234567"></div>
              </div>
              <div class="cfg-block">
                <h4>Administración</h4>
                <div class="desc">Marca abajo qué puede hacer tu cajero. Cuando pases el teléfono a modo <b>Cajero</b>, se te pide crear una <b>clave</b>: sin ella nadie puede devolverse a Administrador.</div>
                <details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">En modo Cajero, esta pantalla de Configuración se recorta sola: desaparecen el nombre del negocio, la activación, el respaldo, las tasas de cambio, los métodos de pago y el botón de borrar datos. Tu cajero solo ve lo suyo, y para salir de ahí hace falta la clave.<br><br><b>Guarda bien tu clave</b> — se queda en este teléfono, no la mandamos a ningún lado y no hay forma de recuperarla desde aquí. Si se te olvida, tendrías que borrar los datos del teléfono y volver a recuperar tu respaldo.<br><br>Vender y cobrar el cajero siempre lo puede hacer — es su trabajo. Lo que eliges abajo es todo lo demás.</div></details>
                <div class="seg" id="roleToggle"><button data-r="admin" class="on">Administrador</button><button data-r="cajero">Cajero</button></div>
                <div id="cajeroPermsWrap" style="display:none">
                  <div class="desc" style="margin:14px 0 8px">¿Qué puede hacer tu cajero?</div>
                  <div id="cajeroPermsList"></div>
                </div>
              </div>
              <div class="cfg-block" id="wipeBlock">
                <h4>Empezar de cero</h4>
                <div class="desc">Borra <b>todo</b> lo que este teléfono tiene guardado: productos, ventas, clientes, turnos, egresos y configuración. La app queda como recién instalada.</div>
                <details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">Úsalo si estabas probando la app y quieres arrancar limpio con los datos reales del negocio, o si le vas a entregar el teléfono a otra persona.<br><br>Esto <b>solo borra este dispositivo</b>. Si tu negocio ya está activado y sincronizó ventas al servidor, esas ventas siguen allá — pero este teléfono no se las vuelve a traer solo, hay que pedirlas desde el panel.<br><br><b>No se puede deshacer.</b> Si no estás seguro, no lo toques.</div></details>
                <button class="btn-secondary" id="wipeBtn" style="width:100%;margin-top:10px;color:var(--red)">Borrar todos mis datos de este teléfono</button>
              </div>
              <div class="cfg-save-row"><button class="btn-primary cfg-save-btn" data-group="negocio">Guardar cambios</button><span class="saved-tag" data-group="negocio">✓ Guardado</span></div>
            </div>
          </details>

          <details class="cfg-group" data-group="perfil">
            <summary><div class="cg-tt"><div class="cg-name">Mi perfil</div><div class="cg-note">Tus datos de cuenta — separado del negocio</div></div><svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><path d="m9 6 6 6-6 6"/></svg></summary>
            <div class="cfg-group-body">
              <div class="cfg-block" id="perfilBlock">
                <h4>Mi perfil</h4>
                <div class="desc">Quién eres, no tu negocio. El negocio (arriba) vive en cada teléfono; esto viaja con tu cuenta — si algún día entras desde otro teléfono con el mismo correo, tu perfil te va a estar esperando.</div>
                <div class="desc" id="perfilLoginMsg" style="display:none">Inicia sesión o crea una cuenta (arriba, en Negocio → Identidad) para ver y editar tu perfil.</div>
                <div id="perfilFields">
                  <div class="cfg-row"><label>Tu foto</label><label class="cfg-file" for="perfilFotoUpload"><span class="logo-preview mark" id="perfilFotoWrap" style="border-radius:50%"></span><span class="cfg-file-text" id="perfilFotoText">Subir foto</span><input type="file" id="perfilFotoUpload" accept="image/*" style="display:none"></label></div>
                  <div class="cfg-row"><label>Correo</label><input class="cfg-input" id="perfilEmail" type="text" disabled></div>
                  <div class="cur-row"><div><div class="nm">¿Cómo te identificas?</div></div></div>
                  <div class="seg" id="perfilTipo" style="margin:8px 0 12px"><button data-v="negocio" class="on">Negocio</button><button data-v="emprendedor">Emprendedor</button></div>
                  <div class="cfg-row"><label id="perfilNombreLbl">Nombre del negocio</label><input class="cfg-input" id="perfilNombre" type="text" placeholder="Ej: Bodega Los Pinos"></div>
                  <div class="cfg-row"><label id="perfilIdLbl">RIF</label><input class="cfg-input" id="perfilIdNumber" type="text" placeholder="Ej: J-12345678-9"></div>
                  <div class="cfg-row"><label>Teléfono</label><input class="cfg-input" id="perfilPhone" type="text" placeholder="Ej: 0414-1234567"></div>
                  <div class="cfg-row"><label>Dirección</label><input class="cfg-input" id="perfilAddress" type="text" placeholder="Ej: Av. Principal, Machiques"></div>
                  <div class="desc" id="perfilSyncNota" style="margin-top:8px"></div>
                </div>
              </div>
              <div class="cfg-save-row"><button class="btn-primary cfg-save-btn" data-group="perfil" id="perfilSaveBtn">Guardar cambios</button><span class="saved-tag" data-group="perfil">✓ Guardado</span></div>
            </div>
          </details>

          <details class="cfg-group" data-group="catalogodigital">
            <summary><div class="cg-tt"><div class="cg-name">Catálogo Digital</div><div class="cg-note">Tu vitrina pública, gratis, para compartir por WhatsApp</div></div><svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><path d="m9 6 6 6-6 6"/></svg></summary>
            <div class="cfg-group-body">
              <div class="cfg-block" id="catalogoDigitalBlock">
                <h4>Catálogo Digital</h4>
                <div class="desc">Una página pública con tu catálogo — sin carrito ni registro. El cliente busca, ve tus productos y te escribe por WhatsApp. Gratis para todos, actives o no un código.</div>
                <div class="desc" id="catalogoLoginMsg" style="display:none">Inicia sesión o crea una cuenta (arriba, en Negocio → Identidad) para publicar tu catálogo.</div>
                <div id="catalogoFields">
                  <div class="cfg-row"><label>Nombre para tu enlace</label><input class="cfg-input" id="catSlug" type="text" placeholder="ej: bodega-los-pinos" autocomplete="off"></div>
                  <div class="desc" id="catSlugPreview" style="margin-top:-6px"></div>
                  <div class="cur-row"><div><div class="nm">Publicar mi catálogo</div><div class="sub">Mientras esté en "No", tu página no es visible para nadie</div></div></div>
                  <div class="seg" id="catVisible" style="margin:8px 0 12px"><button data-v="no" class="on">No</button><button data-v="si">Sí</button></div>
                  <div class="cur-row"><div><div class="nm">¿Mostrar precios?</div><div class="sub">Apagado por defecto: tus clientes ven "Consultar precio" y te escriben por WhatsApp</div></div></div>
                  <div class="seg" id="catMostrarPrecios" style="margin:8px 0 12px"><button data-v="no" class="on">No</button><button data-v="si">Sí</button></div>
                  <div class="cfg-row"><label>Logo (opcional)</label><label class="cfg-file" for="catLogoUpload"><span class="logo-preview mark" id="catLogoPreviewWrap"></span><span class="cfg-file-text" id="catLogoText">Subir imagen</span><input type="file" id="catLogoUpload" accept="image/*" style="display:none"></label></div>
                  <div class="desc">Se publican los productos marcados "Sí" en Inventario → editar producto → "¿Mostrar en tu Catálogo Digital?".</div>
                  <button class="btn-primary" id="catPublicarBtn" style="width:100%;margin-top:10px">Publicar catálogo</button>
                  <div class="desc" id="catAviso" style="margin-top:8px"></div>
                  <!-- v36 — compartir. Solo aparece cuando el catálogo ya está publicado: ofrecer
                       "compartir" algo que todavía no existe manda al cliente a una página caída. -->
                  <div id="catCompartirBloque" style="display:none;margin-top:14px">
                    <div class="group-sep"><div class="nm">Compartir tu catálogo</div><div class="sub">Mándalo por WhatsApp, pégalo en tu estado o en tu perfil de Instagram</div></div>
                    <div class="cfg-row" style="gap:8px;align-items:center">
                      <input class="cfg-input" id="catEnlace" type="text" readonly style="flex:1 1 auto;font-size:.8rem">
                    </div>
                    <button class="btn-primary" id="catCompartirWa" style="width:100%;margin-top:8px">Compartir por WhatsApp</button>
                    <button class="btn-secondary" id="catCopiarBtn" style="width:100%;margin-top:8px">Copiar enlace</button>
                    <button class="btn-secondary" id="catAbrirBtn" style="width:100%;margin-top:8px">Ver cómo lo ven tus clientes</button>
                    <div class="desc" id="catCompartirAviso" style="margin-top:8px"></div>
                  </div>
                </div>
              </div>
            </div>
          </details>

          <details class="cfg-group" data-group="apariencia">
            <summary><div class="cg-tt"><div class="cg-name">Apariencia</div><div class="cg-note">Color, tema, tamaño de letra y productos por fila</div></div><svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><path d="m9 6 6 6-6 6"/></svg></summary>
            <div class="cfg-group-body">
              <div class="cfg-block">
                <h4>Color</h4>
                <div class="swatches" id="swatches"></div>
              </div>
              <div class="cfg-block">
                <h4>Tema</h4>
                <div class="seg" id="modeToggle"><button data-m="dark" class="on">Oscuro</button><button data-m="claro">Claro</button></div>
              </div>
              <div class="cfg-block">
                <h4>Tamaño de letra</h4>
                <div class="seg" id="fontToggle"><button data-f="sm">Pequeño</button><button data-f="md" class="on">Mediano</button><button data-f="lg">Grande</button></div>
              </div>
              <div class="cfg-block">
                <h4>Productos por fila</h4>
                <div class="desc">En el teléfono y en pantalla grande caben cantidades distintas — cada uno tiene su propia preferencia.</div>
                <div class="cur-row" style="border:0;padding:10px 0 4px"><div class="nm">En el teléfono</div></div>
                <div class="seg" id="densityToggleMobile" style="margin-bottom:14px"><button data-d="1">1</button><button data-d="2">2</button><button data-d="3" class="on">3</button></div>
                <div class="cur-row" style="border:0;padding:10px 0 4px"><div class="nm">En pantalla grande</div></div>
                <div class="seg" id="densityToggleDesktop" style="margin-bottom:14px"><button data-d="4">4</button><button data-d="5" class="on">5</button><button data-d="6">6</button></div>
                <details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">Menos columnas = fotos y nombres más grandes, más fáciles de tocar rápido. Más columnas = ves más productos sin hacer scroll, útil si usas una tablet o pantalla grande.</div></details>
                <div class="desc" style="margin-bottom:0">Vista previa:</div>
                <div class="grid" id="previewGrid" data-d="5" style="margin-top:10px"></div>
              </div>
              <div class="cfg-save-row"><button class="btn-primary cfg-save-btn" data-group="apariencia">Guardar cambios</button><span class="saved-tag" data-group="apariencia">✓ Guardado</span></div>
            </div>
          </details>

          <details class="cfg-group" data-group="precios">
            <summary><div class="cg-tt"><div class="cg-name">Precios y tasas</div><div class="cg-note">Cómo se calculan tus precios, tasas de cambio y factura</div></div><svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><path d="m9 6 6 6-6 6"/></svg></summary>
            <div class="cfg-group-body">
              <div class="cfg-block">
                <h4>Modelo de precios</h4>
                <div class="desc">Así arma FrixPOS tus precios <b>y con qué monedas trabajas</b>: bolívares y dólar real.</div>
                <!-- v5 — el selector de modelo se eliminó: hay un solo motor de precios. Antes acá
                     también se describía "Margen sobre costo" (multimoneda, dólar paralelo) como una
                     segunda opción — ese modelo ya no existe desde v5, se quitó la sección completa
                     para no dejar documentado un modo que ya no se puede elegir.
                     v27 — el ancla pasó de BCV a Paralelo (dólar real): ver "Tasas de cambio" abajo.
                     v49 — el cálculo de arriba NO cambia (sigue anclado en dólar real/Paralelo). Lo
                     nuevo es "businessMode": antes cada producto elegía Simple/Personalizado por su
                     cuenta (#prodPricingMode, ver formulario de producto); ahora es UNA decisión para
                     todo el negocio, acá, y decide qué campos pide el formulario de producto. -->
                <div class="cur-row" style="border:0;padding-top:0"><div><div class="nm">¿Cómo quieres armar tus precios?</div><div class="sub">Elige un modo para todo el negocio de una vez — ya no se elige producto por producto</div></div></div>
                <div class="seg" id="businessModeToggle" style="margin:8px 0 14px">
                  <button data-v="simple">Simple</button>
                  <button data-v="medio" class="on">Medio BCV</button>
                  <button data-v="avanzado">Personalizado</button>
                </div>

                <div id="costoGastosGananciaExplain">
                  <div class="cur-row" style="border:0;padding-top:0"><div><div class="nm">Costo + gastos + ganancia</div><div class="sub">Trabajas con <b>bolívares y dólar real</b>. El USD BCV de vitrina es opcional, para cumplir con el SENIAT.</div></div></div>
                  <details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">
                    <b>Qué te permite:</b> saber cuánto de tu precio es costo, cuánto se te va en gastos de tener el negocio abierto y cuánto es ganancia de verdad. Como separa los gastos, la ganancia que ves en Contabilidad es la que realmente te queda en el bolsillo, no una ilusión.<br><br>
                    <b>Qué entra en "gastos operativos":</b> todo lo que pagas para poder abrir la puerta, aunque no vendas nada — luz, agua, gas, internet y teléfono, alquiler del local, sueldos y nómina de quien te ayuda, bolsas y empaques, productos de limpieza, papelería y rollos de la impresora, flete y transporte de la mercancía, mantenimiento y reparaciones de neveras y estantes, vigilancia o alarma, uniformes, mermas de lo que se daña o vence, comisiones del punto de venta, y lo que pagas de patente municipal. Sumas todo eso en el mes, lo divides entre lo que vendes, y ese es tu porcentaje.<br><br>
                    <b>Cómo se calcula:</b> al costo del producto se le suma un % de gastos operativos y un % de ganancia — los dos calculados sobre el costo base. Eso da la <b>Base Imponible</b>. Si el producto lleva IVA, se le suma el <b>IVA Débito Fiscal</b> encima y el resultado es tu <b>USD oferta</b> (el precio ancla, en dólar real — es lo que de verdad necesitas recibir). El bolívar sale multiplicando el USD oferta por la tasa <b>Paralelo</b>.<br><br>
                    <b>USD BCV (vitrina):</b> si además cargas la tasa BCV, FrixPOS calcula un segundo número solo para la etiqueta/factura formal — infla el USD oferta por Paralelo÷BCV, así que al multiplicarlo de nuevo por BCV da el mismo bolívar que ya cobraste. No es una segunda ganancia ni un precio distinto, es la misma plata mostrada en dólares oficiales para cumplir con el SENIAT. Se puede apagar en "Tasas de cambio" si no lo necesitas.<br><br>
                    <b>Ejemplo fácil:</b> compras un jabón en <b>$0.84</b> (sin IVA). Le pones 4% de gastos (<b>$0.034</b>, su parte de la luz y las bolsas) y 30% de ganancia (<b>$0.252</b>, que es el tope que fija la Ley de Precios Justos). Eso suma <b>$1.126</b>, que es tu Base Imponible. Como el jabón sí lleva IVA, le sumas el 16% (<b>$0.180</b>) y el cliente paga <b>$1.306</b> de USD oferta. De esos 18 centavos de IVA, tú ya le habías pagado $0.134 de IVA a tu proveedor — así que al SENIAT solo le debes la diferencia: <b>$0.046</b>.<br><br>
                    <b>Si tu proveedor te factura totalizado:</b> a veces el proveedor no te pone la base y el IVA aparte, sino el monto ya sumado — en vez de $0.84 te factura $0.974. En Inventario marcas "el costo ya trae el IVA" y FrixPOS le saca el 16% solo para llegar a esos $0.84 de base. De ahí en adelante todo sigue igual.<br><br>
                    <b>Con qué monedas trabajas:</b> bolívares (a Paralelo) y USD oferta siempre; USD BCV de vitrina si además cargas esa tasa. Toda la app queda ahí: el carrito, los métodos de pago, los egresos, las deudas y el Cajón. Si además cobras en otra moneda (pesos, reales…), puedes agregarla más abajo, en "Otras monedas" — se valúa contra el dólar real, nunca contra el BCV.
                  </div></details>
                </div>
              </div>
              <div class="cfg-block">
                <h4>Tasas de cambio</h4>
                <div class="desc">Si dejas una tasa vacía, esa moneda no aparece en la app.</div>
                <details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">El <b>Paralelo</b> es el dólar que de verdad se usa en la calle para vender — es el que manda en tus precios y en el bolívar que cobras. El <b>BCV</b> es la tasa oficial: si la cargas junto con el Paralelo, FrixPOS deriva de ahí un "USD BCV" solo para la etiqueta/factura formal (el bolívar que cobras no cambia). Si no tienes Paralelo, el BCV pasa a ser la única tasa del sistema. Actualiza estos números seguido — de aquí sale todo lo demás (precios, deudas, Cajón).</div></details>
                <div class="cur-row"><div><div class="nm">BCV (Bs por USD)</div><div class="sub">Tasa oficial del Banco Central</div></div><input class="cfg-input" id="rateBcv" type="number" inputmode="decimal" placeholder="Ej: 752"></div>
                <div class="cur-row"><div><div class="nm">Paralelo (Bs por USD)</div><div class="sub">Dólar de calle — tu ancla real. Déjalo vacío si no lo usas</div></div><input class="cfg-input" id="rateParalelo" type="number" inputmode="decimal" placeholder="Ej: 890"></div>
                <div class="cur-row" id="mostrarUsdBcvRow"><div><div class="nm">¿Mostrar el USD BCV junto al USD oferta?</div><div class="sub">Si lo apagas, el carrito solo muestra bolívares. Si lo enciendes, cuando estés cobrando en bolívares también ves el dólar (BCV) al lado — bolívares y dólar juntos, de un vistazo. Solo aplica si cargaste BCV y Paralelo a la vez</div></div></div>
                <div class="seg" id="mostrarUsdBcvToggle" style="margin:8px 0 2px"><button data-v="si" class="on">Sí</button><button data-v="no">No</button></div>
                <div class="cur-row" id="mostrarUsdOfertaRow" style="margin-top:10px"><div><div class="nm">¿Mostrar el USD oferta?</div><div class="sub">El dólar real, con descuento por pago en efectivo/digital — un dato informal tuyo, no va en la factura. Apágalo si solo quieres cobrar a precio BCV — el Paralelo sigue funcionando adentro, para cuidar tu costo, solo deja de ofrecerse como descuento</div></div></div>
                <div class="seg" id="mostrarUsdOfertaToggle" style="margin:8px 0 2px"><button data-v="no" class="on">No</button><button data-v="si">Sí</button></div>
                <!-- v5 — "Pesos" dejó de ser una moneda fija del sistema. Ahora se crean abajo, en "Otras monedas". -->
                <div class="cur-row"><div><div class="nm">IVA general (%)</div><div class="sub">Solo aplica a los productos marcados "Sujeto a IVA" en Inventario — control fiscal SENIAT</div></div><input class="cfg-input" id="rateIva" type="number" inputmode="decimal" placeholder="Ej: 16"></div>
                <div class="cur-row" id="gastosDefaultRow"><div><div class="nm">% Gastos operativos por defecto</div><div class="sub">Con qué % vienen los productos nuevos. Se puede cambiar producto por producto en Inventario</div></div><input class="cfg-input" id="gastosDefault" type="number" inputmode="decimal" placeholder="Ej: 4"></div>
              </div>
              <div class="cfg-block" id="monedasBlock">
                <h4>Otras monedas</h4>
                <div class="desc">Si además de bolívares y dólares cobras en otra moneda (pesos, reales, lo que sea), créala aquí con su tasa <b>respecto al dólar</b>.</div>
                <details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">La tasa se escribe como <b>cuántas unidades de esa moneda te dan por un dólar</b>. Ejemplo: si el peso está a 3.500 por dólar, escribes 3500.<br><br>Se valúan contra el <b>dólar paralelo</b>, no contra el BCV — porque quien maneja otra moneda la está cambiando contra el dólar de la calle. Por eso hace falta tener el paralelo cargado arriba antes de poder activar ninguna.<br><br>Cada moneda que crees aparece sola en el carrito, en los métodos de cobro (como "Efectivo &lt;nombre&gt;") y con su propia gaveta en el arqueo del Cajón.<br><br>El <b>redondeo</b> es para no andar con centavos: si pones 100, los precios suben al siguiente múltiplo de 100. Deja 0 si no quieres redondear.</div></details>
                <div id="monedasList"></div>
                <div id="monedasAvisoParalelo" class="desc" style="display:none;margin-top:8px;color:var(--amber)">Para activar otra moneda tienes que cargar primero el <b>dólar paralelo</b> arriba.</div>
                <button class="btn-secondary" id="monedaAddBtn" style="width:100%;margin-top:10px">+ Agregar moneda</button>
              </div>
              <div class="cfg-block" id="priceExampleBlock">
                <h4 id="priceExampleTitle">Así queda un producto de $1.09 de costo</h4>
                <div class="desc">Con las tasas que tienes cargadas arriba.</div>
                <div class="example" id="priceExample"></div>
              </div>
              <div class="cfg-block" id="otrosImpuestosBlock">
                <h4>Otros impuestos del negocio</h4>
                <div class="desc">Además del IVA, un negocio en Venezuela paga la patente de su alcaldía y el ISLR una vez al año. Configura aquí tus porcentajes y Contabilidad te los estima solo.</div>
                <details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body"><b>Patente municipal (Impuesto sobre Actividades Económicas):</b> cada alcaldía tiene su propia ordenanza y su propio porcentaje según el ramo del negocio — bodegas y minimarkets suelen estar entre 1% y 3% de las ventas brutas del mes. Pídele el número exacto a tu alcaldía o a tu contador; si lo dejas en 0, FrixPOS no lo calcula.<br><br><b>ISLR:</b> se paga una vez al año sobre el enriquecimiento neto (lo que de verdad ganó el negocio en todo el ejercicio, ya descontados los gastos deducibles). El porcentaje depende de la forma legal (persona natural, firma personal, compañía anónima) y del tramo en que caiga la ganancia, así que <b>no lo trae puesto</b>: pregúntale a tu contador cuál te toca y escríbelo aquí. Lo que FrixPOS muestra es una <b>estimación de orientación</b>, no una declaración.<br><br><b>Contribuyente Especial:</b> el SENIAT designa formalmente a algunos negocios (por lo general medianos y grandes) como Sujetos Pasivos Especiales, y eso los obliga a retener IVA a sus proveedores y a cobrar el 3% de IGTF en los pagos en divisas. Una bodega normal es <b>Contribuyente Ordinario</b> y no hace ninguna de las dos cosas. Deja esto en "No" salvo que el SENIAT te haya notificado por escrito.</div></details>
                <div class="cur-row"><div><div class="nm">Patente municipal (%)</div><div class="sub">Lo que cobra tu alcaldía sobre las ventas del mes. 0 = no la calcules</div></div><input class="cfg-input" id="ratePatente" type="number" inputmode="decimal" placeholder="Ej: 2"></div>
                <div class="cur-row"><div><div class="nm">ISLR estimado (%)</div><div class="sub">El que te indique tu contador según tu forma legal. 0 = no lo estimes</div></div><input class="cfg-input" id="rateIslr" type="number" inputmode="decimal" placeholder="Ej: 15"></div>
                <div class="cur-row"><div><div class="nm">¿Eres Contribuyente Especial?</div><div class="sub">Solo si el SENIAT te designó por escrito. Casi ninguna bodega lo es</div></div></div>
                <div class="seg" id="contribEspToggle" style="margin:8px 0 2px"><button data-v="no" class="on">No</button><button data-v="si">Sí</button></div>
              </div>
              <div class="cfg-block" id="currOrderBlock">
                <h4>Orden de monedas en el carrito</h4>
                <div class="desc">Ordena cómo aparecen las monedas al cobrar. La primera es la que se muestra por defecto.</div>
                <details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">Solo cambia qué moneda ves primero al cobrar y en las pantallas de saldo — no cambia ningún precio ni ningún cálculo, es puro orden de pantalla.</div></details>
                <div id="currOrderList"></div>
              </div>
              <div class="cfg-block">
                <h4>Moneda del comprobante</h4>
                <div class="desc">En qué moneda se arma el texto cuando compartes un comprobante por WhatsApp.</div>
                <details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">Elige <b>"Mostrar ambas"</b> si tus clientes a veces prefieren ver el monto en bolívares y otras veces en dólares — así no tienes que explicarlo cada vez que compartes un comprobante.</div></details>
                <select class="cfg-input" id="selInvoice"><option value="usd">Dólares (USD)</option><option value="bs">Bolívares (Bs)</option><option value="ambas">Mostrar ambas</option></select>
              </div>
              <div class="cfg-save-row"><button class="btn-primary cfg-save-btn" data-group="precios">Guardar cambios</button><span class="saved-tag" data-group="precios">✓ Guardado</span></div>
            </div>
          </details>

          <details class="cfg-group" data-group="cobros">
            <summary><div class="cg-tt"><div class="cg-name">Cobros y deudas</div><div class="cg-note">Métodos de pago activos y cómo manejas el fiado</div></div><svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><path d="m9 6 6 6-6 6"/></svg></summary>
            <div class="cfg-group-body">
              <div class="cfg-block">
                <h4>Métodos de pago</h4>
                <div class="desc">Activa los que aceptas — se muestran al cobrar.</div>
                <details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">Los métodos que desactives no van a aparecer al cobrar ni en Cajón. No pasa nada si dejas activos varios digitales (USD electrónicos, Bolívares electrónico) aunque casi siempre cobres en efectivo — uno que no usas simplemente no afecta tus cálculos.<br><br>"USD electrónicos" cubre Zelle, transferencia y cualquier otro pago digital en dólares — todo a la misma gaveta. "Bolívares electrónico" cubre pago móvil, transferencia y captahuella — todo pago digital en bolívares.</div></details>
                <div class="pay-cfg" id="payToggleList"></div>
                <div class="desc" id="payOfertaNota" style="display:none;margin-top:10px;color:var(--amber)">Con "Mostrar USD oferta" encendido (Precios y tasas), al cobrar también vas a ver "USD efectivo (oferta)" y "USD electrónicos (oferta)" — se prenden y apagan solos con ese interruptor, no hace falta activarlos acá.</div>
              </div>
              <div class="cfg-block">
                <h4>Deudas y fiado</h4>
                <div class="desc">Así maneja cada bodega el "cuánto debo": algunas fijan el monto en dólares o bolívares del día que se fió, otras simplemente cobran lo que valgan hoy esos mismos productos.</div>
                <details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body"><b>Fijo en USD / Bs BCV / Bs Paralelo:</b> el monto que fías queda congelado en esa moneda el mismo día — nunca cambia, así tú actualices tasas o precios después.<br><br><b>Se actualiza con el precio de hoy:</b> no se congela nada — cada vez que revisas la deuda se recalcula con el costo y la tasa de <b>hoy</b>. Así trabajan muchas bodegas modernas: "hoy le tocan tantos bolívares", sin importar cuándo fió.<br><br>Elige un modo "Fijo" si quieres protegerte de que el cliente se tarde en pagar y el precio suba mientras tanto; elige "Se actualiza" si prefieres simplicidad y cobrar siempre al precio de hoy.</div></details>
                <select class="cfg-input" id="selDebtMode">
                  <option value="dinamico" selected>Se actualiza con el precio de hoy de los productos</option>
                  <option value="usd">Fijo en USD contado (del día que se fió)</option>
                  <option value="bcv">Fijo en Bs a tasa BCV (del día que se fió)</option>
                  <option value="paralelo">Fijo en Bs a tasa Paralelo (del día que se fió)</option>
                </select>
              </div>
              <div class="cfg-save-row"><button class="btn-primary cfg-save-btn" data-group="cobros">Guardar cambios</button><span class="saved-tag" data-group="cobros">✓ Guardado</span></div>
            </div>
          </details>
        </div>
      </main>

      <aside class="cart" id="cart">
        <div class="handle"></div>
        <div class="cart-head">
          <div class="cart-title">Carrito<span id="cartCount">(0)</span></div>
          <button class="icon-btn" id="cartClose" aria-label="Cerrar"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
        </div>
        <div class="cart-items" id="cartItems"></div>
        <div class="cart-summary">
          <div class="curr-toggle" id="currToggle"></div>
          <div class="hero">
            <div class="hero-label">Total a cobrar</div>
            <div class="hero-value" id="heroValue">$0.00</div>
            <div class="hero-sub" id="heroSub">Agrega productos para ver el total</div>
          </div>
          <div class="seg" id="saleType" style="margin-bottom:12px"><button data-t="pagado" class="on">Pagar ahora</button><button data-t="fiado">Fiar</button></div>
          <div id="pay"></div>
          <div id="fiarPicker">
            <div class="hero-label" id="fiarLabel" style="text-align:left;margin-bottom:6px">Cliente (opcional)</div>
            <div class="search" style="margin-bottom:8px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path></svg><input id="fiarSearch" type="text" placeholder="Buscar o agregar cliente…" autocomplete="off"></div>
            <div id="fiarSelected" style="display:none"></div>
            <div id="fiarList" class="client-pick"></div>
            <button class="btn-secondary" id="fiarNewBtn" style="width:100%;text-align:center;margin-bottom:8px">+ Nuevo cliente</button>
            <div class="cfg-block" id="fiarNewForm" style="display:none;padding:14px;max-width:none">
              <div class="cfg-row"><label>Nombre</label><input class="cfg-input" id="fiarNewName" type="text" placeholder="Nombre y apellido"></div>
              <div class="cfg-row"><label>Teléfono</label><input class="cfg-input" id="fiarNewPhone" type="text" placeholder="Ej: 0414-1234567"></div>
              <div class="cfg-row"><label>Cédula o RIF (opcional)</label><input class="cfg-input" id="fiarNewIdNum" type="text" placeholder="Ej: V-12345678"></div>
              <div class="cfg-row"><label>Dirección (opcional)</label><input class="cfg-input" id="fiarNewAddress" type="text" placeholder="Ej: Calle 5, Machiques"></div>
              <div class="form-actions"><button class="btn-secondary" id="fiarNewCancel">Cancelar</button><button class="btn-primary" id="fiarNewSave">Guardar y fiar</button></div>
            </div>
          </div>
          <button class="checkout" id="checkoutBtn" disabled>Agrega productos para cobrar</button>
        </div>
      </aside>
    </div>
  </div>
</div>
<div class="backdrop" id="backdrop"></div>
<div class="mbar" id="mbar"><div class="n" id="mbarN">0</div><div class="lbl">Ver carrito</div><div class="amt" id="mbarAmt">$0.00</div></div>
<div id="printArea"></div>
<div class="confirm-overlay" id="confirmOverlay">
  <div class="confirm-backdrop" id="confirmBackdrop"></div>
  <div class="confirm-box">
    <p id="confirmMsg"></p>
    <div class="form-actions"><button class="btn-secondary" id="confirmNo">No</button><button class="btn-primary" id="confirmYes">Sí</button></div>
  </div>
</div>

<div class="confirm-overlay" id="pinOverlay">
  <div class="confirm-backdrop" id="pinBackdrop"></div>
  <div class="confirm-box">
    <p id="pinMsg"></p>
    <input class="cfg-input" id="pinInput" type="password" inputmode="numeric" autocomplete="off" placeholder="Clave de 4 dígitos o más" style="width:100%;margin-bottom:4px">
    <div class="auth-err" id="pinErr" style="margin:6px 0 0"></div>
    <div class="form-actions"><button class="btn-secondary" id="pinCancel">Cancelar</button><button class="btn-primary" id="pinOk">Continuar</button></div>
  </div>
</div>

<div class="confirm-overlay" id="cropOverlay">
  <div class="confirm-backdrop" id="cropBackdrop"></div>
  <div class="confirm-box" style="max-width:360px">
    <p style="margin-bottom:10px;font-weight:700">Encuadra la foto</p>
    <div id="cropStage">
      <img id="cropImg" alt="" draggable="false">
      <div id="cropMask"></div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;margin:12px 0 4px">
      <span style="font-size:.72rem;color:var(--text-dim)">Zoom</span>
      <input type="range" id="cropZoom" min="1" max="3" step="0.01" value="1" style="flex:1">
    </div>
    <div class="desc" style="margin-bottom:8px">Arrastra la imagen para moverla.</div>
    <div class="form-actions"><button class="btn-secondary" id="cropCancel">Cancelar</button><button class="btn-primary" id="cropOk">Usar foto</button></div>
  </div>
</div>

<!-- venta por peso (v45): pide el peso pesado en el mostrador para un producto ventaPeso, tanto
     al agregarlo por primera vez como al editar una línea ya en el carrito (openPesoModal
     precarga el input con el peso actual en ese segundo caso). -->
<div class="confirm-overlay" id="pesoOverlay">
  <div class="confirm-backdrop" id="pesoBackdrop"></div>
  <div class="confirm-box" style="max-width:340px">
    <p style="margin-bottom:2px;font-weight:700" id="pesoProdName"></p>
    <p class="desc" style="margin:0 0 12px" id="pesoProdPrecioKg"></p>
    <div class="cfg-row"><label>Peso (gramos)</label><input class="cfg-input" id="pesoGramosInput" type="number" inputmode="decimal" min="0" step="1" placeholder="Ej: 250" style="font-size:1.1rem"></div>
    <div class="desc" id="pesoSubtotalPreview" style="margin:6px 0 4px;font-weight:600"></div>
    <div class="form-actions"><button class="btn-secondary" id="pesoCancel">Cancelar</button><button class="btn-primary" id="pesoConfirm">Agregar al carrito</button></div>
  </div>
</div>

<!-- FRIXPOS:BODY-FIN -->
<!-- Ably (mensajería en tiempo real), vendorizado en el propio tema en vez de cargarlo desde
     el CDN de Ably: este POS es un solo archivo autocontenido a propósito (ver sw.js) para que
     siga instalable/offline aunque el CDN de un tercero esté caído. v2.27.0, bajado el
     2026-08-17 desde https://cdn.ably.com/lib/ably.min-2.js — para actualizarlo, repetir esa
     descarga y reemplazar astra-child/pos/ably.min.js. Si este script no carga (bloqueado,
     archivo faltante, etc.) el resto del POS sigue funcionando igual: todo lo que lo usa
     comprueba primero que exista `Ably` en window. -->
<script src="<?php echo esc_url( get_stylesheet_directory_uri() . '/pos/ably.min.js' ); ?>"></script>
<!-- FRIXPOS:SCRIPT-INICIO -->
<script>
(function(){
"use strict";
const $=id=>document.getElementById(id);

let cfg={bcv:755,paralelo:0,monedas:[],ivaGeneral:16,priceModel:'gastos',businessMode:'medio',gastosPctDefault:0,patentePct:0,islrPct:0,contribEspecial:false,mode:'dark',fontSize:'md',densityMobile:'3',densityDesktop:'5',bizName:'FrixPOS',bizRif:'',bizAddress:'',bizPhone:'',invoiceCurrency:'bs',debtMode:'dinamico',currOrder:['bs','contado','bcv'],role:'admin',adminPin:'',mostrarUsdBcv:true,mostrarUsdOferta:false,
  // permisos del CAJERO (v19) — el admin decide qué puede hacer. El admin siempre puede todo.
  // Antes (v16-v18) era rígido: el cajero veía Venta/Pedidos/Por cobrar/Clientes/Cajón y nada más.
  cajeroPerms:{pedidos:true,porcobrar:true,clientes:true,cajon:true,catalogo:false,stock:false,
               informes:false,contabilidad:false,fiar:true,devolver:false,borrarCliente:false},
  lowStockThreshold:5}; // v27 — antes era una constante fija (LOW_STOCK_THRESHOLD); ahora vive en cfg
                        // y se edita directo desde Stock, porque cada bodega vende a otra escala
                        // (bulto de 24 vs. unidad suelta) y 5 no le sirve a todo el mundo por igual.
const LOW_STOCK_DEFAULT=5; // fallback si cfg.lowStockThreshold viniera vacío/inválido
let logoDataUrl=null,activeTheme='aurora',activeCurrency='bs',activeCat='todos',cart={},cartOpen=false,currentSection='venta',saleSuccessOpen=false;
// v1.13 — logo del Catálogo Digital: en memoria hasta publicar (se sube al servidor recién al
// tocar "Publicar catálogo", no antes) — separado de logoDataUrl, que es el logo local del
// negocio y nunca sale de este teléfono.
let catLogoDataUrl=null;
// v28 — activación/sincronización con el backend. null = negocio no activado, el POS sigue
// funcionando 100% local igual que siempre; no bloquea nada.
let puntoToken=null,puntoNegocioId=null,syncing=false;
// token de CUENTA (v1.3 del backend) — separado del token de negocio de arriba. Identifica
// "quién eres" para el respaldo gratis, sin necesitar código de activación. No rota en cada
// login: dos dispositivos con la misma cuenta comparten el mismo token, para que ninguno
// deje de poder respaldar cuando el otro inicia sesión.
let puntoAccountToken=null;
// ¿esta cuenta ya tiene un negocio activo? Lo dice /login. En el teléfono de siempre esto va
// de la mano con puntoToken, pero en un teléfono NUEVO (se perdió el anterior) puntoToken no
// existe y jamás va a existir — el token de negocio solo se entrega una vez. Sin esta bandera,
// un cliente que paga volvía a ver Informes y Contabilidad bloqueados después de recuperar.
let puntoNegocioActivo=false;
// v1.12 — prueba gratis de 60 días desde que se creó la CUENTA (no el negocio, no este
// teléfono). {activo, vence, verificadoEn} — activo/vence vienen del servidor (ver
// punto_cuenta_trial_info en el plugin, calculado con SU reloj); verificadoEn es la hora LOCAL
// (Date.now()) de cuándo se confirmó por última vez, para saber si el cache ya es muy viejo.
let trialInfo=null;
// v32 — fecha (ISO) del último respaldo que ESTE dispositivo logró subir. Solo para poder decir
// "respaldaste hace X" sin pegarle al servidor en cada render del Cajón. La verdad sigue siendo
// el servidor (listarRespaldos) — esto es un recordatorio local, no la fuente de verdad.
let ultimoRespaldoOk=null;
// cuántos días sin poder reconfirmar con el servidor antes de dejar de confiar en el trial
// cacheado. Sin este límite, alguien podría atrasar el reloj de su teléfono, quedarse sin
// internet a propósito y el trial "activo" quedaría cacheado para siempre — el mismo bug que
// tenía Amazon Flex con la cancelación de bloques. Con el límite, lo peor que se gana
// engañando el reloj offline son unos días, no meses.
const TRIAL_GRACIA_DIAS=7;
function trialActivo(){
  if(!trialInfo||!trialInfo.activo)return false;
  const diasSinVerificar=(Date.now()-(trialInfo.verificadoEn||0))/86400000;
  return diasSinVerificar<=TRIAL_GRACIA_DIAS;
}
function diasRestantesTrial(){
  if(!trialInfo||!trialInfo.vence)return 0;
  const ms=new Date(trialInfo.vence.replace(' ','T')+'Z').getTime()-Date.now();
  return Math.max(0,Math.ceil(ms/86400000));
}
// negocio REALMENTE activado con código de pago — sin la prueba gratis. Úsalo para todo lo que
// de verdad toca el servidor de negocio: subir fotos, sincronizar ventas, stock multi-caja y el
// estado de "Activación" en Configuración. La prueba gratis NO paga nada de eso — solo
// desbloquea Informes y Contabilidad (ver esPremium() abajo), que se calculan 100% local.
function esNegocioActivo(){ return !!puntoToken || !!puntoNegocioActivo; }
// ¿premium SOLO por la prueba? (para distinguirlo de un código realmente activado, y mostrar el
// aviso correcto — "te quedan X días" en vez del cartel de venta normal)
function esPremiumPorTrial(){ return !esNegocioActivo() && trialActivo(); }
// única fuente de verdad de "esta instalación ve Informes/Contabilidad completos" — negocio
// pago O prueba gratis vigente. Para lo que toca el servidor de negocio, usa esNegocioActivo().
function esPremium(){ return esNegocioActivo() || trialActivo(); }
// v1.11 — de quién son los datos que hay guardados en ESTE dispositivo ahora mismo. Sin esto,
// cerrar sesión e iniciar sesión con OTRA cuenta en el mismo teléfono/navegador heredaba el
// catálogo, los clientes y las ventas de la cuenta anterior — bug real reportado por Jonathan
// probando dos cuentas seguidas en la misma pestaña. null = los datos locales no están
// reclamados por ninguna cuenta todavía (recién instalado, o se usó sin cuenta) — en ese caso
// SÍ se dejan pasar a la cuenta que entre, es lo correcto (alguien probando gratis y luego
// creando cuenta no debería perder lo que ya cargó).
let puntoAccountEmail=null;
// v1.11 — perfil de la CUENTA (quién eres), separado de cfg.bizName/bizRif/... (el NEGOCIO, que
// vive por dispositivo). Se llena al iniciar sesión/crear cuenta y se puede editar aparte en
// Configuración → Mi perfil, con su propio endpoint /perfil — antes solo se copiaba una vez a
// la Configuración del negocio y ahí se quedaba, sin forma de corregirlo después.
let perfilCuenta={nombre:'',tipo:'negocio',id_number:'',phone:'',address:'',foto_url:''};
// v1.8 — stock multi-caja (Opción B: sigue vendiendo offline siempre, se reconcilia cuando
// hay señal). stockPendiente acumula {productId: delta} desde el último /stock/mover exitoso
// — varios movimientos del mismo producto antes de poder sincronizar se suman en uno solo, no
// se manda una fila por cada venta. stockSyncDesde es el cursor de tiempo del SERVIDOR (no del
// teléfono, para no arrastrar desfases de reloj) para pedir solo lo que cambió desde la
// última vez, no la tabla completa cada vez.
let stockPendiente={}, stockSyncDesde=null;
function registrarMovimientoStock(productId,delta){
  if(!productId||!delta) return;
  stockPendiente[productId]=(stockPendiente[productId]||0)+delta;
}
// Sincronización en tiempo real (Ably), solo negocio activo — mismo candado que stock arriba.
// deviceId identifica a ESTE dispositivo (no la cuenta: dos teléfonos de la misma cuenta son
// dos deviceId distintos), para que un dispositivo no reaplique su propio cambio al recibirlo
// de vuelta. cambiosPendientes es la bitácora local a mandar a /cambios (misma idea que
// stockPendiente arriba, pero en lista porque acá cada evento es distinto, no un delta que se
// pueda sumar). cambiosSyncDesde es el cursor del SERVIDOR (id autoincremental de /cambios,
// nunca la fecha) para ponerse al día tras estar desconectado, sin importar cuánto tiempo.
let deviceId=null, cambiosPendientes=[], cambiosSyncDesde=null;
let ablyClient=null, ablyChannel=null;
function idDispositivo(){
  if(!deviceId) deviceId=uuidLite();
  return deviceId;
}
activeCurrency=(cfg.currOrder&&cfg.currOrder[0])||'bs';
let saleType='pagado',fiarClientId=null,fiarQuery='',clientQuery='',expandedClientId=null,pedidosFilter='todos',expandedOrderId=null;
let payments=[],selMethod=null; // pagos mixtos del carrito: lista de {method,label,currency,amount}, igual que Perijapp
let confirmCb=null;
// cada método de pago tiene una moneda propia — así se convierte lo que se escribe a bolívares
// v51 — pedido de Jonathan: se quitó la pantalla aparte "¿A qué precio cobra cada método?"
// (payPricing/syncPayCurrencyFromCfg). Ahora BCV y Oferta son MÉTODOS DISTINTOS en la lista, no
// una configuración escondida sobre el mismo método — el cajero elige "USD electrónicos" o "USD
// electrónicos (oferta)" directo al cobrar. Los dos métodos "(oferta)" no se prenden a mano: solo
// existen mientras "Mostrar USD oferta" (Precios y tasas) esté encendido (ver methodEnabled) — se
// esconden solos si se apaga, sin dejar un método fantasma activado por error.
// Captahuella se eliminó como método aparte: ahora es parte de "Bolívares electrónico" (junto con
// pago móvil y transferencia) — todo pago digital en bolívares es la misma gaveta.
const PAY_METHODS_OFERTA=['efectivo_usd_oferta','usd_electronico_oferta'];
function methodEnabled(m){ return PAY_METHODS_OFERTA.includes(m.id) ? cfg.mostrarUsdOferta===true : m.on; }
const PAY_CURRENCY_DEFAULT={bolivares_electronico:'bs',efectivo_bs:'bs',efectivo_usd_bcv:'usd_bcv',usd_electronico_bcv:'usd_bcv',efectivo_usd_oferta:'usd',usd_electronico_oferta:'usd'};
const payCurrency=Object.assign({},PAY_CURRENCY_DEFAULT);

const ic={
  venta:'<svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 2 3 14h7l-1 8 10-12h-7l1-8z"/></svg>',
  pedidos:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="18" rx="2"/><line x1="8" y1="8" x2="16" y2="8"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="8" y1="16" x2="13" y2="16"/></svg>',
  porcobrar:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>',
  clientes:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.4"/><path d="M4.5 20c1-4 4-6 7.5-6s6.5 2 7.5 6"/></svg>',
  catalogo:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11.5V5a2 2 0 0 1 2-2h6.5L21 11.5 12.5 20 3 11.5z"/><circle cx="8" cy="8" r="1.3" fill="currentColor" stroke="none"/></svg>',
  stock:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8 12 3 3 8v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>',
  cajon:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/><circle cx="17" cy="14.5" r="1.2" fill="currentColor" stroke="none"/></svg>',
  informes:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="20" x2="6" y2="12"/><line x1="12" y1="20" x2="12" y2="7"/><line x1="18" y1="20" x2="18" y2="3"/></svg>',
  contabilidad:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/></svg>',
  config:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><line x1="4" y1="6" x2="20" y2="6"/><circle cx="9" cy="6" r="2.2" fill="currentColor" stroke="none"/><line x1="4" y1="12" x2="20" y2="12"/><circle cx="15" cy="12" r="2.2" fill="currentColor" stroke="none"/><line x1="4" y1="18" x2="20" y2="18"/><circle cx="7" cy="18" r="2.2" fill="currentColor" stroke="none"/></svg>',
  tasas:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3v14M7 17l-3.5-3.5M7 17l3.5-3.5"/><path d="M17 21V7M17 7l3.5 3.5M17 7l-3.5 3.5"/></svg>',
};
const sections=[
  {id:'venta',label:'Venta',icon:ic.venta,type:'venta'},
  // v37 — pedido de Jonathan: es lo que más se toca en el día a día (la tasa cambia seguido),
  // así que va de segundo, no escondido dentro de Configuración → Precios y tasas.
  {id:'tasas',label:'Tasas',icon:ic.tasas,type:'tasas',adminOnly:true,desc:'BCV, Paralelo y tus monedas propias — actualízalas en un toque.'},
  {id:'pedidos',label:'Pedidos',icon:ic.pedidos,type:'pedidos',desc:'Historial de todas las ventas. Reimprime o reenvía cualquier recibo.'},
  {id:'porcobrar',label:'Por cobrar',icon:ic.porcobrar,type:'porcobrar',badge:'debt',desc:'Fiados agrupados por cliente, con saldo y botón para cobrar.'},
  {id:'clientes',label:'Clientes',icon:ic.clientes,type:'clientes',desc:'Directorio con historial de compras y deudas.'},
  {id:'catalogo',label:'Inventario',icon:ic.catalogo,type:'catalogo',adminOnly:true,desc:'Agrega productos con la calculadora de costo: bulto, IVA, gasto extra y % de ganancia.'},
  {id:'stock',label:'Stock',icon:ic.stock,type:'stock',adminOnly:true,badge:'lowstock',desc:'Inventario en tiempo real, negativos en rojo arriba.'},
  {id:'cajon',label:'Cajón',icon:ic.cajon,type:'cajon',desc:'Turno de caja: monto inicial, ventas por método y arqueo al cerrar.'},
  {id:'informes',label:'Informes',icon:ic.informes,type:'informes',adminOnly:true,desc:'Ventas por día, semana y mes — por método y por producto.'},
  {id:'contabilidad',label:'Contab.',icon:ic.contabilidad,type:'contabilidad',adminOnly:true,desc:'Ganancia neta real, IVA ante el SENIAT, ingresos y egresos del negocio.'},
  {id:'config',label:'Config.',icon:ic.config,type:'config'},
];
const themes=[
  {id:'aurora',name:'Aurora',a:'#3E7BFA',b:'#9B5CF6'},
  {id:'ambar',name:'Ámbar',a:'#FFB443',b:'#FF6B4A'},
  {id:'esmeralda',name:'Esmeralda',a:'#22C55E',b:'#0EA5A4'},
  {id:'coral',name:'Coral',a:'#FF6B9D',b:'#C026D3'},
  {id:'medianoche',name:'Medianoche',a:'#3E7BFA',b:'#22D3EE'},
  {id:'grafito',name:'Grafito',a:'#9CA3AF',b:'#E5E7EB'},
];
// v50 — pedido de Jonathan: por defecto solo 3 métodos activos (USD efectivo, Zelle, y el que
// era "Pago móvil" ahora relabeleado "Bolívares electrónico" porque en la práctica cubre pago
// móvil, transferencia y captahuella — todo lo que es "me pagaron en Bs sin billete físico"). El
// resto (Efectivo Bs, Binance, Captahuella suelto) queda apagado, prendible desde Configuración.
// v51 — Binance ya no es un método fijo: ahora es solo un EJEMPLO de moneda propia que se agrega
// en Personalizado → Otras monedas (junto con COP, Euro, etc.), igual que cualquier otra.
const payMethods=[
  {id:'bolivares_electronico',label:'Bolívares electrónico',on:true},
  {id:'efectivo_bs',label:'Efectivo Bs',on:false},
  {id:'efectivo_usd_bcv',label:'USD efectivo',on:true},
  {id:'usd_electronico_bcv',label:'USD electrónicos',on:true},
  {id:'efectivo_usd_oferta',label:'USD efectivo (oferta)',on:false},
  {id:'usd_electronico_oferta',label:'USD electrónicos (oferta)',on:false},
];
// paleta cíclica para asignar color a cada categoría por su posición
const catPalette=['#3E7BFA','#33D690','#9B5CF6','#FFB443','#FF6B6B','#22C3E6','#E672C4','#7C9CFF','#4CC38A','#F6C445','#C77DFF','#FF8A5B','#5BD1D7','#B5651D','#9AA0FF','#FF5EA0','#6BCB77','#FFA600','#845EF7','#20C997'];
// categorías padre estándar de bodega/minimarket venezolano (editable: se pueden agregar más)
let categories=[
  {id:'viveres',label:'Víveres y granos'},
  {id:'harinas',label:'Harinas y pastas'},
  {id:'enlatados',label:'Enlatados y conservas'},
  {id:'aceites',label:'Aceites y salsas'},
  {id:'cafe_azucar',label:'Café, azúcar y endulzantes'},
  {id:'bebidas',label:'Bebidas y refrescos'},
  {id:'licores',label:'Licores y cervezas'},
  {id:'lacteos',label:'Lácteos y huevos'},
  {id:'charcuteria',label:'Charcutería y embutidos'},
  {id:'carnes',label:'Carnes y pollo'},
  {id:'frutas_verduras',label:'Frutas y verduras'},
  {id:'panaderia',label:'Panadería y dulcería'},
  {id:'snacks',label:'Snacks y golosinas'},
  {id:'limpieza',label:'Limpieza del hogar'},
  {id:'cuidado',label:'Cuidado personal'},
  {id:'bebe',label:'Bebé y pañales'},
  {id:'mascotas',label:'Mascotas'},
  {id:'papeleria',label:'Papelería y varios'},
  {id:'bultos',label:'Bultos y mayor'},
];
function catLabelOf(id){ const c=categories.find(x=>x.id===id); return c?c.label:id; }
function catColorOf(id){ const i=categories.findIndex(x=>x.id===id); return catPalette[(i<0?0:i)%catPalette.length]; }
// compat: código viejo usa catLabel[x] / catColor[x] — los dejamos como proxies sobre las funciones
const catLabel=new Proxy({},{get:(_,k)=>catLabelOf(k)});
const catColor=new Proxy({},{get:(_,k)=>catColorOf(k)});
let productSeq=100,editingProductId=null,expandedStockId=null;
// v37 — sin datos de muestra: la app arranca vacía de verdad. Antes traía 12 productos y 4
// clientes inventados (Harina PAN, María Pérez...) — a un negocio real le tocaba borrarlos uno
// por uno antes de poder usar FrixPOS en serio, y en más de un caso se le quedaba alguno mezclado
// con su catálogo real. "Empezar de cero" (Configuración → Negocio) sigue existiendo para
// quien venga de una versión vieja con estos datos ya guardados.
const products=[];
function catsForChips(){ return ['todos'].concat(categories.filter(c=>products.some(p=>p.cat===c.id)).map(c=>c.id)); }

let clientSeq=100,orderSeq=100;
let clients=[];
let orders=[];
// _today la sigue usando fmtDate() más abajo ("Hoy"/"Ayer") — no era exclusiva de los datos
// de muestra que se acaban de quitar de aquí arriba.
const _today=new Date();

// ===== Cajón (turno de caja) =====
// un solo turno abierto a la vez. openTurno={id, abierto, inicio, fondo:{usd,bs,cop}}.
// Las ventas hechas mientras hay turno abierto guardan su .turnoId para poder desglosar el turno.
let turnoSeq=1, openTurno=null, turnosCerrados=[];
// v31 — un registro por TRANSACCIÓN de abono (no por pedido que tocó, que puede ser varios si
// el abono alcanzó para más de una deuda vieja) — con el desglose de pago completo (mismo shape
// que o.paymentsBreakdown), para poder repartirlo dentro de "Ventas del turno por método" en el
// Cajón. o.abonos[] (por pedido) sigue existiendo igual que antes, es solo la nota legible.
let abonoPagos=[];

function fmtUSD(n){return '$'+(n||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}
function fmtBs(n){return Math.round(n||0).toLocaleString('es-VE')+' Bs'}
// ===== MONEDAS PERSONALIZADAS (v5) =====
// Antes había un slot fijo de "pesos" cableado en medio código: una sola moneda extra, con
// nombre colombiano quemado. Ahora el negocio crea las que necesite, cada una con su nombre y
// su tasa RESPECTO AL DÓLAR PARALELO (no al BCV: quien maneja otra moneda la cambia contra el
// dólar de la calle, no contra el oficial).
// Forma: cfg.monedas = [{id:'m1', nombre:'Pesos', tasa:3500, redondeo:100}]
// REGLA DURA: sin dólar paralelo cargado, ninguna moneda personalizada está activa — no habría
// contra qué valuarlas, y dejarlas "medio activas" daría precios en cero sin avisar.
function monedasActivas(){
  if(!(cfg.paralelo>0))return [];
  return (cfg.monedas||[]).filter(m=>m&&m.tasa>0);
}
function monedaById(id){ return (cfg.monedas||[]).find(m=>m&&m.id===id)||null; }
function monedaActivaById(id){ return monedasActivas().find(m=>m.id===id)||null; }
function nuevoMonedaId(){ let i=1; while(monedaById('m'+i))i++; return 'm'+i; }
function fmtMontoMoneda(amount,currency){
  if(currency==='usd')return fmtUSD(amount);
  if(currency==='usd_bcv')return fmtUSD(amount)+' BCV';
  const m=monedaById(currency);
  if(m)return fmtMonedaVal(amount,m);
  return fmtBs(amount);
}
// v1.18 — si la moneda NO tiene redondeo configurado, se muestran sus decimales (igual que el
// dólar). Antes esto SIEMPRE redondeaba a entero: una ganancia chica en una moneda "grande"
// (ej. Euros, tasa cerca de 1) se veía como "0" — parecía que no hubo ganancia en esa moneda,
// cuando sí la hubo, solo que era una fracción. Si la moneda SÍ tiene redondeo (ej. pesos, de
// a 100), bsToMonedaRaw ya la entregó redondeada — mostrarla tal cual no cambia nada ahí.
function fmtMonedaVal(n,m){
  const val=n||0;
  const texto=(m&&m.redondeo>0)
    ? Math.round(val).toLocaleString('es-VE')
    : val.toLocaleString('es-VE',{minimumFractionDigits:2,maximumFractionDigits:2});
  return texto+' '+((m&&m.nombre)||'');
}
// migración desde v4: quien tenía "pesos" configurados no pierde nada — pasan a ser una moneda
// personalizada llamada Pesos, con su misma tasa y su mismo redondeo.
function migrarMonedasV4(){
  if(!Array.isArray(cfg.monedas))cfg.monedas=[];
  if(cfg.cop>0 && !cfg.monedas.length){
    cfg.monedas.push({id:'m1',nombre:'Pesos',tasa:cfg.cop,redondeo:cfg.copRound||0});
  }
  delete cfg.cop; delete cfg.copRound;
  if(Array.isArray(cfg.currOrder)) cfg.currOrder=cfg.currOrder.map(id=>id==='cop'?'m1':id);
  if(cfg.debtMode==='cop')cfg.debtMode='m1';
  syncMonedaPayMethods();
}
// cada moneda personalizada necesita poder cobrarse EN EFECTIVO y tener su gaveta en el Cajón.
// Si no, podrías fijar precios en esa moneda pero no cobrarlos: la app quedaría coja.
function syncMonedaPayMethods(){
  const activas=monedasActivas();
  activas.forEach(m=>{
    const mid='efectivo_'+m.id;
    payCurrency[mid]=m.id;
    const ex=payMethods.find(x=>x.id===mid);
    if(ex){ ex.label='Efectivo '+m.nombre; }
    else { payMethods.push({id:mid,label:'Efectivo '+m.nombre,on:false}); }
  });
  for(let i=payMethods.length-1;i>=0;i--){
    const id=payMethods[i].id;
    if(id.indexOf('efectivo_m')===0 && !activas.find(m=>('efectivo_'+m.id)===id)){
      delete payCurrency[id];
      payMethods.splice(i,1);
    }
  }
}
function norm(s){return(s||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'')}
function matchesQuery(str,q){return norm(str).includes(norm(q))}
// pagos mixtos — convierte un monto escrito en su moneda propia a bolívares (a la tasa base del
// MODELO DE PRECIOS ACTIVO: BCV en el modelo "costo+gastos+ganancia", Paralelo en el de "margen")
// para poder sumarlo contra el total de la venta. Al delegar en rateBsForPrices(), cambiar de
// modelo en Configuración se propaga solo a pagos, deudas, Cajón y todos los conversores.
function rateBsActual(){ return rateBsForPrices(); }
// ¿tiene sentido mostrar el "dólar BCV" (vitrina) aparte del "USD oferta" (contado, dólar real)?
// Solo si hay algo que diferenciar (BCV y Paralelo cargados a la vez — si falta uno, son el mismo
// número) y el negocio no lo apagó a propósito en Configuración → Precios y tasas. v27 — antes
// esto vivía hardcoded en false porque con un solo dólar (BCV) mostrar los dos habría dicho lo
// mismo dos veces; ahora que el ancla es el dólar real, sí son números distintos y mostrar ambos
// es justamente el pedido: "USD BCV + Bolívares" en el estante, "USD oferta" para quien paga cash.
function showBcvSeparately(){ return cfg.bcv>0 && cfg.paralelo>0 && cfg.mostrarUsdBcv!==false; }
function convertToBs(amount,currency){
  const n=parseFloat(amount)||0;
  if(!n)return 0;
  if(currency==='usd')return n*rateBsActual();                       // USD efectivo/digital = contado
  if(currency==='usd_bcv')return cfg.bcv>0? n*cfg.bcv : 0;            // pago en "dólar BCV" (más alto)
  const mc=monedaActivaById(currency);
  if(mc){ const rp=rateParaleloParaMonedas(); return rp>0? n/mc.tasa*rp : 0; }  // moneda propia -> $ paralelo -> Bs
  return n; // 'bs'
}
// inversos — para mostrar "Falta"/"Vuelto" en cada moneda y para el botón "Exacto"
function bsToUsdRaw(bs){ const r=rateBsActual(); return r>0 ? bs/r : 0; }   // -> contado/USD
function bsToBcvRaw(bs){ return cfg.bcv>0 ? bs/cfg.bcv : 0; }               // -> dólar BCV
// Bs -> moneda personalizada. Pasa por el dólar porque la tasa está expresada contra el dólar.
// La tasa de una moneda propia está expresada contra el DÓLAR PARALELO — quien maneja pesos o
// reales los cambia contra el dólar de la calle, no contra el oficial. El bolívar de vitrina,
// en cambio, sale a BCV. Son dos dólares distintos y hay que respetarlo: se pasa de Bs a
// dólares PARALELOS y de ahí a la moneda. Si se usara el BCV acá, un cliente que paga en pesos
// estaría pagando de más o de menos según el spread, sin que nadie se diera cuenta.
function rateParaleloParaMonedas(){ return cfg.paralelo>0?cfg.paralelo:0; }
function bsToMonedaRaw(bs,m){
  if(!m||!(m.tasa>0))return null;
  const r=rateParaleloParaMonedas();
  if(!(r>0))return null;
  let v=(bs/r)*m.tasa;
  if(m.redondeo>0)v=Math.ceil(v/m.redondeo)*m.redondeo;
  return v;
}
// margen de tolerancia para considerar un pago "cubierto": normalmente medio bolívar, pero si
// el pago se hizo en una moneda con su propio redondeo (centavos de $, pesos enteros), ESE
// redondeo por sí solo puede meter varios bolívares de diferencia que no son un faltante real
// (ej: $2.44 con tasa 755 son 1842.2 Bs, pero el pago se escribe en centavos -> pequeño residuo).
// El margen crece según qué monedas se usaron en los pagos de esta venta/abono en concreto.
function paymentToleranceBs(list){
  let eps=0.5;
  const rate=rateBsActual();
  (list||[]).forEach(p=>{
    if(p.currency==='usd'&&rate>0) eps=Math.max(eps, rate*0.005);            // medio centavo de $ oferta en Bs
    // v50 — bug real: faltaba esta rama. Un pago en 'usd_bcv' (hoy el default) también se escribe
    // redondeado a centavos de dólar, pero a la tasa BCV — que suele ser MÁS alta que la de
    // oferta/Paralelo, así que medio centavo ahí pesa más Bs de lo que cubre el margen genérico de
    // 0.5 Bs. Sin esto, "Exacto" en un método a precio BCV terminaba mostrando "Falta"/"Vuelto"
    // de 1-3 Bs en ventas que sí estaban bien pagadas — mismo criterio que ya usa 'usd' arriba.
    if(p.currency==='usd_bcv'&&cfg.bcv>0) eps=Math.max(eps, cfg.bcv*0.005);
    const mt=monedaActivaById(p.currency);
    const rpT=rateParaleloParaMonedas();
    if(mt&&rpT>0){
      // v1.22 — si hay un paso de redondeo configurado (ej. pesos de a 100), ESE es el "centavo"
      // de esa moneda: medio paso, en Bs, es tolerancia razonable. Antes, SIN redondeo
      // configurado, se asumía "1 unidad completa" como paso mínimo — bug real con monedas
      // fuertes (Euro a tasa ~1): media unidad ENTERA puede ser cientos de bolívares, y ese
      // margen se tragaba faltantes de verdad como si fueran solo redondeo. Sin redondeo
      // configurado se trata como moneda con decimales (mismo criterio que ya usa el dólar
      // arriba): medio CENTAVO de esa moneda, no media unidad completa.
      const paso=mt.redondeo>0?mt.redondeo:0.01;
      eps=Math.max(eps, rpT/mt.tasa*paso*0.5);
    }
  });
  return eps;
}

// arma un renglón con las monedas configuradas — para mostrar una deuda/saldo en todo lo que aplique.
// Bs (paralelo) · $ oferta · $ BCV · COP — el sufijo "oferta" solo aparece cuando el BCV se
// muestra aparte (showBcvSeparately()); si no hay BCV+Paralelo a la vez, "$" es el único dólar
// posible y etiquetarlo sería ruido.
function multiCurrencyLine(bs){
  const parts=[fmtBs(bs)];
  const bcvAparte=showBcvSeparately();
  const mostrarOferta=cfg.mostrarUsdOferta!==false;
  if(mostrarOferta&&rateBsActual()>0)parts.push(fmtUSD(bsToUsdRaw(bs))+(bcvAparte?' oferta':''));
  if(bcvAparte)parts.push(fmtUSD(bsToBcvRaw(bs))+' BCV');
  monedasActivas().forEach(m=>{
    const v=bsToMonedaRaw(bs,m);
    if(v!==null)parts.push(fmtMonedaVal(v,m));
  });
  return parts.join(' · ');
}
// igual que multiCurrencyLine pero sin el Bs. — para acompañar un monto en Bs. que ya se muestra en grande
function otherCurrenciesLine(bs){
  const parts=[];
  const bcvAparte=showBcvSeparately();
  const mostrarOferta=cfg.mostrarUsdOferta!==false;
  if(mostrarOferta&&rateBsActual()>0)parts.push(fmtUSD(bsToUsdRaw(bs))+(bcvAparte?' oferta':''));
  if(bcvAparte)parts.push(fmtUSD(bsToBcvRaw(bs))+' BCV');
  monedasActivas().forEach(m=>{
    const v=bsToMonedaRaw(bs,m);
    if(v!==null)parts.push(fmtMonedaVal(v,m));
  });
  return parts.join(' · ');
}
// el monto (en Bs, para la contabilidad interna) de una deuda, según selDebtMode:
// - 'bcv'/'paralelo'/'cop': queda CONGELADO al valor del día que se fió
// - 'usd': se congela el $ contado del día que se fió, pero el Bs se recalcula con la tasa de HOY
// - 'dinamico': no se congela nada — se recalcula con el costo/margen de HOY de cada producto
// Nota: en el modelo actual el Bs del sistema ya está a tasa Paralelo (priceBs = contado × Paralelo).
function debtBs(o){
  const mode=cfg.debtMode||'paralelo';
  if(mode==='dinamico'){
    return o.items.reduce((s,it)=>{
      const p=products.find(x=>x.id===it.productId);
      return s+(p?effectivePrices(p).bs:it.priceBs)*it.qty;
    },0);
  }
  if(mode==='usd'){
    if(!o.items.some(it=>it.priceUsd!=null))return o.totalBs; // pedidos viejos sin ese dato: se respeta lo congelado en Bs
    const usd=o.items.reduce((s,it)=>s+(it.priceUsd||0)*it.qty,0); // priceUsd = contado/USD
    const r=rateBsActual();
    return r>0?usd*r:o.totalBs;
  }
  if(mode==='bcv'){
    // congelar en "dólar BCV" del día: reexpresar el Bs congelado a BCV de ese día no lo tenemos,
    // así que usamos el Bs congelado tal cual (ya lleva la tasa del día que se fió).
    return o.totalBs;
  }
  const mDeuda=monedaActivaById(mode);
  if(mDeuda){
    // v27 — bug encontrado al verificar el ancla de precios: esto leía it.priceCop, un campo
    // legacy que el checkout congela SIEMPRE en 0 desde v5 (las monedas propias quedaron en
    // it.priceMonedas{}, ver checkoutBtn). Con priceCop siempre en 0 (nunca null), la condición
    // de "pedido viejo sin ese dato" nunca disparaba y la deuda fiada en una moneda propia
    // quedaba calculada en 0 — parecía saldada sin estarlo. Ahora lee priceMonedas[m.id], el
    // campo que sí se congela de verdad por línea.
    if(!o.items.some(it=>it.priceMonedas&&it.priceMonedas[mDeuda.id]!=null))return o.totalBs;
    const montoMoneda=o.items.reduce((s,it)=>s+((it.priceMonedas&&it.priceMonedas[mDeuda.id])||0)*it.qty,0);
    const rpD=rateParaleloParaMonedas();
    return (mDeuda.tasa>0&&rpD>0)?montoMoneda/mDeuda.tasa*rpD:o.totalBs;
  }
  return o.totalBs; // 'bcv' — congelado, el comportamiento de siempre
}

// ===== MOTOR DE PRECIOS — ANCLA EN EL DÓLAR REAL (v27) =====
// Costo + gastos + ganancia + IVA se calculan TODOS en dólar REAL (Paralelo — el de la calle,
// al que de verdad te cambian bolívares/pesos por dólares). Ese resultado es "USD oferta"
// (contado), y es el ancla única de toda la app: venta, carrito, deudas, Cajón, Contabilidad.
//   montoGastos    = costoUnit × %gastosOperativos
//   montoGanancia  = costoUnit × %ganancia
//   baseImponible  = costoUnit + montoGastos + montoGanancia
//   ivaDebito  = ¿Sujeto a IVA? ? baseImponible × IVA general : 0    (IVA cobrado al cliente)
//   contado    = baseImponible + ivaDebito                            (USD oferta — precio ancla real)
//   ivaCredito = ¿Sujeto a IVA? ? costoUnit × IVA general : 0         (IVA ya pagado al proveedor, ESTIMADO por costeo)
//   diferenciaSeniat = ivaDebito − ivaCredito                         (diferencia ESTIMADA por este producto)
//
// El bolívar sale multiplicando `contado` por la tasa Paralelo (cae a BCV si no hay Paralelo
// cargado, para no dejar todo en cero). El "USD BCV" de vitrina/factura formal (`bcv`, ver
// computePrices) es un número DERIVADO hacia afuera, no una segunda ancla: infla `contado` por
// Paralelo/BCV para que, multiplicado otra vez por BCV, dé exactamente el mismo bolívar que ya
// se cobró — es la forma legal de mostrar la etiqueta en dólares oficiales sin regalar margen.
//
// v27 — CAMBIO DE ANCLA (antes salía a BCV primero, Paralelo caía a fallback): con BCV como
// ancla, comprar el mismo costo real en efectivo USD daba un bolívar distinto (y más barato)
// que comprarlo en bolívares, porque solo la rama "compra en Bs" de calcCostBcv() pasaba por
// esa conversión — inconsistencia real entre productos, no solo un matiz. calcCostBcv(),
// computePrices() y preciosEnMonedas() (COP/EUR/etc., que ya se valúan contra Paralelo, nunca
// contra BCV) no cambiaron de fórmula: las tres ya delegaban en rateBsForPrices() como fuente
// única, tal como promete este comentario — así que invertir la prioridad ahí corrige costo,
// precio, IVA y monedas propias a la vez, sin tocar el resto del motor.
//
// v26 — OJO: `ivaCredito`/`diferenciaSeniat` es un número de COSTEO (para Inventario y el
// preview del producto), calculado del costo de lo vendido. NO es el IVA Crédito Fiscal real que
// se declara ante el SENIAT — ese nace únicamente de las facturas de compra registradas en
// Contabilidad → Egresos (`e.ivaFacturaBs`). En la UI, este número se muestra como "IVA estimado
// en costos"; el real se llama "IVA Crédito Fiscal (facturas de compras)". Ver contabData().

// tasa a la que sale el bolívar — ÚNICA fuente de verdad para precios, costo, deudas y pagos.
// Paralelo manda si está cargado (es el dólar real de calle); si no, cae a BCV para no dejar
// todo en cero. Nunca al revés — ver el comentario de arriba.
function rateBsForPrices(){ return cfg.paralelo>0?cfg.paralelo:(cfg.bcv||0); }
// gavetas de efectivo del arqueo: siempre USD y Bs, más una por cada moneda propia activa.
function nuevoEfectivo(){
  const o={usd:0,bs:0};
  monedasActivas().forEach(m=>{ o[m.id]=0; });
  return o;
}
// PVP en cada moneda propia. 'contado' YA es el USD oferta (dólar real/Paralelo, ver el motor de
// precios arriba) — cada moneda propia se valúa multiplicando directo por su tasa (que también
// está expresada contra el Paralelo, nunca contra el BCV: quien maneja pesos o reales los cambia
// contra el dólar de la calle). v27 — antes 'contado' llegaba anclado a BCV y esta función tenía
// que subir a bolívares y volver a bajar por el paralelo para no mezclar los dos dólares; con
// 'contado' ya anclado al real ese rodeo sobraba (monedasActivas() ya exige Paralelo cargado, así
// que multiplicar directo es lo mismo que hacía el rodeo, sin la vuelta innecesaria).
function preciosEnMonedas(contado){
  const out={};
  monedasActivas().forEach(m=>{
    let v=contado*m.tasa;
    if(m.redondeo>0 && v>0) v=Math.ceil(v/m.redondeo)*m.redondeo;
    out[m.id]=v;
  });
  return out;
}
function computePrices(costUsd,marginPct,ivaSubject,gastosPct){
  const cost=costUsd||0;
  const gp=(gastosPct==null?(cfg.gastosPctDefault||0):gastosPct)/100;
  const baseImponible=cost + cost*gp + cost*((marginPct||0)/100);   // costo + gastos + ganancia
  const ivaRate=(cfg.ivaGeneral||0)/100;
  const ivaDebito=ivaSubject? baseImponible*ivaRate : 0;
  const ivaCredito=ivaSubject? cost*ivaRate : 0;
  const contado=baseImponible+ivaDebito;                         // PVP Final — precio ancla
  const rateBs=rateBsForPrices();
  const bs=contado*rateBs;
  const bcv=cfg.bcv>0? bs/cfg.bcv : contado;
  const monedas=preciosEnMonedas(contado);
  const montoGastos=cost*gp;
  const montoGanancia=cost*((marginPct||0)/100);
  return{bs,bcv,contado,monedas,baseImponible,ivaDebito,ivaCredito,diferenciaSeniat:ivaDebito-ivaCredito,montoGastos,montoGanancia};
}
// precio de oferta (v16, opcional por producto): si p.offerPrice>0, ESE es el contado/USD real
// de venta (reemplaza el calculado por costo+margen) — el resto de monedas se recalculan a partir
// de él con la misma lógica de computePrices, para que Bs/BCV/COP sigan siendo consistentes entre sí.
// v17: si el producto está sujeto a IVA, el precio de oferta se trata como PVP Final (ya con IVA
// incluido, es el precio de vitrina) y se "destapa" hacia atrás para seguir teniendo Base
// Imponible/IVA Débito/Crédito correctos para el control fiscal, aunque el precio esté rebajado.
// v1.21 — modo Simple: convierte lo que el usuario escribió (en la moneda que eligió) a
// 'contado', el mismo ancla real/Paralelo que usa todo el motor de precios — mismo tratamiento
// de fondo que ya usa offerPrice, solo que acá el número de entrada puede venir en Paralelo,
// BCV o Bolívares, no solo en dólar real. Si viene en BCV, hay que "bajarlo" al real: BCV está
// inflado por Paralelo÷BCV respecto al real (ver rateBsForPrices/computePrices), así que se
// revierte esa misma proporción.
function simplePrecioAContado(precio,moneda){
  const v=parseFloat(precio)||0;
  if(v<=0)return 0;
  if(moneda==='bs'){ const r=rateBsForPrices(); return r>0? v/r : 0; }
  if(moneda==='bcv'){ const rateBs=rateBsForPrices(); return (cfg.bcv>0&&rateBs>0)? v*cfg.bcv/rateBs : 0; }
  return v; // 'paralelo' — ya es el ancla real, tal cual
}
// v1.21 — precio SIN el descuento de oferta, según el modelo del producto. Separado de
// effectivePrices() para poder usarlo como ".original" (el tachado) cuando SÍ hay oferta,
// y como el resultado normal cuando no la hay — una sola fuente para las dos ramas.
function basePriceOf(p){
  if(p.pricingMode==='simple'){
    const x=simplePrecioAContado(p.simplePrecio,p.simpleMoneda||'paralelo');
    const ivaRate=(cfg.ivaGeneral||0)/100;
    const ivaSubject=!!p.ivaSubject;
    let baseImponible,ivaDebito,contado;
    if(!ivaSubject){
      baseImponible=x; ivaDebito=0; contado=x;
    } else if(p.simpleIvaIncluido!==false){ // por defecto 'sí': lo escrito YA es el precio final
      contado=x; baseImponible=ivaRate>0? x/(1+ivaRate) : x; ivaDebito=contado-baseImponible;
    } else { // 'no': lo escrito es la base sin IVA, se le suma encima
      baseImponible=x; ivaDebito=x*ivaRate; contado=baseImponible+ivaDebito;
    }
    const rateBs=rateBsForPrices();
    const bs=contado*rateBs;
    const bcv=cfg.bcv>0? bs/cfg.bcv : contado;
    const monedas=preciosEnMonedas(contado);
    // montoGanancia queda en null (no en 0, que mentiría "ganancia cero") — no hay costo con
    // qué calcularla. sinCosto:true avisa a quien consuma este resultado que falta ese dato.
    return{bs,bcv,contado,monedas,baseImponible,ivaDebito,ivaCredito:0,diferenciaSeniat:ivaDebito,montoGastos:0,montoGanancia:null,sinCosto:true};
  }
  return computePrices(p.costBcv,p.margin,!!p.ivaSubject,p.gastosPct);
}
function effectivePrices(p){
  const base=basePriceOf(p);
  // "Precio de oferta" es un descuento TEMPORAL y manda sin importar el modelo de precio de
  // fondo (Simple o Personalizado) — por eso se revisa después de calcular base, pero antes de
  // decidir qué devolver. Usa costBcv||0 (no p.costBcv a secas) porque en modo Simple ese campo
  // no existe: sin esto, un producto Simple con oferta activa calculaba ivaCredito como NaN.
  if(p.offerPrice>0){
    const rateBs=rateBsForPrices();
    const contado=p.offerPrice;
    const ivaRate=(cfg.ivaGeneral||0)/100;
    const baseImponible=p.ivaSubject? contado/(1+ivaRate) : contado;
    const ivaDebito=contado-baseImponible;
    const ivaCredito=(p.ivaSubject&&p.pricingMode!=='simple')? (p.costBcv||0)*ivaRate : 0;
    const bs=contado*rateBs;
    const bcv=cfg.bcv>0? bs/cfg.bcv : contado;
    const monedas=preciosEnMonedas(contado);
    const montoGanancia=p.pricingMode==='simple'? null : baseImponible-(p.costBcv||0);
    return{bs,bcv,contado,monedas,baseImponible,ivaDebito,ivaCredito,diferenciaSeniat:ivaDebito-ivaCredito,montoGastos:0,montoGanancia,original:base,onOffer:true,sinCosto:p.pricingMode==='simple'};
  }
  return Object.assign({},base,{onOffer:false});
}
function displayPrice(p){ return fmtUSD(effectivePrices(p).contado); }
// unidad de venta (v16, opcional): peso en gramos o volumen en ml, guardado normalizado como
// p.unitValueBase. parseUnitValue acepta con o sin sufijo ("750", "750 g", "1 kg", "1.5 Kg", "500ml",
// "1.5 L") — si no hay sufijo, un valor chico (<20) se asume Kg/L y uno grande se asume g/ml, que es
// como suele escribirse el tamaño de un producto de bodega (pedido explícito de Jonathan).
function parseUnitValue(raw,type){
  if(!raw)return null;
  const s=raw.trim().toLowerCase().replace(',','.');
  const m=s.match(/^([\d.]+)\s*(kgs?|kilos?|gr?s?|gramos?|lt?s?|litros?|ml)?$/);
  if(!m)return null;
  const val=parseFloat(m[1]);
  if(isNaN(val)||val<=0)return null;
  const unit=m[2]||'';
  const isBig=unit.startsWith('k')||unit.startsWith('l')&&!unit.startsWith('lt');
  if(type==='peso'){
    if(unit.startsWith('k'))return val*1000;
    if(unit==='g'||unit.startsWith('gr')||unit.startsWith('gram'))return val;
    return val<20? val*1000 : val; // sin sufijo: heurística por magnitud
  }else{
    if(unit==='l'||unit.startsWith('lt')||unit.startsWith('litro'))return val*1000;
    if(unit==='ml')return val;
    return val<20? val*1000 : val;
  }
}
function formatUnitLabel(p){
  if(!p||!p.unitType||p.unitType==='unidad'||!p.unitValueBase)return '';
  if(p.unitType==='peso')
    return p.unitValueBase>=1000 ? (Math.round((p.unitValueBase/1000)*100)/100)+' Kg' : Math.round(p.unitValueBase)+' g';
  return p.unitValueBase>=1000 ? (Math.round((p.unitValueBase/1000)*100)/100)+' L' : Math.round(p.unitValueBase)+' ml';
}
// venta por peso (v45): cart[id]/it.qty para un producto ventaPeso guarda KILOGRAMOS (decimal),
// no una cantidad de unidades — así el precio configurado (interpretado como precio POR KG) sigue
// multiplicando qty igual que en cualquier otro producto, en todas las fórmulas existentes
// (checkout, informes, recibo, stock...), sin tocar ni una de ellas. formatPeso solo decide cómo
// se IMPRIME ese número: gramos si es menos de 1 Kg, Kg con hasta 2 decimales si no.
function formatPeso(kg){
  kg=kg||0;
  return kg>=1 ? (Math.round(kg*100)/100)+' Kg' : Math.round(kg*1000)+' g';
}
function formatByCurrency(bsAmount,currency){
  // bsAmount siempre llega en bolívares, a la tasa base del MODELO DE PRECIOS ACTIVO
  if(currency==='bs') return fmtBs(bsAmount);
  if(currency==='bcv') return cfg.bcv>0? fmtUSD(bsAmount/cfg.bcv) : '—';
  const rateBs=rateBsForPrices();
  const contadoVal=rateBs>0? bsAmount/rateBs : 0;   // volver del Bs al PVP Final en USD
  const mf=monedaActivaById(currency);
  if(mf){
    const v=bsToMonedaRaw(bsAmount,mf);
    return v===null?'—':fmtMonedaVal(v,mf);
  }
  return fmtUSD(contadoVal); // 'contado' / USD / efectivo
}
// v47 — segunda moneda debajo del total en Bs (mismo criterio que ya usaba SOLO el hero del
// carrito: si hay BCV+Paralelo cargados a la vez y no se apagó "mostrar BCV", ese es el que
// acompaña al bolívar; si no, cae al USD oferta como respaldo). Se extrajo a función aparte para
// que la tarjeta de producto en la grilla pueda mostrar el mismo par Bs+USD que ya muestra el
// carrito, en vez de un solo número — pedido explícito: "si tengo bolívares y dólar activo,
// quiero que los muestre allí también".
function secondaryCurrencyLine(bsAmount){
  if(activeCurrency!=='bs')return '';
  return showBcvSeparately()
    ? formatByCurrency(bsAmount,'bcv')+' BCV'
    : (cfg.mostrarUsdOferta!==false ? formatByCurrency(bsAmount,'contado')+' USD oferta' : '');
}

// clientes / pedidos / fiados — helpers
function fmtDate(d){
  const diffDays=Math.round((_today-d)/86400000);
  if(diffDays<=0)return 'Hoy';
  if(diffDays===1)return 'Ayer';
  return d.toLocaleDateString('es-VE',{day:'numeric',month:'short'});
}
function fmtTime(d){ return d.toLocaleTimeString('es-VE',{hour:'2-digit',minute:'2-digit'}); }
// v4 — fecha COMPLETA para comprobantes. fmtDate() dice "Hoy"/"Ayer", que está bien dentro de
// la app (una lista de pedidos se lee mejor así) pero es inservible en un papel que el cliente
// se lleva: al día siguiente ese comprobante seguiría diciendo "Hoy". Acá va día/mes/año y hora.
function fmtDateTimeFull(d){
  const dt=(d instanceof Date)?d:new Date(d);
  if(isNaN(dt.getTime()))return '—';
  return dt.toLocaleDateString('es-VE',{day:'2-digit',month:'2-digit',year:'numeric'})+' '+fmtTime(dt);
}
function fmtRefDate(d){ return String(d.getFullYear()).slice(-2)+String(d.getMonth()+1).padStart(2,'0')+String(d.getDate()).padStart(2,'0'); }
function saldoOf(o){ if(o.status!=='fiado')return 0; return Math.max(0,Math.round((debtBs(o)-(o.paidBs||0))*100)/100); }
function payLabel(id){if(id==='mixto')return 'Pago mixto';const m=payMethods.find(x=>x.id===id);return m?m.label:'—'}
function itemsSummary(items){return items.map(i=>(i.esPeso?formatPeso(i.qty):i.qty+'×')+' '+i.name).join(', ')}
function clientOrders(id){return orders.filter(o=>o.clientId===id).slice().sort((a,b)=>b.date-a.date)}
function clientSaldo(id){return clientOrders(id).filter(o=>o.status==='fiado').reduce((s,o)=>s+saldoOf(o),0)}
function debtsByClient(){
  const map={};
  orders.filter(o=>o.status==='fiado'&&saldoOf(o)>0).forEach(o=>{
    if(!map[o.clientId])map[o.clientId]={client:clients.find(c=>c.id===o.clientId),orders:[]};
    map[o.clientId].orders.push(o);
  });
  return Object.values(map).filter(g=>g.client)
    .map(g=>({client:g.client,orders:g.orders.sort((a,b)=>b.date-a.date),saldoBs:g.orders.reduce((s,o)=>s+saldoOf(o),0)}))
    .sort((a,b)=>b.saldoBs-a.saldoBs);
}

// brand
function applyBrandName(name){
  const n=(name||'').trim()||'FrixPOS';
  document.querySelectorAll('.brand-name').forEach(el=>el.textContent=n);
  document.title=(n==='FrixPOS')?n:n+' · FrixPOS';
  if(!logoDataUrl){const l=n.charAt(0).toUpperCase();document.querySelectorAll('.brand-mark').forEach(el=>{el.textContent=l;el.style.backgroundImage='none'})}
}
function applyLogo(url){logoDataUrl=url;document.querySelectorAll('.brand-mark').forEach(el=>{el.textContent='';el.style.backgroundImage='url('+url+')'})}
$('bizName').addEventListener('input',e=>{cfg.bizName=e.target.value;applyBrandName(e.target.value)});
$('logoUpload').addEventListener('change',e=>{
  const f=e.target.files[0];if(!f)return;
  const r=new FileReader();
  r.onload=()=>{ cropTarget='logo'; openCropper(r.result); };
  r.readAsDataURL(f);
  e.target.value=''; // permitir re-elegir la misma imagen
});
$('catLogoUpload').addEventListener('change',e=>{
  const f=e.target.files[0];if(!f)return;
  const r=new FileReader();
  r.onload=()=>{ cropTarget='catalogoLogo'; openCropper(r.result); };
  r.readAsDataURL(f);
  e.target.value='';
});
$('perfilFotoUpload').addEventListener('change',e=>{
  const f=e.target.files[0];if(!f)return;
  const r=new FileReader();
  r.onload=()=>{ cropTarget='perfilFoto'; openCropper(r.result); };
  r.readAsDataURL(f);
  e.target.value='';
});
$('bizRif').addEventListener('input',e=>{cfg.bizRif=e.target.value});
$('bizAddress').addEventListener('input',e=>{cfg.bizAddress=e.target.value});
$('bizPhone').addEventListener('input',e=>{cfg.bizPhone=e.target.value});
// v1.11 — Mi perfil: mismo patrón que Negocio arriba, aplica en vivo mientras se escribe,
// queda confirmado solo al tocar "Guardar cambios" (ver captureGroup/applyGroup/'perfil').
$('perfilNombre').addEventListener('input',e=>{perfilCuenta.nombre=e.target.value});
$('perfilIdNumber').addEventListener('input',e=>{perfilCuenta.id_number=e.target.value});
$('perfilPhone').addEventListener('input',e=>{perfilCuenta.phone=e.target.value});
$('perfilAddress').addEventListener('input',e=>{perfilCuenta.address=e.target.value});
$('perfilTipo').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  document.querySelectorAll('#perfilTipo button').forEach(x=>x.classList.toggle('on',x===b));
  perfilCuenta.tipo=b.dataset.v;
  $('perfilNombreLbl').textContent=b.dataset.v==='negocio'?'Nombre del negocio':'Nombre y apellido';
  $('perfilIdLbl').textContent=b.dataset.v==='negocio'?'RIF':'Cédula';
});
// v1.13 — Catálogo Digital: slug en vivo con vista previa de la URL, y los dos interruptores.
// Nada de esto sale al servidor hasta tocar "Publicar catálogo" — a propósito, para no gastar
// una llamada de red por cada letra que se escribe.
function updateCatSlugPreview(){
  const raw=($('catSlug').value||'').trim().toLowerCase();
  const el=$('catSlugPreview'); if(!el)return;
  el.textContent = raw ? ('Tu enlace: '+location.origin+'/p/'+raw) : 'Elige un nombre corto, sin espacios ni acentos — por ejemplo, el de tu negocio.';
}
$('catSlug').addEventListener('input',updateCatSlugPreview);
$('catVisible').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  document.querySelectorAll('#catVisible button').forEach(x=>x.classList.toggle('on',x===b));
});
$('catMostrarPrecios').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  document.querySelectorAll('#catMostrarPrecios button').forEach(x=>x.classList.toggle('on',x===b));
});
// v36 — compartir el catálogo. navigator.share es el menú nativo del teléfono (WhatsApp, Telegram,
// Instagram, correo…); si no existe (PC, navegador viejo) se cae a wa.me, que abre WhatsApp Web.
$('catCompartirWa').addEventListener('click',async()=>{
  const texto=catalogoMensajeCompartir();
  if(navigator.share){
    try{ await navigator.share({text:texto}); return; }catch(e){ /* canceló, o no se pudo — sigue al fallback */ }
  }
  window.open('https://wa.me/?text='+encodeURIComponent(texto),'_blank');
});
$('catCopiarBtn').addEventListener('click',async()=>{
  const url=catalogoUrlPublica(), aviso=$('catCompartirAviso');
  let ok=false;
  try{ await navigator.clipboard.writeText(url); ok=true; }
  catch(e){
    // navegadores sin permiso de portapapeles (o sin HTTPS): se selecciona el campo para que
    // el usuario copie a mano, en vez de dejarlo sin saber qué pasó.
    try{ const inp=$('catEnlace'); inp.focus(); inp.select(); ok=document.execCommand&&document.execCommand('copy'); }catch(e2){}
  }
  if(aviso){
    aviso.textContent = ok ? 'Enlace copiado. Ya lo puedes pegar donde quieras.' : 'No se pudo copiar solo — el enlace está seleccionado arriba, cópialo a mano.';
    aviso.style.color = ok ? 'var(--green)' : 'var(--amber)';
  }
});
$('catAbrirBtn').addEventListener('click',()=>{
  const url=catalogoUrlPublica();
  if(url)window.open(url,'_blank');
});
$('catPublicarBtn').addEventListener('click',async()=>{
  const btn=$('catPublicarBtn'),aviso=$('catAviso');
  btn.disabled=true;btn.textContent='Publicando…';
  const r=await publicarCatalogoEnServidor();
  btn.disabled=false;btn.textContent='Publicar catálogo';
  if(!aviso)return;
  if(r.ok){
    aviso.innerHTML=r.url
      ? 'Publicado. Tu catálogo: <a href="'+r.url+'" target="_blank" rel="noopener">'+r.url+'</a>'
      : 'Guardado. Actívalo en "Publicar mi catálogo" para que quede visible.';
    aviso.style.color='var(--green)';
    // v36 — aparece el bloque de compartir con el enlace ya listo. A diferencia del resto de
    // Configuración, acá el acordeón NO se cierra solo: acabar de publicar y que se te cierre
    // en la cara justo cuando aparecen los botones de compartir sería quitarte el paso que
    // sigue. Se le hace scroll para que quede a la vista.
    renderCatalogoCompartir();
    const compartir=$('catCompartirBloque');
    if(compartir&&compartir.style.display!=='none'){
      setTimeout(()=>{ try{ compartir.scrollIntoView({behavior:'smooth',block:'center'}); }catch(e){} },300);
    }
  } else {
    aviso.textContent=r.error||'No se pudo publicar el catálogo.';
    aviso.style.color='var(--amber)';
  }
});
// permisos que el admin le puede dar (o quitar) al cajero
const CAJERO_PERMS=[
  {k:'tasas',label:'Cambiar las Tasas',sub:'BCV, Paralelo y monedas propias — mueve todos los precios del negocio'},
  {k:'pedidos',label:'Ver Pedidos',sub:'Historial de ventas y reimprimir recibos'},
  {k:'porcobrar',label:'Ver Por cobrar',sub:'Fiados pendientes y registrar abonos'},
  {k:'clientes',label:'Ver Clientes',sub:'Directorio y historial de cada cliente'},
  {k:'cajon',label:'Usar el Cajón',sub:'Abrir turno, arquear y cerrar caja'},
  {k:'catalogo',label:'Entrar a Inventario',sub:'Puede ver y cambiar precios y costos'},
  {k:'stock',label:'Entrar a Stock',sub:'Ver y ajustar el inventario'},
  {k:'informes',label:'Ver Informes',sub:'Ventas y ganancias del negocio'},
  {k:'contabilidad',label:'Ver Contabilidad',sub:'IVA, egresos y ganancia neta'},
  {k:'fiar',label:'Fiar a clientes',sub:'Dejar que una venta quede como fiada'},
  {k:'devolver',label:'Hacer devoluciones',sub:'Devolver productos y regresar la plata'},
  {k:'borrarCliente',label:'Eliminar clientes',sub:'Borrar a alguien del directorio'},
];
// ===== Clave de administrador (v1.10) =====
// Antes el rol era un simple toggle sin candado: el cajero entraba a Configuración (siempre puede),
// tocaba "Administrador" y ya tenía todo. Peor todavía, la lista de permisos se mostraba JUSTO
// cuando el rol activo era cajero — o sea que el cajero podía darse permisos a sí mismo. Ahora:
// el admin define sus permisos SIN cambiarse de rol, y volver de cajero a admin pide la clave.
let pinCb=null;
function askPin(msg,onOk){
  pinCb=onOk;
  $('pinMsg').textContent=msg;
  $('pinInput').value='';
  $('pinErr').textContent='';$('pinErr').classList.remove('show');
  $('pinOverlay').classList.add('show');
  setTimeout(()=>{try{$('pinInput').focus()}catch(e){}},50);
}
function closePinModal(){$('pinOverlay').classList.remove('show');pinCb=null}
function pinError(msg){const e=$('pinErr');e.textContent=msg;e.classList.add('show')}
$('pinCancel').addEventListener('click',closePinModal);
$('pinBackdrop').addEventListener('click',closePinModal);
$('pinOk').addEventListener('click',()=>{ if(pinCb)pinCb($('pinInput').value.trim()); });
$('pinInput').addEventListener('keydown',e=>{ if(e.key==='Enter'){e.preventDefault();$('pinOk').click();} });

// ===== venta por peso (v45) =====
// Modal para capturar el peso pesado en el mostrador — mismo patrón estático de #pinOverlay (un
// solo overlay fijo en el DOM, se abre/cierra con .show), pero sin callback: al confirmar escribe
// directo en `cart`. openPesoModal(id) sirve tanto para agregar por primera vez (input vacío) como
// para editar una línea que ya está en el carrito (se precarga con su peso actual) — el botón
// siempre REEMPLAZA el valor de cart[id], nunca acumula, así una sola función cubre los dos casos.
let pesoModalProductId=null;
function openPesoModal(productId){
  const p=products.find(x=>x.id===productId);
  if(!p)return;
  const pr=effectivePrices(p);
  if(!(pr.bs>0)){
    askConfirm('"'+p.name+'" no tiene un precio por Kg configurado todavía. Ponle un precio en Inventario antes de venderlo.',()=>{});
    return;
  }
  pesoModalProductId=productId;
  $('pesoProdName').textContent=p.name;
  $('pesoProdPrecioKg').textContent=formatByCurrency(pr.bs,activeCurrency)+' /Kg';
  $('pesoGramosInput').value=cart[productId]?Math.round(cart[productId]*1000):'';
  $('pesoConfirm').textContent=cart[productId]?'Actualizar':'Agregar al carrito';
  updatePesoPreview();
  $('pesoOverlay').classList.add('show');
  setTimeout(()=>{try{$('pesoGramosInput').focus();$('pesoGramosInput').select()}catch(e){}},50);
}
function closePesoModal(){$('pesoOverlay').classList.remove('show');pesoModalProductId=null}
function updatePesoPreview(){
  const p=products.find(x=>x.id===pesoModalProductId);
  const gramos=parseFloat($('pesoGramosInput').value)||0;
  $('pesoSubtotalPreview').textContent=(p&&gramos>0)?('Subtotal: '+formatByCurrency(effectivePrices(p).bs*(gramos/1000),activeCurrency)):'';
}
$('pesoGramosInput').addEventListener('input',updatePesoPreview);
$('pesoCancel').addEventListener('click',closePesoModal);
$('pesoBackdrop').addEventListener('click',closePesoModal);
$('pesoGramosInput').addEventListener('keydown',e=>{ if(e.key==='Enter'){e.preventDefault();$('pesoConfirm').click();} });
$('pesoConfirm').addEventListener('click',()=>{
  const gramos=parseFloat($('pesoGramosInput').value)||0;
  if(gramos<=0){$('pesoGramosInput').focus();return}
  cart[pesoModalProductId]=gramos/1000;
  closePesoModal();
  renderCart();
});

// Qué NO puede ver un cajero en Configuración. Sin esto podía renombrar el negocio, activar o
// desactivar el código, BORRAR LA CUENTA entera, borrar todos los datos del teléfono, y mover
// las tasas de cambio — todo desde el mismo Configuración al que siempre tiene acceso.
const BLOQUES_SOLO_ADMIN=['identidadBlock','activacionBlock','respaldoBlock','fiscalBlock','wipeBlock'];
function applyRoleVisibility(){
  const esCajero=cfg.role==='cajero';
  BLOQUES_SOLO_ADMIN.forEach(id=>{ const el=$(id); if(el)el.style.display=esCajero?'none':''; });
  // v1.11 — Mi perfil es la identidad personal del DUEÑO de la cuenta (cédula/RIF, teléfono,
  // dirección); un cajero no debería poder verla ni editarla desde el mismo teléfono compartido.
  // v1.13 — mismo criterio para Catálogo Digital: publicar la vitrina pública es una decisión
  // del dueño, no algo que un cajero deba poder tocar desde el teléfono de la tienda.
  document.querySelectorAll('.cfg-group[data-group="precios"],.cfg-group[data-group="cobros"],.cfg-group[data-group="perfil"],.cfg-group[data-group="catalogodigital"]').forEach(g=>{
    g.style.display=esCajero?'none':'';
  });
  const saveRow=document.querySelector('.cfg-save-row [data-group="negocio"]');
  if(saveRow&&saveRow.parentElement)saveRow.parentElement.style.display=esCajero?'none':'';
}
function renderCajeroPerms(){
  // el editor de permisos ahora se ve en modo ADMIN (que es quien decide), no en modo cajero.
  const esAdmin=cfg.role!=='cajero';
  $('cajeroPermsWrap').style.display=esAdmin?'':'none';
  if(!esAdmin)return;
  const p=cfg.cajeroPerms||{};
  $('cajeroPermsList').innerHTML=CAJERO_PERMS.map(x=>
    '<div class="cur-row"><div><div class="nm">'+x.label+'</div><div class="sub">'+x.sub+'</div></div>'
    +'<div class="seg" data-perm="'+x.k+'"><button data-v="no" class="'+(p[x.k]?'':'on')+'">No</button><button data-v="si" class="'+(p[x.k]?'on':'')+'">Sí</button></div></div>'
  ).join('');
}
$('cajeroPermsList').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  const seg=b.closest('[data-perm]');if(!seg)return;
  const key=seg.dataset.perm;
  cfg.cajeroPerms=Object.assign({},cfg.cajeroPerms||{});
  cfg.cajeroPerms[key]=(b.dataset.v==='si');
  renderCajeroPerms();renderNav();
});
function aplicarRol(r){
  cfg.role=r;
  document.querySelectorAll('#roleToggle button').forEach(x=>x.classList.toggle('on',x.dataset.r===r));
  renderCajeroPerms();renderNav();applyRoleVisibility();
  schedulePersist(true); // el rol es una barrera, no una preferencia: se guarda de una
}
$('roleToggle').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  const destino=b.dataset.r;
  if(destino===cfg.role)return;

  // cajero -> admin: pide la clave. Si nunca se creó una (teléfono que quedó en modo cajero con
  // una versión anterior a este candado), se deja pasar en vez de dejar a alguien encerrado.
  if(destino==='admin'){
    if(!cfg.adminPin){ aplicarRol('admin'); return; }
    askPin('Escribe tu clave de administrador para volver a tener acceso completo.',val=>{
      if(val!==cfg.adminPin){ pinError('Clave incorrecta.'); return; }
      closePinModal(); aplicarRol('admin');
    });
    return;
  }

  // admin -> cajero: si todavía no hay clave, se crea ahora. Sin esto el modo cajero no
  // protege nada, porque cualquiera podría devolverse a admin con un toque.
  if(!cfg.adminPin){
    askPin('Crea una clave de administrador. Te la van a pedir para volver a este modo, así tu cajero no puede devolverse solo.',val=>{
      if(!val||val.length<4){ pinError('Usa al menos 4 caracteres.'); return; }
      cfg.adminPin=val; closePinModal(); aplicarRol('cajero');
    });
    return;
  }
  askConfirm('Vas a pasar a modo Cajero. Para volver a Administrador vas a necesitar tu clave. ¿Continuar?',()=>aplicarRol('cajero'));
});

// nav
// ¿el usuario actual puede entrar a esta sección / hacer esta acción?
// El admin puede todo. El cajero, solo lo que el admin le haya marcado en Configuración.
function can(key){
  if(cfg.role!=='cajero')return true;
  const p=cfg.cajeroPerms||{};
  return !!p[key];
}
function sectionAllowed(s){
  if(cfg.role!=='cajero')return true;
  if(s.id==='venta'||s.id==='config')return true;   // vender y ver su config básica: siempre
  return can(s.id);
}
function renderNav(){
  $('nav').innerHTML=sections.filter(sectionAllowed).map(s=>{
    let badge='';
    if(s.badge==='debt'){const n=debtsByClient().length;if(n>0)badge='<div class="ndot">'+n+'</div>'}
    if(s.badge==='lowstock'){const n=lowStockProducts().length+products.filter(p=>p.stock<0).length;if(n>0)badge='<div class="ndot">'+n+'</div>'}
    if(s.id==='cajon'&&openTurno)badge='<div class="ndot" style="background:var(--green)">●</div>';
    return '<button class="nav-item'+(s.id===currentSection?' on':'')+'" data-id="'+s.id+'">'+s.icon+'<div class="lbl">'+s.label+'</div>'+badge+'</button>';
  }).join('');
}
$('nav').addEventListener('click',e=>{const b=e.target.closest('.nav-item');if(!b)return;goSection(b.dataset.id);closeDrawer()});
// v1.14 — respaldar desde el menú. No cierra el drawer a propósito: el aviso de resultado sale
// justo debajo del botón, y cerrarlo dejaría al dueño sin saber si funcionó o no.
$('navRespaldoBtn').addEventListener('click',async()=>{
  const btn=$('navRespaldoBtn'), aviso=$('navRespaldoAviso');
  const lbl=btn.querySelector('.lbl');
  if(btn.disabled)return;
  btn.disabled=true; lbl.textContent='Subiendo…';
  aviso.style.display='';
  aviso.style.color='var(--text-faint)';
  aviso.textContent='Guardando tu información…';
  const r=await guardarRespaldo();
  btn.disabled=false; lbl.textContent='Respaldar';
  aviso.textContent = r.ok ? 'Listo, respaldo guardado.' : (r.error||'No se pudo respaldar.');
  aviso.style.color = r.ok ? 'var(--green)' : 'var(--amber)';
  if(currentSection==='cajon')renderCajon();
  if(typeof renderRespaldo==='function')renderRespaldo();
  setTimeout(()=>{ aviso.style.display='none'; },6000);
});
$('sidebarBrand').addEventListener('click',()=>{goSection('venta');closeDrawer()});
$('tbMark').addEventListener('click',()=>goSection('venta'));
function goSection(id){
  if(saleSuccessOpen)resetSaleUI();
  const target=sections.find(x=>x.id===id);
  if(target&&!sectionAllowed(target))id='venta'; // sin permiso -> se le manda a Venta
  if(currentSection==='config'&&id!=='config')discardUnsavedGroups(); // Config: lo no guardado se descarta al salir
  currentSection=id;
  document.querySelectorAll('.nav-item').forEach(x=>x.classList.toggle('on',x.dataset.id===id));
  const s=sections.find(x=>x.id===id);
  const label=s.label==='Contab.'?'Contabilidad':(s.label==='Config.'?'Configuración':s.label);
  $('sectionTitle').textContent=label;
  const isVenta=s.type==='venta';
  const known=['venta','config','clientes','porcobrar','pedidos','catalogo','stock','cajon','informes','contabilidad','tasas'];
  ['view-venta','view-config','view-clientes','view-porcobrar','view-pedidos','view-catalogo','view-stock','view-cajon','view-informes','view-contabilidad','view-tasas','view-placeholder'].forEach(vid=>$(vid).classList.remove('on'));
  if(known.includes(s.type)){
    $('view-'+s.type).classList.add('on');
    if(s.type==='clientes')renderClientes();
    if(s.type==='porcobrar')renderPorCobrar();
    if(s.type==='pedidos')renderPedidos();
    if(s.type==='catalogo')renderCatalogo();
    if(s.type==='stock')renderStock();
    if(s.type==='cajon')renderCajon();
    if(s.type==='informes')renderInformes();
    if(s.type==='contabilidad')renderContabilidad();
    if(s.type==='tasas')renderTasas();
  } else {
    $('view-placeholder').classList.add('on');
    $('view-placeholder').innerHTML='<div class="placeholder"><div class="picon">'+s.icon+'</div><span class="tag-soon">Próximamente</span><h3>'+label+'</h3><p>'+s.desc+'</p></div>';
  }
  $('sectionTitle').style.display=isVenta?'none':'';
  $('tbSearchWrap').style.display=isVenta?'':'none';
  document.body.classList.toggle('hide-cart',!isVenta);
  // FIX v24_1 — cada sección arranca desde arriba. Sin esto, salir de una sección larga (Pedidos,
  // Inventario) hacia una corta (Por cobrar sin deudas) dejaba la página en la posición de scroll
  // vieja, y el encabezado sticky quedaba pintado a mitad de pantalla con una banda vacía encima.
  try{ window.scrollTo(0,0); }catch(err){}
}
$('menuBtn').addEventListener('click',()=>{$('sidebar').classList.add('open');$('navBackdrop').classList.add('show');document.body.classList.add('drawer-open')});
$('navBackdrop').addEventListener('click',closeDrawer);
function closeDrawer(){$('sidebar').classList.remove('open');$('navBackdrop').classList.remove('show');document.body.classList.remove('drawer-open')}

// config: color
function renderSwatches(){
  $('swatches').innerHTML=themes.map(t=>'<div class="swatch-wrap"><button class="swatch'+(t.id===activeTheme?' on':'')+'" data-id="'+t.id+'" style="background:linear-gradient(135deg,'+t.a+','+t.b+')" aria-label="'+t.name+'"></button><div class="swatch-name">'+t.name+'</div></div>').join('');
}
$('swatches').addEventListener('click',e=>{const b=e.target.closest('.swatch');if(!b)return;const t=themes.find(x=>x.id===b.dataset.id);if(!t)return;activeTheme=t.id;document.documentElement.style.setProperty('--accent-a',t.a);document.documentElement.style.setProperty('--accent-b',t.b);renderSwatches()});

// config: mode / font
$('modeToggle').addEventListener('click',e=>{const b=e.target.closest('button');if(!b)return;cfg.mode=b.dataset.m;document.body.classList.toggle('light',cfg.mode==='claro');document.querySelectorAll('#modeToggle button').forEach(x=>x.classList.toggle('on',x===b))});
$('fontToggle').addEventListener('click',e=>{const b=e.target.closest('button');if(!b)return;cfg.fontSize=b.dataset.f;document.documentElement.style.fontSize=({sm:'13px',md:'15px',lg:'17px'})[cfg.fontSize];document.querySelectorAll('#fontToggle button').forEach(x=>x.classList.toggle('on',x===b))});

// config: descuento por efectivo
// (el modelo de precios estilo Excel no usa "descuento por efectivo": el contado ES el precio base)

// v50 — pedido de Jonathan: en modo Simple no hay costo/gastos/ganancia que explicar (ahí el
// precio se escribe directo), así que el ejemplo se reduce a un solo caso simple, sin el segundo
// caso "sujeto a IVA" (Simple siempre es exento) ni el pie de IVA-crédito (eso es costeo, y Simple
// no tiene costo). En Personalizado/Medio se queda el ejemplo completo de siempre.
function updateExample(){
  if(currentPricingMode()==='simple'){
    $('priceExampleTitle').textContent='Así se vería un producto con precio final $1.40';
    const draft={pricingMode:'simple',ivaSubject:false,offerPrice:0,simplePrecio:1.40,simpleMoneda:'paralelo',simpleIvaIncluido:true};
    $('priceExample').innerHTML=priceBreakdownHTML(effectivePrices(draft),false);
    return;
  }
  $('priceExampleTitle').textContent='Así queda un producto de $1.09 de costo';
  const COSTO=1.09, GAN=30;
  const prNo=computePrices(COSTO,GAN,false,cfg.gastosPctDefault);
  const prSi=computePrices(COSTO,GAN,true,cfg.gastosPctDefault);
  const desglose='Gastos operativos ('+(cfg.gastosPctDefault||0)+'%): <b>'+fiscalLineVal(prNo.montoGastos)+'</b> · Ganancia (30%): <b>'+fiscalLineVal(prNo.montoGanancia)+'</b><br>';
  $('priceExample').innerHTML=
    '<div style="margin-bottom:2px"><b>Producto exento de IVA</b> <span style="color:var(--text-faint)">(la mayoría de víveres)</span></div>'+
    desglose+
    priceBreakdownHTML(prNo,false)+
    monedasActivas().map(m=>m.nombre+': <b>'+fmtMonedaVal(prNo.monedas[m.id],m)+'</b><br>').join('')+
    '<div style="margin:12px 0 2px"><b>Producto sujeto a IVA</b> <span style="color:var(--text-faint)">(IVA general '+(cfg.ivaGeneral||0)+'%)</span></div>'+
    priceBreakdownHTML(prSi,true)+
    'IVA estimado en costos: <b>'+fmtUSD(prSi.ivaCredito)+'</b> · Diferencia SENIAT estimada por este producto: <b>'+fmtUSD(prSi.diferenciaSeniat)+'</b>'+
    '<div style="color:var(--text-faint);font-size:.78rem;margin-top:4px">Esto es costeo, no tu declaración real — esa sale de tus facturas de compra en Contabilidad.</div>';
}
// selector de modelo de precios — cambia el motor completo y refresca toda la app

// config: rates
$('rateBcv').value=cfg.bcv;$('rateParalelo').value=cfg.paralelo||'';$('rateIva').value=cfg.ivaGeneral||'';
function onRateChange(){
  // v5 — ya no se esconde ninguna tasa: con un solo modelo, todas aplican siempre.
  syncMonedaPayMethods();
  renderMonedas();
  applyPriceModelToProductForm();
  applyBusinessModeToProductForm();
  applyBusinessModeToConfigBlocks();
  renderCurrOrder();renderPayConfig();renderPay();
  renderCurrToggle();renderRatesStrip();renderGrid();renderCart();updateExample();
  if(currentSection==='porcobrar')renderPorCobrar();
  if(currentSection==='clientes')renderClientes();
  if(currentSection==='pedidos')renderPedidos();
  if(currentSection==='catalogo')renderCatalogo();
  if(currentSection==='contabilidad')renderContabilidad();
}
$('rateBcv').addEventListener('input',e=>{cfg.bcv=parseFloat(e.target.value)||0;onRateChange()});
$('rateParalelo').addEventListener('input',e=>{cfg.paralelo=parseFloat(e.target.value)||0;onRateChange()});
document.querySelectorAll('#mostrarUsdBcvToggle button').forEach(x=>x.classList.toggle('on',x.dataset.v===(cfg.mostrarUsdBcv!==false?'si':'no')));
// v49 — modo de negocio global (Simple/Medio BCV/Avanzado): antes cada producto elegía su propio
// modelo (#prodPricingMode); ahora es una sola decisión acá, que decide qué campos pide el
// formulario de producto (ver applyBusinessModeToProductForm). "Medio BCV" queda deshabilitado en
// el HTML (disabled) hasta que Jonathan lo defina — no tiene botón que lo active todavía.
document.querySelectorAll('#businessModeToggle button').forEach(x=>x.classList.toggle('on',x.dataset.v===(cfg.businessMode||'medio')));
$('businessModeToggle').addEventListener('click',e=>{
  const b=e.target.closest('button'); if(!b||b.disabled)return;
  cfg.businessMode=b.dataset.v;
  // v50 — Medio BCV es un perfil FIJO (Jonathan: "sin poder editar"): al entrar se fuerza una vez.
  // OJO: cfg.currOrder NO se toca acá a propósito — se reescribió antes y era una puerta sin
  // vuelta (Bs/BCV built-in no tienen botón "+Agregar" como las monedas propias, así que salir de
  // Medio dejaba "oferta" desaparecida para siempre del carrito). En vez de mutar currOrder, es
  // availableCurrencies() la que filtra a solo Bs/BCV MIENTRAS isMedio() es cierto — al salir de
  // Medio, currOrder queda intacto y "oferta" vuelve sola. gastosPctDefault sí se apaga acá porque
  // ese modo no pide Gastos operativos en ningún producto nuevo.
  if(cfg.businessMode==='medio'){
    cfg.mostrarUsdBcv=true; cfg.mostrarUsdOferta=false; cfg.gastosPctDefault=0;
    document.querySelectorAll('#mostrarUsdBcvToggle button').forEach(x=>x.classList.toggle('on',x.dataset.v==='si'));
    document.querySelectorAll('#mostrarUsdOfertaToggle button').forEach(x=>x.classList.toggle('on',x.dataset.v==='no'));
    $('gastosDefault').value=0;
  }
  document.querySelectorAll('#businessModeToggle button').forEach(x=>x.classList.toggle('on',x===b));
  onRateChange();
});
$('mostrarUsdBcvToggle').addEventListener('click',e=>{
  const b=e.target.closest('button'); if(!b)return;
  cfg.mostrarUsdBcv=(b.dataset.v==='si');
  document.querySelectorAll('#mostrarUsdBcvToggle button').forEach(x=>x.classList.toggle('on',x===b));
  onRateChange();
});
document.querySelectorAll('#mostrarUsdOfertaToggle button').forEach(x=>x.classList.toggle('on',x.dataset.v===(cfg.mostrarUsdOferta!==false?'si':'no')));
$('mostrarUsdOfertaToggle').addEventListener('click',e=>{
  const b=e.target.closest('button'); if(!b)return;
  cfg.mostrarUsdOferta=(b.dataset.v==='si');
  document.querySelectorAll('#mostrarUsdOfertaToggle button').forEach(x=>x.classList.toggle('on',x===b));
  onRateChange();
});
$('rateIva').addEventListener('input',e=>{cfg.ivaGeneral=parseFloat(e.target.value)||0;onRateChange()});
$('gastosDefault').value=cfg.gastosPctDefault;
$('gastosDefault').addEventListener('input',e=>{cfg.gastosPctDefault=parseFloat(e.target.value)||0;onRateChange()});
// v24 — impuestos que no dependen del motor de precios (no tocan ningún PVP, solo Contabilidad)
$('ratePatente').value=cfg.patentePct||'';
$('rateIslr').value=cfg.islrPct||'';
$('ratePatente').addEventListener('input',e=>{cfg.patentePct=parseFloat(e.target.value)||0;if(currentSection==='contabilidad')renderContabilidad()});
$('rateIslr').addEventListener('input',e=>{cfg.islrPct=parseFloat(e.target.value)||0;if(currentSection==='contabilidad')renderContabilidad()});
$('contribEspToggle').addEventListener('click',e=>{
  const b=e.target.closest('button'); if(!b)return;
  cfg.contribEspecial=(b.dataset.v==='si');
  document.querySelectorAll('#contribEspToggle button').forEach(x=>x.classList.toggle('on',x===b));
  if(currentSection==='contabilidad')renderContabilidad();
});

// config: cómo se guarda la deuda, y en qué moneda se arma la factura compartida
$('selDebtMode').value=cfg.debtMode;
$('selDebtMode').addEventListener('change',e=>{
  cfg.debtMode=e.target.value;
  if(currentSection==='porcobrar')renderPorCobrar();
  if(currentSection==='clientes')renderClientes();
});
$('selInvoice').value=cfg.invoiceCurrency;
$('selInvoice').addEventListener('change',e=>{cfg.invoiceCurrency=e.target.value});

const CURR_META={
  bs:{id:'bs',label:'Bs'},
  contado:{id:'contado',label:'$ oferta'},
  bcv:{id:'bcv',label:'$ BCV'},
};
function currMeta(id){ return CURR_META[id] || {id:id,label:(monedaById(id)||{}).nombre||id}; }
// ===== QUÉ MONEDAS EXISTEN SEGÚN EL MODELO DE PRECIOS (v19) =====
// Los dos modelos no son solo dos fórmulas: son dos formas distintas de trabajar, y cada una
// trae su propio juego de monedas. Elegir el modelo cambia qué monedas aparecen en TODA la app
// (toggle del carrito, métodos de pago, egresos de Contabilidad, deudas, Cajón, factura, notas).
//   'gastos' (SENIAT)  -> Bs + $ BCV. Un solo dólar, el oficial. Es lo que se puede facturar formal.
//   'margen' (calle)   -> Bs (paralelo) + $ contado + $ BCV + COP. Multimoneda de frontera.
// v5 — las monedas que existen son las que el negocio tiene activas, no una lista por "modelo".
function modelCurrencies(){ return ['bs','contado','bcv'].concat(monedasActivas().map(m=>m.id)); }
function currIsAvailable(id){
  if(id==='bs')return true;
  // v31 — "$ oferta" (dólar real/paralelo) se puede apagar: la mayoría solo quiere mostrar
  // Bs + BCV en la vitrina y usar el oferta puertas adentro, para proteger su inversión sin
  // ofrecer el descuento en efectivo a todo el mundo. Sigue existiendo, solo deja de mostrarse.
  if(id==='contado')return cfg.mostrarUsdOferta!==false;
  if(id==='bcv')return showBcvSeparately();
  return !!monedaActivaById(id);
}
// v27 — antes esto forzaba 'contado' a decir "$ BCV" a propósito (en el modelo viejo, 'contado'
// SÍ era el dólar BCV). Ahora 'contado' es el USD oferta (dólar real) y 'bcv' es un botón aparte
// — con el override viejo, los dos botones decían "$ BCV" a la vez (bug real, reportado
// probando el carrito). currMeta ya tiene la etiqueta correcta de cada uno, sin pisar nada.
function currLabel(id){ return currMeta(id).label; }
// ¿este método de pago tiene sentido con el modelo activo? En el modelo SENIAT no hay pesos.
function payMethodAllowed(id){
  const cur=payCurrency[id]||'bs';
  if(cur==='bs'||cur==='usd'||cur==='usd_bcv')return true;
  return !!monedaActivaById(cur);
}
function availableCurrencies(){
  // respeta el orden elegido en Configuración (cfg.currOrder); filtra las que no aplican por tasas
  let orden=(cfg.currOrder||['bs','contado','bcv']).slice();
  monedasActivas().forEach(m=>{ if(!orden.includes(m.id))orden.push(m.id); });
  // v50 — Medio BCV: solo Bs y BCV, sin tocar cfg.currOrder (ver businessModeToggle) — un filtro
  // en vivo, no una mutación, para que salir de Medio devuelva todo tal cual estaba.
  if(isMedio())orden=orden.filter(id=>id==='bs'||id==='bcv');
  return orden.filter(currIsAvailable).map(currMeta);
}
function renderCurrToggle(){
  const avail=availableCurrencies();
  if(!avail.find(c=>c.id===activeCurrency))activeCurrency=avail.length?avail[0].id:'bs';
  $('currToggle').innerHTML=avail.map(c=>'<button data-c="'+c.id+'" class="'+(c.id===activeCurrency?'on':'')+'">'+currLabel(c.id)+'</button>').join('');
}

// config: orden de monedas del carrito
function renderCurrOrder(){
  const order=(cfg.currOrder||['bs','contado','bcv']).concat(monedasActivas().filter(m=>!(cfg.currOrder||[]).includes(m.id)).map(m=>m.id));
  $('currOrderList').innerHTML=order.map((id,i)=>{
    const avail=currIsAvailable(id);
    const porQueNo=' <span style="color:var(--text-faint)">(sin tasa)</span>';
    return '<div class="pay-list-item" style="'+(avail?'':'opacity:.5')+'">'
      +'<span><b>'+(i+1)+'.</b> '+currLabel(id)+(avail?'':porQueNo)+(i===0?' <span style="color:var(--accent-a)">· por defecto</span>':'')+'</span>'
      +'<button class="curr-up" data-i="'+i+'" '+(i===0?'disabled style="opacity:.3"':'')+'>▲</button>'
      +'<button class="curr-down" data-i="'+i+'" '+(i===order.length-1?'disabled style="opacity:.3"':'')+'>▼</button>'
      +'</div>';
  }).join('');
}
$('currOrderList').addEventListener('click',e=>{
  const up=e.target.closest('.curr-up'), down=e.target.closest('.curr-down');
  if(!up&&!down)return;
  const order=cfg.currOrder.slice();
  const i=parseInt((up||down).dataset.i,10);
  const j=up?i-1:i+1;
  if(j<0||j>=order.length)return;
  [order[i],order[j]]=[order[j],order[i]];
  cfg.currOrder=order;
  // si cambió la primera, esa pasa a ser la moneda por defecto/activa
  activeCurrency=order.find(currIsAvailable)||'bs';
  renderCurrOrder();renderCurrToggle();renderCart();
});
renderCurrOrder();
function renderRatesStrip(){
  const p=[];
  if(cfg.bcv>0)p.push('<span>BCV '+cfg.bcv+'</span>');
  if(cfg.paralelo>0)p.push('<span>Paralelo '+cfg.paralelo+'</span>');
  monedasActivas().forEach(m=>p.push('<span>'+m.nombre+' '+m.tasa.toLocaleString('es-VE')+'</span>'));
  $('rates').innerHTML=p.join('');
}
// v37 — vista "Tasas" en el menú principal: BCV, Paralelo y monedas propias, editables en un
// toque. Escribe en los MISMOS cfg.bcv/cfg.paralelo/moneda.tasa que Configuración → Precios y
// tasas — no hay un segundo estado que sincronizar, solo un segundo lugar para tocarlo. Todo lo
// demás de esa sección (IVA, gastos operativos, agregar/quitar moneda) se queda solo allá, para
// no duplicar controles que casi nunca se usan.
function renderTasas(){
  const el=$('tasasBody'); if(!el)return;
  const monedasHtml=(cfg.monedas||[]).map(m=>
    '<div class="cfg-row"><label>'+(m.nombre||'Moneda')+' (por USD)</label>'
    +'<input class="cfg-input" data-tasas-mon="'+m.id+'" type="number" inputmode="decimal" value="'+(m.tasa||'')+'" placeholder="Ej: 3500"></div>'
  ).join('');
  el.innerHTML='<div class="cfg-block">'
    +'<h4>Tasas</h4>'
    +'<div class="desc">De aquí sale todo lo demás — precios, deudas, Cajón. Actualízalas apenas cambien.</div>'
    +'<div class="cfg-row"><label>BCV (Bs por USD)</label><input class="cfg-input" id="tasasBcv" type="number" inputmode="decimal" placeholder="Ej: 750" value="'+(cfg.bcv||'')+'"></div>'
    +'<div class="cfg-row"><label>Paralelo (Bs por USD)</label><input class="cfg-input" id="tasasParalelo" type="number" inputmode="decimal" placeholder="Ej: 900" value="'+(cfg.paralelo||'')+'"></div>'
    +monedasHtml
    +(cfg.paralelo>0?'':'<div class="desc" style="color:var(--amber);margin-top:8px">Sin Paralelo cargado, tus monedas propias no están activas.</div>')
    +'<div class="desc" style="margin-top:14px">¿Agregar una moneda nueva, el IVA o los gastos operativos? Eso sigue en <b>Configuración → Precios y tasas</b>.</div>'
    +'</div>';
}
const _tasasBody=$('tasasBody');
if(_tasasBody){
  _tasasBody.addEventListener('input',e=>{
    // ojo: nunca llamar renderTasas() aquí adentro — reconstruiría de una el input que se está
    // tecleando en este mismo instante y le haría perder el foco a cada dígito.
    if(e.target.id==='tasasBcv'){ cfg.bcv=parseFloat(e.target.value)||0; $('rateBcv').value=cfg.bcv||''; onRateChange(); return; }
    if(e.target.id==='tasasParalelo'){ cfg.paralelo=parseFloat(e.target.value)||0; $('rateParalelo').value=cfg.paralelo||''; onRateChange(); return; }
    const monId=e.target.dataset.tasasMon;
    if(monId){
      const m=(cfg.monedas||[]).find(x=>x.id===monId); if(!m)return;
      m.tasa=parseFloat(e.target.value)||0;
      renderMonedas(); // refleja el cambio en Configuración si estaba pintada — contenedor distinto, sin riesgo de foco
      onRateChange();
    }
  });
}

// config: pay methods
// v51 — los 2 métodos "(oferta)" (PAY_METHODS_OFERTA) no aparecen en esta lista manual: no se
// prenden a mano, aparecen solos cuando "Mostrar USD oferta" está encendido (ver methodEnabled),
// con una notita acá para que no parezca que faltan.
function renderPayConfig(){
  $('payToggleList').innerHTML=payMethods.filter(m=>!PAY_METHODS_OFERTA.includes(m.id)).map(m=>{
    const ok=payMethodAllowed(m.id);
    return '<button data-id="'+m.id+'" class="'+(m.on&&ok?'on':'')+'"'+(ok?'':' style="opacity:.45"')+'>'+m.label+(ok?'':' · no aplica')+'</button>';
  }).join('');
  $('payOfertaNota').style.display=cfg.mostrarUsdOferta===true?'':'none';
}
$('payToggleList').addEventListener('click',e=>{const b=e.target.closest('button');if(!b)return;const m=payMethods.find(x=>x.id===b.dataset.id);if(!m)return;m.on=!m.on;if(!m.on&&selMethod===m.id)selMethod=null;renderPayConfig();renderPay()});

// pagos mixtos — flujo tipo Perijapp: eliges UN método, escribes el monto, "+ Agregar pago"
// lo suma a la lista (convertido a Bs). Se repite hasta cubrir el total. El campo de monto
// vive fuera del re-render de refreshCheckout() para no perder el foco mientras se escribe.
// La moneda del toggle del carrito manda sobre qué métodos de pago se pueden usar (v23):
// si estás cobrando en Bs no tiene sentido ofrecer Zelle, y si cobras en $ no tiene sentido
// ofrecer pago móvil. Devuelve la moneda de PAGO ('bs' | 'usd' | 'cop') que corresponde a la
// moneda mostrada en el carrito.
function payCurrencyForActive(){
  if(activeCurrency==='bs')return 'bs';
  if(activeCurrency==='bcv')return 'usd_bcv';
  if(monedaActivaById(activeCurrency))return activeCurrency;
  return 'usd';
}
function renderPay(){
  const monedaPago=payCurrencyForActive();
  const active=payMethods.filter(m=>methodEnabled(m)&&payMethodAllowed(m.id)&&(payCurrency[m.id]||'bs')===monedaPago);
  // si el método que estaba elegido ya no aplica a esta moneda, se deselecciona solo
  if(selMethod&&!active.find(m=>m.id===selMethod))selMethod=null;
  const methodsHtml=active.length
    ? active.map(m=>'<button class="pm-btn'+(selMethod===m.id?' on':'')+'" data-id="'+m.id+'">'+m.label+'</button>').join('')
    : '<div class="cart-empty" style="padding:14px 0"><b>Sin métodos en '+currLabel(activeCurrency)+'</b>'
      +(payMethods.some(m=>methodEnabled(m)&&payMethodAllowed(m.id))
        ? 'Cambia la moneda arriba, o activa un método en '+currLabel(activeCurrency)+' desde Configuración.'
        : 'Actívalos en Configuración → Métodos de pago.')+'</div>';
  const listHtml=payments.map((p,i)=>{
    const amtFmt=fmtMontoMoneda(p.amount,p.currency);
    return '<div class="pay-list-item"><span>'+p.label+' — <b>'+amtFmt+'</b></span><button data-rm="'+i+'">✕</button></div>';
  }).join('');
  // pista de pago mixto (v23): como los métodos ahora se filtran por la moneda de arriba, hay que
  // decirle al cajero cómo cobrar una venta partida entre dos monedas — si no, parece que ya no se puede.
  const otrasMonedas=availableCurrencies().filter(c=>c.id!==activeCurrency).length>0;
  const hintMixto=otrasMonedas
    ? '<div class="pay-hint">¿Va a pagar con varias monedas? Agrega lo que te dé en '+currLabel(activeCurrency)+', luego cambia la moneda arriba y agrega el resto.</div>'
    : '';
  $('pay').innerHTML=
    '<div class="pay-methods">'+methodsHtml+'</div>'
    +hintMixto
    +'<div class="pay-amount-row"><input type="number" inputmode="decimal" id="payAmountInput" placeholder="Monto"><button class="pay-exact-btn" id="payExactBtn">Exacto</button></div>'
    +'<button class="pay-add-btn" id="payAddBtn">+ Agregar pago</button>'
    +(listHtml?'<div class="pay-list">'+listHtml+'</div>':'')
    +'<div class="paymix-summary" id="paySummary"></div>';
  refreshCheckout();
}
// avisa algo puntual (método sin elegir, monto vacío) reusando la caja de resumen, sin inventar un sistema de toasts nuevo
function payWarn(msg){
  const summary=$('paySummary');if(!summary)return;
  summary.className='paymix-summary falta';summary.textContent=msg;
}
$('pay').addEventListener('click',e=>{
  const mBtn=e.target.closest('.pm-btn');
  if(mBtn){ selMethod=mBtn.dataset.id; renderPay(); const inp=$('payAmountInput'); if(inp)inp.focus(); return; }
  if(e.target.closest('#payExactBtn')){
    if(!selMethod){payWarn('Elige primero el método de pago');return}
    const remainBs=Math.max(0,cartSubtotalBs()-payments.reduce((s,p)=>s+convertToBs(p.amount,p.currency),0));
    const cur=payCurrency[selMethod]||'bs';
    const mEx=monedaActivaById(cur);
    const val=cur==='usd'?bsToUsdRaw(remainBs):cur==='usd_bcv'?bsToBcvRaw(remainBs):mEx?(bsToMonedaRaw(remainBs,mEx)||0):remainBs;
    $('payAmountInput').value=(cur==='usd'||cur==='usd_bcv')?val.toFixed(2):Math.round(val);
    return;
  }
  if(e.target.closest('#payAddBtn')){
    if(!selMethod){payWarn('Elige primero el método de pago');return}
    const val=parseFloat($('payAmountInput').value);
    if(!val||val<=0){payWarn('Escribe el monto recibido');return}
    const m=payMethods.find(x=>x.id===selMethod);
    payments.push({method:selMethod,label:m.label,currency:payCurrency[selMethod]||'bs',amount:val});
    renderPay();
    return;
  }
  const rm=e.target.closest('[data-rm]');
  if(rm){ payments.splice(parseInt(rm.dataset.rm,10),1); renderPay(); }
});

function cartSubtotalBs(){
  const ids=Object.keys(cart).filter(id=>cart[id]>0);
  return ids.reduce((s,id)=>{const p=products.find(x=>x.id===id);if(!p)return s;return s+effectivePrices(p).bs*cart[id]},0);
}
// refresca el resumen de pago y el botón de cobrar sin re-renderizar los inputs (para no perder el foco al escribir)
function refreshCheckout(){
  const subtotalBs=cartSubtotalBs();
  const ids=Object.keys(cart).filter(id=>cart[id]>0);
  const count=ids.reduce((s,id)=>s+cart[id],0);
  let remain=0;
  const eps=paymentToleranceBs(payments);
  if(saleType==='pagado'){
    const paidBs=payments.reduce((s,p)=>s+convertToBs(p.amount,p.currency),0);
    remain=Math.round((subtotalBs-paidBs)*100)/100;
    const summary=$('paySummary');
    if(summary){
      let cls='',txt='';
      if(!subtotalBs){cls='';txt='';}
      else if(remain>eps){
        cls='falta';
        // v27 — bug encontrado de paso: esto exigía cfg.bcv>0 para mostrar el "$" de la falta,
        // así que un negocio con SOLO Paralelo cargado (sin BCV) nunca veía el equivalente en
        // dólares acá, aunque bsToUsdRaw() sí tuviera con qué calcularlo. rateBsActual()>0 es la
        // misma condición que ya usa multiCurrencyLine/otherCurrenciesLine para esto mismo.
        txt='Falta '+fmtBs(remain)+(rateBsActual()>0?' · '+fmtUSD(bsToUsdRaw(remain)):'')+monedasActivas().map(m=>' = '+fmtMonedaVal(bsToMonedaRaw(remain,m),m)).join('');
      }
      else if(remain<-eps){
        const vuelto=-remain;
        cls='cambio';
        txt='Vuelto: '+fmtBs(vuelto)+(rateBsActual()>0?' · '+fmtUSD(bsToUsdRaw(vuelto)):'')+monedasActivas().map(m=>' = '+fmtMonedaVal(bsToMonedaRaw(vuelto,m),m)).join('');
      }
      else{cls='ok';txt='Cubierto ✓ listo para cobrar';}
      summary.className='paymix-summary'+(cls?' '+cls:'');
      summary.textContent=txt;
    }
  }
  const btn=$('checkoutBtn');
  const needsClient=saleType==='fiado';
  if(!count){btn.disabled=true;btn.textContent='Agrega productos para cobrar';return}
  if(needsClient){
    btn.disabled=!fiarClientId;
    const c=clients.find(x=>x.id===fiarClientId);
    btn.textContent=fiarClientId?('Fiar '+formatByCurrency(subtotalBs,'bs')+' a '+(c?c.name:'')):'Elige un cliente para fiar';
    return;
  }
  if(remain>eps){btn.disabled=true;btn.textContent='Falta '+fmtBs(remain)+' para cobrar'}
  else{btn.disabled=false;btn.textContent=remain<-eps?('Cobrar · vuelto '+fmtBs(-remain)):('Cobrar '+fmtBs(subtotalBs))}
}

// config: density (v1.11 — teléfono y pantalla grande tienen su propia preferencia, cada una
// con su propio rango de opciones: 1/2/3 en teléfono, 4/5/6 en pantalla grande. Antes había un
// solo número para los dos, y en el teléfono terminaba viéndose distinto a lo que decía el
// selector porque el CSS lo achicaba solo a partir de cierto ancho — confuso: elegías "6" y
// veías 3. Ahora lo que eliges es lo que se ve, porque cada dispositivo tiene su propio valor.
const DENSITY_BREAKPOINT='(max-width:520px)'; // mismo corte que ya usaba el CSS para achicar la grilla
function esPantallaChica(){ return window.matchMedia(DENSITY_BREAKPOINT).matches; }
function densidadActual(){ return esPantallaChica()?(cfg.densityMobile||'3'):(cfg.densityDesktop||'5'); }
function aplicarDensidadAGrillas(){
  const d=densidadActual();
  $('grid').dataset.d=d; $('previewGrid').dataset.d=d;
  document.querySelectorAll('#densityToggleMobile button').forEach(x=>x.classList.toggle('on',x.dataset.d===cfg.densityMobile));
  document.querySelectorAll('#densityToggleDesktop button').forEach(x=>x.classList.toggle('on',x.dataset.d===cfg.densityDesktop));
}
$('densityToggleMobile').addEventListener('click',e=>{const b=e.target.closest('button');if(!b)return;cfg.densityMobile=b.dataset.d;aplicarDensidadAGrillas();renderPreview()});
$('densityToggleDesktop').addEventListener('click',e=>{const b=e.target.closest('button');if(!b)return;cfg.densityDesktop=b.dataset.d;aplicarDensidadAGrillas();renderPreview()});
// si giras el teléfono, o cambias el tamaño de la ventana entre angosta y ancha, la grilla
// tiene que saltar sola entre la preferencia de teléfono y la de pantalla grande.
window.matchMedia(DENSITY_BREAKPOINT).addEventListener('change',aplicarDensidadAGrillas);
function renderPreview(){
  const demo=products.slice(0,3);
  $('previewGrid').innerHTML=demo.map(p=>cardHTML(p,0)).join('');
}

// grid
function renderChips(){
  if(!categories.some(c=>c.id===activeCat)&&activeCat!=='todos')activeCat='todos';
  $('chips').innerHTML=catsForChips().map(c=>'<button class="chip'+(c===activeCat?' on':'')+'" data-cat="'+c+'">'+(c==='todos'?'Todos':catLabel[c])+'</button>').join('');
}
renderChips();
$('chips').addEventListener('click',e=>{const b=e.target.closest('.chip');if(!b)return;activeCat=b.dataset.cat;document.querySelectorAll('.chip').forEach(x=>x.classList.toggle('on',x===b));renderGrid()});

function cardHTML(p,i){
  const bg=catColor[p.cat]||'#3E7BFA';
  const av=p.photo
    ? '<div class="avatar" style="padding:0;overflow:hidden"><img src="'+p.photo+'" style="width:100%;height:100%;object-fit:cover"></div>'
    : '<div class="avatar" style="background:'+bg+'18"><span class="initxt" style="color:'+bg+'">'+p.name.trim().charAt(0).toUpperCase()+'</span></div>';
  const pr=effectivePrices(p);
  const porKgSuf=p.ventaPeso?' <span style="color:var(--text-faint);font-weight:600">/Kg</span>':'';
  // v46 — bug real corregido: la tarjeta mostraba SIEMPRE el USD oferta (fmtUSD(pr.contado)) sin
  // importar qué moneda esté activa en el carrito (activeCurrency), así que un negocio que apagó
  // "Mostrar USD oferta" (cobra a precio BCV) veía un precio en la grilla distinto al que después
  // le cobraba el carrito por el mismo producto. formatByCurrency(pr.bs,activeCurrency) es la
  // misma llamada que ya usa renderCart() para la línea del carrito — ahora tarjeta y carrito
  // siempre muestran el mismo número, cambien o no de moneda con el toggle de arriba.
  const priceMain=pr.onOffer
    ? '<span class="pr" style="color:var(--red)">'+formatByCurrency(pr.bs,activeCurrency)+'</span> <span style="text-decoration:line-through;color:var(--text-faint);font-size:.7em">'+formatByCurrency(pr.original.bs,activeCurrency)+'</span>'+porKgSuf
    : '<span class="pr">'+formatByCurrency(pr.bs,activeCurrency)+'</span>'+porKgSuf;
  // v47 — "si tengo bolívares y dólar activo, quiero que los muestre allí también": la tarjeta
  // solo mostraba UN número (el de activeCurrency); ahora, con Bs activo, agrega debajo la misma
  // segunda moneda que ya acompaña al total del carrito (BCV, o USD oferta de respaldo).
  const priceSub=secondaryCurrencyLine(pr.bs);
  const priceHTML='<div class="pr-wrap">'+priceMain+(priceSub?'<div class="pr-sub">'+priceSub+'</div>':'')+'</div>';
  const unitLbl=formatUnitLabel(p);
  const nameLine=p.name+(unitLbl?' <span style="color:var(--text-faint);font-weight:600">· '+unitLbl+'</span>':'');
  return '<button class="card" data-id="'+p.id+'" style="animation-delay:'+i*30+'ms">'
    +av
    +'<div class="nprow"><span class="nm">'+nameLine+'</span>'+priceHTML+'</div></button>';
}
function renderGrid(){
  const q=norm($('search').value);
  const items=products.filter(p=>(activeCat==='todos'||p.cat===activeCat)&&(!q||norm(p.name).includes(q)));
  // aviso de caja cerrada (v22): que se vea ANTES de tocar un producto, no solo al intentarlo
  const avisoCaja=openTurno?'':'<div class="caja-cerrada" style="grid-column:1/-1">'
    +'<b>La caja está cerrada</b>'
    +'<span>Abre el turno para poder vender. Así el arqueo te cuadra al final del día.</span>'
    +'<button class="btn-primary" id="irAlCajon">Abrir caja</button>'
    +'</div>';
  $('grid').innerHTML=avisoCaja+(items.map(cardHTML).join('')||'<div class="cart-empty" style="grid-column:1/-1"><b>Nada por aquí</b>No hay productos con ese nombre o categoría.</div>');
}
$('grid').addEventListener('click',e=>{
  if(e.target.closest('#irAlCajon')){ goSection('cajon'); return; }
  const c=e.target.closest('.card');if(!c)return;
  if(!exigirTurnoAbierto())return;          // sin caja abierta no se puede vender
  const p=products.find(x=>x.id===c.dataset.id);
  if(p&&p.ventaPeso){ openPesoModal(p.id); return; }  // se pesa en el mostrador, no se suma 1 directo
  cart[c.dataset.id]=(cart[c.dataset.id]||0)+1;
  renderCart();
});
// Guardia de turno (v22): en una bodega el turno de caja es lo que hace que el arqueo cuadre y
// que el Reporte Z tenga sentido. Si se vende sin turno abierto, esas ventas no quedan amarradas
// a ningún cierre y el cuadre del día se pierde. Por eso ahora se exige abrir caja primero.
function exigirTurnoAbierto(){
  if(openTurno)return true;
  askConfirm('Tienes que abrir la caja antes de vender. Te llevo al Cajón para que cuentes con cuánto arrancas.',()=>{
    goSection('cajon');
  });
  return false;
}
$('search').addEventListener('input',renderGrid);

// cart — el subtotal se acumula en bolívares (la unidad más estable) y se convierte al mostrar
function renderCart(){
  const ids=Object.keys(cart).filter(id=>cart[id]>0);
  const count=ids.reduce((s,id)=>s+cart[id],0);
  const subtotalBs=ids.reduce((s,id)=>{const p=products.find(x=>x.id===id);if(!p)return s;return s+effectivePrices(p).bs*cart[id]},0);
  $('cartCount').textContent='('+count+')';
  $('mbarN').textContent=count;
  $('mbarAmt').textContent=formatByCurrency(subtotalBs,'bs');
  $('mbar').classList.toggle('show',count>0&&!cartOpen);
  if(!ids.length){
    $('cartItems').innerHTML='<div class="cart-empty"><b>Tu carrito está vacío</b>Toca un producto para agregarlo.</div>';
  } else {
    $('cartItems').innerHTML=ids.map(id=>{
      const p=products.find(x=>x.id===id);
      const lineBs=effectivePrices(p).bs;
      // venta por peso (v45): sin stepper ±1 (no tiene sentido saltar de a 1 Kg) — un botón
      // "Editar" reabre el modal de peso precargado, y un botón quitar borra la línea directo.
      const qtyCtrl=(p&&p.ventaPeso)
        ? '<div class="stepper"><button data-act="editpeso" style="width:auto;padding:0 9px;font-size:.72rem;white-space:nowrap">✎ '+formatPeso(cart[id])+'</button><button data-act="rm">✕</button></div>'
        : '<div class="stepper"><button data-act="dec">−</button><div class="q">'+cart[id]+'</div><button data-act="inc">+</button></div>';
      return '<div class="item" data-id="'+id+'"><div style="flex:1 1 auto"><div class="inm">'+p.name+'</div><div class="isub">'+formatByCurrency(lineBs,activeCurrency)+(p&&p.ventaPeso?' /Kg':' c/u')+'</div></div>'
        +qtyCtrl
        +'<div class="iprice">'+formatByCurrency(lineBs*cart[id],activeCurrency)+'</div></div>';
    }).join('');
  }
  $('heroValue').textContent=formatByCurrency(subtotalBs,activeCurrency);
  if(!count){
    $('heroSub').textContent='Agrega productos para ver el total';
  } else if(activeCurrency==='bs'){
    // v27 — bajo el total en Bs: el USD BCV (vitrina), que es el que de verdad acompaña al
    // bolívar en el estante. Si no hay BCV+Paralelo a la vez, cae al USD oferta como respaldo
    // (mejor mostrar algo en dólares que nada). El resto de pestañas (oferta, BCV solo, COP,
    // Euro...) van solas abajo — pedido explícito: mezclarlas con "tanto en bolívares" confundía
    // más de lo que ayudaba, cada pestaña ahora muestra ÚNICAMENTE su propia moneda.
    $('heroSub').textContent = secondaryCurrencyLine(subtotalBs);
  } else {
    $('heroSub').textContent='';
  }
  if(saleSuccessOpen)return; // no reconstruir el panel de pago mientras se muestra "venta lista"
  if(!ids.length&&(payments.length||selMethod)){ payments=[];selMethod=null;renderPay(); }
  else refreshCheckout();
}
$('cartItems').addEventListener('click',e=>{
  const btn=e.target.closest('button[data-act]');if(!btn)return;
  const id=btn.closest('.item').dataset.id;
  if(btn.dataset.act==='editpeso'){ openPesoModal(id); return; }
  if(btn.dataset.act==='rm'){ delete cart[id]; renderCart(); return; }
  if(btn.dataset.act==='inc')cart[id]++;else{cart[id]--;if(cart[id]<=0)delete cart[id]}
  renderCart();
});
$('currToggle').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  activeCurrency=b.dataset.c;
  document.querySelectorAll('#currToggle button').forEach(x=>x.classList.toggle('on',x===b));
  renderCart();
  renderPay();   // los métodos de pago se filtran por esta moneda (v23)
  renderGrid();  // v46 — las tarjetas de producto ahora también muestran precio en activeCurrency
});
function setCartOpen(v){cartOpen=v;$('cart').classList.toggle('open',v);$('backdrop').classList.toggle('show',v);if(v)$('mbar').classList.remove('show');else renderCart()}
$('mbar').addEventListener('click',()=>setCartOpen(true));
$('cartClose').addEventListener('click',()=>setCartOpen(false));
$('backdrop').addEventListener('click',()=>setCartOpen(false));

// ===== Inventario =====
// calculadora de costo alineada con el Excel de control fiscal SENIAT de Jonathan (v17):
//   costo base (por unidad, o por bulto ÷ cantidad) en la MONEDA DE COMPRA -> se pasa a dólar
//   real = costo unitario efectivo (USD, SIN impuestos).
// v28 — "Costos extra" (recargo por divisa + gasto extra) se quitó: eran dos campos más para
// algo que "Gastos operativos %" (computePrices) ya cubre — un solo lugar para gastos, no dos.
// El IVA ya NO se hornea aquí (antes ×(1+IVA%)): ahora se controla aparte por producto con el
// interruptor "¿Sujeto a IVA?" + la tasa única de Configuración, para poder separar IVA Débito/Crédito
// fiscal (ver computePrices). Ese costo sin impuestos es el que computePrices multiplica por
// (1+ganancia) para dar la Base Imponible.
function calcCostBcv(){
  const buyMode=document.querySelector('#prodBuyMode button.on')?.dataset.v||'unidad';
  const buyCur=document.querySelector('#prodBuyCur button.on')?.dataset.v||'usd';
  let baseInBuyCur=0;
  if(buyMode==='bulto'){
    const bulkCost=parseFloat($('prodBulkCost').value)||0;
    const bulkUnits=parseFloat($('prodBulkUnits').value)||0;
    baseInBuyCur=bulkUnits>0?bulkCost/bulkUnits:0;
  } else {
    baseInBuyCur=parseFloat($('prodUnitCost').value)||0;
  }
  // pasar el costo base a USD según la moneda en que se compró
  let baseUsd=baseInBuyCur;
  if(buyCur==='bs'){ const r=rateBsActual(); baseUsd = r>0 ? baseInBuyCur/r : 0; }  // compras en Bs -> $ a Paralelo (o BCV si no hay Paralelo cargado)
  else if(buyCur==='usd'){
    // v48 — bug real: un proveedor formal factura en USD a tasa BCV (oficial), no a tasa
    // Paralelo/real — pagar $25.50 "BCV" no es lo mismo que pagar $25.50 reales. Antes ese
    // número entraba directo como costo real y el precio de venta salía inflado. Ahora se
    // "destapa" a bolívares con la tasa a la que de verdad se facturó (BCV) y de ahí se vuelve
    // a pasar a dólar real (÷ la tasa del motor, Paralelo). Sin BCV cargado no hay de dónde
    // partir la conversión — se deja el número tal cual, como siempre.
    const r=rateBsForPrices();
    baseUsd = (cfg.bcv>0 && r>0) ? (baseInBuyCur*cfg.bcv)/r : baseInBuyCur;
  }
  else {
    // compras en una moneda propia: se pasa a dólares PARALELOS y de ahí a la unidad de costo
    // del motor (dólares a tasa de precios), para no mezclar los dos dólares.
    const mb=monedaActivaById(buyCur), rp=rateParaleloParaMonedas(), rB=rateBsForPrices();
    if(mb&&rp>0&&rB>0) baseUsd = (baseInBuyCur/mb.tasa*rp)/rB;
    else if(mb) baseUsd = 0;
  }
  let cost=baseUsd;
  // v28 — "Costos extra" (recargo por divisa + gasto extra por unidad/lote) se quitó del
  // formulario a pedido de Jonathan: dos campos de más para algo que ya cubre "Gastos
  // operativos %" de abajo. El costo ahora es directo: lo que pagaste, convertido a dólar real.
  // v20 — EL COSTO DEL PROVEEDOR PUEDE VENIR CON EL IVA YA ADENTRO.
  // Pasa a cada rato: el proveedor factura el monto totalizado en vez de poner la base y el IVA
  // aparte. Todo el motor de precios trabaja con el costo SIN impuestos (esa es la base sobre la
  // que se calculan gastos, ganancia y el IVA Crédito Fiscal), así que si el usuario dice que el
  // monto ya viene totalizado hay que "destaparlo" hacia atrás:  base = total / (1 + IVA).
  // Solo aplica si el producto está sujeto a IVA — si es exento, el proveedor no le cobró IVA.
  if(costoTraeIvaIncluido()){
    const ivaRate=(cfg.ivaGeneral||0)/100;
    if(ivaRate>0)cost=cost/(1+ivaRate);
  }
  return cost;
}
// ¿el usuario marcó que el costo escrito ya viene con IVA? (solo cuenta si el producto es gravado)
function costoTraeIvaIncluido(){
  const gravado=document.querySelector('#prodIvaSubject button.on')?.dataset.v==='si';
  if(!gravado)return false;
  return document.querySelector('#prodCostIvaIncl button.on')?.dataset.v==='si';
}
// "sobre costo" es markup directo (ya es lo que usa computePrices). "sobre venta" es margen bruto
// (% del precio final) — hay que convertirlo al markup equivalente para que el motor de precios dé el mismo resultado.
function calcMarginPct(){
  const mode=document.querySelector('#prodMarginMode button.on')?.dataset.v||'costo';
  const pct=parseFloat($('prodMarginPct').value)||0;
  if(mode==='venta'){
    if(pct>=100)return 0;
    return pct/(1-pct/100);
  }
  return pct;
}
// muestra/oculta los campos del formulario de producto según el modelo de precios activo:
// - modelo 'gastos': aparece "% Gastos operativos", y la ganancia es siempre sobre costo (se
//   esconde el toggle Sobre costo/Sobre venta, porque el Excel de ese modelo no lo contempla)
// - modelo 'margen': se esconde "% Gastos operativos" y vuelve el toggle de modo de margen
function updateCostIvaNota(){
  const el=$('prodCostIvaNota');if(!el)return;
  el.innerHTML=costoTraeIvaIncluido()
    ? 'Le voy a sacar el '+(cfg.ivaGeneral||0)+'% para quedarme con la base, que es sobre la que se calculan tus gastos, tu ganancia y el IVA que puedes descontar.'
    : 'Perfecto, uso el monto tal cual como base de tu costo.';
}
function applyPriceModelToProductForm(){
  // v5 — con un solo modelo el formulario ya no cambia de forma. Se conserva la función (la
  // llaman varios sitios) y ahora hace lo que sí quedó dinámico: las monedas del negocio.
  $('prodGastosHead').style.display='';
  $('prodGastosRow').style.display='';
  $('prodMarginMode').style.display='none';
  $('prodMarginNote').textContent='Se calcula sobre el costo base, aparte de los gastos operativos';
  const cont=$('prodBuyCur');
  if(cont){
    const sel=cont.querySelector('button.on')?cont.querySelector('button.on').dataset.v:'usd';
    cont.innerHTML='<button data-v="usd">USD</button><button data-v="bs">Bs</button>'
      +monedasActivas().map(m=>'<button data-v="'+m.id+'">'+m.nombre+'</button>').join('');
    const keep=cont.querySelector('[data-v="'+sel+'"]')||cont.querySelector('[data-v="usd"]');
    if(keep)keep.classList.add('on');
  }
  const sd=$('selDebtMode');
  if(sd){
    const cur=cfg.debtMode;
    [].slice.call(sd.querySelectorAll('option[data-moneda]')).forEach(o=>o.remove());
    const dinamica=sd.querySelector('option[value="dinamico"]');
    monedasActivas().forEach(m=>{
      const o=document.createElement('option');
      o.value=m.id; o.setAttribute('data-moneda','1');
      o.textContent='Fijo en '+m.nombre+' (del día que se fió)';
      sd.insertBefore(o,dinamica);
    });
    sd.value=sd.querySelector('option[value="'+cur+'"]')?cur:'paralelo';
    cfg.debtMode=sd.value;
  }
}
// v1.21 — arma un producto "borrador" con lo que hay escrito AHORA MISMO en el formulario,
// según el modelo de precio activo — para que la vista previa use la MISMA función real
// (effectivePrices/basePriceOf) que usa el resto de la app al vender, en vez de repetir la
// cuenta a mano y arriesgarse a que las dos versiones se desincronicen con el tiempo.
function draftProductFromForm(){
  const mode=currentPricingMode();
  const ivaSubject=mode==='simple'?false:document.querySelector('#prodIvaSubject button.on')?.dataset.v==='si';
  const offerPrice=parseFloat($('prodOfferPrice').value)||0;
  if(mode==='simple'){
    return {
      pricingMode:'simple', ivaSubject, offerPrice,
      simplePrecio:parseFloat($('prodSimplePrecio').value)||0,
      simpleMoneda:document.querySelector('#prodSimpleMoneda button.on')?.dataset.v||'paralelo',
      simpleIvaIncluido:document.querySelector('#prodSimpleIvaIncl button.on')?.dataset.v!=='no'
    };
  }
  const gastosRaw=parseFloat($('prodGastosPct').value);
  return {
    pricingMode:'personalizado', ivaSubject, offerPrice,
    costBcv:calcCostBcv(), margin:calcMarginPct(),
    gastosPct:isMedio()?0:(isNaN(gastosRaw)?null:gastosRaw)
  };
}
// v49 — el número de cada línea SIGUE calculándose exactamente igual que siempre (computePrices/
// effectivePrices, ancladas en dólar real/Paralelo — eso no cambia). Lo que cambia es que ahora se
// muestran las 4 piezas del desglose fiscal por separado, en su equivalente bolívar/BCV (con el
// literal en Bs al lado), y la línea de Oferta queda claramente aparte: "dato tuyo, no es lo que
// va en la factura". Si no hay tasa BCV cargada, esas 4 líneas caen a mostrar solo el bolívar.
// v50 — mismo criterio reusado en Inventario y Pedidos: el dólar que se muestra ya no es el
// oferta (informal), es su equivalente BCV. Si no hay BCV cargado, se deja el oferta tal cual —
// no hay otro dólar de dónde sacarlo (mismo respaldo que ya usa el recibo/catálogo público).
function toBcvUsd(usdOferta){ return cfg.bcv>0 ? ((usdOferta||0)*rateBsForPrices())/cfg.bcv : (usdOferta||0); }
function fiscalLineVal(usdOferta){
  const bs=(usdOferta||0)*rateBsForPrices();
  return cfg.bcv>0 ? (fmtUSD(toBcvUsd(usdOferta))+' ('+fmtBs(bs)+')') : fmtBs(bs);
}
function priceBreakdownHTML(pr,ivaSubject){
  const ivaPct=cfg.ivaGeneral||0;
  const baseImponibleUsd=ivaSubject?(pr.baseImponible||0):0;
  const baseExentaUsd=ivaSubject?0:(pr.baseImponible||0);
  // v50 — pedido de Jonathan: si tiene apagado "Mostrar USD oferta" en Tasas de cambio, este dato
  // ni siquiera debe aparecer acá — sería mostrarle un número que él mismo decidió no usar.
  const ofertaLine=(rateParaleloParaMonedas()>0 && cfg.mostrarUsdOferta!==false)
    ? 'Oferta: <b>'+fmtUSD(pr.contado)+'</b> <span style="color:var(--text-faint)">(dato tuyo — no va en la factura, no lleva bolívares)</span><br>'
    : '';
  return 'Base Imponible ('+ivaPct+'%): <b>'+fiscalLineVal(baseImponibleUsd)+'</b><br>'+
    'Monto del IVA ('+ivaPct+'%): <b>'+fiscalLineVal(pr.ivaDebito||0)+'</b><br>'+
    'Base Exenta: <b>'+fiscalLineVal(baseExentaUsd)+'</b><br>'+
    'PVP Final: <b>'+fiscalLineVal(pr.contado)+'</b><br>'+
    ofertaLine;
}
function updateProdPreview(){
  const draft=draftProductFromForm();
  if(draft.pricingMode==='simple'){
    const pr=effectivePrices(draft);
    const baseCalc=basePriceOf(draft); // sin la oferta encima, para explicar qué significa lo que escribió
    let nota='';
    if(draft.ivaSubject && draft.simpleIvaIncluido===false && draft.simplePrecio>0){
      nota='Con el '+(cfg.ivaGeneral||0)+'% de IVA sumado encima, el precio final queda en <b>'+fmtUSD(baseCalc.contado)+'</b>.';
    }
    $('prodSimpleNota').innerHTML=nota;
    let offerLine='';
    if(draft.offerPrice>0){
      offerLine='<br>Precio de oferta: <b style="color:var(--red)">'+fmtUSD(draft.offerPrice)+'</b> <span style="text-decoration:line-through;color:var(--text-faint)">'+fmtUSD(baseCalc.contado)+'</span>';
    }
    $('prodPreview').innerHTML=
      '<div style="color:var(--amber);margin-bottom:6px">Sin costo registrado en este producto — no vamos a poder calcular tu ganancia real de él en Informes.</div>'+
      priceBreakdownHTML(pr,draft.ivaSubject)+
      monedasActivas().map(m=>m.nombre+': <b>'+fmtMonedaVal(pr.monedas[m.id],m)+'</b><br>').join('')+offerLine;
    return;
  }
  const costUsd=draft.costBcv, marginPct=draft.margin, ivaSubject=draft.ivaSubject;
  let pr=computePrices(costUsd,marginPct,ivaSubject,draft.gastosPct);
  let offerLine='';
  if(draft.offerPrice>0){
    offerLine='<br>Precio de oferta: <b style="color:var(--red)">'+fmtUSD(draft.offerPrice)+'</b> <span style="text-decoration:line-through;color:var(--text-faint)">'+fmtUSD(pr.contado)+'</span>';
  }
  // v50 — "Costo unitario"/Gastos/Ganancia se quedaban en escala oferta (dólar real) mientras el
  // resto del desglose (Base Imponible, PVP Final, etc.) ya se muestra en BCV — desentonaba (el
  // dueño esperaba ver acá el número que reconoce de su factura BCV, no el "real" convertido, que
  // es solo para el cálculo interno). Con fiscalLineVal() queda todo en la misma escala.
  const desglose='Gastos operativos: <b>'+fiscalLineVal(pr.montoGastos)+'</b> · Ganancia: <b>'+fiscalLineVal(pr.montoGanancia)+'</b><br>';
  const creditoLines=ivaSubject
    ? 'IVA estimado en costos: <b>'+fmtUSD(pr.ivaCredito)+'</b> · Diferencia SENIAT estimada: <b>'+fmtUSD(pr.diferenciaSeniat)+'</b><br>'+
      '<span style="color:var(--text-faint);font-size:.78rem">Esto es costeo. El IVA Crédito Fiscal real para declarar sale de tus facturas de compra, en Contabilidad.</span><br>'
    : '';
  let destapeLine='';
  if(costoTraeIvaIncluido()){
    const ivaRate=(cfg.ivaGeneral||0)/100;
    destapeLine='Escribiste <b>'+fiscalLineVal(costUsd*(1+ivaRate))+'</b> con IVA incluido, así que la base de tu costo es <b>'+fiscalLineVal(costUsd)+'</b> (le saqué el '+(cfg.ivaGeneral||0)+'%)<br>';
  }
  // Ley de Precios Justos: tope de 30% de ganancia en la cadena de comercialización.
  const avisoMargen=(marginPct>30)
    ? '<div style="color:var(--amber);margin-top:6px">Ojo: le estás poniendo '+marginPct.toFixed(0)+'% de ganancia. La Ley de Precios Justos fija un tope de 30%.</div>'
    : '';
  $('prodPreview').innerHTML=
    destapeLine+
    'Costo unitario (sin impuestos): <b>'+fiscalLineVal(costUsd)+'</b><br>'+
    desglose+
    priceBreakdownHTML(pr,ivaSubject)+
    creditoLines+
    monedasActivas().map(m=>m.nombre+': <b>'+fmtMonedaVal(pr.monedas[m.id],m)+'</b><br>').join('')+offerLine+avisoMargen;
}
$('prodUnitType').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  document.querySelectorAll('#prodUnitType button').forEach(x=>x.classList.toggle('on',x===b));
  const v=b.dataset.v;
  $('prodUnitValueRow').style.display=v==='unidad'?'none':'';
  $('prodUnitValueLabel').textContent=v==='peso'?'Peso del envase':'Volumen del envase';
  $('prodUnitValue').placeholder=v==='peso'?'Ej: 750 g, 1 kg, 1.5 Kg':'Ej: 500 ml, 1 L, 1.5 L';
  updateUnitHint();
});
function updateUnitHint(){
  const v=document.querySelector('#prodUnitType button.on')?.dataset.v||'unidad';
  if(v==='unidad'){$('prodUnitHint').textContent='';return}
  const parsed=parseUnitValue($('prodUnitValue').value,v);
  $('prodUnitHint').textContent=parsed?('Se guarda como: '+formatUnitLabel({unitType:v,unitValueBase:parsed})):($('prodUnitValue').value?'No se entendió esa cantidad — prueba "750 g" o "1.5 Kg"':'');
}
$('prodUnitValue').addEventListener('input',updateUnitHint);
// venta por peso (v45): mutuamente excluyente con "Cómo se vende" (unitType) — ese campo es solo
// la etiqueta del contenido de un envase fijo (ej. "750 g" de harina), no tiene sentido a la vez
// que el producto se pese suelto en el mostrador. Un solo toggle decide cuál de los dos se ve, y
// relabelea los campos de precio/costo para dejar claro que ahora son "por Kg".
function applyVentaPesoToProductForm(){
  const on=document.querySelector('#prodVentaPeso button.on')?.dataset.v==='si';
  $('prodComoSeVendeWrap').style.display=on?'none':'';
  $('prodVentaPesoNota').style.display=on?'':'none';
  $('prodSimplePrecioLabel').textContent=on?'Precio final (por Kg)':'Precio final';
  $('prodUnitCostLabel').textContent=on?'Costo por unidad (por Kg)':'Costo por unidad';
}
// v50 — Medio BCV ya es real: usa el mismo motor de costo+margen que Personalizado (nada más le
// falta el % de Gastos operativos, ver applyBusinessModeToProductForm/isMedio). Solo Simple usa
// el motor de precio final directo.
function currentPricingMode(){ return cfg.businessMode==='simple'?'simple':'personalizado'; }
function isMedio(){ return cfg.businessMode==='medio'; }
// muestra/oculta los campos del formulario de producto según el modo de negocio elegido en
// Configuración → Precios y tasas (antes era un interruptor #prodPricingMode DENTRO de cada
// producto — se quitó, ver el comentario en el HTML). En Simple se esconden todos los campos que
// no hacen falta para "ya tengo el precio final, solo quiero venderlo" — marca, SKU, stock inicial
// (eso se carga aparte en Stock), Cómo se vende, oferta manual, Catálogo Digital e Impuestos.
function applyBusinessModeToProductForm(){
  const simple=currentPricingMode()==='simple';
  ['prodBrandRow','prodSkuRow','prodStockRow','prodOfferPriceRow','prodCatalogoWrap','prodImpuestosWrap'].forEach(id=>{
    $(id).style.display=simple?'none':'';
  });
  applyVentaPesoToProductForm(); // labels "(por Kg)" y Cómo-se-vende según el toggle de peso
  if(simple){
    // en Simple, Cómo-se-vende no existe ni para productos que NO se pesan: el interruptor
    // "¿se vende pesado?" ya decide todo (sí→peso, no→unidad), sin más opciones que elegir.
    $('prodComoSeVendeWrap').style.display='none';
    $('prodVentaPesoNota').style.display='none';
  }
  $('prodSimpleWrap').style.display=simple?'':'none';
  $('prodPersonalizadoWrap').style.display=simple?'none':'';
  // v50 — Medio BCV usa costo+margen igual que Personalizado, pero SIN el % de Gastos operativos
  // (pedido de Jonathan: "gastos operativos eliminados" en ese modo) — se esconde el campo y el
  // guardado (ver prodSave) fuerza gastosPct=0 para que no quede un valor viejo escondido.
  const medio=isMedio();
  $('prodGastosHead').style.display=medio?'none':'';
  $('prodGastosRow').style.display=medio?'none':'';
  updateProdPreview();
}
// v50 — pedido de Jonathan: en Simple no tiene sentido pedir Patente/ISLR/Contribuyente Especial
// (esos porcentajes solo importan si estás calculando costo real en Contabilidad, y Simple no
// registra costo). En Personalizado/Medio se queda visible.
// Medio BCV además: sin Otras monedas, sin Gastos por defecto, y con mostrarUsdBcv/mostrarUsdOferta/
// Orden de monedas FIJOS (no editables) — al entrar a Medio se fuerza ese perfil una sola vez desde
// el click del selector (ver businessModeToggle), acá solo se esconden los controles.
function applyBusinessModeToConfigBlocks(){
  const simple=currentPricingMode()==='simple';
  const medio=isMedio();
  $('costoGastosGananciaExplain').style.display=simple?'none':'';
  $('otrosImpuestosBlock').style.display=simple?'none':'';
  $('monedasBlock').style.display=medio?'none':'';
  $('gastosDefaultRow').style.display=medio?'none':'';
  ['mostrarUsdBcvRow','mostrarUsdBcvToggle','mostrarUsdOfertaRow','mostrarUsdOfertaToggle','currOrderBlock'].forEach(id=>{
    $(id).style.display=medio?'none':'';
  });
  updateExample();
}
$('prodVentaPeso').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  document.querySelectorAll('#prodVentaPeso button').forEach(x=>x.classList.toggle('on',x===b));
  applyVentaPesoToProductForm();
});
$('prodOfferPrice').addEventListener('input',updateProdPreview);
$('prodBuyMode').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  document.querySelectorAll('#prodBuyMode button').forEach(x=>x.classList.toggle('on',x===b));
  $('prodUnitCostRow').style.display=b.dataset.v==='unidad'?'':'none';
  $('prodBulkRows').style.display=b.dataset.v==='bulto'?'':'none';
  updateProdPreview();
});
$('prodBuyCur').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  document.querySelectorAll('#prodBuyCur button').forEach(x=>x.classList.toggle('on',x===b));
  updateProdPreview();
});
$('prodEnCatalogo').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  document.querySelectorAll('#prodEnCatalogo button').forEach(x=>x.classList.toggle('on',x===b));
});
$('prodCostIvaIncl').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  document.querySelectorAll('#prodCostIvaIncl button').forEach(x=>x.classList.toggle('on',x===b));
  updateCostIvaNota();updateProdPreview();
});
$('prodIvaSubject').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  document.querySelectorAll('#prodIvaSubject button').forEach(x=>x.classList.toggle('on',x===b));
  $('prodCostIvaWrap').style.display=b.dataset.v==='si'?'':'none';
  $('prodSimpleIvaInclWrap').style.display=b.dataset.v==='si'?'':'none';
  updateCostIvaNota();
  updateProdPreview();
});
// v49 — el modelo Simple/Personalizado por producto se quitó de aquí — ahora lo decide
// applyBusinessModeToProductForm() según cfg.businessMode (Configuración → Precios y tasas).
$('prodSimplePrecio').addEventListener('input',updateProdPreview);
$('prodSimpleMoneda').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  document.querySelectorAll('#prodSimpleMoneda button').forEach(x=>x.classList.toggle('on',x===b));
  updateProdPreview();
});
$('prodSimpleIvaIncl').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  document.querySelectorAll('#prodSimpleIvaIncl button').forEach(x=>x.classList.toggle('on',x===b));
  updateProdPreview();
});
$('prodMarginMode').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  document.querySelectorAll('#prodMarginMode button').forEach(x=>x.classList.toggle('on',x===b));
  updateProdPreview();
});
['prodUnitCost','prodBulkCost','prodBulkUnits','prodMarginPct','prodGastosPct'].forEach(id=>{
  $(id).addEventListener('input',updateProdPreview);
});
// llena el <select> de categorías del formulario
function fillCatSelect(sel){
  $('prodCat').innerHTML=categories.map(c=>'<option value="'+c.id+'">'+c.label+'</option>').join('');
  if(sel)$('prodCat').value=sel;
}
// foto del producto (data-URL en memoria — se pierde al recargar, normal en el mockup)
let prodPhotoData=null;
// true si el nombre actual del formulario vino de tocar un resultado del catálogo maestro —
// si es así, al guardar NO se manda como sugerencia (ya existe ahí, sería mandarlo de vuelta).
let prodDesdeMaestro=false;
let _prodBuscarTimer=null;
// ---- Catálogo maestro (backend v1.7) — búsqueda mientras se escribe, con internet ----
// A propósito nunca bloquea: sin señal, o si la petición falla, el formulario sigue
// funcionando exactamente igual que siempre, solo sin sugerencias.
async function buscarCatalogoMaestro(q){
  try{
    const res=await fetch('/wp-json/punto/v1/catalogo-maestro/buscar?q='+encodeURIComponent(q));
    const data=await res.json().catch(()=>null);
    if(!res.ok||!data||!data.ok) return [];
    return data.resultados||[];
  }catch(e){
    return [];
  }
}
function renderResultadosMaestro(resultados){
  const cont=$('prodNameResultados');
  if(!resultados.length){ cont.style.display='none'; cont.innerHTML=''; return; }
  cont.innerHTML=resultados.map(r=>
    '<button type="button" data-maestro-id="'+r.id+'">'
    +(r.foto_url?'<img src="'+r.foto_url+'" alt="">':'<div class="ph"></div>')
    +'<div><div class="nm">'+r.nombre+'</div><div class="sub">'+[r.marca,r.categoria].filter(Boolean).join(' · ')+'</div></div>'
    +'</button>'
  ).join('');
  cont.dataset.resultados=JSON.stringify(resultados);
  cont.style.display='';
}
$('prodName').addEventListener('input',()=>{
  prodDesdeMaestro=false;
  clearTimeout(_prodBuscarTimer);
  const q=$('prodName').value.trim();
  if(q.length<2){ $('prodNameResultados').style.display='none'; return; }
  _prodBuscarTimer=setTimeout(async ()=>{
    const resultados=await buscarCatalogoMaestro(q);
    // el nombre pudo cambiar mientras esperaba la respuesta — no pisar algo más nuevo
    if($('prodName').value.trim()===q) renderResultadosMaestro(resultados);
  },350);
});
$('prodNameResultados').addEventListener('click',e=>{
  const btn=e.target.closest('[data-maestro-id]');
  if(!btn)return;
  const resultados=JSON.parse($('prodNameResultados').dataset.resultados||'[]');
  const r=resultados.find(x=>String(x.id)===btn.dataset.maestroId);
  if(!r)return;
  $('prodName').value=r.nombre;
  $('prodBrand').value=r.marca||'';
  $('prodSku').value=r.sku||'';
  if(r.categoria){
    const catExistente=categories.find(c=>c.label.toLowerCase()===r.categoria.toLowerCase());
    if(catExistente)$('prodCat').value=catExistente.id;
  }
  if(r.unidad_tipo&&r.unidad_tipo!=='unidad'){
    document.querySelectorAll('#prodUnitType button').forEach(x=>x.classList.toggle('on',x.dataset.v===r.unidad_tipo));
    $('prodUnitValueRow').style.display='';
    $('prodUnitValueLabel').textContent=r.unidad_tipo==='peso'?'Peso del envase':'Volumen del envase';
    if(r.unidad_valor_base)$('prodUnitValue').value=formatUnitLabel({unitType:r.unidad_tipo,unitValueBase:r.unidad_valor_base});
    updateUnitHint();
  }
  if(r.foto_url){
    prodPhotoData=r.foto_url;
    $('prodPhotoWrap').innerHTML='<img src="'+r.foto_url+'" style="width:100%;height:100%;object-fit:cover;border-radius:10px">';
    $('prodPhotoText').textContent='Cambiar imagen';
  }
  prodDesdeMaestro=true;
  $('prodNameResultados').style.display='none';
  updateProdPreview();
});
// silencioso: si falla o no hay cuenta, no pasa nada — el producto ya se guardó local igual
async function sugerirCatalogoMaestro(datos){
  try{
    const headers={'Content-Type':'application/json'};
    if(puntoAccountToken) headers['Authorization']='Bearer '+puntoAccountToken;
    await fetch('/wp-json/punto/v1/catalogo-maestro/sugerir',{ method:'POST', headers, body:JSON.stringify(datos) });
  }catch(e){}
}
let cropTarget='product'; // v27 — el recortador ahora lo comparten la foto de producto y el logo del negocio
// ---- Recortador de foto cuadrado ----
// abre un modal con la imagen; el usuario arrastra + hace zoom; al confirmar,
// se dibuja el cuadro visible en un canvas 400x400 -> data-URL liviano.
const CROP_OUT=400, STAGE=280;
let cropState={img:null,scale:1,minScale:1,x:0,y:0,dragging:false,startX:0,startY:0,ox:0,oy:0};
function clampCrop(){
  const iw=cropState.img.naturalWidth*cropState.scale;
  const ih=cropState.img.naturalHeight*cropState.scale;
  // la imagen debe cubrir todo el stage: los bordes no pueden entrar
  cropState.x=Math.min(0,Math.max(STAGE-iw,cropState.x));
  cropState.y=Math.min(0,Math.max(STAGE-ih,cropState.y));
}
function paintCrop(){
  clampCrop();
  const im=$('cropImg');
  im.style.width=(cropState.img.naturalWidth*cropState.scale)+'px';
  im.style.height=(cropState.img.naturalHeight*cropState.scale)+'px';
  im.style.transform='translate('+cropState.x+'px,'+cropState.y+'px)';
}
function openCropper(dataUrl){
  const img=new Image();
  img.onload=()=>{
    // escala mínima para que la imagen cubra el cuadro
    const minScale=Math.max(STAGE/img.naturalWidth,STAGE/img.naturalHeight);
    cropState={img,scale:minScale,minScale,x:0,y:0,dragging:false,startX:0,startY:0,ox:0,oy:0};
    // centrar
    cropState.x=(STAGE-img.naturalWidth*minScale)/2;
    cropState.y=(STAGE-img.naturalHeight*minScale)/2;
    $('cropImg').src=dataUrl;
    $('cropZoom').min=minScale; $('cropZoom').max=minScale*3; $('cropZoom').step=(minScale*2)/100; $('cropZoom').value=minScale;
    paintCrop();
    $('cropOverlay').classList.add('show');
  };
  img.src=dataUrl;
}
function closeCropper(){ $('cropOverlay').classList.remove('show'); }
function commitCrop(){
  const c=document.createElement('canvas');
  c.width=CROP_OUT; c.height=CROP_OUT;
  const ctx=c.getContext('2d');
  const ratio=CROP_OUT/STAGE;
  // dibujar la imagen tal como se ve en el stage, escalado a 400
  const dw=cropState.img.naturalWidth*cropState.scale*ratio;
  const dh=cropState.img.naturalHeight*cropState.scale*ratio;
  ctx.drawImage(cropState.img, cropState.x*ratio, cropState.y*ratio, dw, dh);
  const dataUrl=c.toDataURL('image/jpeg',0.85);
  // v27 — mismo recortador para el logo del negocio (antes solo el logo se guardaba tal cual,
  // sin pasar por el cuadro/zoom, y podía verse deformado igual que la foto de producto antes de v12).
  // v1.13 — v3er destino: el logo del Catálogo Digital (separado del logo del negocio — uno es
  // solo local, el otro se sube al servidor y se ve en la vitrina pública).
  if(cropTarget==='logo'){
    applyLogo(dataUrl); // ya actualiza todos los .brand-mark, incluida la vista previa de Configuración
  } else if(cropTarget==='catalogoLogo'){
    catLogoDataUrl=dataUrl;
    $('catLogoPreviewWrap').innerHTML='<img src="'+dataUrl+'" style="width:100%;height:100%;object-fit:cover;border-radius:10px">';
    $('catLogoText').textContent='Cambiar imagen';
  } else if(cropTarget==='perfilFoto'){
    // v1.14 — se ve al instante y se sube en segundo plano. A diferencia del logo del negocio
    // (que es solo local), esta foto vive en el servidor: es la que identifica a la persona.
    $('perfilFotoWrap').innerHTML='<img src="'+dataUrl+'" style="width:100%;height:100%;object-fit:cover;border-radius:50%">';
    $('perfilFotoText').textContent='Subiendo…';
    subirFotoPerfil(dataUrl);
  } else {
    prodPhotoData=dataUrl;
    $('prodPhotoWrap').innerHTML='<img src="'+dataUrl+'" style="width:100%;height:100%;object-fit:cover;border-radius:10px">';
    $('prodPhotoText').textContent='Cambiar imagen';
  }
  closeCropper();
}
$('cropZoom').addEventListener('input',e=>{
  // hacer zoom manteniendo el centro del stage
  const newScale=parseFloat(e.target.value);
  const cx=STAGE/2, cy=STAGE/2;
  const relX=(cx-cropState.x)/cropState.scale, relY=(cy-cropState.y)/cropState.scale;
  cropState.scale=newScale;
  cropState.x=cx-relX*newScale; cropState.y=cy-relY*newScale;
  paintCrop();
});
(function(){
  const stage=$('cropStage');
  function down(x,y){ cropState.dragging=true; cropState.startX=x; cropState.startY=y; cropState.ox=cropState.x; cropState.oy=cropState.y; }
  function move(x,y){ if(!cropState.dragging)return; cropState.x=cropState.ox+(x-cropState.startX); cropState.y=cropState.oy+(y-cropState.startY); paintCrop(); }
  function up(){ cropState.dragging=false; }
  stage.addEventListener('mousedown',e=>down(e.clientX,e.clientY));
  window.addEventListener('mousemove',e=>move(e.clientX,e.clientY));
  window.addEventListener('mouseup',up);
  stage.addEventListener('touchstart',e=>{const t=e.touches[0];down(t.clientX,t.clientY)},{passive:true});
  stage.addEventListener('touchmove',e=>{const t=e.touches[0];move(t.clientX,t.clientY);e.preventDefault()},{passive:false});
  stage.addEventListener('touchend',up);
})();
$('cropCancel').addEventListener('click',closeCropper);
$('cropBackdrop').addEventListener('click',closeCropper);
$('cropOk').addEventListener('click',commitCrop);

$('prodPhoto').addEventListener('change',e=>{
  const f=e.target.files&&e.target.files[0];if(!f)return;
  const rd=new FileReader();
  rd.onload=()=>{ cropTarget='product'; openCropper(rd.result); };
  rd.readAsDataURL(f);
  e.target.value=''; // permitir re-elegir la misma imagen
});
// agregar categoría nueva desde el formulario
$('prodCatAdd').addEventListener('click',()=>{
  const name=prompt('Nombre de la nueva categoría:');
  if(!name||!name.trim())return;
  const label=name.trim();
  const id='cat_'+Date.now().toString(36);
  categories.push({id,label});
  fillCatSelect(id);
  emitCambio('categoria',{id,label});
});
function openProductForm(){
  const p=editingProductId?products.find(x=>x.id===editingProductId):null;
  const meta=(p&&p.calcMeta)?p.calcMeta:{buyMode:'unidad',buyCur:'usd',unitCost:p?p.costBcv:0,bulkCost:0,bulkUnits:0,ivaSubject:p?!!p.ivaSubject:false,costIvaIncl:false,marginMode:'costo',marginPct:p?p.margin:0,gastosPct:p?p.gastosPct:null};
  $('productFormTitle').textContent=p?'Editar producto':'Nuevo producto';
  prodDesdeMaestro=false;
  $('prodNameResultados').style.display='none';
  $('prodName').value=p?p.name:'';
  $('prodBrand').value=p?(p.brand||''):'';
  $('prodSku').value=p?(p.sku||''):'';
  $('prodOfferPrice').value=(p&&p.offerPrice>0)?p.offerPrice:'';
  // v1.13 — visible por defecto (true) en productos nuevos y en los que ya existían antes de
  // este campo: encender el interruptor general del Catálogo Digital debe mostrar todo lo que
  // ya tenías, no dejarlo vacío hasta que entres producto por producto a decir que sí.
  document.querySelectorAll('#prodEnCatalogo button').forEach(x=>x.classList.toggle('on',x.dataset.v===((p&&p.enCatalogoPublico===false)?'no':'si')));
  fillCatSelect(p?p.cat:(categories[0]&&categories[0].id));
  $('prodStock').value=p?(p.stock||0):0;
  document.querySelectorAll('#prodVentaPeso button').forEach(x=>x.classList.toggle('on',x.dataset.v===((p&&p.ventaPeso)?'si':'no')));
  applyVentaPesoToProductForm();
  const unitType=p?(p.unitType||'unidad'):'unidad';
  document.querySelectorAll('#prodUnitType button').forEach(x=>x.classList.toggle('on',x.dataset.v===unitType));
  $('prodUnitValueRow').style.display=unitType==='unidad'?'none':'';
  $('prodUnitValueLabel').textContent=unitType==='peso'?'Peso del envase':'Volumen del envase';
  $('prodUnitValue').placeholder=unitType==='peso'?'Ej: 750 g, 1 kg, 1.5 Kg':'Ej: 500 ml, 1 L, 1.5 L';
  $('prodUnitValue').value=(p&&p.unitValueBase)?formatUnitLabel(p):'';
  updateUnitHint();
  prodPhotoData=(p&&p.photo)?p.photo:null;
  if(prodPhotoData){ $('prodPhotoWrap').innerHTML='<img src="'+prodPhotoData+'" style="width:100%;height:100%;object-fit:cover;border-radius:10px">'; $('prodPhotoText').textContent='Cambiar imagen'; }
  else{ $('prodPhotoWrap').innerHTML=''; $('prodPhotoText').textContent='Subir imagen'; }
  document.querySelectorAll('#prodBuyMode button').forEach(x=>x.classList.toggle('on',x.dataset.v===meta.buyMode));
  $('prodUnitCostRow').style.display=meta.buyMode==='unidad'?'':'none';
  $('prodBulkRows').style.display=meta.buyMode==='bulto'?'':'none';
  document.querySelectorAll('#prodBuyCur button').forEach(x=>x.classList.toggle('on',x.dataset.v===(meta.buyCur||'usd')));
  $('prodUnitCost').value=meta.unitCost||'';
  $('prodBulkCost').value=meta.bulkCost||'';
  $('prodBulkUnits').value=meta.bulkUnits||'';
  document.querySelectorAll('#prodIvaSubject button').forEach(x=>x.classList.toggle('on',x.dataset.v===(meta.ivaSubject?'si':'no')));
  document.querySelectorAll('#prodCostIvaIncl button').forEach(x=>x.classList.toggle('on',x.dataset.v===(meta.costIvaIncl?'si':'no')));
  $('prodCostIvaWrap').style.display=meta.ivaSubject?'':'none';
  $('prodSimpleIvaInclWrap').style.display=meta.ivaSubject?'':'none';
  updateCostIvaNota();
  document.querySelectorAll('#prodMarginMode button').forEach(x=>x.classList.toggle('on',x.dataset.v===meta.marginMode));
  $('prodMarginPct').value=meta.marginPct||0;
  $('prodGastosPct').value=(meta.gastosPct==null?(cfg.gastosPctDefault||0):meta.gastosPct);
  // v49 — el modo (Simple/Avanzado) ya no viene del producto (p.pricingMode) — sea nuevo o ya
  // existente, se arma con el modo global de Configuración → Precios y tasas.
  $('prodSimplePrecio').value=(p&&p.simplePrecio>0)?p.simplePrecio:'';
  document.querySelectorAll('#prodSimpleMoneda button').forEach(x=>x.classList.toggle('on',x.dataset.v===((p&&p.simpleMoneda)||'paralelo')));
  document.querySelectorAll('#prodSimpleIvaIncl button').forEach(x=>x.classList.toggle('on',x.dataset.v===((p&&p.simpleIvaIncluido===false)?'no':'si')));
  applyPriceModelToProductForm();
  applyBusinessModeToProductForm();
  $('productForm').style.display='';
  updateProdPreview();
  $('prodName').focus();
}
$('newProductBtn').addEventListener('click',()=>{editingProductId=null;openProductForm()});
$('prodCancel').addEventListener('click',()=>{$('productForm').style.display='none';editingProductId=null});
$('prodSave').addEventListener('click',()=>{
  const name=$('prodName').value.trim();
  if(!name){$('prodName').focus();return}
  const pricingMode=currentPricingMode();
  const simplePrecio=parseFloat($('prodSimplePrecio').value)||0;
  if(pricingMode==='simple'&&simplePrecio<=0){$('prodSimplePrecio').focus();return}
  const cat=$('prodCat').value;
  const stock=parseFloat($('prodStock').value)||0;
  const buyMode=document.querySelector('#prodBuyMode button.on').dataset.v;
  const buyCur=document.querySelector('#prodBuyCur button.on').dataset.v;
  const marginMode=document.querySelector('#prodMarginMode button.on').dataset.v;
  // v49 — en modo Simple no hay interruptor de IVA visible (decisión de Jonathan: todo producto
  // Simple queda exento, sin excepción) — se fuerza a false sin importar qué diga el botón oculto.
  const ivaSubject=pricingMode==='simple'?false:document.querySelector('#prodIvaSubject button.on').dataset.v==='si';
  const gastosRaw=parseFloat($('prodGastosPct').value);
  const gastosPct=isMedio()?0:(isNaN(gastosRaw)?(cfg.gastosPctDefault||0):gastosRaw);
  const calcMeta={
    buyMode,buyCur,
    unitCost:parseFloat($('prodUnitCost').value)||0,
    bulkCost:parseFloat($('prodBulkCost').value)||0,
    bulkUnits:parseFloat($('prodBulkUnits').value)||0,
    ivaSubject,
    costIvaIncl:document.querySelector('#prodCostIvaIncl button.on')?.dataset.v==='si',
    marginMode,
    marginPct:parseFloat($('prodMarginPct').value)||0,
    gastosPct,
  };
  const costBcv=calcCostBcv(),margin=calcMarginPct();
  const brand=$('prodBrand').value.trim();
  const sku=$('prodSku').value.trim();
  const offerPrice=parseFloat($('prodOfferPrice').value)||0;
  const ventaPeso=document.querySelector('#prodVentaPeso button.on')?.dataset.v==='si';
  // ventaPeso y unitType son mutuamente excluyentes (ver applyVentaPesoToProductForm): un producto
  // que se pesa en el mostrador no tiene un "contenido de envase" fijo que etiquetar, así que se
  // guarda limpio en 'unidad'/null aunque el usuario lo haya dejado configurado de antes.
  const unitType=ventaPeso?'unidad':document.querySelector('#prodUnitType button.on').dataset.v;
  const unitValueBase=unitType==='unidad'?null:parseUnitValue($('prodUnitValue').value,unitType);
  const enCatalogoPublico=document.querySelector('#prodEnCatalogo button.on')?.dataset.v!=='no';
  const simpleMoneda=document.querySelector('#prodSimpleMoneda button.on')?.dataset.v||'paralelo';
  const simpleIvaIncluido=document.querySelector('#prodSimpleIvaIncl button.on')?.dataset.v!=='no';
  const extraFields={brand,sku,offerPrice,unitType,unitValueBase,ivaSubject,gastosPct,enCatalogoPublico,
    pricingMode,simplePrecio,simpleMoneda,simpleIvaIncluido,ventaPeso};
  const esNuevo=!editingProductId;
  let prodGuardado=null;
  if(editingProductId){
    const p=products.find(x=>x.id===editingProductId);
    if(p){
      // v1.7 — si la foto cambió, se invalida la URL ya subida al respaldo (más abajo): el
      // próximo respaldo sube la nueva en vez de quedarse pegado con la que ya tenía en caché.
      if(p.photo!==prodPhotoData){ delete p.fotoRespaldoUrl; delete p.fotoRespaldoHash; }
      Object.assign(p,{name,cat,costBcv,margin,stock,calcMeta,photo:prodPhotoData},extraFields);
      prodGuardado=p;
    }
  } else {
    // v1.8 — id único global (uuidLite), no secuencial por dispositivo: con multi-caja, dos
    // teléfonos generando 'p13' cada uno por su lado serían productos DISTINTOS con el MISMO
    // id, y el servidor sumaría su stock como si fueran el mismo producto. productSeq se deja
    // vivo (sigue en el respaldo) por compatibilidad con productos viejos, ya creados así.
    prodGuardado=Object.assign({id:'p_'+uuidLite(),name,cat,costBcv,margin,stock,calcMeta,photo:prodPhotoData},extraFields);
    products.push(prodGuardado);
  }
  // catálogo maestro (v1.7 del backend) — si es un producto nuevo y no vino de un resultado
  // del buscador, se manda como sugerencia en silencio; si vino del buscador, ya existe ahí.
  if(esNuevo&&!prodDesdeMaestro){
    // v1.11 — se manda la foto que el negocio le puso (si tiene), para que quien revisa la vea
    // y decida si la aprueba tal cual o la cambia por una mejor — antes la sugerencia llegaba
    // "ciega", sin ninguna foto que mirar.
    const fotoParaSugerir=(prodPhotoData&&prodPhotoData.indexOf('data:')===0)?prodPhotoData:null;
    sugerirCatalogoMaestro({nombre:name,marca:brand,sku,categoria:catLabelOf(cat),unidad_tipo:unitType,unidad_valor_base:unitValueBase,foto_base64:fotoParaSugerir});
  }
  $('productForm').style.display='none';
  editingProductId=null;
  renderCatalogo();renderGrid();renderChips();renderStock();
  if(prodGuardado) emitCambio('producto',prodGuardado);
});
function catalogProductCardHTML(p){
  const pr=effectivePrices(p);
  const bg=catColor[p.cat]||'#3E7BFA';
  const neg=p.stock<0;
  const av=p.photo
    ? '<div class="avatar-round" style="padding:0;overflow:hidden"><img src="'+p.photo+'" style="width:100%;height:100%;object-fit:cover"></div>'
    : '<div class="avatar-round" style="background:'+bg+'18;color:'+bg+'">'+p.name.trim().charAt(0).toUpperCase()+'</div>';
  const unitLbl=formatUnitLabel(p);
  const subBits=[(catLabel[p.cat]||p.cat)];
  if(p.brand)subBits.push(p.brand);
  if(unitLbl)subBits.push(unitLbl);
  if(p.sku)subBits.push('SKU '+p.sku);
  if(p.ivaSubject)subBits.push('IVA');
  // v1.21 — en modo Simple no hay costBcv (nunca se pidió) — mostrar "costo $0.00" sería
  // mentir que no gana nada, cuando en realidad es que no se sabe. Se avisa distinto.
  // v50 — el costo y el precio de Inventario mostraban oferta (informal); ahora BCV, igual que
  // el resto de la app.
  subBits.push(p.pricingMode==='simple'?'sin costo (Simple)':'costo '+fmtUSD(toBcvUsd(p.costBcv)));
  const porKgSuf=p.ventaPeso?' <span style="font-size:.7rem;font-weight:600;color:var(--text-faint)">/Kg</span>':'';
  const priceHTML=pr.onOffer
    ? '<b style="color:var(--red)">'+fmtUSD(toBcvUsd(pr.contado))+'</b>'+porKgSuf+'<div style="text-decoration:line-through;color:var(--text-faint);font-size:.7rem">'+fmtUSD(toBcvUsd(pr.original.contado))+'</div>'
    : '<b>'+fmtUSD(toBcvUsd(pr.contado))+'</b>'+porKgSuf;
  // v36 — los dos botones y el precio no se encogen, así que en un teléfono angosto le comían
  // todo el ancho al nombre: el catálogo se veía "Ha…", "Ar…", ilegible (reportado por Jonathan
  // con captura). Ahora las acciones van en su propio contenedor y, en pantalla chica, bajan a
  // una segunda fila (ver .cc-actions en el CSS) — el nombre se queda con el ancho completo.
  const stockTxt=p.ventaPeso?formatPeso(p.stock):p.stock;
  return '<div class="client-card" data-id="'+p.id+'"><div class="cc-row cc-row-prod">'
    +av
    +'<div class="cc-mid"><div class="cc-name">'+p.name+'</div><div class="cc-sub">'+subBits.join(' · ')+' · stock <span style="'+(neg?'color:var(--red);font-weight:800':'')+'">'+stockTxt+'</span></div></div>'
    +'<div class="cc-saldo">'+priceHTML+'</div>'
    +'<div class="cc-actions">'
      +'<button class="btn-secondary prod-edit" data-id="'+p.id+'">Editar</button>'
      +'<button class="btn-secondary prod-del" data-id="'+p.id+'" style="color:var(--red)">Borrar</button>'
    +'</div>'
    +'</div></div>';
}
function renderCatalogo(){
  const q=norm($('catalogoSearch').value);
  const items=products.filter(p=>!q||norm(p.name).includes(q));
  $('catalogoList').innerHTML=items.length?items.map(catalogProductCardHTML).join(''):'<div class="cart-empty"><b>Sin productos</b>Prueba con otro nombre.</div>';
  // v1.21 — recordatorio de cuántos productos siguen en modo Simple (sin costo registrado),
  // visible apenas entras a Inventario — para que no tengas que ir hasta Contabilidad solo
  // para acordarte de cuáles te faltan por personalizar cuando te llegue la factura.
  const av=$('catalogoSimpleAviso'); if(!av)return;
  const nSimple=products.filter(p=>p.pricingMode==='simple').length;
  av.innerHTML=nSimple
    ? '<div class="desc" style="margin:-4px 0 10px;color:var(--amber)">'+nSimple+' producto'+(nSimple===1?'':'s')+' en modelo Simple, sin costo registrado — no entran en tu ganancia real hasta que los pases a Personalizado.</div>'
    : '';
}
$('catalogoSearch').addEventListener('input',renderCatalogo);
$('catalogoList').addEventListener('click',e=>{
  const editBtn=e.target.closest('.prod-edit');
  if(editBtn){ editingProductId=editBtn.dataset.id;openProductForm();return }
  const delBtn=e.target.closest('.prod-del');
  if(delBtn){
    const id=delBtn.dataset.id;
    const p=products.find(x=>x.id===id);
    if(!p)return;
    askConfirm('¿Borrar "'+p.name+'" del catálogo? Las ventas ya hechas con este producto no se tocan — solo deja de estar disponible para vender. No se puede deshacer.',()=>{
      const idx=products.findIndex(x=>x.id===id);
      if(idx>=0)products.splice(idx,1);
      if(cart[id])delete cart[id]; // si estaba en el carrito activo, se saca también
      schedulePersist(true);
      renderCatalogo();renderCart();renderGrid();
      emitCambio('producto_borrado',{id});
    });
  }
});

// ===== Stock =====
// negativos primero (más urgente arriba), luego el resto de menor a mayor cantidad
function stockRowHTML(p){
  const neg=p.stock<0;
  const open=expandedStockId===p.id;
  const bg=catColor[p.cat]||'#3E7BFA';
  let detail='';
  // stock por peso (v45): saltar de a 1 con el stepper ±1 no tiene sentido en Kg — solo queda el
  // input "Ajustar a…" (ya era inputmode="decimal", soporta Kg fraccionarios sin cambios).
  const stepper='<div class="stepper"><button class="stock-step" data-act="dec" data-id="'+p.id+'">−</button><div class="q" style="min-width:34px">'+p.stock+'</div><button class="stock-step" data-act="inc" data-id="'+p.id+'">+</button></div>';
  if(open){
    detail='<div class="detail-block"><div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">'
      +(p.ventaPeso?'':stepper)
      +'<input type="number" inputmode="decimal" class="cfg-input stock-set-input" data-id="'+p.id+'" placeholder="'+(p.ventaPeso?'Ajustar a… (Kg)':'Ajustar a…')+'" style="width:110px">'
      +'<button class="btn-secondary stock-set-btn" data-id="'+p.id+'">Ajustar</button>'
      +'</div></div>';
  }
  return '<div class="client-card" data-id="'+p.id+'">'
    +'<button class="cc-row-btn" data-toggle="'+p.id+'"><div class="cc-row">'
    +'<div class="avatar-round" style="background:'+bg+'18;color:'+bg+'">'+p.name.trim().charAt(0).toUpperCase()+'</div>'
    +'<div class="cc-mid"><div class="cc-name">'+p.name+'</div><div class="cc-sub">'+(catLabel[p.cat]||p.cat)+'</div></div>'
    +'<div class="cc-saldo'+(neg?'':' ok')+'">'+(neg?'Negativo':'Disponible')+'<br><b style="'+(neg?'color:var(--red)':'')+'">'+(p.ventaPeso?formatPeso(p.stock):p.stock+' und')+'</b></div>'
    +'<svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" style="transform:rotate('+(open?'90deg':'0deg')+')"><path d="m9 6 6 6-6 6"/></svg>'
    +'</div></button>'+detail+'</div>';
}
// inventario bajo (v16, umbral configurable desde v27): botón rojo aparte del de stock negativo —
// negativo es "ya vendiste de más", bajo es "todavía alcanza, pero pronto no"
// (0 < stock <= cfg.lowStockThreshold, editable en Stock — antes era un número fijo en el código).
function lowStockThreshold(){ const v=parseFloat(cfg.lowStockThreshold); return (!isNaN(v)&&v>=1)?v:LOW_STOCK_DEFAULT; }
function lowStockProducts(){ return products.filter(p=>p.stock>=0&&p.stock<=lowStockThreshold()); }
let stockLowFilter=false;
// v5 — el listado de Stock siempre venía ordenado por "menos inventario primero". Sirve para
// reponer, pero no para buscar un producto concreto ni para ver qué te sobra. Ahora se elige.
let stockSort='menos';
const STOCK_SORTS=[
  {id:'menos',label:'Menos inventario'},
  {id:'mas',label:'Más inventario'},
  {id:'nombre',label:'Nombre (A-Z)'}
];
function ordenarStock(items){
  const arr=items.slice();
  if(stockSort==='nombre')return arr.sort((a,b)=>norm(a.name).localeCompare(norm(b.name)));
  if(stockSort==='mas')return arr.sort((a,b)=>b.stock-a.stock);
  // 'menos': los negativos primero (es lo más urgente), después de menor a mayor
  return arr.sort((a,b)=>{
    if(a.stock<0&&b.stock<0)return a.stock-b.stock;
    if(a.stock<0)return -1;
    if(b.stock<0)return 1;
    return a.stock-b.stock;
  });
}
function renderStock(){
  const q=norm($('stockSearch').value);
  let items=products.filter(p=>!q||norm(p.name).includes(q));
  if(stockLowFilter)items=items.filter(p=>p.stock>=0&&p.stock<=lowStockThreshold());
  items=ordenarStock(items);
  const negCount=products.filter(p=>p.stock<0).length;
  const lowCount=lowStockProducts().length;
  let summary=negCount>0
    ?'<b style="color:var(--red)">'+negCount+' producto'+(negCount===1?'':'s')+' con stock negativo</b><div class="pc-sub">Vendiste sin tener suficiente cargado. Ajusta abajo.</div>'
    :'<b style="color:var(--green)">Todo en orden</b><div class="pc-sub">Ningún producto con stock negativo.</div>';
  if(lowCount>0)summary+='<button class="lowstock-btn" id="lowStockBtn">'+lowCount+' con inventario bajo'+(stockLowFilter?' — ver todos':' — ver solo estos')+'</button>';
  // v27 — el umbral de "inventario bajo" ya no es fijo en el código: cada bodega vende a otra
  // escala (bulto de 24 vs. unidad suelta), así que se edita directo aquí, donde se usa.
  summary+='<div class="chips" style="margin-top:10px">'+STOCK_SORTS.map(o=>'<button class="chip'+(stockSort===o.id?' on':'')+'" data-stock-sort="'+o.id+'">'+o.label+'</button>').join('')+'</div>';
  summary+='<div class="pc-sub" style="margin-top:8px">Inventario bajo cuando quedan <input type="number" inputmode="numeric" min="1" step="1" class="cfg-input" id="lowStockThresholdInput" value="'+lowStockThreshold()+'" style="width:56px;display:inline-block;padding:4px 6px;text-align:center"> unidades o menos</div>';
  $('stockSummary').innerHTML=summary;
  $('stockList').innerHTML=items.length?items.map(stockRowHTML).join(''):'<div class="cart-empty"><b>Sin productos</b>Prueba con otro nombre.</div>';
}
$('stockSearch').addEventListener('input',renderStock);
$('stockSummary').addEventListener('click',e=>{
  if(e.target.closest('#lowStockBtn')){stockLowFilter=!stockLowFilter;renderStock();return;}
  const so=e.target.closest('[data-stock-sort]');
  if(so){ stockSort=so.dataset.stockSort; renderStock(); }
});
$('stockSummary').addEventListener('change',e=>{
  if(e.target.id==='lowStockThresholdInput'){
    const v=parseInt(e.target.value,10);
    cfg.lowStockThreshold=(!isNaN(v)&&v>=1)?v:LOW_STOCK_DEFAULT;
    renderStock();renderNav(); // el badge del menú también depende del umbral
  }
});
$('stockList').addEventListener('click',e=>{
  const step=e.target.closest('.stock-step');
  if(step){
    const p=products.find(x=>x.id===step.dataset.id);
    if(p){
      const delta=step.dataset.act==='inc'?1:-1;
      p.stock+=delta; registrarMovimientoStock(p.id,delta);
      renderStock();if(currentSection==='catalogo')renderCatalogo();
    }
    return;
  }
  const setBtn=e.target.closest('.stock-set-btn');
  if(setBtn){
    const input=$('stockList').querySelector('.stock-set-input[data-id="'+setBtn.dataset.id+'"]');
    const v=parseFloat(input.value);
    if(!isNaN(v)){
      const p=products.find(x=>x.id===setBtn.dataset.id);
      if(p){
        const delta=v-p.stock; // corrección manual (conteo físico) — se reporta como delta igual que todo lo demás
        p.stock=v; registrarMovimientoStock(p.id,delta);
        renderStock();if(currentSection==='catalogo')renderCatalogo();
      }
    }
    return;
  }
  const toggle=e.target.closest('.cc-row-btn');if(!toggle)return;
  expandedStockId=expandedStockId===toggle.dataset.toggle?null:toggle.dataset.toggle;
  renderStock();
});

// ===== Cajón (turno de caja) =====
// helper: la moneda "física" de un método de pago (para el arqueo de efectivo)
function methodCashCurrency(mid){
  // solo el efectivo cuenta como dinero físico en caja; lo digital (USD electrónicos, Bolívares
  // electrónico, etc.) no se arquea en gaveta. v51 — "efectivo USD" ahora son 2 métodos (BCV y
  // oferta, ver PAY_METHODS_OFERTA) pero siguen siendo la MISMA gaveta física: da igual a qué
  // precio se cobró, lo que entra a la caja son billetes de dólar.
  if(mid==='efectivo_usd_bcv'||mid==='efectivo_usd_oferta')return 'usd';
  if(mid==='efectivo_bs')return 'bs';
  if(mid.indexOf('efectivo_')===0){
    const id=mid.slice(9);
    if(monedaActivaById(id))return id;   // efectivo en moneda propia: sí es una gaveta más
  }
  return null; // digital
}
// ventas pagadas del turno abierto
function turnoSales(){
  if(!openTurno)return [];
  return orders.filter(o=>o.turnoId===openTurno.id&&o.status==='pagado');
}
// v31 — abonos a fiados registrados durante el turno (entran a caja como plata del turno), UNO
// POR TRANSACCIÓN (no por pedido que tocó — ver abonoPagos/applyAbonoToClient), con su desglose
// de pago intacto para poder repartirlo dentro de "Ventas del turno por método" en turnoDesglose().
function turnoAbonos(){
  if(!openTurno)return [];
  return abonoPagos.filter(t=>t.turnoId===openTurno.id);
}
// ===== REPORTE Z (v20) =====
// El "Reporte Z" es el resumen fiscal que la máquina fiscal saca al cerrar el día y que el
// comerciante venezolano le entrega a su contador para armar el Libro de Ventas. El Cajón de FrixPOS
// ya hacía el arqueo de efectivo; esto le agrega lo que el contador realmente necesita: cuánto se
// vendió gravado, cuánto exento y cuánto IVA se cobró en el turno.
function turnoReporteZ(){
  const ventas=turnoSales();
  let gravadoBs=0,exentoBs=0,ivaBs=0,devueltoBs=0;
  ventas.forEach(o=>{
    o.items.forEach(it=>{
      const lineaBs=it.priceBs*it.qty;
      const ivaLinea=(it.ivaDebitoBs||0)*it.qty;
      if(ivaLinea>0){ gravadoBs+=(lineaBs-ivaLinea); ivaBs+=ivaLinea; }
      else exentoBs+=lineaBs;
    });
    (o.devoluciones||[]).forEach(dv=>{
      if(openTurno&&dv.date>=openTurno.inicio){
        devueltoBs+=dv.montoBs;
        ivaBs-=(dv.ivaDebitoBs||0);
        gravadoBs-=(dv.montoBs-(dv.ivaDebitoBs||0));
      }
    });
  });
  return {ventas,gravadoBs,exentoBs,ivaBs,devueltoBs,totalBs:gravadoBs+exentoBs+ivaBs};
}
// egresos pagados del cajón durante el turno abierto (v21)
function turnoEgresos(){
  if(!openTurno)return [];
  return egresos.filter(e=>e.origen==='cajon'&&e.turnoId===openTurno.id);
}
// desglose del turno: total por método (Bs + monto NATIVO en su propia moneda) + efectivo
// esperado por moneda. v31 — antes solo se guardaba el Bs de cada método, y "Ventas del turno
// por método" reconvertía ESE Bs a oferta/BCV/monedas para mostrarlo (otherCurrenciesLine) sin
// importar en qué moneda se cobró de verdad — ahí nacía el "Zelle: $25 oferta · $31 BCV" que no
// tenía sentido (Zelle solo se cobró en UNA de las dos). Ahora se guarda también el monto nativo
// tal cual se tipeó en el pago (p.amount, sin reconvertir — evita drift si la tasa se mueve entre
// la venta y el momento de ver el Cajón), y cada método se muestra en su única moneda real.
function turnoDesglose(){
  const porMetodo={}; // mid -> {label, bs, nativo, cur}
  const efectivo=nuevoEfectivo(); // efectivo físico ESPERADO por moneda (en su propia unidad)
  let totalBs=0;
  const acumular=(mid,label,bsPago,montoNativo)=>{
    if(!porMetodo[mid])porMetodo[mid]={label,bs:0,nativo:0,cur:payCurrency[mid]||'bs'};
    porMetodo[mid].bs+=bsPago;
    porMetodo[mid].nativo+=montoNativo;
  };
  // reparte un monto en Bs (venta = signo +1, devolución = signo -1) entre los pagos de una
  // orden, proporcional al peso de cada pago — mismo criterio para las dos direcciones, así
  // "Vendido" y "esperado en cierre" siempre cuadran igual de bien entren o salgan.
  // v26_1 — el Bs de cada pago tiene que sumar EXACTO el o.totalBs ya congelado (el mismo que
  // usa el Reporte del día / zDeVentas), no el resultado de reconvertir cada monto a la tasa
  // ACTUAL — el redondeo sobrante cae en el último pago.
  function repartirPagos(o,montoBs,signo){
    const pagos=o.paymentsBreakdown||[];
    if(!pagos.length||!(o.totalBs>0))return;
    const pesos=pagos.map(p=>convertToBs(p.amount,p.currency));
    const pesoTotal=pesos.reduce((s,x)=>s+x,0)||1;
    const frac=Math.min(1,montoBs/o.totalBs);
    let repartidoAbsBs=0;
    pagos.forEach((p,i)=>{
      const esUltimo=i===pagos.length-1;
      const parteAbsBs=esUltimo? (montoBs-repartidoAbsBs) : Math.round(montoBs*(pesos[i]/pesoTotal));
      repartidoAbsBs+=parteAbsBs;
      const bsPago=signo*parteAbsBs;
      const montoNativo=signo*(parseFloat(p.amount)||0)*frac;
      const m=payMethods.find(x=>x.id===p.method);
      acumular(p.method,m?m.label:p.method,bsPago,montoNativo);
      const cashCur=methodCashCurrency(p.method);
      if(cashCur)efectivo[cashCur]+=montoNativo; // entra o sale efectivo en su propia moneda, sin tocar
    });
    totalBs+=signo*repartidoAbsBs;
  }
  turnoSales().forEach(o=>{
    repartirPagos(o,o.totalBs,1);
    // el vuelto se entregó en efectivo Bs -> SALE de la gaveta de bolívares
    if(o.changeBs)efectivo.bs-=o.changeBs;
  });
  // v1.18 — devoluciones que se pagaron de vuelta en efectivo/cuenta (no las que bajaron una
  // deuda, esas nunca movieron plata de la caja) DURANTE este turno — sin importar en qué
  // turno se hizo la venta original: si alguien devuelve hoy algo comprado hace 3 días, esa
  // plata sale de la gaveta HOY. Antes esto no se procesaba nunca: un pago mixto (Bs + otra
  // moneda) dejaba la parte de la otra moneda "pegada" en lo esperado aunque el producto ya
  // se hubiera devuelto.
  orders.forEach(o=>{
    (o.devoluciones||[]).forEach(dv=>{
      if(dv.aplicadoA!=='efectivo')return;
      if(!(openTurno && dv.date>=openTurno.inicio))return;
      repartirPagos(o,dv.montoBs,-1);
    });
  });
  // v31 — abonos a fiados: se reparten DENTRO de su método real, no aparte. El Cajón es "cuánto
  // hay de cada cosa en la tienda" — una plata que entró por Zelle es plata de Zelle, sin importar
  // si vino de una venta nueva o de cobrar una deuda vieja. No suman a totalBs: un abono no es
  // una venta (ya se contó como venta el día que se fió), es solo un movimiento de caja.
  turnoAbonos().forEach(t=>{
    (t.breakdown||[]).forEach(p=>{
      const bsPago=Math.round(convertToBs(p.amount,p.currency));
      const m=payMethods.find(x=>x.id===p.method);
      const label=m?m.label:p.method;
      acumular(p.method,label,bsPago,parseFloat(p.amount)||0);
      const cashCur=methodCashCurrency(p.method);
      if(cashCur)efectivo[cashCur]+=parseFloat(p.amount)||0;
    });
  });
  // fondo inicial se suma al efectivo esperado
  if(openTurno){ Object.keys(efectivo).forEach(k=>{ efectivo[k]+=(openTurno.fondo&&openTurno.fondo[k])||0; }); }
  // v21 — egresos pagados CON LA PLATA DE LA CAJA durante este turno: salen del efectivo esperado.
  // Antes el arqueo no los veía y siempre marcaba "falta" cuando el dueño le pagaba al proveedor
  // con lo que había en la gaveta.
  const egresosTurno=turnoEgresos();
  egresosTurno.forEach(e=>{ if(efectivo[e.currency]!==undefined)efectivo[e.currency]-=e.amount; });
  const egresosBs=egresosTurno.reduce((s,e)=>s+e.amountBs,0);
  return {porMetodo,efectivo,totalBs,egresosTurno,egresosBs};
}
function cajonAbrirHTML(){
  return '<div class="cfg-block">'
    +'<h4>Abrir turno de caja</h4>'
    +'<div class="desc">Cuenta el dinero con el que arrancas (fondo de caja) en cada moneda. Al cerrar, el sistema te dice cuánto deberías tener.</div>'
    +'<div class="cfg-row"><label>Fondo USD ($)</label><input class="cfg-input" id="cjFondoUsd" type="number" inputmode="decimal" value="0"></div>'
    +'<div class="cfg-row"><label>Fondo Bolívares (Bs)</label><input class="cfg-input" id="cjFondoBs" type="number" inputmode="decimal" value="0"></div>'
    +monedasActivas().map(m=>'<div class="cfg-row"><label>Fondo '+m.nombre+'</label><input class="cfg-input" data-fondo="'+m.id+'" type="number" inputmode="decimal" value="0"></div>').join('')
    +'<div class="form-actions" style="margin-top:12px"><button class="btn-primary" id="cjAbrir" style="width:100%">Abrir turno</button></div>'
    +'</div>'
    +cajonHistorialHTML();
}
function reporteZHTML(){
  const z=turnoReporteZ();
  if(!z.ventas.length)return '';
  const linea=(nombre,sub,bs,cls)=>'<div class="order-row"><div><div class="or-top">'+nombre+'</div>'
    +(sub?'<div class="or-sub">'+sub+'</div>':'')+'</div><div'+(cls?' style="color:'+cls+'"':'')+'>'+fmtBs(bs)+'</div></div>';
  return '<div class="cfg-block"><h4>Resumen fiscal del turno</h4>'
    +'<div class="desc">Solo las ventas <b>pagadas</b> de este turno — te sirve para cuadrar la gaveta al cerrar. El resumen fiscal completo del día (con lo fiado incluido) está en Contabilidad → <b>Reporte del día</b>.</div>'
    +'<details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">'
      +'Cuánto vendiste gravado (productos que llevan IVA), cuánto exento (la mayoría de los víveres) y cuánto IVA cobraste, de las ventas pagadas EN ESTE TURNO — por eso puede no coincidir con el Reporte del día si abriste/cerraste caja más de una vez hoy, o si hubo ventas fiadas.<br><br>'
      +'Ojo: esto es el resumen que lleva FrixPOS, <b>no sustituye a una máquina fiscal homologada</b> si el SENIAT te obliga a tener una.'
    +'</div></details>'
    +linea('Ventas gravadas','Productos que llevan IVA, sin contar el IVA',z.gravadoBs)
    +linea('Ventas exentas','Productos sin IVA',z.exentoBs)
    +linea('IVA cobrado','Lo que le estás guardando al SENIAT',z.ivaBs)
    +(z.devueltoBs>0?linea('Devoluciones del turno','Ya descontadas de los totales de arriba',z.devueltoBs,'var(--amber)'):'')
    +'<div class="order-row" style="border-top:1px solid var(--border);margin-top:4px;padding-top:8px"><div><b>Total vendido</b><div class="or-sub">'+z.ventas.length+' venta'+(z.ventas.length===1?'':'s')+' en el turno</div></div><div><b>'+fmtBs(z.totalBs)+'</b></div></div>'
    +'</div>';
}
// v26_3 — reconciliación de cuentas digitales del turno (Zelle, pago móvil, Binance, captahuella):
// mismo espíritu que el arqueo de efectivo, pero para lo que debería haber llegado a cada cuenta,
// no a la gaveta. "Esperado" sale de d.porMetodo (ya corregido en v26_1, así que siempre cuadra
// contra el Reporte Z); "confirmado" lo escribe el dueño mirando el saldo/movimientos reales de
// cada cuenta. Es reconciliación POR TURNO (detecta si un pago dijo que llegó y no llegó) — NO es
// un saldo acumulado de la cuenta (eso necesita persistencia real, que el mockup no tiene todavía;
// ver "Visión de plataforma" en el .md).
// v4 — antes esto era un bloque aparte ("Cuentas digitales del turno") que vivía lejos del
// arqueo de efectivo, así que cerrar un turno obligaba a llenar dos formularios en dos sitios
// distintos. Pedido de Jonathan: un solo listado en "Cerrar turno", con esperado y contado
// pegados, línea por línea, sin importar si la plata entró a la gaveta o a una cuenta.
// Esta función ya no arma su propio bloque — solo devuelve las filas digitales, que
// cajonAbiertoHTML() pega debajo de las de efectivo.
function cuentasDigitalesRowsHTML(d){
  const digitales=Object.keys(d.porMetodo).filter(mid=>!methodCashCurrency(mid));
  if(!digitales.length)return '';
  return digitales.map(mid=>{
    const m=d.porMetodo[mid];
    const cur=m.cur;
    // v31 — m.nativo ya está en la moneda real del método (lo que se tipeó al cobrar), no hay
    // que reconvertir desde Bs — eso es justo lo que evita el drift si la tasa se movió.
    const esperadoNativo=(cur==='usd'||cur==='usd_bcv')?m.nativo:m.bs;
    const fmtFn=(cur==='usd'||cur==='usd_bcv')?fmtUSD:fmtBs;
    return arqueoRowHTML(m.label,esperadoNativo,fmtFn,
      'data-cuenta-digital="'+mid+'" data-cur="'+cur+'"');
  }).join('');
}
// fila única del arqueo: nombre + dónde escribir cuánto hay de verdad — SIN mostrar cuánto
// debería haber (conteo ciego, v31, pedido de Jonathan). Quien cuenta la caja no ve la meta
// antes de contar: si la viera, lo más fácil del mundo es escribir el número que ya está en
// pantalla en vez de contar de verdad, y el arqueo deja de servir para lo único que sirve —
// detectar una diferencia real. La comparación (sobra/falta) se revela recién en updateArqueo(),
// después de que la persona ya escribió lo que contó — esperadoNativo se sigue calculando para
// esa comparación, solo dejó de imprimirse en la etiqueta/placeholder.
function arqueoRowHTML(label,esperadoNativo,fmtFn,attrs){
  return '<div class="cfg-row"><label>'+label+'</label>'
    +'<input class="cfg-input" '+attrs+' type="number" inputmode="decimal" placeholder="Cuánto contaste"></div>';
}
// v31 — "Vendido" en las monedas realmente vendidas (no todo reconvertido a Bs): suma el monto
// NATIVO de cada método agrupado por su moneda real, así un turno que cobró parte en Zelle
// oferta y parte en efectivo Bs se lee "Bs X + $Y oferta", no un solo Bs escondiendo la mezcla.
function ventidoPorMonedaLine(porMetodo){
  const porCur={};
  Object.values(porMetodo).forEach(m=>{
    const cur=m.cur||'bs';
    porCur[cur]=(porCur[cur]||0)+(cur==='bs'?m.bs:m.nativo);
  });
  const parts=[];
  if(porCur.bs)parts.push(fmtBs(porCur.bs));
  if(porCur.usd)parts.push(fmtUSD(porCur.usd)+' oferta');
  if(porCur.usd_bcv)parts.push(fmtUSD(porCur.usd_bcv)+' BCV');
  monedasActivas().forEach(m=>{ if(porCur[m.id])parts.push(fmtMonedaVal(porCur[m.id],m)); });
  return parts.length?parts.join(' + '):fmtBs(0);
}
function cajonAbiertoHTML(){
  const d=turnoDesglose();
  const dur=Math.max(0,Math.round((Date.now()-openTurno.inicio.getTime())/60000));
  const durTxt=dur<60?dur+' min':(Math.floor(dur/60)+'h '+(dur%60)+'m');
  // fiados creados durante el turno (referencia, NO cuentan como plata)
  const fiadosTurno=orders.filter(o=>o.status==='fiado'&&o.date>=openTurno.inicio);
  const fiadoBs=fiadosTurno.reduce((s,o)=>s+debtBs(o),0);

  // v31 — un solo monto por método, en SU moneda real (oferta o BCV, según lo que el negocio
  // configuró en Configuración → Cobros) — ya no la reconversión a las 3 monedas de siempre.
  // Los abonos a fiados pagados por este método ya están sumados adentro (ver turnoDesglose).
  let metodosHtml=Object.keys(d.porMetodo).length
    ? Object.values(d.porMetodo).map(m=>{
        const val = m.cur==='bs' ? fmtBs(m.bs)
          : m.cur==='usd' ? fmtUSD(m.nativo)+' oferta'
          : m.cur==='usd_bcv' ? fmtUSD(m.nativo)+' BCV'
          : monedaById(m.cur) ? fmtMonedaVal(m.nativo,monedaById(m.cur))
          : fmtBs(m.bs);
        return '<div class="order-row"><div class="or-top">'+m.label+'</div><div>'+val+'</div></div>';
      }).join('')
    : '<div class="or-sub">Sin ventas en este turno todavía.</div>';

  const digitalesRows=cuentasDigitalesRowsHTML(d);

  // v51 — pedido de Jonathan: "Efectivo esperado en cierre" se quita como bloque aparte (ya lo
  // cubre "Ventas del turno por método" de arriba) y el arqueo de EFECTIVO solo pregunta por las
  // monedas que de verdad se usaron este turno (aparecieron en porMetodo como método en efectivo,
  // o tenían fondo inicial ≠ 0) — antes preguntaba por USD/Bs/cada moneda propia siempre, aunque
  // el turno solo hubiera usado una.
  const monedasUsadas=new Set();
  Object.keys(d.porMetodo).forEach(mid=>{ const cur=methodCashCurrency(mid); if(cur)monedasUsadas.add(cur); });
  if((openTurno.fondo&&openTurno.fondo.usd)>0)monedasUsadas.add('usd');
  if((openTurno.fondo&&openTurno.fondo.bs)>0)monedasUsadas.add('bs');
  monedasActivas().forEach(m=>{ if((openTurno.fondo&&openTurno.fondo[m.id])>0)monedasUsadas.add(m.id); });
  let efectivoRows='';
  if(monedasUsadas.has('usd'))efectivoRows+=arqueoRowHTML('USD efectivo',d.efectivo.usd,fmtUSD,'id="cjCUsd"');
  if(monedasUsadas.has('bs'))efectivoRows+=arqueoRowHTML('Bolívares efectivo',d.efectivo.bs,fmtBs,'id="cjCBs"');
  efectivoRows+=monedasActivas().filter(m=>monedasUsadas.has(m.id))
    .map(m=>arqueoRowHTML(m.nombre+' efectivo',d.efectivo[m.id]||0,(v)=>fmtMonedaVal(v,m),'data-contado="'+m.id+'"')).join('');

  return '<div class="cfg-block">'
    +'<div class="cc-row" style="margin-bottom:6px"><div class="cc-mid"><div class="cc-name">Turno abierto</div><div class="cc-sub">Desde '+fmtTime(openTurno.inicio)+' · '+durTxt+'</div></div>'
    +'<div class="cc-saldo">Vendido<br><b>'+ventidoPorMonedaLine(d.porMetodo)+'</b></div></div>'
    +'<div class="desc" style="margin:6px 0 4px">Fondo inicial: '+fmtUSD(openTurno.fondo.usd)+' · '+fmtBs(openTurno.fondo.bs)+monedasActivas().map(m=>' · '+fmtMonedaVal((openTurno.fondo&&openTurno.fondo[m.id])||0,m)).join('')+'</div>'
    +'</div>'
    +'<div class="cfg-block"><h4>Ventas del turno por método</h4><div class="desc">Ya incluye los abonos a fiados que te pagaron por cada método.</div>'+metodosHtml
    +(d.egresosTurno&&d.egresosTurno.length
      ?'<div class="desc" style="margin-top:10px">Sacaste de la caja <b>'+fmtBs(d.egresosBs)+'</b> en '+d.egresosTurno.length+' gasto'+(d.egresosTurno.length===1?'':'s')+' (registrados en Contabilidad), ya descontado de lo que cuentes al cerrar.</div>'
      :'')
    +'</div>'
    +reporteZHTML()
    +(fiadoBs>0?'<div class="cfg-block"><h4>Fiado en el turno (referencia)</h4><div class="desc">Esto NO es plata que entró — es lo que se entregó fiado durante el turno.</div><div class="order-row"><div class="or-top">'+fiadosTurno.length+' fiado'+(fiadosTurno.length===1?'':'s')+'</div><div style="color:var(--red)">'+fmtBs(fiadoBs)+'</div></div></div>':'')
    +'<div class="cfg-block"><h4>Cerrar turno (arqueo)</h4>'
      +'<div class="desc">Ve bajando y escribe lo que de verdad tienes de cada cosa: el efectivo lo cuentas de la gaveta, lo digital lo miras en el saldo de cada cuenta. A propósito no te decimos cuánto debería haber antes de que cuentes — si sobra o falta, aparece abajo recién cuando termines.</div>'
      +(efectivoRows?'<div class="or-sub" style="margin:10px 0 4px;font-weight:600">Efectivo en la gaveta</div>'+efectivoRows:'')
      +(digitalesRows?'<div class="or-sub" style="margin:14px 0 4px;font-weight:600">Cuentas digitales</div>'
        +'<div class="desc" style="margin:0 0 8px">Por si un pago móvil o un Zelle no llegó como esperabas. Es control interno tuyo: no cambia el resumen fiscal de arriba.</div>'
        +digitalesRows:'')
      +(!efectivoRows&&!digitalesRows?'<div class="desc">Nada que arquear todavía — este turno no ha movido plata.</div>':'')
      +'<div class="paymix-summary" id="cjArqueo" style="margin-top:10px"></div>'
      +'<div class="form-actions" style="margin-top:12px"><button class="btn-secondary" id="cjCerrar" style="width:100%">Cerrar turno</button></div>'
    +'</div>';
}
function cajonHistorialHTML(){
  if(!turnosCerrados.length)return '';
  return '<div class="cfg-block"><h4>Turnos anteriores</h4>'
    +turnosCerrados.slice().reverse().map(t=>{
      const difTxt=t.difBs===0?'cuadró ✓':(t.difBs>0?'sobró '+fmtBs(t.difBs):'faltó '+fmtBs(-t.difBs));
      const difColor=t.difBs===0?'var(--green)':(t.difBs>0?'var(--text-dim)':'var(--red)');
      const dig=(t.cuentasDigitales||[]).filter(f=>f.contadoNativo!==null);
      const digConDif=dig.filter(f=>Math.round((f.contadoNativo-f.esperadoNativo)*100)!==0);
      const digTxt=dig.length
        ?('<div class="or-sub">Cuentas digitales: '+(digConDif.length?digConDif.length+' con diferencia':'cuadraron ✓')+'</div>')
        :'';
      return '<div class="order-row"><div><div class="or-top">'+fmtDate(t.inicio)+' · '+fmtTime(t.inicio)+'→'+fmtTime(t.fin)+'</div><div class="or-sub">Vendido '+fmtBs(t.totalBs)+'</div>'+digTxt+'</div><div style="color:'+difColor+'">'+difTxt+'</div></div>';
    }).join('')
    +'</div>';
}
// v32 — respaldo a un toque, sin entrar a Configuración (pedido de Jonathan). Vive en Cajón
// porque es donde el dueño ya viene a cerrar el día — el mismo momento en que conviene respaldar.
// Es un atajo al MISMO guardarRespaldo() de Configuración → Negocio → Respaldo, no una vía
// paralela: si mañana cambia la regla del respaldo, cambia en un solo sitio.
function respaldoTarjetaHTML(){
  if(!puntoAccountToken){
    return '<div class="cfg-block"><h4>Respaldo en la nube</h4>'
      +'<div class="desc">Tus ventas, catálogo y clientes viven <b>solo en este teléfono</b>. Si se pierde o se daña, se pierde todo. Crea una cuenta (Configuración → Negocio → Identidad) para guardar una copia en la nube.</div>'
    +'</div>';
  }
  let cuando='Todavía no has respaldado desde este teléfono.';
  if(ultimoRespaldoOk){
    const d=new Date(ultimoRespaldoOk);
    const mins=Math.max(0,Math.round((Date.now()-d.getTime())/60000));
    cuando = mins<60 ? ('Último respaldo: hace '+mins+' min.')
      : mins<1440 ? ('Último respaldo: hace '+Math.floor(mins/60)+' h.')
      : ('Último respaldo: '+fmtDate(d)+'.');
  }
  return '<div class="cfg-block"><h4>Respaldo en la nube</h4>'
    +'<div class="desc">'+cuando+'</div>'
    +'<details class="note"><summary><span class="n-closed">¿Cómo funciona? ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">'
      +'Todo lo que vendes se guarda primero <b>en este teléfono</b>, funcione o no el internet — por eso puedes seguir vendiendo con la señal caída. El respaldo sube una copia a la nube para que, si el teléfono se pierde, se daña o te lo roban, puedas recuperar tu catálogo, tus clientes y tus ventas en otro equipo iniciando sesión con tu correo.<br><br>'
      +'<b>Se guarda un respaldo por día.</b> Si respaldas otra vez el mismo día, se actualiza el de hoy en vez de crear uno nuevo. Se hace solo cada vez que cierras turno, y con este botón cuando tú quieras.<br><br>'
      +'<b>Ojo:</b> el respaldo NO es lo mismo que la sincronización de ventas. Las ventas se van subiendo solas si tienes un código activo; el respaldo es la foto completa de tu negocio para poder recuperarlo.'
    +'</div></details>'
    +'<button class="btn-secondary" id="cjRespaldarBtn" style="width:100%;margin-top:8px">Respaldar ahora</button>'
    +'<div class="desc" id="cjRespaldoAviso" style="margin-top:8px"></div>'
  +'</div>';
}
function renderCajon(){
  $('cajonBody').innerHTML=(openTurno?cajonAbiertoHTML():cajonAbrirHTML())+respaldoTarjetaHTML();
  if(openTurno){ updateArqueo(); }
}
// v4 — un solo resumen para todo el cierre. Antes había dos cajas separadas (una para el
// efectivo, otra para las cuentas digitales) y podías cerrar el turno mirando solo una.
function updateArqueo(){
  const box=$('cjArqueo');if(!box)return;
  const d=turnoDesglose();
  // v51 — mismo null-safe que #cjCerrar: las filas USD/Bs efectivo son condicionales ahora.
  const cuEl=$('cjCUsd'), cbEl=$('cjCBs');
  const cu=cuEl?parseFloat(cuEl.value):NaN, cb=cbEl?parseFloat(cbEl.value):NaN;
  const parts=[];
  let anyDiff=false;
  const linea=(label,dif,fmtFn)=>{ if(dif!==0)anyDiff=true; return label+': '+(dif===0?'cuadra ✓':(dif>0?'sobra '+fmtFn(dif):'falta '+fmtFn(-dif))); };
  if(!isNaN(cu)) parts.push(linea('USD efectivo',Math.round((cu-d.efectivo.usd)*100)/100,fmtUSD));
  if(!isNaN(cb)) parts.push(linea('Bs efectivo',Math.round(cb-d.efectivo.bs),fmtBs));
  monedasActivas().forEach(m=>{
    const inp=document.querySelector('[data-contado="'+m.id+'"]'); if(!inp)return;
    const v=parseFloat(inp.value); if(isNaN(v))return;
    parts.push(linea(m.nombre+' efectivo',Math.round(v-(d.efectivo[m.id]||0)),(x)=>fmtMonedaVal(x,m)));
  });
  digitalReconciliacion().filter(f=>f.contadoNativo!==null).forEach(f=>{
    const esUsd=f.cur==='usd'||f.cur==='usd_bcv';
    const fmtFn=esUsd?fmtUSD:fmtBs;
    const dif=esUsd?Math.round((f.contadoNativo-f.esperadoNativo)*100)/100:Math.round(f.contadoNativo-f.esperadoNativo);
    parts.push(linea(f.label,dif,fmtFn));
  });
  if(!parts.length){ box.className='paymix-summary'; box.textContent='Escribe lo que contaste para ver la diferencia.'; return; }
  box.className='paymix-summary '+(anyDiff?'falta':'ok');
  box.innerHTML=parts.join('<br>');
}
// v26_3 — misma mecánica de updateArqueo() pero recorriendo los inputs [data-cuenta-digital],
// uno por cada método digital usado en el turno (ver cuentasDigitalesRowsHTML).
function digitalReconciliacion(){
  const d=turnoDesglose();
  return [...document.querySelectorAll('[data-cuenta-digital]')].map(inp=>{
    const mid=inp.dataset.cuentaDigital, cur=inp.dataset.cur;
    const m=d.porMetodo[mid];
    const esperadoNativo=(cur==='usd'||cur==='usd_bcv')?m.nativo:m.bs;
    const val=parseFloat(inp.value);
    return {mid,label:m.label,cur,esperadoBs:m.bs,esperadoNativo,contadoNativo:isNaN(val)?null:val};
  });
}
// v4 — updateArqueoDigital() se eliminó: su caja aparte ya no existe, todo lo resume updateArqueo().
$('cajonBody').addEventListener('input',e=>{
  if(e.target.closest('#cjCUsd,#cjCBs,[data-contado],[data-cuenta-digital]'))updateArqueo();
});
$('cajonBody').addEventListener('click',async e=>{
  // v32 — atajo de respaldo (ver respaldoTarjetaHTML). No re-renderiza el Cajón entero al
  // terminar a propósito: si hay un turno abierto, el usuario podría tener escrito a medias
  // el arqueo, y volver a pintar le borraría lo tecleado. Solo se actualiza el aviso.
  if(e.target.closest('#cjRespaldarBtn')){
    const btn=e.target.closest('#cjRespaldarBtn'), aviso=$('cjRespaldoAviso');
    btn.disabled=true; btn.textContent='Respaldando…';
    const r=await guardarRespaldo();
    btn.disabled=false; btn.textContent='Respaldar ahora';
    if(aviso){
      aviso.textContent = r.ok ? ('Listo — respaldo guardado ('+(r.cantidad||1)+' de '+(r.cupo||2)+' usados).') : (r.error||'No se pudo respaldar.');
      aviso.style.color = r.ok ? 'var(--green)' : 'var(--amber)';
    }
    return;
  }
  if(e.target.closest('#cjAbrir')){
    openTurno={
      id:'t'+(turnoSeq++),
      inicio:new Date(),
      fondo:{
        usd:parseFloat($('cjFondoUsd').value)||0,
        bs:parseFloat($('cjFondoBs').value)||0
      }
    };
    monedasActivas().forEach(m=>{
      const inp=document.querySelector('[data-fondo="'+m.id+'"]');
      openTurno.fondo[m.id]=inp?(parseFloat(inp.value)||0):0;
    });
    schedulePersist(true);
    renderCajon();renderNav();renderGrid();
    return;
  }
  if(e.target.closest('#cjCerrar')){
    const d=turnoDesglose();
    // v51 — las filas USD efectivo/Bolívares efectivo ahora son condicionales (solo si esa moneda
    // se usó este turno, ver cajonAbiertoHTML) — el input puede no existir en el DOM, a diferencia
    // de antes que siempre estaba. Sin el elemento, NaN (mismo efecto que "no lo contó": no suma
    // diferencia), igual que ya hace el bloque de monedas propias justo abajo.
    const cuEl=$('cjCUsd'), cbEl=$('cjCBs');
    const cu=cuEl?parseFloat(cuEl.value):NaN, cb=cbEl?parseFloat(cbEl.value):NaN;
    // diferencia total llevada a Bs para el resumen del historial
    let difBs=0;
    const contadoMonedas={};
    if(!isNaN(cu))difBs+=convertToBs(cu-d.efectivo.usd,'usd');
    if(!isNaN(cb))difBs+=(cb-d.efectivo.bs);
    monedasActivas().forEach(m=>{
      const inp=document.querySelector('[data-contado="'+m.id+'"]'); if(!inp)return;
      const v=parseFloat(inp.value); if(isNaN(v))return;
      contadoMonedas[m.id]=v;
      difBs+=convertToBs(v-(d.efectivo[m.id]||0),m.id);
    });
    difBs=Math.round(difBs);
    askConfirm('¿Cerrar el turno? Ya no podrás agregarle ventas.',()=>{
      turnosCerrados.push({
        id:openTurno.id,inicio:openTurno.inicio,fin:new Date(),
        fondo:openTurno.fondo,totalBs:d.totalBs,esperado:d.efectivo,
        contado:Object.assign({usd:isNaN(cu)?null:cu,bs:isNaN(cb)?null:cb},contadoMonedas),
        difBs,
        // v26_3 — reconciliación de cuentas digitales del turno, para el historial
        cuentasDigitales:digitalReconciliacion()
      });
      openTurno=null;
      schedulePersist(true);
      renderCajon();renderNav();renderGrid();   // vuelve el aviso de caja cerrada en Venta
      // v1.5/v1.6 del backend — respaldo y contabilidad del mes anterior, en silencio: si no
      // hay cuenta o no hay internet simplemente no pasa nada, la venta ya quedó guardada local.
      guardarRespaldo().then(()=>{ if(typeof renderRespaldo==='function') renderRespaldo(); });
      enviarContabilidadMensual();
    });
    return;
  }
});

// ===== Informes =====
// filtros de rango de fecha + ganancia neta por producto. La ganancia se calcula con
// costUsd/costBs, que quedan CONGELADOS en cada ítem al momento de vender (mismo criterio
// que priceBs/priceUsd) — así el informe de un día viejo nunca cambia aunque el costo del
// producto se actualice después en Inventario. Cuenta ventas pagadas Y fiadas (el producto ya
// salió del negocio en ambos casos), pero separa cuánto de esa ganancia ya se cobró.
let informesRange='hoy',informesCustomFrom=null,informesCustomTo=null;
// orden de las listas de Ganancia por producto / Categorías — 'ganancia' es el default en ambas
// (pedido explícito de Jonathan); 'menos_vendido' sirve también para ver lo que nunca se vendió
// en el período, porque ordena ascendente y esos productos quedan con qty=0 arriba de todo.
let informesProdSort='ganancia',informesProdExpanded=false;
let informesCatSort='ganancia',informesCatExpanded=false;
const INFORMES_RANGES=[
  {id:'hoy',label:'Hoy'},{id:'ayer',label:'Ayer'},{id:'semana',label:'7 días'},
  {id:'mes',label:'30 días'},{id:'3m',label:'3 meses'},{id:'anio',label:'Año'},
  {id:'todo',label:'Todo'},{id:'custom',label:'Personalizado'},
];
const INF_PROD_SORTS=[
  {id:'ganancia',label:'Ganancia'},{id:'vendido',label:'Más vendido'},
  {id:'menos_vendido',label:'Menos vendido'},{id:'ingreso',label:'Más ingreso'},
];
const INF_CAT_SORTS=[
  {id:'ganancia',label:'Ganancia'},{id:'vendido',label:'Más vendido'},{id:'ingreso',label:'Más ingreso'},
];
function informesRangeDates(){
  const startOfDay=d=>{const x=new Date(d);x.setHours(0,0,0,0);return x};
  const endOfDay=d=>{const x=new Date(d);x.setHours(23,59,59,999);return x};
  const today0=startOfDay(new Date());
  let from=today0,to=endOfDay(new Date());
  if(informesRange==='ayer'){ from=new Date(today0);from.setDate(from.getDate()-1); to=endOfDay(from); }
  else if(informesRange==='semana'){ from=new Date(today0);from.setDate(from.getDate()-6); }
  else if(informesRange==='mes'){ from=new Date(today0);from.setDate(from.getDate()-29); }
  else if(informesRange==='3m'){ from=new Date(today0);from.setDate(from.getDate()-89); }
  else if(informesRange==='anio'){ from=new Date(today0);from.setDate(from.getDate()-364); }
  else if(informesRange==='todo'){ from=new Date(0); }
  else if(informesRange==='custom'){
    from=informesCustomFrom?startOfDay(new Date(informesCustomFrom+'T00:00:00')):new Date(0);
    to=informesCustomTo?endOfDay(new Date(informesCustomTo+'T00:00:00')):to;
  }
  return {from,to};
}
// granularidad de la gráfica según el rango elegido: entre más largo el rango, buckets más gruesos
// (Hoy/Ayer -> hora, 7d/30d -> día, 3 meses -> semana, Año/Todo -> mes; Personalizado se calcula
// según cuántos días abarque de verdad, con los mismos cortes).
const INF_MESES=['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
function informesGranularity(){
  if(informesRange==='hoy'||informesRange==='ayer')return 'hora';
  if(informesRange==='semana'||informesRange==='mes')return 'dia';
  if(informesRange==='3m')return 'semana';
  if(informesRange==='anio'||informesRange==='todo')return 'mes';
  const {from,to}=informesRangeDates();
  const dias=(to-from)/86400000;
  if(dias<=2)return 'hora';
  if(dias<=45)return 'dia';
  if(dias<=210)return 'semana';
  return 'mes';
}
// arma la key (para agrupar), el label (para mostrar) y un timestamp (para ordenar) de un momento
// según la granularidad — misma fecha siempre cae en el mismo bucket.
function informesBucketInfo(date,gran){
  const dt=new Date(date);
  const y=dt.getFullYear(),mo=dt.getMonth(),da=dt.getDate(),h=dt.getHours();
  const pad=n=>String(n).padStart(2,'0');
  if(gran==='hora')return {key:y+'-'+pad(mo+1)+'-'+pad(da)+'-'+pad(h),label:pad(h)+':00',ord:new Date(y,mo,da,h).getTime()};
  if(gran==='dia')return {key:y+'-'+pad(mo+1)+'-'+pad(da),label:da+'/'+(mo+1),ord:new Date(y,mo,da).getTime()};
  if(gran==='semana'){
    const ws=new Date(y,mo,da);const dow=(ws.getDay()+6)%7;ws.setDate(ws.getDate()-dow); // lunes de esa semana
    return {key:ws.getFullYear()+'-'+pad(ws.getMonth()+1)+'-'+pad(ws.getDate()),label:ws.getDate()+'/'+(ws.getMonth()+1),ord:ws.getTime()};
  }
  return {key:y+'-'+pad(mo+1),label:INF_MESES[mo]+' '+String(y).slice(-2),ord:new Date(y,mo,1).getTime()};
}
// lista completa y ordenada de buckets entre from/to (con huecos en 0 donde no hubo venta) —
// así la gráfica se ve como una línea de tiempo real, no solo puntos sueltos. Tope de 400 buckets
// como salvavidas por si un rango queda mal formado.
function informesBucketRange(from,to,gran){
  const out=[];
  let cur=new Date(from);
  if(gran==='hora')cur.setMinutes(0,0,0);
  else if(gran==='dia')cur.setHours(0,0,0,0);
  else if(gran==='semana'){cur.setHours(0,0,0,0);const dow=(cur.getDay()+6)%7;cur.setDate(cur.getDate()-dow);}
  else {cur.setDate(1);cur.setHours(0,0,0,0);}
  const end=new Date(to);
  let guard=0;
  while(cur<=end&&guard<400){
    out.push(informesBucketInfo(cur,gran));
    if(gran==='hora')cur.setHours(cur.getHours()+1);
    else if(gran==='dia')cur.setDate(cur.getDate()+1);
    else if(gran==='semana')cur.setDate(cur.getDate()+7);
    else cur.setMonth(cur.getMonth()+1);
    guard++;
  }
  return out;
}
function informesData(){
  const {from,to}=informesRangeDates();
  const list=orders.filter(o=>o.date>=from&&o.date<=to);
  let ventasBs=0,gananciaBs=0,gananciaCobradaBs=0,gananciaFiadaBs=0,sinCosto=false,unitsSold=0;
  let ivaDebitoBs=0,ivaCreditoBs=0;
  const porProducto={},porMetodo={},porCategoria={};
  const gran=informesGranularity();
  const bucketMap={};
  list.forEach(o=>{
    ventasBs+=o.totalBs;
    let gananciaOrden=0;
    o.items.forEach(it=>{
      const ingresoBs=it.priceBs*it.qty;
      const ivaDebitoLinea=(it.ivaDebitoBs||0)*it.qty;
      const ivaCreditoLinea=(it.ivaCreditoBs||0)*it.qty;
      ivaDebitoBs+=ivaDebitoLinea; ivaCreditoBs+=ivaCreditoLinea;
      unitsSold+=it.qty;
      if(!porProducto[it.productId])porProducto[it.productId]={name:it.name,qty:0,ingresoBs:0,costoBs:0,gananciaBs:0,sinCosto:false,esPeso:!!it.esPeso};
      const pp=porProducto[it.productId];
      pp.qty+=it.qty; pp.ingresoBs+=ingresoBs;
      // categoría del producto: se busca en el catálogo ACTUAL (no se congela por ítem, a diferencia
      // del precio/costo) — hoy no hay forma de borrar un producto, así que en la práctica siempre se
      // encuentra; el fallback es solo por seguridad.
      const prod=products.find(pr=>pr.id===it.productId);
      const catId=prod?prod.cat:'sin_categoria';
      if(!porCategoria[catId])porCategoria[catId]={catId,qty:0,ingresoBs:0,gananciaBs:0};
      const pc=porCategoria[catId];
      pc.qty+=it.qty; pc.ingresoBs+=ingresoBs;
      if(it.costBs!=null){
        const costoLinea=it.costBs*it.qty;
        // ganancia REAL = ingreso sin el IVA Débito cobrado (esa parte no es ganancia, es plata
        // que se le debe al SENIAT) menos el costo — control fiscal SENIAT (v17).
        const ingresoRealLinea=ingresoBs-ivaDebitoLinea;
        pp.costoBs+=costoLinea; pp.gananciaBs+=(ingresoRealLinea-costoLinea);
        pc.gananciaBs+=(ingresoRealLinea-costoLinea);
        gananciaOrden+=(ingresoRealLinea-costoLinea);
      } else { pp.sinCosto=true; sinCosto=true; } // venta de antes de este cambio: no se puede calcular su ganancia
    });
    gananciaBs+=gananciaOrden;
    if(o.status==='fiado')gananciaFiadaBs+=gananciaOrden; else gananciaCobradaBs+=gananciaOrden;
    if(o.status==='pagado'){
      const pagos=o.paymentsBreakdown||[];
      if(pagos.length){
        // v27 — mismo criterio del fix v26_1 (turnoDesglose/Cajón): repartir el o.totalBs YA
        // CONGELADO entre los pagos, no reconvertir cada pago a la tasa ACTUAL. Antes esto vivía
        // sin usarse en pantalla; ahora que "por método de pago" de Informes muestra más detalle
        // (multi-moneda + export), hay que blindarlo contra el mismo par de Bs de diferencia que
        // ya se corrigió en Cajón — si no, "por método" podría no cuadrar con "Vendido" arriba.
        const pesos=pagos.map(p=>convertToBs(p.amount,p.currency));
        const pesoTotal=pesos.reduce((s,x)=>s+x,0)||1;
        let repartidoBs=0;
        pagos.forEach((p,i)=>{
          const esUltimo=i===pagos.length-1;
          const bsPago=esUltimo?(o.totalBs-repartidoBs):Math.round(o.totalBs*(pesos[i]/pesoTotal));
          repartidoBs+=bsPago;
          porMetodo[p.method]=(porMetodo[p.method]||0)+bsPago;
        });
      } else if(o.method){
        porMetodo[o.method]=(porMetodo[o.method]||0)+o.totalBs; // pedidos viejos de muestra, sin desglose de pago
      }
    }
    const bi=informesBucketInfo(o.date,gran);
    if(!bucketMap[bi.key])bucketMap[bi.key]={label:bi.label,ventasBs:0,gananciaBs:0};
    bucketMap[bi.key].ventasBs+=o.totalBs;
    bucketMap[bi.key].gananciaBs+=gananciaOrden;
  });
  // rango de la gráfica: "Todo"/"Personalizado sin desde" no pueden arrancar en 1970 (informesRangeDates
  // usa new Date(0) como "sin límite") — para graficar se usa la fecha de la venta más vieja del período.
  // El bucket de "Hoy" tampoco debe llegar a horas futuras del día, por eso se topa con la hora actual.
  let chartFrom=from;
  if(informesRange==='todo'||(informesRange==='custom'&&!informesCustomFrom)){
    chartFrom=list.length?new Date(Math.min.apply(null,list.map(o=>+o.date))):to;
  }
  const chartTo=informesRange==='hoy'?new Date():to;
  let buckets=(list.length&&chartFrom<=chartTo)
    ?informesBucketRange(chartFrom,chartTo,gran).map(b=>{
        const found=bucketMap[b.key];
        return {label:b.label,ventasBs:found?found.ventasBs:0,gananciaBs:found?found.gananciaBs:0};
      })
    :[];
  // recortar los buckets vacíos del principio/final: en rangos como "Hoy" o "30 días" la mayoría
  // de las horas/días del rango pueden no tener ninguna venta (ej. hoy apenas son las 9am, o en 30
  // días las ventas se concentran en la última semana) — sin esto, la parte con datos reales queda
  // fuera de la pantalla y hay que hacer scroll para encontrarla, dando la impresión de una gráfica
  // "vacía"/rota. Se deja 1 bucket de margen a cada lado (si existe) para que no arranque en seco.
  if(buckets.length){
    let firstIdx=buckets.findIndex(b=>b.ventasBs>0||b.gananciaBs>0);
    if(firstIdx===-1){ buckets=[]; }
    else{
      let lastIdx=buckets.length-1;
      while(lastIdx>0&&!(buckets[lastIdx].ventasBs>0||buckets[lastIdx].gananciaBs>0))lastIdx--;
      firstIdx=Math.max(0,firstIdx-1);
      lastIdx=Math.min(buckets.length-1,lastIdx+1);
      buckets=buckets.slice(firstIdx,lastIdx+1);
    }
  }
  return {from,to,list,ventasBs,gananciaBs,gananciaCobradaBs,gananciaFiadaBs,sinCosto,unitsSold,porProducto,porMetodo,porCategoria,buckets,ivaDebitoBs,ivaCreditoBs};
}
// productos para "Ganancia por producto": arranca de lo vendido en el período (porProducto) y le
// suma el resto del catálogo actual en 0 — así "menos vendido" puede mostrar arriba lo que nunca
// se vendió en el período, no solo lo que vendió poquito.
function informesProductRows(d){
  const rows={};
  Object.entries(d.porProducto).forEach(([pid,pp])=>{
    rows[pid]={name:pp.name,qty:pp.qty,ingresoBs:pp.ingresoBs,gananciaBs:pp.gananciaBs,sinCosto:pp.sinCosto,esPeso:pp.esPeso};
  });
  products.forEach(p=>{ if(!rows[p.id])rows[p.id]={name:p.name,qty:0,ingresoBs:0,gananciaBs:0,sinCosto:false,esPeso:!!p.ventaPeso}; });
  return Object.values(rows);
}
// categorías: solo las que tuvieron venta en el período (no tiene sentido "nunca vendida" acá,
// serían casi todas — a diferencia de productos, que sí es información útil por bodega).
function informesCategoryRows(d){
  return Object.values(d.porCategoria).map(pc=>({
    name:pc.catId==='sin_categoria'?'Sin categoría':catLabelOf(pc.catId),
    qty:pc.qty,ingresoBs:pc.ingresoBs,gananciaBs:pc.gananciaBs,
  }));
}
// mismo criterio de orden para productos y categorías: ambos tienen {qty,ingresoBs,gananciaBs}
function sortInformesRows(rows,sort){
  const r=rows.slice();
  if(sort==='vendido')r.sort((a,b)=>b.qty-a.qty||b.gananciaBs-a.gananciaBs);
  else if(sort==='menos_vendido')r.sort((a,b)=>a.qty-b.qty||a.gananciaBs-b.gananciaBs);
  else if(sort==='ingreso')r.sort((a,b)=>b.ingresoBs-a.ingresoBs);
  else r.sort((a,b)=>b.gananciaBs-a.gananciaBs); // 'ganancia' (default)
  return r;
}
function informesSortDesc(sort){
  if(sort==='vendido')return 'De más a menos unidades vendidas en el período.';
  if(sort==='menos_vendido')return 'De menos a más unidades vendidas — arriba, lo que casi no se movió o nunca se vendió en este período.';
  if(sort==='ingreso')return 'De mayor a menor dinero vendido en el período.';
  return 'De mayor a menor ganancia generada en el período.';
}
function informesRowHtml(row){
  const marginTxt=row.ingresoBs>0?Math.round(row.gananciaBs/row.ingresoBs*100)+'%':'—';
  const sub=[(row.esPeso?formatPeso(row.qty):row.qty+' unid.'),'margen '+marginTxt];
  if(row.sinCosto)sub.push('sin costo congelado');
  return '<div class="order-row"><div><div class="or-top">'+row.name+'</div><div class="or-sub">'+sub.join(' · ')+'</div></div><div style="text-align:right"><div>'+fmtBs(row.gananciaBs)+'</div><div class="or-sub">ingreso '+fmtBs(row.ingresoBs)+'</div></div></div>';
}
// bloque genérico de lista ordenable con top-5 + "Ver más" — usado por Ganancia por producto y
// por Categorías más vendidas, mismo patrón visual (chips + order-row) que ya usa el resto de Informes.
// v1.9 — el título de cada bloque de Informes se ve siempre (para que quede claro qué existe);
// lo que se paga es el contenido. blurWrap() no lo esconde del DOM (no hay nada que ocultar de
// verdad, todo se calcula local) — solo lo hace ilegible y no interactivo, como vitrina.
function blurWrap(html){
  return '<div style="filter:blur(4px);opacity:.55;pointer-events:none;user-select:none">'+html+'</div>';
}
// aviso de lo que desbloquea el código. Mismo bloque visual que Configuración → Activación, para
// que se reconozca al instante como "esto es lo de pagar" y no como un error de la app.
function premiumAvisoHTML(titulo,puntos){
  return '<div class="premium-box" style="margin-top:12px">'
    +'<div class="premium-head"><span class="premium-tag">FrixPOS Premium</span></div>'
    +'<div style="font-weight:700;font-size:.9rem;margin-bottom:8px">'+titulo+'</div>'
    +'<div class="premium-list">'+puntos.map(p=>'<div>'+p+'</div>').join('')+'</div>'
    +'<a class="premium-cta" href="/activar-codigo" target="_blank" rel="noopener">Quiero mi código</a>'
    +'<div class="desc" style="margin:8px 0 0;text-align:center">¿Ya tienes uno? Actívalo en Configuración → Negocio</div>'
  +'</div>';
}
// v1.12 — aviso de la prueba gratis: se muestra en Informes/Contabilidad en vez del cartel de
// venta (premiumAvisoHTML) cuando esPremium() es true SOLO porque hay trial activo, no porque
// haya un código realmente activado. Tono informativo, no de venta — ya está desbloqueado.
function trialAvisoHTML(){
  if(!esPremiumPorTrial())return '';
  const dias=diasRestantesTrial();
  return '<div class="premium-box" style="margin-top:12px">'
    +'<div class="premium-head"><span class="premium-tag">Prueba gratis</span></div>'
    +'<div style="font-weight:700;font-size:.9rem;margin-bottom:4px">Estás disfrutando de tu prueba gratis</div>'
    +'<div class="desc" style="margin:0">Te '+(dias<=0?'queda menos de 1 día':dias===1?'queda 1 día':'quedan '+dias+' días')+' con Informes y Contabilidad completos, sin código. Actívalo antes de que termine para no perder el acceso.</div>'
    +'<a class="premium-cta" href="/activar-codigo" target="_blank" rel="noopener" style="margin-top:10px">Quiero mi código</a>'
  +'</div>';
}
// variante compacta: para cuando ya se mostró la caja grande más arriba en la misma pantalla y
// repetirla completa se siente como publicidad. Misma marca, sin volver a listar todo.
function premiumNotaHTML(puntos){
  return '<div class="premium-nota">'
    +'<span class="premium-tag">FrixPOS Premium</span>'
    +'<div class="premium-list" style="margin-top:8px">'+puntos.map(p=>'<div>'+p+'</div>').join('')+'</div>'
    +'<a class="premium-link" href="/activar-codigo" target="_blank" rel="noopener">Quiero mi código →</a>'
  +'</div>';
}
function informesListBlock(opts){
  const sorted=sortInformesRows(opts.rows,opts.curSort);
  const shown=opts.expanded?sorted:sorted.slice(0,5);
  const chipsHtml=opts.sorts.map(s=>'<button class="chip chip-sm'+(opts.curSort===s.id?' on':'')+'" data-sort="'+s.id+'">'+s.label+'</button>').join('');
  const rowsHtml=shown.length?shown.map(informesRowHtml).join(''):'<div class="cart-empty" style="padding:14px 0"><b>Sin datos</b>No hay ventas en este período.</div>';
  const moreBtn=sorted.length>5?'<button class="btn-secondary" id="'+opts.moreId+'" style="width:100%;text-align:center;margin-top:6px">'+(opts.expanded?'Ver menos ↑':'Ver más ('+(sorted.length-5)+') ↓')+'</button>':'';
  const cuerpo='<div class="chips" id="'+opts.chipsId+'">'+chipsHtml+'</div>'+rowsHtml+moreBtn;
  return '<div class="cfg-block"><h4>'+opts.title+'</h4><div class="desc">'+informesSortDesc(opts.curSort)+'</div>'
    +(opts.blur?blurWrap(cuerpo):cuerpo)+'</div>';
}
// v50 — pedido de Jonathan: Tendencia pasa de línea a BARRAS de "Vendido", y cuando el rango es
// "Hoy" o "7 días" compara contra el período inmediatamente anterior (mismo tamaño): Hoy = por
// hora contra ayer; 7 días = por día contra los 7 días previos, alineados por posición (así el
// lunes de esta semana cae justo debajo del lunes de la semana pasada). Para el resto de rangos
// (30 días, 3 meses, año, todo, personalizado) se queda una sola barra por bucket, sin comparar
// contra nada — ahí "período anterior" no estaba definido en el pedido.
function informesComparativeBuckets(){
  if(informesRange!=='hoy'&&informesRange!=='semana')return null;
  const {from,to}=informesRangeDates();
  const gran=informesRange==='hoy'?'hora':'dia';
  const spanMs=to-from;
  const prevTo=new Date(from.getTime()-1);
  const prevFrom=new Date(prevTo.getTime()-spanMs);
  function bucketsFor(fromD,toD){
    const list=orders.filter(o=>o.date>=fromD&&o.date<=toD);
    const map={};
    list.forEach(o=>{
      const bi=informesBucketInfo(o.date,gran);
      map[bi.key]=(map[bi.key]||0)+o.totalBs;
    });
    return informesBucketRange(fromD,toD,gran).map(b=>({label:b.label,ventasBs:map[b.key]||0}));
  }
  const current=bucketsFor(from,to);
  const previous=bucketsFor(prevFrom,prevTo);
  const n=Math.max(current.length,previous.length);
  const paired=[];
  for(let i=0;i<n;i++){
    paired.push({
      label:(current[i]&&current[i].label)||(previous[i]&&previous[i].label)||'',
      curVal:current[i]?current[i].ventasBs:0,
      prevVal:previous[i]?previous[i].ventasBs:0,
    });
  }
  return paired;
}
// gráfica de tendencia en SVG a mano — la app no usa librerías externas. `items` es
// [{label,curVal,prevVal?}]; si trae prevVal, se dibuja un par de barras por posición (actual +
// período anterior); si no, una sola barra. Ancho proporcional a la cantidad de posiciones, con
// scroll horizontal si no caben.
function svgTrendChart(items){
  const n=items.length;
  const comparando=items.some(x=>x.prevVal!=null);
  const barW=comparando?14:20;
  const groupW=comparando?(barW*2+8):(barW+12);
  const w=Math.max(320,n*groupW),h=150,padL=12,padR=12,padT=8,padB=22;
  const innerH=h-padT-padB;
  const maxV=Math.max(1,...items.map(x=>x.curVal||0),...items.map(x=>x.prevVal||0));
  const hFor=v=>(v/maxV)*innerH;
  const showEvery=Math.max(1,Math.ceil(n/7));
  let bars='';
  items.forEach((it,i)=>{
    const gx=padL+i*groupW;
    if(comparando){
      const hPrev=hFor(it.prevVal),hCur=hFor(it.curVal);
      bars+='<rect class="inf-bar-prev" x="'+gx.toFixed(1)+'" y="'+(padT+innerH-hPrev).toFixed(1)+'" width="'+barW+'" height="'+hPrev.toFixed(1)+'" rx="2"/>';
      bars+='<rect class="inf-bar-cur" x="'+(gx+barW+3).toFixed(1)+'" y="'+(padT+innerH-hCur).toFixed(1)+'" width="'+barW+'" height="'+hCur.toFixed(1)+'" rx="2"/>';
    } else {
      const hCur=hFor(it.curVal);
      bars+='<rect class="inf-bar-cur" x="'+gx.toFixed(1)+'" y="'+(padT+innerH-hCur).toFixed(1)+'" width="'+barW+'" height="'+hCur.toFixed(1)+'" rx="2"/>';
    }
  });
  const labels=items.map((it,i)=>{
    if(!(i%showEvery===0||i===n-1))return '';
    const gx=padL+i*groupW+(comparando?(barW+1.5):(barW/2));
    return '<text class="inf-axis-label" x="'+gx.toFixed(1)+'" y="'+(h-6)+'" text-anchor="middle">'+it.label+'</text>';
  }).join('');
  return '<div class="inf-chart-wrap"><svg viewBox="0 0 '+w+' '+h+'" width="'+w+'" height="'+h+'">'+bars+labels+'</svg></div>';
}
function renderInformes(){
  const d=informesData();
  const chipsHtml=INFORMES_RANGES.map(r=>'<button class="chip'+(informesRange===r.id?' on':'')+'" data-r="'+r.id+'">'+r.label+'</button>').join('');
  const customHtml=informesRange==='custom'
    ?'<div class="cfg-row" style="gap:8px"><input class="cfg-input" type="date" id="infFrom" value="'+(informesCustomFrom||'')+'"><input class="cfg-input" type="date" id="infTo" value="'+(informesCustomTo||'')+'"></div>'
    :'';
  const margenPct=d.ventasBs>0?Math.round(d.gananciaBs/d.ventasBs*100):null;
  const otrosVendido=otherCurrenciesLine(d.ventasBs);
  const otrosGanancia=otherCurrenciesLine(d.gananciaBs);
  // v1.9 — Informes es de pago (código activo); "Vendido" se queda gratis para cualquiera
  // (es lo mínimo para saber si el día fue bueno), todo lo demás se ve pero borroso, con su
  // título intacto, como vitrina de lo que desbloquea el código.
  const esGratis=!esPremium();
  const gananciaContenido='<div class="cc-sub">Cobrada '+fmtBs(d.gananciaCobradaBs)+(d.gananciaFiadaBs?' · Fiada '+fmtBs(d.gananciaFiadaBs):'')+(margenPct!==null?' · margen '+margenPct+'%':'')+'</div>'
    +'<div class="cc-saldo ok"><b>'+fmtBs(d.gananciaBs)+'</b>'+(otrosGanancia?'<div style="font-weight:600">'+otrosGanancia+'</div>':'')+'</div>';
  const summaryHtml='<div class="cfg-block">'
      +'<div class="cc-row" style="margin-bottom:10px"><div class="cc-mid"><div class="cc-name">Vendido</div><div class="cc-sub">'+d.list.length+' venta'+(d.list.length===1?'':'s')+' · '+d.unitsSold+' unid.'+(d.list.length?' · ticket prom. '+fmtBs(d.ventasBs/d.list.length):'')+'</div></div><div class="cc-saldo"><b>'+fmtBs(d.ventasBs)+'</b>'+(otrosVendido?'<div style="font-weight:600">'+otrosVendido+'</div>':'')+'</div></div>'
      +'<div class="cc-row"><div class="cc-mid"><div class="cc-name">Ganancia neta</div>'+(esGratis?blurWrap(gananciaContenido):gananciaContenido)+'</div></div>'
      +(esGratis?premiumAvisoHTML('Estás viendo solo cuánto vendiste',[
          'Tu <b>ganancia real</b>, ya descontado lo que te costó la mercancía',
          'Qué producto y qué categoría te dejan más',
          'La gráfica de cómo va tu negocio en el tiempo',
          'Descargar el resumen en Excel'
        ]):trialAvisoHTML())
      +(d.sinCosto?'<div class="desc" style="color:var(--amber);margin-top:10px">⚠Hay ventas en este período de antes de este cambio, sin costo congelado — su ganancia no entra en el total.</div>':'')
    +'</div>';
  // v50 — Tendencia en barras: "Hoy"/"7 días" comparan contra el período anterior (mismo tamaño),
  // el resto de rangos muestra una sola barra de Vendido por bucket (ver svgTrendChart).
  const comparativos=informesComparativeBuckets();
  const chartItems=comparativos||d.buckets.map(b=>({label:b.label,curVal:b.ventasBs}));
  const chartLegend=comparativos
    ?'<div class="inf-legend"><span><b style="background:var(--accent-a)"></b>Este período</span><span><b style="background:var(--text-faint);opacity:.55"></b>Período anterior</span></div>'
    :'<div class="inf-legend"><span><b style="background:var(--accent-a)"></b>Vendido</span></div>';
  const chartCuerpo=chartItems.length
    ?chartLegend+svgTrendChart(chartItems)
    :'<div class="desc" style="margin-bottom:0">Sin ventas en este período para graficar.</div>';
  const chartHtml='<div class="cfg-block"><h4>Tendencia</h4>'+(esGratis?blurWrap(chartCuerpo):chartCuerpo)+'</div>';
  const prodBlock=informesListBlock({title:'Ganancia por producto',chipsId:'informesProdChips',sorts:INF_PROD_SORTS,curSort:informesProdSort,rows:informesProductRows(d),moreId:'informesProdMore',expanded:informesProdExpanded,blur:esGratis});
  const catBlock=informesListBlock({title:'Categorías más vendidas',chipsId:'informesCatChips',sorts:INF_CAT_SORTS,curSort:informesCatSort,rows:informesCategoryRows(d),moreId:'informesCatMore',expanded:informesCatExpanded,blur:esGratis});
  // v27 — mismo patrón que ya usa Cajón desde v26_2 (otherCurrenciesLine bajo el monto en Bs.):
  // antes "por método de pago" solo mostraba Bs. sin importar si el pago fue en $/Zelle/pesos.
  const metodoContenido=Object.keys(d.porMetodo).length
    ?Object.entries(d.porMetodo).map(([mid,bs])=>{
        const m=payMethods.find(x=>x.id===mid);
        const otros=otherCurrenciesLine(bs);
        return '<div class="order-row"><div class="or-top">'+(m?m.label:(mid==='mixto'?'Pago mixto':mid))+'</div><div><b>'+fmtBs(bs)+'</b>'+(otros?'<div class="or-sub">'+otros+'</div>':'')+'</div></div>';
      }).join('')
    :'<div class="or-sub">Sin ventas pagadas en este período.</div>';
  // v27 — Informes no tenía forma de compartir/exportar (Pedidos sí podía desde v9_3). Un CSV
  // simple con el resumen del período elegido, reusando los mismos helpers que ya usa Contabilidad.
  const exportHtml=esGratis
    ?''
    :'<div class="cfg-block"><h4>Exportar</h4>'
      +'<div class="desc">Descarga un resumen de este período — vendido, ganancia, por producto, por categoría y por método de pago — en un CSV que abre en Excel.</div>'
      +'<button class="btn-secondary" id="infExportBtn" style="width:100%;margin-top:8px">Descargar resumen (CSV)</button>'
      +'<div class="desc" id="infExportAviso" style="margin-top:8px"></div>'
      +'</div>';
  $('informesBody').innerHTML=
    '<div class="chips" id="informesChips">'+chipsHtml+'</div>'
    +customHtml
    +summaryHtml
    +chartHtml
    +prodBlock
    +catBlock
    +'<div class="cfg-block"><h4>Por método de pago</h4>'+(esGratis?blurWrap(metodoContenido):metodoContenido)+'</div>'
    +exportHtml;
}
// v27 — resumen de Informes en CSV. Mismo separador ';' + BOM UTF-8 que ya usan los exportadores
// de Contabilidad (para que Excel en español lo abra bien), pero es un borrador de trabajo del
// negocio, no un libro fiscal — no reemplaza los CSV de Contabilidad (Libro de Ventas/Compras/Z).
function exportarInformesCsv(){
  const d=informesData();
  const rows=[[cfg.bizName||'FrixPOS','RIF: '+(cfg.bizRif||'—')],
              ['RESUMEN DE INFORMES (borrador interno, no es documento fiscal)'],
              ['Período',fechaCorta(d.from)+' a '+fechaCorta(d.to)],
              [],
              ['RESUMEN'],
              ['Ventas',d.list.length],
              ['Unidades vendidas',d.unitsSold],
              ['Vendido (Bs)',d.ventasBs.toFixed(2)],
              ['Ganancia neta (Bs)',d.gananciaBs.toFixed(2)],
              ['Ganancia cobrada (Bs)',d.gananciaCobradaBs.toFixed(2)],
              ['Ganancia fiada (Bs)',d.gananciaFiadaBs.toFixed(2)],
              []];
  rows.push(['GANANCIA POR PRODUCTO']);
  rows.push(['Producto','Unidades','Ingreso (Bs)','Ganancia (Bs)','Sin costo congelado']);
  informesProductRows(d).slice().sort((a,b)=>b.gananciaBs-a.gananciaBs).forEach(r=>{
    rows.push([r.name,r.esPeso?formatPeso(r.qty):r.qty,r.ingresoBs.toFixed(2),r.gananciaBs.toFixed(2),r.sinCosto?'Sí':'No']);
  });
  rows.push([]);
  rows.push(['CATEGORÍAS MÁS VENDIDAS']);
  rows.push(['Categoría','Unidades','Ingreso (Bs)','Ganancia (Bs)']);
  informesCategoryRows(d).slice().sort((a,b)=>b.gananciaBs-a.gananciaBs).forEach(r=>{
    rows.push([r.name,r.qty,r.ingresoBs.toFixed(2),r.gananciaBs.toFixed(2)]);
  });
  rows.push([]);
  rows.push(['POR MÉTODO DE PAGO']);
  rows.push(['Método','Bs']);
  Object.entries(d.porMetodo).forEach(([mid,bs])=>{
    const m=payMethods.find(x=>x.id===mid);
    rows.push([m?m.label:(mid==='mixto'?'Pago mixto':mid),bs.toFixed(2)]);
  });
  if(d.sinCosto)rows.push([],['Nota','Hay ventas en este período de antes de que Informes guardara el costo por línea — su ganancia no entra en los totales de arriba.']);
  return descargarCsv('informes-'+fechaArchivo(d.from)+'-a-'+fechaArchivo(d.to)+'.csv',csvFromRows(rows));
}
$('informesBody').addEventListener('click',e=>{
  const chip=e.target.closest('#informesChips .chip');
  if(chip){ informesRange=chip.dataset.r; renderInformes(); return; }
  const prodChip=e.target.closest('#informesProdChips .chip');
  if(prodChip){ informesProdSort=prodChip.dataset.sort; renderInformes(); return; }
  const catChip=e.target.closest('#informesCatChips .chip');
  if(catChip){ informesCatSort=catChip.dataset.sort; renderInformes(); return; }
  if(e.target.closest('#informesProdMore')){ informesProdExpanded=!informesProdExpanded; renderInformes(); return; }
  if(e.target.closest('#informesCatMore')){ informesCatExpanded=!informesCatExpanded; renderInformes(); return; }
  if(e.target.closest('#infExportBtn')){
    const ok=exportarInformesCsv();
    const aviso=$('infExportAviso');
    if(aviso){
      aviso.textContent=ok?'Archivo descargado. Búscalo en las descargas de tu teléfono o computadora.':'No se pudo descargar aquí. Prueba desde el navegador del teléfono o de la computadora.';
      aviso.style.color=ok?'var(--green)':'var(--amber)';
    }
    return;
  }
});
$('informesBody').addEventListener('change',e=>{
  if(e.target.id==='infFrom'){ informesCustomFrom=e.target.value; renderInformes(); }
  if(e.target.id==='infTo'){ informesCustomTo=e.target.value; renderInformes(); }
});


// ===== Contabilidad (v18) =====
// Sección real (antes placeholder). Junta tres cosas que un negocio venezolano necesita y que
// hasta ahora estaban dispersas o no existían:
//   1. Control fiscal SENIAT (IVA Débito / Crédito / Diferencia a pagar) — se movió acá desde
//      Informes por pedido de Jonathan: Informes es para ventas, Contabilidad para impuestos y plata.
//   2. Ingresos y egresos del negocio — los egresos son NUEVOS: hasta v17 no había forma de
//      registrar lo que sale (compra de mercancía, alquiler, luz, sueldos, flete, impuestos).
//   3. Ganancia neta real = ventas − costo de mercancía − egresos operativos del período.
// Los egresos viven en memoria como todo lo demás del mockup (se pierden al recargar).
//
// Categorías de egreso pensadas para bodega/minimarket venezolano. Las marcadas deducible:true
// son las que normalmente se pueden imputar como gasto del negocio para el ISLR — es una guía
// práctica, NO asesoría fiscal (Jonathan debe confirmarlo con su contador). ISLR real: pendiente.
//
// v31 — ivaAplica: el IVA y el ISLR son dos preguntas DISTINTAS del mismo gasto (no toda categoría
// que es deducible para ISLR genera crédito fiscal de IVA — un sueldo nunca lleva IVA, así que
// pedirle RIF de "proveedor" y factura a una nómina no tiene sentido y solo generaba avisos falsos
// en el semáforo). 'gravado' = normalmente trae IVA discriminado si el proveedor factura formal.
// 'no_iva' = por ley nunca lleva IVA (trabajo humano, tributos). 'mixto' = depende de qué se
// compró (mercancía puede ser gravada o exenta, igual que en Inventario).
const EGRESO_CATS=[
  {id:'mercancia',label:'Compra de mercancía',deducible:true,ivaAplica:'mixto',nota:'Lo que le pagas a tus proveedores por lo que vas a revender'},
  {id:'alquiler',label:'Alquiler del local',deducible:true,ivaAplica:'gravado',nota:''},
  {id:'servicios',label:'Servicios (luz, agua, internet)',deducible:true,ivaAplica:'gravado',nota:''},
  {id:'sueldos',label:'Sueldos y personal',deducible:true,ivaAplica:'no_iva',nota:'Incluye lo que le pagas a quien te ayuda en el negocio. El trabajo no lleva IVA.'},
  {id:'flete',label:'Flete y transporte',deducible:true,ivaAplica:'gravado',nota:''},
  {id:'impuestos',label:'Impuestos y tasas',deducible:false,ivaAplica:'no_iva',nota:'IVA declarado, ISLR, patente municipal, timbres. Los tributos no llevan IVA.'},
  {id:'mantenimiento',label:'Mantenimiento y reparaciones',deducible:true,ivaAplica:'gravado',nota:''},
  {id:'insumos',label:'Insumos (bolsas, limpieza, papelería)',deducible:true,ivaAplica:'gravado',nota:''},
  {id:'otros',label:'Otros gastos',deducible:true,ivaAplica:'mixto',nota:''},
];
function egresoCatLabel(id){ const c=EGRESO_CATS.find(x=>x.id===id); return c?c.label:id; }
function egresoCatIvaAplica(id){ const c=EGRESO_CATS.find(x=>x.id===id); return c?c.ivaAplica:'mixto'; }
// ¿este egreso, por su categoría, puede tener factura con IVA discriminado? Solo 'no_iva' se
// excluye — 'gravado' y 'mixto' sí pueden traer RIF/factura/IVA (mixto no siempre, pero el dueño
// decide caso por caso al ver la compra, no lo puede decidir la categoría sola).
function egresoPuedeIva(id){ return egresoCatIvaAplica(id)!=='no_iva'; }
// Egresos registrados a mano. Se guardan en Bs (moneda interna de toda la app) + la moneda y el
// monto original en que se pagó, para poder mostrarlo tal cual se registró.
// v37 — sin egresos de muestra (ver nota junto a products/clients/orders más arriba).
let egresos=[];
let egresoSeq=1;

let contabRange='mes',contabCustomFrom=null,contabCustomTo=null,contabFormOpen=false;
// v33 — acordeón exclusivo de Contabilidad: solo una sección abierta a la vez (id de data-acc,
// o null si están todas cerradas). Se guarda acá y no en el DOM porque el HTML de Contabilidad
// se reconstruye entero en cada renderContabilidad() — sin este estado, cada render "olvidaría"
// cuál sección tenías abierta. Pedido de Jonathan: al guardar un egreso, la sección se cierra
// sola para ver toda la pantalla de nuevo (ver egresoSave y setupContabAccordion()).
let contabAccOpen=null;
// v30 — fecha elegida para "Reporte del día" (Contabilidad), yyyy-mm-dd. Por defecto hoy.
let reporteDiaFecha=hoyYyyymmdd();
// v5 — id del egreso que se está editando, o null si el formulario es para uno nuevo. Antes solo
// se podía borrar y volver a escribir todo: si te equivocabas en un dígito del IVA de una factura
// tenías que rehacer proveedor, RIF, número de factura, base y fecha desde cero.
let egresoEditId=null;
const CONTAB_RANGES=[
  {id:'hoy',label:'Hoy'},{id:'semana',label:'7 días'},{id:'mes',label:'30 días'},
  {id:'3m',label:'3 meses'},{id:'anio',label:'Año'},{id:'todo',label:'Todo'},{id:'custom',label:'Personalizado'},
];
// ===== v26_4 — Mes calendario para exportar (separado del filtro de arriba) =====
// El SENIAT declara el IVA por mes calendario (día 1 al último), nunca por rango libre ni por
// "30 días". Los chips de arriba (contabRange) siguen sirviendo para que Jonathan vea SU negocio
// como quiera (7 días, 3 meses, personalizado...), pero lo que se exporta para el contador usa
// su propio selector de mes, desacoplado del filtro del dashboard.
let contabMesCal=(()=>{const d=new Date();return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0');})();
function mesCalDates(yyyymm){
  const startOfDay=d=>{const x=new Date(d);x.setHours(0,0,0,0);return x};
  const endOfDay=d=>{const x=new Date(d);x.setHours(23,59,59,999);return x};
  if(!yyyymm)return contabRangeDates();
  const [y,m]=yyyymm.split('-').map(Number);
  return {from:startOfDay(new Date(y,m-1,1)),to:endOfDay(new Date(y,m,0))};
}
function mesCalLabel(yyyymm){
  const {from,to}=mesCalDates(yyyymm);
  return fechaCorta(from)+' al '+fechaCorta(to);
}
// v32 — un mes en curso no se puede exportar: todavía le pueden entrar ventas o egresos hasta que
// termine, así que el archivo saldría incompleto y el contador se llevaría un número que después
// cambia. Coincide con la regla que ya explica el bloque de arriba: el IVA se declara por mes
// CERRADO, no a mitad de camino.
function mesCalEsActual(yyyymm){
  const d=new Date();
  return yyyymm===(d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0'));
}
const MESES_ES=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
function mesCalListoDesde(yyyymm){
  const [y,m]=yyyymm.split('-').map(Number);
  const sig=new Date(y,m,1); // día 1 del mes siguiente
  return '1 de '+MESES_ES[sig.getMonth()]+' de '+sig.getFullYear();
}
function hoyYyyymmdd(){ const d=new Date(); return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0'); }
// v30 — límites de UN día calendario, para "Reporte del día".
function diaDates(yyyymmdd){
  const [y,m,d]=(yyyymmdd||'').split('-').map(Number);
  const from=new Date(y,m-1,d,0,0,0,0);
  const to=new Date(y,m-1,d,23,59,59,999);
  return {from,to};
}
function contabRangeDates(){
  const startOfDay=d=>{const x=new Date(d);x.setHours(0,0,0,0);return x};
  const endOfDay=d=>{const x=new Date(d);x.setHours(23,59,59,999);return x};
  const today0=startOfDay(new Date());
  let from=today0,to=endOfDay(new Date());
  if(contabRange==='semana'){ from=new Date(today0);from.setDate(from.getDate()-6); }
  else if(contabRange==='mes'){ from=new Date(today0);from.setDate(from.getDate()-29); }
  else if(contabRange==='3m'){ from=new Date(today0);from.setDate(from.getDate()-89); }
  else if(contabRange==='anio'){ from=new Date(today0);from.setDate(from.getDate()-364); }
  else if(contabRange==='todo'){ from=new Date(0); }
  else if(contabRange==='custom'){
    from=contabCustomFrom?startOfDay(new Date(contabCustomFrom+'T00:00:00')):new Date(0);
    to=contabCustomTo?endOfDay(new Date(contabCustomTo+'T00:00:00')):to;
  }
  return {from,to};
}
// Junta ventas (de orders) y egresos del período en una sola foto contable.
// rangeOverride (v26_4): si se pasa {from,to}, se usa eso en vez del filtro de chips — así el
// exportador puede pedir un mes calendario exacto sin tocar lo que el dashboard está mostrando.
function contabData(rangeOverride){
  const {from,to}=rangeOverride||contabRangeDates();
  const ventas=orders.filter(o=>o.date>=from&&o.date<=to);
  const egs=egresos.filter(e=>e.date>=from&&e.date<=to);
  let ingresosBs=0,costoMercanciaBs=0,ivaDebitoBs=0,ivaCreditoCostoBs=0,cobradoBs=0,fiadoBs=0,sinCosto=false;
  let devolucionesBs=0;
  // v1.21 — no solo SI hay ventas sin costo, sino CUÁLES productos — para poder decir "estos
  // 4 son los que te faltan por personalizar" en vez de una advertencia genérica. La causa ya
  // no es solo "pedido viejo sin costo congelado": ahora también son productos en modo Simple.
  const sinCostoNombres=new Set();
  ventas.forEach(o=>{
    ingresosBs+=o.totalBs;
    if(o.status==='fiado')fiadoBs+=o.totalBs; else cobradoBs+=o.totalBs;
    o.items.forEach(it=>{
      ivaDebitoBs+=(it.ivaDebitoBs||0)*it.qty;
      ivaCreditoCostoBs+=(it.ivaCreditoBs||0)*it.qty;
      if(it.costBs!=null)costoMercanciaBs+=it.costBs*it.qty; else { sinCosto=true; sinCostoNombres.add(it.name); }
    });
    // devoluciones (v19): lo devuelto NO es venta. Se resta del ingreso, del IVA que le debes al
    // SENIAT (no vendiste eso al final) y del costo de mercancía (esa mercancía volvió al estante).
    (o.devoluciones||[]).forEach(d=>{
      if(d.date<from||d.date>to)return;
      devolucionesBs+=d.montoBs;
      ingresosBs-=d.montoBs;
      ivaDebitoBs-=(d.ivaDebitoBs||0);
      costoMercanciaBs-=(d.costoBs||0);
      if(o.status==='fiado')fiadoBs-=d.montoBs; else cobradoBs-=d.montoBs;
    });
  });
  let egresosBs=0,egresosDeduciblesBs=0,ivaCreditoFacturasBs=0,baseComprasFacturadasBs=0;
  const porCatEgreso={};
  egs.forEach(e=>{
    egresosBs+=e.amountBs;
    if(e.deducible)egresosDeduciblesBs+=e.amountBs;
    // v26 — el IVA Crédito Fiscal REAL nace acá: de la factura de compra, no del costo de lo vendido.
    ivaCreditoFacturasBs+=(e.ivaFacturaBs||0);
    baseComprasFacturadasBs+=(e.baseFacturaBs||0);
    if(!porCatEgreso[e.cat])porCatEgreso[e.cat]={cat:e.cat,total:0,count:0};
    porCatEgreso[e.cat].total+=e.amountBs; porCatEgreso[e.cat].count++;
  });
  // Ingreso sin IVA: el IVA cobrado no es ingreso del negocio, es plata del SENIAT que va de paso.
  const ingresosSinIvaBs=ingresosBs-ivaDebitoBs;
  const gananciaBrutaBs=ingresosSinIvaBs-costoMercanciaBs;
  const gananciaNetaBs=gananciaBrutaBs-egresosBs;
  return {from,to,ventas,egs,ingresosBs,ingresosSinIvaBs,costoMercanciaBs,egresosBs,egresosDeduciblesBs,devolucionesBs,
          gananciaBrutaBs,gananciaNetaBs,ivaDebitoBs,ivaCreditoCostoBs,ivaCreditoFacturasBs,baseComprasFacturadasBs,
          diferenciaSeniatBs:ivaDebitoBs-ivaCreditoFacturasBs,
          cobradoBs,fiadoBs,porCatEgreso,sinCosto,sinCostoProductos:[...sinCostoNombres]};
}
// ===== v24 — ¿Dónde está la plata? (conciliación por medio de custodia) =====
// El "Resultado del período" dice cuánto GANASTE; esto dice DÓNDE está esa plata. Un negocio puede
// tener ganancia buena y aun así no tener con qué pagarle al proveedor, porque casi todo está en
// Zelle, en el banco o fiado en la calle. Sin este desglose la ganancia da una falsa tranquilidad.
function contabCustodia(){
  const {from,to}=contabRangeDates();
  const ventas=orders.filter(o=>o.date>=from&&o.date<=to);
  const efectivo=nuevoEfectivo();
  let efectivoBs=0,digitalBs=0;
  let aproximado=false;
  ventas.filter(o=>o.status==='pagado').forEach(o=>{
    if(o.paymentsBreakdown&&o.paymentsBreakdown.length){
      o.paymentsBreakdown.forEach(p=>{
        const bs=convertToBs(p.amount,p.currency);
        const cash=methodCashCurrency(p.method);
        if(cash){ efectivo[cash]+=(parseFloat(p.amount)||0); efectivoBs+=bs; }
        else digitalBs+=bs;
      });
      if(o.changeBs){ efectivo.bs-=o.changeBs; efectivoBs-=o.changeBs; }  // el vuelto sale de la gaveta
    }else{
      // Ventas viejas (datos de muestra o anteriores a los pagos mixtos) solo guardan el método y el
      // total en Bs — no se sabe cuántos dólares o pesos físicos entraron. Se clasifican como efectivo
      // o digital por su método, pero NO se suman al conteo por moneda: inventar ahí daría un arqueo
      // falso. Se marca el resultado como aproximado para avisarlo en pantalla.
      aproximado=true;
      if(methodCashCurrency(o.method))efectivoBs+=o.totalBs; else digitalBs+=o.totalBs;
    }
  });
  // egresos: los del cajón bajan el efectivo físico; los "de otro lado" salieron del banco o del bolsillo
  let egCajonBs=0,egExternoBs=0;
  egresos.filter(e=>e.date>=from&&e.date<=to).forEach(e=>{
    if(e.origen==='externo') egExternoBs+=e.amountBs;
    else { egCajonBs+=e.amountBs; if(efectivo[e.currency]!==undefined)efectivo[e.currency]-=e.amount; }
  });
  efectivoBs-=egCajonBs;
  const fiadoPendienteBs=ventas.filter(o=>o.status==='fiado').reduce((s,o)=>s+saldoOf(o),0);
  return {efectivo,efectivoBs,digitalBs,egCajonBs,egExternoBs,fiadoPendienteBs,aproximado,
          disponibleBs:efectivoBs+digitalBs-egExternoBs};
}
// ===== v24 — Semáforo de salud fiscal =====
// Revisa lo que un contador reclamaría antes de declarar. No calcula impuestos nuevos: solo mira
// los datos que ya existen y avisa lo que está incompleto, mientras todavía se puede arreglar.
function contabSemaforo(d){
  const items=[];
  if(!d.ventas.length){
    items.push({lvl:'amber',t:'Sin ventas en este período',s:'Cambia el filtro de fecha de arriba o registra ventas para que esto tenga algo que revisar.'});
    return items;
  }
  // 1) IVA por apartar
  if(d.diferenciaSeniatBs>0)
    items.push({lvl:'amber',t:'Tienes IVA por pagar: '+fmtBs(d.diferenciaSeniatBs),s:'Esa plata no es tuya. Apártala antes de gastarla — cuando toque declarar tiene que estar.'});
  else if(d.ivaDebitoBs>0||d.ivaCreditoFacturasBs>0)
    items.push({lvl:'ok',t:'IVA sin diferencia a pagar',s:'En este período el crédito fiscal te cubre el débito.'});
  // 1b) v26 — sin esto el IVA a pagar de arriba está sobreestimado: el crédito fiscal real nace de
  // las facturas de compra, no del costo de lo vendido. Es la pieza que sostiene todo el bloque SENIAT.
  if(d.ivaDebitoBs>0&&d.ivaCreditoFacturasBs<=0)
    items.push({lvl:'red',t:'No registraste facturas de compra con IVA este mes',s:'Tu IVA Crédito Fiscal real sale de las facturas de tus proveedores, no de lo que vendiste. Sin facturas cargadas, el sistema asume crédito en cero y la diferencia a pagar al SENIAT queda sobreestimada.'});
  // 2) egresos sin factura de proveedor — solo aplica a categorías que SÍ pueden traer IVA
  // discriminado (v31): pedirle factura a una nómina o a un pago de tasa municipal es un aviso
  // falso, esos gastos legítimamente no tienen ese papel.
  const sinFactura=d.egs.filter(e=>egresoPuedeIva(e.cat)&&!(e.factura&&String(e.factura).trim()));
  if(sinFactura.length)
    items.push({lvl:'amber',t:sinFactura.length+' egreso'+(sinFactura.length===1?'':'s')+' sin número de factura',s:'Sin la factura del proveedor el SENIAT no te acepta ese gasto. Anótale el número mientras la tengas en la mano.'});
  else if(d.egs.length)
    items.push({lvl:'ok',t:'Todos los egresos con su factura anotada',s:'Guarda igual el papel original en la carpeta del mes.'});
  // 3) egresos sin RIF del proveedor (hace falta para el Libro de Compras) — mismo criterio
  const sinRif=d.egs.filter(e=>egresoPuedeIva(e.cat)&&!(e.provRif&&String(e.provRif).trim()));
  if(sinRif.length)
    items.push({lvl:'amber',t:sinRif.length+' egreso'+(sinRif.length===1?'':'s')+' sin RIF del proveedor',s:'El Libro de Compras se arma con el RIF de quien te vendió. Sin eso, tu contador tiene que devolverte la lista.'});
  // 4) ventas fuera de turno (sí entran al Reporte del día y al Libro de Ventas, pero no al
  // arqueo de ningún turno — la gaveta no las puede cuadrar si no sabe que existieron)
  const sinTurno=d.ventas.filter(o=>!o.turnoId).length;
  if(sinTurno)
    items.push({lvl:'amber',t:sinTurno+' venta'+(sinTurno===1?'':'s')+' sin turno de caja',s:'No entran al arqueo de ningún turno (el Reporte del día y el Libro de Ventas sí las cuentan). Abre el turno en Cajón antes de vender para que la gaveta cuadre.'});
  else
    items.push({lvl:'ok',t:'Todas las ventas dentro de un turno',s:'Cada una queda amarrada a su arqueo.'});
  // 5) ventas sin costo congelado
  if(d.sinCosto)
    items.push({lvl:'red',t:'Hay ventas sin costo congelado',s:'Su costo no entra en el cálculo, así que la ganancia de arriba se ve más alta de lo que es.'});
  // 6) ningún egreso registrado
  if(!d.egs.length)
    items.push({lvl:'red',t:'No registraste ningún egreso',s:'Sin alquiler, luz ni sueldos cargados, la ganancia neta de arriba está inflada y el ISLR estimado también.'});
  // 7) v25 — ventas fiadas a un cliente sin cédula/RIF: quedan en el resumen diario del Art. 77
  const clienteSinDoc=d.ventas.filter(o=>o.clientId&&!ventaConDocumento(o)).length;
  if(clienteSinDoc)
    items.push({lvl:'amber',t:clienteSinDoc+' venta'+(clienteSinDoc===1?'':'s')+' con cliente sin cédula ni RIF',s:'Van al resumen diario de consumidor final. Si ese cliente necesita factura a su nombre, cárgale el documento en Clientes.'});
  // 8) patente sin configurar
  if(!(cfg.patentePct>0))
    items.push({lvl:'amber',t:'Patente municipal sin configurar',s:'Pídele el porcentaje a tu alcaldía y cárgalo en Configuración → Precios y tasas para que FrixPOS te lo estime.'});
  return items;
}
function semaforoRow(it){
  return '<div class="sem-row"><span class="sem-dot '+it.lvl+'"></span><div><div class="sem-t">'+it.t+'</div>'
    +(it.s?'<div class="sem-s">'+it.s+'</div>':'')+'</div></div>';
}
// ===== v24 — Exportación para el contador (CSV) =====
// Formato de trabajo, NO la planilla oficial del SENIAT: sirve para que el contador arme los libros
// sin tipear factura por factura. Separador ';' y BOM para que Excel en español lo abra derecho.
function csvEscape(v){ return '"'+String(v==null?'':v).replace(/"/g,'""')+'"'; }
function csvFromRows(rows){ return rows.map(r=>r.map(csvEscape).join(';')).join('\r\n'); }
function descargarCsv(nombre,contenido){
  try{
    const blob=new Blob(['\ufeff'+contenido],{type:'text/csv;charset=utf-8;'});
    const url=URL.createObjectURL(blob);
    const a=document.createElement('a');
    a.href=url; a.download=nombre;
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    setTimeout(()=>{URL.revokeObjectURL(url)},1500);
    return true;
  }catch(err){ return false; }
}
function fechaCorta(d){ const p=n=>String(n).padStart(2,'0'); return p(d.getDate())+'/'+p(d.getMonth()+1)+'/'+d.getFullYear(); }
function fechaArchivo(d){ const p=n=>String(n).padStart(2,'0'); return d.getFullYear()+'-'+p(d.getMonth()+1)+'-'+p(d.getDate()); }
// desglose fiscal de un conjunto de ventas (mismo criterio del Reporte Z del Cajón)
function zDeVentas(ventas){
  let gravadoBs=0,exentoBs=0,ivaBs=0,devueltoBs=0;
  ventas.forEach(o=>{
    o.items.forEach(it=>{
      const lineaBs=it.priceBs*it.qty, ivaLinea=(it.ivaDebitoBs||0)*it.qty;
      if(ivaLinea>0){ gravadoBs+=(lineaBs-ivaLinea); ivaBs+=ivaLinea; }
      else exentoBs+=lineaBs;
    });
    (o.devoluciones||[]).forEach(dv=>{
      devueltoBs+=dv.montoBs; ivaBs-=(dv.ivaDebitoBs||0); gravadoBs-=(dv.montoBs-(dv.ivaDebitoBs||0));
    });
  });
  return {gravadoBs,exentoBs,ivaBs,devueltoBs,totalBs:gravadoBs+exentoBs+ivaBs};
}
// ===== v25 — correlativo de la venta =====
// El refCode es 'PT-AAMMDD-0100': la parte final es orderSeq, un contador que solo sube. NO es un
// hash aleatorio, así que sirve como correlativo de auditoría interna para el "comprobante desde /
// hasta" que pide el Art. 77 del RLIVA. No es numeración fiscal: eso solo existe con máquina fiscal
// homologada o talonario de imprenta autorizada.
function correlativoDe(o){
  if(o.refCode){
    const p=String(o.refCode).split('-');
    const n=p[p.length-1];
    if(/^\d+$/.test(n))return parseInt(n,10);
  }
  const m=String(o.id||'').match(/(\d+)/);   // ventas de muestra: 'o1', 'o2'...
  return m?parseInt(m[1],10):null;
}
function correlativoFmt(o){ const n=correlativoDe(o); return n==null?'':String(n).padStart(6,'0'); }
// ===== v25 — ¿Art. 76 o Art. 77? =====
// Art. 76 = operaciones documentadas con factura a nombre de alguien identificado (renglón por
// renglón). Art. 77 = ventas a no contribuyentes / consumidor final (resumen POR DÍA, con el primer
// y el último comprobante del día). Art. 78 obliga a llevarlas SEPARADAS, por eso son dos archivos.
// Criterio práctico de FrixPOS: si la venta tiene cliente con cédula o RIF cargado, va al libro de
// facturas; si no, al resumen diario. El contador decide si un V- es contribuyente o no.
function ventaConDocumento(o){
  if(!o.clientId)return false;
  const c=clients.find(x=>x.id===o.clientId);
  return !!(c&&c.idNumber&&String(c.idNumber).trim());
}
function alicuotaTxt(z){ return z.ivaBs>0?((cfg.ivaGeneral||0)+'%'):'0% (exento)'; }
// ===== v30 — REPORTE DEL DÍA =====
// Antes esto vivía adentro de "Cerrar turno" en el Cajón y se llamaba "Reporte Z" — un nombre
// que no le corresponde (Reporte Z es el que saca una máquina fiscal homologada al cerrar, y
// FrixPOS no es eso). Además quedaba atado al turno abierto: una bodega que abre y cierra caja
// varias veces al día, o que cruza la medianoche con el turno abierto, terminaba con el resumen
// fiscal partido en pedazos que no correspondían a un día calendario. Pedido de Jonathan: que
// sea por DÍA, como Informes, sin importar cuántos turnos hubo ese día.
//
// A diferencia del arqueo del Cajón (que solo cuenta ventas 'pagado', porque solo esas mueven
// efectivo), el Reporte del día cuenta TODAS las ventas emitidas ese día — pagadas y fiadas —
// porque el comprobante y el IVA Débito nacen al vender, no al cobrar. Es la misma diferencia
// que ya existe entre Contabilidad (todo) y Cajón (solo lo que tocó la gaveta).
function ventasDelDia(yyyymmdd){
  const {from,to}=diaDates(yyyymmdd);
  return orders.filter(o=>o.date>=from&&o.date<=to).sort((a,b)=>a.date-b.date);
}
// "Reporte del día N°": cuántos días calendario con al menos una venta hay, desde el primero
// hasta el elegido (inclusive) — no es un contador que se pueda tocar a mano ni resetear: sale
// solo de contar las ventas que ya existen, así que no se puede "hacer trampa" adelantándolo.
function numeroReporteDia(yyyymmdd){
  const dias=new Set();
  orders.forEach(o=>{ const d=o.date; dias.add(d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')); });
  return [...dias].filter(d=>d<=yyyymmdd).length;
}
function reporteDelDia(yyyymmdd){
  const ventas=ventasDelDia(yyyymmdd);
  const z=zDeVentas(ventas);
  const conDoc=ventas.filter(o=>o.refCode||correlativoDe(o)!=null);
  const primerDoc=conDoc.length?conDoc[0]:null;
  const ultimoDoc=conDoc.length?conDoc[conDoc.length-1]:null;
  // acumulado en memoria fiscal: TODAS las ventas de siempre, sin filtrar por fecha ni estado —
  // como el contador de una caja registradora, solo sube. Las devoluciones no lo bajan: una
  // máquina fiscal tampoco "desvende" lo ya emitido, registra la nota de crédito aparte.
  const acumuladoBs=orders.reduce((s,o)=>s+(o.totalBs||0),0);
  return {ventas,z,primerDoc,ultimoDoc,numero:numeroReporteDia(yyyymmdd),acumuladoBs};
}
function reporteDelDiaHTML(esGratis){
  const bloqueado=esGratis&&reporteDiaFecha!==hoyYyyymmdd();
  const r=reporteDelDia(bloqueado?hoyYyyymmdd():reporteDiaFecha);
  const linea=(nombre,sub,bs,cls)=>'<div class="order-row"><div><div class="or-top">'+nombre+'</div>'
    +(sub?'<div class="or-sub">'+sub+'</div>':'')+'</div><div'+(cls?' style="color:'+cls+'"':'')+'>'+fmtBs(bs)+'</div></div>';
  return '<details class="cfg-block cfg-acc" data-acc="reporteDia"'+(contabAccOpen==='reporteDia'?' open':'')+'><summary><h4 style="display:inline">Reporte del día</h4><span class="acc-chev">›</span></summary>'
    +'<div class="desc">El resumen fiscal de un día calendario completo — junta todos los turnos de ese día. Es tu propio resumen, <b>no reemplaza una máquina fiscal homologada</b> si el SENIAT te obliga a tener una.</div>'
    +'<div class="cfg-row"><label>Día a revisar</label><input class="cfg-input" type="date" id="reporteDiaInput" value="'+reporteDiaFecha+'" max="'+hoyYyyymmdd()+'"'+(esGratis?' disabled':'')+'></div>'
    +(bloqueado?'<div class="desc" style="color:var(--amber);margin-top:-6px">Con tu código activo puedes revisar cualquier día anterior. Por ahora ves el de hoy.</div>':'')
    +'<div class="order-row"><div><div class="or-top">'+(cfg.bizName||'—')+'</div><div class="or-sub">RIF: '+(cfg.bizRif||'—')+'</div></div><div style="text-align:right">Reporte del día N° '+String(r.numero).padStart(4,'0')+'<div class="or-sub">'+fechaCorta(diaDates(bloqueado?hoyYyyymmdd():reporteDiaFecha).from)+'</div></div></div>'
    +(r.primerDoc?'<div class="order-row"><div><div class="or-top">Documentos emitidos</div><div class="or-sub">'+r.ventas.length+' comprobante'+(r.ventas.length===1?'':'s')+'</div></div><div>'+correlativoFmt(r.primerDoc)+' a '+correlativoFmt(r.ultimoDoc)+'</div></div>':'')
    +linea('Ventas exentas','Productos sin IVA',r.z.exentoBs)
    +linea('Base imponible gravada','Productos con IVA, sin contar el IVA',r.z.gravadoBs)
    +linea('Débito fiscal IVA ('+(cfg.ivaGeneral||0)+'%)','Lo que le estás guardando al SENIAT',r.z.ivaBs)
    +(r.z.devueltoBs>0?linea('Notas de crédito / devoluciones','Ya descontadas de los totales de arriba',r.z.devueltoBs,'var(--amber)'):'')
    +'<div class="order-row" style="border-top:1px solid var(--border);margin-top:4px;padding-top:8px"><div><b>Venta total del día</b></div><div><b>'+fmtBs(r.z.totalBs)+'</b></div></div>'
    +'<div class="order-row"><div><div class="or-top">Acumulado en memoria fiscal</div><div class="or-sub">Total vendido desde que existe este negocio en FrixPOS, nunca se borra</div></div><div>'+fmtBs(r.acumuladoBs)+'</div></div>'
    +'<div class="desc" style="margin-top:10px">Guarda o fotografía este resumen cada día, junto con los boucher y capturas de Pago Móvil. Es lo que le entregas a tu contador — el mes completo lo puedes descargar en "Exportar para el contador", más abajo.</div>'
    +'</details>';
}
function exportarLibroVentas(){
  const d=contabData(mesCalDates(contabMesCal));
  const conDoc=d.ventas.filter(ventaConDocumento).sort((a,b)=>a.date-b.date);
  const rows=[[cfg.bizName||'FrixPOS','RIF: '+(cfg.bizRif||'—')],
              ['LIBRO DE VENTAS — OPERACIONES CON FACTURA (Art. 76 RLIVA)'],
              ['Período',fechaCorta(d.from)+' a '+fechaCorta(d.to)],
              [],
              ['Nro_Operacion','Fecha','Nro_Comprobante','Referencia','RIF_Cedula','Nombre_Razon_Social',
               'Condicion','Ventas_Exentas_Bs','Base_Imponible_Bs','Alicuota','IVA_Debito_Bs',
               'Devoluciones_NC_Bs','Total_Venta_Con_IVA_Bs']];
  let n=0;
  conDoc.forEach(o=>{
    const z=zDeVentas([o]);
    const c=clients.find(x=>x.id===o.clientId);
    n++;
    rows.push([n,fechaCorta(o.date),correlativoFmt(o),o.refCode||o.id,
      (c&&c.idNumber)?c.idNumber:'',c?c.name:'',
      o.status==='fiado'?'Fiado':'Cobrado',
      z.exentoBs.toFixed(2),z.gravadoBs.toFixed(2),alicuotaTxt(z),z.ivaBs.toFixed(2),
      z.devueltoBs.toFixed(2),z.totalBs.toFixed(2)]);
  });
  const tot=zDeVentas(conDoc);
  rows.push([],['TOTALES','','','','','','',tot.exentoBs.toFixed(2),tot.gravadoBs.toFixed(2),'',
               tot.ivaBs.toFixed(2),tot.devueltoBs.toFixed(2),tot.totalBs.toFixed(2)]);
  rows.push([],['Nota','Solo las ventas con cliente identificado (cédula o RIF cargado). Las ventas a consumidor final van en el archivo aparte del Art. 77, como exige el Art. 78 del Reglamento.']);
  rows.push(['Nota','Nro_Comprobante es el correlativo interno de FrixPOS, para auditoría. No es numeración fiscal: eso requiere máquina fiscal homologada o talonario de imprenta autorizada.']);
  rows.push(['Nota','Borrador de trabajo para el contador. No sustituye el Libro de Ventas en el formato que exige el SENIAT.']);
  return descargarCsv('libro-ventas-facturas-'+fechaArchivo(d.from)+'-a-'+fechaArchivo(d.to)+'.csv',csvFromRows(rows));
}
// ===== v25 — Art. 77: resumen POR DÍA de las ventas a consumidor final =====
// El Art. 77 no quiere un renglón por ticket de 1 dólar: quiere, por cada día, el número del primer
// y del último comprobante emitido, el total gravado, el IVA y el total exento/exonerado/no sujeto.
// Su Parágrafo Segundo dice que el reporte global diario de una máquina fiscal se registra igual.
// Así un mes pasa de 1.000 filas a unas 30.
function exportarResumenDiario(){
  const d=contabData(mesCalDates(contabMesCal));
  const sinDoc=d.ventas.filter(o=>!ventaConDocumento(o));
  const porDia={};
  sinDoc.forEach(o=>{
    const k=fechaArchivo(o.date);
    (porDia[k]=porDia[k]||[]).push(o);
  });
  const rows=[[cfg.bizName||'FrixPOS','RIF: '+(cfg.bizRif||'—')],
              ['LIBRO DE VENTAS — RESUMEN DIARIO A CONSUMIDOR FINAL (Art. 77 RLIVA)'],
              ['Período',fechaCorta(d.from)+' a '+fechaCorta(d.to)],
              [],
              ['Nro_Operacion','Fecha','Comprobante_Desde','Comprobante_Hasta','Cantidad_Comprobantes',
               'Ventas_Exentas_Bs','Base_Imponible_Bs','Alicuota','IVA_Debito_Bs','Devoluciones_NC_Bs','Total_Ventas_Bs']];
  let n=0;
  Object.keys(porDia).sort().forEach(k=>{
    const vs=porDia[k].slice().sort((a,b)=>(correlativoDe(a)||0)-(correlativoDe(b)||0));
    const z=zDeVentas(vs);
    const nums=vs.map(correlativoDe).filter(x=>x!=null);
    n++;
    rows.push([n,fechaCorta(vs[0].date),
      nums.length?String(Math.min.apply(null,nums)).padStart(6,'0'):'',
      nums.length?String(Math.max.apply(null,nums)).padStart(6,'0'):'',
      vs.length,
      z.exentoBs.toFixed(2),z.gravadoBs.toFixed(2),alicuotaTxt(z),z.ivaBs.toFixed(2),
      z.devueltoBs.toFixed(2),z.totalBs.toFixed(2)]);
  });
  const tot=zDeVentas(sinDoc);
  rows.push([],['TOTALES','','','',sinDoc.length,tot.exentoBs.toFixed(2),tot.gravadoBs.toFixed(2),'',
               tot.ivaBs.toFixed(2),tot.devueltoBs.toFixed(2),tot.totalBs.toFixed(2)]);
  rows.push([],['Nota','Un renglón por día, como exige el Art. 77 del Reglamento de la Ley de IVA para las operaciones con no contribuyentes.']);
  rows.push(['Nota','Comprobante_Desde y Comprobante_Hasta son el correlativo interno de FrixPOS, útil como referencia de auditoría. No es numeración fiscal ni sustituye el número de registro de una máquina fiscal.']);
  rows.push(['Nota','Si un día tiene ventas gravadas y exentas mezcladas, la columna Alicuota indica la tasa de las gravadas; las exentas van en su propia columna.']);
  return descargarCsv('libro-ventas-consumidor-final-'+fechaArchivo(d.from)+'-a-'+fechaArchivo(d.to)+'.csv',csvFromRows(rows));
}
function exportarLibroCompras(){
  const d=contabData(mesCalDates(contabMesCal));
  const rows=[[cfg.bizName||'FrixPOS','RIF: '+(cfg.bizRif||'—')],
              ['LIBRO DE COMPRAS Y EGRESOS (borrador de trabajo)'],
              ['Período',fechaCorta(d.from)+' a '+fechaCorta(d.to)],
              [],
              ['Nro_Operacion','Fecha','RIF_Proveedor','Nombre_Proveedor','Nro_Factura','Categoria',
               'Descripcion','Con_Factura','Base_Imponible_Bs','IVA_Credito_Bs','Deducible','Origen_Del_Dinero',
               'Compra_Con_Factura_Bs','Gasto_No_Fiscal_Bs','Monto_Original','Moneda','Total_Bs']];
  let n=0,conFacturaBs=0,sinFacturaBs=0,baseTotalBs=0,ivaCreditoTotalBs=0;
  d.egs.slice().sort((a,b)=>a.date-b.date).forEach(e=>{
    const tiene=!!(e.factura&&String(e.factura).trim());
    if(tiene)conFacturaBs+=(e.amountBs||0); else sinFacturaBs+=(e.amountBs||0);
    baseTotalBs+=(e.baseFacturaBs||0); ivaCreditoTotalBs+=(e.ivaFacturaBs||0);
    n++;
    rows.push([n,fechaCorta(e.date),e.provRif||'',e.proveedor||'',e.factura||'',
      egresoCatLabel(e.cat),e.desc||'',tiene?'Sí':'No',
      (e.baseFacturaBs||0).toFixed(2),(e.ivaFacturaBs||0).toFixed(2),
      e.deducible?'Sí':'No',
      e.origen==='externo'?'Banco / otro':'Cajón',
      tiene?(e.amountBs||0).toFixed(2):'0.00',
      tiene?'0.00':(e.amountBs||0).toFixed(2),
      (e.amount||0).toFixed(2),(e.currency||'bs').toUpperCase(),(e.amountBs||0).toFixed(2)]);
  });
  rows.push([],['TOTALES','','','','','','','',baseTotalBs.toFixed(2),ivaCreditoTotalBs.toFixed(2),'','',conFacturaBs.toFixed(2),sinFacturaBs.toFixed(2),'','',d.egresosBs.toFixed(2)]);
  rows.push(['TOTAL DEDUCIBLES','','','','','','','','','','','','','','','',d.egresosDeduciblesBs.toFixed(2)]);
  rows.push([],['Nota','Los gastos sin factura van a la columna Gasto_No_Fiscal: no generan crédito fiscal, aunque sí sean costo del negocio.']);
  rows.push(['Nota','Base_Imponible_Bs e IVA_Credito_Bs son los que el dueño escribió a mano leyendo su factura de compra (Contabilidad → Egresos). Quedan en 0.00 si la factura no discriminaba IVA aparte o si esa compra era exenta. El IVA Crédito Fiscal de Contabilidad sale de sumar esta columna, no del costo de los productos vendidos.']);
  return descargarCsv('libro-compras-'+fechaArchivo(d.from)+'-a-'+fechaArchivo(d.to)+'.csv',csvFromRows(rows));
}
// v32 — el export del contador seguía agrupado por TURNO (Resumen de Turnos del mes), pero el
// Reporte del día (Contabilidad) ya es por día calendario. Este es el mismo Reporte del día,
// uno por fila, para el mes completo — así lo que ve el dueño en pantalla y lo que descarga
// para el contador son exactamente el mismo criterio. Se salta los días sin ventas.
function exportarReportesDelDia(){
  const {from,to}=mesCalDates(contabMesCal);
  const rows=[['REPORTES DEL DÍA DEL MES (uno por día calendario con ventas)'],
              [cfg.bizName||'FrixPOS','RIF: '+(cfg.bizRif||'—')],
              ['Período',fechaCorta(from)+' a '+fechaCorta(to)],
              [],
              ['Día','N° Reporte','Documentos','Desde','Hasta','Ventas exentas (Bs)','Base gravada (Bs)','IVA débito (Bs)','Devuelto (Bs)','Total del día (Bs)']];
  let totExento=0,totGravado=0,totIva=0,totDevuelto=0,totDia=0,totDocs=0;
  const cursor=new Date(from);
  while(cursor<=to){
    const dstr=cursor.getFullYear()+'-'+String(cursor.getMonth()+1).padStart(2,'0')+'-'+String(cursor.getDate()).padStart(2,'0');
    const r=reporteDelDia(dstr);
    if(r.ventas.length){
      rows.push([fechaCorta(diaDates(dstr).from),r.numero,r.ventas.length,
        r.primerDoc?correlativoFmt(r.primerDoc):'',r.ultimoDoc?correlativoFmt(r.ultimoDoc):'',
        r.z.exentoBs.toFixed(2),r.z.gravadoBs.toFixed(2),r.z.ivaBs.toFixed(2),r.z.devueltoBs.toFixed(2),r.z.totalBs.toFixed(2)]);
      totExento+=r.z.exentoBs; totGravado+=r.z.gravadoBs; totIva+=r.z.ivaBs; totDevuelto+=r.z.devueltoBs; totDia+=r.z.totalBs; totDocs+=r.ventas.length;
    }
    cursor.setDate(cursor.getDate()+1);
  }
  rows.push([],['TOTALES','',totDocs,'','',totExento.toFixed(2),totGravado.toFixed(2),totIva.toFixed(2),totDevuelto.toFixed(2),totDia.toFixed(2)]);
  rows.push([],['Nota','Cuenta TODAS las ventas emitidas ese día — pagadas y fiadas — porque el IVA nace al vender, no al cobrar. No es lo mismo que "Resumen de Turnos del mes", que solo cuenta lo pagado en caja.']);
  return descargarCsv('reportes-del-dia-'+fechaArchivo(from)+'-a-'+fechaArchivo(to)+'.csv',csvFromRows(rows));
}
function exportarCierresZ(){
  const d=contabData(mesCalDates(contabMesCal));
  const rows=[['RESUMEN DE TURNOS DEL MES (arqueo de caja, no es el Reporte del día)'],
              [cfg.bizName||'FrixPOS','RIF: '+(cfg.bizRif||'—')],
              ['Período',fechaCorta(d.from)+' a '+fechaCorta(d.to)],
              [],
              ['Turno','Apertura','Cierre','Ventas','Gravado (Bs)','IVA débito (Bs)','Exento (Bs)','Devuelto (Bs)','Total (Bs)']];
  const porTurno={};
  d.ventas.forEach(o=>{ const k=o.turnoId||'sin-turno'; (porTurno[k]=porTurno[k]||[]).push(o); });
  Object.keys(porTurno).forEach(k=>{
    const vs=porTurno[k], z=zDeVentas(vs);
    const t=turnosCerrados.find(x=>x.id===k)||(openTurno&&openTurno.id===k?openTurno:null);
    rows.push([k==='sin-turno'?'(ventas sin turno)':k,
      t&&t.inicio?fechaCorta(t.inicio):'', t&&t.fin?fechaCorta(t.fin):'(turno abierto)',
      vs.length,z.gravadoBs.toFixed(2),z.ivaBs.toFixed(2),z.exentoBs.toFixed(2),z.devueltoBs.toFixed(2),z.totalBs.toFixed(2)]);
  });
  const tot=zDeVentas(d.ventas);
  rows.push([],['TOTALES','','',d.ventas.length,tot.gravadoBs.toFixed(2),tot.ivaBs.toFixed(2),tot.exentoBs.toFixed(2),tot.devueltoBs.toFixed(2),tot.totalBs.toFixed(2)]);
  return descargarCsv('resumen-turnos-'+fechaArchivo(d.from)+'-a-'+fechaArchivo(d.to)+'.csv',csvFromRows(rows));
}
function contabRowHtml(nombre,sub,montoBs,cls){
  const otros=otherCurrenciesLine(montoBs);
  return '<div class="cc-row"><div class="cc-mid"><div class="cc-name">'+nombre+'</div>'+(sub?'<div class="cc-sub">'+sub+'</div>':'')+'</div>'
    +'<div class="cc-saldo'+(cls?' '+cls:'')+'"><b>'+fmtBs(montoBs)+'</b>'+(otros?'<div style="font-weight:600">'+otros+'</div>':'')+'</div></div>';
}
// v5 — vuelca un egreso existente en el formulario. Va aparte de renderContabilidad() porque
// tiene que correr DESPUÉS de que el HTML del formulario exista en el DOM.
function llenarFormEgreso(){
  const e=egresos.find(x=>x.id===egresoEditId); if(!e)return;
  const set=(id,v)=>{ const el=$(id); if(el)el.value=(v===undefined||v===null)?'':v; };
  set('egresoAmount',e.amount);
  set('egresoDesc',e.desc);
  set('egresoProv',e.proveedor);
  set('egresoProvRif',e.provRif);
  set('egresoFactura',e.factura);
  set('egresoBaseFactura',e.baseFacturaBs||'');
  set('egresoIvaFactura',e.ivaFacturaBs||'');
  const d=(e.date instanceof Date)?e.date:new Date(e.date);
  if(!isNaN(d.getTime()))set('egresoDate',d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0'));
  const cat=$('egresoCat'); if(cat)cat.value=e.cat;
  document.querySelectorAll('#egresoCur button').forEach(b=>b.classList.toggle('on',b.dataset.v===e.currency));
  document.querySelectorAll('#egresoOrigen button').forEach(b=>b.classList.toggle('on',b.dataset.v===e.origen));
  if(typeof updateEgresoNota==='function')updateEgresoNota();
  if(typeof updateEgresoOrigenNota==='function')updateEgresoOrigenNota();
}
function renderContabilidad(){
  // v1.9 — Contabilidad es de pago; gratis solo ve HOY (se recalcula con lo que ya vendiste
  // desde que abriste el turno, sin rangos históricos). Se fuerza acá, no solo en el click del
  // chip, para que tampoco quede un rango viejo pegado si canceló su código entre visitas.
  const esGratis=!esPremium();
  if(esGratis) contabRange='hoy';
  const d=contabData();
  const chipsHtml=CONTAB_RANGES.map(r=>{
    const bloqueado=esGratis&&r.id!=='hoy';
    return '<button class="chip'+(contabRange===r.id?' on':'')+'"'
      +(bloqueado?' disabled style="opacity:.4;cursor:not-allowed" title="Activa tu código para ver este rango"':'')
      +' data-r="'+r.id+'">'+r.label+'</button>';
  }).join('');
  const trialAviso=trialAvisoHTML();
  const bloqueoAvisoHtml=esGratis?premiumAvisoHTML('Estás viendo solo el día de hoy',[
      'Cualquier período: 7 días, 30 días, el año o el rango que quieras',
      'Tu control de IVA ante el SENIAT, mes por mes',
      'Los archivos que le entregas a tu contador (Libro de Ventas y de Compras)',
      'Estimación de patente municipal e ISLR'
    ])+'<div style="height:12px"></div>':(trialAviso?trialAviso+'<div style="height:12px"></div>':'');
  const customHtml=contabRange==='custom'
    ?'<div class="cfg-row" style="gap:8px"><input class="cfg-input" type="date" id="contabFrom" value="'+(contabCustomFrom||'')+'"><input class="cfg-input" type="date" id="contabTo" value="'+(contabCustomTo||'')+'"></div>'
    :'';

  // 1) Resultado del período — la pregunta de fondo: ¿cuánto me quedó de verdad?
  const resultadoHtml='<details class="cfg-block cfg-acc" data-acc="resultado"'+(contabAccOpen==='resultado'?' open':'')+'><summary><h4 style="display:inline">Resultado del período</h4><span class="acc-chev">›</span></summary>'
    +'<div class="desc">Lo que entró, lo que salió y lo que de verdad te quedó.</div>'
    +contabRowHtml('Ingresos por ventas','Ya con las devoluciones descontadas · '+d.ventas.length+' venta'+(d.ventas.length===1?'':'s'),d.ingresosBs)
    +(d.devolucionesBs>0?contabRowHtml('Devoluciones del período','Mercancía que volvió y plata que salió',d.devolucionesBs):'')
    +contabRowHtml('Ingresos sin IVA','Lo que es tuyo de las ventas (el IVA es del SENIAT)',d.ingresosSinIvaBs)
    +'<details class="note note-row"><summary><span class="n-closed">¿Qué puedo hacer con esta plata? ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">'
      +'Esta es la plata con la que el negocio de verdad funciona. De aquí sale, en este orden: <b>reponer la mercancía</b> que vendiste (esto es lo primero, si no lo separas te quedas sin qué vender), <b>pagar los sueldos</b> de quien te ayuda, y <b>los gastos fijos</b> — luz, agua, alquiler, internet, gas, bolsas, flete. Lo que sobre después de todo eso es tu ganancia neta, la de la última línea de este bloque.<br><br>'
      +'Lo que <b>no</b> deberías tocar es el IVA que cobraste: esa plata nunca fue tuya, la estás guardando para el SENIAT. Si te la gastas, cuando toque declarar vas a tener que sacarla de tu bolsillo.'
    +'</div></details>'
    +contabRowHtml('− Costo de mercancía','Lo que te costó lo que vendiste',d.costoMercanciaBs,'')
    +contabRowHtml('= Ganancia bruta','Antes de tus gastos del negocio',d.gananciaBrutaBs,'ok')
    +contabRowHtml('− Egresos del período','Alquiler, luz, sueldos, compras, etc.',d.egresosBs,'')
    +contabRowHtml('= Ganancia neta','Lo que de verdad te queda',d.gananciaNetaBs,d.gananciaNetaBs>=0?'ok':'')
    +'<div class="desc" style="margin-top:10px">De tus ventas, <b>'+fmtBs(d.cobradoBs)+'</b> ya lo cobraste'+(d.fiadoBs?' y <b>'+fmtBs(d.fiadoBs)+'</b> sigue fiado (la ganancia de eso todavía no está en tu bolsillo).':'.')+'</div>'
    // v1.21 — ya no es solo "ventas viejas": ahora también son productos en modo Simple (sin
    // costo a propósito). Antes esto era un aviso genérico sin decir CUÁLES — ahora se puede,
    // porque contabData() ya junta los nombres, no solo el booleano.
    +(d.sinCosto?'<div class="desc" style="color:var(--amber);margin-top:8px">⚠ '+d.sinCostoProductos.length+' producto'+(d.sinCostoProductos.length===1?'':'s')+' vendido'+(d.sinCostoProductos.length===1?'':'s')+' sin costo registrado — tu ganancia real puede ser menor a la de arriba.</div>'
      +'<details class="note note-row"><summary><span class="n-closed">Ver cuáles ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">'
        +d.sinCostoProductos.join('<br>')
        +'<br><br>Casi siempre es porque están en modelo de precio <b>Simple</b> (Inventario → editar producto) — en cuanto sepas el costo real, pásalos a Personalizado y van a dejar de salir en esta lista.'
      +'</div></details>'
      :'')
    +'</details>';

  // 2) Control fiscal SENIAT — movido acá desde Informes (v18); v26 separa costeo de crédito real
  const difCls=d.diferenciaSeniatBs>0?'':'ok';
  const seniatHtml='<details class="cfg-block cfg-acc" data-acc="seniat"'+(contabAccOpen==='seniat'?' open':'')+'><summary><h4 style="display:inline">IVA — Control fiscal SENIAT</h4><span class="acc-chev">›</span></summary>'
    +'<div class="desc">El IVA Débito cuenta productos marcados "Sujeto a IVA" en Inventario, congelado al momento de cada venta. El IVA Crédito Fiscal cuenta las facturas de compra que registraste abajo, en Egresos.</div>'
    +'<details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">'
      +'El <b>IVA Débito Fiscal</b> es el IVA que le cobraste a tus clientes — esa plata no es tuya, la estás guardando para el SENIAT. El <b>IVA Crédito Fiscal</b> es el IVA que TÚ ya le pagaste a tus proveedores, y nace únicamente de las facturas de compra que cargas en Egresos — no del costo de lo que vendiste. La <b>diferencia</b> es lo que realmente le tienes que pagar al SENIAT. Si te da negativo, tienes un crédito a favor que puedes usar en el próximo período.<br><br>'
      +'<b>Por qué el número puede brincar de un mes a otro:</b> un mes que compras una paca grande de mercancía, tu crédito fiscal sube y puede darte a favor. El mes siguiente, si compras poco, el crédito baja y la diferencia a pagar sube. Es lo correcto fiscalmente — el crédito nace cuando compras, no cuando vendes — pero de un vistazo puede parecer un error. No lo es: revisa cuánto compraste con factura ese mes antes de asumir que algo está mal.<br><br>'
      +'En Venezuela la declaración de IVA es <b>mensual</b> para la mayoría de contribuyentes ordinarios — un mes calendario exacto, no "30 días" hacia atrás. Para ver este bloque con ese corte, usa "Personalizado" arriba con el 1 y el último día del mes. Si lo que quieres es descargar los archivos para tu contador, baja a "Exportar para el contador": ese bloque ya elige el mes calendario solo.'
    +'</div></details>'
    +contabRowHtml('IVA Débito Fiscal','Lo que le cobraste a tus clientes',d.ivaDebitoBs)
    +contabRowHtml('IVA Crédito Fiscal (facturas de compras)','Lo que tus proveedores te facturaron con IVA discriminado',d.ivaCreditoFacturasBs,'ok')
    +contabRowHtml(d.diferenciaSeniatBs>=0?'Diferencia a pagar al SENIAT':'Crédito a tu favor',
        d.diferenciaSeniatBs>=0?'Esto es lo que debes declarar y pagar':'Te queda a favor para el próximo período',
        Math.abs(d.diferenciaSeniatBs),difCls)
    +'<details class="note note-row"><summary><span class="n-closed">IVA estimado en costos (referencial) ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">'
      +contabRowHtml('IVA estimado en costos','Calculado del costo de lo que vendiste, no de facturas',d.ivaCreditoCostoBs)
      +'<div class="desc" style="margin-top:8px">Esto <b>no</b> es tu crédito fiscal real — es una referencia de costeo (lo mismo que ves en el preview de cada producto en Inventario). Sirve para comparar contra el de arriba: si están muy lejos uno del otro, probablemente te falten facturas de compra por registrar.</div>'
    +'</div></details>'
    +(d.ivaDebitoBs===0&&d.ivaCreditoFacturasBs===0?'<div class="desc" style="margin-top:10px">Todavía no has vendido productos sujetos a IVA en este período. Si vendes alguno gravado, márcalo en Inventario con "¿Sujeto a IVA? Sí".</div>':'')
    +'</details>';

  // 3) Egresos — registro manual
  const egresosOrdenados=d.egs.slice().sort((a,b)=>b.date-a.date);
  const egresosListHtml=egresosOrdenados.length
    ?egresosOrdenados.map(e=>{
        const amtFmt=fmtMontoMoneda(e.amount,e.currency);
        return '<div class="order-row"><div><div class="or-top">'+(e.desc||egresoCatLabel(e.cat))+'</div>'
          +'<div class="or-sub">'+egresoCatLabel(e.cat)+' · '+fmtDate(e.date)+(e.deducible?'':' · no deducible')
          +(e.provRif?' · '+e.provRif:'')+(e.factura?' · fact. '+e.factura:'')
          +(e.ivaFacturaBs>0?' · <span style="color:var(--green)">IVA crédito '+fmtBs(e.ivaFacturaBs)+'</span>':'')
          +(egresoPuedeIva(e.cat)&&!e.factura?' · <span style="color:var(--amber)">sin factura</span>':'')+'</div></div>'
          +'<div style="text-align:right"><div>'+amtFmt+'</div><div class="or-sub">'+fmtBs(e.amountBs)+'</div>'
          +'<button class="btn-secondary" data-edit-egreso="'+e.id+'" style="margin-top:4px;padding:3px 10px;font-size:.72rem">Editar</button>'
          +'<button class="btn-secondary" data-rm-egreso="'+e.id+'" style="margin-top:4px;margin-left:6px;padding:3px 10px;font-size:.72rem;color:var(--red)">Borrar</button></div></div>';
      }).join('')
    :'<div class="cart-empty" style="padding:14px 0"><b>Sin egresos</b>No has registrado gastos en este período.</div>';
  const catsResumen=Object.values(d.porCatEgreso).sort((a,b)=>b.total-a.total)
    .map(c=>'<div class="order-row"><div><div class="or-top">'+egresoCatLabel(c.cat)+'</div><div class="or-sub">'+c.count+' registro'+(c.count===1?'':'s')+'</div></div><div>'+fmtBs(c.total)+'</div></div>').join('');
  // v31 — categoría vigente del formulario AL RENDERIZAR (la del egreso si se está editando, si
  // no la primera del selector): decide si el bloque de factura/IVA nace visible u oculto. El
  // cambio en vivo (el usuario cambiando el selector a media edición) lo hace updateEgresoFacturaBlock().
  const egresoActualCat=egresoEditId?((egresos.find(x=>x.id===egresoEditId)||{}).cat||EGRESO_CATS[0].id):EGRESO_CATS[0].id;
  const facturaBlockVisible=egresoPuedeIva(egresoActualCat);
  const formHtml=contabFormOpen
    ?'<div class="cfg-block" style="margin-top:10px">'
      +'<h4>'+(egresoEditId?'Editar egreso':'Nuevo egreso')+'</h4>'
      +'<div class="cfg-row"><label>¿En qué gastaste?</label><select class="cfg-input" id="egresoCat">'
        +EGRESO_CATS.map(c=>'<option value="'+c.id+'">'+c.label+'</option>').join('')
      +'</select></div>'
      +'<div class="desc" id="egresoCatNota" style="margin:-4px 0 10px"></div>'
      +'<div class="cfg-row"><label>Descripción (opcional)</label><input class="cfg-input" id="egresoDesc" type="text" placeholder="Ej: Compra a distribuidora Polar"></div>'
      // v24 — datos del proveedor: sin RIF y número de factura el contador no puede armar el Libro de Compras
      +'<div class="cfg-row"><label>Proveedor (opcional)</label><input class="cfg-input" id="egresoProv" type="text" placeholder="Ej: Distribuidora Polar C.A."></div>'
      // v31 — RIF/factura/base/IVA solo tienen sentido para categorías que pueden traer IVA
      // discriminado. Un sueldo o un tributo municipal no tienen "proveedor con RIF" ni factura.
      +'<div id="egresoFacturaBlock" style="display:'+(facturaBlockVisible?'':'none')+'">'
        +'<div class="cfg-row"><label>RIF del proveedor</label><input class="cfg-input" id="egresoProvRif" type="text" placeholder="Ej: J-12345678-9"></div>'
        +'<div class="cfg-row"><label>N° de factura</label><input class="cfg-input" id="egresoFactura" type="text" placeholder="Ej: 00012345"></div>'
        +'<div class="desc" style="margin:-4px 0 10px">Anótalos ahora que tienes la factura en la mano — son las casillas que tu contador necesita para el Libro de Compras.</div>'
        +'<div class="cfg-row"><label>Base imponible de la factura (Bs)</label><input class="cfg-input" id="egresoBaseFactura" type="number" inputmode="decimal" placeholder="Ej: 4000"></div>'
        +'<div class="cfg-row"><label>IVA de la factura (Bs)</label><input class="cfg-input" id="egresoIvaFactura" type="number" inputmode="decimal" placeholder="Ej: 640"></div>'
        +'<div class="desc" style="margin:-4px 0 10px">Solo si la factura trae el IVA discriminado aparte. Si la compra es exenta, o el proveedor te la factura totalizada sin desglose, deja estos dos en 0 — de acá sale tu <b>IVA Crédito Fiscal real</b> ante el SENIAT, no del costo del producto.</div>'
      +'</div>'
      +'<div class="desc" id="egresoNoIvaNota" style="margin:-4px 0 10px;display:'+(facturaBlockVisible?'none':'')+'">Esta categoría no lleva IVA — no genera crédito fiscal, así que no te pido factura ni RIF de proveedor.</div>'
      +'<div class="cfg-row"><label>Monto</label><input class="cfg-input" id="egresoAmount" type="number" inputmode="decimal" placeholder="Ej: 120"></div>'
      +'<div class="cur-row"><div><div class="nm">¿En qué moneda pagaste?</div></div></div>'
      +'<div class="seg" id="egresoCur" style="margin:8px 0 10px"><button data-v="bs" class="on">Bs</button>'
        +'<button data-v="usd">$ BCV</button>'
        +monedasActivas().map(m=>'<button data-v="'+m.id+'">'+m.nombre+'</button>').join('')+'</div>'
      +'<div class="cur-row"><div><div class="nm">¿De dónde salió la plata?</div><div class="sub">Si la sacaste de la caja, el arqueo del Cajón lo descuenta solo</div></div></div>'
      +'<div class="seg" id="egresoOrigen" style="margin:8px 0 10px"><button data-v="cajon" class="on">Del cajón</button><button data-v="externo">De otro lado</button></div>'
      +'<div class="desc" id="egresoOrigenNota" style="margin:-4px 0 10px"></div>'
      +'<div class="cfg-row"><label>Fecha</label><input class="cfg-input" id="egresoDate" type="date" value="'+new Date().toISOString().slice(0,10)+'"></div>'
      +'<div class="desc" style="margin-top:10px;color:var(--amber)">Recuerda guardar la factura física de este gasto. Sin ella no cuenta ante el SENIAT.</div>'
      +'<div class="form-actions" style="margin-top:12px"><button class="btn-secondary" id="egresoCancel">Cancelar</button><button class="btn-primary" id="egresoSave">Guardar egreso</button></div>'
    +'</div>'
    :'';
  const egresosHtml='<details class="cfg-block cfg-acc" data-acc="egresos"'+(contabAccOpen==='egresos'?' open':'')+'><summary><h4 style="display:inline">Egresos del negocio</h4><span class="acc-chev">›</span></summary>'
    +'<div class="desc">Todo lo que sale: compras a proveedores, alquiler, luz, sueldos, flete, impuestos. <b>Guarda siempre la factura física</b> — sin el papel, el SENIAT no te acepta el gasto.</div>'
    +'<details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">'
      +'Registrar tus egresos es lo que hace que la "ganancia neta" de arriba sea de verdad. Sin esto, el sistema solo sabe cuánto vendiste y cuánto te costó la mercancía — pero no sabe que pagaste alquiler ni luz, y te haría creer que ganaste más de lo que ganaste.<br><br>'
      +'Los egresos marcados como <b>deducibles</b> son los que normalmente se pueden imputar como gasto del negocio al declarar ISLR. Es una guía práctica para organizarte, <b>no asesoría fiscal</b> — confírmalo con tu contador antes de declarar.<br><br>'
      +'<b>IVA e ISLR son dos preguntas distintas del mismo gasto.</b> Que un egreso sea deducible de ISLR no significa que tenga IVA que descontar: un sueldo o un tributo municipal bajan tu ganancia del año, pero nunca traen factura con IVA discriminado — por eso FrixPOS no te pide RIF ni número de factura en esas categorías. Alquiler, servicios, flete, mantenimiento e insumos sí suelen traer IVA si el proveedor factura formal; mercancía y "otros" dependen de qué compraste.<br><br>'
      +'<b>Guarda siempre la factura física.</b> Lo que registras aquí es tu control, pero el SENIAT no acepta un gasto sin el papel original. La factura tiene que traer el RIF de tu proveedor, el tuyo, número de control y ser de imprenta autorizada — si le falta algo, tu contador la va a descartar y pierdes ese crédito fiscal. Métela en una carpeta por mes y entrégasela completa a tu contador.'
    +'</div></details>'
    +(contabFormOpen?'':'<button class="btn-primary" id="egresoNewBtn" style="width:100%;margin:10px 0">+ Registrar egreso</button>')
    +formHtml
    +(catsResumen?'<div class="desc" style="margin:14px 0 6px">Por categoría:</div>'+catsResumen:'')
    +'<div class="desc" style="margin:14px 0 6px">Detalle:</div>'
    +egresosListHtml
    +'<div class="desc" style="margin-top:10px">Total del período: <b>'+fmtBs(d.egresosBs)+'</b> · deducibles: <b>'+fmtBs(d.egresosDeduciblesBs)+'</b></div>'
    +'</details>';

  // 4) Bases para lo que falta — deja explícito qué SÍ y qué NO cubre todavía
  const pendientesHtml='<details class="cfg-block cfg-acc" data-acc="pendientes"'+(contabAccOpen==='pendientes'?' open':'')+'><summary><h4 style="display:inline">Lo que falta para estar completo</h4><span class="acc-chev">›</span></summary>'
    +'<div class="desc">FrixPOS ya te lleva el IVA, los ingresos, los egresos, la ganancia real y las estimaciones de patente e ISLR. Estas otras obligaciones de un negocio en Venezuela todavía no están automatizadas:</div>'
    +'<div class="order-row"><div><div class="or-top">Libros en el formato oficial del SENIAT</div><div class="or-sub">FrixPOS ya te exporta el borrador de trabajo; el formato legal exacto lo arma tu contador</div></div><span class="tag-soon">Próximamente</span></div>'
    +'<div class="order-row"><div><div class="or-top">Facturación fiscal (imprenta autorizada)</div><div class="or-sub">Numeración y formato de factura según la Providencia del SENIAT</div></div><span class="tag-soon">Próximamente</span></div>'
    +'<div class="order-row"><div><div class="or-top">Retenciones de IVA</div><div class="or-sub">Si te designan agente de retención o te retienen a ti</div></div><span class="tag-soon">Próximamente</span></div>'
    +'<div class="order-row"><div><div class="or-top">Declarar y pagar en el portal</div><div class="or-sub">FrixPOS te da los números; presentar la planilla ante el SENIAT y la alcaldía sigue siendo manual</div></div><span class="tag-soon">Próximamente</span></div>'
    +'<div class="desc" style="margin-top:12px">⚠FrixPOS te ayuda a organizarte y ver tus números claros, pero <b>no sustituye a un contador</b>. Confirma con el tuyo antes de declarar.</div>'
    +'<details class="note" style="margin-top:10px"><summary><span class="n-closed">Aviso legal ↓</span><span class="n-open">Aviso legal ↑</span></summary><div class="note-body">FrixPOS es una <b>herramienta de control administrativo e información interna</b>: lleva tu caja, tu stock, tus fiados y te arma los borradores de tus libros. <b>No sustituye las obligaciones fiscales del contribuyente ni constituye un medio de facturación fiscal homologado por el SENIAT.</b> Las declaraciones, los impuestos y la numeración fiscal de tus documentos son responsabilidad exclusiva tuya y de tu contador.<br><br>Si tu negocio, por su actividad o su nivel de ventas, está obligado a tener <b>máquina fiscal o talonario de imprenta autorizada</b>, esa máquina o talonario sigue siendo la que emite tu factura legal — FrixPOS lleva la caja, el stock y los fiados por su lado, no la reemplaza.</div></details>'
    +'</details>';

  // 5) v24 — Semáforo de salud fiscal: lo que el contador reclamaría, mientras aún se puede arreglar
  const semItems=contabSemaforo(d);
  const semRojo=semItems.some(x=>x.lvl==='red'), semAmbar=semItems.some(x=>x.lvl==='amber');
  // v5 — acordeón. Antes la lista completa se abría de golpe y la sección quedaba larguísima;
  // ahora se ve el título con su color de estado y el detalle se despliega al tocarlo.
  const semColor=semRojo?'var(--red)':(semAmbar?'var(--amber)':'var(--green)');
  const semaforoHtml='<details class="cfg-block cfg-acc" data-acc="salud"'+(contabAccOpen==='salud'?' open':'')+'><summary><h4 style="display:inline;color:'+semColor+'">Salud fiscal del período</h4>'
    +'<span class="acc-chev">›</span></summary>'
    +'<div class="desc" style="color:'+semColor+'">'+(semRojo?'Hay algo que te está distorsionando los números.':semAmbar?'Casi todo en orden, pero quedan cosas por completar.':'Todo en orden para este período.')+'</div>'
    +'<details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">'
      +'Esto no calcula impuestos nuevos: revisa los datos que ya tienes cargados y te avisa qué le falta a la información <b>antes</b> de que se la lleves al contador. Es lo mismo que él te va a reclamar a fin de mes — la diferencia es que aquí te enteras a tiempo, cuando todavía tienes la factura en la mano y te acuerdas de a quién le compraste.'
    +'</div></details>'
    +semItems.map(semaforoRow).join('')
    +'</details>';

  // 6) v24 — ¿Dónde está la plata? (efectivo vs. digital vs. fiado)
  const cu=contabCustodia();
  const efLinea=Object.keys(cu.efectivo).filter(k=>Math.abs(cu.efectivo[k])>0.009)
    .map(k=>fmtMontoMoneda(cu.efectivo[k],k)).join(' · ');
  const custodiaHtml='<details class="cfg-block cfg-acc" data-acc="custodia"'+(contabAccOpen==='custodia'?' open':'')+'><summary><h4 style="display:inline">¿Dónde está la plata?</h4><span class="acc-chev">›</span></summary>'
    +'<div class="desc">La ganancia de arriba dice cuánto ganaste. Esto dice dónde está — no es lo mismo tenerla en la gaveta que tenerla fiada en la calle.</div>'
    +'<details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">'
      +'Un negocio puede cerrar el mes con buena ganancia y aun así no tener con qué pagarle al camión del proveedor. Pasa cuando la mayor parte de lo que vendiste entró por Zelle, pago móvil o quedó fiado: el número se ve bonito, pero la plata no está en la mano.<br><br>'
      +'<b>Efectivo en caja</b> es lo que de verdad tienes en la gaveta, ya descontado el vuelto que diste y los gastos que pagaste con esa misma plata. <b>Banco y digital</b> es lo que entró por pago móvil, Zelle, Binance o punto de venta. <b>Fiado por cobrar</b> todavía no es tuyo, aunque ya lo hayas contado como venta.<br><br>'
      +'Los abonos que te pagan de deudas viejas todavía no se separan por método aquí — entran al Cajón del turno, pero este desglose solo mira las ventas del período.'
    +'</div></details>'
    +contabRowHtml('Efectivo en caja',efLinea?('Por moneda: '+efLinea):'Lo que quedó físico en la gaveta',cu.efectivoBs,cu.efectivoBs>=0?'ok':'')
    +contabRowHtml('Banco y digital','Pago móvil, Zelle, Binance, punto de venta',cu.digitalBs)
    +(cu.egCajonBs>0?contabRowHtml('− Gastos pagados de la caja','Ya descontados del efectivo de arriba',cu.egCajonBs):'')
    +(cu.egExternoBs>0?contabRowHtml('− Gastos pagados de otro lado','Banco, transferencia o bolsillo del dueño',cu.egExternoBs):'')
    +contabRowHtml('= Disponible del período','Lo que tienes de verdad para mover',cu.disponibleBs,cu.disponibleBs>=0?'ok':'')
    +(cu.fiadoPendienteBs>0?contabRowHtml('Fiado por cobrar','Todavía no está en tu bolsillo',cu.fiadoPendienteBs):'')
    +(cu.aproximado?'<div class="desc" style="margin-top:10px;color:var(--amber)">⚠Hay ventas viejas que no guardaron con qué se pagaron. Se clasificaron por su método, pero no entran en el conteo por moneda de arriba.</div>':'')
    +'</details>';

  // 7) v24 — Patente municipal e ISLR estimados (configurables, nunca hardcodeados)
  const patenteBs=d.ingresosSinIvaBs*((cfg.patentePct||0)/100);
  const islrBaseBs=d.ingresosSinIvaBs-d.costoMercanciaBs-d.egresosDeduciblesBs;
  const islrBs=Math.max(0,islrBaseBs)*((cfg.islrPct||0)/100);
  const otrosImpHtml=esGratis?'':'<details class="cfg-block cfg-acc" data-acc="otrosImp"'+(contabAccOpen==='otrosImp'?' open':'')+'><summary><h4 style="display:inline">Otros impuestos estimados</h4><span class="acc-chev">›</span></summary>'
    +'<div class="desc">Estimaciones de orientación con los porcentajes que cargaste en Configuración. <b>No son declaraciones</b> — confírmalas con tu contador.</div>'
    +'<details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">'
      +'La <b>patente municipal</b> la cobra tu alcaldía sobre las ventas del mes, no sobre tu ganancia — se paga aunque el mes te haya ido mal. FrixPOS la estima sobre tus ingresos sin IVA (el IVA nunca fue ingreso tuyo), pero cada ordenanza municipal define su propia base: pregúntale a tu contador si en tu municipio se calcula así.<br><br>'
      +'El <b>ISLR</b> es anual y se paga sobre el enriquecimiento neto: ingresos menos costo de mercancía menos gastos deducibles. Lo que ves aquí es esa base aplicada al período que tienes filtrado arriba — si quieres la cifra del año, filtra el año completo. El porcentaje real depende de tu forma legal y del tramo en que caiga la ganancia, y ese número te lo tiene que dar tu contador: por eso FrixPOS no trae ninguno puesto.<br><br>'
      +'Estos dos impuestos <b>no</b> están descontados de la ganancia neta de arriba. Cuando los pagues, regístralos como egreso en la categoría "Impuestos y tasas" para que queden en el histórico.'
    +'</div></details>'
    +(cfg.patentePct>0
      ? contabRowHtml('Patente municipal ('+cfg.patentePct+'%)','Sobre '+fmtBs(d.ingresosSinIvaBs)+' de ingresos sin IVA',patenteBs)
      : '<div class="desc" style="margin:10px 0">Patente municipal sin configurar. Carga el % de tu alcaldía en <b>Configuración → Precios y tasas → Otros impuestos del negocio</b>.</div>')
    +contabRowHtml('Enriquecimiento neto estimado','Ingresos sin IVA − costo de mercancía − gastos deducibles',islrBaseBs,islrBaseBs>=0?'ok':'')
    +(cfg.islrPct>0
      ? contabRowHtml('ISLR estimado ('+cfg.islrPct+'%)','Estimación de orientación, no una declaración',islrBs)
      : '<div class="desc" style="margin:10px 0">ISLR sin configurar. Pregúntale a tu contador qué porcentaje te toca y cárgalo en <b>Configuración → Precios y tasas</b>.</div>')
    +'</details>';

  // 8) v24 — Contribuyente Especial: solo aparece si el negocio lo es (casi ninguna bodega)
  const especialHtml=(cfg.contribEspecial&&!esGratis)
    ?'<details class="cfg-block cfg-acc" data-acc="especial"'+(contabAccOpen==='especial'?' open':'')+'><summary><h4 style="display:inline">Obligaciones de Contribuyente Especial</h4><span class="acc-chev">›</span></summary>'
      +'<div class="desc">Marcaste que el SENIAT te designó Sujeto Pasivo Especial. Eso te agrega dos obligaciones que FrixPOS <b>todavía no calcula</b>:</div>'
      +'<div class="order-row"><div><div class="or-top">Retención de IVA a proveedores</div><div class="or-sub">Al comprar retienes el 75% o el 100% del IVA de la factura, entregas comprobante de retención y le pagas esa parte directo al SENIAT en calendario quincenal</div></div><span class="tag-soon">Próximamente</span></div>'
      +'<div class="order-row"><div><div class="or-top">IGTF 3% en pagos en divisas</div><div class="or-sub">Como agente de percepción tienes que cobrar el 3% extra cuando el cliente paga en dólares efectivo o cripto</div></div><span class="tag-soon">Próximamente</span></div>'
      +'<div class="desc" style="margin-top:12px;color:var(--amber)">⚠Mientras esto no esté automatizado, tu contador tiene que llevar las retenciones y el IGTF por fuera. Si no eres Contribuyente Especial, apaga esta opción en Configuración.</div>'
    +'</details>'
    :'';

  // 9) v24 → v26_4 — Exportar para el contador, con su propio mes calendario (no el filtro de arriba)
  // v1.10 — es lo que se paga. Registrar egresos SÍ es gratis a propósito: sin ellos la "Ganancia
  // neta" del bloque de arriba (que sí es gratis) saldría inflada, y mostrar un número malo es
  // peor que no mostrarlo. Lo que se cobra es el archivo que le entregas al contador.
  // v32 — un mes en curso no exporta: SENIAT declara por mes CERRADO, y un archivo a mitad de
  // mes le cambiaría los números al contador apenas se registre una venta o egreso más.
  const mesEnCurso=mesCalEsActual(contabMesCal);
  const exportBtns=[
    {id:'expVentas',cls:'btn-primary',label:'Libro de Ventas — con factura (Art. 76)'},
    {id:'expDiario',cls:'btn-primary',label:'Libro de Ventas — consumidor final (Art. 77)'},
    {id:'expReportesDia',cls:'btn-secondary',label:'Descargar Reportes del día (mes)'},
    {id:'expCompras',cls:'btn-secondary',label:'Descargar Libro de Compras y Egresos'},
    {id:'expCierres',cls:'btn-secondary',label:'Descargar Resumen de Turnos del mes'}
  ].map(b=>'<button class="'+b.cls+'" id="'+b.id+'" style="width:100%;margin-top:8px'+(mesEnCurso?';opacity:.4;cursor:not-allowed':'')+'"'+(mesEnCurso?' disabled':'')+'>'+b.label+'</button>').join('');
  const exportHtml=esGratis
    ?'<details class="cfg-block cfg-acc" data-acc="export"'+(contabAccOpen==='export'?' open':'')+'><summary><h4 style="display:inline">Exportar para el contador</h4><span class="acc-chev">›</span></summary>'
      +'<div class="desc">Los archivos que tu contador necesita para armar el Libro de Ventas y el de Compras sin tipear factura por factura.</div>'
      +premiumNotaHTML([
          'Libro de Ventas con factura (Art. 76)',
          'Libro de Ventas consumidor final, resumido por día (Art. 77)',
          'Reportes del día y Libro de Compras',
          'Resumen de Turnos del mes'
        ])
    +'</details>'
    :'<details class="cfg-block cfg-acc" data-acc="export"'+(contabAccOpen==='export'?' open':'')+'><summary><h4 style="display:inline">Exportar para el contador</h4><span class="acc-chev">›</span></summary>'
    +'<div class="desc">El SENIAT declara el IVA por mes calendario, así que aquí eliges el mes completo que vas a declarar — no usa el filtro de arriba (ese es solo para que veas tu negocio como quieras, en el rango que sea).</div>'
    +'<div class="cfg-row"><label>Mes a exportar</label><input class="cfg-input" type="month" id="contabMesCalInput" value="'+contabMesCal+'"></div>'
    +'<div class="desc" style="margin-top:-6px">Vas a exportar: <b>'+mesCalLabel(contabMesCal)+'</b></div>'
    +(mesEnCurso?'<div class="desc" style="color:var(--amber);margin-top:-6px">Este mes todavía está en curso — no se puede descargar completo hasta que termine, porque le pueden entrar más ventas o egresos. Va a estar listo el <b>'+mesCalListoDesde(contabMesCal)+'</b>. Mientras tanto, elige un mes anterior arriba si necesitas descargar algo ahora.</div>':'')
    +'<details class="note"><summary><span class="n-closed">Ver más ↓</span><span class="n-open">Ver menos ↑</span></summary><div class="note-body">'
      +'Son <b>borradores de trabajo</b>, no las planillas oficiales del SENIAT. Tu contador los usa para armar el Libro de Ventas y el Libro de Compras en el formato legal sin tener que tipear factura por factura — que es donde de verdad se le va el tiempo y donde se cometen los errores.<br><br>'
      +'<b>Por qué mes calendario y no una fecha libre:</b> el período de imposición del IVA es el mes completo — día 1 al último — para un Contribuyente Ordinario. No existe la quincena para tu caso (esa solo aplica a Contribuyentes Especiales que retienen IVA a proveedores). Un rango como "del 7 de un mes al 7 del siguiente" no corresponde a ningún período que el SENIAT reconozca, por eso aquí no se puede escribir una fecha suelta: eliges el mes y listo. Por la misma razón, un mes que todavía no termina tampoco se puede exportar.<br><br>'
      +'<b>Por qué el Libro de Ventas son dos archivos:</b> el Reglamento de la Ley de IVA los separa. Las ventas con factura a un cliente identificado van renglón por renglón (Art. 76). Las ventas a consumidor final van <b>resumidas por día</b>, con el primer y el último comprobante de la jornada (Art. 77) — nadie tiene que listar mil tickets de un dólar. Y el Art. 78 obliga a llevar los dos tipos por separado. FrixPOS manda al libro de facturas toda venta cuyo cliente tenga cédula o RIF cargado; el resto va al resumen diario.<br><br>'
      +'<b>Reportes del día (mes)</b> es el mismo Reporte del día de arriba, uno por cada día con ventas del mes completo — el mismo criterio, solo que en un archivo. <b>Resumen de Turnos del mes</b> es distinto: agrupa por apertura/cierre de caja, para cuadrar el arqueo, no para el Libro de Ventas.<br><br>'
      +'Antes de exportar, revisa el semáforo de arriba: si hay egresos sin RIF o sin número de factura, van a salir con esas casillas vacías y te los va a devolver.<br><br>'
      +'Tus datos ya se guardan solos en este dispositivo — no hace falta exportar por miedo a perderlos. De igual forma, descarga el mes cuando lo cierres: es el archivo que le entregas a tu contador.'
    +'</div></details>'
    +exportBtns
    +'<div class="desc" id="expAviso" style="margin-top:10px"></div>'
    +'</details>';

  $('contabilidadBody').innerHTML=
    '<div class="chips" id="contabChips">'+chipsHtml+'</div>'
    +bloqueoAvisoHtml
    +customHtml
    +semaforoHtml
    +resultadoHtml
    +custodiaHtml
    +seniatHtml
    +otrosImpHtml
    +especialHtml
    +egresosHtml
    +reporteDelDiaHTML(esGratis)
    +exportHtml
    +pendientesHtml;
  if(contabFormOpen){updateEgresoNota();updateEgresoOrigenNota();}
  setupContabAccordion();
}
// v33 — acordeón exclusivo: abrir una sección cierra la que estaba abierta. El HTML se
// reconstruye entero en cada render, así que los listeners se reenganchan cada vez (el evento
// 'toggle' de <details> no burbujea, no se puede delegar en el contenedor).
function setupContabAccordion(){
  document.querySelectorAll('#contabilidadBody details.cfg-acc').forEach(d=>{
    d.addEventListener('toggle',()=>{
      if(d.open){
        contabAccOpen=d.dataset.acc;
        document.querySelectorAll('#contabilidadBody details.cfg-acc').forEach(o=>{ if(o!==d&&o.open)o.open=false; });
      } else if(contabAccOpen===d.dataset.acc){
        contabAccOpen=null;
      }
    });
  });
}
function updateEgresoOrigenNota(){
  const el=$('egresoOrigenNota');if(!el)return;
  const esCajon=document.querySelector('#egresoOrigen button.on')?.dataset.v==='cajon';
  if(esCajon){
    el.innerHTML=openTurno
      ? 'Se descuenta del efectivo que deberías tener al cerrar el turno, así el arqueo te cuadra.'
      : 'No tienes un turno abierto, así que solo se registra como gasto. Ábrelo antes si quieres que afecte el arqueo.';
  } else {
    el.innerHTML='Salió del banco, de una transferencia o de tu bolsillo. No toca la plata de la caja.';
  }
}
function updateEgresoNota(){
  const sel=$('egresoCat'); const nota=$('egresoCatNota');
  if(!sel||!nota)return;
  const c=EGRESO_CATS.find(x=>x.id===sel.value);
  nota.textContent=c&&c.nota?c.nota:'';
  updateEgresoFacturaBlock();
}
// v31 — muestra/oculta RIF/factura/base/IVA según si la categoría elegida puede traer IVA
// discriminado. No borra lo ya escrito si el usuario cambia de categoría y se arrepiente —
// solo lo oculta; si vuelve a una categoría que sí aplica, sus datos siguen ahí.
function updateEgresoFacturaBlock(){
  const sel=$('egresoCat'); const block=$('egresoFacturaBlock'); const nota=$('egresoNoIvaNota');
  if(!sel||!block)return;
  const visible=egresoPuedeIva(sel.value);
  block.style.display=visible?'':'none';
  if(nota)nota.style.display=visible?'none':'';
}
$('contabilidadBody').addEventListener('click',e=>{
  const chip=e.target.closest('#contabChips .chip');
  if(chip){ contabRange=chip.dataset.r; renderContabilidad(); return; }
  if(e.target.closest('#egresoNewBtn')){ egresoEditId=null; contabFormOpen=true; contabAccOpen='egresos'; renderContabilidad(); return; }
  const ed=e.target.closest('[data-edit-egreso]');
  if(ed){
    egresoEditId=ed.dataset.editEgreso;
    contabFormOpen=true;
    contabAccOpen='egresos';
    renderContabilidad();
    llenarFormEgreso();
    return;
  }
  // v24 — exportaciones para el contador
  const expBtn=e.target.closest('#expVentas,#expDiario,#expReportesDia,#expCompras,#expCierres');
  if(expBtn){
    const aviso=$('expAviso');
    // v32 — resguardo aparte del atributo disabled del botón: si por lo que sea el click llega
    // igual (ej. un re-render que se atrasó), no deja pasar la descarga de un mes en curso.
    if(mesCalEsActual(contabMesCal)){
      if(aviso){ aviso.textContent='Este mes todavía está en curso — se podrá descargar completo a partir del '+mesCalListoDesde(contabMesCal)+'.'; aviso.style.color='var(--amber)'; }
      return;
    }
    const ok=expBtn.id==='expVentas'?exportarLibroVentas()
            :expBtn.id==='expDiario'?exportarResumenDiario()
            :expBtn.id==='expReportesDia'?exportarReportesDelDia()
            :expBtn.id==='expCompras'?exportarLibroCompras():exportarCierresZ();
    if(aviso){
      aviso.textContent=ok?'Archivo descargado. Búscalo en las descargas de tu teléfono o computadora.':'No se pudo descargar aquí. Prueba desde el navegador del teléfono o de la computadora.';
      aviso.style.color=ok?'var(--green)':'var(--amber)';
    }
    return;
  }
  if(e.target.closest('#egresoCancel')){ contabFormOpen=false; egresoEditId=null; renderContabilidad(); return; }
  const curBtn=e.target.closest('#egresoCur button');
  if(curBtn){ document.querySelectorAll('#egresoCur button').forEach(x=>x.classList.toggle('on',x===curBtn)); return; }
  const origBtn=e.target.closest('#egresoOrigen button');
  if(origBtn){
    document.querySelectorAll('#egresoOrigen button').forEach(x=>x.classList.toggle('on',x===origBtn));
    updateEgresoOrigenNota();
    return;
  }
  if(e.target.closest('#egresoSave')){
    const amount=parseFloat($('egresoAmount').value)||0;
    if(amount<=0){ $('egresoAmount').focus(); return; }
    const cat=$('egresoCat').value;
    const currency=document.querySelector('#egresoCur button.on')?.dataset.v||'bs';
    const origen=document.querySelector('#egresoOrigen button.on')?.dataset.v||'cajon';
    const dateStr=$('egresoDate').value;
    const date=dateStr?new Date(dateStr+'T12:00:00'):new Date();
    const catDef=EGRESO_CATS.find(x=>x.id===cat);
    const datos={
      date,cat,
      desc:$('egresoDesc').value.trim(),
      amount,currency,
      amountBs:convertToBs(amount,currency),
      deducible:catDef?catDef.deducible:true,
      // v24 — soporte documental del gasto (Libro de Compras y semáforo de salud fiscal)
      proveedor:($('egresoProv')?$('egresoProv').value.trim():''),
      provRif:($('egresoProvRif')?$('egresoProvRif').value.trim():''),
      factura:($('egresoFactura')?$('egresoFactura').value.trim():''),
      // v26 — de acá nace el IVA Crédito Fiscal real (no del costo de lo vendido)
      baseFacturaBs:(parseFloat($('egresoBaseFactura')?.value)||0),
      ivaFacturaBs:(parseFloat($('egresoIvaFactura')?.value)||0),
      // v21 — de dónde salió la plata. Si salió del cajón y hay un turno abierto, queda amarrado a
      // ese turno para que el arqueo lo descuente del efectivo esperado (ver turnoDesglose).
      origen
    };
    let egresoGuardado=null;
    if(egresoEditId){
      const ex=egresos.find(x=>x.id===egresoEditId);
      if(ex){
        // el turnoId NO se recalcula al editar: el egreso pertenece al turno en el que se
        // registró, aunque hoy haya otro turno abierto. Cambiarlo movería plata de un arqueo
        // ya cerrado a otro — misma regla de oro que congela costos al momento de la venta.
        Object.assign(ex,datos);
        egresoGuardado=ex;
      }
    } else {
      // id único global (uuidLite), no secuencial: mismo motivo que 'p_'+uuidLite() en
      // productos (v1.8) — con sincronización entre dispositivos, dos teléfonos generando
      // 'e12' cada uno por su lado serían egresos DISTINTOS con el MISMO id. egresoSeq se
      // deja vivo (sigue en el respaldo) por compatibilidad con egresos viejos, ya creados así.
      datos.id='e_'+uuidLite();
      datos.turnoId=(origen==='cajon'&&openTurno)?openTurno.id:null;
      egresos.push(datos);
      egresoGuardado=datos;
    }
    contabFormOpen=false; egresoEditId=null;
    contabAccOpen=null; // pedido de Jonathan: al guardar, se cierra el acordeón y se ve toda la pantalla
    schedulePersist(true);
    renderContabilidad();
    if(egresoGuardado) emitCambio('egreso',egresoGuardado);
    return;
  }
  const rm=e.target.closest('[data-rm-egreso]');
  if(rm){
    const id=rm.dataset.rmEgreso;
    askConfirm('¿Borrar este egreso?',()=>{
      const i=egresos.findIndex(x=>x.id===id);
      if(i>=0)egresos.splice(i,1);
      schedulePersist(true);
      renderContabilidad();
      emitCambio('egreso_borrado',{id});
    });
    return;
  }
});
$('contabilidadBody').addEventListener('change',e=>{
  if(e.target.id==='contabFrom'){ contabCustomFrom=e.target.value; renderContabilidad(); }
  if(e.target.id==='contabTo'){ contabCustomTo=e.target.value; renderContabilidad(); }
  if(e.target.id==='contabMesCalInput'){ if(e.target.value){ contabMesCal=e.target.value; renderContabilidad(); } }
  if(e.target.id==='reporteDiaInput'){ if(e.target.value){ reporteDiaFecha=e.target.value; renderContabilidad(); } }
  if(e.target.id==='egresoCat'){ updateEgresoNota(); }
});

$('checkoutBtn').addEventListener('click',()=>{
  if($('checkoutBtn').disabled)return;
  if(!exigirTurnoAbierto())return;   // segunda barrera: tampoco se cobra sin caja abierta
  const ids=Object.keys(cart).filter(id=>cart[id]>0);
  const items=ids.map(id=>{
    const p=products.find(x=>x.id===id);
    const pr=effectivePrices(p);
    const rateBsSale=rateBsForPrices();
    return{
      productId:id,name:p.name,qty:cart[id],
      // venta por peso (v45): congelado en la línea (nunca leído del catálogo vivo) — mismo
      // principio que priceBs/costBs de abajo. qty ya viene en Kg (decimal) para estos productos
      // y priceBs ya es el precio POR KG, así que priceBs*qty (usado en TODO el resto del sistema:
      // total, informes, recibo, stock) da el subtotal correcto sin tocar ninguna de esas fórmulas.
      esPeso:!!p.ventaPeso,
      priceBs:Math.round(pr.bs),
      priceUsd:pr.contado,                          // USD oferta (dólar real) — el ancla
      priceMonedas:Object.assign({},pr.monedas),   // v5 — congelado por moneda, no un solo "cop"
      priceCop:0,
      // v27 — vitrina/factura formal, congelada aparte para que un recibo o reimpresión vieja
      // pueda seguir mostrando el USD BCV con el que se cobró ese día, aunque el BCV de hoy
      // sea otro. null si ese día no había BCV+Paralelo cargados a la vez (nada que inflar).
      priceUsdBcv:(cfg.bcv>0&&cfg.paralelo>0)?Math.round(pr.bcv*100)/100:null,
      // costo unitario CONGELADO al momento de vender (misma regla de oro que priceBs/priceUsd):
      // así la ganancia de esta venta en Informes nunca cambia aunque después se actualice
      // el costo del producto en Inventario. costBs usa la misma tasa que ya usa priceBs arriba.
      // v1.21 — un producto en modo Simple no tiene costBcv (nunca se le pidió costo): se
      // congela null, NUNCA 0 ni el resultado de multiplicar undefined (que daría NaN y
      // arruinaría cualquier suma de costoMercanciaBs que lo tocara). null es justo lo que ya
      // sabe leer el aviso "ventas sin costo congelado" que existe en Contabilidad — mismo
      // mecanismo, ahora con una causa más además de los pedidos viejos.
      costUsd:(p.pricingMode==='simple')?null:p.costBcv,
      costBs:(p.pricingMode==='simple')?null:Math.round(p.costBcv*rateBsSale),
      // control fiscal SENIAT (v17), también CONGELADO: la diferencia a pagar de una venta
      // vieja no debe moverse si después se ajusta el IVA general o el interruptor "¿Sujeto
      // a IVA?" del producto en Inventario.
      ivaDebitoUsd:pr.ivaDebito||0,
      ivaDebitoBs:Math.round((pr.ivaDebito||0)*rateBsSale),
      ivaCreditoUsd:pr.ivaCredito||0,
      ivaCreditoBs:Math.round((pr.ivaCredito||0)*rateBsSale),
      // modelo de precios con el que se calculó esta línea (v18) — queda congelado para que una
      // venta vieja se pueda auditar aunque después se cambie de modelo en Configuración.
      priceModel:'gastos',
      gastosUsd:pr.montoGastos||0
    };
  });
  const totalBs=items.reduce((s,i)=>s+i.priceBs*i.qty,0);
  // toda venta (pagada o fiada) descuenta el stock — el producto ya salió del negocio
  ids.forEach(id=>{const p=products.find(x=>x.id===id);if(p){p.stock-=cart[id];registrarMovimientoStock(id,-cart[id]);}});
  let method=null,paymentsBreakdown=null,changeBs=0;
  if(saleType==='pagado'){
    paymentsBreakdown=payments.map(p=>({method:p.method,label:p.label,amount:p.amount,currency:p.currency}));
    method=payments.length===1?payments[0].method:(payments.length>1?'mixto':null);
    // vuelto = lo entregado por encima del total EXACTO (mismo total sin redondear por línea que
    // ya validó "Cubierto ✓" en refreshCheckout/cartSubtotalBs) — usar el totalBs de arriba (cada
    // línea redondeada a Bs entero) metía vuelto fantasma: al redondear, el total registrado podía
    // quedar unos Bs por debajo de lo realmente cobrado (sobre todo en ventas chicas pagadas en $),
    // y esa diferencia — que el propio resumen ya daba por "Cubierto" dentro de tolerancia — se
    // guardaba como si fuera vuelto real entregado en Bs. turnoDesglose() resta ese vuelto de
    // "Bolívares efectivo", así que terminaba restando Bs fantasma incluso en ventas 100% en dólares
    // que nunca tocaron un bolívar físico (caso real: "Bolívares efectivo -3 Bs" en el arqueo).
    const totalBsExacto=cartSubtotalBs();
    const entregadoBs=payments.reduce((s,p)=>s+convertToBs(p.amount,p.currency),0);
    const diffBs=entregadoBs-totalBsExacto;
    changeBs=diffBs>paymentToleranceBs(payments)?Math.round(diffBs*100)/100:0;
  }
  // código de referencia de la venta (v16): pedido explícito de Jonathan, uno por venta, visible en
  // factura/Pedidos. Se arma con la fecha + el mismo contador de orderSeq, así queda único sin
  // necesitar un contador aparte.
  const refCode='PT-'+fmtRefDate(new Date())+'-'+String(orderSeq).padStart(4,'0');
  // v51 — "descuento oferta" en el recibo: si TODOS los métodos con los que se pagó esta venta son
  // de los "(oferta)" (PAY_METHODS_OFERTA — ahora un método aparte, no una config escondida sobre
  // el mismo método, ver v51 más arriba), se congela acá para que el recibo lo muestre como
  // descuento al final. Congelado como booleano nada más: el monto del descuento se recalcula
  // siempre desde priceUsd/priceUsdBcv, que YA quedan congelados por línea arriba — así una
  // reimpresión de mañana da el mismo número aunque cambien las tasas.
  const pagoAOferta=saleType==='pagado' && payments.length>0
    && payments.every(p=>PAY_METHODS_OFERTA.includes(p.method));
  const order={id:'o'+(orderSeq++),uuid:uuidLite(),synced:false,refCode,date:new Date(),items,totalBs,method,paymentsBreakdown,changeBs,turnoId:(openTurno?openTurno.id:null),clientId:fiarClientId||null,status:saleType,paidBs:saleType==='pagado'?totalBs:0,pagoAOferta};
  orders.unshift(order);
  schedulePersist(true); // venta = momento de máximo valor a proteger, se guarda YA, no en el autosave periódico
  syncPendingOrders(); // intento silencioso — si no hay token o no hay internet, no hace nada y no bloquea
  sincronizarStock(); // idem para el stock multi-caja — la venta ya descontó local, esto la reporta si puede
  showSaleSuccess(order);
});
// pantalla de "venta lista" dentro del mismo panel de cobro: total + compartir por WhatsApp + nueva venta.
// no se auto-cierra sola — antes se cerraba a los 1.4s y no daba tiempo a compartir la factura.
function showSaleSuccess(o){
  saleSuccessOpen=true;
  cart={};payments=[];selMethod=null;
  $('saleType').style.display='none';
  $('fiarPicker').style.display='none';
  $('checkoutBtn').style.display='none';
  $('pay').innerHTML='<div class="paymix-summary ok" style="margin-bottom:10px">'
    +(o.status==='fiado'?'✓ Fiado registrado — ':'✓ Venta registrada — ')+fmtBs(o.totalBs)
    +(o.refCode?'<div style="font-weight:600;font-size:.767rem;margin-top:4px">Ref: '+o.refCode+'</div>':'')+'</div>'
    +'<button class="btn-primary" id="shareNowBtn" style="width:100%;margin-bottom:8px;justify-content:center;display:flex;gap:6px;align-items:center">Compartir comprobante por WhatsApp</button>'
    +'<div style="display:flex;gap:8px;margin-bottom:8px">'
    +'<button class="btn-secondary" id="printNowBtn" style="flex:1;justify-content:center;display:flex;gap:6px;align-items:center">Imprimir</button>'
    +'<button class="btn-secondary" id="emailNowBtn" style="flex:1;justify-content:center;display:flex;gap:6px;align-items:center">Correo</button>'
    +'</div>'
    +'<button class="btn-secondary" id="newSaleBtn" style="width:100%;text-align:center">+ Nueva venta</button>';
  renderCart();
  $('shareNowBtn').onclick=()=>shareReceipt(o);
  $('printNowBtn').onclick=()=>printReceipt(o);
  $('emailNowBtn').onclick=()=>emailReceipt(o);
  $('newSaleBtn').onclick=resetSaleUI;
}
function resetSaleUI(){
  saleSuccessOpen=false;
  cart={};
  payments=[];selMethod=null;
  saleType='pagado';fiarClientId=null;fiarQuery='';$('fiarSearch').value='';
  $('fiarNewForm').style.display='none';
  document.querySelectorAll('#saleType button').forEach(x=>x.classList.toggle('on',x.dataset.t==='pagado'));
  $('saleType').style.display='';
  $('fiarPicker').style.display='';
  $('checkoutBtn').style.display='';
  renderFiarList();renderPay();renderCart();renderNav();
  if(currentSection==='pedidos')renderPedidos();
  if(currentSection==='porcobrar')renderPorCobrar();
  if(currentSection==='clientes')renderClientes();
  if(currentSection==='catalogo')renderCatalogo();
  if(currentSection==='stock')renderStock();
  setCartOpen(false);
}
// arma el texto de la factura respetando la moneda elegida en Configuración → Moneda de la factura
function buildReceiptText(o){
  const cur=cfg.invoiceCurrency||'usd';
  const client=o.clientId?clients.find(c=>c.id===o.clientId):null;
  const lineAmt=(usd,bs)=>cur==='usd'?fmtUSD(usd||0):cur==='bs'?fmtBs(bs):fmtUSD(usd||0)+' ('+fmtBs(bs)+')';
  // v1.18 — el comprobante que ve el cliente NUNCA muestra el dólar oferta (el de caja/
  // efectivo con descuento) ni la palabra "vitrina" — eso es información interna del dueño,
  // no algo para imprimir. Si el pedido tiene el dólar BCV congelado (priceUsdBcv, desde v27)
  // se usa ESE en todo el comprobante — es el mismo número que priceBs, solo en dólares, así
  // que nunca desentona con el bolívar de al lado. Un pedido viejo sin ese campo (antes de
  // v27) cae al oferta porque no hay otro número que mostrar, pero jamás se etiqueta cuál es.
  const usdItem=it=>(it.priceUsdBcv!=null?it.priceUsdBcv:it.priceUsd)||0;
  let txt='*'+(cfg.bizName||'FrixPOS')+'*\n';
  if(cfg.bizRif)txt+='RIF/NIT: '+cfg.bizRif+'\n';
  if(cfg.bizAddress)txt+=cfg.bizAddress+'\n';
  if(cfg.bizPhone)txt+='Tel: '+cfg.bizPhone+'\n';
  txt+=(o.status==='fiado'?'NOTA DE ENTREGA — POR COBRAR':'COMPROBANTE DE VENTA')+'\n';
  txt+='(documento sin valor fiscal)\n';
  txt+='Ref: '+(o.refCode||o.id)+'\n'; // v4 — si por lo que sea no hay refCode, va el id: el comprobante NUNCA sale sin un número con qué buscarlo después
  txt+='Fecha: '+fmtDateTimeFull(o.date)+'\n';
  if(client){
    txt+='Cliente: '+client.name+'\n';
    if(client.idNumber)txt+='Cédula/RIF: '+client.idNumber+'\n';
    if(client.address)txt+='Dirección: '+client.address+'\n';
  }
  txt+='------------------------------\n';
  o.items.forEach(it=>{ txt+=(it.esPeso?formatPeso(it.qty):it.qty+'x')+' '+it.name+'\n   '+lineAmt(usdItem(it)*it.qty,it.priceBs*it.qty)+'\n'; });
  txt+='------------------------------\n';
  const totalUsd=o.items.reduce((s,it)=>s+usdItem(it)*it.qty,0);
  // v29 — desglose fiscal del comprobante (Subtotal/Exento/Base imponible/IVA/Total), del
  // MONTO FINAL de la venta — no del costo+margen de cada producto (eso es Informes, otra
  // cosa). Reusa zDeVentas([o]), la misma cuenta que ya usa el Reporte del día y los exports
  // para el contador, así que el comprobante y el libro de ventas SIEMPRE cuadran entre sí.
  const z=zDeVentas([o]);
  const ratio=totalUsd>0?(o.totalBs/totalUsd):rateBsActual(); // tasa congelada de ESTA venta
  const usdOf=bs=>ratio>0?bs/ratio:0;
  const subtotalBs=z.gravadoBs+z.exentoBs;
  txt+='Subtotal: '+lineAmt(usdOf(subtotalBs),subtotalBs)+'\n';
  if(z.exentoBs>0.5)txt+='  Exento: '+lineAmt(usdOf(z.exentoBs),z.exentoBs)+'\n';
  if(z.gravadoBs>0.5)txt+='  Base imponible: '+lineAmt(usdOf(z.gravadoBs),z.gravadoBs)+'\n';
  if(z.ivaBs>0.5)txt+='IVA ('+(cfg.ivaGeneral||0)+'%): '+lineAmt(usdOf(z.ivaBs),z.ivaBs)+'\n';
  txt+='------------------------------\n';
  // v49 — si toda la venta se pagó a precio Oferta (congelado en o.pagoAOferta, ver checkoutBtn),
  // el total final del recibo baja al oferta y se muestra el descuento aparte — solo dólar, sin
  // bolívares (la oferta es un dato informal, no algo que se declare). Si no, el recibo sigue
  // exactamente igual que siempre (BCV/Bs, sin esta línea).
  if(o.pagoAOferta){
    const totalUsdOferta=o.items.reduce((s,it)=>s+(it.priceUsd||0)*it.qty,0);
    const descuentoUsd=totalUsd-totalUsdOferta;
    const pct=totalUsd>0.005?Math.round((descuentoUsd/totalUsd)*100):0;
    if(descuentoUsd>0.005)txt+='Descuento oferta '+pct+'% - '+fmtUSD(descuentoUsd)+'\n';
    txt+='*TOTAL: '+fmtUSD(totalUsdOferta)+'*\n';
  } else {
    txt+='*TOTAL: '+lineAmt(totalUsd,o.totalBs)+'*\n';
  }
  if(o.status==='fiado'){
    const s=saldoOf(o);
    txt+= s>0.5 ? ('Pendiente por cobrar: '+lineAmt(bsToUsdRaw(s),s)+'\n') : 'Saldada ✓\n';
  }
  txt+='\n¡Gracias por tu compra! ';
  return txt;
}
function shareReceipt(o){
  const text=buildReceiptText(o);
  if(navigator.share){navigator.share({text}).catch(()=>{});}
  else{window.open('https://wa.me/?text='+encodeURIComponent(text),'_blank');}
}
// imprimir (v16): vuelca el mismo texto de la factura en #printArea (oculto en pantalla, visible
// solo con @media print) y dispara el diálogo nativo de impresión del navegador/WebView.
function printReceipt(o){
  $('printArea').textContent=buildReceiptText(o).replace(/\*/g,'');
  window.print();
}
// compartir por correo (v16): mismo texto de la factura, vía mailto (abre la app de correo del
// teléfono con el asunto y cuerpo ya listos).
function emailReceipt(o){
  const text=buildReceiptText(o).replace(/\*/g,'');
  const subject='Comprobante '+(o.refCode||o.id)+' — '+(cfg.bizName||'FrixPOS');
  window.open('mailto:?subject='+encodeURIComponent(subject)+'&body='+encodeURIComponent(text),'_blank');
}

// ===== Clientes =====
function clientCardHTML(c){
  const saldo=clientSaldo(c.id);
  const hist=clientOrders(c.id);
  const open=expandedClientId===c.id;
  let detail='';
  if(open){
    detail='<div class="detail-block">'
      +(saldo>0?abonoBlockHTML(c.id):'')
      +((c.idNumber||c.address)?'<div class="or-sub" style="margin-bottom:8px">'+[c.idNumber,c.address].filter(Boolean).join(' · ')+'</div>':'')
      +(hist.length?hist.map(o=>{
          const s=o.status==='fiado'?saldoOf(o):0;
          const chip=s>0?'<span class="status-chip fiado">Fiado</span>':'<span class="status-chip pagado">Pagado</span>';
          const totalNow=o.status==='fiado'?debtBs(o):o.totalBs;
          return '<div class="order-row"><div><div class="or-top">'+fmtDate(o.date)+' · '+itemsSummary(o.items)+'</div>'+(s>0?'<div class="or-sub">Saldo '+multiCurrencyLine(s)+' de '+multiCurrencyLine(totalNow)+'</div>':'')+'</div><div style="text-align:right">'+chip+'<div>'+fmtBs(totalNow)+'</div></div></div>';
        }).join(''):'<div class="cart-empty" style="padding:20px 4px"><b>Sin compras todavía</b></div>')
      +(can('borrarCliente')&&saldo<=0
        ?'<button class="btn-secondary" data-del-client="'+c.id+'" style="width:100%;margin-top:12px;color:var(--red)">Eliminar cliente</button>'
        :'')
      +'</div>';
  }
  const otros=saldo>0?otherCurrenciesLine(saldo):'';
  return '<div class="client-card" data-id="'+c.id+'">'
    +'<button class="cc-row-btn" data-toggle="'+c.id+'"><div class="cc-row">'
      +'<div class="avatar-round">'+c.name.trim().charAt(0).toUpperCase()+'</div>'
      +'<div class="cc-mid"><div class="cc-name">'+c.name+'</div><div class="cc-sub">'+(c.phone||'Sin teléfono')+'</div></div>'
      +(saldo>0?'<div class="cc-saldo">Debe<br><b style="color:var(--red)">'+fmtBs(saldo)+'</b>'+(otros?'<div style="font-weight:600">'+otros+'</div>':'')+'</div>':'<div class="cc-saldo ok">Al día</div>')
      +'<svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" style="transform:rotate('+(open?'90deg':'0deg')+')"><path d="m9 6 6 6-6 6"/></svg>'
    +'</div></button>'+detail+'</div>';
}
function renderClientes(){
  const items=clients.filter(c=>!clientQuery||matchesQuery(c.name,clientQuery)||matchesQuery(c.phone||'',clientQuery));
  $('clientList').innerHTML=items.length?items.map(clientCardHTML).join(''):'<div class="cart-empty"><b>Sin resultados</b>Prueba con otro nombre.</div>';
}
$('clientSearch').addEventListener('input',e=>{clientQuery=e.target.value;renderClientes()});
$('clientList').addEventListener('input',e=>{
  const inp=e.target.closest('.ab-amount');if(!inp)return;
  updateAbonoSummary(inp.dataset.cid,$('clientList'));
});
$('clientList').addEventListener('click',e=>{
  if(handleAbonoClick(e,$('clientList')))return;
  // eliminar cliente (v19) — con dos protecciones: permiso del rol, y no borrar a quien debe plata
  const del=e.target.closest('[data-del-client]');
  if(del){
    if(!can('borrarCliente')){ askConfirm('No tienes permiso para eliminar clientes. Pídeselo al administrador.',()=>{}); return; }
    const cid=del.dataset.delClient;
    const c=clients.find(x=>x.id===cid);
    if(!c)return;
    const deuda=(debtsByClient().find(d=>d.client.id===cid)||{}).totalBs||0;
    if(deuda>0){
      askConfirm('No puedes eliminar a '+c.name+': todavía debe '+fmtBs(deuda)+'. Cobra o perdona la deuda primero.',()=>{});
      return;
    }
    const compras=orders.filter(o=>o.clientId===cid).length;
    const msg=compras>0
      ? '¿Eliminar a '+c.name+'? Tiene '+compras+' compra'+(compras===1?'':'s')+' en el historial. Las ventas NO se borran (quedan como venta sin cliente), pero perderás sus datos de contacto.'
      : '¿Eliminar a '+c.name+'? Esta acción no se puede deshacer.';
    askConfirm(msg,()=>{
      const i=clients.findIndex(x=>x.id===cid);
      if(i>=0)clients.splice(i,1);
      // las ventas viejas se quedan, pero sin cliente asociado — no se borra historial de ventas
      orders.forEach(o=>{ if(o.clientId===cid){ o.clientId=null; o.clientName=(o.clientName||c.name)+' (eliminado)'; } });
      if(expandedClientId===cid)expandedClientId=null;
      renderClientes();
      if(currentSection==='porcobrar')renderPorCobrar();
      emitCambio('cliente_borrado',{id:cid});
    });
    return;
  }
  const toggle=e.target.closest('.cc-row-btn');if(!toggle)return;
  expandedClientId=expandedClientId===toggle.dataset.toggle?null:toggle.dataset.toggle;
  renderClientes();
});
$('newClientBtn').addEventListener('click',()=>{
  const f=$('newClientForm');const showing=f.style.display!=='none';
  f.style.display=showing?'none':'';
  if(!showing){$('newClientName').value='';$('newClientPhone').value='';$('newClientIdNum').value='';$('newClientAddress').value='';$('newClientName').focus()}
});
$('newClientCancel').addEventListener('click',()=>{$('newClientForm').style.display='none'});
$('newClientSave').addEventListener('click',()=>{
  const name=$('newClientName').value.trim();
  if(!name){$('newClientName').focus();return}
  // id único global (uuidLite), no secuencial — mismo motivo que productos (v1.8): con
  // sincronización entre dispositivos, dos teléfonos generando 'c101' cada uno por su lado
  // serían clientes DISTINTOS con el MISMO id. clientSeq se deja vivo por compatibilidad.
  const c={id:'c_'+uuidLite(),name,phone:$('newClientPhone').value.trim(),idNumber:$('newClientIdNum').value.trim(),address:$('newClientAddress').value.trim()};
  clients.push(c);
  $('newClientForm').style.display='none';
  renderClientes();
  emitCambio('cliente',c);
});

// ===== Confirmación genérica (sí/no) =====
function askConfirm(msg,onYes){confirmCb=onYes;$('confirmMsg').textContent=msg;$('confirmOverlay').classList.add('show')}
function closeConfirmModal(){$('confirmOverlay').classList.remove('show');confirmCb=null}
$('confirmYes').addEventListener('click',()=>{const cb=confirmCb;closeConfirmModal();if(cb)cb()});
$('confirmNo').addEventListener('click',closeConfirmModal);
$('confirmBackdrop').addEventListener('click',closeConfirmModal);

// ===== Abono (pagos mixtos) — usado en Por cobrar y en Clientes =====
// igual que el pago del carrito: un campo por método activo, cada uno en su propia moneda.
// estado del panel de abono, uno por cliente abierto: {mode:'todo'|'abonar', sel, payments:[]}
const abonoState={};
function abonoBlockHTML(cid){
  const st=abonoState[cid]||(abonoState[cid]={mode:'todo',sel:null,payments:[]});
  const active=payMethods.filter(m=>m.on);
  const methodsHtml=active.length
    ? active.map(m=>'<button class="pm-btn ab-method'+(st.sel===m.id?' on':'')+'" data-cid="'+cid+'" data-id="'+m.id+'">'+m.label+'</button>').join('')
    : '<div class="or-sub">Sin métodos activos. Actívalos en Configuración.</div>';
  const listHtml=st.payments.map((p,i)=>{
    const amtFmt=fmtMontoMoneda(p.amount,p.currency);
    return '<div class="pay-list-item"><span>'+p.label+' — <b>'+amtFmt+'</b></span><button class="ab-rm" data-cid="'+cid+'" data-i="'+i+'">✕</button></div>';
  }).join('');
  return '<div class="abono-wrap">'
    +'<button class="btn-primary abono-toggle" data-cid="'+cid+'">Registrar pago</button>'
    +'<div class="abono-form" data-cid="'+cid+'" style="display:none">'
      +'<div class="seg ab-seg" style="margin-bottom:12px"><button class="ab-mode'+(st.mode==='todo'?' on':'')+'" data-cid="'+cid+'" data-m="todo">Pagar todo</button><button class="ab-mode'+(st.mode==='abonar'?' on':'')+'" data-cid="'+cid+'" data-m="abonar">Abonar</button></div>'
      +'<div class="pay-methods">'+methodsHtml+'</div>'
      +'<div class="pay-amount-row"><input type="number" inputmode="decimal" class="ab-amount" data-cid="'+cid+'" placeholder="Monto"><button class="pay-exact-btn ab-exact" data-cid="'+cid+'">Exacto</button></div>'
      +'<button class="pay-add-btn ab-add" data-cid="'+cid+'">+ Agregar pago</button>'
      +(listHtml?'<div class="pay-list">'+listHtml+'</div>':'')
      +'<div class="paymix-summary" data-cid="'+cid+'"></div>'
      +'<div class="form-actions"><button class="btn-primary abono-confirm" data-cid="'+cid+'" style="width:100%">Registrar pago</button></div>'
    +'</div></div>';
}
function abonoSumBs(cid){
  const st=abonoState[cid];if(!st)return 0;
  return st.payments.reduce((s,p)=>s+convertToBs(p.amount,p.currency),0);
}
function updateAbonoSummary(cid,container){
  const summary=container.querySelector('.paymix-summary[data-cid="'+cid+'"]');
  if(!summary)return;
  const st=abonoState[cid]||{};
  const saldo=clientSaldo(cid);
  const entered=abonoSumBs(cid);
  const remain=Math.round((saldo-entered)*100)/100;
  const eps=paymentToleranceBs(st.payments);
  let cls='',txt='Saldo actual: '+fmtBs(saldo);
  if(st.mode==='todo'){
    // "pagar todo": el objetivo siempre es el saldo completo
    if(entered<=eps){cls='';txt='Total a cobrar: '+fmtBs(saldo)+' · '+otherCurrenciesLine(saldo);}
    else if(remain>eps){cls='falta';txt='Falta '+fmtBs(remain)+' · '+otherCurrenciesLine(remain);}
    else if(remain<-eps){cls='cambio';txt='Vuelto '+fmtBs(-remain)+' · '+otherCurrenciesLine(-remain);}
    else{cls='ok';txt='Cubierto ✓ deuda saldada';}
  }else{
    // "abonar": paga una parte; lo que quede sigue como deuda
    if(entered<=eps){cls='';txt='Abona lo que traiga el cliente. Saldo: '+fmtBs(saldo);}
    else if(remain>eps){cls='ok';txt='Abona '+fmtBs(entered)+' · quedará debiendo '+fmtBs(remain);}
    else if(remain<-eps){cls='cambio';txt='Cubre todo · vuelto '+fmtBs(-remain);}
    else{cls='ok';txt='Abona '+fmtBs(entered)+' · queda en cero';}
  }
  summary.className='paymix-summary'+(cls?' '+cls:'');
  summary.textContent=txt;
}
// maneja los clics del panel de abono (mismo flujo que el carrito). Devuelve true si manejó el clic.
function handleAbonoClick(e,container){
  const toggle=e.target.closest('.abono-toggle');
  if(toggle){
    const cid=toggle.dataset.cid;
    const form=container.querySelector('.abono-form[data-cid="'+cid+'"]');
    const showing=form.style.display!=='none';
    form.style.display=showing?'none':'';
    if(!showing)updateAbonoSummary(cid,container);
    return true;
  }
  const modeBtn=e.target.closest('.ab-mode');
  if(modeBtn){
    const cid=modeBtn.dataset.cid;const st=abonoState[cid]||(abonoState[cid]={mode:'todo',sel:null,payments:[]});
    st.mode=modeBtn.dataset.m;
    if(st.mode==='todo')st.payments=[]; // "pagar todo" arranca limpio; el Exacto llenará el saldo
    reRenderAbono(cid,container);
    return true;
  }
  const methodBtn=e.target.closest('.ab-method');
  if(methodBtn){
    const cid=methodBtn.dataset.cid;const st=abonoState[cid];
    st.sel=methodBtn.dataset.id;reRenderAbono(cid,container);
    const inp=container.querySelector('.ab-amount[data-cid="'+cid+'"]');if(inp)inp.focus();
    return true;
  }
  const exact=e.target.closest('.ab-exact');
  if(exact){
    const cid=exact.dataset.cid;const st=abonoState[cid];
    if(!st.sel){updateAbonoSummaryWarn(cid,container,'Elige primero el método');return true;}
    const remainBs=Math.max(0,clientSaldo(cid)-abonoSumBs(cid));
    const cur=payCurrency[st.sel]||'bs';
    const mEx=monedaActivaById(cur);
    const val=cur==='usd'?bsToUsdRaw(remainBs):cur==='usd_bcv'?bsToBcvRaw(remainBs):mEx?(bsToMonedaRaw(remainBs,mEx)||0):remainBs;
    const inp=container.querySelector('.ab-amount[data-cid="'+cid+'"]');
    if(inp)inp.value=(cur==='usd'||cur==='usd_bcv')?val.toFixed(2):Math.round(val);
    return true;
  }
  const add=e.target.closest('.ab-add');
  if(add){
    const cid=add.dataset.cid;const st=abonoState[cid];
    if(!st.sel){updateAbonoSummaryWarn(cid,container,'Elige primero el método');return true;}
    const inp=container.querySelector('.ab-amount[data-cid="'+cid+'"]');
    const val=parseFloat(inp?inp.value:0);
    if(!val||val<=0){updateAbonoSummaryWarn(cid,container,'Escribe el monto recibido');return true;}
    const m=payMethods.find(x=>x.id===st.sel);
    st.payments.push({method:st.sel,label:m.label,currency:payCurrency[st.sel]||'bs',amount:val});
    reRenderAbono(cid,container);
    return true;
  }
  const rm=e.target.closest('.ab-rm');
  if(rm){
    const cid=rm.dataset.cid;const st=abonoState[cid];
    st.payments.splice(parseInt(rm.dataset.i,10),1);reRenderAbono(cid,container);
    return true;
  }
  const conf=e.target.closest('.abono-confirm');
  if(conf){
    const cid=conf.dataset.cid;const st=abonoState[cid];
    const amt=abonoSumBs(cid);
    if(amt<=0){updateAbonoSummaryWarn(cid,container,'Agrega al menos un pago');return true;}
    const saldo=clientSaldo(cid);
    // si lo que trajo el cliente queda dentro del margen de tolerancia (el mismo que ya deja ver
    // "Cubierto ✓ deuda saldada" en el resumen), se aplica el saldo COMPLETO — si no, el redondeo de
    // convertir a otra moneda (ej. Zelle en $) deja un residuo fantasma de 1-2 Bs que nunca se va
    // de Por cobrar aunque la pantalla ya le haya dicho al usuario que la deuda quedó saldada.
    const eps=paymentToleranceBs(st.payments);
    const aplicado=Math.abs(saldo-amt)<=eps ? saldo : Math.min(amt,saldo);
    askConfirm('¿Registrar pago de '+fmtBs(aplicado)+' a la cuenta de '+(clients.find(c=>c.id===cid)||{}).name+'?',()=>{
      applyAbonoToClient(cid,aplicado,st.payments.slice());
      abonoState[cid]={mode:'todo',sel:null,payments:[]};
    });
    return true;
  }
  return false;
}
function updateAbonoSummaryWarn(cid,container,msg){
  const summary=container.querySelector('.paymix-summary[data-cid="'+cid+'"]');
  if(summary){summary.className='paymix-summary falta';summary.textContent=msg;}
}
// re-renderiza solo el bloque de abono de un cliente sin recargar toda la lista (mantiene el resto abierto)
function reRenderAbono(cid,container){
  const wrap=container.querySelector('.abono-form[data-cid="'+cid+'"]');
  if(!wrap)return;
  const holder=wrap.parentElement; // .abono-wrap
  holder.outerHTML=abonoBlockHTML(cid);
  const newForm=container.querySelector('.abono-form[data-cid="'+cid+'"]');
  if(newForm)newForm.style.display='';
  updateAbonoSummary(cid,container);
}

// el abono se aplica a la cuenta del cliente completa, no pedido por pedido:
// paga primero la deuda más antigua hasta agotarla, y lo que sobra pasa a la siguiente (FIFO).
// Cada pedido afectado recibe una NOTA explicando el abono, para que al sumar los productos
// del pedido nadie piense que el sistema está roto ("debía 14.949, abonó, ahora debe 5.000").
function applyAbonoToClient(clientId,montoBs,breakdown){
  let restante=Math.max(0,montoBs);
  if(restante<=0)return;
  const totalAbono=restante;
  const fecha=new Date();
  const metodos=(breakdown&&breakdown.length)?breakdown.map(p=>{
    const amtFmt=fmtMontoMoneda(p.amount,p.currency);
    return p.label+' '+amtFmt;
  }).join(' + '):'';
  // v31 — registro aparte de la transacción completa (no por pedido tocado), para el Cajón: así
  // "Ventas del turno por método" puede sumar esta plata dentro de Zelle/USD efectivo/etc., sin
  // importar cuántas deudas viejas terminó pagando.
  if(breakdown&&breakdown.length){
    abonoPagos.push({fecha,clientId,turnoId:openTurno?openTurno.id:null,montoBs:totalAbono,breakdown:breakdown.slice()});
  }
  const deudas=orders.filter(o=>o.clientId===clientId&&o.status==='fiado'&&saldoOf(o)>0).sort((a,b)=>a.date-b.date);
  let sobranteDeAnterior=0;
  for(const o of deudas){
    if(restante<=0)break;
    const s=saldoOf(o);
    const pago=Math.min(s,restante);
    const saldoPrevio=s;
    o.paidBs=(o.paidBs||0)+pago;
    const saldoNuevo=saldoOf(o);
    // nota legible en el pedido
    if(!o.abonos)o.abonos=[];
    o.abonos.push({
      fecha,
      aplicado:pago,
      deSobrante:sobranteDeAnterior>0?Math.min(sobranteDeAnterior,pago):0,
      saldoPrevio,saldoNuevo,
      totalAbono,
      metodos
    });
    restante-=pago;
    // lo que este pedido no consumió del abono, "arrastra" al siguiente
    sobranteDeAnterior=restante;
    if(saldoNuevo>0)break; // este pedido no se saldó del todo; el abono ya se acabó aquí
  }
  renderPorCobrar();renderClientes();renderNav();
}
// texto de una nota de abono para mostrar dentro del pedido
function abonoNotaHTML(o){
  if(!o.abonos||!o.abonos.length)return '';
  return o.abonos.map(a=>{
    let t=''+fmtDate(a.fecha)+': abono de '+fmtBs(a.aplicado)+' a este pedido';
    if(a.metodos)t+=' ('+a.metodos+')';
    t+='. Bajó de '+fmtBs(a.saldoPrevio)+' a '+fmtBs(a.saldoNuevo)+'.';
    if(a.deSobrante>0)t+=' Parte vino del sobrante de un pago mayor ('+fmtBs(a.totalAbono)+' en total, se repartió entre varias deudas).';
    return '<div class="abono-nota">'+t+'</div>';
  }).join('');
}
function debtCardHTML(g){
  const c=g.client;
  const otros=otherCurrenciesLine(g.saldoBs);
  return '<div class="cfg-block" style="max-width:560px"><div class="cc-row" style="margin-bottom:10px">'
    +'<div class="avatar-round">'+c.name.trim().charAt(0).toUpperCase()+'</div>'
    +'<div class="cc-mid"><div class="cc-name">'+c.name+'</div><div class="cc-sub">'+(c.phone||'Sin teléfono')+'</div></div>'
    +'<div class="cc-saldo">Saldo<br><b style="color:var(--red)">'+fmtBs(g.saldoBs)+'</b>'+(otros?'<div style="font-weight:600">'+otros+'</div>':'')+'</div>'
    +'</div>'
    +'<div style="margin-bottom:10px">'+abonoBlockHTML(c.id)+'</div>'
    +'<div class="or-sub" style="margin-bottom:2px">Se abona primero a la deuda más antigua</div>'
    +g.orders.map(o=>{
      return '<div class="order-row"><div><div class="or-top">'+fmtDate(o.date)+' · '+itemsSummary(o.items)+'</div><div class="or-sub">de '+multiCurrencyLine(debtBs(o))+'</div>'+abonoNotaHTML(o)+'</div><div>'+multiCurrencyLine(saldoOf(o))+'</div></div>';
    }).join('')+'</div>';
}
function renderPorCobrar(){
  const groups=debtsByClient();
  const totalBs=groups.reduce((s,g)=>s+g.saldoBs,0);
  $('pcSummary').innerHTML=groups.length
    ?'<div class="pc-total">'+fmtBs(totalBs)+'</div><div class="pc-sub">'+otherCurrenciesLine(totalBs)+'</div><div class="pc-sub">por cobrar entre '+groups.length+' cliente'+(groups.length===1?'':'s')+'</div>'
    :'<div class="pc-total ok">Bs 0</div><div class="pc-sub">Nadie te debe nada ahora mismo </div>';
  $('pcList').innerHTML=groups.map(debtCardHTML).join('');
}
$('pcList').addEventListener('input',e=>{
  const inp=e.target.closest('.ab-amount');if(!inp)return;
  updateAbonoSummary(inp.dataset.cid,$('pcList'));
});
$('pcList').addEventListener('click',e=>{handleAbonoClick(e,$('pcList'))});

// ===== Pedidos =====
function orderRowHTML(o){
  const client=o.clientId?clients.find(c=>c.id===o.clientId):null;
  const open=expandedOrderId===o.id;
  const s=o.status==='fiado'?saldoOf(o):0;
  let chip=s>0?'<span class="status-chip fiado">Fiado</span>':'<span class="status-chip pagado">Pagado</span>';
  const devTot=totalDevueltoQty(o);
  if(devTot>0){
    // v45 — comparar por LÍNEA (orderTieneDevolvible ya lo hace bien, it.qty vs devueltoDe de esa
    // misma línea) en vez de sumar it.qty de todo el pedido: un pedido con productos normales
    // (unidades) y un producto por peso (Kg) a la vez no se puede comparar sumando ambos en un
    // solo número — mezclaría "unidades" con "Kg" sin sentido.
    chip+='<span class="status-chip" style="background:var(--amber)22;color:var(--amber)">'+(orderTieneDevolvible(o)?'Devol. parcial':'Devuelto')+'</span>';
  }
  let detail='';
  if(open){
    detail='<div class="detail-block">'
      +(o.refCode?'<div class="or-sub" style="margin-bottom:6px">Ref: '+o.refCode+'</div>':'')
      +o.items.map(i=>'<div class="order-row"><div>'+(i.esPeso?formatPeso(i.qty):i.qty+'×')+' '+i.name+'</div><div>'+fmtBs(i.priceBs*i.qty)+'</div></div>').join('')
      +'<div class="order-row" style="border-top:1px solid var(--border);margin-top:4px;padding-top:8px"><div><b>Total</b></div><div><b>'+fmtBs(o.totalBs)+'</b></div></div>'
      +(o.status==='fiado'?'<div class="or-sub" style="margin-top:4px">'+(s>0?'Saldo pendiente: '+fmtBs(s):'Fiado, ya saldado')+'</div>'
        :'<div class="or-sub" style="margin-top:4px">Pagado con '+payLabel(o.method)
          +(o.paymentsBreakdown&&o.paymentsBreakdown.length>1?': '+o.paymentsBreakdown.map(b=>b.label+' '+(fmtMontoMoneda(b.amount,b.currency))).join(' + '):'')
          +'</div>')
      +devolucionesResumenHTML(o)
      +'<div class="form-actions" style="margin-top:10px;justify-content:flex-start;flex-wrap:wrap">'
      +'<button class="btn-secondary resend-btn" data-oid="'+o.id+'">WhatsApp</button>'
      +'<button class="btn-secondary print-btn" data-oid="'+o.id+'">Imprimir</button>'
      +'<button class="btn-secondary email-btn" data-oid="'+o.id+'">Correo</button>'
      +(can('devolver')&&orderTieneDevolvible(o)?'<button class="btn-secondary devol-btn" data-oid="'+o.id+'" style="color:var(--amber)">Devolver</button>':'')
      +'</div>'
      +devolFormHTML(o)
      +'</div>';
  }
  return '<div class="client-card" data-id="'+o.id+'">'
    +'<button class="cc-row-btn" data-toggle="'+o.id+'"><div class="cc-row">'
      +'<div class="date-badge"><span>'+o.date.getDate()+'</span><small>'+o.date.toLocaleDateString('es-VE',{month:'short'})+'</small></div>'
      +'<div class="cc-mid"><div class="cc-name">'+(client?client.name:'Venta directa')+'</div><div class="cc-sub">'+itemsSummary(o.items)+'</div></div>'
      +'<div class="cc-saldo">'+chip+'<b>'+fmtBs(o.totalBs)+'</b><div style="font-size:.72rem;font-weight:600;color:var(--text-faint)">'+fmtUSD(orderTotalUsdBcv(o))+'</div></div>'
      +'<svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" style="transform:rotate('+(open?'90deg':'0deg')+')"><path d="m9 6 6 6-6 6"/></svg>'
    +'</div></button>'+detail+'</div>';
}

// ===== DEVOLUCIONES (v19) =====
// Un cliente trae algo de vuelta: se devuelve la plata (o se le baja la deuda si estaba fiado) y
// la mercancía vuelve al inventario. Se puede devolver TODO o solo algunas unidades.
// Se registra por unidad y a los precios CONGELADOS de la venta original — nunca a los de hoy —
// para no regalar ni cobrar de más si las tasas cambiaron desde que compró.
// Efectos de una devolución:
//   1. El stock del producto vuelve a subir.
//   2. Si la venta estaba PAGADA: se registra la plata que sale (aparece en Contabilidad).
//   3. Si estaba FIADA: se le baja la deuda al cliente, no sale plata del cajón.
//   4. El IVA de lo devuelto se resta del IVA Débito — el SENIAT no te cobra IVA de algo que
//      al final no vendiste.
// v50 — total de un pedido en dólar BCV (nunca oferta), usando lo ya congelado por línea al
// vender (mismo criterio que ya usa buildReceiptText con priceUsdBcv/priceUsd) — para que Pedidos
// pueda mostrar el $ al lado del Bs sin inventar un número nuevo.
function orderTotalUsdBcv(o){
  return o.items.reduce((s,it)=>s+((it.priceUsdBcv!=null?it.priceUsdBcv:it.priceUsd)||0)*it.qty,0);
}
let devolFormOrderId=null;
function devueltoDe(o,productId){
  return (o.devoluciones||[]).reduce((sum,d)=>sum+(d.items[productId]||0),0);
}
function totalDevueltoQty(o){
  return (o.devoluciones||[]).reduce((sum,d)=>sum+Object.values(d.items).reduce((a,b)=>a+b,0),0);
}
function orderTieneDevolvible(o){
  return o.items.some(it=>it.qty-devueltoDe(o,it.productId)>0);
}
function devolucionesResumenHTML(o){
  if(!(o.devoluciones||[]).length)return '';
  return '<div style="margin-top:10px;padding-top:8px;border-top:1px solid var(--border)">'
    +(o.devoluciones).map(d=>{
        const detalle=Object.entries(d.items).map(([pid,q])=>{
          const it=o.items.find(x=>x.productId===pid);
          return (it&&it.esPeso?formatPeso(q):q+'x')+' '+(it?it.name:pid);
        }).join(', ');
        return '<div class="order-row"><div><div class="or-top" style="color:var(--amber)">Devolución · '+fmtDate(d.date)+'</div>'
          +'<div class="or-sub">'+detalle+(d.motivo?' · '+d.motivo:'')+' · '+(d.aplicadoA==='deuda'?'se le bajó la deuda':'se le devolvió la plata')+'</div></div>'
          +'<div style="text-align:right;color:var(--amber)">-'+fmtBs(d.montoBs)+'</div></div>';
      }).join('')
    +'<div class="order-row" style="border-top:1px solid var(--border);margin-top:4px;padding-top:8px"><div><b>Neto de la venta</b></div><div><b>'+fmtBs(o.totalBs-totalDevueltoBs(o))+'</b></div></div>'
    +'</div>';
}
function totalDevueltoBs(o){
  return (o.devoluciones||[]).reduce((s,d)=>s+d.montoBs,0);
}
function devolFormHTML(o){
  if(devolFormOrderId!==o.id)return '';
  const filas=o.items.map(it=>{
    const disp=it.qty-devueltoDe(o,it.productId);
    if(disp<=0)return '<div class="order-row"><div><div class="or-top" style="opacity:.5">'+it.name+'</div><div class="or-sub">Ya devuelto completo</div></div></div>';
    // v45 — un producto por peso se devuelve pidiendo GRAMOS (mismo lenguaje que el modal de
    // agregar al carrito), no Kg fraccionarios en un input entero: el max/value viajan en gramos
    // y devolSave (más abajo) los vuelve a dividir entre 1000 antes de armar `cantidades`.
    const inputAttrs=it.esPeso
      ? 'inputmode="decimal" step="1" max="'+Math.round(disp*1000)+'"'
      : 'inputmode="numeric" max="'+disp+'"';
    return '<div class="order-row"><div><div class="or-top">'+it.name+'</div>'
      +'<div class="or-sub">'+fmtBs(it.priceBs)+(it.esPeso?' /Kg':' c/u')+' · puede devolver hasta '+(it.esPeso?formatPeso(disp):disp)+'</div></div>'
      +'<input class="cfg-input devol-qty" data-pid="'+it.productId+'" data-espeso="'+(it.esPeso?'1':'0')+'" type="number" '+inputAttrs+' min="0" value="0" placeholder="'+(it.esPeso?'gramos':'')+'" style="width:80px"></div>';
  }).join('');
  const estaFiada=o.status==='fiado'&&saldoOf(o)>0;
  return '<div class="cfg-block" style="margin-top:12px">'
    +'<h4>Devolver productos</h4>'
    +'<div class="desc">Pon cuántas unidades trae de vuelta. Se calcula con los precios de <b>esa</b> venta, no con los de hoy.</div>'
    +filas
    +'<div class="cfg-row" style="margin-top:10px"><label>Motivo (opcional)</label><input class="cfg-input" id="devolMotivo" type="text" placeholder="Ej: venía dañado"></div>'
    +'<div class="desc" style="margin-top:8px">'+(estaFiada
        ? 'Esta venta está fiada, así que la devolución le <b>baja la deuda</b> al cliente. No sale plata del cajón.'
        : 'Esta venta ya está pagada, así que le <b>devuelves la plata</b> al cliente. Se registra como salida en Contabilidad.')+'</div>'
    +'<div class="form-actions" style="margin-top:12px"><button class="btn-secondary" id="devolCancel">Cancelar</button><button class="btn-primary" id="devolSave" data-oid="'+o.id+'">Confirmar devolución</button></div>'
    +'</div>';
}
function procesarDevolucion(o,cantidades,motivo){
  let montoBs=0,ivaDebitoBs=0,costoBs=0;
  const items={};
  Object.entries(cantidades).forEach(([pid,q])=>{
    if(q<=0)return;
    const it=o.items.find(x=>x.productId===pid);
    if(!it)return;
    items[pid]=q;
    montoBs+=it.priceBs*q;                        // precio CONGELADO de la venta original
    ivaDebitoBs+=(it.ivaDebitoBs||0)*q;           // el IVA de lo devuelto ya no se le debe al SENIAT
    costoBs+=(it.costBs||0)*q;
    const prod=products.find(x=>x.id===pid);
    if(prod){prod.stock+=q;registrarMovimientoStock(pid,q);}   // la mercancía vuelve al inventario
  });
  if(!Object.keys(items).length)return false;
  const estaFiada=o.status==='fiado'&&saldoOf(o)>0;
  o.devoluciones=o.devoluciones||[];
  o.devoluciones.push({
    id:'d'+Date.now(),date:new Date(),items,montoBs,ivaDebitoBs,costoBs,motivo:motivo||'',
    aplicadoA:estaFiada?'deuda':'efectivo'
  });
  // si estaba fiada, la devolución cuenta como un abono: le baja el saldo pendiente.
  // El saldo real de la app se lleva en o.paidBs (ver saldoOf), así que ahí es donde hay que sumar.
  if(estaFiada){
    const aplicado=Math.min(montoBs,saldoOf(o));
    o.paidBs=(o.paidBs||0)+aplicado;
    o.abonos=o.abonos||[];
    o.abonos.push({date:new Date(),amountBs:aplicado,nota:'Devolución de mercancía',esDevolucion:true});
    if(saldoOf(o)<=0)o.status='pagado';
  }
  return true;
}
function renderPedidos(){
  const list=orders.slice().sort((a,b)=>b.date-a.date).filter(o=>{
    if(pedidosFilter==='todos')return true;
    if(pedidosFilter==='pagado')return o.status==='pagado'||saldoOf(o)<=0;
    return o.status==='fiado'&&saldoOf(o)>0;
  });
  if(!list.length){$('pedidosList').innerHTML='<div class="cart-empty"><b>Sin pedidos</b>Todavía no hay ventas en esta categoría.</div>';return}
  let html='',lastKey=null;
  list.forEach(o=>{
    const key=fmtDate(o.date);
    if(key!==lastKey){html+='<div class="date-sep">'+key+'</div>';lastKey=key}
    html+=orderRowHTML(o);
  });
  $('pedidosList').innerHTML=html;
}
$('pedidosChips').innerHTML=['todos','pagado','fiado'].map(f=>'<button class="chip'+(f==='todos'?' on':'')+'" data-f="'+f+'">'+(f==='todos'?'Todos':f==='pagado'?'Pagados':'Fiados')+'</button>').join('');
$('pedidosChips').addEventListener('click',e=>{const b=e.target.closest('.chip');if(!b)return;pedidosFilter=b.dataset.f;document.querySelectorAll('#pedidosChips .chip').forEach(x=>x.classList.toggle('on',x===b));renderPedidos()});
$('pedidosList').addEventListener('click',e=>{
  const resend=e.target.closest('.resend-btn');
  if(resend){ const o=orders.find(x=>x.id===resend.dataset.oid); if(o)shareReceipt(o); return; }
  const printBtn=e.target.closest('.print-btn');
  if(printBtn){ const o=orders.find(x=>x.id===printBtn.dataset.oid); if(o)printReceipt(o); return; }
  const emailBtn=e.target.closest('.email-btn');
  if(emailBtn){ const o=orders.find(x=>x.id===emailBtn.dataset.oid); if(o)emailReceipt(o); return; }
  const devolBtn=e.target.closest('.devol-btn');
  if(devolBtn){ devolFormOrderId=devolBtn.dataset.oid; renderPedidos(); return; }
  if(e.target.closest('#devolCancel')){ devolFormOrderId=null; renderPedidos(); return; }
  const devolSave=e.target.closest('#devolSave');
  if(devolSave){
    const o=orders.find(x=>x.id===devolSave.dataset.oid);
    if(!o)return;
    const cantidades={};
    document.querySelectorAll('.devol-qty').forEach(inp=>{
      // v45 — bug real corregido: un producto por peso manda gramos con decimales/fracciones y
      // parseInt lo truncaba a 0 en silencio (nunca se registraba la devolución). parseFloat +
      // dividir entre 1000 al final para volver a Kg, que es la unidad en que vive it.qty.
      const esPeso=inp.dataset.espeso==='1';
      const raw=Math.min(parseFloat(inp.value)||0,parseFloat(inp.max)||0);
      const q=esPeso?raw/1000:raw;
      if(q>0)cantidades[inp.dataset.pid]=q;
    });
    if(!Object.keys(cantidades).length){ askConfirm('Pon al menos una unidad para devolver.',()=>{}); return; }
    const motivo=($('devolMotivo')&&$('devolMotivo').value.trim())||'';
    askConfirm('¿Confirmar la devolución? La mercancía vuelve al inventario y no se puede deshacer.',()=>{
      procesarDevolucion(o,cantidades,motivo);
      devolFormOrderId=null;
      renderPedidos();
      if(currentSection==='porcobrar')renderPorCobrar();
      renderGrid();
    });
    return;
  }
  const toggle=e.target.closest('.cc-row-btn');if(!toggle)return;
  expandedOrderId=expandedOrderId===toggle.dataset.toggle?null:toggle.dataset.toggle;
  renderPedidos();
});

// ===== Fiar (dentro del carrito de Venta) =====
$('saleType').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  saleType=b.dataset.t;
  document.querySelectorAll('#saleType button').forEach(x=>x.classList.toggle('on',x===b));
  $('pay').style.display=saleType==='pagado'?'':'none';
  renderFiarList();
  if(saleType==='pagado')renderPay();
  renderCart();
});
function renderFiarSelected(){
  if(!fiarClientId){$('fiarSelected').style.display='none';return}
  const c=clients.find(x=>x.id===fiarClientId);
  $('fiarSelected').style.display='';
  $('fiarSelected').innerHTML='<div class="sel-chip">'+(c?c.name:'')+'<button id="fiarClear">✕</button></div>';
}
function renderFiarList(){
  const lbl=$('fiarLabel');
  if(lbl)lbl.textContent=saleType==='fiado'?'¿A quién se le fía? *':'Cliente (opcional)';
  // el botón del formulario de cliente nuevo decía "Guardar y fiar" SIEMPRE, incluso cuando la
  // venta se estaba pagando completa — hacía dudar de si guardar al cliente iba a dejar la venta fiada.
  const saveBtn=$('fiarNewSave');
  if(saveBtn)saveBtn.textContent=saleType==='fiado'?'Guardar y fiar':'Guardar cliente';
  const q=fiarQuery.trim();
  const items=clients.filter(c=>!q||matchesQuery(c.name,q)||matchesQuery(c.phone||'',q));
  let html=items.map(c=>'<button data-id="'+c.id+'">'+c.name+'<span>'+(c.phone||'')+'</span></button>').join('');
  if(q&&!items.find(c=>norm(c.name)===norm(q)))html+='<button class="new" data-new="1">+ Agregar "'+q+'" como cliente nuevo</button>';
  $('fiarList').innerHTML=html||'<div class="cart-empty" style="padding:14px"><b>Sin clientes</b>Escribe un nombre para agregarlo, o usa "+ Nuevo cliente" abajo.</div>';
  renderFiarSelected();
}
$('fiarSearch').addEventListener('input',e=>{fiarQuery=e.target.value;renderFiarList()});
$('fiarList').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  if(b.dataset.new){
    const name=fiarQuery.trim();if(!name)return;
    const c={id:'c_'+uuidLite(),name,phone:''}; // id único global — ver nota en newClientSave
    clients.push(c);fiarClientId=c.id;fiarQuery='';$('fiarSearch').value='';
    emitCambio('cliente',c);
  } else fiarClientId=b.dataset.id;
  renderFiarList();renderCart();
});
$('fiarSelected').addEventListener('click',e=>{
  if(e.target.closest('#fiarClear')){fiarClientId=null;renderFiarSelected();renderCart()}
});
$('fiarNewBtn').addEventListener('click',()=>{
  const f=$('fiarNewForm');const showing=f.style.display!=='none';
  f.style.display=showing?'none':'';
  if(!showing){$('fiarNewName').value='';$('fiarNewPhone').value='';$('fiarNewIdNum').value='';$('fiarNewAddress').value='';$('fiarNewName').focus()}
});
$('fiarNewCancel').addEventListener('click',()=>{$('fiarNewForm').style.display='none'});
$('fiarNewSave').addEventListener('click',()=>{
  const name=$('fiarNewName').value.trim();
  if(!name){$('fiarNewName').focus();return}
  const c={id:'c_'+uuidLite(),name,phone:$('fiarNewPhone').value.trim(),idNumber:$('fiarNewIdNum').value.trim(),address:$('fiarNewAddress').value.trim()}; // id único global — ver nota en newClientSave
  clients.push(c);fiarClientId=c.id;fiarQuery='';$('fiarSearch').value='';
  $('fiarNewForm').style.display='none';
  renderFiarList();renderCart();
  emitCambio('cliente',c);
});

// ===== Identidad — Bienvenida / Iniciar sesión / Crear cuenta (v15, cuenta real desde v1.1 del backend) =====
// "Iniciar sesión" y "Crear cuenta" validan de verdad contra /login y /registro (ver
// registrarNegocio()/loginNegocio() arriba). Sin internet, ambas fallan con un mensaje
// claro — a propósito no hay fallback local "entra igual", porque una cuenta que a veces
// valida y a veces no es peor que no tener cuenta. cfg/signupData se siguen guardando
// EN MEMORIA (IndexedDB de este teléfono) para dejar la app lista con el nombre del negocio.
let authStep='welcome'; // welcome | login | recover | signup-credentials | signup-business | restaurar | restaurar-activar
let signupData={email:'',accountType:'negocio',name:'',idNumber:'',phone:'',address:''};
let _suPendingPass=''; // contraseña en tránsito entre los 2 pasos del signup — nunca se persiste (no va en signupData/idbSet)
// respaldos ofrecidos tras un login en un dispositivo sin ventas reales (ver 'restaurar' abajo)
let respaldosDisponibles=[];
// v4 — ¿ya entró alguien en este dispositivo? Se guarda en IndexedDB (ver schedulePersist).
// Antes de v4 esto no existía: el gate se escondía solo con una clase CSS, así que al recargar
// la app SIEMPRE volvía a pedir crear cuenta o iniciar sesión aunque los datos estuvieran ahí.
// Ojo: esto NO es autenticación real — es "recordar que ya pasaste por aquí en este teléfono".
// La autenticación de verdad llega con las cuentas de negocio del backend.
let authed=false;
function authRow(label,id,type,placeholder,labelId){
  return '<div class="cfg-row"><label'+(labelId?' id="'+labelId+'"':'')+'>'+label+'</label><input class="cfg-input" id="'+id+'" type="'+type+'" placeholder="'+placeholder+'" autocomplete="off"></div>';
}
const eyeOpenSvg='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg>';
const eyeOffSvg='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"/><path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c7 0 10.5 7 10.5 7a13.6 13.6 0 0 1-3.1 4"/><path d="M6.6 6.6C3.9 8.3 1.5 12 1.5 12s3.5 7 10.5 7a10.5 10.5 0 0 0 4.4-.9"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>';
// campo de contraseña con botón "mostrar/ocultar" — pedido explícito de Jonathan tras probar
// el registro/login reales: sin esto es fácil escribir mal la contraseña sin darse cuenta.
function authPassRow(label,id,placeholder,labelId){
  return '<div class="cfg-row"><label'+(labelId?' id="'+labelId+'"':'')+'>'+label+'</label>'
    +'<div class="auth-pass-wrap"><input class="cfg-input" id="'+id+'" type="password" placeholder="'+placeholder+'" autocomplete="off">'
    +'<button type="button" class="auth-pass-toggle" data-pass-toggle="'+id+'" aria-label="Mostrar contraseña">'+eyeOpenSvg+'</button></div></div>';
}
function authBackBtn(target){
  return '<button class="auth-back" data-back="'+target+'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M15 18l-6-6 6-6"/></svg>Volver</button>';
}
function authSteps(n){
  return '<div class="auth-steps"><span class="'+(n>=1?'on':'')+'"></span><span class="'+(n>=2?'on':'')+'"></span></div>';
}
function authErr(msg){
  const e=$('authErr');if(!e)return;
  e.textContent=msg||'';e.classList.toggle('show',!!msg);
}
function renderAuth(){
  const el=$('authCard');
  const isNeg=signupData.accountType==='negocio';
  if(authStep==='welcome'){
    el.innerHTML=
      '<div class="auth-brand"><div class="mark brand-mark">F</div><div class="nm brand-name">FrixPOS</div></div>'
      +'<div class="auth-h1">Vende, cobra y controla tu negocio</div>'
      +'<div class="auth-sub">El punto de venta pensado para bodegas y tiendas venezolanas. Solo para llevar tu negocio — no manejamos tu dinero ni tus cuentas bancarias.</div>'
      +'<div class="auth-actions">'
        +'<button class="btn-primary" id="authGoSignup">Crear cuenta</button>'
        +'<button class="btn-secondary" id="authGoLogin">Iniciar sesión</button>'
      +'</div>';
  } else if(authStep==='login'){
    el.innerHTML=authBackBtn('welcome')
      +'<div class="auth-h1">Inicia sesión</div>'
      +'<div class="auth-sub">Entra con el correo de tu cuenta.</div>'
      +authRow('Correo','authLoginEmail','email','tucorreo@ejemplo.com')
      +authPassRow('Contraseña','authLoginPass','Tu contraseña')
      +'<div class="auth-err" id="authErr"></div>'
      +'<button class="btn-primary" id="authLoginBtn">Entrar</button>'
      +'<div class="auth-foot"><button id="authForgot">¿Olvidaste tu contraseña?</button></div>'
      +'<div class="auth-foot">¿No tienes cuenta? <button id="authToSignup">Crear cuenta</button></div>';
  } else if(authStep==='recover'){
    el.innerHTML=authBackBtn('login')
      +'<div class="auth-h1">Recupera tu contraseña</div>'
      +'<div class="auth-sub">Escribe el correo de tu cuenta y te mandamos instrucciones para elegir una nueva.</div>'
      +authRow('Correo','authRecoverEmail','email','tucorreo@ejemplo.com')
      +'<div class="auth-err" id="authErr"></div>'
      +'<div class="paymix-summary ok" id="authRecoverOk" style="display:none;margin-bottom:12px"></div>'
      +'<button class="btn-primary" id="authRecoverBtn">Enviar instrucciones</button>'
      +'<div class="auth-foot">¿No te llegó el correo? Revisa tu bandeja de spam, y si sigue sin llegar <a href="/soporte" target="_blank" rel="noopener">escríbenos a soporte</a>.</div>';
  } else if(authStep==='signup-credentials'){
    el.innerHTML=authBackBtn('welcome')+authSteps(1)
      +'<div class="auth-h1">Crea tu cuenta</div>'
      +'<div class="auth-sub">Con esto vas a entrar a tu FrixPOS la próxima vez.</div>'
      +authRow('Correo','suEmail','email','tucorreo@ejemplo.com')
      +authPassRow('Contraseña','suPass','Mínimo 8 caracteres')
      +authPassRow('Repetir contraseña','suPass2','Escríbela de nuevo')
      +'<div class="auth-err" id="authErr"></div>'
      +'<button class="btn-primary" id="suContinueBtn">Continuar</button>';
  } else if(authStep==='signup-business'){
    el.innerHTML=authBackBtn('signup-credentials')+authSteps(2)
      +'<div class="auth-h1">Cuéntanos de tu negocio</div>'
      +'<div class="auth-sub">Así dejamos FrixPOS listo con tu nombre y tus datos.</div>'
      +'<div class="seg" id="acctType" style="margin-bottom:16px"><button data-t="negocio" class="'+(isNeg?'on':'')+'">Negocio</button><button data-t="emprendedor" class="'+(isNeg?'':'on')+'">Emprendedor</button></div>'
      +authRow(isNeg?'Nombre del negocio':'Nombre y apellido','suName','text',isNeg?'Nombre de tu negocio':'Tu nombre y apellido','suNameLbl')
      +authRow(isNeg?'RIF':'Cédula','suId','text',isNeg?'J-12345678-9':'V-12345678','suIdLbl')
      +authRow('Teléfono','suPhone','tel','0414-1234567')
      +authRow('Dirección','suAddress','text','Calle, sector, ciudad')
      +'<div class="auth-err" id="authErr"></div>'
      +'<button class="btn-primary" id="suFinishBtn">Crear cuenta</button>';
  } else if(authStep==='restaurar'){
    // v1.5 del backend — se llega acá desde el login cuando este dispositivo no tiene ventas
    // reales todavía Y la cuenta sí tiene respaldos guardados (ver el handler de authLoginBtn).
    const ultimo=respaldosDisponibles[0]||{};
    const s=ultimo.resumen||{};
    // v1.14 — se muestra DE QUÉ CUENTA es. Sin esto, alguien que acaba de cambiar de cuenta en
    // el mismo teléfono no tiene cómo saber si el respaldo que le ofrecen es el suyo o el de la
    // cuenta anterior (duda real de Jonathan). El respaldo siempre se pide con el token de la
    // sesión actual y el servidor solo devuelve los de ESE wp_user_id — pero eso hay que
    // poder verlo en pantalla, no solo confiar en que está bien por dentro.
    // v1.14 — se dice de una si puede recuperarlo o no, en vez de dejarlo tocar "Recuperar" para
    // que el servidor le responda que necesita código. Su respaldo sigue guardado igual: el día
    // que active, lo recupera completo — eso también se dice acá, no se esconde.
    const puedeRecuperar=esNegocioActivo();
    el.innerHTML=
      '<div class="auth-h1">Encontramos un respaldo tuyo</div>'
      +'<div class="auth-sub">De <b>'+(puntoAccountEmail||'tu cuenta')+'</b>, del '+(ultimo.fecha||'')+'.</div>'
      +'<div class="cfg-block" style="margin:14px 0 18px;padding:12px"><div class="sub">'
        +(s.productos||0)+' productos · '+(s.pedidos||0)+' pedidos · '+(s.clientes||0)+' clientes'
        +(s.porCobrarBs?' · '+fmtBs(s.porCobrarBs)+' por cobrar':'')
      +'</div></div>'
      +(puedeRecuperar
        ? '<div class="auth-sub" style="margin-bottom:14px">Si empiezas de cero ahora, tu respaldo se queda guardado igual — lo puedes recuperar después desde Configuración → Negocio → Respaldo.</div>'
        : '<div class="auth-sub" style="margin-bottom:14px;color:var(--amber)">Recuperar tu información en un teléfono necesita un código de activación. <b>Tu respaldo no se pierde</b>: se queda guardado, y el día que actives lo recuperas completo.</div>')
      +'<div class="auth-err" id="authErr"></div>'
      +(puedeRecuperar
        ? '<button class="btn-primary" id="restaurarBtn" style="width:100%">Recuperar datos</button>'
          +'<button class="btn-secondary" id="empezarCeroBtn" style="width:100%;margin-top:10px">Empezar de cero</button>'
        : '<button class="btn-primary" id="restaurarBtn" style="width:100%">Tengo un código, activarlo</button>'
          +'<button class="btn-secondary" id="empezarCeroBtn" style="width:100%;margin-top:10px">Seguir sin recuperar</button>');
  } else if(authStep==='restaurar-activar'){
    el.innerHTML=authBackBtn('restaurar')
      +'<div class="auth-h1">Activa tu código</div>'
      +'<div class="auth-sub">Recuperar el respaldo completo necesita un código de activación.</div>'
      +authRow('Código de activación','restaurarCodigo','text','Ej: FRIXPOS-AB12-CD34')
      +'<div class="auth-err" id="authErr"></div>'
      +'<button class="btn-primary" id="restaurarActivarBtn" style="width:100%">Activar y recuperar</button>';
  }
}
$('authGate').addEventListener('click',e=>{
  const passToggle=e.target.closest('[data-pass-toggle]');
  if(passToggle){
    const input=$(passToggle.dataset.passToggle);
    const show=input.type==='password';
    input.type=show?'text':'password';
    passToggle.innerHTML=show?eyeOffSvg:eyeOpenSvg;
    passToggle.setAttribute('aria-label',show?'Ocultar contraseña':'Mostrar contraseña');
    input.focus();
    return;
  }
  const back=e.target.closest('[data-back]');
  if(back){authStep=back.dataset.back;renderAuth();return}
  if(e.target.closest('#authGoSignup')){authStep='signup-credentials';renderAuth();return}
  if(e.target.closest('#authGoLogin')){authStep='login';renderAuth();return}
  if(e.target.closest('#authToSignup')){authStep='signup-credentials';renderAuth();return}
  if(e.target.closest('#authLoginBtn')){
    const email=$('authLoginEmail').value.trim(),pass=$('authLoginPass').value;
    if(!email||!pass){authErr('Escribe tu correo y tu contraseña.');return}
    authErr('');
    const btn=$('authLoginBtn');btn.disabled=true;btn.textContent='Entrando...';
    loginNegocio(email,pass).then(async r=>{
      if(r.recargando)return; // el teléfono tenía datos de otra cuenta — ya va a recargar solo
      btn.disabled=false;btn.textContent='Entrar';
      if(!r.ok){authErr(r.error);return}
      const n=r.negocio;
      if(n&&n.nombre){
        cfg.bizName=n.nombre;$('bizName').value=n.nombre;applyBrandName(n.nombre);
        cfg.bizRif=n.id_number||'';cfg.bizAddress=n.address||'';cfg.bizPhone=n.phone||'';
        $('bizRif').value=cfg.bizRif;$('bizAddress').value=cfg.bizAddress;$('bizPhone').value=cfg.bizPhone;
        cfgSnapshots.negocio=captureGroup('negocio');
      }
      // v1.5 del backend — este dispositivo todavía no tiene ninguna venta real (los datos de
      // muestra no cuentan, no tienen uuid): si la cuenta sí tiene respaldos, se le pregunta
      // antes de entrar directo con el catálogo de muestra. En el teléfono de siempre, con
      // ventas reales ya guardadas, esto no se pregunta nunca — entra derecho como hoy.
      //
      // v1.14 — pero "sin ventas" no alcanzaba como señal de teléfono vacío. Alguien que cargó
      // su catálogo con fotos y todavía no ha vendido nada, al cerrar sesión y volver a entrar
      // se topaba con la pantalla de "recuperar respaldo" — con sus datos intactos ahí mismo,
      // detrás. Peor aún si era gratis: recuperar le pedía código, y la otra opción decía que
      // iba a entrar "sin tu catálogo real". Susto puro y sin motivo, porque cerrar sesión
      // NUNCA borra nada (ver el botón Salir: solo apaga authed).
      // Ahora se pregunta solo si este teléfono de verdad no tiene nada del dueño: ni ventas
      // reales, ni productos creados por él (los de muestra son 'p1'..'p12'; los suyos 'p_...'),
      // ni haber estado ya logueado con esta misma cuenta antes.
      const tieneVentasReales=orders.some(o=>o.uuid);
      const tieneProductosPropios=products.some(p=>String(p.id||'').indexOf('p_')===0);
      const dispositivoVacio=!tieneVentasReales && !tieneProductosPropios && !r.mismoDispositivo;
      if(dispositivoVacio){
        const data=await listarRespaldos();
        if(data.ok && data.respaldos && data.respaldos.length){
          respaldosDisponibles=data.respaldos;
          authStep='restaurar';renderAuth();
          return;
        }
      }
      enterApp(false);
    });
    return;
  }
  if(e.target.closest('#authForgot')){authStep='recover';renderAuth();return}
  if(e.target.closest('#authRecoverBtn')){
    const email=$('authRecoverEmail').value.trim();
    if(!email){authErr('Escribe tu correo.');return}
    authErr('');
    const btn=$('authRecoverBtn');btn.disabled=true;btn.textContent='Enviando...';
    recuperarPassword(email).then(r=>{
      btn.disabled=false;btn.textContent='Enviar instrucciones';
      const ok=$('authRecoverOk');
      ok.textContent=r.message||'Si ese correo tiene una cuenta, te mandamos instrucciones.';
      ok.style.display='block';
    });
    return;
  }
  if(e.target.closest('#empezarCeroBtn')){
    askConfirm('Vas a entrar con un catálogo de muestra en este dispositivo, sin tu catálogo, pedidos ni clientes reales. Puedes recuperarlos cuando quieras desde Configuración → Negocio → Respaldo. ¿Continuar?',()=>{
      enterApp(true); // primera vez real en este dispositivo — vale la pena mostrarle la guía
    });
    return;
  }
  if(e.target.closest('#restaurarBtn')){
    // v1.14 — sin negocio activo el servidor va a responder que hace falta código, así que se
    // va derecho a pedirlo en vez de simular un intento de recuperación que ya sabemos que falla.
    if(!esNegocioActivo()){ authStep='restaurar-activar';renderAuth();return; }
    const btn=$('restaurarBtn');btn.disabled=true;btn.textContent='Recuperando...';
    restaurarRespaldo(respaldosDisponibles[0].id).then(r=>{
      if(r.ok){ rehydrateUI(); refrescarUI(); enterApp(false); return; }
      btn.disabled=false;btn.textContent='Recuperar datos';
      if(r.requiereActivacion){ authStep='restaurar-activar';renderAuth();return; }
      authErr(r.error);
    });
    return;
  }
  if(e.target.closest('#restaurarActivarBtn')){
    const codigo=$('restaurarCodigo').value;
    if(!codigo||!codigo.trim()){authErr('Escribe tu código de activación.');return}
    authErr('');
    const btn=$('restaurarActivarBtn');btn.disabled=true;btn.textContent='Activando...';
    activarNegocio(codigo, cfg.bizName).then(r=>{
      if(!r.ok){ btn.disabled=false;btn.textContent='Activar y recuperar'; authErr(r.error); return; }
      restaurarRespaldo(respaldosDisponibles[0].id).then(r2=>{
        if(r2.ok){ rehydrateUI(); refrescarUI(); enterApp(false); return; }
        btn.disabled=false;btn.textContent='Activar y recuperar';
        authErr(r2.error);
      });
    });
    return;
  }
  if(e.target.closest('#suContinueBtn')){
    const email=$('suEmail').value.trim(),pass=$('suPass').value,pass2=$('suPass2').value;
    if(!email||!pass){authErr('Completa tu correo y tu contraseña.');return}
    if(pass.length<8){authErr('La contraseña debe tener al menos 8 caracteres.');return}
    if(pass!==pass2){authErr('Las contraseñas no coinciden.');return}
    signupData.email=email;_suPendingPass=pass;authErr('');authStep='signup-business';renderAuth();return;
  }
  if(e.target.closest('#suFinishBtn')){
    const name=$('suName').value.trim(),idNum=$('suId').value.trim(),phone=$('suPhone').value.trim(),address=$('suAddress').value.trim();
    if(!name){authErr(signupData.accountType==='negocio'?'Escribe el nombre de tu negocio.':'Escribe tu nombre.');return}
    authErr('');
    signupData.name=name;signupData.idNumber=idNum;signupData.phone=phone;signupData.address=address;
    const pass=_suPendingPass;
    const btn=$('suFinishBtn');btn.disabled=true;btn.textContent='Creando...';
    registrarNegocio({email:signupData.email,password:pass,nombre:name,tipo:signupData.accountType,id_number:idNum,phone,address}).then(r=>{
      _suPendingPass='';
      if(r.recargando)return; // el teléfono tenía datos de otra cuenta — ya va a recargar solo
      btn.disabled=false;btn.textContent='Crear cuenta';
      if(!r.ok){authErr(r.error);return}
      // v4 — esto antes corría SOLO para cuentas tipo "negocio". Un emprendedor terminaba con
      // cfg.bizName vacío, y por eso sus comprobantes salían encabezados "FrixPOS" en vez de con
      // su nombre, sin cédula y sin dirección. Los datos son los mismos campos (nombre/cédula
      // en vez de razón social/RIF), así que van al mismo lugar en los dos casos.
      cfg.bizName=name;$('bizName').value=name;applyBrandName(name);
      cfg.bizRif=idNum;cfg.bizAddress=address;cfg.bizPhone=phone;
      $('bizRif').value=idNum;$('bizAddress').value=address;$('bizPhone').value=phone;
      cfgSnapshots.negocio=captureGroup('negocio'); // lo que se llena al crear la cuenta ya queda "guardado"
      enterApp(true);
    });
    return;
  }
  const typeBtn=e.target.closest('#acctType button');
  if(typeBtn){
    signupData.accountType=typeBtn.dataset.t;
    const neg=signupData.accountType==='negocio';
    document.querySelectorAll('#acctType button').forEach(b=>b.classList.toggle('on',b===typeBtn));
    $('suNameLbl').textContent=neg?'Nombre del negocio':'Nombre y apellido';
    $('suIdLbl').textContent=neg?'RIF':'Cédula';
    $('suName').placeholder=neg?'Nombre de tu negocio':'Tu nombre y apellido';
    $('suId').placeholder=neg?'J-12345678-9':'V-12345678';
    return;
  }
});
// Enter dentro de cualquier campo de Identidad dispara el botón principal del paso actual —
// antes solo se podía tocar el botón, y en un teléfono con teclado eso rompe el flujo natural.
$('authGate').addEventListener('keydown',e=>{
  if(e.key!=='Enter') return;
  if(!e.target.closest('input')) return;
  const btn=$('authCard').querySelector('.btn-primary');
  if(btn){ e.preventDefault(); btn.click(); }
});
function enterApp(firstTime){
  authed=true;
  schedulePersist(true); // v4 — se guarda YA, no en el autosave: si cierran la app de una, la sesión igual queda
  $('authGate').classList.add('hide');
  // Activación y Respaldo se pintaron al arrancar, ANTES de que existiera puntoAccountToken:
  // sin esto, alguien que acababa de crear su cuenta o iniciar sesión entraba a Configuración y
  // el bloque de Respaldo seguía diciéndole "crea una cuenta o inicia sesión", como si no
  // hubiera entrado. Se repintan ya con la sesión en mano.
  try{ renderActivacion(); renderRespaldo(); renderPerfil(); renderCatalogoDigital(); }catch(e){}
  if(firstTime)setTimeout(startTour,350);
  conectarTiempoReal(); // no-op seguro si no hay negocio activo, o si ya hay conexión abierta
}
document.querySelector('.out').addEventListener('click',()=>{
  authed=false; schedulePersist(true);
  authStep='welcome';renderAuth();
  $('authGate').classList.remove('hide');$('authGate').classList.remove('booting');closeDrawer();
  desconectarTiempoReal();
});
renderAuth();

// ===== Guía paso a paso — cerrable, arranca tras crear cuenta (v15) =====
// Las tarjetas de bienvenida: qué es FrixPOS, en 4 pantallas. Terminan en Configuración a
// propósito — es lo primero que toca hacer de verdad — y desde ahí engancha el paso a paso.
const tourSteps=[
  {icon:'',title:'Bienvenido a FrixPOS',text:'Con FrixPOS vendes, cobras en la moneda que sea, fías, llevas tu inventario y sabes cuánto ganaste <b>de verdad</b>.<br><br>Todo funciona <b>sin internet</b>: si se cae la señal, tú sigues vendiendo igual. La señal solo hace falta para respaldar.',go:'venta'},
  {icon:'',title:'Así es tu día',text:'<b>1.</b> Abres tu caja con el efectivo que traes de fondo.<br><b>2.</b> Vendes: tocas productos y cobras.<br><b>3.</b> Al que no te paga hoy, le fías a su nombre.<br><b>4.</b> Cierras la caja contando la gaveta, y FrixPOS te dice si cuadra.<br><br>Ese cierre es el que te avisa si algo se perdió el mismo día, no a fin de mes cuando ya no te acuerdas.',go:'venta'},
  {icon:'',title:'De dónde sale tu ganancia',text:'Tú cargas <b>cuánto te costó</b> y <b>cuánto quieres ganar</b>, y FrixPOS saca el precio solo:<br><br><span style="opacity:.85">costo + gastos del local + tu ganancia = precio</span><br><br>Por eso FrixPOS te muestra la ganancia ya con el costo descontado: <b>vender mucho no es ganar mucho</b>. Y ojo con el IVA que cobras — esa plata nunca fue tuya, la estás guardando para el SENIAT.',go:'catalogo'},
  {icon:'',title:'Empecemos por Configuración',text:'Es lo primero de todo: ponle nombre a tu negocio y carga la tasa del día, que es de donde salen todos tus precios.<br><br>De aquí en adelante te voy <b>señalando cada cosa</b> en pantalla y explicándote para qué sirve. Lo puedes saltar cuando quieras.',go:'config',final:true}
];
let tourStep=0,tourActive=false;
function renderTour(){
  const s=tourSteps[tourStep];
  const dots=tourSteps.map((_,i)=>'<span class="'+(i===tourStep?'on':'')+'"></span>').join('');
  $('tourBox').innerHTML='<button class="tour-close" id="tourCloseBtn" aria-label="Cerrar">✕</button>'
    +'<div class="tour-dots">'+dots+'</div>'
    +'<div class="tour-icon">'+s.icon+'</div>'
    +'<h3>'+s.title+'</h3><p>'+s.text+'</p>'
    +'<div class="tour-actions">'
      +'<button class="tour-skip" id="tourSkipBtn">Saltar guía</button>'
      +(tourStep>0?'<button class="btn-secondary" id="tourPrevBtn">Atrás</button>':'')
      +'<button class="btn-primary" id="tourNextBtn">'+(tourStep===tourSteps.length-1?'Entendido':'Siguiente')+'</button>'
    +'</div>';
  if(s.go)goSection(s.go);
}
function startTour(){tourActive=true;tourStep=0;$('tourOverlay').classList.add('show');renderTour()}
function closeTour(){tourActive=false;$('tourOverlay').classList.remove('show')}
$('tourOverlay').addEventListener('click',e=>{
  if(e.target.closest('.tour-backdrop')||e.target.closest('#tourCloseBtn')||e.target.closest('#tourSkipBtn')){closeTour();return}
  if(e.target.closest('#tourNextBtn')){
    if(tourStep>=tourSteps.length-1){ closeTour(); startCoach(); return }
    tourStep++;renderTour();return;
  }
  if(e.target.closest('#tourPrevBtn')){tourStep=Math.max(0,tourStep-1);renderTour();return}
});
const tourReplayBtn=$('tourReplayBtn');
if(tourReplayBtn)tourReplayBtn.addEventListener('click',startTour);

// ===== Guía guiada paso a paso (coach marks) =====
// Señala UN elemento real de la pantalla a la vez, apaga el resto y explica qué se hace ahí.
// Cada paso puede pedir: ir a una sección (sec), abrir el drawer del menú (drawer), abrir un
// bloque plegable de Configuración (open), y a qué elemento apuntar (sel).
const coachSteps=[
  // --- el menú de la izquierda, una por una ---
  {drawer:true, sel:'.nav-item[data-id="venta"]', t:'Venta', d:'Tu pantalla del día a día: tocas productos, se arma el carrito y cobras. Es donde vas a pasar casi todo el tiempo.'},
  {drawer:true, sel:'.nav-item[data-id="pedidos"]', t:'Pedidos', d:'El historial de todo lo que has vendido. Desde aquí vuelves a mandar un comprobante por WhatsApp o registras una devolución.'},
  {drawer:true, sel:'.nav-item[data-id="porcobrar"]', t:'Por cobrar', d:'Lo que te deben, agrupado por cliente. Cuando alguien te abona, lo registras aquí y la deuda baja sola.'},
  {drawer:true, sel:'.nav-item[data-id="clientes"]', t:'Clientes', d:'Tu libreta: nombre, teléfono, cédula o RIF. La cédula importa si el cliente te pide factura.'},
  {drawer:true, sel:'.nav-item[data-id="catalogo"]', t:'Inventario', d:'Lo que vendes. Cargas cuánto te costó y cuánto quieres ganar, y FrixPOS te saca el precio en cada moneda.'},
  {drawer:true, sel:'.nav-item[data-id="stock"]', t:'Stock', d:'Cuánto te queda de cada cosa. Baja solo con cada venta y te avisa en rojo lo que se está acabando.'},
  {drawer:true, sel:'.nav-item[data-id="cajon"]', t:'Cajón', d:'Tu turno de caja. Abres con el efectivo que tienes de fondo y al cerrar cuentas la gaveta: FrixPOS te dice si sobra o falta.'},
  {drawer:true, sel:'.nav-item[data-id="informes"]', t:'Informes', d:'Cuánto vendiste y cuánto ganaste de verdad, por día o por mes, y qué producto te deja más.'},
  {drawer:true, sel:'.nav-item[data-id="contabilidad"]', t:'Contabilidad', d:'Tu IVA, tus gastos y la ganancia limpia. De aquí salen los archivos que le entregas a tu contador.'},
  {drawer:true, sel:'.nav-item[data-id="config"]', t:'Configuración', d:'Todo lo que define cómo trabaja tu FrixPOS: tu nombre, tus tasas, tus métodos de cobro. Vamos para allá.'},
  // v1.14 — el respaldo entra a la guía. Es lo que salva al negocio cuando se pierde el teléfono,
  // y hasta ahora no se lo explicaba nadie: había que toparse con él en Configuración.
  {drawer:true, sel:'#navRespaldoBtn', t:'Respaldar tu negocio', d:'Todo lo que vendes se guarda <b>en este teléfono</b> — por eso puedes seguir vendiendo aunque se caiga el internet. Pero si el teléfono se pierde, se daña o te lo roban, se va todo con él.<br><br>Este botón sube una copia de tu catálogo, tus clientes y tus ventas a la nube. Se hace solo cada vez que cierras turno, y aquí cuando tú quieras. <b>Tócalo al final del día</b> y duermes tranquilo.'},
  // --- Configuración, campo por campo ---
  {sec:'config', open:'negocio', sel:'#bizName', t:'El nombre de tu negocio', d:'Escribe aquí cómo se llama tu negocio. Es el nombre que va a salir arriba en la app y en cada comprobante que le mandes a un cliente.'},
  {sec:'config', open:'negocio', sel:'#logoPreviewWrap', t:'Tu logo', d:'Si tienes logo, súbelo. Sale junto a tu nombre en los comprobantes. Si no tienes, no pasa nada: queda la inicial de tu negocio.'},
  {sec:'config', open:'precios', sel:'#rateBcv', t:'La tasa del día', d:'De este número salen TODOS tus precios en bolívares. Actualízalo cada mañana y tu catálogo entero se acomoda solo — no tienes que tocar producto por producto. Es el número que más mueve tu negocio.'},
  {sec:'config', open:'precios', sel:'#rateIva', t:'El IVA', d:'La alícuota general que cobras. Cada producto decide aparte si lleva IVA o es exento: en una bodega, la comida de la cesta básica casi siempre es exenta, y refrescos, licores y limpieza sí llevan. Ese IVA no es ganancia tuya — lo estás guardando para el SENIAT.'},
  {sec:'config', open:'cobros', sel:'#payToggleList', t:'Cómo te pagan', d:'Enciende los métodos que de verdad usas: efectivo, pago móvil, Zelle. Los que apagues no salen al momento de cobrar, para no estorbarte.'},
  // --- primer producto y primera venta ---
  {sec:'catalogo', sel:'#newProductBtn', t:'Carga tu primer producto', d:'Aquí agregas lo que vendes: le pones cuánto te costó y cuánto quieres ganar, y FrixPOS calcula el precio. Con internet, al escribir el nombre te salen sugerencias con foto — tocas una y se llena casi solo.'},
  {sec:'cajon', sel:'#cajonBody', t:'Abre tu turno antes de vender', d:'Escribe el efectivo con el que arrancas el día y abre el turno. Sin turno abierto no se puede vender: así ninguna venta queda fuera de tu cuadre, y al cerrar sabes de una si falta plata.'},
  {sec:'venta', sel:'#grid', t:'Y ya estás listo', d:'Tocas un producto, se va al carrito, eliges cómo te pagan y cobras. Eso es todo.<br><br>Para arrancar hoy: carga tus productos, abre tu caja y vende. Puedes volver a ver esta guía cuando quieras desde Configuración.'}
];
let coachIdx=0, coachOn=false;

function coachTarget(s){
  try{ return s.sel?document.querySelector(s.sel):null; }catch(e){ return null; }
}
function coachPrepara(s){
  // deja la pantalla en el estado en que el elemento del paso EXISTE y se ve
  if(s.drawer){
    $('sidebar').classList.add('open');$('navBackdrop').classList.add('show');document.body.classList.add('drawer-open');
  } else {
    closeDrawer();
  }
  if(s.sec && currentSection!==s.sec) goSection(s.sec);
  if(s.open){
    const g=document.querySelector('.cfg-group[data-group="'+s.open+'"]');
    if(g) g.open=true;
  }
}
function coachColoca(){
  const s=coachSteps[coachIdx];
  const el=coachTarget(s);
  const hole=$('coachHole'), card=$('coachCard');
  const margen=8;
  if(!el){
    // el elemento no está en pantalla (una sección bloqueada, por ejemplo): se salta solo
    // en vez de dejar la guía trabada apuntando al vacío.
    hole.style.opacity='0';
    card.style.top=(window.scrollY+window.innerHeight/2-120)+'px';
    card.style.left=Math.max(16,(window.innerWidth-320)/2)+'px';
    return;
  }
  hole.style.opacity='1';
  const r=el.getBoundingClientRect();
  const top=r.top+window.scrollY-margen, left=r.left+window.scrollX-margen;
  hole.style.top=top+'px'; hole.style.left=left+'px';
  hole.style.width=(r.width+margen*2)+'px'; hole.style.height=(r.height+margen*2)+'px';

  // la tarjeta va debajo del elemento si cabe; si no, arriba
  const cardH=card.offsetHeight||190, cardW=card.offsetWidth||320;
  const espacioAbajo=window.innerHeight-r.bottom;
  let cTop = espacioAbajo>cardH+24 ? (r.bottom+14+window.scrollY) : (r.top-cardH-14+window.scrollY);
  if(cTop<window.scrollY+8) cTop=window.scrollY+8;
  let cLeft=r.left+window.scrollX+r.width/2-cardW/2;
  cLeft=Math.max(12,Math.min(cLeft,window.innerWidth-cardW-12));
  card.style.top=cTop+'px'; card.style.left=cLeft+'px';
}
function renderCoach(){
  const s=coachSteps[coachIdx];
  coachPrepara(s);
  $('coachCard').innerHTML=
    '<div class="coach-top"><span class="coach-count">Paso '+(coachIdx+1)+' de '+coachSteps.length+'</span>'
      +'<button class="coach-skip" id="coachSkip">Saltar guía</button></div>'
    +'<h4>'+s.t+'</h4><p>'+s.d+'</p>'
    +'<div class="coach-actions">'
      +(coachIdx>0?'<button class="btn-secondary" id="coachPrev">Atrás</button>':'')
      +'<button class="btn-primary" id="coachNext">'+(coachIdx===coachSteps.length-1?'Listo':'Siguiente')+'</button>'
    +'</div>';
  // Se deja respirar un momento: la sección recién navegada y el drawer (que abre con una
  // transición de .35s) todavía no tienen su posición final.
  // ⚠️ A propósito con setTimeout y NO con requestAnimationFrame: rAF no se dispara cuando la
  // pestaña no se está pintando (pestaña de fondo, app minimizada), y la guía se quedaba con el
  // recuadro sin colocar, señalando la esquina superior izquierda. Con setTimeout siempre corre.
  setTimeout(()=>{
    const el=coachTarget(s);
    if(el){ try{ el.scrollIntoView({block:'center',behavior:'smooth'}); }catch(e){} }
    setTimeout(coachColoca,60);
    setTimeout(coachColoca,430); // segunda pasada: ya terminó la transición del drawer y el scroll
  },0);
}
function startCoach(){
  coachOn=true;coachIdx=0;
  $('coachLayer').style.display='';
  renderCoach();
}
function closeCoach(){
  coachOn=false;
  $('coachLayer').style.display='none';
  closeDrawer();
}
// v1.11 — si el paso actual señalaba un campo de Configuración (s.open), lo que se haya
// escrito ahí se guarda de una al avanzar, retroceder o salir de la guía. Antes se perdía en
// silencio: goSection() descarta lo no guardado al salir de Configuración
// (discardUnsavedGroups), y la guía siempre termina saliendo de Configuración hacia otra
// sección — escribir el nombre del negocio durante la guía nunca quedaba guardado de verdad.
function coachGuardarGrupoActual(){
  const s=coachSteps[coachIdx];
  if(s && s.open){
    cfgSnapshots[s.open]=captureGroup(s.open);
    schedulePersist(true);
  }
}
$('coachLayer').addEventListener('click',e=>{
  if(e.target.closest('#coachSkip')){ coachGuardarGrupoActual(); closeCoach(); return }
  if(e.target.closest('#coachPrev')){ coachGuardarGrupoActual(); coachIdx=Math.max(0,coachIdx-1); renderCoach(); return }
  if(e.target.closest('#coachNext')){
    coachGuardarGrupoActual();
    if(coachIdx>=coachSteps.length-1){ closeCoach(); return }
    coachIdx++; renderCoach(); return;
  }
});
window.addEventListener('resize',()=>{ if(coachOn) coachColoca(); });
// el recuadro y la tarjeta viven en coordenadas del documento, así que siguen al elemento
// cuando la página se mueve — sin esto quedaban pegados donde estaban al abrir el paso.
window.addEventListener('scroll',()=>{ if(coachOn) coachColoca(); },{passive:true});
const coachReplayBtn=$('coachReplayBtn');
if(coachReplayBtn)coachReplayBtn.addEventListener('click',startCoach);

// ===== Configuración: guardar/descartar por bloque (v16) =====
// Los campos siguen aplicando en vivo mientras se tocan (para ver el efecto al momento, ej. tema o
// tamaño de letra), pero solo quedan CONFIRMADOS cuando se toca "Guardar cambios" del bloque —
// pedido explícito de Jonathan. Si se sale de Configuración (a Venta o cualquier otra sección) sin
// haber guardado, lo tocado en ese bloque se revierte al último estado guardado.
function captureGroup(g){
  // OJO: cfg.role NO entra en el snapshot. Es una barrera de acceso, no una preferencia editable:
  // si entrara, salir de Configuración sin guardar (discardUnsavedGroups) devolvería al cajero a
  // modo Administrador solo, y el candado no serviría de nada. aplicarRol() lo persiste aparte.
  if(g==='negocio')return{bizName:cfg.bizName,bizRif:cfg.bizRif,bizAddress:cfg.bizAddress,bizPhone:cfg.bizPhone,logo:logoDataUrl,cajeroPerms:Object.assign({},cfg.cajeroPerms||{})};
  if(g==='perfil')return{nombre:perfilCuenta.nombre,tipo:perfilCuenta.tipo,id_number:perfilCuenta.id_number,phone:perfilCuenta.phone,address:perfilCuenta.address};
  if(g==='apariencia')return{theme:activeTheme,mode:cfg.mode,fontSize:cfg.fontSize,densityMobile:cfg.densityMobile,densityDesktop:cfg.densityDesktop};
  if(g==='precios')return{bcv:cfg.bcv,paralelo:cfg.paralelo,monedas:JSON.parse(JSON.stringify(cfg.monedas||[])),ivaGeneral:cfg.ivaGeneral,gastosPctDefault:cfg.gastosPctDefault,patentePct:cfg.patentePct,islrPct:cfg.islrPct,contribEspecial:cfg.contribEspecial,currOrder:(cfg.currOrder||[]).slice(),invoiceCurrency:cfg.invoiceCurrency,mostrarUsdBcv:cfg.mostrarUsdBcv!==false,mostrarUsdOferta:cfg.mostrarUsdOferta!==false,businessMode:cfg.businessMode||'simple'};
  if(g==='cobros')return{pay:payMethods.map(m=>({id:m.id,on:m.on})),debtMode:cfg.debtMode};
  return null;
}
function applyGroup(g,snap){
  if(!snap)return;
  if(g==='negocio'){
    cfg.bizName=snap.bizName;cfg.bizRif=snap.bizRif;cfg.bizAddress=snap.bizAddress;cfg.bizPhone=snap.bizPhone;cfg.cajeroPerms=Object.assign({},snap.cajeroPerms||{});renderCajeroPerms();
    $('bizName').value=cfg.bizName;$('bizRif').value=cfg.bizRif||'';$('bizAddress').value=cfg.bizAddress||'';$('bizPhone').value=cfg.bizPhone||'';
    logoDataUrl=snap.logo;
    applyBrandName(cfg.bizName);
    if(logoDataUrl)applyLogo(logoDataUrl);
    renderNav();
  }
  if(g==='perfil'){
    // v1.14 — foto_url NO entra en el snapshot a propósito: la foto se sube al servidor apenas
    // se elige, no espera al botón "Guardar cambios". Por eso se conserva la que ya hay en vez
    // de leerla del snapshot — si no, descartar cambios te borraría de pantalla una foto que
    // en el servidor sí quedó guardada.
    perfilCuenta={nombre:snap.nombre||'',tipo:snap.tipo||'negocio',id_number:snap.id_number||'',phone:snap.phone||'',address:snap.address||'',foto_url:perfilCuenta.foto_url||''};
    pintarPerfilForm();
  }
  if(g==='apariencia'){
    activeTheme=snap.theme;
    const t=themes.find(x=>x.id===activeTheme);
    if(t){document.documentElement.style.setProperty('--accent-a',t.a);document.documentElement.style.setProperty('--accent-b',t.b);}
    renderSwatches();
    cfg.mode=snap.mode;document.body.classList.toggle('light',cfg.mode==='claro');
    document.querySelectorAll('#modeToggle button').forEach(x=>x.classList.toggle('on',x.dataset.m===cfg.mode));
    cfg.fontSize=snap.fontSize;document.documentElement.style.fontSize=({sm:'13px',md:'15px',lg:'17px'})[cfg.fontSize];
    document.querySelectorAll('#fontToggle button').forEach(x=>x.classList.toggle('on',x.dataset.f===cfg.fontSize));
    cfg.densityMobile=snap.densityMobile||'3'; cfg.densityDesktop=snap.densityDesktop||'5';
    aplicarDensidadAGrillas();renderPreview();
  }
  if(g==='precios'){
    cfg.bcv=snap.bcv;cfg.paralelo=snap.paralelo;cfg.ivaGeneral=snap.ivaGeneral;
    cfg.monedas=JSON.parse(JSON.stringify(snap.monedas||[]));syncMonedaPayMethods();
    cfg.gastosPctDefault=snap.gastosPctDefault;
    cfg.patentePct=snap.patentePct;cfg.islrPct=snap.islrPct;cfg.contribEspecial=snap.contribEspecial;
    cfg.mostrarUsdBcv=snap.mostrarUsdBcv!==false;
    cfg.mostrarUsdOferta=snap.mostrarUsdOferta!==false;
    cfg.businessMode=snap.businessMode||'simple';
    $('rateBcv').value=cfg.bcv;$('rateParalelo').value=cfg.paralelo||'';$('rateIva').value=cfg.ivaGeneral||'';$('gastosDefault').value=cfg.gastosPctDefault;renderMonedas();
    $('ratePatente').value=cfg.patentePct||'';$('rateIslr').value=cfg.islrPct||'';
    document.querySelectorAll('#contribEspToggle button').forEach(x=>x.classList.toggle('on',x.dataset.v===(cfg.contribEspecial?'si':'no')));
    document.querySelectorAll('#mostrarUsdBcvToggle button').forEach(x=>x.classList.toggle('on',x.dataset.v===(cfg.mostrarUsdBcv?'si':'no')));
    document.querySelectorAll('#mostrarUsdOfertaToggle button').forEach(x=>x.classList.toggle('on',x.dataset.v===(cfg.mostrarUsdOferta?'si':'no')));
    document.querySelectorAll('#businessModeToggle button').forEach(x=>x.classList.toggle('on',x.dataset.v===cfg.businessMode));
    applyPriceModelToProductForm();
    cfg.currOrder=snap.currOrder.slice();renderCurrOrder();
    cfg.invoiceCurrency=snap.invoiceCurrency;$('selInvoice').value=cfg.invoiceCurrency;
    onRateChange();
  }
  if(g==='cobros'){
    snap.pay.forEach(s=>{const m=payMethods.find(x=>x.id===s.id);if(m)m.on=s.on;});
    renderPayConfig();renderPay();
    cfg.debtMode=snap.debtMode;$('selDebtMode').value=cfg.debtMode;
    if(currentSection==='porcobrar')renderPorCobrar();
    if(currentSection==='clientes')renderClientes();
  }
}
let cfgSnapshots={};
function snapshotAllGroups(){ ['negocio','perfil','apariencia','precios','cobros'].forEach(g=>{cfgSnapshots[g]=captureGroup(g)}); }
function groupChanged(g){ return JSON.stringify(captureGroup(g))!==JSON.stringify(cfgSnapshots[g]); }
function discardUnsavedGroups(){ ['negocio','perfil','apariencia','precios','cobros'].forEach(g=>{ if(groupChanged(g))applyGroup(g,cfgSnapshots[g]); }); }
const CFG_GROUP_NAMES={negocio:'Negocio',perfil:'Mi perfil',apariencia:'Apariencia',precios:'Precios y tasas',cobros:'Cobros y deudas'};
$('view-config').addEventListener('click',e=>{
  const saveBtn=e.target.closest('.cfg-save-btn');
  if(!saveBtn)return;
  const g=saveBtn.dataset.group;
  askConfirm('¿Guardar los cambios de "'+CFG_GROUP_NAMES[g]+'"?',async ()=>{
    cfgSnapshots[g]=captureGroup(g);
    schedulePersist(true);
    // 'perfil' es dato de CUENTA (ver más abajo, sube por /perfil), no de negocio — no es algo
    // que otro dispositivo del mismo negocio deba "heredar" en tiempo real como si fuera config
    // de la tienda.
    if(g!=='perfil') emitCambio('cfg',{group:g,snap:cfgSnapshots[g]});
    const tag=document.querySelector('.saved-tag[data-group="'+g+'"]');
    if(tag){tag.classList.add('show');setTimeout(()=>tag.classList.remove('show'),2200);}
    // v1.11 — "Mi perfil" no es solo local: vive en la cuenta, así que además de guardar en
    // este teléfono hay que subirlo al servidor. Si falla (sin internet), lo local ya quedó
    // guardado igual — el aviso solo dice que falta reintentar cuando haya señal.
    if(g==='perfil'){
      const r=await guardarPerfilEnServidor();
      const nota=$('perfilSyncNota');
      if(nota){ nota.textContent=r.ok?'':r.error; nota.style.color='var(--amber)'; }
    }
    // v33 — pedido de Jonathan: al guardar, el acordeón se cierra solo para ver toda la
    // pantalla (mismo criterio que ya tiene Contabilidad).
    const det=saveBtn.closest('details.cfg-group');
    if(det)det.open=false;
  });
});
// v33 — acordeón exclusivo en Configuración: abrir un grupo cierra el que estaba abierto. A
// diferencia de Contabilidad, acá el HTML de los <details> es ESTÁTICO (vive en la página desde
// que carga, no se reconstruye por innerHTML), así que los listeners se enganchan una sola vez.
document.querySelectorAll('#view-config details.cfg-group').forEach(d=>{
  d.addEventListener('toggle',()=>{
    if(d.open){
      document.querySelectorAll('#view-config details.cfg-group').forEach(o=>{ if(o!==d&&o.open)o.open=false; });
    }
  });
});

// ===== Teclas rápidas (v16) — pensadas para cajeros con práctica: F2 busca producto, F9 cobra,
// ESC cancela/cierra lo que esté abierto en ese momento (prioridad: modal > formulario > carrito). =====
document.addEventListener('keydown',e=>{
  if(e.key==='F2'){
    e.preventDefault();
    if(currentSection!=='venta')goSection('venta');
    $('search').focus();$('search').select();
  } else if(e.key==='F9'){
    e.preventDefault();
    const btn=$('checkoutBtn');
    if(!btn.disabled&&btn.style.display!=='none')btn.click();
  } else if(e.key==='Escape'){
    if($('confirmOverlay').classList.contains('show')){$('confirmNo').click();return}
    if($('cropOverlay').classList.contains('show')){closeCropper();return}
    if($('pinOverlay').classList.contains('show')){closePinModal();return}
    if($('pesoOverlay').classList.contains('show')){closePesoModal();return}
    if(coachOn){closeCoach();return}
    if($('tourOverlay').classList.contains('show')){closeTour();return}
    if($('productForm').style.display!=='none'){$('prodCancel').click();return}
    if($('newClientForm').style.display!=='none'){$('newClientCancel').click();return}
    if($('fiarNewForm').style.display!=='none'){$('fiarNewCancel').click();return}
    if(cartOpen){setCartOpen(false);return}
  }
});

// ============================================================================
// Persistencia local (IndexedDB) + activación/sincronización con el backend — v28
// ============================================================================
// Antes de esto, products/clients/orders/cfg/etc. SIEMPRE arrancaban de los datos de
// muestra de abajo y se perdían al recargar — ni siquiera sobrevivían en el mismo
// teléfono. Ahora: si ya hay algo guardado en ESTE navegador, se usa eso; si no
// (primera vez), arranca con los datos de muestra de siempre — quien prueba el
// mockup por primera vez lo sigue viendo exactamente igual que antes.
// IMPORTANTE: esto guarda en el navegador de ESTE dispositivo, todavía no en ningún
// servidor. Para eso está la sección de abajo (activación + sync) — son dos capas
// separadas a propósito: local siempre funciona; la nube es un beneficio extra.
const IDB_NAME='puntoDB', IDB_STORE='state', IDB_VERSION=1;
function idbOpen(){
  return new Promise((resolve,reject)=>{
    if(!('indexedDB' in window)){reject(new Error('sin IndexedDB en este navegador'));return;}
    const req=indexedDB.open(IDB_NAME, IDB_VERSION);
    req.onupgradeneeded=()=>{ if(!req.result.objectStoreNames.contains(IDB_STORE)) req.result.createObjectStore(IDB_STORE); };
    req.onsuccess=()=>resolve(req.result);
    req.onerror=()=>reject(req.error);
  });
}
async function idbGet(key){
  try{
    const db=await idbOpen();
    return await new Promise((res,rej)=>{
      const tx=db.transaction(IDB_STORE,'readonly');
      const rq=tx.objectStore(IDB_STORE).get(key);
      rq.onsuccess=()=>res(rq.result);
      rq.onerror=()=>rej(rq.error);
    });
  }catch(e){ return undefined; }
}
async function idbSet(key,val){
  try{
    const db=await idbOpen();
    return await new Promise((res,rej)=>{
      const tx=db.transaction(IDB_STORE,'readwrite');
      tx.objectStore(IDB_STORE).put(val,key);
      tx.oncomplete=()=>res(true);
      tx.onerror=()=>rej(tx.error);
    });
  }catch(e){ return false; }
}

// Simplificación deliberada del MVP: cada pieza de estado se guarda como UN blob JSON bajo
// su propia llave (en vez de una base normalizada con un store por tabla) — mucho más simple
// y seguro de implementar en una sola pasada. Si el catálogo llegara a miles de productos,
// conviene pasar a stores separados por registro; por ahora el volumen de una bodega no lo justifica.
let persistTimer=null;
// v4 — la lista de abajo ES el contrato de qué sobrevive a cerrar la app. Antes faltaban
// piezas que hacían que la app se sintiera "en blanco" aunque los datos sí estuvieran:
//   · productSeq  -> sin él, los productos creados tras recargar repetían IDs ya usados
//   · egresos/egresoSeq -> toda la Contabilidad se perdía
//   · payMethodsOn -> volvían a aparecer métodos de cobro que el negocio había apagado
//   · activeTheme -> el color de acento volvía a 'aurora'
//   · authed/signupData -> por esto la app pedía iniciar sesión otra vez en cada apertura
// Si mañana se agrega una variable de estado nueva, va acá Y en initState(), o no se guarda.
function schedulePersist(immediate){
  if(persistTimer){clearTimeout(persistTimer);persistTimer=null;}
  const run=()=>{
    idbSet('products',products);idbSet('clients',clients);idbSet('orders',orders);
    idbSet('cfg',cfg);idbSet('categories',categories);idbSet('turnosCerrados',turnosCerrados);idbSet('abonoPagos',abonoPagos);
    idbSet('openTurno',openTurno);idbSet('orderSeq',orderSeq);idbSet('clientSeq',clientSeq);
    idbSet('turnoSeq',turnoSeq);idbSet('logoDataUrl',logoDataUrl);
    idbSet('puntoToken',puntoToken);idbSet('puntoNegocioId',puntoNegocioId);
    idbSet('puntoAccountToken',puntoAccountToken);idbSet('puntoNegocioActivo',puntoNegocioActivo);
    idbSet('puntoAccountEmail',puntoAccountEmail);idbSet('perfilCuenta',perfilCuenta);idbSet('trialInfo',trialInfo);
    idbSet('ultimoRespaldoOk',ultimoRespaldoOk);
    idbSet('stockPendiente',stockPendiente);idbSet('stockSyncDesde',stockSyncDesde);
    idbSet('productSeq',productSeq);idbSet('egresos',egresos);idbSet('egresoSeq',egresoSeq);
    idbSet('activeTheme',activeTheme);
    idbSet('payMethodsOn',payMethods.map(m=>({id:m.id,on:m.on})));
    idbSet('authed',authed);idbSet('signupData',signupData);
    idbSet('deviceId',deviceId);idbSet('cambiosPendientes',cambiosPendientes);idbSet('cambiosSyncDesde',cambiosSyncDesde);
  };
  if(immediate){ run(); } else { persistTimer=setTimeout(run,400); }
}

// v4 — borrado total (Configuración → Negocio → "Borrar todos mis datos"). Vacía la base de
// este navegador y recarga: la app vuelve a arrancar como recién instalada, con los datos de
// muestra. No toca nada del servidor — si el negocio ya sincronizó, eso sigue allá.
async function wipeLocalData(){
  if(persistTimer){clearTimeout(persistTimer);persistTimer=null;}
  try{
    const db=await idbOpen();
    await new Promise((res)=>{
      const tx=db.transaction(IDB_STORE,'readwrite');
      tx.objectStore(IDB_STORE).clear();
      tx.oncomplete=()=>res(true); tx.onerror=()=>res(false);
    });
    db.close&&db.close();
  }catch(e){}
  try{ indexedDB.deleteDatabase(IDB_NAME); }catch(e){}
}

async function initState(){
  try{
    const keys=['products','clients','orders','cfg','categories','turnosCerrados','openTurno','orderSeq','clientSeq','turnoSeq','logoDataUrl','puntoToken','puntoNegocioId','productSeq','egresos','egresoSeq','activeTheme','payMethodsOn','authed','signupData','puntoAccountToken','stockPendiente','stockSyncDesde','puntoNegocioActivo','puntoAccountEmail','perfilCuenta','trialInfo','abonoPagos','ultimoRespaldoOk','deviceId','cambiosPendientes','cambiosSyncDesde'];
    // ⚠️ Con tope de tiempo a propósito. idbGet() se queda esperando para siempre si el
    // almacenamiento del dispositivo no contesta (base bloqueada por otra pestaña, cuota llena,
    // WebView con el storage corrupto). Sin este tope, initState() nunca resuelve, el .then() de
    // abajo nunca corre y la app se queda CLAVADA en la pantalla de arranque, con el menú vacío
    // y sin poder vender — encontrado en pruebas con la base bloqueada. Ahora, si el
    // almacenamiento no responde en 5 segundos, se arranca con los datos de fábrica: se pierde
    // lo guardado en esa sesión, pero el negocio puede seguir vendiendo, que es lo que importa.
    const vals=await Promise.race([
      Promise.all(keys.map(idbGet)),
      new Promise(r=>setTimeout(()=>r(null),5000))
    ]);
    if(!vals) throw new Error('El almacenamiento del dispositivo no respondió');
    const [sp,sc,so,scfg,scat,stc,sot,sos,scs,sts,slogo,stok,snid,spseq,seg,segseq,sth,spm,sauth,ssignup,sacct,sstockp,sstockd,snact,sacctEmail,sperfil,strial,sabonos,sultResp,sdevid,scambp,scambd]=vals;
    // 'products' es const: se muta el array en su lugar en vez de reasignar la variable.
    // v4 — se chequea Array.isArray, no .length: una lista vacía es un estado guardado
    // válido (el negocio borró todo su catálogo, o todavía no ha vendido nada). Antes, con
    // .length, quedarse en cero hacía "revivir" los datos de muestra en la próxima apertura.
    if(Array.isArray(sp)){ products.length=0; sp.forEach(p=>products.push(p)); }
    if(Array.isArray(sc)) clients=sc;
    if(Array.isArray(so)) orders=so;
    // merge superficial: lo guardado pisa los defaults, pero un campo NUEVO agregado en una
    // versión más reciente del POS (ej. lowStockThreshold en v27) que un guardado viejo no
    // tenía, conserva su valor por defecto en vez de quedar 'undefined'.
    if(scfg) cfg=Object.assign({},cfg,scfg);
    if(scat && scat.length) categories=scat;
    if(stc && stc.length) turnosCerrados=stc;
    if(sot) openTurno=sot;
    if(typeof sos==='number') orderSeq=sos;
    if(typeof scs==='number') clientSeq=scs;
    if(typeof sts==='number') turnoSeq=sts;
    if(slogo){ logoDataUrl=slogo; }
    if(stok) puntoToken=stok;
    if(snid) puntoNegocioId=snid;
    if(typeof spseq==='number') productSeq=spseq;
    // egresos: a diferencia de products/clients, un negocio puede legítimamente tener CERO
    // egresos guardados, y eso es un estado válido que hay que respetar — por eso se chequea
    // Array.isArray y no .length (si no, borrar el último egreso "revivía" los de muestra).
    if(Array.isArray(seg)) egresos=seg;
    if(typeof segseq==='number') egresoSeq=segseq;
    if(sth) activeTheme=sth;
    if(Array.isArray(spm)) spm.forEach(s=>{const m=payMethods.find(x=>x.id===s.id); if(m)m.on=s.on;});
    if(sauth) authed=true;
    if(ssignup) signupData=Object.assign({},signupData,ssignup);
    if(sacct) puntoAccountToken=sacct;
    if(sacctEmail) puntoAccountEmail=sacctEmail;
    if(sperfil) perfilCuenta=Object.assign({},perfilCuenta,sperfil);
    if(strial) trialInfo=strial;
    if(Array.isArray(sabonos)) abonoPagos=sabonos;
    if(sultResp) ultimoRespaldoOk=sultResp;
    if(typeof snact==='boolean') puntoNegocioActivo=snact;
    if(puntoToken) puntoNegocioActivo=true; // el teléfono que activó el código ya es premium
    // Reparación: un dispositivo que recuperó un respaldo ANTES de que existiera
    // revivirFechasRespaldo() guardó fechas como string en su propio IndexedDB, y ahí se
    // quedaron. Revivirlas también acá deja esos dispositivos sanos sin tener que borrar nada.
    revivirFechasRespaldo({orders,egresos,turnosCerrados,openTurno,abonoPagos});
    if(sstockp) stockPendiente=sstockp;
    if(sstockd) stockSyncDesde=sstockd;
    if(sdevid) deviceId=sdevid;
    if(Array.isArray(scambp)) cambiosPendientes=scambp;
    if(scambd) cambiosSyncDesde=scambd;
  }catch(e){
    // sin IndexedDB (navegador viejo / algunos modos privados): sigue con los datos de
    // muestra de siempre, exactamente el comportamiento del mockup antes de v28.
  }
}

function uuidLite(){
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,c=>{
    const r=Math.random()*16|0, v=c==='x'?r:(r&0x3|0x8);
    return v.toString(16);
  });
}

// ---------- cuenta del dueño (v1.1) ----------
// A propósito separado de la activación de abajo: esto es "quién eres" (gratis, sin
// código), la activación es "tu negocio ya pagó el respaldo en la nube" (con código).
// Igual que el resto del backend: si no hay internet, el error se explica y la app
// sigue funcionando local — la cuenta no es requisito para vender.
// v1.11 — si este dispositivo tiene guardados los datos de OTRA cuenta (correo distinto al que
// acaba de entrar o crearse), hay que limpiarlos ANTES de aplicar nada — si no, la cuenta nueva
// hereda el catálogo, los clientes y las ventas de la anterior. Bug real: Jonathan cerró sesión
// y entró con otra cuenta en la misma pestaña, y le aparecieron datos de la primera.
// wipeLocalData()+recargar es la forma segura de dejarlo limpio de verdad — reutiliza el mismo
// camino ya probado de "Borrar todos mis datos" en vez de intentar resetear cada variable a
// mano y arriesgarse a olvidar una. El costo es que hay que iniciar sesión otra vez después de
// recargar — aceptable por lo poco frecuente que es cambiar de cuenta en el mismo teléfono.
async function limpiarSiEsOtraCuenta(emailNuevo){
  const e=(emailNuevo||'').trim().toLowerCase();
  if(puntoAccountEmail && puntoAccountEmail!==e){
    alert('Este teléfono tiene guardados los datos de otra cuenta ('+puntoAccountEmail+'). Los vamos a borrar de AQUÍ (esa cuenta no se toca) para que entres limpio con la tuya. Después de que recargue, inicia sesión de nuevo.');
    await wipeLocalData();
    location.reload();
    return true;
  }
  puntoAccountEmail=e;
  return false;
}
async function registrarNegocio(datos){
  try{
    const res=await fetch('/wp-json/punto/v1/registro',{
      method:'POST', headers:{'Content-Type':'application/json'},
      body:JSON.stringify(datos)
    });
    const data=await res.json().catch(()=>null);
    if(!res.ok || !data || !data.ok) return {ok:false, error:(data&&data.message)||'No se pudo crear la cuenta. Intenta de nuevo.'};
    if(await limpiarSiEsOtraCuenta(datos.email)) return {ok:true, recargando:true};
    if(data.token){ puntoAccountToken=data.token; schedulePersist(true); }
    // v1.12 — sembrado inmediato del trial recién arrancado, sin esperar la llamada a /perfil
    if(typeof data.trial_activo==='boolean'){ trialInfo={activo:data.trial_activo,vence:data.trial_vence||null,verificadoEn:Date.now()}; }
    return {ok:true};
  }catch(e){
    return {ok:false, error:'No se pudo conectar al servidor. Verifica tu internet e intenta de nuevo.'};
  }
}
async function loginNegocio(email,pass){
  try{
    const res=await fetch('/wp-json/punto/v1/login',{
      method:'POST', headers:{'Content-Type':'application/json'},
      body:JSON.stringify({email,password:pass})
    });
    const data=await res.json().catch(()=>null);
    if(!res.ok || !data || !data.ok) return {ok:false, error:(data&&data.message)||'Correo o contraseña incorrectos.'};
    // v1.14 — ¿este teléfono YA era de esta misma cuenta? Hay que preguntarlo ANTES de
    // limpiarSiEsOtraCuenta(), porque esa función sobrescribe puntoAccountEmail. Sirve para no
    // ofrecerle "recuperar un respaldo" a quien solo cerró sesión y volvió a entrar: sus datos
    // nunca se fueron a ningún lado, están acá mismo.
    const mismoDispositivo=(puntoAccountEmail||'').trim().toLowerCase()===(email||'').trim().toLowerCase();
    if(await limpiarSiEsOtraCuenta(email)) return {ok:true, recargando:true};
    if(data.token){ puntoAccountToken=data.token; }
    puntoNegocioActivo=!!data.negocio_activo;
    if(data.negocio_id&&!puntoNegocioId) puntoNegocioId=data.negocio_id;
    // v1.12 — sembrado inmediato del estado del trial (ver cargarPerfilDelServidor para el
    // detalle de por qué verificadoEn es hora LOCAL y no algo que venga del servidor).
    if(typeof data.trial_activo==='boolean'){ trialInfo={activo:data.trial_activo,vence:data.trial_vence||null,verificadoEn:Date.now()}; }
    schedulePersist(true);
    return {ok:true, negocio:data.negocio||null, mismoDispositivo};
  }catch(e){
    return {ok:false, error:'No se pudo conectar al servidor. Verifica tu internet e intenta de nuevo.'};
  }
}
async function recuperarPassword(email){
  try{
    const res=await fetch('/wp-json/punto/v1/recuperar',{
      method:'POST', headers:{'Content-Type':'application/json'},
      body:JSON.stringify({email})
    });
    const data=await res.json().catch(()=>null);
    return {ok:true, message:(data&&data.message)||'Si ese correo tiene una cuenta, te mandamos instrucciones.'};
  }catch(e){
    return {ok:false, error:'No se pudo conectar al servidor. Verifica tu internet e intenta de nuevo.'};
  }
}

// ---------- activación + sincronización con el backend ----------
// Nada de esto bloquea el uso normal del POS: sin token (negocio no activado) o sin
// internet, todo sigue funcionando 100% local — es un beneficio extra, no un requisito.
async function activarNegocio(codigo, nombreNegocio){
  try{
    // v1.4 del backend: si hay cuenta logueada, el negocio queda vinculado a ella (para la
    // cuota de respaldos paga/gratis) — se manda el token de cuenta cuando existe, pero
    // activar sin cuenta sigue funcionando igual que siempre, el código es lo que importa.
    const headers={'Content-Type':'application/json'};
    if(puntoAccountToken) headers['Authorization']='Bearer '+puntoAccountToken;
    const res=await fetch('/wp-json/punto/v1/activar',{
      method:'POST', headers,
      body:JSON.stringify({codigo:(codigo||'').trim(), nombre_negocio:(nombreNegocio||cfg.bizName||'').trim()})
    });
    const data=await res.json().catch(()=>null);
    if(!res.ok || !data || !data.token) return {ok:false, error:(data&&data.message)||'Código inválido, ya usado, o hubo un error del servidor.'};
    puntoToken=data.token; puntoNegocioId=data.negocio_id;
    schedulePersist(true);
    syncPendingOrders();
    // El bloque de Respaldo quedó pintado con el cupo GRATIS (2). Al activar sube a 30 y las
    // fotos ya se pueden respaldar: hay que mostrarlo YA, no después de cerrar y reabrir la app.
    try{ renderRespaldo(); }catch(e){}
    return {ok:true};
  }catch(e){
    return {ok:false, error:'No se pudo conectar al servidor. Verifica tu internet e intenta de nuevo.'};
  }
}

// ---------- respaldo (backend v1.5) ----------
// El servidor NUNCA calcula nada acá — el POS arma "payload" (lo que se restaura) y
// "resumen" (conteos/totales livianos, para mostrar sin haber pagado) y el servidor solo
// guarda. Necesita cuenta (puntoAccountToken), NO necesita código de activación — el cupo
// gratis (2 respaldos) ya funciona sin pagar; el código sube el cupo a 30 (ver backend).
function respaldoFechaCorte(){ const d=new Date(); d.setDate(d.getDate()-30); return d; }
// último mes completo, MÁS cualquier fiado viejo que siga pendiente — "por cobrar" es plata
// que el negocio todavía no ha cobrado, no puede expirar solo porque pasó un mes.
function respaldoOrdenesIncluidas(){
  const corte=respaldoFechaCorte();
  return orders.filter(o=>o.uuid && (new Date(o.date)>=corte || o.status==='fiado'));
}
// hash chiquito, no criptográfico — solo para detectar "esta foto es la misma que ya
// subimos" sin tener que guardar el base64 completo dos veces en el dispositivo.
function hashLigero(str){
  let h=0;
  for(let i=0;i<str.length;i++){ h=(h*31+str.charCodeAt(i))|0; }
  return h;
}
// v1.7 — sube al servidor (Biblioteca de Medios) las fotos propias que todavía no tienen
// URL guardada, o cuya foto cambió desde la última subida. Cada producto se sube UNA VEZ en
// su vida (mientras no le cambies la foto): p.fotoRespaldoUrl queda pegada al producto y los
// siguientes respaldos solo mandan esa URL, nunca vuelven a subir el mismo archivo.
// v1.14 — YA NO exige negocio activo. Las fotos de TODOS suben, pague o no: lo que se paga es
// poder recuperar el respaldo, no que se guarde. Antes esto se salía de una en la prueba
// gratis, así que las fotos nunca llegaban al servidor y nadie se enteraba.
// No bloquea nunca: si una foto falla (sin internet a mitad de camino, lo que sea), esa queda
// pendiente para el próximo respaldo y las demás siguen su curso.
async function subirFotosPendientes(){
  if(!puntoAccountToken) return; // sin cuenta no hay a dónde subirlas
  for(const p of products){
    if(!p.photo || p.photo.indexOf('data:')!==0) continue;
    const h=hashLigero(p.photo);
    if(p.fotoRespaldoUrl && p.fotoRespaldoHash===h) continue; // ya está, sin cambios
    try{
      const res=await fetch('/wp-json/punto/v1/respaldo/foto',{
        method:'POST', headers:{'Content-Type':'application/json','Authorization':'Bearer '+puntoAccountToken},
        body:JSON.stringify({foto_base64:p.photo})
      });
      const data=await res.json().catch(()=>null);
      if(res.ok && data && data.ok && data.url){ p.fotoRespaldoUrl=data.url; p.fotoRespaldoHash=h; }
    }catch(e){ /* sin internet a mitad de camino — se reintenta en el próximo respaldo */ }
  }
}
function armarRespaldoPayload(){
  const corte=respaldoFechaCorte();
  // si ya se subió la foto propia (subirFotosPendientes, arriba) se manda su URL, liviana,
  // en vez del base64 — así el paquete se queda chiquito aunque el negocio tenga cientos de
  // productos con foto. Las que todavía no se pudieron subir (sin internet, recién agregadas)
  // van con su base64 tal cual, y el servidor decide si las conserva según si paga o no.
  const productosParaRespaldo=products.map(p=>{
    if(p.fotoRespaldoUrl && p.photo && p.photo.indexOf('data:')===0 && p.fotoRespaldoHash===hashLigero(p.photo)){
      return Object.assign({},p,{photo:p.fotoRespaldoUrl});
    }
    return p;
  });
  return {
    products:productosParaRespaldo, clients, categories,
    orders:respaldoOrdenesIncluidas(),
    egresos:(egresos||[]).filter(e=>new Date(e.date)>=corte),
    abonoPagos:(abonoPagos||[]).filter(t=>new Date(t.fecha)>=corte),
    cfg, turnosCerrados, openTurno, logoDataUrl,
    // v1.14 — faltaban para poder "recuperar todo" de verdad: sin esto, un teléfono nuevo
    // recuperaba el catálogo y las ventas pero volvía con los métodos de cobro de fábrica
    // (Zelle y Binance encendidos aunque el negocio no los use) y el color de acento por defecto.
    payMethodsOn:payMethods.map(m=>({id:m.id,on:m.on})),
    activeTheme,
    productSeq, clientSeq, orderSeq, egresoSeq, turnoSeq
  };
}
function armarRespaldoResumen(){
  const incluidas=respaldoOrdenesIncluidas();
  const porCobrarBs=orders.filter(o=>o.uuid&&o.status==='fiado').reduce((s,o)=>s+Math.max(0,(o.totalBs||0)-(o.paidBs||0)),0);
  return { productos:products.length, pedidos:incluidas.length, clientes:clients.length, porCobrarBs:Math.round(porCobrarBs) };
}
async function guardarRespaldo(){
  if(!puntoAccountToken) return {ok:false, error:'Crea una cuenta o inicia sesión para poder respaldar.'};
  try{
    await subirFotosPendientes(); // best-effort — si falla, armarRespaldoPayload manda base64 igual
    const res=await fetch('/wp-json/punto/v1/respaldo',{
      method:'POST', headers:{'Content-Type':'application/json','Authorization':'Bearer '+puntoAccountToken},
      body:JSON.stringify({payload:armarRespaldoPayload(), resumen:armarRespaldoResumen()})
    });
    const data=await res.json().catch(()=>null);
    if(!res.ok||!data||!data.ok) return {ok:false, error:(data&&data.message)||'No se pudo respaldar. Intenta de nuevo.'};
    ultimoRespaldoOk=new Date().toISOString(); // v32 — para la tarjeta del Cajón
    schedulePersist(true); // los fotoRespaldoUrl/fotoRespaldoHash nuevos quedan guardados también
    return {ok:true, cantidad:data.cantidad, cupo:data.cupo};
  }catch(e){
    return {ok:false, error:'No se pudo conectar al servidor. Verifica tu internet e intenta de nuevo.'};
  }
}
async function listarRespaldos(){
  if(!puntoAccountToken) return {ok:false};
  try{
    const res=await fetch('/wp-json/punto/v1/respaldo',{ headers:{'Authorization':'Bearer '+puntoAccountToken} });
    const data=await res.json().catch(()=>null);
    if(!res.ok||!data||!data.ok) return {ok:false};
    return data; // {ok:true, cupo, respaldos:[{id,fecha,resumen}]}
  }catch(e){
    return {ok:false};
  }
}
// ⚠️ PIEZA CRÍTICA — el respaldo viaja como JSON, y JSON no tiene tipo fecha: todo lo que se
// guardó como Date vuelve del servidor como STRING ("2026-08-10T14:23:00.000Z"). El resto de la
// app asume Date de verdad y llama .toLocaleDateString()/.getTime() directo (fmtDate, fmtTime,
// fechaCorta, cajonAbiertoHTML...), así que un string revienta el render entero de la sección:
// Pedidos y Cajón quedaban pintados en blanco, "solo el fondo", después de recuperar un respaldo.
// IndexedDB no tenía este problema porque el structured clone sí conserva Date — por eso el bug
// solo aparecía por el camino del servidor. Se revive todo acá, en la frontera, para que de este
// punto en adelante nadie más tenga que preocuparse por el tipo.
function reviveFecha(v){
  if(!v) return v;
  if(v instanceof Date) return v;
  const d=new Date(v);
  return isNaN(d.getTime())?v:d;
}
function revivirFechasRespaldo(payload){
  (payload.orders||[]).forEach(o=>{
    o.date=reviveFecha(o.date);
    (o.abonos||[]).forEach(a=>{ a.fecha=reviveFecha(a.fecha); });
    (o.devoluciones||[]).forEach(dv=>{ dv.date=reviveFecha(dv.date); });
  });
  (payload.egresos||[]).forEach(e=>{ e.date=reviveFecha(e.date); });
  (payload.turnosCerrados||[]).forEach(t=>{ t.inicio=reviveFecha(t.inicio); t.fin=reviveFecha(t.fin); });
  if(payload.openTurno) payload.openTurno.inicio=reviveFecha(payload.openTurno.inicio);
  (payload.abonoPagos||[]).forEach(t=>{ t.fecha=reviveFecha(t.fecha); });
  return payload;
}
// aplica un payload de respaldo YA descargado al estado en memoria — misma forma que
// initState() usa para lo que sale de IndexedDB, pero esto viene del servidor y siempre
// reemplaza (a diferencia de initState, que solo aplica lo que sí vino guardado).
function aplicarRespaldo(payload){
  if(!payload) return;
  revivirFechasRespaldo(payload);
  if(Array.isArray(payload.products)){ products.length=0; payload.products.forEach(p=>products.push(p)); }
  if(Array.isArray(payload.clients)) clients=payload.clients;
  if(Array.isArray(payload.orders)) orders=payload.orders;
  if(Array.isArray(payload.categories)&&payload.categories.length) categories=payload.categories;
  if(Array.isArray(payload.egresos)) egresos=payload.egresos;
  if(payload.cfg) cfg=Object.assign({},cfg,payload.cfg);
  if(Array.isArray(payload.turnosCerrados)) turnosCerrados=payload.turnosCerrados;
  if(payload.openTurno) openTurno=payload.openTurno;
  if(Array.isArray(payload.abonoPagos)) abonoPagos=payload.abonoPagos;
  // v1.14 — métodos de cobro y color de acento (ver armarRespaldoPayload)
  if(Array.isArray(payload.payMethodsOn)){
    payload.payMethodsOn.forEach(s=>{ const m=payMethods.find(x=>x.id===s.id); if(m)m.on=s.on; });
  }
  if(payload.activeTheme) activeTheme=payload.activeTheme;
  if(payload.logoDataUrl) logoDataUrl=payload.logoDataUrl;
  if(typeof payload.productSeq==='number') productSeq=payload.productSeq;
  if(typeof payload.clientSeq==='number') clientSeq=payload.clientSeq;
  if(typeof payload.orderSeq==='number') orderSeq=payload.orderSeq;
  if(typeof payload.egresoSeq==='number') egresoSeq=payload.egresoSeq;
  if(typeof payload.turnoSeq==='number') turnoSeq=payload.turnoSeq;
  schedulePersist(true);
}
async function restaurarRespaldo(id){
  if(!puntoAccountToken) return {ok:false, error:'Inicia sesión de nuevo.'};
  try{
    const res=await fetch('/wp-json/punto/v1/respaldo/'+id+'/completo',{
      headers:{'Authorization':'Bearer '+puntoAccountToken}
    });
    const data=await res.json().catch(()=>null);
    if(res.status===403 && data && data.requiere_activacion) return {ok:false, requiereActivacion:true, error:data.message};
    if(!res.ok||!data||!data.ok) return {ok:false, error:(data&&data.message)||'No se pudo recuperar el respaldo.'};
    aplicarRespaldo(data.payload);
    return {ok:true};
  }catch(e){
    return {ok:false, error:'No se pudo conectar al servidor. Verifica tu internet e intenta de nuevo.'};
  }
}
// v51 — pedido de Jonathan: "Formatear cuenta" hacía lo mismo que "Empezar de cero" (Configuración
// → Negocio) para la mayoría de la gente — se quitó, queda solo "Empezar de cero" como único botón
// para arrancar limpio. Diferencia real que existía (por si hace falta recordarla): "Empezar de
// cero" (wipeLocalData) borraba TAMBIÉN la cuenta/sesión de este teléfono; "Formatear cuenta" solo
// borraba catálogo/ventas/clientes/egresos y dejaba la cuenta intacta. Ahora, quien quiera limpiar
// datos de prueba sin perder su sesión tiene que iniciar sesión de nuevo después de "Empezar de
// cero" — trade-off que Jonathan aceptó a cambio de un botón menos y menos confusión.
// v1.9 — zona de peligro, se muestra siempre que haya cuenta (con o sin internet en ese
// instante) porque no depende de que listarRespaldos() haya funcionado.
function cuentaZonaPeligroHTML(){
  return '<div class="cfg-block" style="margin-top:14px;border-color:var(--red)"><h4 style="color:var(--red)">Borrar mi cuenta</h4>'
    +'<div class="desc">Borra tu acceso (correo/contraseña) y desactiva tu negocio: se acaba el respaldo en la nube y el stock compartido entre cajas. Tus ventas ya sincronizadas no se tocan. Esto también borra todo lo de este dispositivo y no se puede deshacer.</div>'
    +'<button class="btn-secondary" id="borrarCuentaBtn" style="width:100%;margin-top:8px;color:var(--red)">Borrar mi cuenta</button>'
  +'</div>';
}
// ===== v1.11 — Mi perfil (Configuración, separado de Negocio) =====
// Campos SIEMPRE estáticos en el HTML (igual que bizName/bizRif arriba) — a diferencia de
// Respaldo, que se reconstruye entero por innerHTML cada vez, acá el usuario puede estar
// escribiendo mientras la consulta al servidor todavía no vuelve, y reconstruir el formulario
// le borraría lo que está tecleando. Por eso solo se togglea visibilidad y se pintan valores.
function pintarPerfilForm(){
  $('perfilNombre').value=perfilCuenta.nombre||'';
  $('perfilIdNumber').value=perfilCuenta.id_number||'';
  $('perfilPhone').value=perfilCuenta.phone||'';
  $('perfilAddress').value=perfilCuenta.address||'';
  document.querySelectorAll('#perfilTipo button').forEach(x=>x.classList.toggle('on',x.dataset.v===(perfilCuenta.tipo||'negocio')));
  $('perfilNombreLbl').textContent=(perfilCuenta.tipo==='negocio')?'Nombre del negocio':'Nombre y apellido';
  $('perfilIdLbl').textContent=(perfilCuenta.tipo==='negocio')?'RIF':'Cédula';
  const fw=$('perfilFotoWrap');
  if(fw){
    if(perfilCuenta.foto_url){
      fw.innerHTML='<img src="'+perfilCuenta.foto_url+'" style="width:100%;height:100%;object-fit:cover;border-radius:50%">';
      $('perfilFotoText').textContent='Cambiar foto';
    } else {
      fw.innerHTML=''; $('perfilFotoText').textContent='Subir foto';
    }
  }
}
function renderPerfil(){
  const msg=$('perfilLoginMsg'),fields=$('perfilFields'),saveBtn=$('perfilSaveBtn');
  if(!msg||!fields)return;
  const saveRow=saveBtn?saveBtn.closest('.cfg-save-row'):null;
  if(!puntoAccountToken){
    msg.style.display='';fields.style.display='none';
    if(saveRow)saveRow.style.display='none';
    return;
  }
  msg.style.display='none';fields.style.display='';
  if(saveRow)saveRow.style.display='';
  $('perfilEmail').value=puntoAccountEmail||'';
  pintarPerfilForm();
  cargarPerfilDelServidor(); // pinta con lo local ya, y corrige solo si el servidor trae otra cosa
}
// trae el perfil fresco del servidor — por si se editó desde otro teléfono con la misma cuenta.
// No bloquea: lo que ya hay en pantalla (de perfilCuenta local) se ve al toque, esto solo corrige
// si hace falta. Si falla (sin internet), se queda con lo local — no es un error del usuario.
async function cargarPerfilDelServidor(){
  const nota=$('perfilSyncNota');
  if(!puntoAccountToken)return{ok:false};
  try{
    const res=await fetch('/wp-json/punto/v1/perfil',{headers:{'Authorization':'Bearer '+puntoAccountToken}});
    const data=await res.json().catch(()=>null);
    if(!res.ok||!data||!data.ok){
      if(nota){nota.textContent='No se pudo actualizar tu perfil desde el servidor — revisa tu internet.';nota.style.color='var(--amber)';}
      return{ok:false};
    }
    perfilCuenta={nombre:data.nombre||'',tipo:data.tipo||'negocio',id_number:data.id_number||'',phone:data.phone||'',address:data.address||'',foto_url:data.foto_url||''};
    cfgSnapshots.perfil=captureGroup('perfil');
    pintarPerfilForm();
    // v1.12 — cada vez que se puede hablar con el servidor, se reconfirma la prueba gratis.
    // verificadoEn es HORA LOCAL a propósito: mide "hace cuánto no logro preguntarle al
    // servidor", no una fecha que alguien pueda adelantar para inventarse más días de prueba.
    if(typeof data.trial_activo==='boolean'){
      trialInfo={activo:data.trial_activo,vence:data.trial_vence||null,verificadoEn:Date.now()};
    }
    if(nota)nota.textContent='';
    return{ok:true};
  }catch(e){
    if(nota){nota.textContent='No se pudo conectar al servidor — revisa tu internet.';nota.style.color='var(--amber)';}
    return{ok:false};
  }
}
// sube lo que el dueño acaba de guardar en "Mi perfil". Se llama desde el botón genérico de
// Configuración (ver el listener de .cfg-save-btn) cuando el grupo es 'perfil'.
// v1.14 — sube la foto de perfil al servidor y guarda su URL. Gratis para todos (igual que el
// resto del respaldo). Si falla, la foto se ve igual en pantalla pero se avisa que no subió —
// mentir diciendo que quedó guardada sería peor que el error.
async function subirFotoPerfil(base64){
  const txt=$('perfilFotoText'), nota=$('perfilSyncNota');
  if(!puntoAccountToken){ if(txt)txt.textContent='Inicia sesión para guardar tu foto'; return; }
  try{
    const res=await fetch('/wp-json/punto/v1/perfil/foto',{
      method:'POST', headers:{'Content-Type':'application/json','Authorization':'Bearer '+puntoAccountToken},
      body:JSON.stringify({foto_base64:base64})
    });
    const data=await res.json().catch(()=>null);
    if(res.ok&&data&&data.ok&&data.url){
      perfilCuenta.foto_url=data.url;
      cfgSnapshots.perfil=captureGroup('perfil');
      schedulePersist(true);
      if(txt)txt.textContent='Cambiar foto';
      if(nota){ nota.textContent='Tu foto quedó guardada en tu cuenta.'; nota.style.color='var(--green)'; }
      return;
    }
    if(txt)txt.textContent='Cambiar foto';
    if(nota){ nota.textContent=(data&&data.message)||'No se pudo guardar tu foto.'; nota.style.color='var(--amber)'; }
  }catch(e){
    if(txt)txt.textContent='Cambiar foto';
    if(nota){ nota.textContent='No se pudo subir tu foto — revisa tu internet e inténtalo de nuevo.'; nota.style.color='var(--amber)'; }
  }
}
async function guardarPerfilEnServidor(){
  if(!puntoAccountToken)return{ok:false,error:'Inicia sesión de nuevo.'};
  try{
    const res=await fetch('/wp-json/punto/v1/perfil',{
      method:'POST', headers:{'Content-Type':'application/json','Authorization':'Bearer '+puntoAccountToken},
      body:JSON.stringify(perfilCuenta)
    });
    const data=await res.json().catch(()=>null);
    if(!res.ok||!data||!data.ok)return{ok:false,error:(data&&data.message)||'No se pudo actualizar en el servidor.'};
    return{ok:true};
  }catch(e){
    return{ok:false,error:'No se pudo conectar al servidor. Tus cambios quedaron guardados en este teléfono — vuelve a tocar "Guardar cambios" cuando tengas señal.'};
  }
}

// ===== v1.13 — Catálogo Digital =====
// Configuración vive en cfg.catalogo* (igual que cfg.bizName): local hasta que se publica, y se
// persiste solo con schedulePersist() normal — no necesita sus propias claves de IndexedDB.
function renderCatalogoDigital(){
  const msg=$('catalogoLoginMsg'),fields=$('catalogoFields');
  if(!msg||!fields)return;
  if(!puntoAccountToken){
    msg.style.display='';fields.style.display='none';
    return;
  }
  msg.style.display='none';fields.style.display='';
  $('catSlug').value=cfg.catalogoSlug||'';
  updateCatSlugPreview();
  document.querySelectorAll('#catVisible button').forEach(x=>x.classList.toggle('on',x.dataset.v===(cfg.catalogoVisible?'si':'no')));
  document.querySelectorAll('#catMostrarPrecios button').forEach(x=>x.classList.toggle('on',x.dataset.v===(cfg.catalogoMostrarPrecios?'si':'no')));
  if(cfg.catalogoLogoUrl){
    $('catLogoPreviewWrap').innerHTML='<img src="'+cfg.catalogoLogoUrl+'" style="width:100%;height:100%;object-fit:cover;border-radius:10px">';
    $('catLogoText').textContent='Cambiar imagen';
  }
  renderCatalogoCompartir();
}
// v36 — el bloque de compartir. Se muestra solo si el catálogo está publicado Y tiene enlace:
// mandarle a alguien un enlace apagado es peor que no darle nada.
function catalogoUrlPublica(){
  return cfg.catalogoSlug ? (location.origin+'/p/'+cfg.catalogoSlug) : '';
}
function renderCatalogoCompartir(){
  const bloque=$('catCompartirBloque'); if(!bloque)return;
  const url=catalogoUrlPublica();
  const listo=!!url && cfg.catalogoVisible;
  bloque.style.display=listo?'':'none';
  if(listo)$('catEnlace').value=url;
}
// mensaje que acompaña al enlace. Va en primera persona, como lo mandaría el propio bodeguero.
function catalogoMensajeCompartir(){
  const nombre=cfg.bizName||'mi negocio';
  return 'Este es el catálogo de '+nombre+'. Mira lo que tenemos y escríbeme por aquí para pedir: '+catalogoUrlPublica();
}
// sube UNA foto para el catálogo digital (producto o logo) — gratis, sin exigir negocio activo.
async function subirFotoCatalogo(base64){
  if(!puntoAccountToken)return null;
  try{
    const res=await fetch('/wp-json/punto/v1/catalogo-publico/foto',{
      method:'POST', headers:{'Content-Type':'application/json','Authorization':'Bearer '+puntoAccountToken},
      body:JSON.stringify({foto_base64:base64})
    });
    const data=await res.json().catch(()=>null);
    if(res.ok&&data&&data.ok&&data.url)return data.url;
  }catch(e){ /* sin internet — se reintenta la próxima vez que toque "Publicar catálogo" */ }
  return null;
}
// arma y publica el catálogo: sube las fotos que falten (una vez cada una, se cachea por hash
// igual que subirFotosPendientes() del respaldo), y manda solo los productos marcados visibles.
async function publicarCatalogoEnServidor(){
  if(!puntoAccountToken)return{ok:false,error:'Inicia sesión de nuevo.'};
  const slug=($('catSlug').value||'').trim().toLowerCase();
  const visible=document.querySelector('#catVisible button.on')?.dataset.v==='si';
  const mostrarPrecios=document.querySelector('#catMostrarPrecios button.on')?.dataset.v==='si';
  if(visible&&!slug)return{ok:false,error:'Escribe un nombre para tu enlace antes de publicar.'};

  const visibles=products.filter(p=>p.enCatalogoPublico!==false);
  for(const p of visibles){
    if(p.photo&&p.photo.indexOf('data:')===0){
      const h=hashLigero(p.photo);
      if(!(p.catalogoFotoUrl&&p.catalogoFotoHash===h)){
        const url=await subirFotoCatalogo(p.photo);
        if(url){ p.catalogoFotoUrl=url; p.catalogoFotoHash=h; }
      }
    }
  }
  let logoUrl=cfg.catalogoLogoUrl||'';
  if(catLogoDataUrl){
    const hLogo=hashLigero(catLogoDataUrl);
    if(!(cfg.catalogoLogoUrl&&cfg.catalogoLogoHash===hLogo)){
      const url=await subirFotoCatalogo(catLogoDataUrl);
      if(url){ logoUrl=url; cfg.catalogoLogoUrl=url; cfg.catalogoLogoHash=hLogo; }
    }
  }

  const productos=visibles.map(p=>{
    const pr=effectivePrices(p);
    // venta por peso (v45): a diferencia de /sync y /respaldo (blobs opacos), la tabla del
    // Catálogo Digital público tiene columnas fijas en el servidor y no hay una para "precio por
    // Kg" — sin tocar el plugin backend, la única forma de que la vitrina lo comunique es
    // horneando el aviso dentro del nombre que se manda.
    // v49 — la columna del servidor sigue llamándose "precio_usd" (no se puede tocar el plugin
    // backend desde acá — su tabla tiene columnas fijas, a diferencia de /sync y /respaldo que
    // son blobs), pero ahora se manda el USD BCV en vez del USD oferta: la oferta pasó a ser un
    // dato informal del dueño, no algo para mostrarle al cliente en la vitrina que comparte por
    // WhatsApp — mismo criterio que ya usa el recibo desde v1.18. Si no hay BCV cargado, cae al
    // oferta porque no hay otro número que mandar (igual que hace el recibo en pedidos viejos).
    const precioPublico=(cfg.bcv>0)?pr.bcv:pr.contado;
    return {
      producto_id:p.id, nombre:p.name+(p.ventaPeso?' (precio por Kg)':''),
      precio_usd:(pr&&precioPublico>0)?Math.round(precioPublico*100)/100:null,
      foto_url:p.catalogoFotoUrl||'', categoria:catLabelOf(p.cat)||''
    };
  });

  try{
    const res=await fetch('/wp-json/punto/v1/catalogo-publico',{
      method:'POST', headers:{'Content-Type':'application/json','Authorization':'Bearer '+puntoAccountToken},
      body:JSON.stringify({slug,visible,mostrar_precios:mostrarPrecios,logo_url:logoUrl,productos})
    });
    const data=await res.json().catch(()=>null);
    if(!res.ok||!data||!data.ok)return{ok:false,error:(data&&data.message)||'No se pudo publicar el catálogo.'};
    cfg.catalogoSlug=slug; cfg.catalogoVisible=visible; cfg.catalogoMostrarPrecios=mostrarPrecios;
    schedulePersist(true);
    return{ok:true,url:data.url};
  }catch(e){
    return{ok:false,error:'No se pudo conectar al servidor. Verifica tu internet e intenta de nuevo.'};
  }
}

function renderRespaldo(){
  const el=$('respaldoEstado'); if(!el) return;
  if(!puntoAccountToken){
    el.innerHTML='<div class="desc">Crea una cuenta o inicia sesión para activar el respaldo.</div>';
    return;
  }
  el.innerHTML='<div class="desc">Consultando tus respaldos…</div>';
  listarRespaldos().then(data=>{
    const elNow=$('respaldoEstado'); if(!elNow) return; // la pantalla pudo cambiar mientras esperaba
    if(!data.ok){
      elNow.innerHTML='<div class="desc">No se pudo consultar tus respaldos ahora — revisa tu internet.</div>'
        +'<button class="btn-secondary" id="respaldoAhoraBtn" style="width:100%;margin-top:8px">Respaldar ahora</button>'
        +cuentaZonaPeligroHTML();
      return;
    }
    const respaldos=data.respaldos||[];
    // v1.18 — TODOS traen botón de recuperar, no solo el más reciente. Antes solo dejaba
    // elegir el de hoy; pedido de Jonathan: si tiene respaldo de ayer, de hace 3 días o de
    // hace una semana, tiene que poder elegir ESE, por ejemplo si el de hoy ya quedó mal por
    // algo que pasó en el dispositivo viejo. Vienen ordenados del más nuevo al más viejo (el
    // backend ya los manda así, ORDER BY fecha DESC).
    const filas=respaldos.map((r,i)=>{
      const s=r.resumen||{};
      return '<div class="cur-row" style="padding:8px 0;display:flex;align-items:center;justify-content:space-between;gap:8px"><div><div class="nm">'+r.fecha+(i===0?' <span style="color:var(--text-faint);font-weight:400">(el más reciente)</span>':'')+'</div>'
        +'<div class="sub">'+(s.productos||0)+' productos · '+(s.pedidos||0)+' pedidos · '+(s.clientes||0)+' clientes'
        +(s.porCobrarBs?' · '+fmtBs(s.porCobrarBs)+' por cobrar':'')+'</div></div>'
        +'<button class="btn-secondary" data-recuperar-respaldo="'+r.id+'" style="flex-shrink:0">Recuperar</button>'
        +'</div>';
    }).join('') || '<div class="desc">Todavía no tienes respaldos.</div>';
    elNow.innerHTML='<div class="desc">'+respaldos.length+' de '+data.cupo+' respaldos usados'
      +(data.cupo===2?' (gratis — actívalo con tu código para subir a 30).':'.')+'</div>'
      // sin esta nota parece que "Respaldar ahora" no hizo nada: se guarda UNO POR DÍA, así que
      // tocarlo varias veces el mismo día actualiza el de hoy en vez de agregar uno nuevo.
      +'<div class="desc" style="margin:-2px 0 8px">Se guarda un respaldo por día. Si respaldas otra vez hoy, se actualiza el de hoy con lo último que tengas.</div>'
      +filas
      +'<button class="btn-secondary" id="respaldoAhoraBtn" style="width:100%;margin-top:8px">Respaldar ahora</button>'
      +cuentaZonaPeligroHTML();
  });
}

// ---------- contabilidad mensual en la nube (backend v1.6) ----------
// El respaldo normal (arriba) solo cubre los últimos 30 días — un mes ya cerrado se cae de
// esa ventana antes de que nadie piense en respaldarlo. Contabilidad se declara por MES
// calendario, así que necesita su propia foto permanente por mes, tomada apenas ese mes
// cierra. contabData()/mesCalDates() ya existen (Contabilidad → Exportar para el contador,
// v26_4 del proyecto original) — acá solo se reutilizan, no se calcula nada nuevo.
function mesAnteriorYYYYMM(){
  const d=new Date(); d.setDate(1); d.setMonth(d.getMonth()-1);
  return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0');
}
function armarContabilidadMensual(mes){
  const d=contabData(mesCalDates(mes));
  return {
    mes,
    resumen:{
      ingresosBs:d.ingresosBs, ingresosSinIvaBs:d.ingresosSinIvaBs, costoMercanciaBs:d.costoMercanciaBs,
      egresosBs:d.egresosBs, egresosDeduciblesBs:d.egresosDeduciblesBs, devolucionesBs:d.devolucionesBs,
      gananciaBrutaBs:d.gananciaBrutaBs, gananciaNetaBs:d.gananciaNetaBs,
      ivaDebitoBs:d.ivaDebitoBs, ivaCreditoFacturasBs:d.ivaCreditoFacturasBs, diferenciaSeniatBs:d.diferenciaSeniatBs,
      cobradoBs:d.cobradoBs, fiadoBs:d.fiadoBs
    },
    // detalle: para que, al restaurar, los exportadores de CSV que ya existen (Libro de
    // Ventas/Compras) funcionen solos con esto en orders/egresos — sin construir nada nuevo.
    detalle:{ventas:d.ventas, egresos:d.egs}
  };
}
async function enviarContabilidadMensual(){
  if(!puntoAccountToken) return {ok:false};
  try{
    const datos=armarContabilidadMensual(mesAnteriorYYYYMM());
    // un mes sin ninguna venta ni egreso no vale la pena guardarlo (ej. negocio recién creado)
    if(!datos.detalle.ventas.length && !datos.detalle.egresos.length) return {ok:true, omitido:true};
    const res=await fetch('/wp-json/punto/v1/contabilidad-mensual',{
      method:'POST', headers:{'Content-Type':'application/json','Authorization':'Bearer '+puntoAccountToken},
      body:JSON.stringify(datos)
    });
    const data=await res.json().catch(()=>null);
    if(!res.ok||!data||!data.ok) return {ok:false, error:(data&&data.message)||'No se pudo guardar la contabilidad del mes.'};
    return {ok:true};
  }catch(e){
    return {ok:false, error:'No se pudo conectar al servidor.'};
  }
}

// token que se manda al servidor para operaciones de negocio. En el teléfono que activó el
// código es el de negocio; en un teléfono nuevo (recuperado) ese no existe y va el de cuenta —
// el servidor acepta los dos (ver punto_negocio_por_token en el plugin).
function tokenNegocioParaApi(){ return puntoToken||puntoAccountToken; }
async function syncPendingOrders(){
  if(!esNegocioActivo() || !tokenNegocioParaApi() || syncing) return;
  if(typeof navigator!=='undefined' && 'onLine' in navigator && !navigator.onLine) return;
  // v4 — se exige o.uuid, no solo !o.synced. Sin esto entraban al lote las órdenes de
  // muestra (y cualquier venta anterior a que existiera el uuid), que salen con uuid
  // undefined. El servidor las descarta porque no puede deduplicarlas, nunca las confirma,
  // y el cliente las vuelve a mandar en cada venta, en cada 'online' y en cada carga:
  // un reintento infinito de algo que jamás va a poder guardarse. De paso, los datos de
  // muestra ya no ensucian el respaldo en la nube de un negocio real.
  const pendientes=orders.filter(o=>o.uuid&&!o.synced);
  if(!pendientes.length) return;
  syncing=true;
  try{
    const res=await fetch('/wp-json/punto/v1/sync',{
      method:'POST',
      headers:{'Content-Type':'application/json','Authorization':'Bearer '+tokenNegocioParaApi()},
      body:JSON.stringify({ventas:pendientes.map(o=>({uuid:o.uuid, payload:o, fecha:o.date, monto_bs:o.totalBs}))})
    });
    if(res.ok){
      const data=await res.json().catch(()=>({}));
      const okUuids=new Set(data.sincronizadas_uuids || pendientes.map(o=>o.uuid));
      let cambios=false;
      orders.forEach(o=>{ if(!o.synced && okUuids.has(o.uuid)){ o.synced=true; cambios=true; } });
      if(cambios) schedulePersist(true);
    }
  }catch(e){ /* offline o el servidor no respondió — se reintenta con el próximo 'online' o el intervalo */ }
  syncing=false;
  if(typeof renderActivacion==='function') renderActivacion();
}

// ---------- stock multi-caja (v1.8, Opción B) ----------
// Solo tiene sentido con cuenta (dos dispositivos se reconocen como "el mismo negocio" por
// estar logueados en la MISMA cuenta, no por reactivar el código — eso ya funciona hoy sin
// nada nuevo). El servidor exige negocio activo además (403 si no); esNegocioActivo() es solo
// un atajo local para no ni intentarlo cuando ya sabemos que este dispositivo no está activado
// con código de pago — la prueba gratis no alcanza para esto (ver esPremium() vs esNegocioActivo()).
let syncingStock=false;
async function sincronizarStock(){
  if(!puntoAccountToken || !esNegocioActivo() || syncingStock) return;
  if(typeof navigator!=='undefined' && 'onLine' in navigator && !navigator.onLine) return;
  syncingStock=true;
  try{
    // 1) reporta PRIMERO lo que este dispositivo ya sabe que vendió/devolvió/corrigió — así
    // queda sumado en el servidor antes de jalar el número de vuelta, la ventana en la que
    // este mismo dispositivo vería su propio movimiento "desaparecido" es la más chica posible.
    const pendientes=Object.keys(stockPendiente).filter(id=>stockPendiente[id]);
    if(pendientes.length){
      const res=await fetch('/wp-json/punto/v1/stock/mover',{
        method:'POST', headers:{'Content-Type':'application/json','Authorization':'Bearer '+puntoAccountToken},
        body:JSON.stringify({movimientos:pendientes.map(id=>({producto_id:id,delta:stockPendiente[id]}))})
      });
      if(res.ok){ pendientes.forEach(id=>delete stockPendiente[id]); schedulePersist(true); }
    }
    // 2) jala el número real (suma de todas las cajas del negocio) y lo aplica local.
    const qs=stockSyncDesde?('?desde='+encodeURIComponent(stockSyncDesde)):'';
    const res2=await fetch('/wp-json/punto/v1/stock'+qs,{ headers:{'Authorization':'Bearer '+puntoAccountToken} });
    const data=await res2.json().catch(()=>null);
    if(res2.ok && data && data.ok){
      let cambios=false;
      (data.stock||[]).forEach(row=>{
        const p=products.find(x=>x.id===row.producto_id);
        if(p && p.stock!==row.stock){ p.stock=row.stock; cambios=true; }
      });
      if(data.servidor_ahora) stockSyncDesde=data.servidor_ahora;
      if(cambios){
        // stock en rojo cuando queda negativo ya es la señal existente de "algo pasó" — si dos
        // cajas vendieron lo mismo a la vez, se ve exactamente ahí, sin construir una alerta aparte.
        renderStock(); if(currentSection==='catalogo')renderCatalogo(); renderGrid();
      }
      schedulePersist(true);
    }
  }catch(e){ /* sin internet — se reintenta en el próximo intento, sin bloquear nada */ }
  syncingStock=false;
}

// ---------- sincronización en tiempo real (Ably) ----------
// Mismo candado que el stock de arriba: solo negocio activo (pago), esNegocioActivo() y no
// esPremium() — la prueba gratis no incluye multi-dispositivo. Ably por sí solo es "dispara y
// olvida": si el otro dispositivo está apagado o sin señal cuando se publica, se lo pierde para
// siempre. Por eso emitCambio() hace DOS cosas: publica en vivo por Ably (si hay conexión, para
// que se sienta instantáneo) Y lo manda a /cambios del servidor con reintento (igual criterio
// que stockPendiente arriba) — esa segunda vía es la que de verdad garantiza que nada se pierde,
// sin importar cuánto tiempo estuvo apagado el otro dispositivo, sin depender de ninguna ventana
// de historial pagada de Ably.
let syncingCambios=false;

function emitCambio(tipo,payload){
  if(!esNegocioActivo()) return; // gratis/trial: sin tiempo real, sin gasto de red de más
  cambiosPendientes.push({tipo,payload,origen:idDispositivo()});
  schedulePersist(true);
  if(ablyChannel){
    try{ ablyChannel.publish('cambio',{tipo,payload}); }catch(e){}
  }
  flushCambiosPendientes();
}

async function flushCambiosPendientes(){
  if(!esNegocioActivo() || !puntoAccountToken || syncingCambios) return;
  if(typeof navigator!=='undefined' && 'onLine' in navigator && !navigator.onLine) return;
  if(!cambiosPendientes.length) return;
  syncingCambios=true;
  try{
    // uno a la vez, en orden: si uno falla se corta ahí y el resto queda en cola para el
    // próximo intento — mismo criterio "sin backoff, se reintenta con el próximo disparador"
    // que syncPendingOrders/sincronizarStock arriba; el volumen de una bodega no pide más.
    while(cambiosPendientes.length){
      const c=cambiosPendientes[0];
      const res=await fetch('/wp-json/punto/v1/cambios',{
        method:'POST', headers:{'Content-Type':'application/json','Authorization':'Bearer '+puntoAccountToken},
        body:JSON.stringify(c)
      });
      if(!res.ok) break;
      cambiosPendientes.shift();
    }
    schedulePersist(true);
  }catch(e){ /* sin internet — se reintenta en el próximo intento, sin bloquear nada */ }
  syncingCambios=false;
}

async function ponerseAlDiaConCambios(){
  if(!esNegocioActivo() || !puntoAccountToken) return;
  try{
    const qs=cambiosSyncDesde?('?desde='+encodeURIComponent(cambiosSyncDesde)):'';
    const res=await fetch('/wp-json/punto/v1/cambios'+qs,{ headers:{'Authorization':'Bearer '+puntoAccountToken} });
    const data=await res.json().catch(()=>null);
    if(!res.ok || !data || !data.ok) return;
    // el propio origen se salta: ya lo tenemos (lo publicamos nosotros mismos).
    (data.cambios||[]).forEach(c=>{ if(c.origen!==idDispositivo()) aplicarCambioRemoto(c.tipo,c.payload); });
    if(data.ultimo_id) cambiosSyncDesde=data.ultimo_id;
    schedulePersist(true);
  }catch(e){ /* sin internet — se reintenta en el próximo intento */ }
}

// Aplica un cambio llegado de OTRO dispositivo de la misma cuenta (por Ably en vivo, o al
// ponerse al día con /cambios). El servidor no valida payload (mismo criterio que /sync y
// /stock/mover: guarda, no interpreta) — cada rama revisa lo mínimo antes de tocar el estado
// local, y un tipo desconocido se ignora sin más (una versión futura del cliente podría mandar
// tipos que esta versión todavía no entiende, y eso no debe romper nada).
function aplicarCambioRemoto(tipo,payload){
  try{
    if(!payload||typeof payload!=='object') return;
    if(tipo==='cfg'){
      // 'perfil' nunca llega hasta acá (ver emitCambio en el guardado de Configuración): es
      // dato de CUENTA, no de negocio, y ya tiene su propio camino de sync (/perfil). Para el
      // resto, applyGroup(g,snap) es la MISMA función que usa "descartar cambios sin guardar" —
      // ya sabe que 'apariencia' vive en activeTheme (no cfg.theme), 'negocio' en logoDataUrl
      // (no cfg.logo), 'cobros' en el arreglo payMethods (no cfg.pay), etc., y ya repinta lo
      // que cada grupo necesita. Reimplementar ese mapeo acá a mano sería duplicar lógica y
      // arriesgarse a que un campo quede mal aplicado.
      if(!payload.group||!payload.snap) return;
      applyGroup(payload.group,payload.snap);
      cfgSnapshots[payload.group]=payload.snap; // re-ancla "sin guardar" contra el nuevo estado
      schedulePersist(true);
      return;
    }
    if(!payload.id) return; // el resto de los tipos son registros con id — sin id no hay qué aplicar
    if(tipo==='producto'){
      const i=products.findIndex(p=>p.id===payload.id);
      if(i>=0) Object.assign(products[i],payload); else products.push(payload);
      renderCatalogo();renderGrid();renderChips();renderStock();
    } else if(tipo==='producto_borrado'){
      const i=products.findIndex(p=>p.id===payload.id);
      if(i>=0){ products.splice(i,1); renderCatalogo();renderCart();renderGrid(); }
    } else if(tipo==='cliente'){
      const i=clients.findIndex(c=>c.id===payload.id);
      if(i>=0) Object.assign(clients[i],payload); else clients.push(payload);
      renderClientes();renderFiarList();
      if(currentSection==='porcobrar') renderPorCobrar();
    } else if(tipo==='cliente_borrado'){
      const i=clients.findIndex(c=>c.id===payload.id);
      if(i>=0){ clients.splice(i,1); renderClientes(); if(currentSection==='porcobrar')renderPorCobrar(); }
    } else if(tipo==='categoria'){
      const i=categories.findIndex(c=>c.id===payload.id);
      if(i>=0) Object.assign(categories[i],payload); else categories.push(payload);
      renderChips();
    } else if(tipo==='egreso'){
      const i=egresos.findIndex(e=>e.id===payload.id);
      if(i>=0) Object.assign(egresos[i],payload); else egresos.push(payload);
      if(currentSection==='contabilidad') renderContabilidad();
    } else if(tipo==='egreso_borrado'){
      const i=egresos.findIndex(e=>e.id===payload.id);
      if(i>=0){ egresos.splice(i,1); if(currentSection==='contabilidad')renderContabilidad(); }
    } else {
      return; // tipo desconocido: no toca nada ni reprograma el guardado
    }
    schedulePersist(true);
  }catch(e){ /* un cambio remoto con forma rara no debe tumbar el POS */ }
}

// Conecta (o reconecta) el canal de tiempo real de este negocio. No-op seguro si: no hay
// negocio activo (gratis/trial), no hay negocio_id todavía, no cargó el SDK de Ably (ver la
// nota junto al <script src> en el <head> — el POS sigue funcionando igual sin él), o ya hay
// una conexión abierta — así se puede llamar varias veces sin cuidado (login, boot,
// reconexión) sin abrir conexiones duplicadas.
function conectarTiempoReal(){
  if(!esNegocioActivo() || !puntoAccountToken || !puntoNegocioId) return;
  if(ablyClient) return;
  if(typeof Ably==='undefined') return;
  try{
    ablyClient=new Ably.Realtime({
      authUrl:'/wp-json/punto/v1/ably-token',
      authMethod:'POST',
      authHeaders:{'Authorization':'Bearer '+puntoAccountToken},
      echoMessages:false // esta conexión no necesita recibir de vuelta lo que ella misma publicó
    });
    ablyChannel=ablyClient.channels.get('negocio-'+puntoNegocioId);
    ablyChannel.subscribe('cambio',(msg)=>{
      try{ aplicarCambioRemoto(msg.data.tipo,msg.data.payload); }catch(e){}
    });
    // cada (re)conexión es también el momento de ponerse al día por si acaso: Ably cubre huecos
    // cortos solo, /cambios cubre cualquier hueco sin importar cuánto duró.
    ablyClient.connection.on('connected',ponerseAlDiaConCambios);
  }catch(e){ ablyClient=null; ablyChannel=null; }
}
function desconectarTiempoReal(){
  if(ablyClient){ try{ ablyClient.close(); }catch(e){} }
  ablyClient=null; ablyChannel=null;
}

// ---------- UI de Activación (Configuración → Negocio) ----------
function renderActivacion(){
  const el=$('activacionEstado'); if(!el) return;
  // v1.12 — esNegocioActivo() (no esPremium()): esta pantalla es sobre el código de PAGO — el
  // respaldo en la nube, el sync de ventas y el stock multi-caja. La prueba gratis no cubre
  // nada de eso (solo Informes/Contabilidad, que se calculan local), así que no puede mostrar
  // "✓ Activado" solo por tener trial — sería mentirle al dueño sobre si su negocio sincroniza.
  if(esNegocioActivo()){
    const pendientes=orders.filter(o=>o.uuid&&!o.synced).length;
    el.innerHTML='<div class="paymix-summary ok" style="margin-bottom:8px">✓ Activado'+(puntoNegocioId?' — negocio #'+puntoNegocioId:'')+'</div>'
      +'<div class="desc">'+(pendientes>0?(pendientes+' venta(s) pendiente(s) de sincronizar.'):'Todo sincronizado con el servidor.')+'</div>'
      +'<button class="btn-secondary" id="activacionSyncBtn" style="width:100%;margin-top:8px">Sincronizar ahora</button>';
    return;
  }
  const activarFormHtml='<div class="cfg-row" style="margin-top:12px"><label>Código de activación</label><input class="cfg-input" id="activacionCodigo" type="text" placeholder="Ej: FRIXPOS-AB12-CD34"></div>'
    +'<button class="btn-primary" id="activacionBtn" style="width:100%;margin-top:6px">Activar</button>'
    +'<div class="desc" id="activacionMsg" style="margin-top:8px"></div>'
    +'<div class="desc" style="margin-top:10px">¿Todavía no tienes código? <a href="/activar-codigo" target="_blank" rel="noopener">Mira cómo conseguirlo</a> · ¿Algún problema? <a href="/soporte" target="_blank" rel="noopener">Escríbenos a soporte</a></div>';
  if(esPremiumPorTrial()){
    const dias=diasRestantesTrial();
    el.innerHTML='<div class="premium-box">'
        +'<div class="premium-head"><span class="premium-tag">Prueba gratis</span></div>'
        +'<div class="desc" style="margin:0">Te '+(dias<=0?'queda menos de 1 día':dias===1?'queda 1 día':'quedan '+dias+' días')+' de <b>Informes y Contabilidad completos</b>, sin código. Actívalo antes de que termine para no perderlos — de paso sumas respaldo en la nube (hasta 30 copias con fotos) y tu negocio sincronizado en varios teléfonos, que la prueba gratis no incluye.</div>'
      +'</div>'
      +activarFormHtml;
  } else {
    el.innerHTML='<div class="premium-box">'
      +'<div class="premium-head"><span class="premium-tag">FrixPOS Premium</span></div>'
      +'<div class="premium-list">'
        +'<div>Informes completos — ganancia real, tendencia, qué producto te deja más</div>'
        +'<div>Contabilidad completa — IVA, egresos y los archivos para tu contador</div>'
        +'<div>Respaldo en la nube — hasta 30 copias, con las fotos de tus productos</div>'
        +'<div>Tu negocio en varios teléfonos, con el mismo catálogo y stock</div>'
      +'</div>'
      +'<div class="desc" style="margin:10px 0 0">Vender, cobrar, fiar y llevar tu caja es <b>gratis para siempre</b>, con o sin código.</div>'
    +'</div>'
      +activarFormHtml;
  }
}
// v4 — borrado total, con doble confirmación a propósito: es la única acción de la app que
// destruye datos sin vuelta atrás, así que no puede quedar a un solo toque de distancia.
// ===== Monedas personalizadas — UI (v5) =====
function renderMonedas(){
  const cont=$('monedasList'); if(!cont)return;
  const hayParalelo=cfg.paralelo>0;
  const aviso=$('monedasAvisoParalelo'); if(aviso)aviso.style.display=hayParalelo?'none':'';
  const btn=$('monedaAddBtn'); if(btn){ btn.disabled=!hayParalelo; btn.style.opacity=hayParalelo?'':'.5'; }
  const list=cfg.monedas||[];
  if(!list.length){ cont.innerHTML='<div class="desc">Todavía no tienes otras monedas. Con bolívares y dólares es suficiente para la mayoría de las bodegas.</div>'; return; }
  cont.innerHTML=list.map(m=>
    '<div class="cfg-block" style="margin:10px 0;padding:12px" data-moneda-card="'+m.id+'">'
    +'<div class="cfg-row"><label>Nombre</label><input class="cfg-input" data-mon-nombre="'+m.id+'" type="text" value="'+String(m.nombre||'').replace(/"/g,'&quot;')+'" placeholder="Ej: Pesos"></div>'
    +'<div class="cfg-row"><label>Cuántos '+(m.nombre||'')+' por 1 dólar</label><input class="cfg-input" data-mon-tasa="'+m.id+'" type="number" inputmode="decimal" value="'+(m.tasa||'')+'" placeholder="Ej: 3500"></div>'
    +'<div class="cfg-row"><label>Redondear a</label><input class="cfg-input" data-mon-round="'+m.id+'" type="number" inputmode="decimal" value="'+(m.redondeo||0)+'" placeholder="0 = sin redondeo"></div>'
    +'<div class="desc" style="margin-top:6px">'+(hayParalelo&&m.tasa>0
        ? '✓ Activa. Aparece en el carrito, en los cobros como <b>Efectivo '+(m.nombre||'')+'</b> y con su gaveta en el Cajón.'
        : (!hayParalelo?'Inactiva: falta cargar el dólar paralelo arriba.':'Inactiva: falta la tasa.'))+'</div>'
    +'<button class="btn-secondary" data-mon-del="'+m.id+'" style="width:100%;margin-top:8px;color:var(--red)">Eliminar '+(m.nombre||'moneda')+'</button>'
    +'</div>').join('');
}
const _monedasBlock=$('monedasBlock');
if(_monedasBlock){
  _monedasBlock.addEventListener('click',e=>{
    if(e.target.closest('#monedaAddBtn')){
      // la regla que pediste: sin paralelo no se puede activar ninguna otra tasa
      if(!(cfg.paralelo>0)){ askConfirm('Para activar otra tasa debe estar habilitado el dólar paralelo. Cárgalo arriba y vuelve.',()=>{}); return; }
      if(!Array.isArray(cfg.monedas))cfg.monedas=[];
      cfg.monedas.push({id:nuevoMonedaId(),nombre:'Moneda '+(cfg.monedas.length+1),tasa:0,redondeo:0});
      renderMonedas(); onRateChange();
      return;
    }
    const del=e.target.closest('[data-mon-del]');
    if(del){
      const id=del.dataset.monDel;
      const m=monedaById(id);
      askConfirm('¿Eliminar '+((m&&m.nombre)||'esta moneda')+'? Sus precios dejan de mostrarse y su método de cobro en efectivo desaparece. Las ventas ya hechas no se tocan.',()=>{
        cfg.monedas=(cfg.monedas||[]).filter(x=>x.id!==id);
        if(cfg.debtMode===id)cfg.debtMode='paralelo';
        if(activeCurrency===id)activeCurrency='bs';
        renderMonedas(); onRateChange();
      });
    }
  });
  _monedasBlock.addEventListener('input',e=>{
    const t=e.target;
    const id=t.dataset.monNombre||t.dataset.monTasa||t.dataset.monRound;
    if(!id)return;
    const m=monedaById(id); if(!m)return;
    if(t.dataset.monNombre!==undefined&&t.dataset.monNombre)m.nombre=t.value;
    if(t.dataset.monTasa)m.tasa=parseFloat(t.value)||0;
    if(t.dataset.monRound)m.redondeo=parseFloat(t.value)||0;
    // no se re-renderiza la lista entera a propósito: borraría lo que se está escribiendo
    syncMonedaPayMethods();
    renderCurrToggle();renderRatesStrip();renderGrid();renderCart();renderPayConfig();renderPay();updateExample();
  });
}

const _wipeBtn=$('wipeBtn');
if(_wipeBtn){
  _wipeBtn.addEventListener('click',()=>{
    askConfirm('¿Borrar TODO lo guardado en este teléfono? Productos, ventas, clientes, turnos y configuración.',()=>{
      askConfirm('Última confirmación: esto NO se puede deshacer. ¿Seguro?',async ()=>{
        await wipeLocalData();
        location.reload();
      });
    });
  });
}

const _activacionBlockEl=$('activacionBlock');
if(_activacionBlockEl){
  _activacionBlockEl.addEventListener('click',async e=>{
    if(e.target.id==='activacionBtn'){
      const codigo=$('activacionCodigo')?$('activacionCodigo').value:'';
      e.target.disabled=true; e.target.textContent='Activando…';
      const r=await activarNegocio(codigo, cfg.bizName);
      if(r.ok){ renderActivacion(); }
      else{
        e.target.disabled=false; e.target.textContent='Activar';
        const msg=$('activacionMsg'); if(msg){ msg.textContent=r.error; msg.style.color='var(--red)'; }
      }
    }
    if(e.target.id==='activacionSyncBtn'){
      e.target.disabled=true; e.target.textContent='Sincronizando…';
      await syncPendingOrders();
      // syncPendingOrders() se sale temprano cuando no hay nada pendiente (el caso normal) y en
      // ese camino nunca llamaba a renderActivacion(): el botón se quedaba en "Sincronizando…"
      // deshabilitado para siempre, como si se hubiera trabado. Se repinta siempre desde acá.
      renderActivacion();
    }
  });
}

async function borrarCuenta(){
  if(!puntoAccountToken) return {ok:false, error:'No has iniciado sesión.'};
  try{
    const res=await fetch('/wp-json/punto/v1/cuenta/borrar',{
      method:'POST', headers:{'Authorization':'Bearer '+puntoAccountToken}
    });
    const data=await res.json().catch(()=>null);
    if(!res.ok||!data||!data.ok) return {ok:false, error:(data&&data.message)||'No se pudo borrar la cuenta. Intenta de nuevo.'};
    return {ok:true};
  }catch(e){
    return {ok:false, error:'No se pudo conectar al servidor. Verifica tu internet e intenta de nuevo.'};
  }
}
const _respaldoBlockEl=$('respaldoBlock');
if(_respaldoBlockEl){
  _respaldoBlockEl.addEventListener('click',async e=>{
    if(e.target.id==='borrarCuentaBtn'){
      askConfirm('¿Borrar tu cuenta? Se borra tu acceso, tu negocio queda desactivado (se acaba el respaldo y el stock compartido entre cajas), y este dispositivo se resetea por completo. Tus ventas ya sincronizadas no se tocan. No se puede deshacer.',async ()=>{
        e.target.disabled=true; e.target.textContent='Borrando…';
        const r=await borrarCuenta();
        if(r.ok){ await wipeLocalData(); location.reload(); return; }
        e.target.disabled=false; e.target.textContent='Borrar mi cuenta';
        askConfirm(r.error,()=>{});
      });
      return;
    }
    if(e.target.id==='respaldoAhoraBtn'){
      e.target.disabled=true; e.target.textContent='Respaldando…';
      const r=await guardarRespaldo();
      if(r.ok){ renderRespaldo(); }
      else{
        e.target.disabled=false; e.target.textContent='Respaldar ahora';
        askConfirm(r.error,()=>{});
      }
      return;
    }
    const recBtn=e.target.closest('[data-recuperar-respaldo]');
    if(recBtn){
      const id=recBtn.dataset.recuperarRespaldo;
      askConfirm('¿Reemplazar lo que tienes en este dispositivo con ese respaldo? Tu catálogo, stock, clientes y pedidos actuales de este teléfono se pierden. No se puede deshacer.',async ()=>{
        recBtn.disabled=true; recBtn.textContent='Recuperando…';
        const r=await restaurarRespaldo(id);
        if(r.ok){ location.reload(); return; }
        recBtn.disabled=false; recBtn.textContent='Recuperar';
        if(r.requiereActivacion){ askConfirm('Necesitas activar tu código para recuperar el respaldo completo — actívalo arriba, en Activación, y vuelve a intentar.',()=>{}); }
        else{ askConfirm(r.error,()=>{}); }
      });
    }
  });
}

// v4 — LA PIEZA QUE FALTABA. initState() devolvía los datos a memoria, pero la pantalla ya
// se había pintado con los valores de fábrica: los inputs de Configuración, el toggle de
// modelo de precios, el tema claro/oscuro, el tamaño de letra y la densidad se llenan más
// arriba en este mismo script, de forma síncrona, ANTES de que IndexedDB conteste. Resultado:
// cfg.bcv podía valer 800 y el input mostrar 755; el nombre y el logo del negocio no volvían.
// Por eso se sentía que "no guardaba nada" aunque sí guardaba.
// Se reutiliza applyGroup(), que ya sabía hacer exactamente esto (lo usaba para descartar
// cambios sin guardar en Configuración) — no hay lógica nueva que se pueda desincronizar.
function rehydrateUI(){
  migrarMonedasV4();
  renderMonedas();
  ['negocio','perfil','apariencia','precios','cobros'].forEach(g=>{ try{ applyGroup(g,captureGroup(g)); }catch(e){} });
}

// se usa al arrancar Y después de restaurar un respaldo (recuperar-o-cero, más abajo) — en
// los dos casos el estado cambió por debajo de una UI que ya estaba pintada con otra cosa.
function refrescarUI(){
  renderNav();renderSwatches();renderRatesStrip();renderCurrToggle();
  renderPayConfig();renderPay();renderFiarList();renderGrid();renderCart();renderPreview();updateExample();
  snapshotAllGroups();
  renderActivacion();
  renderRespaldo();
  renderPerfil();
  renderCatalogoDigital();
  applyRoleVisibility(); // si el teléfono quedó guardado en modo cajero, arranca ya bloqueado
}
initState().then(()=>{
  rehydrateUI();
  if(authed){ $('authGate').classList.add('hide'); }
  $('authGate').classList.remove('booting');
  refrescarUI();
  setInterval(()=>schedulePersist(false), 4000); // red de seguridad — cubre cualquier cambio que no tenga un hook explícito
  if(typeof window!=='undefined'){
    window.addEventListener('online', syncPendingOrders);
    window.addEventListener('online', sincronizarStock);
    window.addEventListener('online', conectarTiempoReal);
    window.addEventListener('online', flushCambiosPendientes);
    window.addEventListener('online', ponerseAlDiaConCambios);
    syncPendingOrders(); // intento silencioso al cargar, por si ya hay conexión y quedaron ventas pendientes
    sincronizarStock(); // idem — trae el stock real de las otras cajas apenas abre, si aplica
    conectarTiempoReal(); // idem — reconecta el canal si el dispositivo ya estaba logueado (sesión restaurada, sin pasar por enterApp)
    flushCambiosPendientes(); // por si quedó algo pendiente de mandar de la sesión anterior
    // cada 60s, no cada 4s como el autosave local: esto sí es una petición de red, no hay
    // que golpear el servidor a cada rato solo para preguntar "¿algo nuevo?" en dos cajas.
    setInterval(sincronizarStock, 60000);
    setInterval(flushCambiosPendientes, 60000);
  }
});
})();
</script>
<!-- FRIXPOS:SCRIPT-FIN -->
<script>
// registro del service worker — servido en la raíz del sitio (/sw.js) vía
// punto_pwa_intercept() en functions.php, para que pueda controlar la página
// sin importar el slug que le hayas puesto (ver nota de la plantilla arriba).
if('serviceWorker' in navigator){
  window.addEventListener('load',function(){
    navigator.serviceWorker.register('/sw.js').catch(function(){});
  });
}
</script>
</body>
</html>
