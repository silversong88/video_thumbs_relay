<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\VideoThumbsRelay\Service;

use OCA\VideoThumbsRelay\AppInfo\Application;
use OCP\IAppConfig;
use OCP\Security\ISecureRandom;

/**
 * Optional shared secret the worker sends in the X-VideoThumbsRelay-Secret header.
 * When no secret is set, any authenticated user may call the API.
 */
class SecretService {
	public const HEADER = 'X-VideoThumbsRelay-Secret';
	private const KEY = 'api_secret';

	public function __construct(
		private IAppConfig $appConfig,
		private ISecureRandom $random,
	) {
	}

	public function isSet(): bool {
		return $this->get() !== '';
	}

	public function verify(string $provided): bool {
		$secret = $this->get();
		return $secret === '' || hash_equals($secret, $provided);
	}

	public function regenerate(): string {
		$secret = $this->random->generate(48, ISecureRandom::CHAR_ALPHANUMERIC);
		$this->appConfig->setValueString(Application::APP_ID, self::KEY, $secret, sensitive: true);
		return $secret;
	}

	public function clear(): void {
		$this->appConfig->deleteKey(Application::APP_ID, self::KEY);
	}

	private function get(): string {
		return $this->appConfig->getValueString(Application::APP_ID, self::KEY);
	}
}
