# FrixPOS — Backend (plugin de WordPress) · v1.18

**Nota:** el producto pasó a llamarse **FrixPOS** (antes "Punto"). El historial de versiones de abajo queda tal como se escribió en su momento — donde diga "Punto" es solo el nombre viejo, el código y el comportamiento son los mismos.

**v1.26 agrega la bitácora de cambios — la red de seguridad de v1.25 para cuando un dispositivo estuvo apagado o sin señal.**

- **Por qué hacía falta:** Ably solo, "dispara y olvida", entrega el mensaje a quien esté conectado en ese instante. Si el otro dispositivo de la cuenta estaba apagado, ese cambio se perdía para siempre — sin importar la ventana de historial de Ably (que además depende del plan pagado en Ably, no de este servidor).
- **`POST /wp-json/punto/v1/cambios`** — con `Authorization: Bearer <token de cuenta>`, exige negocio activo. Body `{origen, tipo, payload}`. El dispositivo que publica algo por Ably lo guarda acá EN PARALELO (mismo payload, tal cual) — el servidor no lo valida ni lo interpreta, solo lo guarda, igual que `/sync` y `/stock/mover`. Responde `{id}`: el cursor de esa fila.
- **`GET /wp-json/punto/v1/cambios?desde=<id>`** — mismo Bearer, mismo candado. Devuelve los cambios con `id` mayor al cursor que mandes, en orden, más `ultimo_id` (el cursor a guardar para la próxima). **Sin `desde`, NO reproduce la bitácora completa** — el estado actual ya lo sirven `/stock`, `/respaldo`, etc.; esta bitácora es solo para ponerse al día tras una desconexión, no para el arranque inicial de un dispositivo nuevo.
- **`origen`** identifica el DISPOSITIVO que publicó, no la cuenta — dos teléfonos de la misma cuenta son orígenes distintos. El cliente lo usa para no reaplicarse su propio cambio al ponerse al día.
- **No es historial permanente.** Se poda a los últimos 500 cambios por negocio en cada inserción — es una red de seguridad de un rato desconectado, no un respaldo. Para eso ya existe `/respaldo`.
- Tabla nueva `wp_punto_cambios`. `PUNTO_DB_VERSION` subió a 1.9 — se crea sola al activar/actualizar.

**v1.25 agrega la sincronización en tiempo real entre dispositivos (Ably), solo para cuentas premium.**

- **`POST /wp-json/punto/v1/ably-token`** — con `Authorization: Bearer <token de cuenta>`. Exige negocio activo (mismo candado que `/stock`); sin eso, 403 con `requiere_activacion:true`. Devuelve un `TokenDetails` de Ably (no la API Key) válido 1 hora, con permiso **solo** sobre el canal `negocio-{id}` de esa cuenta — ni ve ni puede tocar el canal de otro negocio.
- **La API Key completa de Ably no sale nunca del servidor.** Se guarda en `wp_options` (`punto_ably_api_key`) desde el panel nuevo **Punto → Tiempo real**, y de ahí este endpoint la usa para pedirle un token a Ably vía HTTP directo — un `TokenRequest` "sin firmar" autenticado con Basic Auth (la key completa como credencial), en vez de calcular el `mac`/`nonce` a mano. Se eligió ese camino y no el SDK oficial de Ably para no depender de Composer, que en hosting compartido (Hostinger y similares) suele no estar disponible.
- **Ably no reemplaza ninguna API existente, solo avisa.** El cliente sigue pidiendo el dato de verdad a `/stock`, `/respaldo`, etc. — por el canal de Ably solo viaja un aviso liviano de "algo cambió" entre los dispositivos de la misma cuenta, para que refresquen al instante en vez de esperar el próximo sync manual. Así no hay dos caminos con la misma lógica de negocio, ni un dispositivo puede inventarse un dato publicando directo en el canal.
- **Qué le falta al cliente (POS) para aprovechar esto:** todavía no se conecta a Ably. Falta, del lado de `page-pos.php`: cargar el SDK `ably.js`, conectarse con `authUrl: /wp-json/punto/v1/ably-token` (mandando el `Authorization` de siempre) en vez de una API Key, suscribirse al canal una vez logueado con negocio activo, publicar un aviso corto cuando cambie algo local (venta, stock, catálogo, config) y, al recibir uno de otro dispositivo, refrescar desde la API existente. Queda para la próxima entrega.

