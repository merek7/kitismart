// ===================================
// SERVICE WORKER - KitiSmart PWA
// ===================================

const CACHE_VERSION = 'kitismart-v1.3.0';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const DYNAMIC_CACHE = `${CACHE_VERSION}-dynamic`;

// Fichiers à mettre en cache immédiatement (seulement les assets statiques, pas les pages authentifiées)
const STATIC_ASSETS = [
  '/assets/css/dashboard/index.css',
  '/assets/js/dashboard/charts.js',
  '/assets/img/logo.svg',
  '/manifest.json'
];

// Pages qui ne doivent PAS être mises en cache (pages authentifiées)
const NO_CACHE_PATTERNS = [
  '/login',
  '/register',
  '/logout'
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
// Fonctions utilitaires
// ===================================

/**
 * Vérifie si une réponse indique une session expirée
 */
function isSessionExpired(response, url) {
  // Vérifier le code HTTP
  if (response.status === 401 || response.status === 403) {
    return true;
  }

  // Vérifier si c'est une redirection vers /login
  if (response.redirected && response.url.includes('/login')) {
    return true;
  }

  // Vérifier le header X-Session-Expired personnalisé
  if (response.headers.get('X-Session-Expired') === 'true') {
    return true;
  }

  return false;
}

/**
 * Notifie tous les clients que la session a expiré
 */
async function notifySessionExpired() {
  const clients = await self.clients.matchAll();
  clients.forEach(client => {
    client.postMessage({
      type: 'SESSION_EXPIRED',
      message: 'Votre session a expiré. Veuillez vous reconnecter.'
    });
  });
}

/**
 * Nettoie le cache des pages authentifiées
 */
async function clearAuthenticatedPagesCache() {
  const cache = await caches.open(DYNAMIC_CACHE);
  const keys = await cache.keys();

  for (const request of keys) {
    const url = new URL(request.url);
    // Supprimer les pages qui nécessitent une authentification
    if (url.pathname.startsWith('/dashboard') ||
        url.pathname.startsWith('/expenses') ||
        url.pathname.startsWith('/budget') ||
        url.pathname.startsWith('/categories') ||
        url.pathname.startsWith('/settings') ||
        url.pathname.startsWith('/recurrences')) {
      await cache.delete(request);
      console.log('[SW] Cache supprimé pour:', url.pathname);
    }
  }
}

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

  // Ne pas mettre en cache les pages d'authentification
  if (NO_CACHE_PATTERNS.some(pattern => url.pathname.startsWith(pattern))) {
    return;
  }

  // ===================================
  // Gestion des requêtes non-GET (POST, PUT, DELETE) pour mode hors ligne
  // ===================================
  if (request.method !== 'GET' && request.method !== 'HEAD') {
    // Ignorer les requêtes HEAD (vérifications de connectivité)
    // Ignorer aussi les requêtes de synchronisation (marquées avec X-Sync-Request)
    if (request.headers.get('X-Sync-Request') === 'true') {
      console.log('[SW] Requête de synchronisation détectée - pas d\'interception');
      return; // Laisser passer la requête normalement
    }

    // Cloner la requête AVANT de l'utiliser dans fetch (pour pouvoir lire le body après)
    const requestClone = request.clone();

    event.respondWith(
      fetch(request)
        .then(async (response) => {
          // Vérifier si la session a expiré (pour les requêtes POST/PUT/DELETE)
          if (isSessionExpired(response, url)) {
            console.log('[SW] Session expirée détectée sur requête POST');
            await clearAuthenticatedPagesCache();
            await notifySessionExpired();
          }
          return response;
        })
        .catch(async () => {
          // Si la requête échoue (hors ligne), stocker dans IndexedDB via le client
          const requestData = {
            url: requestClone.url,
            method: requestClone.method,
            headers: Object.fromEntries(requestClone.headers.entries()),
            body: await requestClone.text(),
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

          console.log('[SW] Requête sauvegardée pour synchronisation ultérieure');

          return new Response(
            JSON.stringify({
              success: false,
              offline: true,
              message: 'Données enregistrées hors ligne. Elles seront synchronisées automatiquement.'
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

  // Ignorer les requêtes API (laisser passer en ligne) - mais surveiller les erreurs d'auth
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(
      fetch(request).then(async (response) => {
        if (isSessionExpired(response, url)) {
          console.log('[SW] Session expirée détectée sur API');
          await clearAuthenticatedPagesCache();
          await notifySessionExpired();
        }
        return response;
      })
    );
    return;
  }

  // Stratégie: Network First, puis Cache (pour les pages dynamiques)
  if (url.pathname.startsWith('/dashboard') || url.pathname.startsWith('/expenses') || url.pathname.startsWith('/budget') || url.pathname.startsWith('/categories') || url.pathname.startsWith('/settings') || url.pathname.startsWith('/recurrences')) {
    event.respondWith(
      fetch(request)
        .then(async (response) => {
          // Vérifier si la session a expiré
          if (isSessionExpired(response, url)) {
            console.log('[SW] Session expirée détectée - redirection vers login');
            await clearAuthenticatedPagesCache();
            await notifySessionExpired();
            // Retourner la réponse originale (qui est probablement une redirection vers /login)
            return response;
          }

          // Ne mettre en cache que si la réponse est valide (pas de redirection vers login)
          if (response.ok && !response.redirected) {
            const responseClone = response.clone();
            caches.open(DYNAMIC_CACHE).then((cache) => {
              cache.put(request, responseClone);
            });
          }
          return response;
        })
        .catch(() => {
          // Si offline, retourner depuis le cache
          return caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
              return cachedResponse;
            }

            // Si pas en cache non plus, retourner la page offline
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
                  <h2>Page non disponible hors ligne</h2>
                  <p>Cette page n'a pas encore été visitée. Visitez-la d'abord en ligne pour l'utiliser hors connexion.</p>
                  <button onclick="window.location.href='/dashboard'">Retour au Dashboard</button>
                </div>
              </body>
              </html>`,
              {
                status: 200,
                headers: { 'Content-Type': 'text/html' }
              }
            );
          });
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

  if (event.data && event.data.type === 'CLEAR_AUTH_CACHE') {
    event.waitUntil(clearAuthenticatedPagesCache());
  }

  if (event.data && event.data.type === 'SYNC_NOW') {
    event.waitUntil(syncOfflineData());
  }

  if (event.data && event.data.type === 'LOGOUT') {
    // Nettoyer le cache lors de la déconnexion
    event.waitUntil(
      Promise.all([
        clearAuthenticatedPagesCache(),
        // Aussi nettoyer le cache statique pour forcer le rechargement
        caches.delete(STATIC_CACHE),
        caches.delete(DYNAMIC_CACHE)
      ]).then(() => {
        console.log('[SW] Cache nettoyé après déconnexion');
      })
    );
  }
});

// ===================================
// Synchronisation en arrière-plan
// ===================================
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-offline-data') {
    event.waitUntil(syncOfflineData());
  }
});

async function syncOfflineData() {
  console.log('[SW] Déclenchement de la synchronisation des données hors ligne...');

  const clients = await self.clients.matchAll();
  clients.forEach(client => {
    client.postMessage({
      type: 'SYNC_OFFLINE_DATA'
    });
  });
}

// ===================================
// Notification de changement de contrôleur
// ===================================
self.addEventListener('controllerchange', () => {
  console.log('[SW] Nouveau Service Worker activé');
});

console.log('[SW] Service Worker chargé - Version:', CACHE_VERSION);
