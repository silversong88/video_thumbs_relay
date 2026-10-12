<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\VideoThumbsRelay\Settings;

use OCA\VideoThumbsRelay\AppInfo\Application;
use OCA\VideoThumbsRelay\Service\SecretService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;
use OCP\Util;

class Admin implements ISettings {
	public function __construct(
		private SecretService $secret,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getForm(): TemplateResponse {
		Util::addScript(Application::APP_ID, 'admin');
		Util::addStyle(Application::APP_ID, 'admin');
		return new TemplateResponse(Application::APP_ID, 'admin', [
			'secretSet' => $this->secret->isSet(),
			'secretUrl' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.settings.regenerateSecret'),
			'apiBase' => $this->urlGenerator->getAbsoluteURL('/index.php/apps/' . Application::APP_ID . '/thumbnail'),
		]);
	}

	public function getSection(): string {
		return 'additional';
	}

	public function getPriority(): int {
		return 50;
	}
}
