@extends('admin.admin_master')
@section('admin')

@php
	// Page meta shared with common include (icon used in header)
	$pageTitle = $pageTitle ?? 'Business Register';
	$pageIcon = $pageIcon ?? 'fas fa-building';
@endphp

@include('components.common_js', [
	'wrapCard' => true,
	'icon' => $pageIcon,
	'iconClass' => 'fa-solid fa-building',
	'iconIsFa' => true,
	'title' => $pageTitle,
	'refreshId' => 'btnGridRefresh',
	'reloadIconId' => 'reloadIcon',
	'gridId' => 'businessGrid',
	'bodyHeight' => '500px',
	'showUserAvatar' => false
])

<script>
	(function(){
		'use strict';

		document.addEventListener('DOMContentLoaded', function(){
			// Ensure required libraries are present (set up by common_js include)
			if (!(window.AppUtils && window.AppUtils.librariesReady)) {
				return;
			}

			// Business endpoints
			const listUrl = @json(route('list.businesses'));
			const usersListUrl = @json(route('business.users.list'));
			const addUrl = @json(route('add.business'));
            const updateUrl = @json(route('update.business'));
            const removeUrl = @json(route('delete.business'));
            const setActiveUrl = @json(route('setactive.business'));
			const setLockedUrl = @json(route('setlocked.business'));
			const purgeUrl = @json(route('purge.business.data'));
			const termsGetUrl = @json(route('business.terms.get'));
			const termsUpdateUrl = @json(route('business.terms.update'));

			// Date fields to format consistently in exports and UI
			const DATE_FIELDS = ['created_at', 'updated_at'];

			// Detect small screens to fine-tune column chooser visibility
			const isSmallScreen = (typeof window !== 'undefined') && window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches;
			const showColumnChooser = !isSmallScreen;

			// Reuse page title from Blade in JS
			const PAGE_TITLE = @json($pageTitle);

			// Placeholder image URL for missing logos (declare early for use in templates)
			const NO_IMAGE = @json(url('upload/no_image.jpg'));

			// Use shared helpers from AppUtils (defined in common_js)
			const BASE_URL = @json(url('/'));

			// Support stacking multiple Bootstrap modals: ensure newest modal/backdrop is on top
			(function enableModalStacking(){
				var baseZ = 1055;
				document.addEventListener('show.bs.modal', function (evt) {
					var openCount = document.querySelectorAll('.modal.show').length;
					var z = baseZ + (10 * openCount);
					var modal = evt.target;
					if (modal && modal.classList.contains('modal')) {
						modal.style.zIndex = z;
						setTimeout(function(){
							var backs = document.querySelectorAll('.modal-backdrop:not(.modal-stack)');
							var bd = backs[backs.length - 1];
							if (bd) { bd.style.zIndex = (z - 5); bd.classList.add('modal-stack'); }
						}, 0);
					}
				});
				document.addEventListener('hidden.bs.modal', function(){
					var visible = document.querySelectorAll('.modal.show').length;
					var stacks = document.querySelectorAll('.modal-backdrop.modal-stack');
					for (var i=stacks.length - 1; i>=visible; i--) {
						var el = stacks[i];
						if (el && el.parentNode) el.parentNode.removeChild(el);
					}
				});
			})();

			// Build Syncfusion Grid (Businesses)
			// Cache to avoid repeated user fetch per business
			const BusinessUsersCache = Object.create(null);

			function renderUserPills(arr, businessId){
				if (!arr || arr.length === 0) return '<div class="text-muted">-</div>';
				// Filter to only users belonging to this business when company_id is present
				try {
					if (businessId != null) {
						var cid = String(businessId);
						arr = (arr || []).filter(function(u){
							return (u && (u.company_id !== undefined && u.company_id !== null)) ? (String(u.company_id) === cid) : true;
						});
					}
				} catch(_) {}
				function roleBadge(role){
					var r = (role ? String(role) : '').trim().toLowerCase();
					var label = 'USER', cls = 'bg-secondary';
                    var style = 'font-size:.55rem;line-height:1;';
					if (r === 'admin') { label = 'ADMIN'; cls = 'bg-success'; }
					else if (r === 'super admin' || r === 'super_admin' || r === 'superadmin') { label = 'SUPER ADMIN'; cls = 'bg-danger'; }
					else if (r === 'waiter') { label = 'WAITER'; cls = 'bg-info'; }
					else if (r === 'chef') { label = 'CHEF'; cls = 'bg-warning text-white'; style += 'background-color:#fc8019!important;color:#fff!important;'; }
					return '<span class="badge '+ cls +' text-uppercase ms-2" style="' + style + '">' + label + '</span>';
				}
				function bool(v){ return (v === true || v === 1 || v === '1'); }
				var html = arr.map(function(u){
					var name = (window.AppUtils && AppUtils.escapeHtml) ? AppUtils.escapeHtml(String(u && u.name ? u.name : '')) : String(u && u.name ? u.name : '');
					var role = u && u.role ? u.role : 'user';
					var active = bool(u && (u.status !== undefined ? u.status : u.is_active));
					var locked = bool(u && u.is_locked);
					// Only show an Inactive badge when status is 0; no tick for active
					var inactiveBadge = '';
					if (!active) {
						inactiveBadge = '<span class="ms-2" style="background-color:#fee2e2;color:#991b1b;padding:1px 6px;border-radius:4px;font-size:10px;line-height:14px;display:inline-flex;align-items:center;">Inactive</span>';
					}
					// Match user grid style: show lock icon when locked; no unlock icon otherwise
					var lockIcon = locked ? '<i data-feather="lock" class="text-warning ms-1" style="width:14px;height:14px;"></i>' : '';
					// No box/border: plain inline pill with spacing only
					return '<span class="d-inline-flex align-items-center me-3 mb-1">'
						+ name + roleBadge(role) + inactiveBadge + lockIcon + '</span>';
				}).join('');
				return '<div class="d-flex flex-wrap align-items-start">' + html + '</div>';
			}

			function renderBulletLines(text){
				var s = (text == null) ? '' : String(text);
				s = s.replace(/\r\n/g, '\n').replace(/\r/g, '\n').trim();
				if (!s) return '<div class="text-muted">-</div>';
				var esc = (window.AppUtils && AppUtils.escapeHtml)
					? function(v){ return AppUtils.escapeHtml(String(v || '')); }
					: function(v){ return String(v || ''); };
				var lines = s.split('\n').map(function(l){ return String(l || '').trim(); }).filter(function(l){ return l !== ''; });
				if (lines.length === 0) return '<div class="text-muted">-</div>';
				var items = lines.map(function(l){
					return '<li style="margin-bottom:2px;">' + esc(l) + '</li>';
				}).join('');
				return '<ul class="m-0 ps-3" style="font-size:12px;line-height:1.25;">' + items + '</ul>';
			}

			function populateUsersForCell(container, businessId){
				if (!container || !businessId) return;
				if (BusinessUsersCache[businessId]) {
					container.innerHTML = renderUserPills(BusinessUsersCache[businessId], businessId);
					if (window.feather) { try { feather.replace(); } catch(_){} }
					return;
				}
				container.innerHTML = '<span class="text-muted">Loading…</span>';
				// Fetch users filtered by company_id from dedicated endpoint
				fetch(usersListUrl + '?company_id=' + encodeURIComponent(businessId))
					.then(r => r.json())
					.then(function(json){
						var arr = Array.isArray(json) ? json : [];
						BusinessUsersCache[businessId] = arr;
						container.innerHTML = renderUserPills(arr, businessId);
						if (window.feather) { try { feather.replace(); } catch(_){} }
					})
					.catch(function(){ container.innerHTML = '<div class="text-muted">-</div>'; });
			}

			let grid = new ej.grids.Grid(Object.assign(
				AppUtils.GridHelpers.baseGridOptions({
					// Hide column chooser on very small screens
					showColumnChooser: showColumnChooser
				}),
				{
					columns: [
						{ field:'image', headerText: '', width: 62, minWidth: 60, textAlign: 'Center', allowSorting: false, allowFiltering: false,
                            showInColumnChooser: false,
                            exportExclude: true,
                        template: function(data){
                            var src = (window.AppUtils && AppUtils.computeImageUrl) ? AppUtils.computeImageUrl(data, BASE_URL, NO_IMAGE) : NO_IMAGE;
                            var safeSrc = (window.AppUtils && AppUtils.escapeHtml) ? AppUtils.escapeHtml(src) : src;
                            var fallback = (window.AppUtils && AppUtils.escapeHtml) ? AppUtils.escapeHtml(NO_IMAGE) : NO_IMAGE;
                            return '<div class="d-flex justify-content-center align-items-center">'
                                + '<img src="' + safeSrc + '" alt="business-logo" class="grid-avatar rounded" style="width:40px;height:40px;object-fit:cover;" onerror="this.onerror=null;this.src=\'' + fallback + '\';" />'
                                + '</div>';
                        }
                        },
						{ field: 'name', headerText: 'Business Name', minWidth: 200, width: 300,
							template: function(data){
								var name = (window.AppUtils && AppUtils.escapeHtml) ? AppUtils.escapeHtml(data && data.name ? data.name : '') : (data && data.name ? data.name : '');
								var isActive = data && (data.is_active === true || data.is_active === 1 || data.is_active === '1');
                                var isLocked = data && (data.is_locked === true || data.is_locked === 1 || data.is_locked === '1');
								var inactivePill = '';
								if (!isActive) {
									inactivePill = '<span style="'
										+ 'background-color:#fee2e2;'
										+ 'color:#991b1b;'
										+ 'padding:2px 8px;'
										+ 'border-radius:5px;'
										+ 'font-size:10px;'
										+ 'font-weight:500;'
										+ 'margin-left:8px;'
										+ 'height:16px;'
										+ 'line-height:16px;'
										+ 'display:inline-flex;'
										+ 'align-items:center;'
										+ '">Inactive</span>';
								}

								var lockIcon = '';
								if (isLocked) {
									lockIcon = '<i data-feather="lock" class="ms-2 text-warning" style="width:14px;height:14px;vertical-align:middle;" title="Locked"></i>';
								}

								// Category name (shown in muted text below business name - larger, italic, bold)

                                var code = (data && data.company_code) ? ((window.AppUtils && AppUtils.escapeHtml) ? AppUtils.escapeHtml(String(data.company_code)) : String(data.company_code)) : '';
							var approved = (data && data.approved_users != null && data.approved_users !== '') ? parseInt(data.approved_users, 10) : 0;
							var used = (data && data.used_users != null && data.used_users !== '') ? parseInt(data.used_users, 10) : 0; // default 0 if not provided

							// Click-to-copy for code badge
							var codeBadge = '';
							if (code) {
								var copyHandler = "(function(c){navigator.clipboard&&navigator.clipboard.writeText(c).then(function(){try{AppUtils.notify('Business code copied',{type:'info'})}catch(_){}})})('" + code + "')";
								codeBadge = '<span class="badge rounded-pill d-print-none" style="border:1px solid #3b82f6;color:#1d4ed8;background-color:#eff6ff;padding:4px 10px;font-weight:700;cursor:pointer;user-select:none;" title="Click to copy" onclick="' + copyHandler + '">' + code + '</span>';
							}

							// Quota badge with dynamic color
							var remaining = (approved > 0 ? (approved - used) : 0);
							var qBg = '#e5e7eb', qColor = '#374151'; // gray default
							if (used > 0) {
								if (approved > 0 && used >= approved) { qBg = '#fee2e2'; qColor = '#991b1b'; } // red
								else if (approved > 0 && remaining <= 2) { qBg = '#ffedd5'; qColor = '#9a3412'; } // orange
								else { qBg = '#dcfce7'; qColor = '#166534'; } // green
							}
							var quotaBadge = '<span class="badge rounded-pill d-print-none" style="background-color:'+qBg+';color:'+qColor+';padding:4px 10px;font-weight:700;">' + used + '/' + approved + '</span>';

							var badgesRow = '<div class="d-flex align-items-center gap-2 mt-1 d-print-none">' + codeBadge + quotaBadge + '</div>';

							var nameRow = '<div class="d-flex align-items-center flex-wrap gap-1 d-print-none">' + name + inactivePill + lockIcon + '</div>';

							// Print-only: show plain business name (visible only when printing)
							var printName = '<span class="d-none d-print-inline">' + name + '</span>';

							return '<div class="flex-column">'
								+ printName
								+ nameRow
								+ badgesRow
							+ '</div>';
							},
						},
						{ field: 'company_code', headerText: 'Code', width: 140, textAlign: 'Center', visible: false },
						{ field: 'is_active', headerText: 'Active', width: 100, textAlign: 'Center', visible: false, showInColumnChooser: showColumnChooser,
							template: function(data){
								var active = data && (data.is_active === true || data.is_active === 1 || data.is_active === '1');
								if (active) {
									return '<i data-feather="check-square" class="text-success d-print-none" style="width:16px;height:16px;"></i>';
								}
								return ''+
									'<span class="d-none d-print-inline text-danger">Inactive</span>'+
									'<i data-feather="x-square" class="text-danger d-print-none" style="width:16px;height:16px;"></i>';
							}
						},
						{ field: 'is_locked', headerText: 'Locked', width: 100, textAlign: 'Center', visible: false, showInColumnChooser: showColumnChooser,
							template: function(data){
								var locked = data && (data.is_locked === true || data.is_locked === 1 || data.is_locked === '1');
								if (locked) {
									return ''+
										'<span class="d-none d-print-inline text-warning" style="color:#f59e0b">Locked</span>'+
										'<i data-feather="lock" class="text-warning d-print-none" style="width:16px;height:16px;"></i>';
								}
								return '';
							}
						},
						{ field: 'email', headerText: 'Email', minWidth: 200, width: 220 },
						{ field: 'mobile', headerText: 'Mobile', width: 150, visible: false },
						{ field: 'approved_users', headerText: 'Approved Users', width: 160, textAlign: 'Center', visible: false },
						{ field: 'gstin', headerText: 'GSTIN', width: 160, visible: false },
						{ field: 'pan', headerText: 'PAN', width: 140, visible: false },
						{ field: 'dine_in_policies', headerText: 'Dine-in Policies', minWidth: 220, width: 260, visible: false, showInColumnChooser: showColumnChooser,
							customAttributes: { class: 'terms-wrap-cell' },
							template: function(data){ return renderBulletLines(data && data.dine_in_policies); }
						},
						{ field: 'delivery_terms', headerText: 'Delivery Terms', minWidth: 220, width: 260, visible: false, showInColumnChooser: showColumnChooser,
							customAttributes: { class: 'terms-wrap-cell' },
							template: function(data){ return renderBulletLines(data && data.delivery_terms); }
						},
						{ field: 'address', headerText: 'Address', type: 'string', minWidth: 240, width: 300,
							template: function(data){
								var msg = (window.AppUtils ? AppUtils.escapeHtml(data && data.address ? data.address : '') : (data && data.address ? data.address : ''));
								return '<div class="line-clamp-2" title="' + msg + '">' + msg + '</div>';
							}
						},
                        // New: Inline users list for this business (name + role + active/lock icons)
						{ field: 'users', headerText: 'Users', minWidth: 260, width: 220, allowSorting: false, allowFiltering: false,
							template: function(data){
								try {
									var businessId = data && data.id ? String(data.id) : '';
									var arr = [];
									if (Array.isArray(data && data.users)) { arr = data.users; }
									else if (typeof (data && data.users) === 'string') {
										try { var parsed = JSON.parse(data.users); if (Array.isArray(parsed)) arr = parsed; } catch(_){}
									} else if (Array.isArray(data && data.user_list)) { arr = data.user_list; }

									// If users already present, render immediately; otherwise return a mount that will be populated async
									if (arr && arr.length > 0) {
										return renderUserPills(arr, businessId);
									}
									var mountId = 'users-mount-' + businessId;
									// Initial placeholder; populated in dataBound/rowDataBound
									return '<div id="'+ mountId +'" class="business-users" data-business-id="'+ businessId +'">-</div>';
								} catch (e) {
									return '<div class="text-muted">-</div>';
								}
							}
						},
                        { field: 'created_at', headerText: 'Created At', width: 200, type: 'datetime',
                            format: { type: 'datetime', format: 'dd-MMM-yyyy hh:mm a' },
                            showInColumnChooser: showColumnChooser,
                            customAttributes: { class: 'hide-on-small-screen' }
                        },
                        { field: 'updated_at', headerText: 'Updated At', width: 200, visible: false, type: 'datetime',
                            format: { type: 'datetime', format: 'dd-MMM-yyyy hh:mm a' },
                            showInColumnChooser: showColumnChooser,
                            customAttributes: { class: 'hide-on-small-screen' }
                        },
						{ 
							headerText: 'Manage Records', width: 220, textAlign: 'Center', showInColumnChooser: false,
							commands: [
								{ type: 'None', title: 'View', buttonOption: { iconCss: 'e-icons e-eye', cssClass: 'e-flat e-fleet cmd-view' } },
								{ type: 'None', title: 'Modify', buttonOption: { iconCss: 'e-icons e-edit', cssClass: 'e-flat e-fleet cmd-edit' } },
								{ type: 'None', title: 'Remove', buttonOption: { iconCss: 'e-icons e-delete', cssClass: 'e-flat e-fleet cmd-delete' } },
                                { type: 'None', title: 'Inactive Record', buttonOption: { iconCss: 'e-icons e-circle-close', cssClass: 'e-close e-flat e-fleet cmd-inactive' } },
								{ type: 'None', title: 'Activate Record', buttonOption: { iconCss: 'e-icons e-circle-check', cssClass: 'e-open e-flat e-fleet cmd-activate' } },
								{ type: 'None', title: 'Lock', buttonOption: { iconCss: 'e-icons e-lock', cssClass: 'e-flat e-fleet text-warning cmd-lock' } },
								{ type: 'None', title: 'Unlock', buttonOption: { iconCss: 'e-icons e-unlock', cssClass: 'e-flat e-fleet text-warning cmd-unlock' } },
								{ type: 'None', title: 'Clear Data', buttonOption: { iconCss: 'e-icons e-redact', cssClass: 'e-flat e-fleet text-danger cmd-purge', content: '' } },
							]
						},
					],
                    commandClick: function(args){
                        try {
                            var target = args.target || (args.originalEvent ? args.originalEvent.target : null);
                            var row = (args.rowData || {});

                            if (target && target.closest && target.closest('.cmd-view')) {
                                populateFormForView(row);
                                openModal();
                                return;
                            }

							if (target && target.closest && target.closest('.cmd-edit')) {
								var canEdit = (row && (row.is_active === true || row.is_active === 1 || row.is_active === '1')) && !(row && (row.is_locked === true || row.is_locked === 1 || row.is_locked === '1'));
								if (!canEdit) { return; }
                                populateFormForEdit(row);
                                openModal();
                                return;
                            }

							if (target && target.closest && target.closest('.cmd-delete')) {
								// Delete business with confirm (reuses shared helper)
								AppUtils.Actions.deleteResource({
									url: removeUrl,
									method: 'POST',
									data: { id: (row && row.id) },
									confirm: { title: 'Are you sure?', text: 'Delete This Data?' },
									successMessage: 'Business deleted successfully.',
									onSuccess: function(){
										AppUtils.GridHelpers.loadDataToGrid(grid, listUrl, { dateFields: DATE_FIELDS });
									}
								});
								return;
							}

							if (target && target.closest && target.closest('.cmd-inactive')) {
								AppUtils.Actions.deleteResource({
									url: setActiveUrl,
									method: 'POST',
									data: { id: (row && row.id), is_active: 0 },
									confirm: { title: 'Are you sure?', text: 'Mark this business as Inactive?', confirmButtonText: 'Yes, mark inactive' },
									successMessage: 'Business marked inactive successfully.',
									onSuccess: function(){ AppUtils.GridHelpers.loadDataToGrid(grid, listUrl, { dateFields: DATE_FIELDS }); }
								});
								return;
							}

							if (target && target.closest && target.closest('.cmd-activate')) {
								AppUtils.Actions.deleteResource({
									url: setActiveUrl,
									method: 'POST',
									data: { id: (row && row.id), is_active: 1 },
									confirm: { title: 'Are you sure?', text: 'Activate this business?', confirmButtonText: 'Yes, activate' },
									successMessage: 'Business activated successfully.',
									onSuccess: function(){ AppUtils.GridHelpers.loadDataToGrid(grid, listUrl, { dateFields: DATE_FIELDS }); }
								});
								return;
							}

							if (target && target.closest && target.closest('.cmd-lock')) {
								var active = row && (row.is_active === true || row.is_active === 1 || row.is_active === '1');
								if (!active) { return; }
								AppUtils.Actions.deleteResource({
									url: setLockedUrl,
									method: 'POST',
									data: { id: (row && row.id), is_locked: 1 },
									confirm: { title: 'Are you sure?', text: 'Lock this business?', confirmButtonText: 'Yes, lock' },
									successMessage: 'Business locked successfully.',
									onSuccess: function(){ AppUtils.GridHelpers.loadDataToGrid(grid, listUrl, { dateFields: DATE_FIELDS }); }
								});
								return;
							}

							if (target && target.closest && target.closest('.cmd-unlock')) {
								var active2 = row && (row.is_active === true || row.is_active === 1 || row.is_active === '1');
								if (!active2) { return; }
								AppUtils.Actions.deleteResource({
									url: setLockedUrl,
									method: 'POST',
									data: { id: (row && row.id), is_locked: 0 },
									confirm: { title: 'Are you sure?', text: 'Unlock this business?', confirmButtonText: 'Yes, unlock' },
									successMessage: 'Business unlocked successfully.',
									onSuccess: function(){ AppUtils.GridHelpers.loadDataToGrid(grid, listUrl, { dateFields: DATE_FIELDS }); }
								});
								return;
							}


							// New: Clear Data (purge) button
							if (target && target.closest && target.closest('.cmd-purge')) {
								var businessId = row && row.id;
								if (!businessId) return;
								AppUtils.Actions.deleteResource({
									url: purgeUrl,
									method: 'POST',
									data: { id: businessId },
									confirm: { title: 'Clear all data?', text: 'This will permanently delete all data for this business from all tables. This action cannot be undone.', confirmButtonText: 'Yes, clear all data' },
									successMessage: 'Business data cleared successfully.',
									onSuccess: function(){
										// Refresh counts and grid data
										AppUtils.GridHelpers.loadDataToGrid(grid, listUrl, { dateFields: DATE_FIELDS });
									}
								});
								return;
							}
                        } catch (_) {}
                    },
					recordDoubleClick: function(args){
						var row = (args && args.rowData) ? args.rowData : {};
						populateFormForView(row);
						openModal();
					},
					emptyRecordTemplate: window.AppUtils.emptyRecordTemplate(PAGE_TITLE),
					created: function(){
						AppUtils.GridHelpers.loadDataToGrid(grid, listUrl, { dateFields: DATE_FIELDS });
					},
					pdfQueryCellInfo: AppUtils.GridHelpers.pdfQueryCellInfoFactory({ 
						dateFields: DATE_FIELDS,
						sanitizeFields: ['address','users','dine_in_policies','delivery_terms'],
						sanitizeOptions: { stripHtml: true, replaceNewlinesWith: ' ' }
					}),
					excelQueryCellInfo: AppUtils.GridHelpers.excelQueryCellInfoFactory({ 
						dateFields: DATE_FIELDS,
						sanitizeFields: ['address','users','dine_in_policies','delivery_terms'],
						sanitizeOptions: { stripHtml: true, replaceNewlinesWith: ' ' }
					}),
                    beforePdfExport: AppUtils.GridHelpers.beforePdfExportFactory({ fontBase64: (window.PDF_EXPORT_FONT_BASE64 || null), fontSize: 10 }),
					actionBegin: AppUtils.GridHelpers.actionBeginSearchKeyUpdaterFactory(() => grid),
					queryCellInfo: AppUtils.GridHelpers.queryCellInfoHighlighterFactory(() => grid),
					toolbarClick: AppUtils.GridHelpers.toolbarClickFactory(() => grid),
					rowDataBound: AppUtils.GridHelpers.rowDataBoundToggleActionsFactory(),
					dataBound: AppUtils.GridHelpers.dataBoundAutoFitFactory(() => grid),
				}
			));

			// Default: do not autofit columns unless toggled by toolbar
			grid._autofitEnabled = false;
			grid.appendTo('#businessGrid');

			// Expose grid for external refresh
			window.businessGrid = grid;
			window.AppPage = window.AppPage || {};
			window.AppPage.onRefresh = function(){
				if (window.businessGrid && listUrl) {
					AppUtils.GridHelpers.loadDataToGrid(window.businessGrid, listUrl, { dateFields: DATE_FIELDS });
				}
			};

			// Row command visibility/availability: show Lock/Unlock conditionally, hide Edit/Delete when inactive or locked
			const prevRowDataBound = grid.rowDataBound;
			grid.rowDataBound = function(args){
				if (typeof prevRowDataBound === 'function') prevRowDataBound.call(this, args);
				try {
					var data = (args && args.data) ? args.data : {};
					var rowEl = (args && args.row) ? args.row : null;
					if (!rowEl) return;
					var active = data && (data.is_active === true || data.is_active === 1 || data.is_active === '1');
					var locked = data && (data.is_locked === true || data.is_locked === 1 || data.is_locked === '1');

					var lockBtn = rowEl.querySelector('.cmd-lock');
					var unlockBtn = rowEl.querySelector('.cmd-unlock');
					var editBtn = rowEl.querySelector('.cmd-edit');
					var deleteBtn = rowEl.querySelector('.cmd-delete');

					if (lockBtn) lockBtn.style.display = (active && !locked) ? '' : 'none';
					if (unlockBtn) unlockBtn.style.display = (active && locked) ? '' : 'none';

					if (editBtn) { editBtn.style.display = (!active || locked) ? 'none' : ''; }
					if (deleteBtn) { deleteBtn.style.display = (!active || locked) ? 'none' : ''; }

					// Ensure lock button has warning color
					if (lockBtn) {
						lockBtn.classList.add('text-warning');
						var icon = lockBtn.querySelector('.e-icons');
						if (icon) icon.classList.add('text-warning');
					}
				} catch(_) {}
			};

			// Feather icons refresh after data binding
			const prevDataBound = grid.dataBound;
			grid.dataBound = function(e){
				if (typeof prevDataBound === 'function') prevDataBound.call(this, e);
				// Populate any async user mounts
				try {
					var el = this.element || document;
					var mounts = el.querySelectorAll('.business-users[data-business-id]');
					Array.prototype.forEach.call(mounts, function(m){
						var cid = m.getAttribute('data-business-id');
						if (!cid) return;
						// Only populate if not already filled with pills (simple heuristic)
						if (!m.dataset.populated) {
							m.dataset.populated = '1';
							populateUsersForCell(m, cid);
						}
					});
				} catch(_){ }
				if (window.feather) { feather.replace(); }
			};

			const modalEl = document.getElementById('businessModal');
			const form = document.getElementById('businessForm');
			// Ensure Add flow has a default action URL
			if (form) { form.action = addUrl; }
			const titleTextEl = document.getElementById('modalTitle');
			const submitBtn = document.getElementById('submitBtn');
			const inputImage = document.getElementById('image');
			const nameEl = document.getElementById('name');
			const addressEl = document.getElementById('address');
			const emailEl = document.getElementById('email');
			const mobileEl = document.getElementById('mobile');
			const approvedEl = document.getElementById('approved_users');
			const gstinEl = document.getElementById('gstin');
			const panEl = document.getElementById('pan');
			const isActiveEl = document.getElementById('is_active');
			const preview = document.getElementById('showImage');
			const imageFeedback = document.getElementById('imageFeedback');
            const inputId = document.getElementById('business_id');
            const auditMountEl = document.getElementById('auditInfoMount');
			const codeBadgeEl = document.getElementById('businessCodeBadge');

			// Terms modal (stacked on top of business modal)
			const termsModalEl = document.getElementById('businessTermsModal');
			const termsSaveBtn = document.getElementById('termsSaveBtn');
			const termsCompanyIdEl = document.getElementById('terms_company_id');
			const termsBusinessNameEl = document.getElementById('terms_business_name_badge');
			const termsAuditMountEl = document.getElementById('businessTermsAuditInfoMount');
			const termsAuditCtrl = (window.AppUtils && AppUtils.AuditInfo && termsAuditMountEl)
				? (function(){ try { return AppUtils.AuditInfo.init(termsAuditMountEl); } catch(_) { return null; } })()
				: null;
			const dineInEl = document.getElementById('terms_dine_in_policies');
			const deliveryEl = document.getElementById('terms_delivery_terms');
			const btnTerms = document.getElementById('btnTerms');
			const termsButtonsGroupEl = document.getElementById('termsButtonsGroup');
			const parentModalContent = modalEl ? modalEl.querySelector('.modal-content') : null;

			function bindLiveTitleCase(el){
				if (!el) return;
				try {
					if (window.AppUtils && AppUtils.InputHelpers && typeof AppUtils.InputHelpers.bindCamelCase === 'function') {
						AppUtils.InputHelpers.bindCamelCase(el);
						return;
					}
				} catch(_) {}

				// Fallback: capitalize only lowercase letters after word boundaries; preserve existing caps
				el.addEventListener('input', function(){
					var start = el.selectionStart, end = el.selectionEnd;
					var v = el.value || '';
					var t = String(v).replace(/(^|[\s\-_/\.])([a-z])/g, function(_, p1, p2){
						return (p1 || '') + String(p2).toUpperCase();
					});
					if (v !== t) {
						el.value = t;
						try { el.setSelectionRange(start, end); } catch(_) {}
					}
				});
			}
			bindLiveTitleCase(dineInEl);
			bindLiveTitleCase(deliveryEl);

			function getCSRF(){ var meta = document.querySelector('meta[name="csrf-token"]'); return meta ? meta.getAttribute('content') : ''; }
			function getCurrentBusinessId(){ return (inputId && inputId.value) ? String(inputId.value) : ''; }
			function getCurrentBusinessName(){ return (nameEl && nameEl.value) ? String(nameEl.value) : ''; }

			function dimParent(on){
				if (!parentModalContent) return;
				if (on) parentModalContent.classList.add('modal-parent-dim');
				else parentModalContent.classList.remove('modal-parent-dim');
			}

			function loadTerms(companyId){
				if (!companyId) return;
				fetch(termsGetUrl + '?company_id=' + encodeURIComponent(companyId), {
					headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
				})
				.then(function(r){ return r.json(); })
				.then(function(json){
					if (!json || json.success !== true) {
						try { AppUtils.notify((json && json.message) ? json.message : 'Failed to load terms.', { type: 'error' }); } catch(_) {}
						return;
					}
					var d = json.data || {};
					if (dineInEl) dineInEl.value = d.dine_in_policies || '';
					if (deliveryEl) deliveryEl.value = d.delivery_terms || '';
					if (termsAuditCtrl) {
						try {
							termsAuditCtrl.set({
								createdAt: d.created_at,
								updatedAt: d.updated_at,
								createdBy: d.created_by,
								updatedBy: d.lastmodified_by
							});
							termsAuditCtrl.showFor('edit');
						} catch(_) {}
					}
				})
				.catch(function(){
					try { AppUtils.notify('Failed to load terms.', { type: 'error' }); } catch(_) {}
				});
			}

			function openTermsModal(focus){
				if (!termsModalEl) return;
				var companyId = getCurrentBusinessId();
				if (!companyId) {
					try { AppUtils.notify('Please open an existing business (View/Edit) first.', { type: 'warning' }); } catch(_) {}
					return;
				}
				if (termsCompanyIdEl) termsCompanyIdEl.value = companyId;
				if (termsBusinessNameEl) termsBusinessNameEl.textContent = getCurrentBusinessName() || ('Business #' + companyId);
				termsModalEl.dataset.focus = focus || '';
				loadTerms(companyId);
				try {
					// Prevent closing when clicking outside / pressing ESC
					bootstrap.Modal.getOrCreateInstance(termsModalEl, { backdrop: 'static', keyboard: false }).show();
				} catch(_) {}
			}

			function saveTerms(){
				var companyId = (termsCompanyIdEl && termsCompanyIdEl.value) ? String(termsCompanyIdEl.value) : '';
				if (!companyId) return;
				var token = getCSRF();
				var payload = {
					company_id: companyId,
					dine_in_policies: dineInEl ? dineInEl.value : '',
					delivery_terms: deliveryEl ? deliveryEl.value : ''
				};
				if (termsSaveBtn) termsSaveBtn.disabled = true;
				fetch(termsUpdateUrl, {
					method: 'POST',
					headers: {
						'X-Requested-With': 'XMLHttpRequest',
						'Accept': 'application/json',
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': token || ''
					},
					body: JSON.stringify(payload)
				})
				.then(function(r){ return r.json().then(function(j){ return { status: r.status, json: j }; }); })
				.then(function(res){
					if (termsSaveBtn) termsSaveBtn.disabled = false;
					var json = res.json || {};
					if (res.status >= 400 || json.success !== true) {
						try { AppUtils.notify((json && json.message) ? json.message : 'Failed to save terms.', { type: 'error' }); } catch(_) {}
						return;
					}
					try { AppUtils.notify(json.message || 'Terms saved.', { type: 'success' }); } catch(_) {}
					var d = json.data || {};
					if (termsAuditCtrl) {
						try {
							termsAuditCtrl.set({
								createdAt: d.created_at,
								updatedAt: d.updated_at,
								createdBy: d.created_by,
								updatedBy: d.lastmodified_by
							});
							termsAuditCtrl.showFor('edit');
						} catch(_) {}
					}
					try { AppUtils.GridHelpers.loadDataToGrid(grid, listUrl, { dateFields: DATE_FIELDS }); } catch(_) {}
					try { bootstrap.Modal.getOrCreateInstance(termsModalEl).hide(); } catch(_) {}
				})
				.catch(function(){
					if (termsSaveBtn) termsSaveBtn.disabled = false;
					try { AppUtils.notify('Failed to save terms.', { type: 'error' }); } catch(_) {}
				});
			}

			if (btnTerms) btnTerms.addEventListener('click', function(){ openTermsModal(); });
			if (termsSaveBtn) termsSaveBtn.addEventListener('click', saveTerms);
			termsModalEl && termsModalEl.addEventListener('shown.bs.modal', function(){
				dimParent(true);
				var focus = (termsModalEl.dataset && termsModalEl.dataset.focus) ? termsModalEl.dataset.focus : '';
				try {
					if (focus === 'delivery' && deliveryEl) deliveryEl.focus({ preventScroll: true });
					else if (dineInEl) dineInEl.focus({ preventScroll: true });
				} catch(_) {}
			});
			termsModalEl && termsModalEl.addEventListener('hidden.bs.modal', function(){ dimParent(false); });

			// Category list URL for fetching dropdown options

			// Capture default error messages from markup to restore on Add mode
			const DEFAULT_ERRORS = (function(){
				function txt(sel){ var el = document.querySelector(sel); return el ? (el.textContent || '').trim() : ''; }
				return {
					name: txt('#name_error'),
					address: txt('#address_error'),
					email: txt('#email_error'),
					mobile: txt('#mobile_error'),
					approved_users: txt('#approved_error'),
					gstin: txt('#gstin_error'),
					pan: txt('#pan_error'),
					image: txt('#imageFeedback')
				};
			})();

			function setMode(mode){ if (form) form.dataset.mode = mode; }

			function setTitleFor(mode){
				const map = { create: 'Add', edit: 'Edit', view: 'View' };
				const suffix = map[mode] || 'Add';
				if (titleTextEl) titleTextEl.textContent = PAGE_TITLE + ' [' + suffix + ']';
			}

			function openModal(){ const modal = bootstrap.Modal.getOrCreateInstance(modalEl); modal.show(); }

			function applyModeUI(mode){
				var isView = (mode === 'view');
						if (termsButtonsGroupEl) termsButtonsGroupEl.classList.toggle('d-none', !isView);

				[nameEl, addressEl, emailEl, mobileEl, approvedEl, gstinEl, panEl, isActiveEl].forEach(function(el){ if (el) el.disabled = isView; });

				if (inputImage) inputImage.disabled = isView;
				if (submitBtn) submitBtn.style.display = isView ? 'none' : '';
				if (auditCtrl) auditCtrl.showFor(mode);
			}

			function resetFormForCreate(){
				if (!form) return;
				form.action = addUrl;

				setMode('create');
				if (titleTextEl) titleTextEl.textContent = PAGE_TITLE + ' [Add]';

				if (submitBtn && submitBtn.querySelector('span')) submitBtn.querySelector('span').textContent = 'Save';

				[nameEl, addressEl, emailEl, mobileEl, approvedEl, gstinEl, panEl].forEach(function(el){ if (el){ el.value = ''; el.classList.remove('is-invalid'); } });
				if (approvedEl) approvedEl.value = 5;


				if (inputImage) { inputImage.value = ''; inputImage.required = true; inputImage.classList.remove('is-invalid'); }
                if (inputId) inputId.value = '';

				// restore default messages for fields for clean Add mode
				var nameErr = document.getElementById('name_error'); if (nameErr) nameErr.textContent = DEFAULT_ERRORS.name;
				var addrErr = document.getElementById('address_error'); if (addrErr) addrErr.textContent = DEFAULT_ERRORS.address;
				var emailErr = document.getElementById('email_error'); if (emailErr) emailErr.textContent = DEFAULT_ERRORS.email;
				var mobErr = document.getElementById('mobile_error'); if (mobErr) mobErr.textContent = DEFAULT_ERRORS.mobile;
				var apprErr = document.getElementById('approved_error'); if (apprErr) apprErr.textContent = DEFAULT_ERRORS.approved_users;
				var gstErr = document.getElementById('gstin_error'); if (gstErr) gstErr.textContent = DEFAULT_ERRORS.gstin;
				var panErr = document.getElementById('pan_error'); if (panErr) panErr.textContent = DEFAULT_ERRORS.pan;
				if (imageFeedback) imageFeedback.textContent = DEFAULT_ERRORS.image;

				form.classList.remove('was-validated');

				if (preview) preview.src = NO_IMAGE;
				if (codeBadgeEl) { codeBadgeEl.textContent = ''; codeBadgeEl.classList.add('d-none'); }

				// Ensure UI reflects create mode (show Save, hide audit info, enable inputs)
				applyModeUI('create');
				if (auditCtrl) auditCtrl.set({ createdAt: '', updatedAt: '' });
			}

			// Build audit info controller
			const auditCtrl = (window.AppUtils && window.AppUtils.AuditInfo && auditMountEl)
				? window.AppUtils.AuditInfo.init(auditMountEl)
				: null;

			// Expose helper for external triggers
			window.BusinessModal = window.BusinessModal || {};
			window.BusinessModal.resetForCreate = resetFormForCreate;

			function populateFormForEdit(data){
				if (!form) return;

				form.action = updateUrl;

				setMode('edit');
				setTitleFor('edit');

				if (submitBtn && submitBtn.querySelector('span')) submitBtn.querySelector('span').textContent = 'Update';
				if (inputId) inputId.value = (data && data.id) || '';
				if (nameEl) nameEl.value = (data && data.name) || '';
				if (addressEl) addressEl.value = (data && data.address) || '';
				if (emailEl) emailEl.value = (data && data.email) || '';
				if (mobileEl) mobileEl.value = (data && data.mobile) || '';
				if (approvedEl) approvedEl.value = (data && data.approved_users) || 5;
				if (gstinEl) gstinEl.value = (data && data.gstin) || '';
				if (panEl) panEl.value = (data && data.pan) || '';
				if (isActiveEl) isActiveEl.checked = !!(data && data.is_active);


				if (inputImage) { inputImage.value = ''; inputImage.required = false; inputImage.classList.remove('is-invalid'); }
				if (preview) preview.src = AppUtils.computeImageUrl(data, BASE_URL, NO_IMAGE);
				if (auditCtrl) auditCtrl.set({ createdAt: data && data.created_at, updatedAt: data && data.updated_at });
				if (codeBadgeEl) {
					var code = (data && data.company_code) ? data.company_code : '';
					codeBadgeEl.textContent = code;
					if (code) { codeBadgeEl.classList.remove('d-none'); } else { codeBadgeEl.classList.add('d-none'); }
				}

				applyModeUI('edit');
			}

			function populateFormForView(data){
				if (!form) return;

				setMode('view');
				setTitleFor('view');

				if (inputId) inputId.value = (data && data.id) || '';
				if (nameEl) nameEl.value = (data && data.name) || '';
				if (addressEl) addressEl.value = (data && data.address) || '';
				if (emailEl) emailEl.value = (data && data.email) || '';
				if (mobileEl) mobileEl.value = (data && data.mobile) || '';
				if (approvedEl) approvedEl.value = (data && data.approved_users) || 5;
				if (gstinEl) gstinEl.value = (data && data.gstin) || '';
				if (panEl) panEl.value = (data && data.pan) || '';
				if (isActiveEl) isActiveEl.checked = !!(data && data.is_active);


				if (inputImage) { inputImage.required = false; inputImage.classList.remove('is-invalid'); }
				if (preview) preview.src = AppUtils.computeImageUrl(data, BASE_URL, NO_IMAGE);
				if (auditCtrl) auditCtrl.set({ createdAt: data && data.created_at, updatedAt: data && data.updated_at });
				if (codeBadgeEl) {
					var code = (data && data.company_code) ? data.company_code : '';
					codeBadgeEl.textContent = code;
					if (code) { codeBadgeEl.classList.remove('d-none'); } else { codeBadgeEl.classList.add('d-none'); }
				}

				applyModeUI('view');
			}

			// Removed custom submit validation so server messages surface via AJAX handler

			// Ajax submit via common helper
			if (window.AppUtils && AppUtils.FormHelpers && form) {
				AppUtils.FormHelpers.attachAjaxSubmit(form, {
					submitBtn,
					submitWhenInvalid: true,
					getMode: function(){ return (form && form.dataset && form.dataset.mode) ? form.dataset.mode : 'create'; },
					// Map server-side validation errors to specific containers under inputs
					errorTargets: {
						name: '#name_error',
						address: '#address_error',
						email: '#email_error',
						mobile: '#mobile_error',
						approved_users: '#approved_error',
						gstin: '#gstin_error',
						pan: '#pan_error',
						image: '#imageFeedback'
					},
					onSuccess: function(json){
						const msg = (json && json.message) ? json.message : 'Business saved successfully.';
						window.AppUtils.notify(msg, { type: 'success' });
						bootstrap.Modal.getOrCreateInstance(modalEl).hide();

						if (window.businessGrid) {
							AppUtils.GridHelpers.loadDataToGrid(window.businessGrid, listUrl, { dateFields: DATE_FIELDS });
						}
					}
				});
			}

			// Modal events
			modalEl && modalEl.addEventListener('shown.bs.modal', function(){
				if (window.feather) { feather.replace(); }

				const firstInvalid = Array.from(form.querySelectorAll('.is-invalid, .form-control:invalid, input:invalid, textarea:invalid'))[0];

				if (firstInvalid) { firstInvalid.focus({ preventScroll: true }); if (firstInvalid.select) firstInvalid.select(); return; }
				if (nameEl) { nameEl.focus({ preventScroll: true }); }
			});

			modalEl && modalEl.addEventListener('hidden.bs.modal', function(){
				try {
					form.classList.remove('was-validated');
					Array.from(form.querySelectorAll('.is-invalid')).forEach(function (el) { el.classList.remove('is-invalid'); });

					[ nameEl, addressEl, emailEl, mobileEl, approvedEl, gstinEl, panEl, isActiveEl, inputImage].forEach(function (el) {
						if (!el) return;
						el.disabled = false;
						if (typeof el.setCustomValidity === 'function') { el.setCustomValidity(''); }
					});

                    if (inputId) inputId.value = '';
					if (auditCtrl) auditCtrl.set({ createdAt: '', updatedAt: '' });

					if (imageFeedback) imageFeedback.textContent = '';
					if (preview) preview.src = NO_IMAGE;
					if (codeBadgeEl) { codeBadgeEl.textContent = ''; codeBadgeEl.classList.add('d-none'); }
				} catch (err) {}
			});

				// No rating control on this modal

			// Image preview + click-to-open + validation
			function openPicker(evt){ 
                evt.preventDefault(); 
                inputImage && inputImage.click(); 
            }

			if (inputImage && preview) {
				inputImage.addEventListener('change', function (e) {
					const file = e.target.files && e.target.files[0];
					if (!file) {
						inputImage.classList.remove('is-invalid');
						if (imageFeedback) imageFeedback.textContent = '';
						if (preview) preview.src = NO_IMAGE; // Reset preview on cancel
						return;
					}

					const isImage = /^image\//.test(file.type);
					const allowed = ['jpeg','png','jpg','gif','svg+xml'];
					const subtype = file.type.split('/')[1] || '';
					const maxSize = 2 * 1024 * 1024; // 2MB

					if (!isImage || allowed.indexOf(subtype) === -1) {
						inputImage.classList.add('is-invalid');
						if (imageFeedback) imageFeedback.textContent = 'Image must be a file of type: jpeg, png, jpg, gif, svg.';
						return;
					}

					if (file.size > maxSize) {
						inputImage.classList.add('is-invalid');
						if (imageFeedback) imageFeedback.textContent = 'Image must not be larger than 2MB.';
						return;
					}

					inputImage.classList.remove('is-invalid');

					if (imageFeedback) imageFeedback.textContent = '';

					const reader = new FileReader();
					reader.onload = function (ev) { preview.src = ev.target.result; };
					reader.readAsDataURL(file);
				});

				preview.addEventListener('click', openPicker);

				preview.addEventListener('keydown', function (e) {
					const key = e.key || e.keyCode;
					if (key === 'Enter' || key === ' ' || key === 'Spacebar' || key === 13 || key === 32) { openPicker(e); }
				});
			}

			// Bind the Add button to open modal in create mode
			const headerAddBtn = document.getElementById('toolbarAdd');
			if (headerAddBtn) {
				headerAddBtn.addEventListener('click', function(e){
					e.preventDefault();
					if (window.BusinessModal && typeof window.BusinessModal.resetForCreate === 'function') {
						window.BusinessModal.resetForCreate();
					}
					const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('businessModal'));
					modal.show();
				});
			}

			// --- New: Auto-caps and input constraints ---
			function toUpperBinder(el){ if (!el) return; el.addEventListener('input', function(){ this.value = (this.value || '').toUpperCase(); }); }
			// Bind camel-case for business name and address, uppercase for GSTIN/PAN
			if (window.AppUtils && AppUtils.InputHelpers) {
				AppUtils.InputHelpers.bindCamelCase(nameEl);
				AppUtils.InputHelpers.bindCamelCase(addressEl);
			}
			toUpperBinder(gstinEl); toUpperBinder(panEl);

			// Force email to lowercase
			if (emailEl) {
				emailEl.addEventListener('input', function(){ this.value = (this.value || '').toLowerCase(); });
			}

			if (mobileEl) {
				mobileEl.addEventListener('input', function(){ this.value = (this.value || '').replace(/\D/g, '').slice(0, 10); });
			}

			// Pattern hint validation (optional custom validity)
			const rePAN = /^[A-Z]{5}[0-9]{4}[A-Z]$/;
			const reGSTIN = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/;
			function addPatternValidation(el, re, msgSel){
				if (!el) return;
				const msgEl = msgSel ? document.querySelector(msgSel) : null;
				el.addEventListener('input', function(){ this.setCustomValidity(''); });
				el.addEventListener('blur', function(){
					const v = (this.value || '').trim();
					if (v && !re.test(v)) { this.setCustomValidity('Invalid'); if (msgEl && !msgEl.textContent) { msgEl.textContent = 'Invalid format'; } }
					else { this.setCustomValidity(''); }
				});
			}
			addPatternValidation(panEl, rePAN, '#pan_error');
			addPatternValidation(gstinEl, reGSTIN, '#gstin_error');

			// Approved Users: allow only 1-100, digits only, center aligned
			if (approvedEl) {
				approvedEl.addEventListener('input', function(){
					var v = (this.value || '').replace(/\D/g, '');
					if (v.length > 3) v = v.slice(0,3);
					var n = v ? parseInt(v, 10) : '';
					if (n !== '' && !isNaN(n)) {
						if (n < 1) n = 1;
						if (n > 100) n = 100;
						this.value = String(n);
					} else {
						this.value = v; // allow empty while typing
					}
				});
			}

			// Business code badge: fill on edit/view, click-to-copy
			if (codeBadgeEl) {
				codeBadgeEl.addEventListener('click', function(){
					const code = (codeBadgeEl.textContent || '').trim();
					if (!code || !navigator.clipboard) return;
					navigator.clipboard.writeText(code).then(function(){ try { AppUtils.notify('Business code copied', { type: 'info' }); } catch(_){} });
				});
			}
		});
	})();
