const CACHE_NAME = 'sensei-omoi-v1';
const PRECACHE_URLS = [
	'./',
	'./css/style.css',
	'./img/logo.png',
	'./img/teacher.png',
	'./img/jk.png',
	'./js/home.js',
	'./js/toc.js'
];

self.addEventListener('install', event => {
	event.waitUntil(
		caches.open(CACHE_NAME)
			.then(cache => cache.addAll(PRECACHE_URLS))
			.then(() => self.skipWaiting())
	);
});

self.addEventListener('activate', event => {
	event.waitUntil(
		caches.keys().then(cacheNames => {
			return Promise.all(
				cacheNames.filter(cacheName => cacheName !== CACHE_NAME)
					.map(cacheName => caches.delete(cacheName))
			);
		}).then(() => self.clients.claim())
	);
});

self.addEventListener('fetch', event => {
	// HTML -> Network First
	if (event.request.headers.get('Accept').includes('text/html')) {
		event.respondWith(
			fetch(event.request)
				.then(response => {
					const responseClone = response.clone();
					caches.open(CACHE_NAME).then(cache => {
						cache.put(event.request, responseClone);
					});
					return response;
				})
				.catch(() => {
					return caches.match(event.request);
				})
		);
		return;
	}

	// Others -> Cache First
	event.respondWith(
		caches.match(event.request)
			.then(response => {
				return response || fetch(event.request);
			})
	);
});
