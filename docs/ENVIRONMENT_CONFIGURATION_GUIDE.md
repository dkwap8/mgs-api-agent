# Environment Configuration Guide

Copy `.env.example` to `.env`. Never commit the deployed `.env` file.

## Application

Configure `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `APP_TIMEZONE`, and a generated `APP_KEY`.

## Database

Set `DB_CONNECTION`, host, port, database, username, password, charset, and collation. Use a dedicated least-privilege database account.

## Redis, cache, sessions, and queues

Production should use Redis-backed cache, sessions, locks, and queues. Configure the Redis host, credentials, prefixes, databases, retry settings, queue connection, retry-after window, and blocking timeout. Use a unique prefix per deployment environment.

## Mail

Configure mail transport, host, port, credentials, encryption scheme, EHLO domain, and verified sender details.

## KYC provider

Configure the KYC driver, base URL, NIN and BVN endpoints, authentication scheme, key/secret, timeouts, retry policy, duplicate scope, and response-field mappings.

## Payment/provider integration

Configure provider base URL, API and secret keys, timeout, and webhook secret. Rotate production secrets independently from staging.

## Webhooks

Configure signature header, timestamp tolerance, delivery timeout, and delivery attempts. Ensure public webhook endpoints use HTTPS.

## CORS and sessions

Restrict CORS origins to approved frontend domains. Enable secure, HTTP-only session cookies with an appropriate SameSite policy and production domain.

## Security

- Set `APP_DEBUG=false`.
- Use unique credentials per environment.
- Protect `.env` and backups.
- Rotate keys after suspected exposure.
- Do not reuse webhook or provider secrets.
