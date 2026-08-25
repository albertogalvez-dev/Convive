FROM node:24.15.0-bookworm AS frontend

WORKDIR /app

COPY apps/web/package.json apps/web/package-lock.json ./
RUN npm ci

COPY apps/web .
RUN npm run build -- --configuration production

FROM caddy:2.11.4-alpine

RUN addgroup --system caddy \
    && adduser --system --ingroup caddy caddy \
    && chown --recursive caddy:caddy /etc/caddy /usr/share/caddy /data /config

COPY infrastructure/production/Caddyfile /etc/caddy/Caddyfile
COPY --from=frontend /app/dist/convive-web/browser /srv/web
# Caddy resolves the FastCGI front controller before forwarding the request to
# the separate PHP-FPM container. The public bootstrap is safe to copy here:
# it is not served statically and contains no runtime secrets or application
# data, but it lets the gateway resolve `/app/public/index.php` consistently.
COPY apps/api/public /app/public

RUN chown --recursive caddy:caddy /etc/caddy /srv/web /app/public

USER caddy
