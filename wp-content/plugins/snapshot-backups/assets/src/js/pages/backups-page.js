/**
 * Page: Backups.
 */
var $ = jQuery;
/**
 * Helper collections.
 */
const Helper = {

	/**
	 * Checks if provided string is empty.
	 *
	 * @param {string} label
	 * @returns {boolean}
	 */
	isEmpty: function (label) {
		return ('' === label || null === label || 'undefined' === label ) ? true : false;
	},

	/**
	 * Checks if the provided data is pure JS object.
	 *
	 * @param {*} obj
	 * @returns {boolean}
	 */
	isObject: function (obj) {
		if (null === obj || 'undefined' === typeof obj) {
			return false;
		}

		if ('object' === typeof obj && 'Object' === obj.constructor.name) {
			return true;
		}

		return false;
	},

	/**
	 * Convert pure Object to FormData
	 *
	 * @param {Object} obj
	 * @returns {FormData}
	 */
	objToFormData: function (obj) {
		if (this.isObject(obj)) {
			let formData = new FormData();
			for (const key in obj) {
				formData.append(key, obj[key]);
			}
			return formData;
		}

		return obj;
	},

	/**
	 * Appends the query strings to the url.
	 *
	 * @param {string} url
	 * @param {*} args
	 *
	 * @returns {string}
	 */
	buildUrl: function(url, args) {
		if (this.isEmpty(url)) {
			return '';
		}

		let params = new URLSearchParams();

		for (let key in args) {
			if (!this.isEmpty(args[key])) {
				params.set(key, args[key]);
			}
		}

		return `${url}?${params.toString()}`
	},

	/**
	 * Resolve or Reject the promise.
	 *
	 * @returns {Promise}
	 */
	 deferred: function () {
		let res,
			rej,
			p = new Promise((a,b) => (res = a, rej = b));

		p.resolve = res;
		p.reject = rej;
		return p;
	},

	/**
	 * Sends the AJAX request.
	 *
	 * @param {Object} data
	 *
	 * @returns {Promise}
	 */
	request: function(data) {
		let method = 'POST';
		if ('method' in data && 'GET' === data.method) {
			method = 'GET';
		}
		delete data['method'];

		let args = {
			credentials: 'same-origin',
			method
		};

		let ajaxUrl = SnapshotAjax.ajaxurl;
		if ('GET' === method) {
			ajaxUrl = this.buildUrl(ajaxUrl, data);
		} else {
			data = this.objToFormData(data);
			args.body = data;
		}

		return fetch(ajaxUrl, args);
	},

	/**
	 * Trigger the defined event.
	 *
	 * @param {string} event Name of the event.
	 * @param {*} els        List of elements|element.
	 */
	trigger: (event, els) => {
		if ( ! els || Helper.isEmpty(event) || els.length === 0 ) {
			return;
		}

		if ( els.length > 1 ) {
			els.forEach((el) => {
				el.dispatchEvent(new Event(event));
			});
		} else {
			els.dispatchEvent(new Event(event));
		}
	},
};

/**
 * UI object.
 */
const UI = {
	/**
	 * Snapshot backups page tabs.
	 */
	tabs: {
		backups: ['.snapshot-list-backups', '.snapshot-vertical-backups'],
		logs: ['.snapshot-logs', '.snapshot-vertical-logs'],
		settings: ['.snapshot-backups-settings', '.snapshot-vertical-settings'],
		notifications: ['.snapshot-notifications', '.snapshot-vertical-notifications']
	},

	/**
	 * Switch the tab
	 * @param {string} tab Id of the tab to switch to.
	 */
	switchTab: function(tab) {
		if (Helper.isEmpty(tab)) {
			return;
		}

		const $navTab = document.querySelector(this.tabs[tab][1]);
		const $tabEl  = document.querySelector(this.tabs[tab][0]);

		for (let name in this.tabs) {
			if (tab === name) {
				$navTab.classList.add('current');
				$tabEl.style.display = 'block';
			} else {
				document.querySelector(this.tabs[name][0]).style.display = 'none';
				document.querySelector(this.tabs[name][1]).classList.remove('current');
			}
		}
	},

	/**
	 * Hide errors.
	 *
	 * @param {string} selector
	 */
	hideErrors: function (selector) {
		const $els = document.querySelectorAll(selector);

		if ($els.length > 0) {
			$els.forEach(($el) => {
				if ('block' === $el.style.display) {
					$el.style.display = 'none';
				}
			});
		}
	},

	/**
	 * Display the notice.
	 *
	 * @param {*} args
	 */
	displayNotice: function (args) {
		if (!'message' in args || Helper.isEmpty(args.message)) {
			// Message must be set and mustn't be empty.
			return;
		}

		let icon_class = '';

		switch (args.type) {
			case 'success':
				icon_class = 'check-tick';
				break;

			case 'error':
			case 'warning':
				icon_class = 'warning-alert';
				break;

			case 'info':
			default:
				icon_class = 'info';
				break;
		}

		const options = {
			icon: icon_class,
			type: args.type
		};

		if (args.dismissible) {
			options.dismiss = {show: true};
		}

		if (args.autoclose) {
			options.autoclose = {timeout: args.autoclose};
		} else {
			options.autoclose = {show: false};
		}

		const $suiHeader    = document.querySelector('.sui-header');
		const noticeId     = 'snapshot-notice-' + Math.random().toString(36).substr(2,9);
		let $noticesWrapper = document.querySelector('.sui-floating-notices');
		let $notice         = document.createElement('div');

		if (!$noticesWrapper) {
			$noticesWrapper = document.createElement('div');
			$noticesWrapper.setAttribute('class', 'sui-floating-notices');
		}
		$suiHeader.insertBefore($noticesWrapper, $suiHeader.childNodes[0]);

		$notice.setAttribute('id', noticeId);
		$notice.setAttribute('role', 'alert');
		$notice.setAttribute('aria-live', 'assertive');
		$notice.setAttribute('class', 'sui-notice');

		$noticesWrapper.appendChild($notice);

		const noticeContent = `<p>${args.message}</p>`;
		SUI.openNotice(noticeId, noticeContent, options);
	},

	/**
	 * Initialize the select2 dropdown for normal "Select"
	 *
	 * @param {HTMLSelectElement} $el
	 */
	initSelect2: function ($el) {
		if ('nodeName' in $el && 'select' !== $el.nodeName.toLowerCase() ) {
			return;
		}

		SUI.select.init(jQuery($el));

		// Add "onchange" event to the "select"
		$el.onchange = (e) => {
			Log.filter(e);
		};
	},

	/**
	 * Attach 'click' event to all the dynamically appended .log-row
	 *
	 * @param {Event} event Click event.
	 */
	tr_click: function(event) {
		let el = event.target;

		if (el.classList.contains('log-row') || el.parentNode.classList.contains('log-row')) {
			if ( 'nodeName' in el && '' !== el.nodeName && 'tr' !== el.nodeName.toLowerCase() ) {
				el = el.parentNode;
				Log.single(el);
			}
		}
	},

	/**
	 * Build backups table.
	 *
	 * @param {*} data
	 */
	build_backups_table: function(response) {
		const $page_main = document.querySelector(Log.main_page);
		const $backups   = $page_main.querySelector('.snapshot-listed-backups');
		const $table     = $backups.querySelector('table');

		if (response.backups !== undefined) {
			const backups_number = response.backups.length;
			const failed_number  = response.failed_backups || 0;

			$table.querySelector('tbody').innerHTML = '';

			const backups = response.backups;
			if (backups && backups.length) {
				const $tbody = document.querySelector('tbody');
				backups.forEach((backup) => {
					const row = backup.row;
					$tbody.innerHTML += backup.row;
					$tbody.innerHTML += backup.row_content;
				});

				const trs = $tbody.querySelectorAll('tr.snapshot-row');
				trs.forEach((tr) => {
					tr.classList.add('sui-accordion-item');
					const nextTr = tr.nextElementSibling;
					nextTr.querySelector('.row-current-schedule').querySelector('.sui-loading').style.display = 'none';
					nextTr.querySelector('.open-edit-schedule').style.display = 'block';
					nextTr.querySelector('span.schedule').textContent = document.querySelector('.snapshot-schedule-frequency').textContent;
					const openEditSchedule = nextTr.querySelector('.open-edit-schedule');
					openEditSchedule.onclick = (e) => {
						e.preventDefault();
						SUI.openModal('modal-snapshot-edit-schedule', e.target);
					}
				});
			}
		}
	}
};

/**
 * Log
 */
