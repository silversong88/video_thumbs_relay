#!/usr/bin/env bash
# SPDX-FileCopyrightText: 2026 silversong88
# SPDX-License-Identifier: AGPL-3.0-or-later
#
# Installs (or updates) the worker as a system service:
#   code    /opt/video-thumbs-relay        (root-owned, read-only to the service)
#   config  /etc/video-thumbs-relay/worker.env  (root:vtrelay 0640)
#   state   /var/lib/video-thumbs-relay    (caches and temp files)
# The service runs as the unprivileged system user "vtrelay", hourly and from boot.
#
# Usage: sudo NODE=/path/to/node worker/deploy/install.sh
set -euo pipefail

[[ $EUID -eq 0 ]] || { echo "Run as root (sudo)." >&2; exit 1; }

SRC="$(cd "$(dirname "$0")/.." && pwd)"
NODE="${NODE:-$(command -v node || true)}"
[[ -x "$NODE" ]] || { echo "Node.js not found; pass NODE=/path/to/node" >&2; exit 1; }
command -v ffmpeg >/dev/null || { echo "ffmpeg not found; install it first" >&2; exit 1; }

PREFIX=/opt/video-thumbs-relay
CONF=/etc/video-thumbs-relay

id vtrelay >/dev/null 2>&1 || useradd --system --no-create-home \
    --home-dir /var/lib/video-thumbs-relay --shell /usr/sbin/nologin vtrelay

# Code: staged, then swapped in so a failed npm install leaves the old version running
STAGE="$(mktemp -d "$PREFIX.new.XXXXXX")"
trap 'rm -rf "$STAGE"' EXIT
install -d -m 755 "$STAGE/bin"
install -m 644 "$SRC/index.js" "$SRC/package.json" "$SRC/package-lock.json" "$STAGE/"
install -m 755 "$NODE" "$STAGE/bin/node"
(cd "$STAGE" && PATH="$(dirname "$NODE"):$PATH" npm ci --omit=dev --ignore-scripts --no-audit --no-fund --loglevel=error)
chown -R root:root "$STAGE"
chmod 755 "$STAGE"
rm -rf "$PREFIX.old"
[[ -d "$PREFIX" ]] && mv "$PREFIX" "$PREFIX.old"
mv "$STAGE" "$PREFIX"
rm -rf "$PREFIX.old"
trap - EXIT

# Config: never overwrite an existing one
install -d -m 750 -o root -g vtrelay "$CONF"
if [[ ! -f "$CONF/worker.env" ]]; then
    install -m 640 -o root -g vtrelay "$SRC/.env.example" "$CONF/worker.env"
    echo "Created $CONF/worker.env - fill in NC_URL, NC_USER, NC_PASS and NC_SECRET."
fi

install -m 644 "$SRC/deploy/video-thumbs-relay.service" "$SRC/deploy/video-thumbs-relay.timer" /etc/systemd/system/
systemctl daemon-reload
systemctl enable --now video-thumbs-relay.timer
echo "Installed. Logs: journalctl -u video-thumbs-relay.service"
