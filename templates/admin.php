<?php
/**
 * SPDX-FileCopyrightText: 2026 silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * @var array{secretSet: bool, secretUrl: string, apiBase: string} $_
 * @var \OCP\IL10N $l
 */
?>
<div id="video-thumbs-relay-admin" class="section" data-secret-url="<?php p($_['secretUrl']); ?>">
	<h2><?php p($l->t('Video Thumbnail Relay')); ?></h2>
	<p class="settings-hint">
		<?php p($l->t('Video previews are rendered by a worker on your own machine, which uploads them to this server. Set the same secret in the worker\'s NC_SECRET setting so only your worker can upload thumbnails.')); ?>
	</p>

	<p>
		<?php p($l->t('API endpoint:')); ?>
		<code><?php p($_['apiBase']); ?></code>
	</p>

	<p id="vtr-status">
		<?php if ($_['secretSet']): ?>
			<?php p($l->t('A secret is set. Requests without it are rejected.')); ?>
		<?php else: ?>
			<?php p($l->t('No secret is set. Any signed-in user can upload thumbnails for their own videos.')); ?>
		<?php endif; ?>
	</p>

	<div id="vtr-new-secret" class="vtr-new-secret hidden">
		<p><?php p($l->t('Copy this secret into the worker\'s NC_SECRET now. It won\'t be shown again.')); ?></p>
		<input id="vtr-secret-value" type="text" readonly aria-label="<?php p($l->t('New secret')); ?>">
	</div>

	<button id="vtr-generate" class="button primary" type="button"><?php p($l->t('Generate new secret')); ?></button>
	<button id="vtr-clear" class="button" type="button" <?php if (!$_['secretSet']): ?>disabled<?php endif; ?>><?php p($l->t('Remove secret')); ?></button>
	<span id="vtr-message" class="msg" aria-live="polite"></span>
</div>