</script>

<style>
	/* Orange outline buttons (match app orange) */
	.btn-outline-orange {
		--c: #ff7a2a;
		color: var(--c);
		border-color: var(--c);
		background: transparent;
	}
	.btn-outline-orange:hover,
	.btn-outline-orange:focus {
		color: #fff;
		background: #ff7a2a;
		border-color: #ff7a2a;
	}

	/* When the stacked terms modal is open, dim the parent business modal via overlay (no transparency) */
	.modal-parent-dim {
		position: relative;
	}
	.modal-parent-dim::after {
		content: '';
		position: absolute;
		inset: 0;
		background: rgba(15, 23, 42, 0.20);
		border-radius: inherit;
		pointer-events: none;
	}

	/* Wrap long bullet text in the new Terms grid columns */
	.e-grid .e-rowcell.terms-wrap-cell {
		white-space: normal !important;
		overflow: visible !important;
		text-overflow: clip !important;
		line-height: 1.25;
	}
	.e-grid .e-rowcell.terms-wrap-cell ul,
	.e-grid .e-rowcell.terms-wrap-cell li {
		white-space: normal !important;
		overflow-wrap: anywhere;
		word-break: break-word;
	}

	/* Business modal footer: Terms button group (view mode only) */
	.terms-buttons-group {
		display: flex;
		align-items: center;
		gap: .5rem;
	}
