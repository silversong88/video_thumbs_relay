# Video Thumbnail Relay

Video previews for Nextcloud instances that can't run FFmpeg themselves, such as managed and shared hosting like Hetzner Storage Share.

There are two parts:

- **Nextcloud app** (`appinfo/`, `lib/`), app ID `video_thumbs_relay`. It adds an API that accepts thumbnails, and a preview provider that serves them as native previews for video files.
- **Worker** (`worker/`). A Node.js script that runs on a machine you control. It scans your files over WebDAV, renders a frame from each video with FFmpeg, and uploads it to the app.

## Status

Version 1.0.0 is available on the [Nextcloud App Store](https://apps.nextcloud.com/apps/video_thumbs_relay) for Nextcloud 31–33.

## Getting started

1. Install **Video Thumbnail Relay** from the Apps page of your Nextcloud.
2. Under **Administration settings → Additional settings → Video Thumbnail Relay**, generate an API secret.
3. Under **Personal settings → Security → Devices & sessions**, create an app password for the worker.
4. On a Linux machine with FFmpeg, set up the worker. See [worker/README.md](worker/README.md).

## Development

```sh
composer install
composer run lint                       # PHP syntax check
composer run psalm                      # static analysis (against nextcloud/ocp)
tests/integration/run-docker.sh 33      # end-to-end test in a throwaway Nextcloud 33 container
WORKER=1 tests/integration/run-docker.sh 31   # include the worker (needs node, ffmpeg, npm ci in worker/)
```

CI runs the same checks on every push and pull request: info.xml validation, PHP lint on PHP 8.2 and 8.5, psalm against the Nextcloud 31–33 public API, and the integration tests on Nextcloud 31, 32 and 33.

## Credits

This is a fork of the work of Vinzenz Hersche:

- Nextcloud app: https://github.com/hersche/nc-local-thumbnailer (App Store ID `localthumbs`)
- Worker: https://github.com/hersche/local-nc-local-thumbnailer

The fork exists because the upstream app has no Nextcloud 33 release. Also, the download host for its App Store release currently fails its TLS handshake, so the release can't be installed.

## License

AGPL-3.0-or-later. See [LICENSE](LICENSE).
