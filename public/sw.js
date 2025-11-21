// ===================================
// SERVICE WORKER - KitiSmart PWA
// ===================================

const CACHE_VERSION = 'kitismart-v1.0.1';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const DYNAMIC_CACHE = `${CACHE_VERSION}-dynamic`;

// Fichiers à mettre en cache immédiatement
const STATIC_ASSETS = [
  '/dashboard',
  '/assets/css/dashboard/index.css',
  '/assets/js/dashboard/charts.js',
  '/assets/img/logo.svg',
  '/manifest.json'
];

// ===================================
// Installation du Service Worker
// ===================================
self.addEventListener('install', (event) => {
  console.log('[SW] Installation en cours...');

  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => {
        console.log('[SW] Mise en cache des assets statiques');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => {
        console.log('[SW] Installation terminée');
        return self.skipWaiting(); // Active immédiatement
      })
      .catch((error) => {
        console.error('[SW] Erreur lors de l\'installation:', error);
      })
  );
});

// ===================================
// Activation du Service Worker
// ===================================
self.addEventListener('activate', (event) => {
  console.log('[SW] Activation en cours...');

  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        // Supprimer les anciens caches
        return Promise.all(
          cacheNames
            .filter((name) => name.startsWith('kitismart-') && name !== STATIC_CACHE && name !== DYNAMIC_CACHE)
            .map((name) => {
              console.log('[SW] Suppression ancien cache:', name);
              return caches.delete(name);
            })
        );
      })
      .then(() => {
        console.log('[SW] Activation terminée');
        return self.clients.claim(); // Prend le contrôle immédiatement
      })
  );
});

// ===================================
// Interception des requêtes (Fetch)
// ===================================
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorer les requêtes non-HTTP/HTTPS (chrome-extension, about, data, etc.)
  if (!url.protocol.startsWith('http')) {
    return;
  }

  // Ignorer les requêtes non-GET
  if (request.method !== 'GET') {
    return;
  }

  // Ignorer les requêtes API (laisser passer en ligne)
  if (url.pathname.startsWith('/api/')) {
    return;
  }

  // Stratégie: Network First, puis Cache (pour les pages dynamiques)
  if (url.pathname.startsWith('/dashboard') || url.pathname.startsWith('/expenses') || url.pathname.startsWith('/budget')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // Cloner la réponse pour la mettre en cache
          const responseClone = response.clone();
          caches.open(DYNAMIC_CACHE).then((cache) => {
            cache.put(request, responseClone);
          });
          return response;
        })
        .catch(() => {
          // Si offline, retourner depuis le cache
          return caches.match(request);
        })
    );
    return;
  }

  // Stratégie: Cache First, puis Network (pour les assets statiques)
  event.respondWith(
    caches.match(request)
      .then((cachedResponse) => {
        if (cachedResponse) {
          return cachedResponse;
        }

        // Si pas en cache, fetch depuis le réseau
        return fetch(request)
          .then((response) => {
            // Ne mettre en cache que les réponses OK
            if (!response || response.status !== 200 || response.type === 'error') {
              return response;
            }

            const responseClone = response.clone();
            caches.open(DYNAMIC_CACHE).then((cache) => {
              cache.put(request, responseClone);
            });

            return response;
          })
          .catch((error) => {
            console.error('[SW] Erreur fetch:', error);

            // Page offline de fallback
            if (request.destination === 'document') {
              return new Response(
                `<!DOCTYPE html>
                <html lang="fr">
                <head>
                  <meta charset="UTF-8">
                  <meta name="viewport" content="width=device-width, initial-scale=1.0">
                  <title>KitiSmart - Hors ligne</title>
                  <style>
                    body {
                      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                      display: flex;
                      align-items: center;
                      justify-content: center;
                      min-height: 100vh;
                      margin: 0;
                      background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
                      color: white;
                      text-align: center;
                      padding: 2rem;
                    }
                    .offline-container {
                      max-width: 400px;
                    }
                    h1 {
                      font-size: 4rem;
                      margin: 0 0 1rem;
                    }
                    p {
                      font-size: 1.2rem;
                      margin: 0 0 2rem;
                    }
                    button {
                      background: white;
                      color: #0d9488;
                      border: none;
                      padding: 1rem 2rem;
                      font-size: 1rem;
                      border-radius: 8px;
                      cursor: pointer;
                      font-weight: 600;
                    }
                    button:hover {
                      transform: scale(1.05);
                    }
                  </style>
                </head>
                <body>
                  <div class="offline-container">
                    <h1>📡</h1>
                    <h2>Vous êtes hors ligne</h2>
                    <p>KitiSmart nécessite une connexion Internet pour cette page.</p>
                    <button onclick="window.location.reload()">Réessayer</button>
                  </div>
                </body>
                </html>`,
                {
                  headers: { 'Content-Type': 'text/html' }
                }
              );
            }
          });
      })
  );
});

// ===================================
// Messages du Service Worker
// ===================================
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }

  if (event.data && event.data.type === 'CLEAR_CACHE') {
    caches.keys().then((cacheNames) => {
      return Promise.all(cacheNames.map((name) => caches.delete(name)));
    }).then(() => {
      console.log('[SW] Tous les caches supprimés');
    });
  }
});

console.log('[SW] Service Worker chargé - Version:', CACHE_VERSION);
