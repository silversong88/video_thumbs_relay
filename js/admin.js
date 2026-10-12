/**
 * SPDX-FileCopyrightText: 2026 silversong88
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
(function() {
	'use strict'

	const translate = (text, vars) => (typeof window.t === 'function' ? window.t('video_thumbs_relay', text, vars) : text.replace(/{(\w+)}/g, (m, k) => (vars && k in vars ? vars[k] : m)))

	function init() {
		const root = document.getElementById('video-thumbs-relay-admin')
		if (!root) {
			return
		}
		const url = root.dataset.secretUrl
		const generate = document.getElementById('vtr-generate')
		const clear = document.getElementById('vtr-clear')
		const status = document.getElementById('vtr-status')
		const box = document.getElementById('vtr-new-secret')
		const value = document.getElementById('vtr-secret-value')
		const message = document.getElementById('vtr-message')

		async function call(method) {
			message.textContent = ''
			const response = await fetch(url, {
				method,
				headers: { requesttoken: document.head.dataset.requesttoken, Accept: 'application/json' },
			})
			if (!response.ok) {
				throw new Error(translate('Request failed ({status})', { status: response.status }))
			}
			return response.json()
		}

		generate.addEventListener('click', async () => {
			try {
				const data = await call('POST')
				value.value = data.secret
				box.classList.remove('hidden')
				value.select()
				clear.disabled = false
				status.textContent = translate('A secret is set. Requests without it are rejected.')
			} catch (e) {
				message.textContent = e.message
			}
		})

		clear.addEventListener('click', async () => {
			try {
				await call('DELETE')
				box.classList.add('hidden')
				value.value = ''
				clear.disabled = true
				status.textContent = translate('No secret is set. Any signed-in user can upload thumbnails for their own videos.')
			} catch (e) {
				message.textContent = e.message
			}
		})
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init)
	} else {
		init()
	}
})()