</style>
 

{{-- Modal: Add/Edit/View Business --}}
<div class="modal fade" id="businessModal" tabindex="-1" aria-labelledby="businessModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header py-2" style="min-height:42px;">
				<h5 class="modal-title d-flex align-items-center fs-6" id="businessModalLabel">
					<i class="fa-solid fa-building me-2" style="width:18px;height:18px;line-height:18px;"></i>
					<span id="modalTitle">{{ $pageTitle }} [Add]</span>
				</h5>

				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>

			<form id="businessForm" action="" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
				@csrf

				<input type="hidden" id="business_id" name="id" value="">

				<div class="modal-body">
					<div class="row g-3">

						<div class="col-12">
							<label for="name" class="form-label d-flex align-items-center justify-content-between">
								<span>Business Name <span class="text-danger">*</span></span>
								<span id="businessCodeBadge" class="badge bg-secondary ms-2 d-none" title="Business Code" role="button" tabindex="0"></span>
							</label>
							<input type="text" class="form-control" id="name" name="name" minlength="10" maxlength="50" autocomplete="off" required>
							<div class="invalid-feedback" id="name_error">Please specify business name!</div>
						</div>

						<div class="col-12">
							<label for="address" class="form-label">Business Address <span class="text-danger">*</span></label>
							<textarea class="form-control" id="address" name="address" rows="3" maxlength="500" required placeholder="Enter Business Address"></textarea>
							<div class="invalid-feedback" id="address_error">Please specify business address!</div>
						</div>

						<div class="col-12">
							<label for="email" class="form-label">Email <span class="text-danger">*</span></label>
							<input type="email" class="form-control" id="email" name="email" maxlength="100" autocomplete="off" required>
							<div class="invalid-feedback" id="email_error">Please specify a valid email address!</div>
						</div>

						<div class="col-12">
							<div class="row gx-4 gy-2 gx-2">
                                <div class="col-12 col-md-6 pe-md-2"> <!-- Right padding on medium+ screens -->
                                    <label for="mobile" class="form-label">Mobile <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="mobile" name="mobile" inputmode="numeric" pattern="\d{10}" maxlength="10" autocomplete="off" required title="Enter 10 digit mobile number">
                                    <div class="invalid-feedback" id="mobile_error">Mobile must be exactly 10 digits.</div>
                                </div>
                                <div class="col-12 col-md-6 ps-md-2"> <!-- Left padding on medium+ screens -->
                                    <label for="approved_users" class="form-label">Approved Users <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-center" id="approved_users" name="approved_users" pattern="^(?:[1-9][0-9]?|100)$" maxlength="3" value="5" autocomplete="off" required title="Enter a number from 1 to 100">
                                    <div class="invalid-feedback" id="approved_error">Enter a number from 1 to 100.</div>
                                </div>
                            </div>
						</div>

                        <div class="col-12">
                            <div class="row gx-4 gy-2 gx-2">
                                <div class="col-12 col-md-6 pe-md-2"> <!-- Right padding on medium+ screens -->
                                    <label for="gstin" class="form-label">GSTIN</label>
                                    <input type="text" class="form-control" id="gstin" name="gstin" maxlength="15" autocomplete="off" placeholder="Enter GSTIN" pattern="[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]" title="Format: 2 digits + PAN(AAAAA0000A) + 1 entity + Z + 1 checksum" style="text-transform: uppercase;">
                                    <div class="invalid-feedback" id="gstin_error">Enter a valid GSTIN (e.g., 22AAAAA0000A1Z5).</div>
                                </div>
                                <div class="col-12 col-md-6 ps-md-2"> <!-- Left padding on medium+ screens -->
                                    <label for="pan" class="form-label">PAN</label>
                                    <input type="text" class="form-control" id="pan" name="pan" maxlength="10" autocomplete="off" placeholder="Enter PAN" pattern="[A-Z]{5}[0-9]{4}[A-Z]" title="Format: AAAAA0000A" style="text-transform: uppercase;">
                                    <div class="invalid-feedback" id="pan_error">Enter a valid PAN (e.g., AAAAA0000A).</div>
                                </div>
                            </div>
                        </div>

						<div class="col-12">
							<label for="image" class="form-label">Business Logo <span class="text-danger">*</span></label>
							<input class="form-control" type="file" id="image" name="image" accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml" required>
							<div class="invalid-feedback" id="imageFeedback">Image must be JPEG/PNG/GIF/SVG up to 2MB.</div>
							<div class="mt-2">
								<div class="d-flex justify-content-center">
									<img id="showImage" src="{{ url('upload/no_image.jpg') }}" class="rounded img-thumbnail" alt="business logo" style="width: 96px; height: 96px; cursor: pointer;" role="button" tabindex="0" title="Click to change logo" aria-label="Change business logo">
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="modal-footer">
					<div class="w-100 d-flex align-items-center justify-content-between">
						<div id="auditInfoMount" class="d-flex flex-column"></div>

						<div class="d-flex align-items-center gap-2">
							<div id="termsButtonsGroup" class="terms-buttons-group d-none">
								<button type="button" id="btnTerms" class="btn btn-outline-orange" title="Terms & Conditions" aria-label="Terms & Conditions">
									<i class="fa-solid fa-file-signature"></i>
								</button>
							</div>
							<button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
								<i data-feather="x" class="me-1"></i>
								<span>Cancel</span>
							</button>

							<button type="submit" id="submitBtn" class="btn btn-primary">
								<i data-feather="save" class="me-1"></i>
								<span>Save</span>
							</button>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>

