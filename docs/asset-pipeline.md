# Asset Pipeline

The Travian T4.6 interface is bundled with Vite and the Laravel Vite plugin. This document outlines how assets are served in both development and production, how cache-busting works, and how the Content Security Policy (CSP) integrates with the build.

## Development

- Run `npm run dev` to start the Vite development server on port 5173. Hot Module Replacement (HMR) automatically refreshes the browser when Blade, Livewire, or asset files change.
- `php artisan serve` (or the Sail environment) proxies Vite while serving PHP responses. The authentication screens already include `@vite([...])`, so the dev server injects module scripts and CSS without manual changes.
- The CSP middleware automatically whitelists `http://localhost:5173` and the matching WebSocket endpoints, and generates a unique nonce that is applied to all rendered scripts and styles.
- Livewire and Flux UI assets inherit the nonce automatically—no additional configuration is required while working locally.

## Production

- Build versioned assets with `npm run build`. This writes hashed files and an updated manifest into `public/build`.
- Deploy the `public/build` directory alongside application code. Because filenames are hashed, browsers receive implicit cache-busting on every deployment.
- Authentication and dashboard pages only reference assets via Vite and the Flux/Livewire helpers, which read the manifest and append version hashes (for example, `?id=abcdef`) to every request.
- The CSP middleware applies the same nonce to Vite, Livewire, and Flux UI responses and enforces `default-src 'self'` without `unsafe-inline`, preventing inline scripts from running unless they carry the generated nonce.
- Additional security headers (`Referrer-Policy`, `X-Content-Type-Options`, `X-Frame-Options`) are applied to every response as part of the middleware, and no extra web-server configuration is required to keep them in sync.

## Troubleshooting

- If Vite throws “Unable to locate file in Vite manifest,” rebuild assets (`npm run build`) and clear cached manifests with `php artisan optimize:clear`.
- When the CSP blocks a request, inspect the browser console for the denied resource. The middleware reads `APP_ASSET_URL` automatically—set this environment variable when serving assets from a CDN so the CSP whitelist updates without code changes.
