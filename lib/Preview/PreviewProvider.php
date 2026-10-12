<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Vinzenz Hersche, silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\VideoThumbsRelay\Preview;

use OCA\VideoThumbsRelay\Service\ThumbnailStore;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\IImage;
use OCP\Image;
use OCP\Preview\IProviderV2;
use Psr\Log\LoggerInterface;

/**
 * Serves thumbnails uploaded by the worker as previews for video files.
 */
class PreviewProvider implements IProviderV2 {
	public const MIME_REGEX = '/^video\/.*/';

	public function __construct(
		private ThumbnailStore $store,
		private LoggerInterface $logger,
	) {
	}

	public function getMimeType(): string {
		return self::MIME_REGEX;
	}

	public function isAvailable(FileInfo $file): bool {
		return $this->store->exists($file->getId());
	}

	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		$thumb = $this->store->get($file->getId());
		if ($thumb === null) {
			return null;
		}
		try {
			$image = new Image();
			$image->loadFromData($thumb->getContent());
			if ($image->valid()) {
				$image->scaleDownToFit($maxX, $maxY);
				return $image;
			}
		} catch (\Throwable $e) {
			$this->logger->warning('Could not load stored thumbnail for file ' . $file->getId(), ['exception' => $e]);
		}
		return null;
	}
}