{{-- Stacked modal: Terms & Conditions for the selected business --}}
<div class="modal fade" id="businessTermsModal" tabindex="-1" aria-labelledby="businessTermsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
	<div class="modal-dialog modal-dialog-centered" style="max-width:760px;">
		<div class="modal-content">
			<div class="modal-header py-2" style="min-height:42px;">
				<h5 class="modal-title d-flex align-items-center fs-6" id="businessTermsModalLabel">
					<i class="fa-solid fa-file-signature me-2" style="width:18px;height:18px;line-height:18px;"></i>
					<span>Terms &amp; Conditions</span>
					<span class="badge bg-white text-dark border ms-2" style="font-size:12px;" id="terms_business_name_badge"></span>
				</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>

			<div class="modal-body">
				<input type="hidden" id="terms_company_id" value="" />
				<div class="mb-3">
					<label for="terms_dine_in_policies" class="form-label">Dine-in Policies</label>
					<textarea id="terms_dine_in_policies" class="form-control" rows="5" maxlength="500" placeholder="Enter one point per line (max 500 chars)"></textarea>
					<div class="form-text">Use one policy per line.</div>
				</div>

				<div class="mb-3">
					<label for="terms_delivery_terms" class="form-label">Delivery Terms</label>
					<textarea id="terms_delivery_terms" class="form-control" rows="5" maxlength="500" placeholder="Enter one point per line (max 500 chars)"></textarea>
					<div class="form-text">Use one term per line.</div>
				</div>
			</div>

			<div class="modal-footer">
				<div class="w-100 d-flex align-items-center justify-content-between">
					<div id="businessTermsAuditInfoMount" class="d-flex flex-column"></div>

					<div class="d-flex align-items-center justify-content-end gap-2">
						<button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
							<i data-feather="x" class="me-1"></i>
							<span>Cancel</span>
						</button>
						<button type="button" class="btn btn-primary" id="termsSaveBtn">
							<i data-feather="save" class="me-1"></i>
							<span>Save</span>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

@endsection