**v1.16 agrega el sitio de marketing: formulario de leads + panel de Solicitudes, y una lista blanca de correos reales en el registro.**

- **`[punto_formulario motivo="comprar|soporte"]`** — shortcode nuevo, pégalo en cualquier página tuya de WordPress (Elementor, bloques, HTML a mano, da igual). Formulario autocontenido — su CSS lleva prefijo propio, no hereda ni pelea con el estilo de la página donde lo pongas. Pide nombre, teléfono y correo; manda a `POST /solicitud` (pública, sin token — quien lo llena casi nunca tiene cuenta todavía). Tiene un honeypot silencioso contra bots.
- **Panel Punto → Solicitudes** (nuevo) — pestañas Pendientes/Atendidas, con enlace directo a WhatsApp y a mailto de cada una, y botón para marcar atendida.
- **Registro con lista blanca de correos** — `punto_api_registro` ahora rechaza cualquier dominio que no esté en `punto_dominio_correo_permitido()` (Gmail, Hotmail, Outlook, Yahoo, iCloud, y similares). Cierra la puerta a correos temporales/desechables sin estorbarle a un dueño de bodega real. Para sumar un dominio nuevo, se agrega a esa lista.
- Tabla nueva `wp_punto_solicitudes` (nombre, teléfono, correo, motivo, atendida, fecha). `PUNTO_DB_VERSION` subió a 1.8 — se crea sola al activar/actualizar.
- **Punto → Limpiar datos** ahora también borra Solicitudes.

**v1.14 cambia el modelo del respaldo: se guarda TODO de TODOS, y lo que se paga es poder recuperarlo.**

Antes, a las cuentas sin código activado se les iban quitando las fotos en silencio antes de guardar, y las fotos de producto ni siquiera se subían. O sea que un negocio que probaba gratis y después decidía pagar, ya había perdido lo suyo por el camino sin enterarse. Ahora:

- **`POST /respaldo/foto`** ya no exige negocio activo. **Todas las fotos suben**, pague o no. Único tope: 800 fotos por cuenta, y es de infraestructura, no de plan.
- **`POST /respaldo`** ya no le quita las fotos a nadie — se guarda el estado completo tal como llega. (`punto_respaldo_quitar_fotos_propias()` se eliminó.)
- **`GET /respaldo/{id}/completo`** sigue exigiendo negocio activo. **Ese es el único candado del plan**: el que no paga, no recupera.
- **`POST /perfil/foto`** (nuevo) — foto de perfil de la persona, gratis para todos. Se guarda en `user_meta` `punto_foto_url`; `GET /perfil` ahora la devuelve.
- **Panel Punto → Respaldos** (nuevo) — la pantalla que faltaba para ver qué hay guardado de cada quien, sin entrar a la base a mano. Lista: correo, nombre, foto, plan (pago/prueba/gratis), último respaldo, cuántos días guardados, conteos y tamaño en disco. Detalle por cuenta: sus datos personales, su configuración real (tasas BCV/Paralelo, IVA, monedas propias), su enlace de catálogo público, el historial de respaldos por día y su catálogo con fotos.
- **Catálogo maestro** — si un producto ya existe **sin foto** (típico de los importados por CSV) y un negocio sugiere ese mismo nombre **con foto**, ahora la foto se engancha a la fila existente para revisión en vez de descartarse. Sigue sin duplicar nombres.

# Punto — Backend (plugin de WordPress) · v1.13

**v1.13** agrega el Catálogo Digital: vitrina pública en `tudominio.com/p/{slug}`, **gratis para toda cuenta**, tenga o no negocio activado con código.

