const CACHE_NAME = 'kitismart-v1';
const OFFLINE_CACHE = 'kitismart-offline-v1';

// Ressources à mettre en cache lors de l'installation
const STATIC_CACHE_URLS = [
  '/dashboard',
  '/expenses/create',
  '/budget/create',
  '/expenses/recurrences',
  '/assets/css/dashboard/index.css',
  '/assets/js/app.js',
  'https://code.jquery.com/jquery-3.6.0.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
  'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
  'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js'
];

// Installation du Service Worker
self.addEventListener('install', (event) => {
  console.log('[SW] Installation...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[SW] Mise en cache des ressources statiques');
        return cache.addAll(STATIC_CACHE_URLS.map(url => new Request(url, {cache: 'reload'})));
      })
      .catch((error) => {
        console.error('[SW] Erreur lors de la mise en cache:', error);
      })
  );
  self.skipWaiting();
});

// Activation du Service Worker
self.addEventListener('activate', (event) => {
  console.log('[SW] Activation...');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME && cacheName !== OFFLINE_CACHE) {
            console.log('[SW] Suppression de l\'ancien cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// Stratégie de cache pour les requêtes
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorer les requêtes non-HTTP
  if (!request.url.startsWith('http')) {
    return;
  }

  // Stratégie pour les requêtes API (POST, PUT, DELETE)
  if (request.method !== 'GET') {
    event.respondWith(
      fetch(request)
        .catch(async () => {
          // Si la requête échoue (hors ligne), stocker dans IndexedDB
          const requestData = {
            url: request.url,
            method: request.method,
            headers: Object.fromEntries(request.headers.entries()),
            body: await request.clone().text(),
            timestamp: Date.now()
          };

          // Envoyer au client pour stockage dans IndexedDB
          const clients = await self.clients.matchAll();
          clients.forEach(client => {
            client.postMessage({
              type: 'SAVE_OFFLINE_REQUEST',
              data: requestData
            });
          });

          return new Response(
            JSON.stringify({
              success: false,
              offline: true,
              message: 'Requête enregistrée. Elle sera synchronisée quand vous serez en ligne.'
            }),
            {
              status: 200,
              headers: { 'Content-Type': 'application/json' }
            }
          );
        })
    );
    return;
  }

  // Stratégie Cache First pour les ressources statiques
  if (url.pathname.match(/\.(css|js|jpg|jpeg|png|gif|svg|woff|woff2|ttf|eot)$/)) {
    event.respondWith(
      caches.match(request)
        .then((cachedResponse) => {
          if (cachedResponse) {
            return cachedResponse;
          }
          return fetch(request).then((response) => {
            return caches.open(CACHE_NAME).then((cache) => {
              cache.put(request, response.clone());
              return response;
            });
          });
        })
        .catch(() => {
          return new Response('Ressource non disponible hors ligne', {
            status: 503,
            statusText: 'Service Unavailable'
          });
        })
    );
    return;
  }

  // Stratégie Network First pour les pages HTML et les API
  event.respondWith(
    fetch(request)
      .then((response) => {
        // Mettre en cache les pages réussies
        if (response.status === 200) {
          const responseClone = response.clone();
          caches.open(OFFLINE_CACHE).then((cache) => {
            cache.put(request, responseClone);
          });
        }
        return response;
      })
      .catch(() => {
        // Si le réseau échoue, essayer le cache
        return caches.match(request).then((cachedResponse) => {
          if (cachedResponse) {
            return cachedResponse;
          }

          // Page hors ligne par défaut
          return new Response(
            `<!DOCTYPE html>
            <html lang="fr">
            <head>
              <meta charset="UTF-8">
              <meta name="viewport" content="width=device-width, initial-scale=1.0">
              <title>Hors ligne - KitiSmart</title>
              <style>
                body {
                  font-family: Arial, sans-serif;
                  display: flex;
                  justify-content: center;
                  align-items: center;
                  height: 100vh;
                  margin: 0;
                  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                  color: white;
                }
                .offline-container {
                  text-align: center;
                  padding: 2rem;
                }
                .offline-icon {
                  font-size: 4rem;
                  margin-bottom: 1rem;
                }
                h1 { margin: 0 0 1rem 0; }
                p { margin: 0.5rem 0; opacity: 0.9; }
                .retry-btn {
                  margin-top: 2rem;
                  padding: 0.75rem 2rem;
                  background: white;
                  color: #667eea;
                  border: none;
                  border-radius: 5px;
                  font-size: 1rem;
                  cursor: pointer;
                  font-weight: bold;
                }
                .retry-btn:hover {
                  transform: scale(1.05);
                }
              </style>
            </head>
            <body>
              <div class="offline-container">
                <div class="offline-icon">📡</div>
                <h1>Vous êtes hors ligne</h1>
                <p>Impossible de charger cette page pour le moment.</p>
                <p>Les pages déjà visitées sont disponibles en mode hors ligne.</p>
                <button class="retry-btn" onclick="window.location.reload()">Réessayer</button>
              </div>
            </body>
            </html>`,
            {
              status: 503,
              statusText: 'Service Unavailable',
              headers: { 'Content-Type': 'text/html' }
            }
          );
        });
      })
  );
});

// Écouter les messages du client pour la synchronisation
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }

  if (event.data && event.data.type === 'SYNC_NOW') {
    event.waitUntil(syncOfflineData());
  }
});

// Synchronisation en arrière-plan
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-offline-data') {
    event.waitUntil(syncOfflineData());
  }
});

async function syncOfflineData() {
  console.log('[SW] Synchronisation des données hors ligne...');

  const clients = await self.clients.matchAll();
  clients.forEach(client => {
    client.postMessage({
      type: 'SYNC_OFFLINE_DATA'
    });
  });
}

// Notification de nouvelle version disponible
self.addEventListener('controllerchange', () => {
  console.log('[SW] Nouvelle version du Service Worker activée');
});