const Log = {
	/**
	 * @var {boolean}
	 */
	processing: false,

	/**
	 * @var {string}
	 */
	main_page: '.snapshot-page-main',

	/**
	 * @var {string}
	 */
	logs_list: '.logs-list',

	/**
	 * Checks if logs are loaded or not.
	 *
	 * @returns {boolean}
	 */
	isListReady: function() {
		const mainPage = document.querySelector(this.main_page);
		return ('1' === mainPage.querySelector(this.logs_list).getAttribute('data-logs-loaded')) ? true : false;
	},

	/**
	 * Loads all log file.
	 *
	 * @param {HTMLButtonElement} el
	 * @returns {Promise}
	 */
	all: function (el) {
		if (this.processing) {
			return;
		}
		this.processing = true;
		let resp = Helper.deferred();

		const $mainPage = document.querySelector(this.main_page);
		const $logLists = $mainPage.querySelector(this.logs_list);

		// Prepare data for AJAX request.
		const data = {
			action: 'snapshot-get_log_list',
			_wpnonce: el.getAttribute('data-nonce'),
			method: 'GET'
		};

		const request = Helper.request(data);
		request
			.then(response => response.json())
			.then((result) => {
				if (result.success) {
					$logLists.setAttribute('data-logs-loaded', 1);
					if ('' !== result.data.content) {
						$logLists.querySelector('.log-rows').innerHTML = result.data.content;
						$logLists.querySelector('.logs-loading').style.display = 'none';
						$logLists.querySelector('.logs-not-empty').style.display = 'block';
						resp.resolve();
					} else {
						UI.displayNotice({
							type: 'warning',
							message: snapshot_messages.no_logs_found,
							autoclose: 3000
						});
						resp.reject();
					}
				} else {
					UI.displayNotice({
						type: 'warning',
						message: snapshot_messages.failed_listing_logs,
						autoclose: 3000
					});
					resp.reject(result.data);
				}
				el.removeAttribute('disabled');
				this.processing = false;
			})
			.catch((err) => {
				this.processing = false;
				el.removeAttribute('disabled');
				UI.displayNotice({
					type: 'warning',
					message: snapshot_messages.failed_listing_logs,
					autoclose: 3000
				});
				resp.reject();
			})

		return resp;
	},

	/**
	 * Loads the single log file.
	 *
	 * @param {*} el
	 *
	 * @returns {void}
	 */
	single: function (el) {
		if (this.processing) {
			return;
		}

		const backupId = el.getAttribute('data-backup-id');
		if (Helper.isEmpty(backupId)) {
			return;
		}
		this.processing = true;
		const $mainPage = document.querySelector(this.main_page);
		let $tableRow   = null;
		if ( 'nodeName' in el && '' !== el.nodeName && 'tr' === el.nodeName.toLowerCase() ){
			$tableRow = el;
		} else {
			$tableRow = $mainPage.querySelector(`tr[data-backup-id="${backupId}"]`);
		}

		if (!$tableRow) {
			this.processing = false;
			el.removeAttribute('disabled');
			UI.displayNotice({
				type: 'warning',
				message: snapshot_messages.backup_log_not_found,
				autoclose: 3000
			});
			return;
		}
		const trSibling = $tableRow.nextElementSibling;

		const data = {
			action: 'snapshot-get_backup_log',
			_wpnonce: el.getAttribute('data-nonce'),
			backup_id: backupId,
			method: 'GET'
		};

		const backup = Helper.request(data);
		backup
			.then(response => response.json())
			.then((result) => {
				trSibling.querySelector('.snapshot-loading').style.display = 'none';
				trSibling.querySelector('.snapshot-loaded').style.display = 'block';
				if (result.success) {
					$tableRow.setAttribute('data-append-log', 1);

					// Hide all errors.
					UI.hideErrors('.notice-error');
					const $select = trSibling.querySelector('select');
					if ($select) {
						UI.initSelect2($select);
					}
					if ('items' in result.data.log) {
						const items = result.data.log.items;

						items.forEach((item) => {
							const entry = `<div class="log-item log-level-${item.level}">
								<div class="log-item__icon" aria-hidden="true"></div>
								<div class="log-item__content">${item.message}</div>
							</div>`;
							trSibling.querySelector('.log-lists').innerHTML += entry;
						});
					}


					// We need to set the pagination as well.
					if ('pages' in result.data.log && result.data.log.pages) {
						const $paginateLog = trSibling.querySelector('.paginate-log');
						$paginateLog.parentNode.style.display = 'block'
						$paginateLog.setAttribute('data-pages', result.data.log.pages);
						$paginateLog.setAttribute('data-next', result.data.log.next_page);
						$paginateLog.setAttribute('data-current', result.data.log.current_page);
					} else if (!result.data.log.pages) {
						trSibling.querySelector('.paginate-log').style.display = 'none';
					}

				} else {
					let $noContent = null;
					if ('no_content' === result.data.status ) {
						$noContent = $mainPage.querySelector('.no-log-content');
					} else {
						$noContent = $mainPage.querySelector('.general-error');
					}

					$noContent.style.display = 'block';
				}
				el.removeAttribute('disabled');
				this.processing = false;
			})
			.catch((err) => {
				// Display the error message.
				el.removeAttribute('disabled');
				this.processing = false;
			});

	},

	/**
	 * Paginates the single log file.
	 *
	 * @param {HTMLButtonElement} el
	 *
	 * @returns {void}
	 */
	paginate: function(el) {
		if (this.processing) {
			return;
		}

		const page      = parseInt(el.getAttribute('data-current'));
		const next      = parseInt(el.getAttribute('data-next'));
		const pages     = parseInt(el.getAttribute('data-pages'));
		const backupId  = el.getAttribute('data-backup-id');
		const boxEl     = el.closest('.sui-box-body');

		// We cannot proceed if we don't have "backup id".
		if (Helper.isEmpty(backupId)) {
			return;
		}
		// Do we need to proceed?
		if (page > pages || next <= 0 || page === pages) {
			el.parentNode.style.display = 'none';
			return;
		}

		this.processing = true;
		el.setAttribute('disabled', 'disabled');
		el.classList.add('sui-button-onload');

		const data = {
			method: 'GET',
			action: 'snapshot-paginate_log',
			_wpnonce: el.getAttribute('data-nonce'),
			offset: next,
			backup_id: backupId
		};

		const request = Helper.request(data);
		request
			.then(response => response.json())
			.then((result) => {
				if (result.success) {

					if ('items' in result.data.log) {
						const items = result.data.log.items;

						items.forEach((item) => {
							const entry = `<div class="log-item log-level-${item.level}">
								<div class="log-item__icon" aria-hidden="true"></div>
								<div class="log-item__content">${item.message}</div>
							</div>`;
							boxEl.querySelector('.log-lists').innerHTML += entry;
						});
					}

					if ('pages' in result.data.log && result.data.log.pages) {
						el.setAttribute('data-current', result.data.log.current_page);
						el.setAttribute('data-next', result.data.log.next_page);

						if (parseInt(result.data.log.current_page) === parseInt(result.data.log.pages)) {
							el.parentNode.style.display = 'none';
						}
					} else {
						el.parentNode.style.display = 'none';
					}

				} else {
					UI.displayNotice({
						type: 'warning',
						message: snapshot_messages.failed_listing_logs,
						autoclose: 3000
					});
				}
				this.processing = false;
				el.removeAttribute('disabled');
				el.classList.remove('sui-button-onload');
			})
			.catch((err) => {
				UI.displayNotice({
					type: 'warning',
					message: snapshot_messages.failed_listing_logs,
					autoclose: 3000
				});
				this.processing = false;
				el.removeAttribute('disabled');
				el.classList.remove('sui-button-onload');
			});
	},

	/**
	 * Filters the log entries.
	 *
	 * @param {HTMLSelectElement} e
	 *
	 * @returns {void}
	 */
	filter: function(e) {
		if ('' === e.target.value) {
			return;
		}

		const el         = e.target;
		const value      = el.value;
		const allErrors  = el.closest('td').querySelectorAll('.notice-error');
		const allEntries = el.closest('td').querySelectorAll('.log-item');
		const error      = el.closest('td').querySelector(`.no-${value}`);
		let hasErrors    = [];

		allEntries.forEach((entry) => {
			if ('all' === value) {
				allErrors.forEach((err) => { err.style.display = 'none' });
				entry.style.display = 'block';
			} else {
				const sel   = `log-level-${value}`;
				allErrors.forEach((err) => { err.style.display = 'none' });
				if (entry.classList.contains(sel)) {
					entry.style.display = 'block';
					hasErrors.push(value);
				} else {
					entry.style.display = 'none';
				}
			}
		});

		if (hasErrors.length === 0) {
			allErrors.forEach((err) => { err.style.display = 'none' });
			if ('all' !== value) error.style.display = 'block';
		}
	}
};

/**
 * Backup
 */
const Backup = {

	/**
	 * Initialize and register "click" listener
	 *
	 * @returns {void}
	 */
	init: function() {
		const $mainPage = document.querySelector('.snapshot-page-main');
		const _self     = this;
		if ($mainPage) {
			$mainPage.addEventListener('click', (e) => {
				_self.click(e);
			});
		}
	},

	/**
	 * Delegated click event.
	 *
	 * @param {Event} e
	 *
	 * @returns {void}
	 */
	click: function(e) {
		let el = e.target;

		if (el.classList.contains('view-log') || el.parentNode.classList.contains('view-log')) {
			e.preventDefault();
			if (el.parentNode.classList.contains('view-log')) {
				el = el.parentNode;
			}

			const mainPage = document.querySelector(Log.main_page);
			const backup   = el.getAttribute('data-backup-id');
			el.setAttribute('disabled', 'disabled');

			if (Log.isListReady()) {
				const $tableRow = mainPage.querySelector(`tr[data-backup-id="${backup}"]`);
				if (!$tableRow) {
					Log.processing = false;
					el.removeAttribute('disabled');
					UI.displayNotice({
						type: 'warning',
						message: snapshot_messages.backup_log_not_found,
						autoclose: 3000
					});
					return;
				}
				$tableRow.click();
				// Switch to "logs" tab.
				UI.switchTab('logs');
				Log.single(el);
			} else {
				const lists = Log.all(el);
				lists.then((result) => {
					const $tableRow = mainPage.querySelector(`tr[data-backup-id="${backup}"]`);
					if (!$tableRow) {
						Log.processing = false;
						UI.displayNotice({
							type: 'warning',
							message: snapshot_messages.backup_log_not_found,
							autoclose: 3000
						});
						return;
					}
					$tableRow.click();
					UI.switchTab('logs');
					Log.single(el);
					const logLists = mainPage.querySelectorAll(".log-row");
					if (logLists.length) {
						logLists.forEach((list) => {
							list.onclick = UI.tr_click.bind(this);
						});
					}
					Helper.trigger('click', logLists);
				})
				.catch((err) => {
					Log.processing = false;
					el.removeAttribute('disabled');
					UI.displayNotice({
						type: 'warning',
						message: snapshot_messages.backup_log_not_found,
						autoclose: 3000
					});
				});
			}
		}

		if (el.classList.contains('paginate-log') || el.parentNode.classList.contains('paginate-log')) {
			e.preventDefault();
			if (el.parentNode.classList.contains('paginate-log')) {
				el = el.parentNode;
			}

			const loadedBox = el.closest('.snapshot-loaded');
			if (loadedBox) {
				const filterLog = loadedBox.querySelector('.log-filter');
				if ('all' !== filterLog.value) {
					const all = filterLog.querySelector('option[value="all"]');
					all.selected = true;
					filterLog.dispatchEvent(new Event('change'));
				}
			}

			/**
			 * Paginate the log file.
			 */
			Log.paginate(el);
		}
	},

	/**
	 * Reload all backups.
	 *
	 * @param {String} nonce
	 */
	reload: function(nonce) {
		const data = {
			_wpnonce: nonce,
			action: 'snapshot-list_backups',
			force_refresh: '1',
			method: 'POST'
		};
		const $main_page = document.querySelector(Log.main_page);
		if ($main_page) {
			$main_page.querySelector('.snapshot-listed-backups').style.display = 'none';
			$main_page.querySelector('.snapshot-backup-list-loader').style.display = 'block';
		}

		const request = Helper.request(data);
		request
			.then(response => response.json())
			.then((result) => {
				$main_page.querySelector('.snapshot-backup-list-loader').style.display = 'none';
				$main_page.querySelector('.snapshot-listed-backups').style.display = 'block';
				UI.build_backups_table(result.data);
			})
			.catch((err) => {
				console.log(err);
			})
	},
};
// Initialize the backup logs.
Backup.init();

const $main_page = document.querySelector('.snapshot-page-main');

const submit_backup_comment_form = (e) => {
	e.preventDefault();
	const el = e.target;
	const btn = el.querySelector('button[type=submit]');
	const action = btn.getAttribute('data-action-type');  // add|edit actions.
	const backupId = el.querySelector('#comment-backup-id').value;
	let comment = el.querySelector('textarea').value;

	if ( null === backupId || 'undefined' === backupId || '' === backupId ) {
		el.closest('.sui-box-body').querySelector('#notice-no-backup-id').style.display = 'block';
		return;
	} else {
		el.closest('.sui-box-body').querySelector('#notice-no-backup-id').style.display = 'none';
	}

	comment = comment.trim();
	if ('' === comment || null === comment || 'undefined' === comment) {
		comment = '';
	}

	// Show loading icon on button.
	btn.classList.add('sui-button-onload');

	const formData = new FormData();
	formData.append('comment_type', action);
	formData.append('description', comment);
	formData.append('backup_id', backupId);
	formData.append('action', 'snapshot-update_backup');
	formData.append('_wpnonce', el.querySelector('#_wpnonce-snapshot_update_backup_comment').value);

	const request = fetch( SnapshotAjax.ajaxurl, {
		method: 'POST',
		credentials: 'same-origin',
		body: formData
	})
	request
		.then(response => response.json())
		.then((result) => {
			Backup.reload(result.data.nonce);

			if (result.success) {
				btn.classList.remove('sui-button-onload');
				SUI.closeModal();
				let msg = null;
				if ('edit' === action) {
					msg = snapshot_messages.comment_updated;
				} else if ('add' === action) {
					msg = snapshot_messages.comment_added;
				}
				UI.displayNotice({
					type: 'success',
					message: msg,
					dismissible: true
				});
			} else {
				// UI.displayNotice({
				// 	type: 'warning',
				// 	message: ''
				// })
			}
		})
		.catch((err) => {
			btn.classList.remove('sui-button-onload');
			SUI.closeModal();
			UI.displayNotice({
				type: 'warning',
				message: snapshot_messages.generic_error,
				autoclose: 3000
			});
		});
};