- **Por qué va atado a la CUENTA (`wp_user_id`) y no al negocio (`negocio_id`):** una cuenta sin código activado no tiene fila en `wp_punto_negocios` — atarlo ahí habría dejado a cualquiera que no paga sin poder publicar, y esto es gratis para todos a propósito (marketing/alcance, mismo criterio que el catálogo maestro).
- **`slug`** — nombre de usuario para la URL, campo separado del login (que sigue siendo el correo). Único entre todas las cuentas, se guarda en `user_meta` (`punto_catalogo_slug`), igual que `punto_catalogo_visible` (interruptor general) y `punto_catalogo_mostrar_precios` (apagado por defecto — precio opcional, decisión de producto: en Venezuela publicar sin precio es normal, evita desconfianza por tasa desactualizada).
- **`POST /catalogo-publico`** — con `Authorization: Bearer <token de cuenta>`. Reemplazo COMPLETO: borra lo que había de esa cuenta en `wp_punto_catalogo_publico` e inserta el catálogo tal como llega. El teléfono decide qué productos manda (los marcados visibles en la vitrina) — el servidor no filtra nada, solo guarda lo que le llega.
- **`POST /catalogo-publico/foto`** — mismo patrón que `/respaldo/foto` pero **sin exigir negocio activo** (gratis para todos). El único límite es de infraestructura, no de pago: 500 fotos propias por cuenta (contadas por un meta en cada adjunto de la Biblioteca de Medios) — ninguna bodega real se acerca a eso.
- **`GET /p/{slug}`** no es un endpoint REST — es una página completa, servida directo por el plugin vía `template_include` (rewrite rule `^p/([^/]+)/?$`, registrada en `init` y flusheada sola al instalar/actualizar). A propósito no depende del tema activo: es 100% dato del plugin. Búsqueda, categorías y scroll infinito (de 8 en 8) son todo client-side sobre el JSON que ya viene embebido en la página — no hay una llamada de red aparte, así que la búsqueda es instantánea.
- El botón de WhatsApp por producto usa `punto_phone` del perfil de la cuenta (Fase 7), normalizado a formato internacional (si empieza en `0`, se cambia por `58`). El mensaje pre-armado incluye el precio solo si `mostrar_precios` está activo.

**v1.12** agrega la prueba gratis de 60 días: toda cuenta nueva desbloquea Informes y Contabilidad completos por 60 días desde que se creó, sin necesitar código de activación.

- **Ancla al reloj del servidor, no al del teléfono.** El cálculo (`punto_cuenta_trial_info()`) usa `user_registered` (lo pone WordPress al crear el usuario, un `wp_create_user()` normal — el teléfono no lo puede tocar) contra `current_time('timestamp', true)` en UTC. Nadie gana ni pierde un día de prueba adelantando o atrasando el reloj de su celular.
- **`trial_activo` y `trial_vence`** viajan en las respuestas de `/registro`, `/login` y `GET /perfil` — así el cliente los tiene disponibles apenas hay sesión, sin una llamada extra.
- El cliente decide **cuánto tiempo confiar en ese dato sin poder reconfirmarlo** (offline demasiado tiempo = deja de confiar en el trial cacheado) — eso vive del lado del POS, el servidor solo entrega la verdad de ese instante.
- No hay endpoint nuevo: se apoyó en los tres que ya existían. Menos superficie, menos que mantener.

**v1.11** agrega el perfil de cuenta, separado de "Negocio" en el cliente. Desde v1.1, `/registro` escribía `punto_nombre`/`punto_tipo`/`punto_id_number`/`punto_phone`/`punto_address` como `user_meta` una sola vez, al crear la cuenta — no había forma de editarlos después ni de que un teléfono nuevo los recuperara aparte de lo que ya traía `/login`.

- **`GET /perfil`** — con `Authorization: Bearer <token de cuenta>`. Devuelve `email`, `nombre`, `tipo`, `id_number`, `phone`, `address` y `fecha_registro` (de `wp_users.user_registered`, sin tocar — sirve de ancla para lo que necesite saber hace cuánto existe la cuenta, ej. un período de prueba).
- **`POST /perfil`** — mismo `Authorization`. Body `{nombre, tipo, id_number, phone, address}`, sobreescribe esos `user_meta`. A propósito **no** toca `email` ni `password`: el correo cambiaría a dónde llega la recuperación de contraseña (necesitaría verificación, fuera de alcance) y la contraseña ya tiene su propio flujo en `/recuperar`.

