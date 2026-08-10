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

RUN chown --recursive caddy:caddy /etc/caddy /srv/web

USER caddy
