# Video Thumbnail Relay

Video previews for Nextcloud instances that can't run FFmpeg themselves, such as managed and shared hosting like Hetzner Storage Share.

There are two parts:

- **Nextcloud app** (`appinfo/`, `lib/`), app ID `video_thumbs_relay`. It adds an API that accepts thumbnails, and a preview provider that serves them as native previews for video files.
- **Worker** (`worker/`). A Node.js script that runs on a machine you control. It scans your files over WebDAV, renders a frame from each video with FFmpeg, and uploads it to the app.

## Status

The app is being adapted for Nextcloud 33 and prepared for the Nextcloud App Store. Until a release is published, treat this repository as work in progress.

## Credits

This is a fork of the work of Vinzenz Hersche:

- Nextcloud app: https://github.com/hersche/nc-local-thumbnailer (App Store ID `localthumbs`)
- Worker: https://github.com/hersche/local-nc-local-thumbnailer

The fork exists because the upstream app has no Nextcloud 33 release. Also, the download host for its App Store release currently fails its TLS handshake, so the release can't be installed.

## License

AGPL-3.0-or-later. See [LICENSE](LICENSE).