El cliente sigue copiando estos mismos campos a la Configuración del negocio al registrarse/iniciar sesión (eso no cambió — sigue siendo el atajo para no escribir el nombre del negocio dos veces), pero ahora también tiene de dónde leer/escribir el perfil de la cuenta por separado.

**v1.7** agrega el catálogo maestro: tabla `wp_punto_catalogo_maestro`, compartida entre TODOS los negocios (nunca lleva costo ni precio — eso es privado de cada uno).

- **`GET /catalogo-maestro/buscar?q=...`** — pública, sin necesitar cuenta ni código. Busca por nombre normalizado (sin tildes/mayúsculas/puntuación) entre productos `estado=aprobado`, prefijo primero. Menos de 2 caracteres devuelve vacío.
- **`POST /catalogo-maestro/sugerir`** — cuando un negocio guarda un producto que no encontró, se manda en silencio (con `Authorization: Bearer` de cuenta si hay sesión) y cae en `estado=pendiente`. No duplica: si ya existe un nombre normalizado igual, no inserta de nuevo.
- **Panel Punto → Catálogo maestro** — pestañas Aprobados/Por revisar, formulario para agregar producto (entra directo aprobado, con subida de foto a la Biblioteca de Medios o URL pegada), aprobar/editar una sugerencia pendiente, eliminar, e importador CSV para la carga masiva inicial (columnas: nombre, marca, sku, categoría, tipo de unidad, valor de unidad — solo nombre obligatorio, se saltan duplicados).

El cliente (POS) todavía no busca contra esto — eso es la próxima entrega del tema.

**v1.6** agrega la contabilidad mensual: tabla `wp_punto_contab_mensual` (un registro por cuenta por **mes** calendario, no por día — `UNIQUE(wp_user_id, mes)`). Resuelve un hueco real del respaldo de v1.5: ese respaldo solo cubre los últimos 30 días, así que un mes ya cerrado (ej. julio, visto desde agosto) se cae de esa ventana antes de que nadie piense en respaldarlo — Contabilidad se declara mensual, necesita su propia foto permanente por mes.

- **`POST /contabilidad-mensual`** — `{mes:"YYYY-MM", resumen:{...}, detalle:{...}}`. `resumen` son los números que ya calculó `contabData()` del cliente (ganancia, IVA débito/crédito, etc.); `detalle` son las ventas y egresos exactos de ese mes, para que el cliente pueda rearmar sus exportadores de CSV ya existentes tras restaurar. El servidor no calcula nada, solo guarda. Poda al cupo: 1 mes si la cuenta no tiene negocio activo, 12 si sí.
- **`GET /contabilidad-mensual`** — lista `{mes, resumen}` de todos los meses guardados, sin `detalle` — seguro de mostrar sin pagar.
- **`GET /contabilidad-mensual/{mes}/completo`** — el `detalle` real. Exige negocio activo.

El cliente todavía no restaura este detalle de vuelta a la app (mezclarlo con lo que ya hay localmente es más delicado — queda para una entrega aparte); por ahora se guarda automáticamente y se puede consultar qué meses están respaldados.

**v1.5** agrega el respaldo: tabla `wp_punto_respaldos` (un respaldo por cuenta por día — `UNIQUE(wp_user_id, fecha)`, así que repetir el guardado el mismo día sobrescribe en vez de acumular) y tres endpoints, todos con `Authorization: Bearer <token de cuenta>`:

- **`POST /respaldo`** — `{ payload: {...}, resumen: {...} }`. `payload` es el estado completo que arma el POS (sin fotos, eso es v1.6+); `resumen` son conteos/totales livianos que el POS también calculó. El servidor no valida ni interpreta ninguno de los dos, solo guarda. Poda automática al cupo: 2 respaldos si la cuenta no tiene negocio activo, 30 si sí (`punto_cuenta_tiene_negocio_activo()` del v1.4).
- **`GET /respaldo`** — lista `{id, fecha, resumen}` de todos los respaldos de la cuenta, **sin el payload** — seguro de mostrar sin haber pagado, es la data que arma la pantalla "esto vas a perder" del cliente.
- **`GET /respaldo/{id}/completo`** — el payload real. Exige negocio activo además del token de cuenta; si no, 403 con `requiere_activacion:true`.

