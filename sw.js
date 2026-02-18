// Service Worker for Attendance System PWA
const CACHE_NAME = 'attendance-system-v1.0.0';
const STATIC_CACHE = 'attendance-static-v1.0.0';
const DYNAMIC_CACHE = 'attendance-dynamic-v1.0.0';

// Files to cache immediately
const STATIC_ASSETS = [
  './landing.php',
  './login.php',
  './assets/css/style.css',
  './assets/js/script.js',
  './manifest.json',
  './assets/images/icon-192.png',
  './assets/images/icon-512.png'
];

// Install event - cache static assets individually so one failure doesn't abort all
self.addEventListener('install', event => {
  console.log('Service Worker: Installing...');
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => {
        console.log('Service Worker: Caching static assets');
        // Use individual adds so a single missing file doesn't break the whole install
        return Promise.allSettled(
          STATIC_ASSETS.map(url =>
            cache.add(url).catch(err => console.warn('SW: could not cache', url, err))
          )
        );
      })
      .then(() => self.skipWaiting())
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('Service Worker: Activating...');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
            console.log('Service Worker: Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch event - serve cached content when offline
self.addEventListener('fetch', event => {
  // Only handle GET requests
  if (event.request.method !== 'GET') return;

  // Skip cross-origin requests
  if (!event.request.url.startsWith(self.location.origin)) return;

  event.respondWith(
    caches.match(event.request)
      .then(cachedResponse => {
        // Return cached version if available
        if (cachedResponse) {
          return cachedResponse;
        }

        // Otherwise, fetch from network
        return fetch(event.request)
          .then(response => {
            // Don't cache non-successful responses
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }

            // Clone the response for caching
            const responseToCache = response.clone();

            // Cache successful responses
            caches.open(DYNAMIC_CACHE)
              .then(cache => {
                cache.put(event.request, responseToCache);
              });

            return response;
          })
          .catch(error => {
            console.log('Service Worker: Fetch failed, returning offline page');
            // Return a basic offline page for navigation requests
            if (event.request.destination === 'document') {
              return caches.match('./index.php');
            }
          });
      })
  );
});

// Background sync for offline actions (if implemented later)
self.addEventListener('sync', event => {
  console.log('Service Worker: Background sync triggered');
  if (event.tag === 'background-sync') {
    event.waitUntil(doBackgroundSync());
  }
});

// Function to handle background sync (placeholder for future implementation)
function doBackgroundSync() {
  // This could be used to sync offline attendance data when connection is restored
  console.log('Service Worker: Performing background sync');
  return Promise.resolve();
}

// Push notifications (if implemented later)
self.addEventListener('push', event => {
  console.log('Service Worker: Push received');
  if (event.data) {
    const data = event.data.json();
    const options = {
    body: data.body,
    icon: './assets/images/icon-192.png',
    badge: './assets/images/icon-192.png',
      vibrate: [100, 50, 100],
      data: {
        dateOfArrival: Date.now(),
        primaryKey: data.primaryKey
      }
    };
    event.waitUntil(
      self.registration.showNotification(data.title, options)
    );
  }
});

// Notification click handler
self.addEventListener('notificationclick', event => {
  console.log('Service Worker: Notification clicked');
  event.notification.close();

  event.waitUntil(
    clients.openWindow('./index.php')
  );
});
