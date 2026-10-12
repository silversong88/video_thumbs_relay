<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Vinzenz Hersche, silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\VideoThumbsRelay\Service;

use OCA\VideoThumbsRelay\AppInfo\Application;
use OCP\Files\Folder;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;

/**
 * Stores one JPEG per file ID in app data, sharded as thumbs/{id % 100}/{(id / 100) % 100}/{id}.jpg
 * so that no single folder grows too large.
 */
class ThumbnailStore {
	private const ROOT = 'thumbs';

	public function __construct(
		private IAppData $appData,
		private IRootFolder $rootFolder,
	) {
	}

	public function exists(int $fileId): bool {
		return $this->get($fileId) !== null;
	}

	public function get(int $fileId): ?ISimpleFile {
		try {
			return $this->getShardFolder($fileId, false)->getFile($this->fileName($fileId));
		} catch (NotFoundException) {
			return null;
		}
	}

	/**
	 * @param resource $data
	 */
	public function put(int $fileId, $data): void {
		$folder = $this->getShardFolder($fileId, true);
		$name = $this->fileName($fileId);
		if ($folder->fileExists($name)) {
			$folder->getFile($name)->putContent($data);
		} else {
			$folder->newFile($name, $data);
		}
	}

	public function delete(int $fileId): void {
		$this->get($fileId)?->delete();
	}

	public function deleteAll(): void {
		try {
			$this->appData->getFolder(self::ROOT)->delete();
		} catch (NotFoundException) {
		}
	}

	/**
	 * Yields the file ID of every stored thumbnail.
	 *
	 * ISimpleFolder listings only contain files, so the shard tree is walked as regular nodes.
	 *
	 * @return \Generator<int>
	 */
	public function allFileIds(): \Generator {
		try {
			$root = $this->rootFolder->get($this->rootFolder->getAppDataDirectoryName() . '/' . Application::APP_ID . '/' . self::ROOT);
		} catch (NotFoundException) {
			return;
		}
		if (!$root instanceof Folder) {
			return;
		}
		foreach ($root->getDirectoryListing() as $shard1) {
			if (!$shard1 instanceof Folder) {
				continue;
			}
			foreach ($shard1->getDirectoryListing() as $shard2) {
				if (!$shard2 instanceof Folder) {
					continue;
				}
				foreach ($shard2->getDirectoryListing() as $file) {
					if (preg_match('/^(\d+)\.jpg$/', $file->getName(), $m)) {
						yield (int)$m[1];
					}
				}
			}
		}
	}

	private function fileName(int $fileId): string {
		return $fileId . '.jpg';
	}

	/**
	 * @throws NotFoundException when $create is false and a folder is missing
	 */
	private function getShardFolder(int $fileId, bool $create): ISimpleFolder {
		$folder = $this->getOrCreate(null, self::ROOT, $create);
		$folder = $this->getOrCreate($folder, sprintf('%02d', $fileId % 100), $create);
		return $this->getOrCreate($folder, sprintf('%02d', intdiv($fileId, 100) % 100), $create);
	}

	private function getOrCreate(?ISimpleFolder $parent, string $name, bool $create): ISimpleFolder {
		try {
			return $parent === null ? $this->appData->getFolder($name) : $parent->getFolder($name);
		} catch (NotFoundException $e) {
			if (!$create) {
				throw $e;
			}
			return $parent === null ? $this->appData->newFolder($name) : $parent->newFolder($name);
		}
	}
}
