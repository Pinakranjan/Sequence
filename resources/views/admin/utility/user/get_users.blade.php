@extends('admin.admin_master')
@section('admin')

	@php
		// Page meta shared with common include (icon used in header)
		$pageTitle = $pageTitle ?? 'User Register';
		$pageIcon = $pageIcon ?? 'fas fa-users-cog';
	@endphp

	@include('components.common_js', [
		'wrapCard' => true,
		'icon' => $pageIcon,
		'title' => $pageTitle,
		'refreshId' => 'btnGridRefresh',
		'reloadIconId' => 'reloadIcon',
		'gridId' => 'userGrid',
		'bodyHeight' => '500px',
		'showUserAvatar' => false,
		'hideAddButton' => true // No add user provision here
	])

	<script>
		(function(){
			'use strict';

			window.UserGridHelpers = window.UserGridHelpers || {};
			if (typeof window.UserGridHelpers.copyBusinessCode !== 'function') {
				   window.UserGridHelpers.copyBusinessCode = function(element){
					try {
						if (!element || !element.getAttribute) { return; }
						var encoded = element.getAttribute('data-business-code') || '';
						if (!encoded) { return; }
						var decoded = encoded;
						try { decoded = decodeURIComponent(encoded); } catch(_) {}
						if (navigator.clipboard && navigator.clipboard.writeText) {
							navigator.clipboard.writeText(decoded)
								.then(function(){ try { AppUtils.notify('Business code copied', { type: 'info' }); } catch(_){} })
								.catch(function(){});
						}
					} catch(_) {}
				};
			}

			document.addEventListener('DOMContentLoaded', function(){
				if (!(window.AppUtils  &&  window.AppUtils.librariesReady)) return;

				(function enableModalStacking(){
					var baseZ = 1055;
					document.addEventListener('show.bs.modal', function (evt) {
						var openCount = document.querySelectorAll('.modal.show').length;
						var z = baseZ + (10 * openCount);
						var modal = evt.target;
						if (modal && modal.classList && modal.classList.contains('modal')) {
							modal.style.zIndex = z;
							  setTimeout(function(){
								var backdrop = document.querySelector('.modal-backdrop:not(.modal-stack)');
								if (backdrop) {
									backdrop.style.zIndex = z - 1;
									backdrop.classList.add('modal-stack');
								}
							}, 0);
						}
					});
					document.addEventListener('hidden.bs.modal', function(){
						var visible = document.querySelectorAll('.modal.show').length;
						var stacks = document.querySelectorAll('.modal-backdrop.modal-stack');
						for (var i = stacks.length - 1; i >= visible; i--) {
							var backdrop = stacks[i];
							if (backdrop && backdrop.parentNode) {
								backdrop.parentNode.removeChild(backdrop);
							}
						}
					});
				})();

				// Current context
				const CURRENT_USER_ID = @json(optional(Auth::user())->id);
				const CURRENT_BUSINESS_ID = @json(optional(Auth::user())->company_id);
				const CURRENT_ROLE = @json(strtolower(trim((string) optional(Auth::user())->role)));
				const IS_SUPER_ADMIN = (CURRENT_ROLE === 'super admin' || CURRENT_ROLE === 'super_admin' || CURRENT_ROLE === 'superadmin');
				const CAN_DELETE_USERS = IS_SUPER_ADMIN;
				let CAN_LIST_BUSINESSES = IS_SUPER_ADMIN; // Revised below after root detection

				// Endpoints
				const listUrl = @json(route('list.users'));
				const permListUrl = @json(route('list.user.forms'));
				const permSaveUrl = @json(route('save.user.permissions'));
				const updateUrl = @json(route('update.user'));
				const setActiveUrl = @json(route('setactive.user'));
				const setLockedUrl = @json(route('setlocked.user'));
				const revokeSessionUrl = @json(route('revoke.user.session'));
				const removeUrl = @json(route('delete.user'));
				const superBusinessListUrl = @json(route('superuser.business.assignments'));
				const superBusinessSyncUrl = @json(route('superuser.business.assignments.sync'));


				// Shared
				const PAGE_TITLE = @json($pageTitle);
				const BASE_URL = @json(url('/'));
				const NO_IMAGE = @json(url('upload/no_image.jpg'));
				const DATE_FIELDS = ['created_at', 'updated_at', 'login_time', 'last_connected_time'];
				const SUPER_USER_ID_EXCLUSIONS = Object.freeze((function(){
					const raw = @json(array_values(config('services.super_users.ids') ?? []));
					if (!Array.isArray(raw)) return [];
					return raw
						.map(function(val){
							const num = Number(val);
							return Number.isFinite(num) ? num : null;
						})
						.filter(function(val){ return val !== null; });
				})());
				const IS_ROOT_SUPER_USER = (function(){
					if (!Array.isArray(SUPER_USER_ID_EXCLUSIONS) || !SUPER_USER_ID_EXCLUSIONS.length) return false;
					const currentId = Number(CURRENT_USER_ID);
					if (!Number.isFinite(currentId)) return false;
					return SUPER_USER_ID_EXCLUSIONS.indexOf(currentId) !== -1;
				})();
				CAN_LIST_BUSINESSES = IS_ROOT_SUPER_USER;

				function filterExcludedSuperUsers(list){
					if (!Array.isArray(list) || !SUPER_USER_ID_EXCLUSIONS.length) return list;
					const blocked = new Set(SUPER_USER_ID_EXCLUSIONS);
					return list.filter(function(item){
						const id = Number(item && item.id);
						return !(Number.isFinite(id) && blocked.has(id));
					});
				}

				// Detect small screens to mirror business grid column chooser behavior
				const isSmallScreen = (typeof window !== 'undefined') && window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches;
				const showColumnChooser = !isSmallScreen;

				// Build action commands (conditionally include Modify/Delete based on role)
				var COMMANDS = [
					{ type: 'None', title: 'View', buttonOption: { iconCss: 'e-icons e-eye', cssClass: 'e-flat e-fleet cmd-view' } }
				];
				if (IS_SUPER_ADMIN) {
					COMMANDS.push({ type: 'None', title: 'Modify', buttonOption: { iconCss: 'e-icons e-edit', cssClass: 'e-flat e-fleet cmd-edit' } });
				}
				if (CAN_DELETE_USERS) {
					COMMANDS.push({ type: 'None', title: 'Remove', buttonOption: { iconCss: 'e-icons e-delete', cssClass: 'e-flat e-fleet cmd-delete' } });
				}
				if (IS_ROOT_SUPER_USER) {
					COMMANDS.push({ type: 'None', title: 'Businesses', buttonOption: { iconCss: 'e-icons e-xml-mapping', cssClass: 'e-flat e-fleet cmd-super-business', title: 'Assign Businesses' } });
				}
				COMMANDS = COMMANDS.concat([
					{ type: 'None', title: 'Setup', buttonOption: { iconCss: 'e-icons e-settings', cssClass: 'e-flat e-fleet cmd-setup', title: 'Setup Permissions' } },

					{ type: 'None', title: 'Inactive Record', buttonOption: { iconCss: 'e-icons e-circle-close', cssClass: 'e-close e-flat e-fleet cmd-inactive' } },
					{ type: 'None', title: 'Activate Record', buttonOption: { iconCss: 'e-icons e-circle-check', cssClass: 'e-open e-flat e-fleet cmd-activate' } },
					{ type: 'None', title: 'Lock', buttonOption: { iconCss: 'e-icons e-lock', cssClass: 'e-flat e-fleet text-warning cmd-lock' } },
					{ type: 'None', title: 'Unlock', buttonOption: { iconCss: 'e-icons e-unlock', cssClass: 'e-flat e-fleet text-warning cmd-unlock' } },
					{ type: 'None', title: 'Terminate', buttonOption: { iconCss: 'e-icons e-export', cssClass: 'e-flat e-fleet text-danger cmd-terminate', title: 'Terminate Active Session' } }
				]);

				// Build grid
				let grid = new ej.grids.Grid(Object.assign(
					AppUtils.GridHelpers.baseGridOptions({
						showColumnChooser: showColumnChooser
					}),
					{
						columns: [
							{ field:'photo', headerText: '', width: 62, minWidth: 60, textAlign: 'Center', allowSorting: false, allowFiltering: false, showInColumnChooser: false, exportExclude: true,
								template: function(data){
									var src = (data && data.photo_url) ? String(data.photo_url) : NO_IMAGE;
									var safeSrc = (window.AppUtils && AppUtils.escapeHtml) ? AppUtils.escapeHtml(src) : src;
									var fallback = (window.AppUtils && AppUtils.escapeHtml) ? AppUtils.escapeHtml(NO_IMAGE) : NO_IMAGE;
									return '<div class="d-flex justify-content-center align-items-center">'
											+ '<img src="' + safeSrc + '" alt="user-photo" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;" onerror="this.onerror=null;this.src=\'' + fallback + '\';" />'
											+ '</div>';
								}
							},
							{ field: 'name', headerText: 'User Name', minWidth: 200, width: 320,
								template: function(data){
									try {
										var name = (window.AppUtils && AppUtils.escapeHtml) ? AppUtils.escapeHtml(data && data.name ? data.name : '') : (data && data.name ? data.name : '');
										var isActive = data && (data.status === true || data.status === 1 || data.status === '1');
										var isLocked = data && (data.is_locked === true || data.is_locked === 1 || data.is_locked === '1');
										var inactivePill = '';
										if (!isActive) {
											inactivePill = '<span style="background-color:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:5px;font-size:10px;font-weight:500;margin-left:8px;height:16px;line-height:16px;display:inline-flex;align-items:center;">Inactive</span>';
										}
										var lockIcon = '';
										if (isLocked) {
											lockIcon = '<i data-feather="lock" class="ms-2 text-warning" style="width:14px;height:14px;vertical-align:middle;" title="Locked"></i>';
										}
										// Role badge: USER / ADMIN / SUPER ADMIN / WAITER / CHEF
										var rawRole = (data && data.role) ? String(data.role).trim().toLowerCase() : '';
										var roleLabel = 'USER';
										var roleClass = 'bg-secondary';
										var roleStyle = 'font-size:.55rem;line-height:1;';
										if (rawRole === 'admin') { roleLabel = 'ADMIN'; roleClass = 'bg-success'; }
										else if (rawRole === 'super admin' || rawRole === 'super_admin' || rawRole === 'superadmin') { roleLabel = 'SUPER ADMIN'; roleClass = 'bg-danger'; }
										else if (rawRole === 'waiter') { roleLabel = 'WAITER'; roleClass = 'bg-info'; }
										else if (rawRole === 'chef') { roleLabel = 'CHEF'; roleClass = 'bg-warning text-white'; roleStyle += 'background-color:{{ config('services.theme.color') }}!important;color:#fff!important;'; }
										var roleBadge = '<span class="badge ' + roleClass + ' text-uppercase" style="' + roleStyle + '">' + roleLabel + '</span>';
										var business = (data && data.company_name) ? ((window.AppUtils && AppUtils.escapeHtml) ? AppUtils.escapeHtml(String(data.company_name)) : String(data.company_name)) : '';
										// Active session icon with tooltip
										var activeIcon = '';
										if (data && (data.active_session === true || data.active_session === 1 || data.active_session === '1')) {
											var loginTime = data.login_time_display || '';
											var lastConn = data.last_connected_display || '';
											var activeSince = data.active_since || '';
											var sys = data.system_name ? ((window.AppUtils && AppUtils.escapeHtml) ? AppUtils.escapeHtml(String(data.system_name)) : String(data.system_name)) : '';
											// Use HTML line breaks for tooltip while keeping display fields (local-time) intact
											// var titleHtml='';
											// var titleHtml = 'Last login: ' + (loginTime||'-')
											// 	+ '\nLast connected: ' + (lastConn||'-')
											// 	+ '\nActive since: ' + (activeSince||'-')
											// 	+ (sys ? ('\nSystem: ' + sys) : '');
											var titleHtml = 'Active since: ' + (activeSince||'-');
											activeIcon = '<i data-feather="user-check" class="ms-2 text-success" style="width:14px;height:14px;vertical-align:middle;" data-bs-toggle="tooltip" data-bs-html="true" title="' + titleHtml.replace(/"/g,'&quot;') + '"></i>';
										}
										// Permission summary pills (M/T/R/U) for USER role only
										var permPills = '';

										try {
											var counts = (data && data.perm_counts) ? data.perm_counts : null;
											var isUserRole = ['user', 'waiter', 'chef'].indexOf(rawRole) !== -1;

											if (isUserRole && counts) {
												try { console.debug('[UserGrid] perm_counts', { id: data.id, name: data.name, counts: counts }); } catch(_){ }
												function pill(txt){ return '<span class="badge rounded-pill me-1" style="background:#3b82f6;color:#ffffff;padding:4px 10px;border-radius:16px;font-weight:600;font-size:.75rem;line-height:1;display:inline-flex;align-items:center;height:auto;">'+ txt +'</span>'; }
												var m = parseInt(counts.M||0,10), t = parseInt(counts.T||0,10), r = parseInt(counts.R||0,10), u = parseInt(counts.U||0,10);
												if (m>0) { permPills += pill('M'+m); }
												if (t>0) { permPills += pill('T'+t); }
												if (r>0) { permPills += pill('R'+r); }
												if (u>0) { permPills += pill('U'+u); }
											}
										} catch(_) {}

										var nameRow = '<div>' + name + inactivePill + lockIcon + activeIcon + '</div>';
										var badgesRow = '<div class="d-flex align-items-center gap-2 mt-1">' + roleBadge + (permPills ? ('<span class="ms-2">'+ permPills +'</span>') : '') + '</div>';
										return '<div class="flex-column">' + nameRow + badgesRow + '</div>';
									} catch (e) {
										return '<div>' + ((data && data.name) ? String(data.name) : '') + '</div>';
									}
								}
							},
							{ field: 'email', headerText: 'Email', minWidth: 200, width: 220 },
							{ field: 'phone', headerText: 'Mobile', width: 150 },
							{ field: 'company_column_text', headerText: 'Business', minWidth: 180, width: 220, visible: true,
								valueAccessor: function(field, data){
									var base = '';
									if (data && typeof data.company_column_text === 'string' && data.company_column_text.trim().length) {
										base = data.company_column_text;
									} else if (data && typeof data.company_name === 'string' && data.company_name.trim().length) {
										base = data.company_name;
									}
									return base;
								},
								template: function(data){
									var assignedRaw = (data && Array.isArray(data.super_businesses)) ? data.super_businesses : [];
									var assignedNamesRaw = (data && Array.isArray(data.super_business_names)) ? data.super_business_names : [];
									var roleLower = (data && data.role) ? String(data.role).trim().toLowerCase() : '';
									var isSuperAdminRow = (roleLower === 'super admin' || roleLower === 'super_admin' || roleLower === 'superadmin');
									var businessIdRaw = (data && data.company_id != null) ? String(data.company_id) : '';
									var businessNameRaw = (data && data.company_name) ? String(data.company_name) : '';
									var businessLogoRaw = (data && data.company_logo_url) ? String(data.company_logo_url) : '';
									var codeBadge = '';
									if (!isSuperAdminRow) {
										var rawCode = (data && typeof data.company_code === 'string') ? data.company_code.trim() : '';
										if (rawCode) {
											var safeCode = (window.AppUtils && AppUtils.escapeHtml) ? AppUtils.escapeHtml(rawCode) : rawCode;
											var encodedCode = encodeURIComponent(rawCode);
											var copyCall = 'window.UserGridHelpers && window.UserGridHelpers.copyBusinessCode && window.UserGridHelpers.copyBusinessCode(this)';
											codeBadge = '<span class="badge rounded-pill" style="border:1px solid #3b82f6;color:#1d4ed8;background-color:#eff6ff;padding:2px 8px;font-weight:600;cursor:pointer;user-select:none;" title="Click to copy" data-business-code="' + encodedCode + '" onclick="' + copyCall + '">' + safeCode + '</span>';
										}
									}

									var assignedBusinesses = assignedRaw
										.map(function(item){
											if (!item) { return null; }

											var label = String(item.name || '').trim();

											if (!label) { return null; }

											if (window.AppUtils && AppUtils.escapeHtml) {
												label = AppUtils.escapeHtml(label);
											}

											return {
												name: label,
												is_active: (item.is_active === true || item.is_active === 1 || item.is_active === '1'),
												is_locked: (item.is_locked === true || item.is_locked === 1 || item.is_locked === '1')
											};
										})
										.filter(function(item){ return item !== null; });

									if (assignedBusinesses.length) {
										var maxVisible = 2;

										var tooltipPlain = assignedNamesRaw
											.filter(function(name){ return typeof name === 'string' && name.trim().length; })
											.map(function(name){ return name.trim(); })
											.join(', ');

										var tooltipAttr = '';

										if (tooltipPlain) {
											var tooltipEscaped = (window.AppUtils && AppUtils.escapeHtml) ? AppUtils.escapeHtml(tooltipPlain) : tooltipPlain;
											tooltipAttr = ' data-bs-toggle="tooltip" title="' + tooltipEscaped.replace(/"/g,'&quot;') + '"';
										}

										var htmlParts = assignedBusinesses.slice(0, maxVisible).map(function(businessItem){
											var badges = '';

											if (!businessItem.is_active) {
												badges += '<span class="super-business-status super-business-status--inactive">Inactive</span>';
											}

											if (businessItem.is_locked) {
												badges += '<i data-feather="lock" class="ms-1 text-warning" style="width:12px;height:12px;vertical-align:middle;" title="Locked"></i>';
											}

											return '<span class="super-business-pill">' + businessItem.name + badges + '</span>';
										});

										if (assignedBusinesses.length > maxVisible) {
											var extra = assignedBusinesses.length - maxVisible;

											htmlParts.push('<span class="super-business-pill super-business-pill--more"' + tooltipAttr + '>+' + extra + ' more</span>');
										}

										var pillsHtml = '<div class="d-flex flex-wrap align-items-center gap-1">' + htmlParts.join('') + '</div>';
										var metaRow = codeBadge ? ('<div class="d-flex align-items-center gap-2 flex-wrap" style="margin-top:4px;">' + codeBadge + '</div>') : '';
										return '<div class="d-flex flex-column align-items-start">' + pillsHtml + metaRow + '</div>';
									}

									var business = (data && data.company_name) ? (AppUtils.escapeHtml ? AppUtils.escapeHtml(String(data.company_name)) : String(data.company_name)) : '';
									var hasBusiness = !!(data && data.company_id);

									if (!hasBusiness || !business) {
										return '<div>-</div>';
									}

									var isActive = data && (data.company_is_active === true || data.company_is_active === 1 || data.company_is_active === '1');
									var isLocked = data && (data.company_is_locked === true || data.company_is_locked === 1 || data.company_is_locked === '1');
									var inactivePill = '';

									if (!isActive) {
										inactivePill = '<span style="background-color:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:5px;font-size:10px;font-weight:500;margin-left:8px;height:16px;line-height:16px;display:inline-flex;align-items:center;">Inactive</span>';
									}

									var lockIcon = '';

									if (isLocked) {
										lockIcon = '<i data-feather="lock" class="ms-2 text-warning" style="width:14px;height:14px;vertical-align:middle;" title="Locked"></i>';
									}
									var nameRow = '<div class="d-flex align-items-center gap-2">'
										+ '<span>' + business + inactivePill + lockIcon + '</span>'
									+ '</div>';
									var codeRow = codeBadge ? ('<div class="d-flex align-items-center gap-2" style="margin-top:4px;">' + codeBadge + '</div>') : '';
									return '<div class="d-flex flex-column align-items-start">' + nameRow + codeRow + '</div>';
								},
								printTemplate: function(args){
									var text = (args && args.company_column_text) ? String(args.company_column_text) : '';

									if (!text) {
										text = (args && args.company_name) ? String(args.company_name) : '';
									}

									if (!text) { return '<span>-</span>'; }

									if (window.AppUtils && AppUtils.escapeHtml) {
										text = AppUtils.escapeHtml(text);
									}

									return '<span>' + text + '</span>';
								}
							},
							{ field: 'role', headerText: 'Role', width: 140, textAlign: 'Center', visible: false },
							{ field: 'active_since', headerText: 'Active Since', width: 150, textAlign: 'Center', visible: false },
							{ field: 'status', headerText: 'Active', width: 100, textAlign: 'Center', visible: false, showInColumnChooser: showColumnChooser,
							  template: function(data){
								var active = data && (data.status === true || data.status === 1 || data.status === '1');
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
							{ field: 'created_at', headerText: 'Created At', width: 200, type: 'datetime',
								format: { type: 'datetime', format: 'dd-MMM-yyyy hh:mm a' }, showInColumnChooser: showColumnChooser,
								customAttributes: { class: 'hide-on-small-screen' }
							},
							{ field: 'updated_at', headerText: 'Updated At', width: 200, type: 'datetime', visible: false,
								format: { type: 'datetime', format: 'dd-MMM-yyyy hh:mm a' }, showInColumnChooser: showColumnChooser,
								customAttributes: { class: 'hide-on-small-screen' }
							},
							{
								headerText: 'Manage Records', width: 240, textAlign: 'Center', showInColumnChooser: false,
								commands: COMMANDS
							}
						],
							commandClick: function(args){
							try {
								var target = args.target || (args.originalEvent ? args.originalEvent.target : null);
								var row = (args.rowData || {});

							if (target && target.closest && target.closest('.cmd-delete')) {
								if (!CAN_DELETE_USERS) { return; }
								AppUtils.Actions.deleteResource({
									url: removeUrl,
									method: 'POST',
									data: { id: (row && row.id) },
									confirm: { title: 'Are you sure?', text: 'Delete This Data?' },
									successMessage: 'User deleted successfully.',
									onSuccess: function(){ reloadUserGrid(); }
								});
								return;
							}

								if (target && target.closest && target.closest('.cmd-super-business')) {
									if (!IS_ROOT_SUPER_USER) { return; }
									var roleForBusinesses = (row && row.role) ? String(row.role).trim().toLowerCase() : '';
									var canManageRole = (roleForBusinesses === 'super admin' || roleForBusinesses === 'super_admin' || roleForBusinesses === 'superadmin');
									if (!canManageRole) { return; }
									try { openSuperBusinessModal(row); } catch(_) {}
									return;
								}

								if (target && target.closest && target.closest('.cmd-view')) {
									populateFormForView(row);
									openModal();
									return;
								}

								if (target && target.closest && target.closest('.cmd-edit')) {
									// Only super admin can edit. Also do not allow editing super admin user rows.
									if (!IS_SUPER_ADMIN) { return; }
									var roleLower2 = (row && row.role) ? String(row.role).trim().toLowerCase() : '';
									var isSuperAdminRow = (roleLower2 === 'super admin' || roleLower2 === 'super_admin' || roleLower2 === 'superadmin');
									var canEdit = !isSuperAdminRow && (row && (row.status === true || row.status === 1 || row.status === '1')) && !(row && (row.is_locked === true || row.is_locked === 1 || row.is_locked === '1'));
									if (!canEdit) { return; }
									populateFormForEdit(row);
									openModal();
									return;
								}

								if (target && target.closest && target.closest('.cmd-inactive')) {
									AppUtils.Actions.deleteResource({
										url: setActiveUrl,
										method: 'POST',
										data: { id: (row && row.id), is_active: 0 },
										confirm: { title: 'Are you sure?', text: 'Mark this user as Inactive?', confirmButtonText: 'Yes, mark inactive' },
										successMessage: 'User marked inactive successfully.',
										onSuccess: function(){ reloadUserGrid(); }
									});
									return;
								}

								if (target && target.closest && target.closest('.cmd-activate')) {
									AppUtils.Actions.deleteResource({
										url: setActiveUrl,
										method: 'POST',
										data: { id: (row && row.id), is_active: 1 },
										confirm: { title: 'Are you sure?', text: 'Activate this user?', confirmButtonText: 'Yes, activate' },
										successMessage: 'User activated successfully.',
										onSuccess: function(){ reloadUserGrid(); }
									});
									return;
								}

								if (target && target.closest && target.closest('.cmd-lock')) {
									var active = row && (row.status === true || row.status === 1 || row.status === '1');
									if (!active) { return; }
									AppUtils.Actions.deleteResource({
										url: setLockedUrl,
										method: 'POST',
										data: { id: (row && row.id), is_locked: 1 },
										confirm: { title: 'Are you sure?', text: 'Lock this user?', confirmButtonText: 'Yes, lock' },
										successMessage: 'User locked successfully.',
										onSuccess: function(){ reloadUserGrid(); }
									});
									return;
								}

									if (target && target.closest && target.closest('.cmd-unlock')) {
									var active2 = row && (row.status === true || row.status === 1 || row.status === '1');
									if (!active2) { return; }
									AppUtils.Actions.deleteResource({
										url: setLockedUrl,
										method: 'POST',
										data: { id: (row && row.id), is_locked: 0 },
										confirm: { title: 'Are you sure?', text: 'Unlock this user?', confirmButtonText: 'Yes, unlock' },
										successMessage: 'User unlocked successfully.',
										onSuccess: function(){ reloadUserGrid(); }
									});
									return;
								}

									if (target && target.closest && target.closest('.cmd-terminate')) {
										if (!(row && (row.active_session === true || row.active_session === 1 || row.active_session === '1'))) { return; }
										var uid = row && row.id; var sid = row && row.session_id;
										if (!uid || !sid) { return; }
										AppUtils.Actions.deleteResource({
											url: revokeSessionUrl,
											method: 'POST',
											data: { user_id: uid, session_id: sid },
											confirm: { title: 'Terminate Session?', text: 'This will immediately log the user out from their current device.', confirmButtonText: 'Yes, terminate' },
											successMessage: 'Active session terminated.',
											onSuccess: function(){ reloadUserGrid(); }
										});
										return;
									}

									// Setup permissions
									if (target && target.closest && target.closest('.cmd-setup')) {
										try {
											openPermissionModal(row);
										} catch(e) {}
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
							reloadUserGrid();
						},
						pdfQueryCellInfo: AppUtils.GridHelpers.pdfQueryCellInfoFactory({ 
							dateFields: DATE_FIELDS,
							sanitizeFields: ['address','company_column_text'],
							sanitizeOptions: { stripHtml: true, replaceNewlinesWith: ' ' }
						}),
						excelQueryCellInfo: AppUtils.GridHelpers.excelQueryCellInfoFactory({ 
							dateFields: DATE_FIELDS,
							sanitizeFields: ['address','company_column_text'],
							sanitizeOptions: { stripHtml: true, replaceNewlinesWith: ' ' }
						}),
						beforePdfExport: AppUtils.GridHelpers.beforePdfExportFactory({ fontBase64: (window.PDF_EXPORT_FONT_BASE64 || null), fontSize: 10 }),
						actionBegin: AppUtils.GridHelpers.actionBeginSearchKeyUpdaterFactory(() => grid),
						queryCellInfo: AppUtils.GridHelpers.queryCellInfoHighlighterFactory(() => grid),
						toolbarClick: AppUtils.GridHelpers.toolbarClickFactory(() => grid),
							rowDataBound: AppUtils.GridHelpers.rowDataBoundToggleActionsFactory({ isActiveField: 'status' }),
							dataBound: AppUtils.GridHelpers.dataBoundAutoFitFactory(() => grid),
					}
				));

				grid._autofitEnabled = false;
				grid.appendTo('#userGrid');

				function reloadUserGrid(){
					if (!grid) return Promise.resolve();

					return AppUtils.GridHelpers.loadDataToGrid(grid, listUrl, {
						dateFields: DATE_FIELDS,
						onAfter(parsed){
							grid.dataSource = filterExcludedSuperUsers(parsed);
						}
					});
				}

				window.userGrid = grid;
				window.AppPage = window.AppPage || {};
				window.AppPage.onRefresh = function(){
					if (window.userGrid && listUrl) {
						reloadUserGrid();
					}
				};

				// Business header moved to topbar header partial

					const prevRowDataBound = grid.rowDataBound;
				grid.rowDataBound = function(args){
					if (typeof prevRowDataBound === 'function') prevRowDataBound.call(this, args);
					try {
						var data = (args && args.data) ? args.data : {};
						var rowEl = (args && args.row) ? args.row : null;
						if (!rowEl) return;
						var active = data && (data.status === true || data.status === 1 || data.status === '1');
						var locked = data && (data.is_locked === true || data.is_locked === 1 || data.is_locked === '1');

						var lockBtn = rowEl.querySelector('.cmd-lock');
						var unlockBtn = rowEl.querySelector('.cmd-unlock');
						var editBtn = rowEl.querySelector('.cmd-edit');
						var terminateBtn = rowEl.querySelector('.cmd-terminate');
						var deleteBtn = rowEl.querySelector('.cmd-delete');
						var setupBtn = rowEl.querySelector('.cmd-setup');
						var superBusinessBtn = rowEl.querySelector('.cmd-super-business');


						if (lockBtn) lockBtn.style.display = (active && !locked) ? '' : 'none';
						if (unlockBtn) unlockBtn.style.display = (active && locked) ? '' : 'none';
						var roleLower = (data && data.role) ? String(data.role).trim().toLowerCase() : '';
						var isSuperAdmin = (roleLower === 'super admin' || roleLower === 'super_admin' || roleLower === 'superadmin');
						var isChef = (roleLower === 'chef');
						if (editBtn) { editBtn.style.display = (IS_SUPER_ADMIN && active && !locked && !isSuperAdmin) ? '' : 'none'; }
						if (terminateBtn) { var showTerm = (data && (data.active_session === true || data.active_session === 1 || data.active_session === '1')); terminateBtn.style.display = showTerm ? '' : 'none'; }
						if (deleteBtn) { deleteBtn.style.display = (!CAN_DELETE_USERS || !active || locked) ? 'none' : ''; }
						if (superBusinessBtn) {
							var isRootTarget = (Number(data && data.id) === Number(CURRENT_USER_ID));
							superBusinessBtn.style.display = (IS_ROOT_SUPER_USER && isSuperAdmin && !isRootTarget) ? '' : 'none';
							var assignedCount = 0;
							if (Array.isArray(data && data.super_businesses)) {
								assignedCount = data.super_businesses.length;
							} else if (typeof data.super_business_count !== 'undefined') {
								var parsed = Number(data.super_business_count);
								assignedCount = Number.isFinite(parsed) ? parsed : 0;
							}
							var hasAssignments = assignedCount > 0;
							var superBusinessIcon = superBusinessBtn.querySelector('.e-icons');
							superBusinessBtn.classList.remove('text-success');
							if (superBusinessIcon) { superBusinessIcon.classList.remove('text-success'); }
							if (hasAssignments) {
								superBusinessBtn.classList.add('text-success');
								if (superBusinessIcon) { superBusinessIcon.classList.add('text-success'); }
								superBusinessBtn.setAttribute('title', 'Manage Assigned Businesses');
							} else {
								superBusinessBtn.setAttribute('title', 'Assign Businesses');
							}
						}
						if (setupBtn) { setupBtn.style.display = ['user', 'waiter', 'chef'].indexOf(String(roleLower)) !== -1 ? '' : 'none'; }


						if (lockBtn) {
							lockBtn.classList.add('text-warning');
							var icon = lockBtn.querySelector('.e-icons');
							if (icon) icon.classList.add('text-warning');
						}
					} catch(_) {}
				};

				const prevDataBound = grid.dataBound;
				grid.dataBound = function(e){
					if (typeof prevDataBound === 'function') prevDataBound.call(this, e);
					if (window.feather) { feather.replace(); }
					try {
						var el = this.element || document;
						var tips = el.querySelectorAll('[data-bs-toggle="tooltip"]');
						Array.prototype.forEach.call(tips, function(t){
							bootstrap.Tooltip.getOrCreateInstance(t, { container: 'body', trigger: 'hover focus' });
						});
						// Log a sample of records to verify perm_counts delivery from server
						var recs = (this.getCurrentViewRecords ? this.getCurrentViewRecords() : (this.dataSource || [])) || [];
						try {
							console.debug('[UserGrid] dataBound sample', recs.slice(0,5).map(function(r){ return { id: r.id, name: r.name, role: r.role, perm_counts: r.perm_counts }; }));
						} catch(_) {}
					} catch(_) {}
				};

				function parseJsonResponse(resp){
					return resp.json().catch(function(){ return {}; }).then(function(body){
						if (!resp.ok) {
							var message = (body && body.message) ? body.message : 'Request failed.';
							var error = new Error(message);
							error.response = body;
							error.status = resp.status;
							throw error;
						}
						return body;
					});
				}

				function getJson(url){
					return fetch(url, { headers: { 'Accept': 'application/json' } }).then(parseJsonResponse);
				}

				function postJson(url, payload){
					var headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
					try {
						var csrf = document.querySelector('meta[name="csrf-token"]').content;
						if (csrf) headers['X-CSRF-TOKEN'] = csrf;
					} catch(_) {}
					return fetch(url, {
						method: 'POST',
						headers: headers,
						body: JSON.stringify(payload || {})
					}).then(parseJsonResponse);
				}

				// Super user business assignment modal (only actionable for fixed super users)
				const superBusinessModalEl = document.getElementById('superBusinessModal');
				const superBusinessModalTitleEl = document.getElementById('superBusinessModalTitle');
				const superBusinessUserNameEl = document.getElementById('superBusinessUserName');
				const superBusinessListEl = document.getElementById('superBusinessList');
				const superBusinessEmptyEl = document.getElementById('superBusinessEmpty');
				const superBusinessLoadingEl = document.getElementById('superBusinessLoading');
				const superBusinessSearchEl = document.getElementById('superBusinessSearch');
				const superBusinessClearSearchBtn = document.getElementById('superBusinessClearSearch');
				const superBusinessErrorEl = document.getElementById('superBusinessError');
				const superBusinessSelectionCounterEl = document.getElementById('superBusinessSelectionCounter');
				const superBusinessSaveBtn = document.getElementById('superBusinessSaveBtn');
				const superBusinessSaveDefaultLabel = superBusinessSaveBtn ? superBusinessSaveBtn.innerHTML : 'Save Access';
				let superBusinessCtx = {
					userId: null,
					rows: [],
					selected: new Set(),
					filterText: ''
				};

				function resetSuperBusinessModalState(){
					superBusinessCtx = {
						userId: null,
						rows: [],
						selected: new Set(),
						filterText: ''
					};
					if (superBusinessListEl) superBusinessListEl.innerHTML = '';
					if (superBusinessEmptyEl) superBusinessEmptyEl.classList.add('d-none');
					if (superBusinessLoadingEl) superBusinessLoadingEl.classList.add('d-none');
					if (superBusinessErrorEl) {
						superBusinessErrorEl.classList.add('d-none');
						superBusinessErrorEl.textContent = '';
					}
					if (superBusinessSelectionCounterEl) superBusinessSelectionCounterEl.textContent = '0 selected';
					if (superBusinessSearchEl) superBusinessSearchEl.value = '';
					if (superBusinessSaveBtn) {
						superBusinessSaveBtn.disabled = true;
						superBusinessSaveBtn.innerHTML = superBusinessSaveDefaultLabel;
					}
				}

				function setSuperBusinessLoading(isLoading){
					if (!superBusinessLoadingEl) return;
					superBusinessLoadingEl.classList.toggle('d-none', !isLoading);
				}

				function updateSuperBusinessSelectionCounter(){
					if (!superBusinessSelectionCounterEl) return;
					superBusinessSelectionCounterEl.textContent = superBusinessCtx.selected.size + ' selected';
				}

				function getFilteredSuperBusinessRows(){
					var filter = (superBusinessCtx.filterText || '').trim().toLowerCase();
					var list = Array.isArray(superBusinessCtx.rows) ? superBusinessCtx.rows.slice() : [];
					if (filter) {
						list = list.filter(function(row){
							if (!row) return false;
							var name = String(row.name || '').toLowerCase();
							var code = String(row.code || '').toLowerCase();
							return name.indexOf(filter) !== -1 || code.indexOf(filter) !== -1;
						});
					}
					return list.sort(function(a,b){
						var idA = Number(a && a.id);
						var idB = Number(b && b.id);
						var aSel = Number.isFinite(idA) && superBusinessCtx.selected.has(idA);
						var bSel = Number.isFinite(idB) && superBusinessCtx.selected.has(idB);
						if (aSel !== bSel) { return aSel ? -1 : 1; }
						var nameA = String(a && a.name ? a.name : '').toLowerCase();
						var nameB = String(b && b.name ? b.name : '').toLowerCase();
						return nameA.localeCompare(nameB);
					});
				}

				function renderSuperBusinessList(){
					if (!superBusinessListEl) return;
					var filtered = getFilteredSuperBusinessRows();
					superBusinessListEl.innerHTML = '';
					if (!filtered.length) {
						if (superBusinessEmptyEl) superBusinessEmptyEl.classList.remove('d-none');
					} else {
						if (superBusinessEmptyEl) superBusinessEmptyEl.classList.add('d-none');
					}

					var escapeHtml = function(val){
						var str = (val === undefined || val === null) ? '' : String(val);
						return (window.AppUtils && AppUtils.escapeHtml) ? AppUtils.escapeHtml(str) : str;
					};

					var fragment = document.createDocumentFragment();
					filtered.forEach(function(row){
						if (!row) return;
						var id = Number(row.id);
						if (!Number.isFinite(id)) return;

						var wrapper = document.createElement('label');
						wrapper.className = 'list-group-item d-flex align-items-start gap-3 flex-wrap';
						wrapper.setAttribute('for', 'super-business-' + id);

						var checkbox = document.createElement('input');
						checkbox.type = 'checkbox';
						checkbox.id = 'super-business-' + id;
						checkbox.className = 'form-check-input mt-1';
						checkbox.dataset.businessId = id;
						checkbox.checked = superBusinessCtx.selected.has(id);
						checkbox.addEventListener('change', function(){
							if (this.checked) {
								superBusinessCtx.selected.add(id);
							} else {
								superBusinessCtx.selected.delete(id);
							}
							updateSuperBusinessSelectionCounter();
						});

						var body = document.createElement('div');
						body.className = 'flex-grow-1';
						var badges = [];
						var isActive = (row.is_active === true || row.is_active === 1 || row.is_active === '1');
						var isLocked = (row.is_locked === true || row.is_locked === 1 || row.is_locked === '1');
						if (!isActive) {
							badges.push('<span class="badge rounded-pill" style="background:#fee2e2;color:#991b1b;">Inactive</span>');
						}
						if (isLocked) {
							badges.push('<span class="badge rounded-pill" style="background:#fef3c7;color:#92400e;">Locked</span>');
						}

						var summaryHtml = '<div class="fw-semibold">' + escapeHtml(row.name || 'Business') +
							' <span class="text-muted">(' + escapeHtml(row.code || '-') + ')</span></div>';
						if (badges.length) {
							summaryHtml += '<div class="d-flex flex-wrap gap-2 mt-1">' + badges.join('') + '</div>';
						}
						if (row.assigned_at) {
							summaryHtml += '<div class="text-muted small">Last assigned: ' + escapeHtml(row.assigned_at) +
								(row.assigned_by ? ' by ' + escapeHtml(row.assigned_by) : '') + '</div>';
						}

						body.innerHTML = summaryHtml;
						wrapper.appendChild(checkbox);
						wrapper.appendChild(body);
						fragment.appendChild(wrapper);
					});

					superBusinessListEl.appendChild(fragment);
					updateSuperBusinessSelectionCounter();
				}

				function showSuperBusinessError(message){
					if (!superBusinessErrorEl) return;
					if (!message) {
						superBusinessErrorEl.classList.add('d-none');
						superBusinessErrorEl.textContent = '';
						return;
					}
					superBusinessErrorEl.textContent = message;
					superBusinessErrorEl.classList.remove('d-none');
				}

				function openSuperBusinessModal(row){
					if (!IS_ROOT_SUPER_USER || !superBusinessModalEl) return;
					if (!row || !row.id) return;
					var roleLower = (row.role ? String(row.role).trim().toLowerCase() : '');
					var canManageRole = (roleLower === 'super admin' || roleLower === 'super_admin' || roleLower === 'superadmin');
					if (!canManageRole) {
						window.AppUtils.notify('Only Super Admin users can be assigned to businesses.', { type: 'info' });
						return;
					}
					resetSuperBusinessModalState();
					superBusinessCtx.userId = row.id;
					var displayName = row.name || 'User';
					if (superBusinessModalTitleEl) superBusinessModalTitleEl.textContent = 'Assign Businesses [' + displayName + ']';
					if (superBusinessUserNameEl) superBusinessUserNameEl.textContent = displayName;
					setSuperBusinessLoading(true);
					bootstrap.Modal.getOrCreateInstance(superBusinessModalEl).show();
					if (window.feather) { try { feather.replace(); } catch(_) {} }

					var url = superBusinessListUrl + '?user_id=' + encodeURIComponent(row.id);
					getJson(url).then(function(json){
						setSuperBusinessLoading(false);
						showSuperBusinessError('');
						var businesses = Array.isArray(json.businesses) ? json.businesses : [];
						superBusinessCtx.rows = businesses;
						var assignedIds = [];
						businesses.forEach(function(item){
							var id = Number(item && item.id);
							if (item && item.assigned && Number.isFinite(id)) {
								assignedIds.push(id);
							}
						});
						superBusinessCtx.selected = new Set(assignedIds);
						superBusinessCtx.filterText = '';
						if (superBusinessSearchEl) superBusinessSearchEl.value = '';
						renderSuperBusinessList();
						if (superBusinessSaveBtn) superBusinessSaveBtn.disabled = false;
					}).catch(function(err){
						setSuperBusinessLoading(false);
						var msg = err && err.message ? err.message : 'Unable to load business assignments.';
						showSuperBusinessError(msg);
						if (superBusinessListEl) {
							superBusinessListEl.innerHTML = '';
						}
						if (superBusinessSaveBtn) superBusinessSaveBtn.disabled = true;
					});
				}

				function setSuperBusinessSaveBusy(isBusy){
					if (!superBusinessSaveBtn) return;
					superBusinessSaveBtn.disabled = !!isBusy;
					superBusinessSaveBtn.innerHTML = isBusy
						? '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...'
						: superBusinessSaveDefaultLabel;
				}

				function saveSuperBusinessAssignments(){
					if (!IS_ROOT_SUPER_USER || !superBusinessCtx.userId) return;
					showSuperBusinessError('');
					setSuperBusinessSaveBusy(true);
					var payload = {
						user_id: superBusinessCtx.userId,
						company_ids: Array.from(superBusinessCtx.selected.values())
					};
					postJson(superBusinessSyncUrl, payload).then(function(json){
						setSuperBusinessSaveBusy(false);
						var ok = json && (json.success !== false);
						var msg = (json && json.message) ? json.message : (ok ? 'Business access updated successfully.' : 'Unable to update business access.');
						window.AppUtils.notify(msg, { type: ok ? 'success' : 'warning' });
						if (ok) {
							bootstrap.Modal.getOrCreateInstance(superBusinessModalEl).hide();
							try { reloadUserGrid(); } catch(_) {}
						}
					}).catch(function(err){
						setSuperBusinessSaveBusy(false);
						var msg = err && err.message ? err.message : 'Failed to update business access.';
						showSuperBusinessError(msg);
						window.AppUtils.notify(msg, { type: 'error' });
					});
				}

				if (superBusinessSaveBtn) {
					superBusinessSaveBtn.addEventListener('click', function(){ saveSuperBusinessAssignments(); });
				}

				if (superBusinessSearchEl) {
					superBusinessSearchEl.addEventListener('input', function(){
						superBusinessCtx.filterText = this.value || '';
						renderSuperBusinessList();
					});
				}

				if (superBusinessClearSearchBtn) {
					superBusinessClearSearchBtn.addEventListener('click', function(){
						if (superBusinessSearchEl) superBusinessSearchEl.value = '';
						superBusinessCtx.filterText = '';
						renderSuperBusinessList();
					});
				}

				if (superBusinessModalEl) {
					superBusinessModalEl.addEventListener('hidden.bs.modal', function(){ resetSuperBusinessModalState(); });
				}

				// Chef Setup Modal (for chef role users to assign menu/category/food type)
				const chefSetupModalEl = document.getElementById('chefSetupModal');
				const chefSetupModalTitleEl = document.getElementById('chefSetupModalTitle');
				const chefSetupUserNameEl = document.getElementById('chefSetupUserName');
				const chefSetupMenuListEl = document.getElementById('chefSetupMenuList');
				const chefSetupCategoryListEl = document.getElementById('chefSetupCategoryList');
				const chefSetupFoodTypeListEl = document.getElementById('chefSetupFoodTypeList');
				const chefSetupLoadingEl = document.getElementById('chefSetupLoading');
				const chefSetupErrorEl = document.getElementById('chefSetupError');
				const chefSetupSaveBtn = document.getElementById('chefSetupSaveBtn');
				const chefSetupSaveDefaultLabel = chefSetupSaveBtn ? chefSetupSaveBtn.innerHTML : 'Save Assignments';

				let chefSetupCtx = {
					userId: null,
					menus: [],
					categories: [],
					foodTypes: [],
					selectedMenus: new Set(),
					selectedCategories: new Set(),
					selectedFoodTypes: new Set()
				};

				function resetChefSetupModalState() {
					chefSetupCtx = {
						userId: null,
						menus: [],
						categories: [],
						foodTypes: [],
						selectedMenus: new Set(),
						selectedCategories: new Set(),
						selectedFoodTypes: new Set()
					};
					if (chefSetupMenuListEl) chefSetupMenuListEl.innerHTML = '';
					if (chefSetupCategoryListEl) chefSetupCategoryListEl.innerHTML = '';
					if (chefSetupFoodTypeListEl) chefSetupFoodTypeListEl.innerHTML = '';
					if (chefSetupLoadingEl) chefSetupLoadingEl.classList.add('d-none');
					if (chefSetupErrorEl) {
						chefSetupErrorEl.classList.add('d-none');
						chefSetupErrorEl.textContent = '';
					}
					if (chefSetupSaveBtn) {
						chefSetupSaveBtn.disabled = true;
						chefSetupSaveBtn.innerHTML = chefSetupSaveDefaultLabel;
					}
				}

				function setChefSetupLoading(isLoading) {
					if (!chefSetupLoadingEl) return;
					chefSetupLoadingEl.classList.toggle('d-none', !isLoading);
				}

				function showChefSetupError(message) {
					if (!chefSetupErrorEl) return;
					if (!message) {
						chefSetupErrorEl.classList.add('d-none');
						chefSetupErrorEl.textContent = '';
						return;
					}
					chefSetupErrorEl.textContent = message;
					chefSetupErrorEl.classList.remove('d-none');
				}

				function renderChefSetupList(listEl, items, selectedSet, type) {
					if (!listEl) return;
					listEl.innerHTML = '';
					if (!items.length) {
						listEl.innerHTML = '<div class="text-center text-muted py-3 small">No items available</div>';
						return;
					}

					var escapeHtml = function(val) {
						var str = (val === undefined || val === null) ? '' : String(val);
						return (window.AppUtils && AppUtils.escapeHtml) ? AppUtils.escapeHtml(str) : str;
					};

					var fragment = document.createDocumentFragment();
					items.forEach(function(item) {
						if (!item) return;
						var id = Number(item.id);
						if (!Number.isFinite(id)) return;

						var wrapper = document.createElement('label');
						wrapper.className = 'list-group-item d-flex align-items-center gap-2 py-2';
						wrapper.setAttribute('for', 'chef-' + type + '-' + id);

						var checkbox = document.createElement('input');
						checkbox.type = 'checkbox';
						checkbox.id = 'chef-' + type + '-' + id;
						checkbox.className = 'form-check-input m-0';
						checkbox.dataset.itemId = id;
						checkbox.dataset.itemType = type;
						checkbox.checked = selectedSet.has(id);
						checkbox.addEventListener('change', function() {
							if (this.checked) {
								selectedSet.add(id);
							} else {
								selectedSet.delete(id);
							}
						});

						var label = document.createElement('span');
						label.className = 'flex-grow-1';
						label.textContent = item.name || 'Item';

						wrapper.appendChild(checkbox);
						wrapper.appendChild(label);
						fragment.appendChild(wrapper);
					});
					listEl.appendChild(fragment);
				}

				function renderChefSetupLists() {
					chefSetupCtx.selectedMenus = new Set(chefSetupCtx.menus.filter(function(m) { return m.assigned; }).map(function(m) { return Number(m.id); }));
					chefSetupCtx.selectedCategories = new Set(chefSetupCtx.categories.filter(function(c) { return c.assigned; }).map(function(c) { return Number(c.id); }));
					chefSetupCtx.selectedFoodTypes = new Set(chefSetupCtx.foodTypes.filter(function(f) { return f.assigned; }).map(function(f) { return Number(f.id); }));

					renderChefSetupList(chefSetupMenuListEl, chefSetupCtx.menus, chefSetupCtx.selectedMenus, 'menu');
					renderChefSetupList(chefSetupCategoryListEl, chefSetupCtx.categories, chefSetupCtx.selectedCategories, 'category');
					renderChefSetupList(chefSetupFoodTypeListEl, chefSetupCtx.foodTypes, chefSetupCtx.selectedFoodTypes, 'foodtype');
				}

				function openChefSetupModal(row) {
					if (!chefSetupModalEl) return;
					if (!row || !row.id) return;

					var roleLower = (row.role ? String(row.role).trim().toLowerCase() : '');
					if (roleLower !== 'chef') {
						window.AppUtils.notify('Only chef users can have menu assignments.', { type: 'info' });
						return;
					}

					resetChefSetupModalState();
					chefSetupCtx.userId = row.id;

					var displayName = row.name || 'Chef';
					if (chefSetupModalTitleEl) chefSetupModalTitleEl.textContent = 'Chef Setup [' + displayName + ']';
					if (chefSetupUserNameEl) chefSetupUserNameEl.textContent = displayName;

					setChefSetupLoading(true);
					bootstrap.Modal.getOrCreateInstance(chefSetupModalEl).show();

					var url = chefAssignmentsListUrl + '?user_id=' + encodeURIComponent(row.id);
					getJson(url).then(function(json) {
						setChefSetupLoading(false);
						showChefSetupError('');

						if (!json.success) {
							showChefSetupError(json.message || 'Failed to load assignments.');
							if (chefSetupSaveBtn) chefSetupSaveBtn.disabled = true;
							return;
						}

						chefSetupCtx.menus = Array.isArray(json.menus) ? json.menus : [];
						chefSetupCtx.categories = Array.isArray(json.categories) ? json.categories : [];
						chefSetupCtx.foodTypes = Array.isArray(json.food_types) ? json.food_types : [];

						renderChefSetupLists();
						if (chefSetupSaveBtn) chefSetupSaveBtn.disabled = false;
					}).catch(function(err) {
						setChefSetupLoading(false);
						var msg = err && err.message ? err.message : 'Unable to load chef assignments.';
						showChefSetupError(msg);
						if (chefSetupSaveBtn) chefSetupSaveBtn.disabled = true;
					});
				}

				function setChefSetupSaveBusy(isBusy) {
					if (!chefSetupSaveBtn) return;
					chefSetupSaveBtn.disabled = !!isBusy;
					chefSetupSaveBtn.innerHTML = isBusy
						? '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...'
						: chefSetupSaveDefaultLabel;
				}

				function saveChefSetupAssignments() {
					if (!chefSetupCtx.userId) return;
					showChefSetupError('');
					setChefSetupSaveBusy(true);

					var payload = {
						user_id: chefSetupCtx.userId,
						menu_ids: Array.from(chefSetupCtx.selectedMenus.values()),
						category_ids: Array.from(chefSetupCtx.selectedCategories.values()),
						food_type_ids: Array.from(chefSetupCtx.selectedFoodTypes.values())
					};

					postJson(chefAssignmentsSyncUrl, payload).then(function(json) {
						setChefSetupSaveBusy(false);
						var ok = json && (json.success !== false);
						var msg = (json && json.message) ? json.message : (ok ? 'Chef assignments updated successfully.' : 'Unable to update assignments.');
						window.AppUtils.notify(msg, { type: ok ? 'success' : 'warning' });
						if (ok) {
							bootstrap.Modal.getOrCreateInstance(chefSetupModalEl).hide();
							try { reloadUserGrid(); } catch(_) {}
						}
					}).catch(function(err) {
						setChefSetupSaveBusy(false);
						var msg = err && err.message ? err.message : 'Failed to save chef assignments.';
						showChefSetupError(msg);
						window.AppUtils.notify(msg, { type: 'error' });
					});
				}

				if (chefSetupSaveBtn) {
					chefSetupSaveBtn.addEventListener('click', function() { saveChefSetupAssignments(); });
				}

				if (chefSetupModalEl) {
				chefSetupModalEl.addEventListener('hidden.bs.modal', function() { resetChefSetupModalState(); });
			}

			// Waiter Setup Modal (for waiter role users to assign section/table)
			const waiterSetupModalEl = document.getElementById('waiterSetupModal');
			const waiterSetupModalTitleEl = document.getElementById('waiterSetupModalTitle');
			const waiterSetupUserNameEl = document.getElementById('waiterSetupUserName');
			const waiterSetupSectionSelectEl = document.getElementById('waiterSetupSectionSelect');
			const waiterSetupTableListEl = document.getElementById('waiterSetupTableList');
			const waiterSetupLoadingEl = document.getElementById('waiterSetupLoading');
			const waiterSetupErrorEl = document.getElementById('waiterSetupError');
			const waiterSetupSaveBtn = document.getElementById('waiterSetupSaveBtn');
			const waiterSetupClearBtn = document.getElementById('waiterSetupClearBtn');
			const waiterSetupSaveDefaultLabel = waiterSetupSaveBtn ? waiterSetupSaveBtn.innerHTML : 'Save Assignments';


			let waiterSetupCtx = {
				userId: null,
				companyId: null,
				sections: [],
				tables: [],
				selectedSectionId: null,
				selectedTableIds: new Set(),
				hasExistingAssignments: false
			};

			function resetWaiterSetupModalState() {
				waiterSetupCtx.userId = null;
				waiterSetupCtx.companyId = null;
				waiterSetupCtx.sections = [];
				waiterSetupCtx.tables = [];
				waiterSetupCtx.selectedSectionId = null;
				waiterSetupCtx.selectedTableIds.clear();
				waiterSetupCtx.hasExistingAssignments = false;
				if (waiterSetupSectionSelectEl) { waiterSetupSectionSelectEl.innerHTML = '<option value="">Select Section</option>'; }
				if (waiterSetupTableListEl) { waiterSetupTableListEl.innerHTML = ''; }
				showWaiterSetupError('');
				setWaiterSetupLoading(false);
				if (waiterSetupSaveBtn) { waiterSetupSaveBtn.disabled = true; waiterSetupSaveBtn.innerHTML = waiterSetupSaveDefaultLabel; }
				if (waiterSetupClearBtn) { waiterSetupClearBtn.style.display = 'none'; }
			}

			function setWaiterSetupLoading(show) {
				if (waiterSetupLoadingEl) waiterSetupLoadingEl.classList.toggle('d-none', !show);
				if (waiterSetupSectionSelectEl) waiterSetupSectionSelectEl.disabled = !!show;
			}

			function showWaiterSetupError(msg) {
				if (!waiterSetupErrorEl) return;
				waiterSetupErrorEl.textContent = msg || '';
				waiterSetupErrorEl.classList.toggle('d-none', !msg);
			}

			function renderWaiterSetupSections() {
				if (!waiterSetupSectionSelectEl) return;
				waiterSetupSectionSelectEl.innerHTML = '<option value="">Select Section *</option>';
				waiterSetupCtx.sections.forEach(function(s) {
					var opt = document.createElement('option');
					opt.value = s.id;
					opt.textContent = s.name;
					if (s.assigned || s.id === waiterSetupCtx.selectedSectionId) {
						opt.selected = true;
						waiterSetupCtx.selectedSectionId = s.id;
					}
					waiterSetupSectionSelectEl.appendChild(opt);
				});
			}

			function renderWaiterSetupTables() {
				if (!waiterSetupTableListEl) return;
				waiterSetupTableListEl.innerHTML = '';
				if (!waiterSetupCtx.tables || waiterSetupCtx.tables.length === 0) {
					waiterSetupTableListEl.innerHTML = '<div class="text-muted text-center py-3 small">No tables in this section (or optional)</div>';
					return;
				}
				waiterSetupCtx.tables.forEach(function(t) {
					var label = document.createElement('label');
					label.className = 'list-group-item list-group-item-action d-flex align-items-center gap-2';
					var checkbox = document.createElement('input');
					checkbox.type = 'checkbox';
					checkbox.className = 'form-check-input m-0';
					checkbox.value = t.id;
					checkbox.checked = t.assigned || waiterSetupCtx.selectedTableIds.has(t.id);
					if (checkbox.checked) waiterSetupCtx.selectedTableIds.add(t.id);
					checkbox.addEventListener('change', function() {
						if (this.checked) {
							waiterSetupCtx.selectedTableIds.add(t.id);
						} else {
							waiterSetupCtx.selectedTableIds.delete(t.id);
						}
					});
					var span = document.createElement('span');
					span.textContent = t.name;
					label.appendChild(checkbox);
					label.appendChild(span);
					waiterSetupTableListEl.appendChild(label);
				});
			}

			function loadTablesForSection(sectionId) {
				waiterSetupCtx.selectedTableIds.clear();
				if (!sectionId) {
					waiterSetupCtx.tables = [];
					renderWaiterSetupTables();
					if (waiterSetupSaveBtn) waiterSetupSaveBtn.disabled = true;
					return;
				}
				var url = waiterTablesForSectionUrl + '?section_id=' + encodeURIComponent(sectionId) + '&company_id=' + encodeURIComponent(waiterSetupCtx.companyId);
				getJson(url).then(function(json) {
					if (json.success && Array.isArray(json.tables)) {
						waiterSetupCtx.tables = json.tables;
					} else {
						waiterSetupCtx.tables = [];
					}
					renderWaiterSetupTables();
					if (waiterSetupSaveBtn) waiterSetupSaveBtn.disabled = false;
				}).catch(function() {
					waiterSetupCtx.tables = [];
					renderWaiterSetupTables();
					if (waiterSetupSaveBtn) waiterSetupSaveBtn.disabled = false;
				});
			}

			function openWaiterSetupModal(row) {
				if (!waiterSetupModalEl) return;
				if (!row || !row.id) return;

				var roleLower = (row.role ? String(row.role).trim().toLowerCase() : '');
				if (roleLower !== 'waiter') {
					window.AppUtils.notify('Only waiter users can have section/table assignments.', { type: 'info' });
					return;
				}

				resetWaiterSetupModalState();
				waiterSetupCtx.userId = row.id;
				waiterSetupCtx.companyId = row.company_id;

				var displayName = row.name || 'Waiter';
				if (waiterSetupModalTitleEl) waiterSetupModalTitleEl.textContent = 'Waiter Setup [' + displayName + ']';
				if (waiterSetupUserNameEl) waiterSetupUserNameEl.textContent = displayName;

				setWaiterSetupLoading(true);
				bootstrap.Modal.getOrCreateInstance(waiterSetupModalEl).show();

				var url = waiterAssignmentsListUrl + '?user_id=' + encodeURIComponent(row.id);
				getJson(url).then(function(json) {
					setWaiterSetupLoading(false);
					showWaiterSetupError('');

					if (!json.success) {
						showWaiterSetupError(json.message || 'Failed to load assignments.');
						if (waiterSetupSaveBtn) waiterSetupSaveBtn.disabled = true;
						return;
					}

					waiterSetupCtx.sections = Array.isArray(json.sections) ? json.sections : [];
					waiterSetupCtx.tables = Array.isArray(json.tables) ? json.tables : [];
					waiterSetupCtx.selectedSectionId = json.assigned_section_id || null;
					if (Array.isArray(json.assigned_table_ids)) {
						json.assigned_table_ids.forEach(function(id) { waiterSetupCtx.selectedTableIds.add(id); });
					}

					// Check if user has existing assignments
					waiterSetupCtx.hasExistingAssignments = !!(json.assigned_section_id);
					if (waiterSetupClearBtn) {
						waiterSetupClearBtn.style.display = waiterSetupCtx.hasExistingAssignments ? '' : 'none';
					}

					renderWaiterSetupSections();
					renderWaiterSetupTables();
					if (waiterSetupSaveBtn) waiterSetupSaveBtn.disabled = !waiterSetupCtx.selectedSectionId;
				}).catch(function(err) {
					setWaiterSetupLoading(false);
					var msg = err && err.message ? err.message : 'Unable to load waiter assignments.';
					showWaiterSetupError(msg);
					if (waiterSetupSaveBtn) waiterSetupSaveBtn.disabled = true;
				});
			}

			function setWaiterSetupSaveBusy(isBusy) {
				if (!waiterSetupSaveBtn) return;
				waiterSetupSaveBtn.disabled = !!isBusy;
				waiterSetupSaveBtn.innerHTML = isBusy
					? '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...'
					: waiterSetupSaveDefaultLabel;
			}

			function saveWaiterSetupAssignments() {
				if (!waiterSetupCtx.userId) return;
				var sectionId = waiterSetupCtx.selectedSectionId;
				if (!sectionId) {
					showWaiterSetupError('Please select a section.');
					return;
				}
				showWaiterSetupError('');
				setWaiterSetupSaveBusy(true);

				var payload = {
					user_id: waiterSetupCtx.userId,
					section_id: sectionId,
					table_ids: Array.from(waiterSetupCtx.selectedTableIds.values())
				};

				postJson(waiterAssignmentsSyncUrl, payload).then(function(json) {
					setWaiterSetupSaveBusy(false);
					var ok = json && (json.success !== false);
					var msg = (json && json.message) ? json.message : (ok ? 'Waiter assignments updated successfully.' : 'Unable to update assignments.');
					window.AppUtils.notify(msg, { type: ok ? 'success' : 'warning' });
					if (ok) {
						bootstrap.Modal.getOrCreateInstance(waiterSetupModalEl).hide();
						try { reloadUserGrid(); } catch(_) {}
					}
				}).catch(function(err) {
					setWaiterSetupSaveBusy(false);
					var msg = err && err.message ? err.message : 'Failed to save waiter assignments.';
					showWaiterSetupError(msg);
					window.AppUtils.notify(msg, { type: 'error' });
				});
			}

			if (waiterSetupSaveBtn) {
				waiterSetupSaveBtn.addEventListener('click', function() { saveWaiterSetupAssignments(); });
			}

			// Clear waiter assignments
			function clearWaiterSetupAssignments() {
				if (!waiterSetupCtx.userId) return;
				if (!waiterSetupCtx.hasExistingAssignments) return;

				Swal.fire({
					title: 'Clear Assignments?',
					text: 'This will remove all section and table assignments for this waiter.',
					icon: 'warning',
					showCancelButton: true,
					confirmButtonColor: '#d33',
					cancelButtonColor: '#6c757d',
					confirmButtonText: 'Yes, clear',
					cancelButtonText: 'Cancel'
				}).then(function(result) {
					if (!result.isConfirmed) return;

					showWaiterSetupError('');
					setWaiterSetupSaveBusy(true);

					var payload = {
						user_id: waiterSetupCtx.userId,
						section_id: null,
						table_ids: []
					};

					postJson(waiterAssignmentsSyncUrl, payload).then(function(json) {
						setWaiterSetupSaveBusy(false);
						var ok = json && (json.success !== false);
						var msg = ok ? 'Waiter assignments cleared successfully.' : (json.message || 'Unable to clear assignments.');
						window.AppUtils.notify(msg, { type: ok ? 'success' : 'warning' });
						if (ok) {
							bootstrap.Modal.getOrCreateInstance(waiterSetupModalEl).hide();
							try { reloadUserGrid(); } catch(_) {}
						}
					}).catch(function(err) {
						setWaiterSetupSaveBusy(false);
						var msg = err && err.message ? err.message : 'Failed to clear waiter assignments.';
						showWaiterSetupError(msg);
						window.AppUtils.notify(msg, { type: 'error' });
					});
				});
			}

			if (waiterSetupClearBtn) {
				waiterSetupClearBtn.addEventListener('click', function() { clearWaiterSetupAssignments(); });
			}

			if (waiterSetupSectionSelectEl) {
				waiterSetupSectionSelectEl.addEventListener('change', function() {
					var val = this.value;
					waiterSetupCtx.selectedSectionId = val ? parseInt(val, 10) : null;
					loadTablesForSection(waiterSetupCtx.selectedSectionId);
				});
			}

			if (waiterSetupModalEl) {
				waiterSetupModalEl.addEventListener('hidden.bs.modal', function() { resetWaiterSetupModalState(); });
			}

				// Permission Setup Modal + Grid
				const permModalEl = document.getElementById('permissionModal');
				const permTitleEl = document.getElementById('permissionModalTitle');
				const permSaveBtn = document.getElementById('permissionSaveBtn');
				const permAuditMountEl = document.getElementById('permissionAuditInfoMount');
				// Audit info controller for Permission modal
				const permAuditCtrl = (window.AppUtils && window.AppUtils.AuditInfo && permAuditMountEl)
					? window.AppUtils.AuditInfo.init(permAuditMountEl)
					: null;
				// A compact container to the right of audit info to show permission counts (M/T/R/U)
				let permSummaryEl = null;
				const permAvatarEl = document.getElementById('permissionUserAvatar');
				let permGrid = null;
				let permRows = [];
				let permUser = { id: null, name: '' };

				window.AppPage = window.AppPage || {};
				window.AppPage.onPermissionToggle = function(input){
					try {
						var field = input.getAttribute('data-field');
						var checked = !!input.checked;
						var isGroup = input.getAttribute('data-is-group') === '1';
						var groupName = input.getAttribute('data-group') || '';
						if (isGroup) {
							if (field === 'group_all') {
								(permRows || []).forEach(function(r){
									if (!r.isGroup && String(r.group||'') === String(groupName)) {
										if (r.is_add) r.add2 = checked;
										if (r.is_edit) r.edit2 = checked;
										if (r.is_delete) r.delete2 = checked;
										if (r.is_view) r.view2 = checked;
										if (r.is_viewatt) r.viewatt2 = checked;
										computeRowAll(r);
									}
								});
								computeGroupAggregates(groupName);
							} else if (['add2','edit2','delete2','view2','viewatt2'].indexOf(field) !== -1) {
								(permRows || []).forEach(function(r){
									if (!r.isGroup && String(r.group||'') === String(groupName)) {
										var gate = 'is_' + field.replace('2','');
										if (r[gate]) r[field] = checked;
										computeRowAll(r);
									}
								});
								computeGroupAggregates(groupName);
							}
						} else {
							var formId = parseInt(input.getAttribute('data-form-id'));
							var row = (permRows || []).find(function(r){ return !r.isGroup && Number(r.id) === Number(formId); });
							if (!row) return;
							if (field === 'row_all') {
								if (row.is_add) row.add2 = checked;
								if (row.is_edit) row.edit2 = checked;
								if (row.is_delete) row.delete2 = checked;
								if (row.is_view) row.view2 = checked;
								if (row.is_viewatt) row.viewatt2 = checked;
							} else if (['add2','edit2','delete2','view2','viewatt2'].indexOf(field) !== -1) {
								row[field] = checked;
							}
							computeRowAll(row);
							computeGroupAggregates(row.group);
						}
						if (permGrid) permGrid.refresh();
						// Update footer summary badges live
						try { renderPermissionSummary(computePermissionCounts(permRows)); } catch(_) {}
					} catch(_) {}
				};

				function checkboxTemplate(field, gateField){
					return function(data){
						try {
							if (!data) return '';
							// Group rows always show checkboxes
							if (!data.isGroup && !data[gateField]) { return ''; }
							var checked = data[field] ? 'checked' : '';
							var attrs = data.isGroup
								? 'data-is-group="1" data-group="'+ (window.AppUtils && AppUtils.escapeHtml ? AppUtils.escapeHtml(String(data.group||'')) : String(data.group||'')) +'"'
								: 'data-form-id="'+ data.id +'" data-group="'+ (window.AppUtils && AppUtils.escapeHtml ? AppUtils.escapeHtml(String(data.group||'')) : String(data.group||'')) +'"';
							return '<input type="checkbox" class="form-check-input" style="width:18px;height:18px;" '+ attrs +' data-field="'+ field +'" onchange="window.AppPage.onPermissionToggle(this)" '+ checked +' />';
						} catch(_) { return ''; }
					};
				}

				function masterTemplate(){
					return function(data){
						try {
							if (!data) return '';
							if (data.isGroup) {
								var checked = data.group_all ? 'checked' : '';
								var g = (window.AppUtils && AppUtils.escapeHtml) ? AppUtils.escapeHtml(String(data.group||'')) : String(data.group||'');
								return '<input type="checkbox" class="form-check-input" style="width:18px;height:18px;" data-is-group="1" data-group="'+ g +'" data-field="group_all" onchange="window.AppPage.onPermissionToggle(this)" '+ checked +' />';
							} else {
								var checked2 = data.row_all ? 'checked' : '';
								return '<input type="checkbox" class="form-check-input" style="width:18px;height:18px;" data-form-id="'+ data.id +'" data-group="'+ (window.AppUtils && AppUtils.escapeHtml ? AppUtils.escapeHtml(String(data.group||'')) : String(data.group||'')) +'" data-field="row_all" onchange="window.AppPage.onPermissionToggle(this)" '+ checked2 +' />';
							}
						} catch(_) { return ''; }
					};
				}

				function computeRowAll(row){
					try {
						var gates = ['is_add','is_edit','is_delete','is_view','is_viewatt'];
						var ok = true; var anyGate = false;
						gates.forEach(function(g){
							if (row[g]) { anyGate = true; var f = g.replace('is_','') + '2'; ok = ok && !!row[f]; }
						});
						row.row_all = anyGate ? ok : false;
					} catch(_) {}
				}

				function computeGroupAggregates(groupName){
					try {
						var items = (permRows || []).filter(function(r){ return !r.isGroup && String(r.group||'') === String(groupName||''); });
						var header = (permRows || []).find(function(r){ return r.isGroup && String(r.group||'') === String(groupName||''); });
						if (!header) return;
						var fields = ['add2','edit2','delete2','view2','viewatt2'];
						var gates = ['is_add','is_edit','is_delete','is_view','is_viewatt'];
						fields.forEach(function(f,idx){
							var gate = gates[idx];
							var gated = items.filter(function(it){ return !!it[gate]; });
							if (gated.length === 0) {
								header[f] = false; // no capability in group -> keep header unchecked
							} else {
								header[f] = gated.every(function(it){ return !!it[f]; });
							}
						});
						var anyGated = false;
						var ok = true;
						gates.forEach(function(g,idx){
							var f = fields[idx];
							var gated = items.filter(function(it){ return !!it[g]; });
							if (gated.length > 0) { anyGated = true; ok = ok && gated.every(function(it){ return !!it[f]; }); }
						});
						header.group_all = anyGated && ok;
					} catch(_) {}
				}

				// --- Robust DB datetime parser (treats 'YYYY-MM-DD HH:mm:ss' as local time) ---
				function parseDbDate(value){
					if (!value) return null;
					if (value instanceof Date) return isNaN(value.getTime()) ? null : value;
					if (typeof value === 'string') {
						// Accept 'YYYY-MM-DD HH:mm[:ss]' or 'YYYY-MM-DDTHH:mm[:ss]' WITHOUT timezone -> treat as UTC then convert to local
						const m = value.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/);
						if (m) {
							const y = +m[1], mo = +m[2] - 1, d = +m[3], hh = +m[4], mm = +m[5], ss = +(m[6] || '0');
							// Interpret as UTC timestamp and return as a Date representing that moment
							const dt = new Date(Date.UTC(y, mo, d, hh, mm, ss));
							return isNaN(dt.getTime()) ? null : dt;
						}
						// If ISO with trailing Z/+HH:mm, new Date will parse as UTC/offset correctly
					}
					const d = new Date(value);
					return isNaN(d.getTime()) ? null : d;
				}

				// --- Permission summary (counts per group) helpers ---
				function groupKeyToLetter(g){
					const s = String(g || '').toLowerCase();
					if (s.startsWith('master')) return 'M';
					if (s.startsWith('report')) return 'R';
					return 'U';
				}

				function computePermissionCounts(rows){
					const counts = { M: 0, R: 0, U: 0 };
					(rows || []).forEach(function(r){
						if (!r || r.isGroup) return;
						// Consider a form "counted" if any permission is granted
						const any = !!(r.add2 || r.edit2 || r.delete2 || r.view2 || r.viewatt2);
						if (!any) return;
						const k = groupKeyToLetter(r.group);
						counts[k] = (counts[k] || 0) + 1;
					});
					return counts;
				}

				function renderPermissionSummary(counts){
					if (!permAuditMountEl) return;
					// Create container once, append next to audit info content
					if (!permSummaryEl) {
						permSummaryEl = document.createElement('div');
						permSummaryEl.id = 'permissionSummaryBadges';
						permSummaryEl.className = 'd-flex align-items-center gap-1 flex-wrap';
						permSummaryEl.style.marginTop = '12px';
						// place it after the audit template content
						permAuditMountEl.appendChild(permSummaryEl);
					}
					// Build badge HTML similar to the React view
					function badge(letter, num){
						if (!num || num <= 0) return '';
						return (
							'<span class="position-relative d-inline-flex align-items-center me-2" style="height:18px;">'
							+ '<span class="badge rounded-pill bg-danger" style="line-height:1;">' + letter + '</span>'
							+ '<span class="badge rounded-pill bg-primary p-0 px-2 position-absolute" style="line-height:1; left:12px; top:-8px; font-size:0.65rem;">' + num + '</span>'
							+ '</span>'
						);
					}
					const html = [
						badge('M', counts.M),
						badge('R', counts.R),
						badge('U', counts.U)
					].join('');
					permSummaryEl.innerHTML = html;
				}

				function makeGroupedRows(rows){
					try {
						var map = {};
						(rows||[]).forEach(function(r){ var g = r.group || 'Other'; (map[g] = map[g] || []).push(r); });
						var out = [];
						var priority = ['Master','Report'];
						Object.keys(map).sort(function(a,b){
							var ia = priority.indexOf(String(a||''));
							var ib = priority.indexOf(String(b||''));
							if (ia !== -1 || ib !== -1) {
								if (ia === -1) return 1;
								if (ib === -1) return -1;
								return ia - ib;
							}
							return String(a||'').localeCompare(String(b||''));
						}).forEach(function(g){
							var header = { id: 'grp:'+g, name: g, group: g, isGroup: true,
								is_add: true, is_edit: true, is_delete: true, is_view: true, is_viewatt: true,
								add2: false, edit2: false, delete2: false, view2: false, viewatt2: false, group_all: false };
							out.push(header);
							map[g].forEach(function(item){ computeRowAll(item); out.push(item); });
							// Do not preset group aggregates; keep header checkboxes unchecked initially
						});
						return out;
					} catch(_) { return rows || []; }
				}

				function buildPermissionGrid(){
					if (permGrid) { permGrid.destroy(); permGrid = null; }
					// Wrap default export formatters so we can convert booleans to ✓/blank
					var excelCellHandler = AppUtils.GridHelpers.excelQueryCellInfoFactory({ 
						dateFields: ['created_at','updated_at'],
						sanitizeFields: [],
						sanitizeOptions: { stripHtml: true, replaceNewlinesWith: ' ' }
					});
					var pdfCellHandler = AppUtils.GridHelpers.pdfQueryCellInfoFactory({ 
						dateFields: ['created_at','updated_at'],
						sanitizeFields: [],
						sanitizeOptions: { stripHtml: true, replaceNewlinesWith: ' ' }
					});
					permGrid = new ej.grids.Grid(Object.assign(
						AppUtils.GridHelpers.baseGridOptions({ showColumnChooser: true }),
						{
						height: '100%',
						allowPaging: false,
						allowSorting: false,
						allowFiltering: false,
						allowExcelExport: true,
						allowPdfExport: true,
							toolbar: ['ColumnChooser','Print','ExcelExport','PdfExport','CsvExport'],
						columns: [
							{ field: 'all', headerText: '', width: 50, textAlign: 'Center', template: masterTemplate(), showInColumnChooser: false, allowExporting: false },
							{ field: 'id', headerText: 'ID', visible: false, showInColumnChooser: false, allowExporting: false },
							{ field: 'name', headerText: 'Form Name', width: 300, clipMode: 'EllipsisWithTooltip', showInColumnChooser: false,
								template: function(data){
									if (data && data.isGroup) {
										var label = (window.AppUtils && AppUtils.escapeHtml) ? AppUtils.escapeHtml(String(data.name||'')) : String(data.name||'');
										return '<div class="group-pill px-2 py-1">' + label + '</div>';
									}
									return (window.AppUtils && AppUtils.escapeHtml) ? AppUtils.escapeHtml(String(data && data.name ? data.name : '')) : (data && data.name ? data.name : '');
								}
							},
							{ field: 'group', headerText: 'Group', width: 140, clipMode: 'EllipsisWithTooltip', visible: false, showInColumnChooser: false },
							{ field: 'add2', headerText: 'Add', width: 80, textAlign: 'Center', template: checkboxTemplate('add2','is_add'), showInColumnChooser: false },
							{ field: 'edit2', headerText: 'Edit', width: 80, textAlign: 'Center', template: checkboxTemplate('edit2','is_edit'), showInColumnChooser: false },
							{ field: 'delete2', headerText: 'Delete', width: 80, textAlign: 'Center', template: checkboxTemplate('delete2','is_delete'), showInColumnChooser: false },
							{ field: 'view2', headerText: 'View', width: 80, textAlign: 'Center', template: checkboxTemplate('view2','is_view'), showInColumnChooser: false },
							{ field: 'viewatt2', headerText: 'File', width: 80, textAlign: 'Center', template: checkboxTemplate('viewatt2','is_viewatt'), showInColumnChooser: false },
							{ field: 'created_at', headerText: 'Created At', width: 180, visible: false, showInColumnChooser: true, type: 'datetime', format: { type: 'datetime', format: 'dd-MMM-yyyy hh:mm a' } },
							{ field: 'created_by', headerText: 'Created By', width: 160, visible: false, showInColumnChooser: true },
							{ field: 'updated_at', headerText: 'Updated At', width: 180, visible: false, showInColumnChooser: true, type: 'datetime', format: { type: 'datetime', format: 'dd-MMM-yyyy hh:mm a' } },
							{ field: 'lastmodified_by', headerText: 'Last Modified By', width: 180, visible: false, showInColumnChooser: true },
						],
						rowDataBound: function(args){
							try {
								var d = args && args.data; var rowEl = args && args.row;
								if (d && d.isGroup && rowEl) {
									rowEl.style.backgroundColor = '#0ea5a5';
									rowEl.style.color = '#ffffff';
									rowEl.classList.add('group-row');
								}
							} catch(_) {}
						},
						created: function(){ this.dataSource = permRows || []; },
						excelQueryCellInfo: function(args){
							try { if (typeof excelCellHandler === 'function') excelCellHandler(args); } catch(_){}
							try {
								var f = (args && args.column && args.column.field) ? String(args.column.field) : '';
								if (['add2','edit2','delete2','view2','viewatt2'].indexOf(f) !== -1) {
									var v = (args && args.data) ? !!args.data[f] : false;
									args.value = v ? 'Yes' : '';
								}
								// Shade group rows in Excel export
								if (args && args.data && args.data.isGroup) {
									args.style = args.style || {};
									args.style.backColor = '#e5e7eb'; // gray-200
									args.style.bold = true;
								}
							} catch(_){}
						},
						pdfQueryCellInfo: function(args){
							try { if (typeof pdfCellHandler === 'function') pdfCellHandler(args); } catch(_){}
							try {
								var f = (args && args.column && args.column.field) ? String(args.column.field) : '';
								if (['add2','edit2','delete2','view2','viewatt2'].indexOf(f) !== -1) {
									var v = (args && args.data) ? !!args.data[f] : false;
									args.value = v ? 'Yes' : '';
								}
								// Shade group rows in PDF export
								if (args && args.data && args.data.isGroup) {
									args.style = args.style || {};
									args.style.backgroundColor = '#e5e7eb'; // gray-200
									args.style.bold = true;
								}
							} catch(_){}
						},
						beforePdfExport: AppUtils.GridHelpers.beforePdfExportFactory({ fontBase64: (window.PDF_EXPORT_FONT_BASE64 || null), fontSize: 10 }),
							toolbarClick: function(args){
								var id = (args && args.item && args.item.id) ? String(args.item.id).toLowerCase() : '';
								if (id.endsWith('_print')) { this.print(); return; }
								var cols = (this.getColumns ? this.getColumns() : (this.columns||[])) || [];
								// Export exactly the currently visible, exportable columns
								var exportCols = cols
									.filter(function(c){ return c && c.visible !== false && c.allowExporting !== false; })
									.map(function(c){ return { field: c.field, headerText: c.headerText || String(c.field||'') }; });
								if (id.endsWith('_excelexport')) { this.excelExport({ includeHiddenColumn: false, columns: exportCols }); return; }
								if (id.endsWith('_pdfexport')) { this.pdfExport({ includeHiddenColumn: false, columns: exportCols }); return; }
								if (id.endsWith('_csvexport') && this.csvExport) { this.csvExport({ includeHiddenColumn: false, columns: exportCols }); return; }
							}
					})
					);
					permGrid.appendTo('#permissionGrid');
				}

				function openPermissionModal(row){
					if (!row || !row.id) return;
					permUser = { id: row.id, name: row.name };
					if (permTitleEl) permTitleEl.textContent = 'Permission Register [' + (row.name || 'User') + ']';
					// Set avatar in modal header
					try {
						var src = (row && row.photo_url) ? String(row.photo_url) : NO_IMAGE;
						if (permAvatarEl) permAvatarEl.src = src;
					} catch(_) {}
					fetch(permListUrl + '?user_id=' + encodeURIComponent(row.id))
						.then(r => r.json())
						.then(function(list){
							var base = Array.isArray(list) ? list : [];
							permRows = makeGroupedRows(base);
							// Derive audit info from permissions: earliest created and latest updated
							try {
								if (permAuditCtrl) {
									var createdAt = null, createdBy = '';
									var updatedAt = null, updatedBy = '';
									(base || []).forEach(function(it){
										if (it && it.created_at) {
											var d = parseDbDate(it.created_at);
											if (d && (!createdAt || d < createdAt)) { createdAt = d; createdBy = it.created_by || createdBy; }
										}
										if (it && it.updated_at) {
											var u = parseDbDate(it.updated_at);
											if (u && (!updatedAt || u > updatedAt)) { updatedAt = u; updatedBy = it.lastmodified_by || it.updated_by || updatedBy || ''; }
										}
									});
									permAuditCtrl.set({ createdAt: createdAt, updatedAt: updatedAt, createdBy: createdBy, updatedBy: updatedBy });
									permAuditCtrl.showFor('edit');
								}
							} catch(_) {}
							// Render permission summary badges in footer
							try { renderPermissionSummary(computePermissionCounts(base)); } catch(_) {}
							buildPermissionGrid();
							bootstrap.Modal.getOrCreateInstance(permModalEl).show();
						}).catch(function(){
							permRows = [];
							try { if (permAuditCtrl) { permAuditCtrl.set({ createdAt: '', updatedAt: '' }); permAuditCtrl.showFor('edit'); } } catch(_){ }
							try { renderPermissionSummary({ M:0, T:0, R:0, U:0 }); } catch(_) {}
							buildPermissionGrid();
							bootstrap.Modal.getOrCreateInstance(permModalEl).show();
						});
				}

				if (permSaveBtn) {
					permSaveBtn.addEventListener('click', function(){
						if (!permUser || !permUser.id) return;
						var payload = new FormData();
						payload.append('user_id', String(permUser.id));
						var rowsToSave = (permRows || []).filter(function(r){ return !r.isGroup; });
						payload.append('permissions', JSON.stringify(rowsToSave));
						try { payload.append('_token', document.querySelector('meta[name="csrf-token"]').content); } catch(_) {}
						fetch(permSaveUrl, { method: 'POST', body: payload }).then(r => r.json()).then(function(json){
							var msg = (json && json.message) ? json.message : 'Permissions saved.';
							var type = /nothing\s+changed/i.test(msg) ? 'warning' : ((json && json.success) ? 'success' : 'info');
							window.AppUtils.notify(msg, { type: type });
							if (json && json.success && !/nothing\s+changed/i.test(msg)) {
								// Refresh the user grid so permission summary pills update immediately
								try { reloadUserGrid(); } catch(_) {}
								bootstrap.Modal.getOrCreateInstance(permModalEl).hide();
							}
						});
					});
				}

				// toggleActive now handled via AppUtils.Actions in commandClick similar to businesses

				// Modal controls
				const modalEl = document.getElementById('userModal');
				const form = document.getElementById('userForm');
				const titleTextEl = document.getElementById('modalTitle');
				const submitBtn = document.getElementById('submitBtn');
				const inputId = document.getElementById('user_id');
				const nameEl = document.getElementById('name');
				const addressEl = document.getElementById('address');
				const emailEl = document.getElementById('email');
				const phoneEl = document.getElementById('phone');
				const businessEl = document.getElementById('business_id');
				const roleEl = document.getElementById('role');
				// no status field in modal anymore
				const inputPhoto = document.getElementById('photo');
				const preview = document.getElementById('showPhoto');
				const photoFeedback = document.getElementById('photoFeedback');
				const removePhotoEl = document.getElementById('remove_photo');
				const btnRemovePhoto = document.getElementById('btnRemovePhoto');
				const auditMountEl = document.getElementById('auditInfoMount');

				// Build audit info controller
				const auditCtrl = (window.AppUtils && window.AppUtils.AuditInfo && auditMountEl)
					? window.AppUtils.AuditInfo.init(auditMountEl)
					: null;

				function setMode(mode){ if (form) form.dataset.mode = mode; }
				function setTitleFor(mode){
					const map = { create: 'Add', edit: 'Edit', view: 'View' };
					const suffix = map[mode] || 'View';
					if (titleTextEl) titleTextEl.textContent = PAGE_TITLE + ' [' + suffix + ']';
				}
				function openModal(){ const modal = bootstrap.Modal.getOrCreateInstance(modalEl); modal.show(); }

				function applyModeUI(mode){
					var isView = (mode === 'view');
					[nameEl, addressEl, phoneEl, businessEl, roleEl].forEach(function(el){ if (el) el.disabled = isView; });
					if (inputPhoto) inputPhoto.disabled = isView;
					if (btnRemovePhoto) btnRemovePhoto.disabled = isView;
					if (emailEl) emailEl.disabled = true; // email is never editable here
					if (submitBtn) submitBtn.style.display = isView ? 'none' : '';
					if (auditCtrl) auditCtrl.showFor(mode);
				}

				let desiredBusinessId = null;
				let desiredBusinessName = '';
				function populateBusinessOptions(){
					if (!businessEl) return;
					if (CAN_LIST_BUSINESSES) {
						// Populate from endpoint (super admin only)
						fetch(@json(route('list.businesses'))).then(r => r.json()).then(function(list){
							const prev = businessEl.value;
							businessEl.innerHTML = '<option value="">Select Business</option>';
							(list || []).forEach(function(c){
								var opt = document.createElement('option');
								opt.value = c.id;
								opt.textContent = c.name;
								businessEl.appendChild(opt);
							});
							var targetId = desiredBusinessId || prev || '';
							if (targetId) {
								businessEl.value = String(targetId);
								if (businessEl.value !== String(targetId)) {
									var fallback = document.createElement('option');
									fallback.value = String(targetId);
									fallback.textContent = desiredBusinessName || 'Current Business';
									businessEl.appendChild(fallback);
									businessEl.value = String(targetId);
								}
							}
						}).catch(function(){});
					} else {
						// Non super-admin: Do NOT call endpoint (avoid 403/404). Show only the relevant business.
						const targetId = desiredBusinessId || CURRENT_BUSINESS_ID || '';
						const targetName = desiredBusinessName || '';
						businessEl.innerHTML = '<option value="">Select Business</option>';
						if (targetId) {
							var opt = document.createElement('option');
							opt.value = String(targetId);
							opt.textContent = targetName || 'Current Business';
							businessEl.appendChild(opt);
							businessEl.value = String(targetId);
						}
					}
				}
				// Only pre-populate for super admin; others are populated per selected row when the modal opens
				if (CAN_LIST_BUSINESSES) { populateBusinessOptions(); }

				function populateFormForEdit(data){
					if (!form) return;
					form.action = updateUrl;
					setMode('edit'); setTitleFor('edit');
					if (submitBtn && submitBtn.querySelector('span')) submitBtn.querySelector('span').textContent = 'Update';

					if (inputId) inputId.value = (data && data.id) || '';
					if (nameEl) nameEl.value = (data && data.name) || '';
					if (addressEl) addressEl.value = (data && data.address) || '';
					if (emailEl) emailEl.value = (data && data.email) || '';
					if (phoneEl) phoneEl.value = (data && data.phone) || '';
					if (businessEl) businessEl.value = (data && data.company_id) || '';
					desiredBusinessId = (data && data.company_id) || '';
					desiredBusinessName = (data && data.company_name) ? String(data.company_name) : '';
					populateBusinessOptions();
					if (roleEl) {
						var raw = String((data && data.role) || 'user').trim().toLowerCase();
						var mapped = raw;
						if (raw === 'super_admin' || raw === 'superadmin') mapped = 'super admin';
						roleEl.value = ['admin', 'user', 'waiter', 'chef', 'super admin'].indexOf(mapped) !== -1 ? mapped : 'user';
					}

					if (inputPhoto) { inputPhoto.value=''; inputPhoto.required = false; inputPhoto.classList.remove('is-invalid'); }
					if (photoFeedback) { photoFeedback.textContent = ''; }
					if (preview) {
						preview.src = (data && data.photo_url) ? String(data.photo_url) : NO_IMAGE;
					}
					if (removePhotoEl) removePhotoEl.value = '0';
					if (btnRemovePhoto) btnRemovePhoto.classList.toggle('d-none', !(data && data.photo));
					if (auditCtrl) auditCtrl.set({ createdAt: data && data.created_at, updatedAt: data && data.updated_at });

					applyModeUI('edit');
				}

				function populateFormForView(data){
					if (!form) return;
					setMode('view'); setTitleFor('view');
					if (inputId) inputId.value = (data && data.id) || '';
					if (nameEl) nameEl.value = (data && data.name) || '';
					if (addressEl) addressEl.value = (data && data.address) || '';
					if (emailEl) emailEl.value = (data && data.email) || '';
					if (phoneEl) phoneEl.value = (data && data.phone) || '';
					if (businessEl) businessEl.value = (data && data.company_id) || '';
					desiredBusinessId = (data && data.company_id) || '';
					desiredBusinessName = (data && data.company_name) ? String(data.company_name) : '';
					populateBusinessOptions();
					if (roleEl) {
						var raw2 = String((data && data.role) || 'user').trim().toLowerCase();
						var mapped2 = raw2;
						if (raw2 === 'super_admin' || raw2 === 'superadmin') mapped2 = 'super admin';
						roleEl.value = ['admin', 'user', 'waiter', 'chef', 'super admin'].indexOf(mapped2) !== -1 ? mapped2 : 'user';
					}
					if (inputPhoto) { inputPhoto.required = false; inputPhoto.classList.remove('is-invalid'); }
					if (photoFeedback) { photoFeedback.textContent = ''; }
					if (preview) {
						preview.src = (data && data.photo_url) ? String(data.photo_url) : NO_IMAGE;
					}
					if (removePhotoEl) removePhotoEl.value = '0';
					if (btnRemovePhoto) btnRemovePhoto.classList.add('d-none');
					if (auditCtrl) auditCtrl.set({ createdAt: data && data.created_at, updatedAt: data && data.updated_at });
					applyModeUI('view');
				}

				// Attach AJAX submit
				if (window.AppUtils && AppUtils.FormHelpers && form) {
					AppUtils.FormHelpers.attachAjaxSubmit(form, {
						submitBtn,
						submitWhenInvalid: true,
						getMode: function(){ return (form && form.dataset && form.dataset.mode) ? form.dataset.mode : 'edit'; },
						errorTargets: {
							name: '#name_error',
							address: '#address_error',
							email: '#email_error',
							phone: '#phone_error',
							company_id: '#business_error',
							role: '#role_error',
							photo: '#photoFeedback',
							// status removed from modal; no error target
						},
						onSuccess: function(json){
							const msg = (json && json.message) ? json.message : 'User updated successfully.';
							window.AppUtils.notify(msg, { type: 'success' });
							bootstrap.Modal.getOrCreateInstance(modalEl).hide();
							if (window.userGrid) {
								reloadUserGrid();
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
						[ nameEl, addressEl, phoneEl, businessEl, roleEl, inputPhoto ].forEach(function (el) {
							if (!el) return; el.disabled = false; if (typeof el.setCustomValidity === 'function') { el.setCustomValidity(''); }
						});
						if (inputId) inputId.value = '';
						if (auditCtrl) auditCtrl.set({ createdAt: '', updatedAt: '' });
						if (photoFeedback) { photoFeedback.textContent = ''; }
							if (preview) preview.src = NO_IMAGE;
						if (emailEl) emailEl.disabled = true;
					} catch (err) {}
				});

				// Input helpers
				if (window.AppUtils && AppUtils.InputHelpers) {
					AppUtils.InputHelpers.bindCamelCase(nameEl);
					AppUtils.InputHelpers.bindCamelCase(addressEl);
				}
						if (removePhotoEl) removePhotoEl.value = '0';
						if (btnRemovePhoto) btnRemovePhoto.classList.add('d-none');
				if (emailEl) { emailEl.addEventListener('input', function(){ this.value = (this.value || '').toLowerCase(); }); }
				if (phoneEl) { phoneEl.addEventListener('input', function(){ this.value = (this.value || '').replace(/\D/g,'').slice(0,10); }); }

				// Photo validation + preview
				function openPicker(evt){ evt.preventDefault(); inputPhoto && inputPhoto.click(); }
				if (inputPhoto && preview) {
					inputPhoto.addEventListener('change', function (e) {
						const file = e.target.files && e.target.files[0];
						if (!file) { inputPhoto.classList.remove('is-invalid'); if (photoFeedback) photoFeedback.textContent = ''; if (preview) preview.src = NO_IMAGE; return; }
						const isImage = /^image\//.test(file.type);
						const allowed = ['jpeg','png','jpg','gif','svg+xml','webp'];
						const subtype = file.type.split('/')[1] || '';
						const maxSize = 2 * 1024 * 1024;
						if (!isImage || allowed.indexOf(subtype) === -1) { inputPhoto.classList.add('is-invalid'); if (photoFeedback) photoFeedback.textContent = 'Image must be a file of type: jpeg, png, jpg, gif, svg.'; return; }
						if (file.size > maxSize) { inputPhoto.classList.add('is-invalid'); if (photoFeedback) photoFeedback.textContent = 'Image must not be larger than 2MB.'; return; }
						inputPhoto.classList.remove('is-invalid'); if (photoFeedback) photoFeedback.textContent = '';
						const reader = new FileReader(); reader.onload = function (ev) { preview.src = ev.target.result; }; reader.readAsDataURL(file);
						if (removePhotoEl) removePhotoEl.value = '0';
					});
					preview.addEventListener('click', openPicker);
					preview.addEventListener('keydown', function (e) { const key = e.key || e.keyCode; if (key === 'Enter' || key === ' ' || key === 'Spacebar' || key === 13 || key === 32) { openPicker(e); } });
				}

				// Remove photo handler
				if (btnRemovePhoto && removePhotoEl) {
					btnRemovePhoto.addEventListener('click', function(){
						removePhotoEl.value = '1';
						if (inputPhoto) inputPhoto.value = '';
						if (preview) preview.src = NO_IMAGE;
						if (photoFeedback) photoFeedback.textContent = '';
					});
				}
			});
		})();
	</script>

	{{-- Modal: View/Edit User --}}
	<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true" data-bs-backdrop="static">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header py-2" style="min-height:42px;">
					<h5 class="modal-title d-flex align-items-center fs-6 text-white" id="userModalLabel">
						@php
							$__isFa = Str::startsWith($pageIcon, 'fa');
						@endphp
						@if($__isFa)
							<i class="{{ $pageIcon }} me-2" style="font-size:18px; width:18px; text-align:center; color:white !important;"></i>
						@else
							<i data-feather="{{ $pageIcon }}" class="me-2 text-white" style="width:18px;height:18px; color:white !important;"></i>
						@endif
						<span id="modalTitle">{{ $pageTitle }} [View]</span>
					</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>

				<form id="userForm" action="" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
					@csrf
					<input type="hidden" id="user_id" name="id" value="">
					<input type="hidden" id="remove_photo" name="remove_photo" value="0">

					<div class="modal-body">
						<div class="row g-3">
							<!-- Photo moved to bottom center per reference -->

							<div class="col-12">
								<div class="row gx-4 gy-2 gx-2">
									<div class="col-12 col-md-8 pe-md-2"> <!-- Right padding on medium+ screens -->
										<label for="name" class="form-label">Name <span class="text-danger">*</span></label>
										<input type="text" class="form-control" id="name" name="name" minlength="3" maxlength="100" autocomplete="off" required>
										<div class="invalid-feedback" id="name_error">Please specify user name!</div>
									</div>
									<div class="col-12 col-md-4 ps-md-2"> <!-- Left padding on medium+ screens -->
										<label for="role" class="form-label">Role <span class="text-danger">*</span></label>
										<select id="role" name="role" class="form-select" required>
											<option value="user">USER</option>
											<option value="admin">ADMIN</option>
										</select>
										<div class="invalid-feedback" id="role_error">Please select a role.</div>
									</div>
								</div>
							</div>

							<div class="col-12">
								<label for="address" class="form-label">Address <span class="text-danger">*</span></label>
								<textarea class="form-control" id="address" name="address" rows="3" maxlength="500" placeholder="Enter Address" required></textarea>
								<div class="invalid-feedback" id="address_error">Please specify address!</div>
							</div>

							<div class="col-12">
								<label for="email" class="form-label">Email <span class="text-danger">*</span></label>
								<input type="email" class="form-control" id="email" name="email" maxlength="100" autocomplete="off" disabled>
								<div class="invalid-feedback" id="email_error">Please specify a valid email address!</div>
							</div>

							<div class="col-12">
								<div class="row gx-4 gy-2 gx-2">
									<div class="col-sm-6 pe-md-2"> <!-- Right padding on medium+ screens -->
										<label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
										<input type="text" class="form-control text-center" id="phone" name="phone" maxlength="10" autocomplete="off" placeholder="10-digit phone" required>
										<div class="invalid-feedback" id="phone_error">Please enter a 10-digit phone number.</div>
									</div>
									<div class="col-sm-6 ps-md-2"> <!-- Left padding on medium+ screens -->
										<label for="business_id" class="form-label">Business <span class="text-danger">*</span></label>
										<select id="business_id" name="company_id" class="form-select" required>
											<option value="">Select Business</option>
										</select>
										<div class="invalid-feedback" id="business_error">Please select a business.</div>
									</div>
								</div>
							</div>
							<!-- Bottom-centered photo controls -->
							<div class="col-12">
								<div class="d-flex justify-content-center mb-2">
									<img id="showPhoto" src="{{ url('upload/no_image.jpg') }}" alt="User Photo" class="rounded-circle" tabindex="0" style="width:96px;height:96px;object-fit:cover;cursor:pointer;" />
								</div>
								<div class="d-flex justify-content-center">
									<input class="form-control w-auto" type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml,image/webp">
								</div>
								<div class="d-flex justify-content-center mt-2">
									<button type="button" id="btnRemovePhoto" class="btn btn-sm btn-outline-secondary d-none">Remove photo</button>
								</div>
								<div class="invalid-feedback text-center" id="photoFeedback">Image must be JPEG/PNG/GIF/SVG/WEBP up to 2MB.</div>
								<div class="text-center"><small class="text-muted">Click the photo to choose a file</small></div>
							</div>
							<!-- Status control removed from modal by request -->


						</div>
					</div>

					<div class="modal-footer">
						<div class="w-100 d-flex align-items-center justify-content-between">
							<div id="auditInfoMount" class="d-flex flex-column"></div>

							<div class="d-flex align-items-center gap-2">
								<button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
									<span>Close</span>
								</button>
								<button type="submit" id="submitBtn" class="btn btn-primary">
									<span>Update</span>
								</button>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
		{{-- Audit info template from common_js is used in the footer mount --}}
		{{-- No rating control required here --}}
	</div>

	{{-- Modal: Super User Business Access --}}
	<div class="modal fade" id="superBusinessModal" tabindex="-1" aria-labelledby="superBusinessModalTitle" aria-hidden="true" data-bs-backdrop="static">
		<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
			<div class="modal-content">
				<div class="modal-header py-2" style="min-height:42px;">
					<h5 class="modal-title d-flex align-items-center fs-6 text-white">
						<i class="fas fa-building me-2" style="color:white !important;"></i>
						<span id="superBusinessModalTitle">Assign Businesses</span>
					</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<p class="text-muted small mb-3">Select the businesses that <span class="fw-bold" id="superBusinessUserName">this super user</span> should manage.</p>
					<div class="row g-2 mb-3 align-items-center">
						<div class="col-lg-9">
							<div class="input-group input-group-sm">
								<span class="input-group-text"><i data-feather="search" class="text-muted"></i></span>
								<input type="search" class="form-control" id="superBusinessSearch" placeholder="Search business or code" autocomplete="off">
								<button class="btn btn-primary" type="button" id="superBusinessClearSearch">Clear</button>
							</div>
						</div>
						<div class="col-lg-3 text-lg-end">
							<div class="small text-muted" id="superBusinessSelectionCounter">0 selected</div>
						</div>
					</div>
					<div id="superBusinessError" class="alert alert-danger small py-2 mb-3 d-none" role="alert"></div>
					<div class="border rounded position-relative" style="max-height:380px;overflow:auto;">
						<div id="superBusinessLoading" class="text-center text-muted py-4">Loading...</div>
						<div id="superBusinessEmpty" class="text-center text-muted py-4 d-none">No businesses found.</div>
						<div id="superBusinessList" class="list-group list-group-flush"></div>
					</div>
				</div>
				<div class="modal-footer">
					<div class="w-100 d-flex align-items-center justify-content-between flex-wrap gap-2">
						<div class="small text-muted">Tip: Deselect a business to revoke access.</div>
						<div class="d-flex align-items-center gap-2">
							<button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
							<button type="button" class="btn btn-primary" id="superBusinessSaveBtn">Save Access</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	{{-- Modal: Chef Setup (Menu/Category/Food Type Assignments) --}}
	<div class="modal fade" id="chefSetupModal" tabindex="-1" aria-labelledby="chefSetupModalTitle" aria-hidden="true" data-bs-backdrop="static">
		<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
			<div class="modal-content">
				<div class="modal-header py-2" style="min-height:42px;">
					<h5 class="modal-title d-flex align-items-center fs-6 text-white">
						<i class="material-icons-outlined me-2" style="font-size:20px; color:white !important;">restaurant_menu</i>
						<span id="chefSetupModalTitle">Chef Setup</span>
					</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<p class="text-muted small mb-3">Assign Menu, Category, and Food Type items to <span class="fw-bold" id="chefSetupUserName">this chef</span>.</p>
					<div id="chefSetupError" class="alert alert-danger small py-2 mb-3 d-none" role="alert"></div>
					<div id="chefSetupLoading" class="text-center text-muted py-4 d-none">Loading...</div>
					<div class="row g-3">
						{{-- Menu Column --}}
						<div class="col-lg-4 pe-md-2">
							<div class="border rounded h-100 d-flex flex-column">
								<div class="px-3 py-2 border-bottom bg-light fw-semibold text-center" style="background-color:#f8f9fa;">
									<i class="fas fa-clipboard-list me-1 text-primary"></i> Menu
								</div>
								<div id="chefSetupMenuList" class="list-group list-group-flush flex-grow-1" style="max-height:320px;overflow:auto;"></div>
							</div>
						</div>
						{{-- Category Column --}}
						<div class="col-lg-4 ps-md-2 pe-md-2">
							<div class="border rounded h-100 d-flex flex-column">
								<div class="px-3 py-2 border-bottom bg-light fw-semibold text-center" style="background-color:#f8f9fa;">
									<i class="fas fa-sitemap me-1 text-success"></i> Menu Category
								</div>
								<div id="chefSetupCategoryList" class="list-group list-group-flush flex-grow-1" style="max-height:320px;overflow:auto;"></div>
							</div>
						</div>
						{{-- Food Type Column --}}
						<div class="col-lg-4 ps-md-2">
							<div class="border rounded h-100 d-flex flex-column">
								<div class="px-3 py-2 border-bottom bg-light fw-semibold text-center" style="background-color:#f8f9fa;">
									<i class="fas fa-seedling me-1 text-warning"></i> Food Type
								</div>
								<div id="chefSetupFoodTypeList" class="list-group list-group-flush flex-grow-1" style="max-height:320px;overflow:auto;"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<div class="w-100 d-flex align-items-center justify-content-between flex-wrap gap-2">
						<div class="small text-muted">Tip: Select items to assign; deselect to remove assignment.</div>
						<div class="d-flex align-items-center gap-2">
							<button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
							<button type="button" class="btn btn-primary" id="chefSetupSaveBtn" disabled>Save Assignments</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	{{-- Modal: Waiter Setup (Section/Table Assignments) --}}
	<div class="modal fade" id="waiterSetupModal" tabindex="-1" aria-labelledby="waiterSetupModalTitle" aria-hidden="true" data-bs-backdrop="static">
		<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
			<div class="modal-content">
				<div class="modal-header py-2" style="min-height:42px;">
					<h5 class="modal-title d-flex align-items-center fs-6 text-white">
						<i class="material-icons-outlined me-2" style="font-size:20px; color:white !important;">room_service</i>
						<span id="waiterSetupModalTitle">Waiter Setup</span>
					</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<p class="text-muted small mb-3">Assign a Section and Tables to <span class="fw-bold" id="waiterSetupUserName">this waiter</span>.</p>
					<div id="waiterSetupError" class="alert alert-danger small py-2 mb-3 d-none" role="alert"></div>
					<div id="waiterSetupLoading" class="text-center text-muted py-4 d-none">Loading...</div>
					<div class="row g-3">
						{{-- Section Dropdown --}}
						<div class="col-12">
							<label for="waiterSetupSectionSelect" class="form-label fw-semibold">Section <span class="text-danger">*</span></label>
							<select id="waiterSetupSectionSelect" class="form-select">
								<option value="">Select Section *</option>
							</select>
						</div>
						{{-- Tables Multi-Select --}}
						<div class="col-12">
							<label class="form-label fw-semibold">Tables (optional)</label>
							<div id="waiterSetupTableList" class="list-group list-group-flush border rounded" style="max-height:220px;overflow:auto;"></div>
							<div class="small text-muted mt-2">Tip: Leave tables empty to assign all tables in the section.</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-outline-secondary" id="waiterSetupClearBtn" style="display:none;">Clear Assignments</button>
					<button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary" id="waiterSetupSaveBtn" disabled>Save Assignments</button>
				</div>
			</div>
		</div>
	</div>

	<style>
		/* Increase modal height (not width) and let grid fill it without double scrollbars */
		.super-business-pill {
			border: 1px solid #c7d2fe;
			background-color: #eef2ff;
			color: #312e81;
			border-radius: 999px;
			padding: 2px 10px;
			font-size: 0.72rem;
			font-weight: 600;
			display: inline-flex;
			align-items: center;
			gap: 6px;
			line-height: 1.1;
		}
		.super-business-pill--more {
			border-style: dashed;
			background-color: #ffffff;
			color: #1f2937;
		}
		.super-business-status {
			font-size: 0.65rem;
			font-weight: 600;
			padding: 1px 6px;
			border-radius: 999px;
		}

		/* Material Icon for Grid Button (Chef Setup) */
		.mi-chef:before {
			content: "\e561" !important; /* restaurant_menu */
			font-family: 'Material Icons Outlined' !important;
			font-size: 18px;
			vertical-align: top;
		}

		/* Material Icon for Grid Button (Waiter Setup) */
		.mi-waiter:before,
		.mi-badge:before {
			content: "\e8cb" !important; /* badge */
			font-family: 'Material Icons Outlined' !important;
			font-size: 18px;
			vertical-align: top;
		}
		/* Material Icon for Grid Button (Waiter Setup) - Custom Size/Weight */
		.mi-room-service {
			font-size: 20px !important;
			font-weight: 100 !important; /* Thin weight */
			vertical-align: middle;
			line-height: 1;
			-webkit-font-smoothing: antialiased;
			-moz-osx-font-smoothing: grayscale;
			font-family: 'Material Icons Outlined', sans-serif; /* Use outlined/light variant */
		}

		.super-business-status--inactive {
			background-color: #fee2e2;
			color: #991b1b;
		}

		#superBusinessModal .form-check-input:checked,
		#chefSetupModal .form-check-input:checked,
		#waiterSetupModal .form-check-input:checked,
		#permissionModal .form-check-input:checked,
		#permissionGrid .e-checkbox-wrapper .e-frame.e-check {
						background-color: {{ config('services.theme.color') }} !important;
						border-color: {{ config('services.theme.color') }} !important;
		}

		/* Orange text utility for Chef Setup button with assignments */
			.text-orange { color: {{ config('services.theme.color') }} !important; }

		/* Ensure all modal header icons are white on the orange background */
		.modal-header i, 
		.modal-header .material-icons-outlined,
		.modal-header [data-feather],
		.modal-header svg { 
			color: #ffffff !important; 
			stroke: #ffffff !important;
		}

		#permissionModal .modal-dialog { max-width: 1000px; } /* keep default-ish width for modal-xl on large screens */
		/* Make the whole modal content fill viewport height and make body flex the remaining space */
		#permissionModal .modal-content { height: 90vh; display: flex; flex-direction: column; }
		#permissionModal .modal-header, 
		#permissionModal .modal-footer { flex: 0 0 auto; }
		#permissionModal .modal-body { flex: 1 1 auto; min-height: 0; overflow: hidden; padding-bottom: 0.25rem; }
		/* When Bootstrap's modal-dialog-scrollable is present, override its body scrolling */
		#permissionModal .modal-dialog.modal-dialog-scrollable .modal-body { max-height: none; overflow: hidden; }
		/* Grid should consume all of the body space and manage its own inner scroll */
		#permissionGrid { height: 100%; box-sizing: border-box; margin-bottom: 0px; }

		/* Keep group row background fixed even on hover/focus */
		#permissionModal #permissionGrid .e-gridcontent .e-row.group-row td,
		#permissionModal #permissionGrid .e-gridcontent .e-row.group-row:hover td,
		#permissionModal #permissionGrid .e-gridcontent .e-row.group-row:focus td {
			background-color: #0ea5a5 !important;
			color: #ffffff !important;
		}

		/* Group pill default styling (used inside Form Name cell for group rows) */
		.group-pill { background:#0ea5a5; color:#fff; border-radius:4px; font-weight:600; display:inline-block; }

		/* Hide first checkbox column when printing */
		@media print {
			#permissionModal #permissionGrid .e-gridheader th:first-child,
			#permissionModal #permissionGrid .e-gridcontent td:first-child,
			#permissionModal #permissionGrid colgroup col:first-child { display: none !important; }

			/* Ensure header background is visible in print (both live and cloned print grid) */
			#permissionModal #permissionGrid .e-gridheader,
			#permissionModal #permissionGrid .e-gridheader th,
			#permissionModal #permissionGrid .e-gridheader .e-headercell,
			#permissionModal #permissionGrid .e-gridheader table thead th,
			.e-print-grid .e-gridheader,
			.e-print-grid .e-gridheader th,
			.e-print-grid .e-gridheader .e-headercell,
			.e-print-grid .e-gridheader table thead th {
				background-color: #888889 !important; /* gray-100 */
				color: #111827 !important; /* gray-900 */
				-webkit-print-color-adjust: exact;
				print-color-adjust: exact;
			}
			/* Preserve group row background in print */
			#permissionModal #permissionGrid .e-row.group-row td {
				background-color: #0ea5a5 !important;
				color: #ffffff !important;
				-webkit-print-color-adjust: exact;
				print-color-adjust: exact;
			}
			/* Also handle the cloned print grid DOM */
			.e-print-grid .e-row.group-row td {
				background-color: #0ea5a5 !important;
				color: #ffffff !important;
				-webkit-print-color-adjust: exact;
				print-color-adjust: exact;
			}
			/* Remove pill background in print so the whole row color shows, not only the cell */
			/* .group-pill { background: transparent !important; color: inherit !important; } */
		}
	</style>

	{{-- Modal: Permission Setup --}}

	<div class="modal fade" id="permissionModal" tabindex="-1" aria-labelledby="permissionModalLabel" aria-hidden="true" data-bs-backdrop="static">
		<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
			<div class="modal-content">
				<div class="modal-header py-2" style="min-height:42px;">
					<h5 class="modal-title d-flex align-items-center fs-6 text-white">
						<i class="fas fa-cog me-2" style="width:18px;height:18px;color:white !important;"></i>
						<span id="permissionModalTitle">Permission Register</span>
						<img id="permissionUserAvatar" src="{{ url('upload/no_image.jpg') }}" alt="User" class="rounded-circle ms-2" style="width:28px;height:28px;object-fit:cover;" />
					</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body mb-2">
					<div id="permissionGrid" style="overflow:auto;"></div>
				</div>
				<div class="modal-footer">
					<div class="w-100 d-flex align-items-center justify-content-between">
						<div id="permissionAuditInfoMount" class="d-flex flex-column"></div>
						<div class="d-flex align-items-center gap-2">
							<button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
							<button type="button" id="permissionSaveBtn" class="btn btn-primary">
								<span>Update</span>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	 </div>

@endsection