Nada de esto lo consume todavía el cliente — eso es lo que sigue (disparar el respaldo al cerrar turno, y la pantalla de recuperar/empezar de cero).

**v1.4** vincula la cuenta con el negocio en el momento de activar un código: si `/activar` llega con `Authorization: Bearer <token de cuenta>`, la fila nueva de `wp_punto_negocios` queda con `wp_user_id` seteado (columna nueva, agregada sola por `dbDelta` al activar — no borra ni toca negocios ya existentes, que se quedan con `wp_user_id` en NULL). Activar **sin** cuenta sigue funcionando exactamente igual que siempre — el código sigue siendo la única credencial que de verdad importa.

Nuevo helper `punto_cuenta_tiene_negocio_activo($wp_user_id)` — todavía no lo usa ningún endpoint (lo va a usar `/respaldo`, pendiente), decide si una cuenta tiene derecho a la cuota paga de respaldos.

Panel **Punto → Negocios** gana una columna "Cuenta" mostrando el correo vinculado, o "sin cuenta" si se activó sin login (incluye todos los negocios de antes de v1.4).

**v1.3** le da a la cuenta su propio token (`punto_emitir_token_cuenta()`, guardado como `user_meta` `punto_token_hash` — solo el hash, igual que el token de negocio). `/registro` y `/login` ahora devuelven `token` en la respuesta. No rota: cada inicio de sesión agrega un token nuevo sin invalidar los que ya tengan otros dispositivos con la misma cuenta (WordPress permite varias filas de `user_meta` con la misma clave por usuario — así se guardan varios a la vez, con tope de 5, podando el más viejo). Todavía **nada verifica este token** — ningún endpoint lo pide aún. Es la base para el respaldo (`/respaldo`, pendiente): sin esto, el servidor no tiene forma de saber de qué cuenta es un respaldo antes de que exista un negocio pagado.

**v1.1** agrega cuenta real del dueño del negocio: **registro, inicio de sesión y recuperar contraseña** (`/registro`, `/login`, `/recuperar`), sobre `wp_users`/`wp_usermeta` nativo de WordPress — sin tablas propias nuevas. Antes, la pantalla de Identidad de la app (Bienvenida/Iniciar sesión/Crear cuenta) era puro mockup: dejaba entrar con cualquier cosa. Ahora valida de verdad.

**A propósito separado de `/activar`.** El código de activación sigue siendo el candado de pago que desbloquea el respaldo de ventas en la nube — eso no cambió. La cuenta nueva es solo la puerta de entrada a la app (gratis, sin código) y no toca la tabla `wp_punto_negocios` ni el token de sync. Un mismo dueño puede tener cuenta y, aparte, activar un código — las dos cosas conviven sin pisarse.

**v1.2** cierra el hueco que dejó v1.1: probando en real, recuperar contraseña terminaba mandando a la persona a iniciar sesión en wp-login.php, y de ahí entraba al panel de wp-admin (vacío y sin permisos, pero visible). Ahora: después de cambiar la contraseña, una cuenta de Punto vuelve directo a `/pos/`; y si de todas formas llega a `/wp-admin/`, se le rebota a la app. El admin real del sitio (con `manage_options`) no se toca. Ver la sección 3c del código (`punto-backend.php`).

El servidor de Punto. Guarda los negocios, los códigos de activación y el respaldo de las ventas que mandan los teléfonos.

---

## Instalar

1. Plugins → Añadir nuevo → Subir plugin → `punto-backend-v1.zip` → Instalar.
2. **Activar.** Al activarlo se crean solas las 3 tablas (`wp_punto_negocios`, `wp_punto_ventas`, `wp_punto_codigos`). No hay que tocar la base de datos a mano.
3. Aparece un menú nuevo **Punto** en la barra lateral de wp-admin, con 3 pantallas: Negocios, Códigos y Ventas.

No hace falta configurar nada más. El plugin y el tema hijo son independientes: el plugin funciona aunque cambies de tema, y el tema funciona aunque el plugin esté apagado (el POS simplemente no puede activarse, que es exactamente como estaba antes).

---

## Cómo se usa, en criollo

