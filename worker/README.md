# Video Thumbnail Relay worker

This worker renders video thumbnails on your own machine and uploads them to the **Video Thumbnail Relay** Nextcloud app.

## Requirements

- Node.js 18 or newer.
- `ffmpeg` and `ffprobe` on your `PATH`. On Debian or Ubuntu, run `sudo apt install ffmpeg`.
- The Video Thumbnail Relay app enabled on your Nextcloud.

## Setup

```sh
npm ci --omit=dev
cp .env.example .env    # then edit .env
chmod 600 .env
node index.js
```

- `NC_PASS`: create an app password under **Personal settings → Security → Devices & sessions**. Don't use your login password.
- `NC_SECRET`: generate one under **Administration settings → Additional settings → Video Thumbnail Relay**.

## Usage

| Command | What it does |
| --- | --- |
| `node index.js` | Scans your files and uploads any missing thumbnails. |
| `node index.js --env /path/to/.env` | Uses a different config file, for example one per user. |
| `node index.js --force` | Ignores the local caches and regenerates every thumbnail. |
| `node index.js --delete-all-thumbs` | Removes all thumbnails on the server and clears the local caches. Needs an admin account. |
| `node index.js --help` | Lists all options and environment variables. |

To get the first frame, the worker tries three methods in order:
1. It streams the video from WebDAV using HTTP range requests.
2. It downloads the first 100 MB of the file.
3. It downloads the whole file, up to `MAX_VIDEO_SIZE_MB`.

FFmpeg jobs run one at a time, while downloads run in parallel (`IO_CONCURRENCY`).

## Running as a system service

`deploy/install.sh` installs the worker as a hardened systemd service. The service runs every 30 minutes and 10 minutes after boot, with no login needed.

```sh
sudo NODE="$(command -v node)" deploy/install.sh
sudoedit /etc/video-thumbs-relay/worker.env    # first install only
```

| Path | Contents |
| --- | --- |
| `/opt/video-thumbs-relay` | Worker code, its dependencies and a copy of Node.js. Owned by root. |
| `/etc/video-thumbs-relay/worker.env` | Connection settings. Mode `0640 root:vtrelay`. |
| `/var/lib/video-thumbs-relay` | Caches and temporary files. |

The service runs as the unprivileged system user `vtrelay`, which can't read home directories or write outside its state directory. The unit sets the cache and temp paths itself, so leave them out of `worker.env`.

Re-run the same `install.sh` command to update the worker. Your config and caches are kept.

```sh
systemctl list-timers video-thumbs-relay.timer   # next run
journalctl -u video-thumbs-relay.service -f      # live log
sudo systemctl start video-thumbs-relay.service  # run now
```

## Known limitations

- **Replaced thumbnails may not show:** Nextcloud caches previews it has already generated, and has no public API for clearing them. If you re-upload a thumbnail with `--force`, an old cached preview may keep showing until the video file itself changes.
- **Password briefly visible:** while streaming, FFmpeg gets the Authorization header as a command-line argument, so other users on the same machine can briefly see it in the process list.

## Credits

Based on [local-nc-local-thumbnailer](https://github.com/hersche/local-nc-local-thumbnailer) by Vinzenz Hersche. Licensed under AGPL-3.0-or-later.
