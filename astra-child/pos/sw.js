// Punto POS — service worker mínimo para que la app sea instalable y funcione offline.
// Estrategia: red primero, cae al cache si no hay conexión — así el POS siempre usa la
// versión más nueva cuando hay internet, y sigue abriendo si se va la señal a mitad de
// una venta. No usa una lista de precache: va cacheando lo que el navegador pida en vivo
// (la página, las fuentes de Google Fonts, el SDK de Ably vendorizado en /pos/ably.min.js,
// etc.) — todo lo que este POS necesita para seguir siendo instalable/offline sin depender
// de que un CDN de terceros esté arriba.
//
// Subir PUNTO_SW_VERSION cada vez que cambie este archivo o el HTML de la app fuerza a
// los teléfonos con la app ya instalada a limpiar el cache viejo la próxima vez que abran
// con internet (ver 'activate' abajo) — si no, alguien podría quedarse pegado en una
// versión vieja del POS aunque tenga señal.
const PUNTO_SW_VERSION = 'v5_38';
const CACHE_NAME = 'punto-pos-' + PUNTO_SW_VERSION;

self.addEventListener('install', () => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return; // no cachear POST/etc.
  event.respondWith(
    caches.open(CACHE_NAME).then((cache) =>
      fetch(event.request)
        .then((res) => {
          if (res && res.status === 200) cache.put(event.request, res.clone());
          return res;
        })
        .catch(() => cache.match(event.request))
    )
  );
});
