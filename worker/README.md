# 🧠 Localthumbs CLI: The Brains of the Operation (v1.0.1)

Your Nextcloud hoster is stingy with CPU cycles? Fine. We'll use your local machine's raw power to crunch those videos into beautiful thumbnails.

**Repos**: [Worker](https://github.com/hersche/local-nc-local-thumbnailer/) | [Nextcloud App](https://github.com/hersche/nc-local-thumbnailer/)

## ⚡ v1.0.1 Improvements
-   **Security**: Added `NC_STRICT_TLS` and `NC_SECRET` support.
-   **Performance**: 
    -   **Parallel I/O**: `ioQueue` handles multiple downloads/API checks simultaneously.
    -   **Sequential Multimedia**: `ffmpegQueue` ensures `ffmpeg` and `ffprobe` never run concurrently.
    -   **Batch API**: Uses `batchExists` to minimize network overhead.
    -   **mtime Caching**: Folders are only re-scanned if their `lastmod` timestamp has changed.
-   **Stability**: Tuned `ffprobe` for higher success rates on remote streams.

## ⚡ Core Features
-   **Smart Caching**:
    -   `folder_cache.csv`: Skips unchanged folders using WebDAV mtime.
    -   `thumb_cache.csv`: Remembers what we've already done.
    -   `fail_cache.csv`: Doesn't waste time retrying broken files.
-   **Ultra-Efficient 3-Stage Processing**:
    1.  **Remote Stream**: Attempts to extract frames directly from the WebDAV URL using HTTP Range requests. This handles 10GB+ files using only a few MBs of bandwidth.
    2.  **Partial Download**: If streaming fails, downloads the first 100MB (works for "Fast Start" optimized files).
    3.  **Full Download**: Last resort fallback, strictly limited by `MAX_VIDEO_SIZE_MB`.
-   **Memory Efficient**: Tested on 4GB RAM instances. Uses Node.js stream pipelines and `spawn`-based child processes to keep a tiny footprint.

## 🛠️ How to use it
1.  **Install**: `npm install`
2.  **Config**: Copy `.env.example` to `.env` and fill in your Nextcloud URL and credentials.
    -   `NC_STRICT_TLS`: Set to `true` to verify SSL certificates (recommended).
    -   `NC_SECRET`: Must match the `api_secret` set on the Nextcloud server.
    -   `IO_CONCURRENCY`: Number of simultaneous downloads (default 2).
3.  **Run**: `node index.js`
4.  **Run (Help)**: `node index.js --help` (Show all available options and environment variables).
5.  **Run (Force Refresh)**: `node index.js --force` (Ignores all caches and overwrites existing thumbnails).
6.  **Run (Delete All)**: `node index.js --delete-all-thumbs` (Removes all thumbnails from the server and wipes local caches).

## 📦 Ubuntu/Debian Packaging
You can package this app as a `.deb` to run it as a systemd service:
1.  Run the build script: `./build-deb.sh`
2.  Install the package: `sudo dpkg -i localthumbs-cli_1.0.1_all.deb`
3.  Configure your credentials in `/etc/localthumbs-cli/localthumbs-cli.env`.
4.  Start the service: `sudo systemctl enable --now localthumbs-cli`

**Note:** This worker requires the [nc-local-thumbnailer](https://github.com/hersche/nc-local-thumbnailer) app to be installed and enabled on your Nextcloud instance.