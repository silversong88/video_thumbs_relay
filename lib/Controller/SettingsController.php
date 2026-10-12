<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\VideoThumbsRelay\Controller;

use OCA\VideoThumbsRelay\Service\SecretService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Admin only (no NoAdminRequired attribute): manages the worker's shared secret.
 */
class SettingsController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private SecretService $secret,
	) {
		parent::__construct($appName, $request);
	}

	public function regenerateSecret(): JSONResponse {
		return new JSONResponse(['secret' => $this->secret->regenerate()]);
	}

	public function clearSecret(): JSONResponse {
		$this->secret->clear();
		return new JSONResponse(['status' => 'success']);
	}
}
