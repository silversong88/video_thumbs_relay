#!/usr/bin/env bash
# SPDX-FileCopyrightText: 2026 silversong88
# SPDX-License-Identifier: AGPL-3.0-or-later
#
# Starts a throwaway Nextcloud container, enables the app and runs smoke.sh.
#   tests/integration/run-docker.sh <nextcloud-major> [port]
# Set WORKER=1 to include the worker test (needs node + ffmpeg and `npm ci` in worker/).
# Set KEEP=1 to leave the container running afterwards.

set -euo pipefail

VERSION="${1:?usage: run-docker.sh <nextcloud-major> [port]}"
PORT="${2:-$((8000 + VERSION))}"
HERE="$(cd "$(dirname "$0")" && pwd)"
APP_DIR="$(cd "$HERE/../.." && pwd)"
NAME="vtr-it-$VERSION"
PASS="$(head -c 24 /dev/urandom | base64 | tr -d '/+=')"

cleanup() { [[ "${KEEP:-0}" == 1 ]] || docker rm -f "$NAME" >/dev/null 2>&1 || true; }
trap cleanup EXIT

docker rm -f "$NAME" >/dev/null 2>&1 || true
docker run -d --name "$NAME" -p "127.0.0.1:$PORT:80" \
	-e SQLITE_DATABASE=nc -e NEXTCLOUD_ADMIN_USER=admin -e NEXTCLOUD_ADMIN_PASSWORD="$PASS" \
	-e NEXTCLOUD_TRUSTED_DOMAINS=localhost \
	-v "$APP_DIR:/opt/video_thumbs_relay:ro" \
	"nextcloud:$VERSION-apache" >/dev/null

echo "Waiting for Nextcloud $VERSION on port $PORT..."
for _ in $(seq 1 90); do
	if curl -sf "http://localhost:$PORT/status.php" | grep -q '"installed":true'; then
		break
	fi
	sleep 2
done
curl -sf "http://localhost:$PORT/status.php" | grep -q '"installed":true' || { docker logs "$NAME" | tail -30; exit 1; }

# Copied in after install: mounting into custom_apps makes it root-owned, which breaks the installer on Nextcloud 31
docker exec "$NAME" sh -c 'mkdir -p /var/www/html/custom_apps/video_thumbs_relay &&
	tar -C /opt/video_thumbs_relay --exclude=./vendor --exclude=./worker --exclude=./tests --exclude=./.git -cf - . |
	tar -C /var/www/html/custom_apps/video_thumbs_relay -xf - &&
	chown -R www-data:www-data /var/www/html/custom_apps'

OCC="docker exec -u www-data $NAME php occ"
$OCC app:enable video_thumbs_relay
$OCC app:disable firstrunwizard >/dev/null 2>&1 || true

# The web server's route and capability caches only pick up an app enabled via occ after a short delay
echo "Waiting for the app's routes..."
for _ in $(seq 1 60); do
	if curl -s -u "admin:$PASS" -H 'OCS-APIRequest: true' "http://localhost:$PORT/index.php/apps/video_thumbs_relay/thumbnail/exists?path=/x" | grep -q '"exists"' &&
		curl -s -u "admin:$PASS" -H 'OCS-APIRequest: true' "http://localhost:$PORT/ocs/v2.php/cloud/capabilities?format=json" | grep -q '"video_thumbs_relay"'; then
		break
	fi
	sleep 2
done

NC_BASE="http://localhost:$PORT" NC_ADMIN=admin NC_PASS="$PASS" OCC="$OCC" "$HERE/smoke.sh"

errors="$(docker exec "$NAME" sh -c 'cat data/nextcloud.log 2>/dev/null' | grep -c '"app":"video_thumbs_relay"\|video_thumbs_relay/lib' || true)"
if [[ "$errors" != 0 ]]; then
	echo "Nextcloud log has $errors entries mentioning the app:"
	docker exec "$NAME" sh -c 'cat data/nextcloud.log' | grep 'video_thumbs_relay' | tail -5
	exit 1
fi
echo "Nextcloud log: no entries from the app"