const $backupForm = document.querySelector('#form-snapshot-edit-manual-backup-comment');
if ( $backupForm ) {
	// Add backup form 'submit' event listener.
	$backupForm.addEventListener('submit', (e) => {
		submit_backup_comment_form(e);
	});
}

/**
 * Binds the click event.
 *
 * @param {Event} e
 *
 * @returns
 */
const open_backup_comment_modal  = (e) => {
	let el = e.target;

	if (
		el.classList.contains('open-edit-backup')
		|| el.parentNode.classList.contains('open-edit-backup')
	) {
		e.preventDefault();

		if (el.classList.contains('sui-icon-pencil') || el.classList.contains('snapshot-backup--content')) {
			el = el.parentNode;
		}

		const type     = el.getAttribute('data-type');
		const modal    = 'modal-snapshot-update-backup-comment';
		const button   = document.querySelector(`#${modal}`).querySelector('button[type=submit]');
		const comment  = el.getAttribute('data-tooltip');
		const snapshot = el.closest('tr').getAttribute('data-backup_id');

		if ('edit' === type) {
			if (comment) {
				document.querySelector(`#${modal}`).querySelector('#manual-backup-comment-modal').value = comment.trim();
			}
			button.querySelector('.sui-button-text-default').innerHTML = snapshot_messages.edit_comment_text;
		} else if ('add' === type ) {
			button.querySelector('.sui-button-text-default').innerHTML = snapshot_messages.add_comment_text;
			document.querySelector(`#${modal}`).querySelector('#manual-backup-comment-modal').value = '';
		}
		document.querySelector(`#${modal}`).querySelector('#comment-backup-id').value = snapshot;
		button.setAttribute('data-action-type', type);
		if (button.classList.contains('sui-button-onload')) {
			button.classList.remove('sui-button-onload');
		}

		SUI.openModal(modal, el);
	}
}

let $textArea = null;
let exclusions = null;
if ($main_page) {
	// Attach the 'click' event listener to the main page.
	$main_page.addEventListener('click', open_backup_comment_modal);
	$textArea = $main_page.querySelector('#snapshot-file-exclusions');
}

/**
 * Dispatches an event.
 *
 * @param {*}      $el   Element where the events need to be dispatched.
 * @param {string} event Event name.
 */
const trigger = ($el, event) => {
	$el.dispatchEvent(new Event(event));
}

var cuClicked = false;
/**
 * Clears the exclusions list in bulk.
 * @param {*} el
 */
const clear_exclusions = (el) => {
	exclusions = $textArea.value;
	$textArea.value = '';
	trigger($textArea, 'change');
}

/**
 * Restores the exclusions list when cleared in bulk.
 * @param {*} el
 */
const undo_exclusions = (el) => {
	$textArea.value = exclusions;
	trigger($textArea, 'change');
}

if ($textArea && null !== $textArea) {
	// Listen to the textarea change.
	$textArea.addEventListener('change', (e) => {
		const $wrap     = e.target.closest('.sui-multistrings-wrap');
		const $lists    = $wrap.querySelector('.sui-multistrings-list');
		const $desc     = $lists.nextElementSibling;
		const $allLists = $lists.querySelectorAll('li');
		const $clearUndo = $desc.querySelector('.snapshot-clear-undo');

		if ($allLists && $allLists.length) {
			if ($allLists.length > 1 && $allLists.length < 3) {
				if(!cuClicked) {
					$clearUndo.style.display = 'none';
				} else {
					$clearUndo.style.display = 'inline';
				}
			} else if ($allLists.length >= 3) {
				$clearUndo.style.display = 'inline';
			}
		}
		cuClicked = false;
	});
}

const $custom_events = document.querySelectorAll('a[data-custom-event]');
if ($custom_events) {
	$custom_events.forEach( ($el) => {
		$el.addEventListener('click', (evt) => {
			evt.preventDefault();
			let el = evt.target;
			let nodename = '';
			if ('nodeName' in el && '' !== el.nodeName ) {
				nodename = el.nodeName.toLowerCase();
			}

			if ('strong' === nodename || 'span' === nodename) {
				el = el.parentNode;
			}
			const action = el.getAttribute('data-custom-event');

			if ('' !== action) {
				cuClicked = true;
			}
			if ('clear-exclusions' === action) {
				el.nextElementSibling.style.display = 'inline';
				clear_exclusions(el);
				el.style.display = 'none';
			} else if ('undo-exclusions' === action) {
				el.previousElementSibling.style.display = 'inline';
				undo_exclusions(el);
				el.style.display = 'none';
			}
		});
	});
}

/**
 * Handle file exclusions
 *
 * @param {Event} e
 * @returns
 */
const handle_file_exclusions = (e) => {
	const $el = e.target;
	if (
		$el.classList.contains('sui-button-close')
		|| $el.parentNode.classList.contains('sui-button-close')
	) {
		e.preventDefault();
		const elementToRemove = $el.closest('li');
		const elementText     = elementToRemove.getAttribute('title');
		const textAreaVal = $textArea.value;
		const replaceable = elementText + "\n";
		textAreaVal.replace( replaceable, '');
		$textArea.value = textAreaVal;
		trigger($textArea, 'change');
		return;
	}
}

if ($main_page) {
	$main_page.addEventListener('click', (e) => {
		handle_file_exclusions(e);
	});
}

/**
 * Display the "Clear exclusions" when "enter" is pressed.
 */
window.addEventListener('keyup', (e) => {
	if ('key' in e && '' !== e.key && 'enter' === e.key.toLowerCase() ) {
		const $lists    = $main_page.querySelector('.sui-multistrings-list');
		const $allLists = $lists.querySelectorAll('li');

		if ($lists && $allLists.length > 2) {
			e.preventDefault();
			trigger($textArea, 'change');
		}
	}
});

