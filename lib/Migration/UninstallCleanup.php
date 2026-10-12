<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\VideoThumbsRelay\Migration;

use OCA\VideoThumbsRelay\AppInfo\Application;
use OCA\VideoThumbsRelay\Service\ThumbnailStore;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Removes all stored thumbnails and app settings when the app is uninstalled.
 */
class UninstallCleanup implements IRepairStep {
	public function __construct(
		private ThumbnailStore $store,
		private IAppConfig $appConfig,
	) {
	}

	public function getName(): string {
		return 'Remove Video Thumbnail Relay thumbnails and settings';
	}

	public function run(IOutput $output): void {
		$this->store->deleteAll();
		$this->appConfig->deleteApp(Application::APP_ID);
	}
}
