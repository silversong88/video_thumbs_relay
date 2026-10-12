<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\VideoThumbsRelay\BackgroundJob;

use OCA\VideoThumbsRelay\Service\ThumbnailStore;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Daily: deletes thumbnails whose video no longer exists in the file cache.
 * Videos in the trash bin stay in the file cache, so their thumbnails are kept until the trash is emptied.
 */
class CleanupOrphans extends TimedJob {
	private const CHUNK = 500;

	public function __construct(
		ITimeFactory $time,
		private ThumbnailStore $store,
		private IDBConnection $db,
	) {
		parent::__construct($time);
		$this->setInterval(24 * 60 * 60);
		$this->setTimeSensitivity(IJob::TIME_INSENSITIVE);
	}

	protected function run($argument): void {
		$chunk = [];
		foreach ($this->store->allFileIds() as $fileId) {
			$chunk[] = $fileId;
			if (count($chunk) >= self::CHUNK) {
				$this->deleteMissing($chunk);
				$chunk = [];
			}
		}
		if ($chunk !== []) {
			$this->deleteMissing($chunk);
		}
	}

	/**
	 * @param list<int> $fileIds
	 */
	private function deleteMissing(array $fileIds): void {
		$qb = $this->db->getQueryBuilder();
		$qb->select('fileid')
			->from('filecache')
			->where($qb->expr()->in('fileid', $qb->createNamedParameter($fileIds, IQueryBuilder::PARAM_INT_ARRAY)));
		$result = $qb->executeQuery();
		$existing = array_map('intval', $result->fetchFirstColumn());
		$result->closeCursor();

		foreach (array_diff($fileIds, $existing) as $missing) {
			$this->store->delete($missing);
		}
	}
}