;(function($) {

	// Offset for continuous update of the running backup log, false - if don't need to update the log
	var update_log_offset = false;

	// Allow auto scrolling in log modal
	var log_allow_auto_scroll = true;

	// Previous scroll position in log modal
	var log_prev_scroll = 0;

	var last_schedule_info = null;

	/**
	 * Tabs in Backups page. Array - [box selector, vertical selector, mobile nav selector].
	 *
	 * @type {Object}
	 */
	var navbar_tabs = {
		'backups': ['.snapshot-list-backups', '.snapshot-vertical-backups'],
		'logs': ['.snapshot-logs', '.snapshot-vertical-logs'],
		'settings': ['.snapshot-backups-settings', '.snapshot-vertical-settings'],
		'notifications': ['.snapshot-notifications', '.snapshot-vertical-notifications']
	};

	/**
	 * Toggles the navbar to show specified tab.
	 *
	 * @param {string} tab Tab in Backups page.
	 */
	function toggle_navbar(tab) {
		for (var current_tab in navbar_tabs) {
			var box_selector = navbar_tabs[current_tab][0];
			var vertical_selector = navbar_tabs[current_tab][1];

			if (tab === current_tab) {
				// Toggle to a box.
				$('.snapshot-page-main').find(box_selector).show();
				// Make a sidenav active.
				$('.snapshot-page-main').find(vertical_selector).addClass('current');
			} else {
				$('.snapshot-page-main').find(box_selector).hide();
				$('.snapshot-page-main').find(vertical_selector).removeClass('current');
			}
		}

		if (tab === 'logs') {
			reload_logs(false);
		}

		return false;
	}

	function get_current_storage() {
		var url = ajaxurl + '?action=snapshot-get_storage';
		var data = {
			_wpnonce: $( '#_wpnonce-snapshot_get_storage' ).val()
		};

		$.ajax({
			type: 'POST',
			url: url,
			data: data,
			cache: false,
			dataType: 'json',
			success: function (response) {
				if (response.success) {
					var widget = $('.snapshot-storage-widget');
					if (response.data.width_float >= 0.95) {
						widget.addClass('full');
						if (!$('.insufficient-storage-space-notice').length) {
							var notice = $('<span class="insufficient-storage-space-notice"></span>')
								.html(snapshot_messages.insufficient_storage_space_notice);
							jQuery(window).trigger('snapshot:show_top_notice', ['error', notice]);
						}
					} else {
						widget.removeClass('full');
						$('.insufficient-storage-space-notice').trigger('snapshot:close_notice');
					}
					widget.find('.snapshot-storage-used-value').html(response.data.current_stats.replace(/\//, ' /'));
					widget.find('.sui-progress-bar > span').css({width: response.data.width});
					widget.css('visibility', 'visible');
				}
			}
		});
	}

    // Retrieve backups info list.
    function snapshot_list_backups(force_refresh) {
        if (force_refresh === undefined) {
            force_refresh = false;
        }

        get_current_storage();

        var data = {
            _wpnonce: $( '#_wpnonce-list-backups' ).val(),
            force_refresh: force_refresh ? 1 : 0
        };

        $('.snapshot-listed-backups').hide();
        $('.snapshot-no-backups').hide();
        // $('.wps-backup-list-ajax-error').hide();

        var deferred = jQuery.Deferred();

        var list_backups_href = ajaxurl + '?action=snapshot-list_backups';

        snapshot_ajax_lhb_xhr = jQuery.ajax({
            type: 'POST',
            url: list_backups_href,
            data: data,
            cache: false,
            dataType: 'json',
			beforeSend: function () {
				$('.sui-box-header.snapshot-has-backups-title').hide();
				$('.snapshot-loading').show();
				$('.snapshot-list-backups .api-error').hide();
				$('.sui-summary-details.snapshot-backups-number .sui-summary-large').text('');
				$('.sui-summary-segment .snapshot-last-backup').text('');
			},
			complete: function () {
				$('.snapshot-loading').hide();
			},
            success: function (reply_data) {
                if (reply_data.success && reply_data.data.backups !== undefined) {
                    backups_number = reply_data.data.backups.length;
					failed_backups_number = reply_data.data.failed_backups || 0;

                    $('.snapshot-listed-backups .sui-table > tbody').empty();

                    // Deal with updating the number of backups.
                    $('.sui-summary-details.snapshot-backups-number .sui-summary-large').html( backups_number - failed_backups_number );

                    if ( reply_data.data.backups && backups_number !== 0) {
                        // Deal with the Last Backup section.
                        lastBackup = reply_data.data.backups[0];
						$('.sui-summary-segment .snapshot-last-backup').html( lastBackup.date );

						// Disable the Backup Now button, if we're still in the same minute with the last backup taken.
						var time_elapsed = ( Date.now() / 1000 - lastBackup.timestamp ) / 60 ;
						$(window).trigger('snapshot:toggle_cooldown', [time_elapsed]);

                        // Deal with the backup listing and loader.
                        $('.snapshot-listed-backups').show();
                        $('.snapshot-no-backups').hide();
                        $('.sui-box-header.snapshot-has-backups-title').show();

                        $.each(reply_data.data.backups, function(i, item) {
                            var row = $(item.row);
                            if (!row.hasClass('snapshot-failed-backup')) {
                                row.addClass('sui-accordion-item');
                            }
                            $('.snapshot-listed-backups .sui-table > tbody:last-child').append(row);
                            $('.snapshot-listed-backups .sui-table > tbody:last-child').append(item.row_content);
						});
						update_backup_rows_schedule();

						fix_export_destination_tooltips();
                    } else {
                        // Deal with the Last Backup section.
                        $('.sui-summary-segment .snapshot-last-backup').text(snapshot_messages.last_backup_unknown_date);

                        if (false === reply_data.data.backup_running) {
                            // Deal with the backup listing header.
                            $('.sui-box-header.snapshot-has-backups-title').hide();

                            // Deal with the backup listing and loader.
                            $('.snapshot-no-backups').show();
                        } else {
                            $('.snapshot-listed-backups').show();
                            $('.sui-box-header.snapshot-has-backups-title').show();
                        }
                    }

                    deferred.resolve(reply_data);
                } else {
                    deferred.reject("error");
                }
                if (!reply_data.success) {
                    show_api_error();
                }
            },
            error: function () {
                deferred.reject("HTTP error");
                show_api_error();
            }
        });

        return deferred.promise();
    }

	function fix_export_destination_tooltips() {
		// Workaround to both show the size tooltip above the icon and to show it when hovering the whole span.
		$('.snapshot-tooltip-size').on('mouseenter', function () {
			$(this).parent().find('.snapshot-icon-tooltip2').show();
			$(this).parent().find('.snapshot-icon-tooltip').hide();
		}).on('mouseleave', function () {
			$(this).parent().find('.snapshot-icon-tooltip').show();
			$(this).parent().find('.snapshot-icon-tooltip2').hide();
		});

		// Workaround to both show the export tooltip above the icon and to show it when hovering the whole span.
		$('.snapshot-export-details-failure').parent().find('.snapshot-export-backup-details').on('mouseenter', function () {
			$(this).parent().find('.snapshot-export-details-failure2').show();
			$(this).parent().find('.snapshot-export-details-failure').hide();
		}).on('mouseleave', function () {
			$(this).parent().find('.snapshot-export-details-failure').show();
			$(this).parent().find('.snapshot-export-details-failure2').hide();
		});

		// Workaround to hide the destination list tooltip when the i tooltip is hovered.
		$(window).trigger('snapshot:hide_double_tooltip');
	}

	function show_api_error() {
		$('.snapshot-list-backups .api-error').show();
		$('.sui-summary-details.snapshot-backups-number .sui-summary-large').text('-');
		$('.sui-summary-segment .snapshot-last-backup').text('-');
		$('.sui-box-header.snapshot-has-backups-title').show();
	}

	function snapshot_get_schedule(open_modal, data) {
		var schedule_modal = function (data) {
			$('#snapshot-backup-schedule').find('>span').text(data.text);
			$('#snapshot-backup-schedule').data('values', data.values);
			$('.snapshot-backups-summary .snapshot-next-backup').text(data.next_backup_time);
			if (open_modal) {
				$(window).trigger('snapshot:schedule', [data.schedule_action, data.values, data.schedule_action]);
			}
		};

		var url = ajaxurl + '?action=snapshot-get_schedule';
		var request_data = {
			_wpnonce: $('#_wpnonce-get-schedule').val()
		};

		if (data) {
			schedule_modal(data);
		} else {
			$.ajax({
				type: 'GET',
				url: url,
				data: request_data,
				cache: false,
				dataType: 'json',
				beforeSend: function () {
					last_schedule_info = null;
					$('.sui-summary-segment .snapshot-next-backup').text('');
					$('.sui-summary-segment .snapshot-schedule-frequency').text('');
					$('#snapshot-backup-schedule .button-manage').hide();
					$('.snapshot-loading-schedule').show();
				},
				success: function (response) {
					if (response.success) {
						schedule_modal(response.data);
						$('#snapshot-backup-schedule').data('modalData', response.data);

						$('.sui-summary-segment .snapshot-next-backup').text(response.data.next_backup_time);
						$('.sui-summary-segment .snapshot-schedule-frequency').text(response.data.text);
						$('#snapshot-backup-schedule').data('modalData', response.data);
						$('#snapshot-backup-schedule .button-manage').show();
						last_schedule_info = response.data;
						update_backup_rows_schedule();
					} else {
						on_load_schedule_error();
					}
				},
				complete: function () {
					$('.snapshot-loading-schedule').hide();
				},
				error: function () {
					on_load_schedule_error();
				}
			});
		}
	}

	function update_backup_rows_schedule() {
		if (last_schedule_info) {
			var line = $('.snapshot-listed-backups .open-edit-schedule');
			line.data('modalData', last_schedule_info);
			line.find('>span.schedule').text(last_schedule_info.text);
			line.show();
			$('.snapshot-listed-backups .row-current-schedule .sui-loading').hide();
		}
	}

	function on_load_schedule_error() {
		jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.get_schedule_error]);
		$('.sui-summary-segment .snapshot-next-backup').text('-');
		$('.sui-summary-segment .snapshot-schedule-frequency').text('-');

		$('.snapshot-listed-backups .row-current-schedule .sui-loading').hide();
	}

	function snapshot_backup_progress(needs_api_call, already_running_backup_status, already_running_backup) {
        if ($('.snapshot-listed-backups .sui-table > tbody:last-child .current-backup-row').data('updating')) {
            return;
        }

        needs_api_call = needs_api_call || '0';
		already_running_backup_status = already_running_backup_status || '0';
		already_running_backup = already_running_backup || '0';

        var refresh_interval = 5000;

        var url = ajaxurl + '?action=snapshot-update_backup_progress';
		var request_data = {
			_wpnonce: $('#_wpnonce-backup-progress').val(),
			do_api_call: needs_api_call,
			already_running_backup: already_running_backup,
			already_running_backup_status: already_running_backup_status
        };

		if (update_log_offset !== false) {
			// Also get new log entries since the last update
			request_data.expand = 'log';
			request_data.log_offset = update_log_offset;
		}

        $.ajax({
            type: 'GET',
            url: url,
            data: request_data,
            cache: false,
            dataType: 'json',
            success: function (response) {
                var tbody = $('.snapshot-listed-backups .sui-table > tbody:last-child');
                var row = tbody.find('.current-backup-row');
				var row_details = tbody.find('.current-backup-details');
				if ( response.success ) {
					if ( true === response.data.backup_cancelled ) {
						backup_is_cancelled();
					} else if ( true === response.data.backup_failed ) {
						// Running backup has failed.

						if (response.data.error_message_html) {
							var notice = $(response.data.error_message_html);
							var log_link = notice.find('.snapshot-log-link');
							log_link.on('click', function (e) {
								var link = this;
								$(link).data('backupId', already_running_backup.id);
								e.preventDefault();
								notice.trigger('snapshot:close_notice');
								// reload logs on log tab before switching to it
								reload_logs(true).then(function () {
									view_log.bind(link)();
									toggle_navbar('logs');
									$(window).trigger('snapshot:close_modal');
								});
							});
							jQuery(window).trigger('snapshot:show_top_notice', ['error', notice]);
						} else {
							var notice = $('<span></span>').html(snapshot_messages.running_backup_fail);
							var a = notice.find('a');
							a.eq(0).on('click', function (e) {
								var link = this;
								$(link).data('backupId', already_running_backup.id);
								e.preventDefault();
								notice.trigger('snapshot:close_notice');
								// reload logs on log tab before switching to it
								reload_logs(true).then(function () {
									view_log.bind(link)();
									toggle_navbar('logs');
									$(window).trigger('snapshot:close_modal');
								});
							});
							a.eq(1).on('click', function (e) {
								e.preventDefault();
								notice.trigger('snapshot:close_notice');

								jQuery(window).trigger('snapshot:backup_modal');
							});

							jQuery(window).trigger('snapshot:show_top_notice', ['error', notice]);
							var snippet = notice.find('.sui-code-snippet');
							if (snippet) {
								snippet.SUICodeSnippet({
									copyText: '<span class="sui-icon-copy" aria-hidden="true"></span>'
								});
							}
							snippet.closest('.sui-code-snippet-wrapper').find('.sui-button').removeClass('sui-button').addClass('sui-button-icon');
						}

						$('.button-create-backup').prop('disabled', false);

						reload_logs(true);

						snapshot_list_backups(true);
					} else if ( 'snapshot_completed' === response.data.backup_running_status ) {
						// Running backup is completed, yay.
						$('#snapshot-modal-cancel-backup #snapshot-cancel-backup').prop('disabled', true);
						update_last_backup_row();
					} else if (false !== response.data.backup_running) {
						// Running backup is yet to be completed, keep doing what you doing.
						var data = $(response.data.backup_running_row).data();
						if (row_details.length && data.percent > 0) {
							row_details.find('.button-view-log').prop('disabled', false);
							row_details.find('.button-cancel-backup').prop('disabled', false);
						}
						if (row.length) {
							// Don't replace the entire block
							// in order to preserve animation phase of loader and prevent its jitter...
							row.find('>tr').attr('id', data.id);
							row.data('backupId', data.backupId);
							row.find('.backup-name').text(data.name);
							row.find('.progress-text').text(data.progressText);

							// Build the Export Destination column
							var export_destination_text;
							if ('None' === response.data.export_text.row) {
								export_destination_text = snapshot_messages.no_destinations;
							} else if ('Loading' === response.data.export_text) {
								export_destination_text = "<span class='sui-icon-loader sui-loading snapshot-destination-loader' aria-hidden='true'></span>" + snapshot_messages.loading_destinations
							} else if (typeof response.data.export_text.html !== 'undefined'){
								var exports = response.data.export_text.html;
								if ( 1 < exports.exports_count ) {
									var export_text = exports.first_export + snapshot_messages.more_destinations.replace('%d', exports.exports_count - 1);
									export_destination_text = "<span class='snapshot-export-backup-header snapshot-" + exports.first_export_type + "-export-backup-header sui-tooltip sui-tooltip-left-mobile sui-tooltip-constrained' style='--tooltip-width: 170px;' data-tooltip='" + exports.exports_tooltip + "'>" + export_text + '</span>';
								} else {
									export_destination_text  = "<span class='snapshot-export-backup-header snapshot-" + exports.first_export_type + "-export-backup-header'>" + exports.first_export + "</span>";
								}
							}
							row.find('.snapshot-backup-export-destinations span').html(export_destination_text);
							row_details.find('.snapshot-backup-export-destinations span').html(export_destination_text);

							row.find('.percent-width').css('width', data.percentWidth);
							$('.current-backup-step-wrap').each(function () {
								var wrap = $(this);
								wrap.attr('class').split(' ').filter(function (cl) {
									return cl.match(/^step\-/);
								}).forEach(function (cl) {
									wrap.removeClass(cl);
								});
								var progressPercent = wrap.find('.progressbar-container .sui-screen-reader-text > p');
								progressPercent.text(progressPercent.text().replace(/\d+%/, data.progressText));
							});
							$('.current-backup-step-wrap').addClass('step-' + data.step);
							$('.current-backup-step-wrap .current-step').text('Step ' + data.step + '/' + data.stepMax);
						} else {
							tbody.prepend(response.data.backup_running_row);
						}

						// If backup still at trigger stage, repeat the progress call with data from the db, if not repeat with data from the DOM.
						if ('manual' !== response.data.backup_running.id) {
							next_running_backup = response.data.backup_running;
						} else {
							next_running_backup = '0';
						}

						// Let's repeat the process, but this time make an api call to get the status.
						// We don't need to do that upon page load, because we use the info taken and stored by the Snapshot_Task_Request_Listing upon page load.
						setTimeout(snapshot_backup_progress.bind(this, '1', response.data.backup_running_status, next_running_backup), refresh_interval);
					}
				} else {
					if ( $('#snapshot-modal-cancel-backup #snapshot-cancel-backup').hasClass('sui-button-onload-text') ) {
						backup_is_cancelled();
					} else {
						jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.update_progress_fail]);
					}
				}

				if (response.data && response.data.log) {
					// Update log with new entries and hide loader if snapshot_completed or backup_failed
					var done = 'snapshot_completed' === response.data.backup_running_status || response.data.backup_failed;
					update_log(response.data.log, false, !done);
					// Set offset for next update or stop updating if done
					update_log_offset = (done || update_log_offset === false) ? false : response.data.log.size;
				} else if (already_running_backup.id && update_log_offset !== false) {
					// Get new log entries since the last update
					update_log_ajax(already_running_backup.id, update_log_offset, false, false);
					// and stop continuous update of the log
					update_log_offset = false;
				} else {
					// Hide loader in the log modal
					update_log(null, false, false);
				}
            },
            error: function () {
                // Hide loader in the log modal
                update_log(null, false, false);
                jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.update_progress_fail]);
            }
        });
	}

	function update_last_backup_row() {
		var tbody = $('.snapshot-listed-backups .sui-table > tbody:last-child');
		var row = tbody.find('.current-backup-row');
		if (!row.length || row.data('updating')) {
			return;
		}
		row.data('updating', true);

		reload_logs();

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'snapshot-list_backups',
				_wpnonce: $( '#_wpnonce-list-backups' ).val(),
				force_refresh: 1
			},
			cache: false,
			dataType: 'json',
			success: function (data) {
				if (data.success && data.data.backups !== undefined) {
					var item = data.data.backups[0];
					var backups_number = data.data.backups.length;
					failed_backups_number = data.data.failed_backups || 0;
					var last_backup = data.data.backups[0];

					// Disable the Backup Now button, if we're still in the same minute with the last backup taken.
					var time_elapsed = ( Date.now() / 1000 - last_backup.timestamp ) / 60 ;
					$(window).trigger('snapshot:toggle_cooldown', [time_elapsed]);

					row.find('.progress-text').text('100%');
					row.find('.percent-width').css('width', '100%');
					var step_max = row.data('stepMax');
					$('.current-backup-step-wrap').each(function () {
						var wrap = $(this);
						wrap.attr('class').split(' ').filter(function (cl) {
							return cl.match(/^step\-/);
						}).forEach(function (cl) {
							wrap.removeClass(cl);
						});
						var progressPercent = wrap.find('.progressbar-container .sui-screen-reader-text > p');
						progressPercent.text(progressPercent.text().replace(/\d+%/, '100%'));
					});
					$('.current-backup-step-wrap').addClass('step-' + step_max);
					$('.current-backup-step-wrap .current-step').text('Step ' + step_max + '/' + step_max);

					$('.snapshot-listed-backups .snapshot-details-row').removeClass('snapshot-last-backup');
					setTimeout(function () {

                        $('.button-create-backup').prop('disabled', false);

						row.remove();
						row = $(item.row);
						var row_content = $(item.row_content);
						if (!row.hasClass('snapshot-failed-backup')) {
						    row.addClass('sui-accordion-item');
						}
						$('.snapshot-listed-backups .sui-table > tbody:last-child').prepend(row_content);
						$('.snapshot-listed-backups .sui-table > tbody:last-child').prepend(row);
						update_backup_rows_schedule();

						// Workaround to hide the destination list tooltip when the i tooltip is hovered.
						$(window).trigger('snapshot:hide_double_tooltip');

						$('.sui-summary-details.snapshot-backups-number .sui-summary-large').html( backups_number - failed_backups_number );
						$('.sui-summary-segment .snapshot-last-backup').html( last_backup.date );
						// Show the appropriate message of backup completion.
						var export_header = row.find('.snapshot-export-backup-header');
						var backup_complete_msg,
							backup_complete_icon;
						if (!export_header.length) {
							backup_complete_msg = snapshot_messages.create_backup_success;
							backup_complete_icon = 'success';
						} else if (export_header.parent().find('.snapshot-export-failure').length ) {
							backup_complete_msg = snapshot_messages.export_backup_failure;
							backup_complete_icon = 'error';
						} else {
							backup_complete_msg = snapshot_messages.export_backup_success;
							backup_complete_icon = 'success';
						}
						jQuery(window).trigger('snapshot:show_top_notice', [backup_complete_icon, backup_complete_msg]);
						row.trigger('click');
					}, 3000);
				} else {
					row.data('updating', false);
				}
			},
			error: function () {
				row.data('updating', false);
			}
		});
	}

	function handle_create_manual_backup(e) {
		if (e && e.preventDefault) e.preventDefault();

		var form = $('#form-snapshot-create-manual-backup');
		var data = {};
		form.serializeArray().forEach(function (item) {
			data[item.name] = item.value;
		});

		var request_data = {
			action: 'snapshot-trigger_backup',
			_wpnonce: $( '#_wpnonce-snapshot_backup_create_manual' ).val(),
			data: {
				backup_name: data.backup_name,
				description: data.backup_description,
				apply_exclusions: data.apply_exclusions === 'on'
			}
		};

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: request_data,
			beforeSend: function () {
				form.find('.sui-button').addClass('sui-button-onload-text', true);
				$('.button-create-backup').prop('disabled', true);
			},
			complete: function () {
				form.find('.sui-button').removeClass('sui-button-onload-text', true);
				form.find('#manual-backup-comment').val('');
				form.find('#snapshot-manual-apply-exclusions').prop('checked', false);
			},
			success: function (response) {
                if ( response.success && false !== response.data.backup_running ) {
                    if ($('.snapshot-listed-backups .snapshot-row').length) {
                        jQuery(window).trigger('snapshot:show_top_notice', ['info', snapshot_messages.backup_is_in_progress]);
                    } else {
                        // First backup
                        jQuery(window).trigger('snapshot:show_top_notice', ['info', snapshot_messages.manual_backup_success]);
                    }

                    $('.snapshot-listed-backups').show();
                    $('.snapshot-no-backups').hide();
                    $('.sui-box-header.snapshot-no-backups-title').hide();
                    $('.sui-box-header.snapshot-has-backups-title').show();

                    $('.snapshot-listed-backups .sui-table > tbody:last-child').prepend(response.data.backup_running);
                    snapshot_backup_progress('1', 'just_triggered');
                } else {
                    if (response.data && Array.isArray(response.data.messages) && response.data.messages.length) {
                        response.data.messages.forEach(function (message) {
                            var message_key = 'manual_' + message;
                            var notice = $('<span></span>').html(snapshot_messages[message_key]);
                            notice.find('a').eq(0).on('click', function () {
                                notice.trigger('snapshot:close_notice');
                                toggle_navbar('logs');
                            });
                            notice.find('a').eq(1).on('click', function () {
                                notice.trigger('snapshot:close_notice');
                                jQuery(window).trigger('snapshot:backup_modal');
                            });
                            jQuery(window).trigger('snapshot:show_top_notice', ['error', notice]);
                        });
                    } else {
                        jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.manual_backup_error]);
                    }
                }
				$(window).trigger('snapshot:close_modal');
			}
		});
	}

	function delete_backup(e, backup) {
		e = e || false;
		if (e && e.preventDefault) e.preventDefault();

		var delete_buttons = $('.snapshot-last-backup .snapshot-delete-backup .sui-button-icon');
		call_if_can_delete_backup(delete_backup_confirm.bind(this, backup), function () {
			delete_buttons.prop('disabled', true);
		}, function () {
			delete_buttons.prop('disabled', false);
		});
	}

	function delete_backup_confirm(backup) {
		SUI.openModal('snapshot-modal-delete-backup', this);
		$('#snapshot-modal-delete-backup').data('backupId', backup);
		$('#snapshot-delete-backup-button').removeClass('sui-button-onload-text').addClass('sui-button-red');
		$('#snapshot-modal-delete-backup-error-notice').hide();
		return false;
	}

	function delete_backup_confirmed() {
		var backup = $('#snapshot-modal-delete-backup').data('backupId');
		delete_backup_force(backup);
	}

	function delete_backup_force(backup) {
		var delete_buttons = $('.snapshot-last-backup .snapshot-delete-backup .sui-button-icon, #snapshot-settings-delete-backups-confirm');
		var modal_button = $('#snapshot-delete-backup-button');

		delete_buttons.prop('disabled', true);
		modal_button.addClass('sui-button-onload-text').removeClass('sui-button-red');
		$('#snapshot-modal-delete-backup-error-notice').hide();

		var request_data = {
			action: 'snapshot-delete_backup',
			_wpnonce: $('#_wpnonce-delete-backup').val(),
			data: {
				backup_id: backup,
			}
		};

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: request_data,
			success: function (response) {
                if ( response.success ) {
                    jQuery(window).trigger('snapshot:show_top_notice', ['info', snapshot_messages.settings_delete_success, 3000, false]);

                    var Promise = $.when(snapshot_list_backups());
                    Promise.done(function () {
                        snapshot_backup_progress();
                    });

                    var log_row = $('.logs-list .log-row[data-backup-id="' + backup + '"]');
                    log_row.next().remove();
                    log_row.remove();
                    if (!$('.logs-list .log-row').length) {
                        $('.snapshot-logs .logs-empty').show();
                        $('.snapshot-logs .logs-not-empty').hide();
                    }
                    $(window).trigger('snapshot:close_modal');
                } else {
                    $('#snapshot-modal-delete-backup-error-notice').show();
                }
            },
                error: function () {
                $('#snapshot-modal-delete-backup-error-notice').show();
            },
            complete: function () {
                delete_buttons.prop('disabled', false);
                modal_button.removeClass('sui-button-onload-text').addClass('sui-button-red');
            }
        });
    }

    function export_backup(e, backup) {
        if (e && e.preventDefault) e.preventDefault();

        var request_data = {
			action: 'snapshot-export_backup',
			_wpnonce: $('#_wpnonce-export-backup').val(),
			backup_id: backup
        };

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: request_data,
			success: function (response) {
                if ( response.success ) {
                    var notice = $('<span></span>').html(snapshot_messages.backup_export_success.replace('%s', response.data.site));
                    jQuery(window).trigger('snapshot:show_top_notice', ['success', notice]);
                } else {
                    var notice = $('<span></span>').html(snapshot_messages.backup_export_error);
                    jQuery(window).trigger('snapshot:show_top_notice', ['error', notice]);
                }
            },
            error: function () {
                var notice = $('<span></span>').html(snapshot_messages.backup_export_error);
                jQuery(window).trigger('snapshot:show_top_notice', ['error', notice]);
            }
        });
    }

	function cancel_backup_confirm() {
		var backup_row = $(this).closest('table').find('.current-backup-row');
		var backup_id = backup_row.data('backupId');
		$('#snapshot-modal-cancel-backup #snapshot-cancel-backup').data('backupId', backup_id);
		$('#snapshot-modal-cancel-backup #snapshot-cancel-backup').prop('disabled', false);
		SUI.openModal('snapshot-modal-cancel-backup', this);
	}

	function cancel_backup() {
		var backup_id = $(this).data('backupId');
		var data = {};

		data._wpnonce = $( '#_wpnonce-snapshot_cancel_backup' ).val();
		data.backup_id = backup_id;

		var url = ajaxurl + '?action=snapshot-cancel_backup';

		$.ajax({
			type: 'POST',
			url: url,
			data: data,
			cache: false,
			dataType: 'json',
			beforeSend: function () {
				$('#snapshot-modal-cancel-backup .snapshot-cancel-backup-cancel').prop('disabled', true);
				$('#snapshot-modal-cancel-backup #snapshot-cancel-backup').prop('disabled', true);
				$('#snapshot-modal-cancel-backup #snapshot-cancel-backup').addClass('sui-button-onload-text');
			},
			success: function (data) {
				if ( ! data.success) {
					jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.cancel_backup_error]);
				}
			},
			error: function () {
				$(window).trigger('snapshot:close_modal');
				jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.cancel_backup_error]);
			}
		});

		return false;
	}

	function filter_log() {
		var select = $(this);
		var container = select.closest('.sui-box').find('.log-items-container');
		var no_warning = container.find('.no-warning');
		var no_error = container.find('.no-error');
		var log_items = container.find('.log-lists > .log-item');
		var show_items = log_items;
		var value = select.val();

		no_warning.hide();
		no_error.hide();
		log_items.hide();

		switch (value) {
			case 'all':
				break;
			case 'warning':
				show_items = log_items.filter('.log-level-warning, .log-level-error');
				if (!show_items.length) {
					no_warning.show();
				}
				break;
			case 'error':
				show_items = log_items.filter('.log-level-error');
				if (!show_items.length) {
					no_error.show();
				}
				break;
		}

		show_items.show();
	}

	function view_backup(backup_id) {
		var row = $('.snapshot-listed-backups .snapshot-row[data-backup_id="' + backup_id + '"]');
		if (row.length) {
			toggle_navbar('backups');
			if (!row.hasClass('sui-accordion-item--open')) {
				row.trigger('click');
			}
			$([document.documentElement, document.body]).animate({
				scrollTop: row.offset().top - 35
			}, 'slow');
		} else {
			jQuery(window).trigger('snapshot:show_top_notice', ['warning', snapshot_messages.log_backup_not_found]);
		}
	}

	function view_log(e, force_reload, backup_id) {
		var $this = $(this);
		if (force_reload === undefined) {
			force_reload = false;
		}
		if (!backup_id) {
			backup_id = $this.data('backupId');
		}
		reload_logs(force_reload).done(function () {
			var row = $('.logs-list .log-row[data-backup-id="' + backup_id + '"]');
			if (row.length) {
				toggle_navbar('logs');
				if (!row.hasClass('sui-accordion-item--open')) {
					row.trigger('click');
				}
				$([document.documentElement, document.body]).animate({
					scrollTop: row.offset().top - 35
				}, 'slow');
			} else {
				jQuery(window).trigger('snapshot:show_top_notice', ['warning', snapshot_messages.backup_log_not_found]);
			}
		});
	}

	function on_log_row_click() {
		var row = $(this);
		var content = row.next();
		if (content.data('loaded')) {
			return;
		}
		Log.single(this);
	}

	function reload_logs(force) {
		if (force === undefined) {
			force = true;
		}

		var deferred = jQuery.Deferred();

		if (!force) {
			var logs_loaded = $('.snapshot-page-main .logs-list').data('logsLoaded');
			if (logs_loaded) {
				deferred.resolve();
				return deferred.promise();
			}
		}

		jQuery.ajax({
			type: 'GET',
			url: ajaxurl,
			data: {
				action: 'snapshot-get_log_list',
				_wpnonce: $('#_wpnonce-get-backup-log').val()
			},
			cache: false,
			dataType: 'json',
			beforeSend: function () {
				$('.snapshot-logs .logs-empty').hide();
				$('.snapshot-logs .logs-not-empty').hide();
				$('.logs-list .logs-loading').show();
			},
			complete: function () {
				$('.logs-list .logs-loading').hide();
			},
			success: function (data) {
				if (data.success) {
					$('.logs-list').data('logsLoaded', true);
					$('.snapshot-logs .log-rows').html(data.data.content);
					$('.logs-list .log-row').on('click', on_log_row_click);
					$('.logs-list .log-rows select').each(function () {
						SUI.select.init($(this));
					});
					if (data.data.show_log) {
						$('.snapshot-logs .logs-empty').hide();
						$('.snapshot-logs .logs-not-empty').show();
					} else {
						$('.snapshot-logs .logs-empty').show();
						$('.snapshot-logs .logs-not-empty').hide();
					}
					fix_export_destination_tooltips();
					deferred.resolve();
				} else {
					deferred.reject();
				}
			},
			error: function () {
				deferred.reject();
			}
		});
		return deferred.promise();
	}

	/**
	 * Open log modal for running backup and start continuous update
	 */
	function open_log_modal(e, backup_id) {
		var modal = $('#snapshot-modal-log');
		if (!modal.length) {
			return;
		}

		modal.find('.sui-box-header .sui-button-icon').on('click', function () {
			update_log_offset = false;
			$(window).trigger('snapshot:close_modal');
		});

		SUI.openModal('snapshot-modal-log', this);

		update_log_ajax(backup_id, 0, true, true);
	}

	/**
	 * Update log modal for running backup with new log entries which are after offset
	 */
	function update_log_ajax(backup_id, offset, clear, show_loader) {
		$.ajax({
			type: 'GET',
			url: ajaxurl,
			data: {
				action: 'snapshot-get_backup_log',
				backup_id: backup_id,
				offset: offset,
				_wpnonce: $('#_wpnonce-get-backup-log').val()
			},
			cache: false,
			dataType: 'json',
			success: function (data) {
				if (data.success) {
					update_log(data.data.log, clear, show_loader);
				} else {
					// Hide loader if error
					update_log(null, false, false);
				}
			},
			error: function () {
				// Hide loader if error
				update_log(null, false, false);
			}
		});
	}

	/**
	 * Update log modal for running backup with new log entries from log.items and scroll down
	 */
	function update_log(log, clear, show_loader) {
		var modal = $('#snapshot-modal-log');
		var log_container = modal.find('.log-container');
		var frame = log_container.closest('.sui-border-frame');

		if (clear) {
			log_container.empty();
			// Offset for next updating
			update_log_offset = log ? log.size : false;

			log_prev_scroll = 0;
			log_allow_auto_scroll = true;
			frame.off('scroll');
			frame.on('scroll', function () {
				var scrollTop = frame.prop('scrollTop');
				var bottom_scroll_offset = frame.prop('scrollHeight') - frame.prop('clientHeight') - scrollTop;
				if (scrollTop < log_prev_scroll) {
					log_allow_auto_scroll = false;
				};
				if (bottom_scroll_offset <= 2) {
					log_allow_auto_scroll = true;
				}
				log_prev_scroll = scrollTop;
			});
		}

		// Remove loader
		log_container.find('>p.log-item:last-child span.sui-icon-loader').remove();

		if (log && log.items) {
			// Update log in modal
			var items = log.items;
			items.reverse().forEach(function (item) {
				var log_item = $('<p class="log-item"></p>');
				log_item.addClass('log-level-' + item.level);
				log_item.text(item.message);
				log_item.appendTo(log_container);
			});
		}

		if (show_loader && update_log_offset !== false) {
			// Show loader in the last log row
			log_container.find('>p.log-item:last-child')
				.append('<span class="sui-icon-loader sui-loading sui-md" aria-hidden="true"></span>');
		}

		// Autoscrolling only if scrollbar is already at the bottom
		if (log_allow_auto_scroll) {
			frame.animate({scrollTop: frame.prop('scrollHeight') - frame.prop('clientHeight')}, 500);
		}
	}

	function view_log_in_modal() {
		var backup_row = $(this).closest('table').find('.current-backup-row');
		var backup_id = backup_row.data('backupId');
		$(window).trigger('snapshot:open_log_modal', [backup_id]);
	}

	function goto_backups_settings() {
		$(window).trigger('snapshot:close_modal');
		toggle_navbar('settings');
		return false;
	}

	function restore_backup(e, backup_id) {
		e = e || false;
		if (e && e.preventDefault) e.preventDefault();

		var form = $('#form-snapshot-restore-backup');
		form.find('input[name=backup_id]').val(backup_id);
		form.find('input[name=restore_rootpath]').val(snapshot_default_restore_path.path);
		SUI.openModal('modal-snapshot-restore-backup', this);
	}

	function show_region_confirmation_modal() {
		call_if_can_delete_backup(show_region_confirmation_modal_force, function () {
			$('.snapshot-region-loading').show();
			$('.snapshot-region-radio').hide();
		}, function () {
			revert_storage_radio();
			$('.snapshot-region-loading').hide();
			$('.snapshot-region-radio').show();
		}).then(function () {
			revert_storage_radio();
		});
		return false;
	}

	function show_region_confirmation_modal_force() {
		SUI.openModal('modal-backups-region-change', this);

		// Remove and re-add listener to the cancel buttons.
		$('#modal-backups-region-change .cancel-region-change').off('click');
		$('#modal-backups-region-change .cancel-region-change').on('click', function() {
			// Lets cancel the change and close the modal.
			revert_storage_radio();
			$(window).trigger('snapshot:close_modal');
		});

		// Deal with the region change.
		$('#snapshot-backups-change-region').off('click');
		$('#snapshot-backups-change-region').on('click', function() {
			var data = {};

			data._wpnonce = $( '#_wpnonce-snapshot_change_region' ).val();
			data.no_backups = $( '.snapshot-backups-number .sui-summary-large' ).text() === '0' ? '1' : '0';
			data.new_region = $('input[name="snapshot-backup-region"]:checked').val();

			var url = ajaxurl + '?action=snapshot_change_region';

			$.ajax({
				type: 'POST',
				url: url,
				data: data,
				cache: false,
				dataType: 'json',
				beforeSend: function () {
					$('#modal-backups-region-change .sui-button').prop('disabled', true);
				},
				complete: function () {
					$('#modal-backups-region-change .sui-button').prop('disabled', false);
					$(window).trigger('snapshot:close_modal');
				},
				success: function (response) {
					if (response.success) {
						snapshot_list_backups();

						if ( true !== response.data.changed_schedule ) {
							// Show notice prompting to run backup or set schedule.
							var notice = $('<span></span>').html(snapshot_messages.change_region_no_schedule);
							var a = notice.find('a');
							a.eq(0).on('click', function (e) {
								notice.trigger('snapshot:close_notice');
								jQuery(window).trigger('snapshot:backup_modal');
							});
							a.eq(1).on('click', function (e) {
								notice.trigger('snapshot:close_notice');
								$('#snapshot-backup-schedule > a').trigger('click');
							});

							jQuery(window).trigger('snapshot:show_top_notice', ['info', notice]);
						} else {
							// Show notice informing that schedule is active already.
							var schedule = $('#snapshot-backup-schedule').data('modalData');
							var notice = $('<span></span>').html(snapshot_messages.change_region_with_schedule.replace('%s', schedule.frequency_human));
							jQuery(window).trigger('snapshot:show_top_notice', ['info', notice]);
						}
					} else {
						revert_storage_radio();

						var notice = $('<span></span>').html(snapshot_messages.change_region_failure);
						jQuery(window).trigger('snapshot:show_top_notice', ['error', notice]);
					}
				},
				error: function () {
					revert_storage_radio();

					var notice = $('<span></span>').html(snapshot_messages.change_region_failure);
					jQuery(window).trigger('snapshot:show_top_notice', ['error', notice]);
				}
			});

			return false;
		});

		return false;
	}

	function revert_storage_radio() {
		var selected_value = $('input[name="snapshot-backup-region"]:checked').val();
		if (selected_value === 'US') {
			unselected_value = 'EU';
		} else {
			unselected_value = 'US';
		}
		$("input[name='snapshot-backup-region'][value='"+unselected_value+"']").prop('checked', true);
		$("input[name='snapshot-backup-region'][value='"+selected_value+"']").prop('checked', false);
	}

	function delete_backups_confirm() {
		call_if_can_delete_backup(delete_backups_confirm_force, function () {
			$('#snapshot-settings-delete-backups-confirm').addClass('sui-button-onload');
		}, function () {
			$('#snapshot-settings-delete-backups-confirm').removeClass('sui-button-onload');
		});
		return false;
	}

	function delete_backups_confirm_force() {
		SUI.openModal('modal-settings-delete-backups', this);
		return false;
	}

	function delete_backups() {
		var data = {};

		data._wpnonce = $( '#_wpnonce-snapshot_delete_all_backups' ).val();

		var url = ajaxurl + '?action=snapshot_delete_all_backups';

		$.ajax({
			type: 'POST',
			url: url,
			data: data,
			cache: false,
			dataType: 'json',
			beforeSend: function () {
				$('#modal-settings-delete-backups .sui-button').prop('disabled', true);
			},
			complete: function () {
				$('#modal-settings-delete-backups .sui-button').prop('disabled', false);
				$(window).trigger('snapshot:close_modal');
				$.when(snapshot_list_backups()).done(function () {
					snapshot_backup_progress();
				});
			},
			success: function (data) {
				if (data.success) {
					jQuery(window).trigger('snapshot:show_top_notice', ['success', snapshot_messages.delete_all_backups_success, 3000, false]);
				} else {
					jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.delete_all_backups_error]);
				}
			},
			error: function () {
				jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.delete_all_backups_error]);
			}
		});

		return false;
	}

	function backup_is_cancelled() {
		$('.button-create-backup').prop('disabled', false);
		jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.cancel_backup_success, 3000, false]);

		var cancelModal = $( '#snapshot-modal-cancel-backup' ).parent();
		cancelModal.find('.sui-button').prop('disabled', false);
		$('#snapshot-modal-cancel-backup #snapshot-cancel-backup').removeClass('sui-button-onload-text');
		if ( cancelModal.hasClass( 'sui-active' ) ) {
			$(window).trigger('snapshot:close_modal');
		}
		snapshot_list_backups();
	}

	function recheck_requirements() {
		var data = {};
		data._wpnonce = $( '#_wpnonce-snapshot_recheck_requirements' ).val();

        $.ajax({
            type: 'POST',
            url: ajaxurl + '?action=snapshot-recheck_requirements',
            data: data,
            beforeSend: function () {
				$('#modal-snapshot-requirements-check-failure .snapshot-recheck-requirements').addClass('sui-button-onload-text', true);
            },
			complete: function () {
				$('#modal-snapshot-requirements-check-failure .snapshot-recheck-requirements').removeClass('sui-button-onload-text', true);
			},
            success: function (response) {
                if (response.success) {
					$('#snapshot-php-version').val(response.data.compat_php_version);
					if (response.data.compat_php_version >= 0) {
						$(window).trigger('snapshot:close_modal');
						SUI.openModal('modal-snapshot-requirements-check-success', 'button-create-backup');
					}
                }
            }
        });
	}

	function populate_snapshot_region() {
		var data = {};
		data._wpnonce = $( '#_wpnonce-populate_snapshot_region' ).val();

        $.ajax({
            type: 'POST',
            url: ajaxurl + '?action=snapshot-check_creds',
            data: data,
			complete: function () {
				$('.snapshot-region-loading').hide();
				$('.snapshot-storage-limit-loading').hide();
				$('.snapshot-region-radio').show();
				$('.snapshot-storage-limit-input').show();
			},
            success: function (response) {
                if (response.success) {
					if (response.data.region === 'US') {
						$("#backup-region-us").prop("checked", true);
						$("#backup-region-eu").prop("checked", false);
					}
					if (response.data.region === 'EU') {
						$("#backup-region-eu").prop("checked", true);
						$("#backup-region-us").prop("checked", false);
					}

					if (response.data.rotation_frequency) {
						$('#snapshot-backup-limit').val(response.data.rotation_frequency);
						$('#existing_backup_limit').val(response.data.rotation_frequency);
					}
                }
            }
        });
	}

	function requirements_passed() {
		$(window).trigger('snapshot:close_modal');
		jQuery(window).trigger('snapshot:backup_modal');
	}

	function toggle_notification_recipients() {
		var checkbox = $(this);
		var content = checkbox.closest('.sui-form-field').find('.sui-toggle-content');
		checkbox.prop('checked') ? content.show() : content.hide();
	}

	function handle_notifications_settings(e) {
		if (e && e.preventDefault) e.preventDefault();

		var form = $(this);
		var data = {};
		form.serializeArray().forEach(function (item) {
			data[item.name] = item.value;
		});
		data.action = 'save_snapshot_settings';
		var recipients = [];
		form.find('#snapshot-notification-recipients .sui-recipients .sui-recipient').each(function () {
			var recipient = $(this);
			recipients.push({
				name: recipient.find('.sui-recipient-name').text(),
				email: recipient.find('.sui-recipient-email').text()
			});
		});
		data.email_settings = {
			on_fail_send: form.find('#snapshot-notifications-send-email').prop('checked'),
			on_fail_recipients: recipients,
			notify_on_fail: form.find('#snapshot-backup-fails').prop('checked'),
			notify_on_complete: form.find('#snapshot-backup-completes').prop('checked')
		};
		data.email_settings = JSON.stringify(data.email_settings);

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: data,
			beforeSend: function () {
				form.find('.sui-button').addClass('sui-button-onload-text').prop('disabled', true);
			},
			complete: function () {
				form.find('.sui-button').removeClass('sui-button-onload-text').prop('disabled', false);
			},
			success: function (response) {
				if ( response.success ) {
					var notice = form.find('.sui-notice.email-notification-notice');
					notice.find('.sui-notice-message p').text(response.data.notice_text);

					form.find('.sui-recipients .sui-recipient').remove();
					response.data.email_settings.on_fail_recipients.forEach(function (item) {
						add_recipient_html(item.name, item.email);
					});
					form.find('#snapshot-backup-fails').prop('checked', response.data.email_settings.notify_on_fail);
					form.find('#snapshot-backup-completes').prop('checked', response.data.email_settings.notify_on_complete);

					if (response.data.notice_type === 'success') {
						notice.addClass('sui-notice-success');
					} else {
						notice.removeClass('sui-notice-success');
						var checkbox = $('#snapshot-notifications-send-email');
						checkbox.prop('checked', response.data.email_settings.on_fail_send);
						var content = checkbox.closest('.sui-form-field').find('.sui-toggle-content');
						response.data.email_settings.on_fail_send ? content.show() : content.hide();
						form.find('.email-notification-notice-empty').hide();
					}
					jQuery(window).trigger('snapshot:show_top_notice', ['success', response.data.top_notice_text, 3000, false]);
				}
			}
		});
	}

	function remove_recipient(e) {
		if (e && e.preventDefault) e.preventDefault();
		var recipient = $(this).closest('.sui-recipient');
		var recipients = recipient.closest('.sui-recipients');
		var notice = recipients.find('.email-notification-notice-empty');
		recipient.remove();
		if (!recipients.find('.sui-recipient').length) {
			notice.show();
		}
	}

	function add_recipient_modal_hide_errors() {
		$('#modal-notification-add-recipient-input-email-error').hide()
			.closest('.sui-form-field').removeClass('sui-form-field-error');
		$('#modal-notification-add-recipient-input-email-duplicate-error').hide();
	}

	function add_recipient(e) {
		if (e && e.preventDefault) e.preventDefault();
		$('#modal-notification-add-recipient input:not([type=hidden])').val('');

		add_recipient_modal_hide_errors();

		SUI.openModal('modal-notification-add-recipient', this, 'modal-notification-add-recipient-input-name');
	}

	function add_recipient_html(name, email) {
		name = name.trim();
		email = email.trim();

		var recipients = $('.snapshot-notifications #snapshot-notification-recipients .sui-recipients');

		var recipient = $('<div class="sui-recipient"></div>');
		$('<div class="sui-recipient-name"></div>').text(name).appendTo(recipient);
		$('<div class="sui-recipient-email"></div>').text(email).appendTo(recipient);
		$('<button type="button" class="sui-button-icon snapshot-remove-recipient"><span class="sui-icon-trash" aria-hidden="true"></span></button>').appendTo(recipient);
		recipient.appendTo(recipients);
		recipients.find('.email-notification-notice-empty').hide();

		return name !== '' ? name : email;
	}

	function handle_add_recipient_form(e) {
		if (e && e.preventDefault) e.preventDefault();

		var error_element = $('#modal-notification-add-recipient-input-email-error');
		var error_element2 = $('#modal-notification-add-recipient-input-email-duplicate-error');

		var form = $(this);
		var data = {};
		form.serializeArray().forEach(function (item) {
			data[item.name] = item.value;
		});

		var recipients = $('.snapshot-notifications #snapshot-notification-recipients .sui-recipients');
		error_element2.hide();
		var has_error = false;
		recipients.find('.sui-recipient').each(function () {
			var recipient = $(this);
			if (recipient.find('.sui-recipient-email').text() === data.email.trim()) {
				error_element2.show();
				error_element2.closest('.sui-form-field').addClass('sui-form-field-error');
				has_error = true;
				return false;
			}
		});
		if (has_error) {
			return;
		}

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'snapshot-json_validate_email',
				_wpnonce: data._wpnonce_snapshot_validate_email,
				email: data.email
			},
			beforeSend: function () {
				form.find('.sui-button').addClass('sui-button-onload-text').prop('disabled', true);
			},
			complete: function () {
				form.find('.sui-button').removeClass('sui-button-onload-text').prop('disabled', false);
			},
			success: function (response) {
				if ( response.success && response.data.is_valid ) {
					var added = add_recipient_html(data.name, data.email);
					SUI.closeModal();
					add_recipient_modal_hide_errors();
					if (added !== false) {
						jQuery(window).trigger('snapshot:show_top_notice', [
							'info',
							snapshot_messages.notifications_user_added.replace('%s', added)
						]);
					}
				} else {
					error_element.show();
					error_element.closest('.sui-form-field').addClass('sui-form-field-error');
				}
			}
		});
	}

	function history_replace_state() {
		if (window.history && window.history.replaceState) {
			window.history.replaceState('', document.title, window.location.pathname + window.location.search);
		}
	}

	/**
	 * Check if current user can delete backups
	 */
	function check_can_delete_backup() {
		var deferred = $.Deferred();

		var request_data = {
			action: 'snapshot-check_can_delete_backup',
			_wpnonce: $('#_wpnonce-check_can_delete_backup').val()
		};

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: request_data,
			cache: false,
			success: function (response) {
				if (response.success && response.data.can_delete_backup === true) {
					deferred.resolve();
				} else {
					deferred.reject();
				}
			},
			error: function () {
				deferred.reject();
			}
		});

		return deferred.promise();
	}

	/**
	 * Show Confirm WPMU DEV password modal
	 * @param {function} callback
	 */
	function confirm_wpmudev_password(callback) {
		var deferred = $.Deferred();
		$('#snapshot-confirm-wpmudev-password-modal').data('on_success', [deferred, callback]);
		$('#error-snapshot-wpmudev-password').hide();
		$('#error-snapshot-wpmudev-password').closest('.sui-form-field').removeClass('sui-form-field-error');
		$('#snapshot-wpmudev-password').val('');
		SUI.openModal('snapshot-confirm-wpmudev-password-modal', this);
		return deferred.promise();
	}

	/**
	 * Check WPMU DEV password
	 * @param {string} wpmudev_password
	 */
	function validate_wpmudev_password(wpmudev_password) {
		var deferred = $.Deferred();

		var request_data = {
			action: 'snapshot-check_wpmudev_password',
			_wpnonce: $('#_wpnonce-check_wpmudev_password').val(),
			wpmudev_password: wpmudev_password
		};

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: request_data,
			cache: false,
			success: function (response) {
				if (response.success && response.data.password_is_valid === true) {
					deferred.resolve();
				} else {
					deferred.reject();
				}
			},
			error: function () {
				deferred.reject();
			}
		});

		return deferred.promise();
	}

	/**
	 * Confirm WPMU DEV password modal: form submit handler
	 */
	function on_submit_wpmudev_password() {
		var form = $('#snapshot-confirm-wpmudev-password-modal-form');
		var password = form.find('[name=wpmudev_password]').val();

		var on_callback = $('#snapshot-confirm-wpmudev-password-modal').data('on_success');
		var deferred = on_callback[0];
		var callback = on_callback[1];

		var button = form.find('.submit-button');
		button.addClass('sui-button-onload');
		validate_wpmudev_password(password).then(function () {
			SUI.closeModal();
			if (typeof callback === 'function') {
				var result = callback();
				deferred.resolve(result);
			}
			$('#error-snapshot-wpmudev-password').hide();
			$('#error-snapshot-wpmudev-password').closest('.sui-form-field').removeClass('sui-form-field-error');
		}, function () {
			//deferred.reject();
			$('#error-snapshot-wpmudev-password').show();
			$('#error-snapshot-wpmudev-password').closest('.sui-form-field').addClass('sui-form-field-error');
			$('#snapshot-wpmudev-password').focus();
		}).always(function () {
			button.removeClass('sui-button-onload');
		});

		return false;
	}

	/**
	 * Run callback function and ask for WPMU DEV password if needed
	 * @param {function} callback
	 */
	function call_if_can_delete_backup(callback, before, after) {
		var deferred = $.Deferred();

		if (typeof before === 'function') {
			before();
		}

		check_can_delete_backup().then(function () {
			if (typeof after === 'function') {
				after();
			}
			var result = callback();
			deferred.resolve(result);
		}, function () {
			if (typeof after === 'function') {
				after();
			}
			confirm_wpmudev_password(callback).then(function (data) {
				deferred.resolve(data);
			}, function () {
				deferred.reject();
			});
		});

		return deferred.promise();
	}

	/**
	 * Change the storage limit in the API.
	 */
	function change_storage_limit() {
		$('#error-snapshot-backup-limit').hide();
		$('#error-snapshot-backup-limit').html("");
		$('.snapshot-storage-limit-input .sui-form-field').removeClass('sui-form-field-error');

		var request_data = {
			action: 'snapshot_change_storage_limit',
			_wpnonce: $('#_wpnonce-snapshot_change_storage_limit').val(),
			storage_limit: $('#snapshot-backup-limit').val()
		};

		if (! $.isNumeric(request_data.storage_limit) || request_data.storage_limit < 1 || request_data.storage_limit > 30) {
			$('#error-snapshot-backup-limit').show();
			$('#error-snapshot-backup-limit').html(snapshot_messages.storage_limit_invalid);
			$('.snapshot-storage-limit-input .sui-form-field').addClass('sui-form-field-error');
			$('#snapshot-backup-limit').trigger('focus');

			return false;
		}

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: request_data,
			beforeSend: function () {
				$('#snapshot-backup-limit-button').addClass('sui-button-onload');
			},
			complete: function () {
				$('#snapshot-backup-limit-button').removeClass('sui-button-onload');
			},
			success: function (response) {
				if (response.success && response.data.changed_storage) {
					$('#snapshot-backup-limit').trigger('focus');
					jQuery(window).trigger('snapshot:show_top_notice', ['success', snapshot_messages.storage_limit_success]);
				} else {
					$('#snapshot-backup-limit').trigger('focus');
					jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.storage_limit_failure]);
				}
			},
			error: function () {
				$('#snapshot-backup-limit').trigger('focus');
				jQuery(window).trigger('snapshot:show_top_notice', ['error', snapshot_messages.storage_limit_failure]);
			}
		});

		return false;
	}

	$(window).on('load', function (e) {
		var matches;
		if ('#set-schedule' === window.location.hash) {
			snapshot_get_schedule(true, $('#snapshot-backup-schedule').data('modalData'));
			history_replace_state();
		} else if ('#logs' === window.location.hash) {
			// Open backup logs tab from URL.
			toggle_navbar('logs');
			history_replace_state();
		} else if ('#settings' === window.location.hash) {
			// Open backup logs tab from URL.
			toggle_navbar('settings');
			history_replace_state();
		} else if (matches = window.location.hash.match(/^#logs\-(.+)/)) {
			// Open backup log from URL.
			view_log(e, true, matches[1]);
			history_replace_state();
		} else if ('#notifications' === window.location.hash) {
			// Open notifications tab from URL.
			toggle_navbar('notifications');
			history_replace_state();
		}
	});

    $(function () {
        if ( $( '.snapshot-page-backups' ).length ) {
			for (var current_tab in navbar_tabs) {
				var vertical_selector = navbar_tabs[current_tab][1];
				$('.snapshot-page-main').find(vertical_selector)
					.on('click', toggle_navbar.bind(this, current_tab));
			}
			$('.snapshot-page-main .sui-mobile-nav').on('change', function () {
				toggle_navbar($(this).val());
			});

			$('#snapshot-backup-schedule > a').on('click', function () {
				snapshot_get_schedule(true, $(this).parent().data('modalData'));
				return false;
            });

			$(window).on('snapshot:get-schedule', function () {
				snapshot_get_schedule(false);
            });

            snapshot_get_schedule(false);

            $('#form-snapshot-create-manual-backup').on('submit', handle_create_manual_backup);

			$('.snapshot-list-backups').on('click', '.open-edit-schedule', function () {
				var data = $(this).data('modal-data');
				$(window).trigger('snapshot:schedule', [data.schedule_action, data.values, data.schedule_action]);
			});

            $(window).on('snapshot:delete_backup', delete_backup);
            $('#snapshot-delete-backup-button').on('click', delete_backup_confirmed);
            $(window).on('snapshot:export_backup', export_backup);
            $(window).on('snapshot:restore_backup', restore_backup);

            var Promise = $.when(snapshot_list_backups());
            Promise.done(function () {
                snapshot_backup_progress('1');

                var matches;
                if (matches = window.location.hash.match(/^#backups\-(.+)/)) {
                    // Open backup from URL
                    setTimeout(function () {
                        view_backup(matches[1]);
                    }, 100);
                    history_replace_state();
                }
            });

            $('#button-reload-backups').on('click', function () {
                $.when(snapshot_list_backups()).done(function () {
                    snapshot_backup_progress();
                });
            });

            $('.snapshot-list-backups').on('click', '.button-cancel-backup', cancel_backup_confirm);
            $('#snapshot-cancel-backup').on('click', cancel_backup);
            $('.snapshot-list-backups').on('click', '.button-view-log', view_log_in_modal);

            $('.logs-list').on('click', '.view-backup', function () {
                view_backup($(this).data('backupId'));
            });

            $('.logs-list .log-row').on('click', on_log_row_click);

            $(window).on('snapshot:open_log_modal', open_log_modal);
            $(window).on('snapshot:view_log', view_log);

            $('#snapshot-button-backups-settings').on('click', goto_backups_settings);

            // For restoration modal log
            $(window).on('snapshot:update_log_ajax', function (event, backup_id, offset, clear, show_loader) {
                update_log_ajax(backup_id, offset, clear, show_loader);
            });
            $(window).on('snapshot:update_log', function (event, log, clear, show_loader) {
                update_log(log, clear, show_loader);
            });
            $(window).on('snapshot:update_log_offset', function (event, value) {
                update_log_offset = value;
            });
            $(window).on('snapshot:get_log_offset', function (event, callback) {
                callback(update_log_offset);
			});

			// Show confirmation modal, when user changes the region in the settings.
			$('#wps-settings input[name=snapshot-backup-region]').on('change', show_region_confirmation_modal);

			$('#snapshot-region-notice').show();

            $('#snapshot-settings-delete-backups-confirm').on('click', delete_backups_confirm);
			$('#snapshot-settings-delete-backups').on('click', delete_backups);

			// Recheck Snapshot requirements (PHP version, etc.) when user clicks the recheck button.
			$('#modal-snapshot-requirements-check-failure .snapshot-recheck-requirements').on('click', recheck_requirements);

			// Continue with the Create Backup modal, since we're covering all requirements.
			$('#modal-snapshot-requirements-check-success .snapshot-checked-requirements').on('click', requirements_passed);

			$('.snapshot-notifications input[aria-controls=snapshot-notification-recipients]').on('change', toggle_notification_recipients);
			$('.snapshot-notifications #wps-notifications').on('submit', handle_notifications_settings);
			$('.snapshot-notifications').on('click', '.snapshot-remove-recipient', remove_recipient);
			$('.snapshot-notifications .snapshot-add-recipient').on('click', add_recipient);
			$('#modal-notification-add-recipient-form').on('submit', handle_add_recipient_form);

			$('#snapshot-default-exclusions').on('change', function() {
				if(this.checked) {
					$('.snapshot-exclusions-settings-box .sui-accordion-item-body .sui-box-body').removeClass('snapshot-disabled-exclusions');
				} else {
					$('.snapshot-exclusions-settings-box .sui-accordion-item-body .sui-box-body').addClass('snapshot-disabled-exclusions');
				}

			});

			populate_snapshot_region();

			$('#snapshot-confirm-wpmudev-password-modal-form').on('submit', on_submit_wpmudev_password);

			$('#snapshot-backup-limit').on('focusin', function () {
				$('#snapshot-backup-limit-button').prop('disabled', false);
			}).on('focusout', function () {
				if (! $('#snapshot-backup-limit-button:active').length && ! $('#snapshot-backup-limit-button:hover').length) {
					$('#snapshot-backup-limit-button').prop('disabled', true);

				}
			});

			$('#snapshot-backup-limit-button').on('click', change_storage_limit);
        }
    });
})(jQuery);
