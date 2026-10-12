<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Vinzenz Hersche, silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\VideoThumbsRelay;

use OCA\VideoThumbsRelay\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\Capabilities\ICapability;

class Capabilities implements ICapability {
	public function __construct(
		private IAppManager $appManager,
	) {
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public function getCapabilities(): array {
		return [
			Application::APP_ID => [
				'version' => $this->appManager->getAppVersion(Application::APP_ID),
				'features' => [
					'batch_exists' => true,
					'secret_header' => true,
					'delete_all' => true,
				],
			],
		];
	}
}
