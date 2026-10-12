<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Vinzenz Hersche, silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

return [
	'routes' => [
		['name' => 'thumbnail#upload', 'url' => '/thumbnail/upload', 'verb' => 'POST'],
		['name' => 'thumbnail#exists', 'url' => '/thumbnail/exists', 'verb' => 'GET'],
		['name' => 'thumbnail#batchExists', 'url' => '/thumbnail/batchExists', 'verb' => 'POST'],
		['name' => 'thumbnail#deleteAll', 'url' => '/thumbnail/deleteAll', 'verb' => 'POST'],
		['name' => 'settings#regenerateSecret', 'url' => '/settings/secret', 'verb' => 'POST'],
		['name' => 'settings#clearSecret', 'url' => '/settings/secret', 'verb' => 'DELETE'],
	],
];
