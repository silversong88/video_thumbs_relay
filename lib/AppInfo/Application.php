<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Vinzenz Hersche, silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\VideoThumbsRelay\AppInfo;

use OCA\VideoThumbsRelay\Capabilities;
use OCA\VideoThumbsRelay\Preview\PreviewProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public const APP_ID = 'video_thumbs_relay';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerCapability(Capabilities::class);
		$context->registerPreviewProvider(PreviewProvider::class, PreviewProvider::MIME_REGEX);
	}

	public function boot(IBootContext $context): void {
	}
}
