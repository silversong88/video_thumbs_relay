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

## Running on a schedule (systemd user timer)

```ini
# ~/.config/systemd/user/video-thumbs-relay.service
[Service]
Type=oneshot
WorkingDirectory=/path/to/worker
ExecStart=/usr/bin/node /path/to/worker/index.js --env /path/to/worker/.env
Nice=10
IOSchedulingClass=idle

# ~/.config/systemd/user/video-thumbs-relay.timer
[Timer]
OnBootSec=10min
OnUnitActiveSec=1h
Persistent=true

[Install]
WantedBy=timers.target
```

Then run `systemctl --user enable --now video-thumbs-relay.timer`. Run `loginctl enable-linger $USER` too, so the timer keeps running after you log out.

## Known limitations

- **Replaced thumbnails may not show:** Nextcloud caches previews it has already generated, and has no public API for clearing them. If you re-upload a thumbnail with `--force`, an old cached preview may keep showing until the video file itself changes.
- **Password briefly visible:** while streaming, FFmpeg gets the Authorization header as a command-line argument, so other users on the same machine can briefly see it in the process list.

## Credits

Based on [local-nc-local-thumbnailer](https://github.com/hersche/local-nc-local-thumbnailer) by Vinzenz Hersche. Licensed under AGPL-3.0-or-later.
