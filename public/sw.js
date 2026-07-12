// Service Worker — ControlPresta
// Cachea el shell estático (CSS, JS de Bootstrap, favicon).
// Los datos de la app siempre van a red (requieren autenticación).

const CACHE = 'gestion-v1';

const SHELL = [
  '/css/controlpresta.css',
  '/favicon.ico',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE).then((c) => c.addAll(SHELL)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  const url = new URL(e.request.url);

  // Solo interceptar GET del mismo origen
  if (e.request.method !== 'GET' || url.origin !== location.origin) return;

  // Rutas de la app (HTML): siempre red; si falla, sin caché (datos en tiempo real)
  if (e.request.headers.get('accept')?.includes('text/html')) return;

  // Assets estáticos: caché primero, luego red y actualizar caché
  e.respondWith(
    caches.match(e.request).then((cached) => {
      const network = fetch(e.request).then((res) => {
        if (res.ok) {
          caches.open(CACHE).then((c) => c.put(e.request, res.clone()));
        }
        return res;
      });
      return cached || network;
    })
  );
});
