<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Vinzenz Hersche, silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\VideoThumbsRelay\Controller;

use OCA\VideoThumbsRelay\Service\SecretService;
use OCA\VideoThumbsRelay\Service\ThumbnailStore;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class ThumbnailController extends Controller {
	private const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;
	private const MAX_BATCH = 500;

	public function __construct(
		string $appName,
		IRequest $request,
		private IRootFolder $rootFolder,
		private IUserSession $userSession,
		private ThumbnailStore $store,
		private SecretService $secret,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[BruteForceProtection(action: 'video_thumbs_relay_secret')]
	public function upload(string $path): JSONResponse {
		if (!$this->secretOk()) {
			return $this->invalidSecret();
		}

		$file = $this->resolveVideo($path);
		if ($file === null) {
			return $this->error('Not a video file you can access: ' . $path, Http::STATUS_NOT_FOUND);
		}

		$upload = $this->request->getUploadedFile('thumbnail');
		if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'] ?? '')) {
			return $this->error('No thumbnail uploaded', Http::STATUS_BAD_REQUEST);
		}
		if (($upload['size'] ?? 0) > self::MAX_UPLOAD_BYTES) {
			return $this->error('Thumbnail larger than 5 MB', Http::STATUS_REQUEST_ENTITY_TOO_LARGE);
		}
		$info = @getimagesize($upload['tmp_name']);
		if ($info === false || $info[2] !== IMAGETYPE_JPEG) {
			return $this->error('Thumbnail must be a JPEG image', Http::STATUS_UNSUPPORTED_MEDIA_TYPE);
		}

		$handle = fopen($upload['tmp_name'], 'rb');
		if ($handle === false) {
			return $this->error('Could not read uploaded thumbnail', Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		try {
			$this->store->put($file->getId(), $handle);
		} catch (\Throwable $e) {
			$this->logger->error('Storing thumbnail failed', ['exception' => $e]);
			return $this->error('Storing thumbnail failed', Http::STATUS_INTERNAL_SERVER_ERROR);
		} finally {
			// newFile() closes the stream itself, so only close it if it is still open
			/** @psalm-suppress RedundantCondition */
			if (is_resource($handle)) {
				fclose($handle);
			}
		}

		return new JSONResponse(['status' => 'success', 'fileId' => $file->getId()]);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[BruteForceProtection(action: 'video_thumbs_relay_secret')]
	public function exists(string $path): JSONResponse {
		if (!$this->secretOk()) {
			return $this->invalidSecret();
		}
		$file = $this->resolveVideo($path);
		return new JSONResponse(['exists' => $file !== null && $this->store->exists($file->getId())]);
	}

	/**
	 * @param list<string> $paths
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[BruteForceProtection(action: 'video_thumbs_relay_secret')]
	public function batchExists(array $paths = []): JSONResponse {
		if (!$this->secretOk()) {
			return $this->invalidSecret();
		}
		if (count($paths) > self::MAX_BATCH) {
			return $this->error('At most ' . self::MAX_BATCH . ' paths per request', Http::STATUS_BAD_REQUEST);
		}

		$results = [];
		foreach ($paths as $path) {
			if (!is_string($path)) {
				continue;
			}
			$file = $this->resolveVideo($path);
			$results[ltrim($path, '/')] = $file !== null && $this->store->exists($file->getId());
		}
		return new JSONResponse(['status' => 'success', 'results' => $results]);
	}

	/**
	 * Admin only: removes every stored thumbnail.
	 */
	#[NoCSRFRequired]
	#[BruteForceProtection(action: 'video_thumbs_relay_secret')]
	public function deleteAll(): JSONResponse {
		if (!$this->secretOk()) {
			return $this->invalidSecret();
		}
		$this->store->deleteAll();
		return new JSONResponse(['status' => 'success', 'message' => 'All thumbnails deleted']);
	}

	private function secretOk(): bool {
		return $this->secret->verify($this->request->getHeader(SecretService::HEADER));
	}

	private function invalidSecret(): JSONResponse {
		$response = $this->error('Invalid secret', Http::STATUS_UNAUTHORIZED);
		$response->throttle(['action' => 'video_thumbs_relay_secret']);
		return $response;
	}

	private function error(string $message, int $status): JSONResponse {
		return new JSONResponse(['status' => 'error', 'message' => $message], $status);
	}

	/**
	 * Resolves a path relative to the current user's home to a video file, or null.
	 */
	private function resolveVideo(string $path): ?File {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return null;
		}
		try {
			$node = $this->rootFolder->getUserFolder($user->getUID())->get(ltrim($path, '/'));
		} catch (NotFoundException) {
			return null;
		}
		if (!$node instanceof File || !str_starts_with($node->getMimetype(), 'video/')) {
			return null;
		}
		return $node;
	}
}
