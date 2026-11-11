#!/bin/sh
# Forced command for the GitHub Actions deploy key (see docs/deploy-home-server.md).
# Reads "<github user>\n<token>" on stdin, pulls the new image and restarts the app.
# shortcut: compose.yaml/.env on the server are not updated by CD; rsync them by hand when they change.
set -eu
cd /srv/apps/portofolio

read -r user
read -r token
export DOCKER_CONFIG="$(mktemp -d)"   # keep the token out of ~/.docker
trap 'rm -rf "$DOCKER_CONFIG"' EXIT

printf '%s' "$token" | docker login ghcr.io -u "$user" --password-stdin >/dev/null
docker compose pull app
docker compose up -d --no-build app
docker image prune -f >/dev/null

# Wait for migrations + boot, then fail the job if the site is not answering.
for i in $(seq 1 30); do
  if curl -fsS -o /dev/null http://100.76.192.19:8080/; then echo "deploy ok"; exit 0; fi
  sleep 3
done
echo "site not answering after deploy" >&2
docker compose logs --tail 30 app >&2
exit 1