**Cuando un negocio te paga:**
1. Punto → Códigos → escribe cuántos códigos quieres y una nota para acordarte de quién es → Generar.
2. Copia el código que sale (formato `PUNTO-AB12-CD34`) y mándaselo por WhatsApp.
3. El negocio lo escribe en su app: Configuración → Negocio → Activación → Activar.
4. Listo. En Punto → Negocios aparece su fila, y sus ventas empiezan a llegar solas a Punto → Ventas.

**Los códigos son de un solo uso.** Al usarse quedan marcados y no sirven para otro teléfono.

**Si un negocio pierde el teléfono o te deja de pagar:** Punto → Negocios → Desactivar. Su token deja de funcionar al instante; sus ventas ya sincronizadas se quedan guardadas.

---

## Verificar que quedó bien instalado

Entra a esta URL en el navegador:

```
https://tudominio.com/wp-json/punto/v1/activar
```

Debe responder algo tipo `{"code":"rest_no_route",...}` **con método GET** — eso está bien, significa que la ruta existe pero solo acepta POST. Si te da un 404 de WordPress ("página no encontrada"), el plugin no está activo o los permalinks están en "Simple": Ajustes → Enlaces permanentes → elige cualquier opción que no sea "Simple" → Guardar.

---

## ⚠️ El problema del header Authorization (léelo si el sync falla)

El endpoint `/sync` se autentica con `Authorization: Bearer <token>`. En **muchos hostings compartidos con Apache** (Hostinger entre ellos, según la configuración) ese header **no llega a PHP** — Apache lo filtra antes. El síntoma es específico: la activación funciona perfecto, pero sincronizar da siempre error de autorización con un token que sabes que es bueno.

Si pasa, agrega esto al `.htaccess` de la raíz de WordPress, **antes** del bloque `# BEGIN WordPress`:

```apache
# Deja pasar el header Authorization a PHP (lo necesita /wp-json/punto/v1/sync)
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule .* - [E=HTTP_AUTHORIZATION:%1]
</IfModule>
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
```

El plugin ya intenta recuperar el header por las 3 vías conocidas (`authorization`, `HTTP_AUTHORIZATION`, `REDIRECT_HTTP_AUTHORIZATION`), así que con suerte no hace falta. Pero si falla, es casi seguro esto y no el código.

---

## El contrato — por qué no se puede cambiar libremente

El cliente (el POS dentro de `page-pos.php`, en el tema hijo) **ya está escrito y desplegado en teléfonos**. Este plugin se adapta a él, no al revés.

**`POST /wp-json/punto/v1/activar`** — público, el código de activación es la credencial.
```json
// entra
{ "codigo": "PUNTO-AB12-CD34", "nombre_negocio": "Bodega Los Pinos" }
// sale (200)
{ "token": "<cadena larga>", "negocio_id": 7 }
// sale (error, cualquier status != 200 — el cliente solo lee "message")
{ "message": "Código inválido o ya usado." }
```

**`POST /wp-json/punto/v1/sync`** — header `Authorization: Bearer <token>`.
```json
// entra
{ "ventas": [ { "uuid": "...", "payload": { ...la orden completa... }, "fecha": "2026-08-09T...", "monto_bs": 856 } ] }
// sale (200)
{ "sincronizadas_uuids": ["..."] }
```

El cliente marca como sincronizada **solo** la venta cuyo `uuid` vuelva en `sincronizadas_uuids`. Si el servidor guardó 3 de 5, las otras 2 se reintentan solas después. Por eso `/sync` **nunca devuelve 500 por una venta mala**: guarda las que puede y omite las que no.

---

## El contrato de cuenta (v1.1)

**`POST /wp-json/punto/v1/registro`** — pública, crea la cuenta.
```json
// entra
{ "email":"...", "password":"mínimo 8", "nombre":"...", "tipo":"negocio|emprendedor",
  "id_number":"...", "phone":"...", "address":"..." }
// sale (201)
{ "ok": true }
// error (400/409/500)
{ "message": "..." }
```

**`POST /wp-json/punto/v1/login`** — pública, valida contra `wp_authenticate()`.
```json
// entra
{ "email":"...", "password":"..." }
// sale (200)
{ "ok": true, "negocio": { "nombre":"...", "tipo":"...", "id_number":"...", "phone":"...", "address":"..." } }
// error (401)
{ "message": "Correo o contraseña incorrectos." }
```
El cliente usa `negocio` para rellenar el nombre/RIF/teléfono/dirección del negocio al entrar desde un teléfono nuevo — no restaura catálogo ni ventas (eso sigue pendiente, ver más abajo), pero al menos no hay que volver a escribir la identidad del negocio.

**`POST /wp-json/punto/v1/recuperar`** — pública.
```json
// entra
{ "email":"..." }
// sale (200, siempre, exista o no la cuenta)
{ "ok": true, "message": "Si ese correo tiene una cuenta, te mandamos instrucciones para recuperar tu contraseña." }
```
Dispara el correo nativo de WordPress (`retrieve_password()`) con un enlace a `wp-login.php?action=rp`. El dueño cambia la contraseña ahí y vuelve a la app a iniciar sesión. **Requiere que el sitio pueda mandar correo** — en Hostinger y hostings compartidos en general esto suele fallar en silencio sin un plugin SMTP (ej. WP Mail SMTP) configurado con las credenciales del correo del dominio; si el correo de recuperación no llega, es casi siempre eso y no el código.

---

## Decisiones de diseño que conviene no deshacer sin pensarlo

**El token no se guarda en claro.** La tabla guarda `sha256(token)`. El token completo existe una sola vez: en la respuesta de `/activar`, y de ahí vive en el teléfono. Si alguien se lleva un volcado de la base, no se lleva las llaves de sincronización de todos los negocios. La consecuencia práctica: **no puedes ver el token de un negocio desde wp-admin**. Si lo pierde, se desactiva y se le da un código nuevo.

**La idempotencia vive en la clave única `(negocio_id, uuid)`**, con `INSERT ... ON DUPLICATE KEY UPDATE`. El cliente reintenta cada vez que vuelve la señal, así que reenviar la misma venta no es un caso raro: pasa todos los días. Sin esa clave, cada bache de internet duplicaría ventas.

**Tablas propias, no Custom Post Types.** Una bodega genera miles de ventas; meterlas en `wp_posts` + `wp_postmeta` (que es lo que hace un CPT) pone lenta la base y convierte cualquier consulta útil en un laberinto de joins. El **catálogo compartido** que viene después sí va a ser un CPT — ahí sí conviene, porque son pocos miles de registros que se editan a mano y quieren Biblioteca de Medios.

**El alfabeto de los códigos no tiene I, O, 0 ni 1.** Estos códigos se dictan por WhatsApp y por teléfono; confundir un cero con una O es soporte perdido.

**Carrera al activar:** el `UPDATE ... WHERE usado = 0` es lo que evita que dos teléfonos activen con el mismo código en el mismo instante. El que pierde la carrera no actualiza ninguna fila, y su negocio recién creado se borra. Sin transacciones explícitas.

---

## Lo que este plugin todavía NO hace

- **No revalida los números.** Guarda el `payload` tal cual llega del teléfono. Un cliente modificado podría mandar precios inventados. No es bloqueante para las primeras pruebas con negocios reales, pero sí importante antes de vender esto en serio. Lo correcto es recalcular precios/IVA contra los datos del servidor al recibir el `/sync`.
- **No sirve el catálogo compartido.** Falta el CPT + Biblioteca de Medios + el endpoint `GET /catalogo`.
- **No devuelve datos al teléfono.** El sync hoy es de una sola vía (teléfono → servidor). Si el negocio pierde el equipo, sus ventas están a salvo en el servidor, pero recuperarlas en un teléfono nuevo todavía es manual.
- **La cuenta (v1.1) no restaura datos.** Login en un teléfono nuevo trae el nombre/RIF/teléfono/dirección del negocio, pero no el catálogo, stock, clientes ni egresos — eso vive solo en el IndexedDB del teléfono viejo hasta que exista el endpoint `/estado`.
- **Sin límite de intentos de login.** `wp_authenticate()` no tiene rate-limit propio en este plugin; para el volumen actual no importa, pero antes de escalar conviene agregar uno (mismo pendiente que ya tenía `/sync`).
- **No hay límite de peticiones.** Un negocio con un cliente roto podría martillar `/sync`. Para el volumen actual no importa; con decenas de negocios, sí.
