@extends('admin.admin_master')
@section('admin')

@php
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\DB;

	$pageTitle = $pageTitle ?? 'Customer Master';
	$pageIcon = $pageIcon ?? 'users';

	$auth = Auth::user();
	$role = $auth?->role ?? 'user';
	$normRole = strtolower(str_replace('_',' ', trim($role)));
	$isAdminRole = in_array($normRole, ['admin','super admin'], true);

	$perm = (object) ['is_add' => 1, 'is_edit' => 1, 'is_delete' => 1];
	if (!$isAdminRole) {
		$row = DB::table('utility_user_permission_register')->where('user_id', $auth->id)->where('form_id', 4)->select('is_add','is_edit','is_delete')->first();
		$perm = $row ?: (object)['is_add' => 0, 'is_edit' => 0, 'is_delete' => 0];
	}

	$hideAddButton = (!$isAdminRole && (int)($perm->is_add ?? 0) !== 1);
	$permsForJs = [
		'add' => $isAdminRole ? true : ((int)($perm->is_add ?? 0) === 1),
		'edit' => $isAdminRole ? true : ((int)($perm->is_edit ?? 0) === 1),
		'delete' => $isAdminRole ? true : ((int)($perm->is_delete ?? 0) === 1),
	];

    $isSuperAdmin = ($normRole === 'super admin');
	$currentUserId = (int) ($auth?->id ?? 0);
	$isRootSuperAdmin = $isSuperAdmin && $currentUserId === 1;
	$businessPickerListRoute = $isSuperAdmin
		? ($isRootSuperAdmin ? route('list.businesses') : route('list.assigned.businesses'))
		: null;
@endphp

@include('components.common_js', [
	'wrapCard' => true, 'icon' => $pageIcon, 'title' => $pageTitle,
	'refreshId' => 'btnGridRefresh', 'reloadIconId' => 'reloadIcon',
	'gridId' => 'customerGrid', 'bodyHeight' => '500px',
	'hideAddButton' => $hideAddButton, 'showUserAvatar' => false,
	'emptyEntity' => 'Customer Register'
])

<script>
(function(){
	'use strict';
	document.addEventListener('DOMContentLoaded', function(){
		if (!(window.AppUtils && window.AppUtils.librariesReady)) return;

		const listUrl = @json(route('list.customers'));
		const addUrl = @json(route('add.customer'));
		const updateUrl = @json(route('update.customer'));
		const removeUrl = @json(route('delete.customer'));
		const setActiveUrl = @json(route('setactive.customer'));
		const NO_IMAGE = @json(url('upload/no_image.jpg'));
		const PERMS = @json($permsForJs);
		const IS_SUPER = @json($isSuperAdmin);
		const PAGE_TITLE = @json($pageTitle);
		const BUSINESS_PICKER_LIST_URL = @json($businessPickerListRoute);
		const DATE_FIELDS = ['created_at','updated_at'];

		const isSmallScreen = window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches;
		const showColumnChooser = !isSmallScreen;

		function isActiveVal(v){ return (v === true || v === 1 || v === '1'); }
		function getSelectedBusinessId(){ const btn = document.getElementById('businessPickerBtn'); return btn && btn.dataset ? (btn.dataset.id || '') : ''; }

		const NO_BUSINESS_TEMPLATE = '<div style="text-align:center;padding:40px 20px;"><div style="font-size:42px;color:#94a3b8;margin-bottom:10px;">🏢</div><h5 style="color:#64748b;margin-bottom:6px;">No Business Selected</h5><p style="color:#94a3b8;margin:0;">Select a business from the top-right to view Customers</p></div>';

		const gridOptions = Object.assign({}, AppUtils.GridHelpers.baseGridOptions(), {
			emptyRecordTemplate: IS_SUPER ? NO_BUSINESS_TEMPLATE : AppUtils.emptyRecordTemplate('Customer Register'),
			columns: [
				{ field: 'id', headerText: 'ID', isPrimaryKey: true, isIdentity: true, width: 70, textAlign: 'Right', visible: false, showInColumnChooser: false },
				{ field: 'customer_name', headerText: 'Customer Name', width: 240, validationRules: { required: true }, template: (args) => {
					const name = AppUtils.escapeHtml(String(args.customer_name || ''));
					var inactivePill = '';
					if (!isActiveVal(args.is_active)) inactivePill = '<span style="background-color:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:5px;font-size:10px;font-weight:500;margin-left:8px;height:16px;line-height:16px;display:inline-flex;align-items:center;">Inactive</span>';
					var code = (args && args.customer_code) ? AppUtils.escapeHtml(String(args.customer_code)) : '';
					var codeBadge = '';
					if (code) {
						var copyHandler = "(function(c){navigator.clipboard&&navigator.clipboard.writeText(c).then(function(){try{AppUtils.notify('Code copied',{type:'info'})}catch(_){}})})('"+code+"')";
						codeBadge = '<span class="badge rounded-pill" style="border:1px solid #3b82f6;color:#1d4ed8;background-color:#eff6ff;padding:4px 10px;font-weight:700;cursor:pointer;user-select:none;" title="Click to copy" onclick="'+copyHandler+'">'+code+'</span>';
					}
					return '<div class="flex-column"><div>'+name+inactivePill+'</div><div class="d-flex align-items-center gap-2 mt-1">'+codeBadge+'</div></div>';
				}},
				{ field: 'customer_code', headerText: 'Code', width: 110, visible: false },
				{ field: 'contact_person', headerText: 'Contact Person', width: 160 },
				{ field: 'mobile_no', headerText: 'Mobile', width: 130 },
				{ field: 'email', headerText: 'Email', width: 180, visible: false },
				{ field: 'gstin', headerText: 'GSTIN', width: 170, visible: false },
				{ field: 'pan', headerText: 'PAN', width: 120, visible: false },
				{ field: 'correspondence_address', headerText: 'Address', width: 220, visible: false },
				{ field: 'is_active_text', headerText: 'Active', width: 100, textAlign: 'Center', template: (args) => {
					return isActiveVal(args.is_active) ? '<i data-feather="check-square" class="text-success" style="width:16px;height:16px;"></i>' : '<i data-feather="x-square" class="text-danger" style="width:16px;height:16px;"></i>';
				}},
				{ field: 'created_at', headerText: 'Created At', width: 200, type: 'datetime', format: { type: 'datetime', format: 'dd-MMM-yyyy hh:mm a' }, showInColumnChooser: showColumnChooser, customAttributes: { class: 'hide-on-small-screen' } },
				{ field: 'created_by', headerText: 'Created By', width: 150, visible: false },
				{ field: 'updated_at', headerText: 'Updated At', width: 200, type: 'datetime', visible: false, format: { type: 'datetime', format: 'dd-MMM-yyyy hh:mm a' }, showInColumnChooser: showColumnChooser, customAttributes: { class: 'hide-on-small-screen' } },
				{ field: 'lastmodified_by', headerText: 'Last Modified By', width: 170, visible: false },
				{ headerText: 'Manage Records', width: 180, textAlign: 'Center', exportExclude: true, showInColumnChooser: false,
					commands: [
						{ type: 'None', title: 'View', buttonOption: { iconCss: 'e-icons e-eye', cssClass: 'e-flat e-fleet cmd-view' } },
						{ type: 'None', title: 'Modify', buttonOption: { iconCss: 'e-icons e-edit', cssClass: 'e-flat e-fleet e-modify cmd-edit' } },
						{ type: 'None', title: 'Remove', buttonOption: { iconCss: 'e-icons e-delete', cssClass: 'e-flat e-fleet cmd-delete' } },
						{ type: 'None', title: 'Inactive Record', buttonOption: { iconCss: 'e-icons e-circle-close', cssClass: 'e-close e-flat e-fleet cmd-deactivate' } },
						{ type: 'None', title: 'Activate Record', buttonOption: { iconCss: 'e-icons e-circle-check', cssClass: 'e-open e-flat e-fleet cmd-activate' } },
					]
				}
			],
			toolbarClick: AppUtils.GridHelpers.toolbarClickFactory(() => grid),
			actionBegin: AppUtils.GridHelpers.actionBeginSearchKeyUpdaterFactory(() => grid),
			queryCellInfo: AppUtils.GridHelpers.queryCellInfoHighlighterFactory(() => grid),
			pdfQueryCellInfo: (function(){
				const base = AppUtils.GridHelpers.pdfQueryCellInfoFactory({ dateFields: DATE_FIELDS });
				return function(args){ try { base(args); } catch(_){ } try { if (args && args.column && args.data) { if (args.column.field === 'customer_name') { let txt = String(args.data.customer_name||''); if (!isActiveVal(args.data.is_active)) txt += ' [Inactive]'; const code = String(args.data.customer_code||''); if (code) txt += ' ('+code+')'; args.value = txt; } if (args.column.field === 'is_active_text') args.value = String(args.data.is_active_text||''); } } catch(_){ } };
			})(),
			excelQueryCellInfo: AppUtils.GridHelpers.excelQueryCellInfoFactory({ dateFields: DATE_FIELDS }),
			rowDataBound: AppUtils.GridHelpers.rowDataBoundToggleActionsFactory({ isActiveField: 'is_active', modifySelector: '.e-modify, .cmd-edit' }),
			dataBound: function(){ try { if (!PERMS.edit) this.element.querySelectorAll('.cmd-edit, .cmd-activate, .cmd-deactivate').forEach(el => el.style.display = 'none'); if (!PERMS.delete) this.element.querySelectorAll('.cmd-delete').forEach(el => el.style.display = 'none'); if (window.feather) feather.replace(); } catch(_) {} }
		});

		const grid = new ej.grids.Grid(Object.assign({}, gridOptions, {
			commandClick: function(args){
				try {
					const row = args.rowData || {};
					const target = args.target || (args.originalEvent ? args.originalEvent.target : null);
					if (!target) return;
					if (target.closest && target.closest('.cmd-view')) { populateFormForView(row); return; }
					if (target.closest && target.closest('.cmd-edit')) { if (!PERMS.edit) return; populateFormForEdit(row); return; }
					if (target.closest && target.closest('.cmd-delete')) { if (!PERMS.delete) return; AppUtils.Actions.deleteResource({ url: removeUrl, method: 'POST', data: { id: row.id }, confirm: { title:'Are you sure?', text:'Delete this customer?' }, onSuccess: refreshGrid }); return; }
					if (target.closest && target.closest('.cmd-activate')) { if (!PERMS.edit) return; onSetActive(1, row.id); return; }
					if (target.closest && target.closest('.cmd-deactivate')) { if (!PERMS.edit) return; onSetActive(0, row.id); return; }
				} catch(_) {}
			}
		}));
		grid._autofitEnabled = false;
		grid.appendTo('#customerGrid');
		grid.recordDoubleClick = function(e){ try { if (e && e.rowData) populateFormForView(e.rowData); } catch(_) {} };

		window.AppPage = window.AppPage || {};
		function refreshGrid(){
			let url = listUrl;
			if (IS_SUPER) { const cid = getSelectedBusinessId(); if (!cid) { grid.emptyRecordTemplate = NO_BUSINESS_TEMPLATE; grid.dataSource = []; return; } url = listUrl + '?company_id=' + encodeURIComponent(cid); }
			AppUtils.GridHelpers.loadDataToGrid(grid, url, { dateFields: DATE_FIELDS });
		}
		window.AppPage.onRefresh = refreshGrid;
		if (!IS_SUPER) refreshGrid();

		const headerAddBtn = document.getElementById('toolbarAdd');
		if (headerAddBtn) {
			if (!PERMS.add) { headerAddBtn.style.display = 'none'; const li = headerAddBtn.closest('.e-toolbar-item'); if (li) li.style.display = 'none'; }
			headerAddBtn.addEventListener('click', function(){ if (resetFormForCreate()) openModal(); });
			if (IS_SUPER) { headerAddBtn.style.display = 'none'; const li = headerAddBtn.closest('.e-toolbar-item'); if (li) li.style.display = 'none'; }
		}

		// ----- Modal & Form -----
		const modalEl = document.getElementById('customerModal');
		const form = document.getElementById('customerForm');
		const submitBtn = document.getElementById('submitBtn');
		const titleTextEl = document.getElementById('modalTitle');
		const inputId = document.getElementById('customer_id');
		const codeEl = document.getElementById('customer_code');
		const nameEl = document.getElementById('customer_name');
		const contactEl = document.getElementById('contact_person');
		const emailEl = document.getElementById('email');
		const mobileEl = document.getElementById('mobile_no');
		const gstinEl = document.getElementById('gstin');
		const panEl = document.getElementById('pan');
		const corrAddrEl = document.getElementById('correspondence_address');
		const billAddrEl = document.getElementById('billing_address');
		const notesEl = document.getElementById('notes');
		const auditMountEl = document.getElementById('auditInfoMount');
		const formCompanyIdEl = document.getElementById('form_company_id');
		const ALL_INPUTS = [codeEl, nameEl, contactEl, emailEl, mobileEl, gstinEl, panEl, corrAddrEl, billAddrEl, notesEl];

		let codeTouched = false;
		function sanitizeCodeVal(v){ return (String(v||'').toUpperCase().replace(/[^A-Z0-9]/g,'')).slice(0,10); }
		function generateCodeFromName(name){
			let s = String(name||'').toUpperCase().replace(/[^A-Z]/g,'');
			if (!s) return '';
			const vowels = new Set(['A','E','I','O','U']);
			let base = s[0] || 'X';
			for (let i=1; i<s.length && base.length<3; i++) { if (!vowels.has(s[i])) base += s[i]; }
			for (let i=1; i<s.length && base.length<3; i++) { if (!base.includes(s[i])) base += s[i]; }
			while (base.length < 3) base += 'X';
			return (base + '01').slice(0,5);
		}

		const auditCtrl = (window.AppUtils && window.AppUtils.AuditInfo && auditMountEl) ? window.AppUtils.AuditInfo.init(auditMountEl) : null;

		function openModal(){ bootstrap.Modal.getOrCreateInstance(modalEl).show(); }
		function closeModal(){ bootstrap.Modal.getOrCreateInstance(modalEl).hide(); }
		function setMode(mode){ if (form) form.dataset.mode = mode; }
		function setTitleFor(mode){ const map = { create: 'Add', edit: 'Edit', view: 'View' }; if (titleTextEl) titleTextEl.textContent = PAGE_TITLE + ' [' + (map[mode]||'Add') + ']'; }

		function applyModeUI(mode){
			const ro = (mode === 'view');
			ALL_INPUTS.forEach(el => { if (el) el.disabled = ro; });
			if (submitBtn) submitBtn.style.display = (mode === 'view') ? 'none' : '';
			if (auditCtrl) auditCtrl.showFor(mode === 'create' ? 'create' : mode);
		}

		function resetFormForCreate(){
			setMode('create'); setTitleFor('create');
			if (form) form.reset();
			if (inputId) inputId.value = '';
			if (form) form.action = addUrl;
			applyModeUI('create');
			if (auditCtrl) auditCtrl.set({ createdAt: null, updatedAt: null });
			codeTouched = false;
			try { ALL_INPUTS.forEach(el => { if (el) el.classList.remove('is-invalid'); }); if (form) form.classList.remove('was-validated'); } catch(_) {}
			try { if (submitBtn && submitBtn.querySelector('span')) submitBtn.querySelector('span').textContent = 'Save'; } catch(_) {}
			if (IS_SUPER) { const cid = getSelectedBusinessId(); if (!cid) { AppUtils.notify('Please select a business first.', { type: 'error' }); return false; } if (formCompanyIdEl) formCompanyIdEl.value = cid; }
			return true;
		}

		function populateForm(row, mode){
			setMode(mode); setTitleFor(mode);
			if (form) form.action = (mode === 'create') ? addUrl : updateUrl;
			if (inputId) inputId.value = row.id || '';
			if (codeEl) codeEl.value = row.customer_code || '';
			if (nameEl) nameEl.value = row.customer_name || '';
			if (contactEl) contactEl.value = row.contact_person || '';
			if (emailEl) emailEl.value = row.email || '';
			if (mobileEl) mobileEl.value = row.mobile_no || '';
			if (gstinEl) gstinEl.value = row.gstin || '';
			if (panEl) panEl.value = row.pan || '';
			if (corrAddrEl) corrAddrEl.value = row.correspondence_address || '';
			if (billAddrEl) billAddrEl.value = row.billing_address || '';
			if (notesEl) notesEl.value = row.notes || '';
			applyModeUI(mode);
			if (auditCtrl) auditCtrl.set({ createdAt: row.created_at, updatedAt: row.updated_at, createdBy: row.created_by, updatedBy: row.lastmodified_by });
			if (IS_SUPER) { const cid = getSelectedBusinessId(); if (formCompanyIdEl) formCompanyIdEl.value = cid; }
			codeTouched = true;
			try { ALL_INPUTS.forEach(el => { if (el) el.classList.remove('is-invalid'); }); if (form) form.classList.remove('was-validated'); } catch(_) {}
			try { if (submitBtn && submitBtn.querySelector('span')) submitBtn.querySelector('span').textContent = mode === 'edit' ? 'Update' : 'Save'; } catch(_) {}
			openModal();
		}

		function populateFormForEdit(row){ populateForm(row, 'edit'); }
		function populateFormForView(row){ populateForm(row, 'view'); }

		function onSetActive(val, id){
			if (!PERMS.edit) return;
			const question = val ? 'Activate this customer?' : 'Deactivate this customer?';
			AppUtils.Actions.deleteResource({ url: setActiveUrl, method: 'POST', data: { id: id, is_active: val }, confirm: { title: 'Are you sure?', text: question, confirmButtonText: val ? 'Yes, activate' : 'Yes, mark inactive' }, onSuccess: refreshGrid });
		}

		if (window.AppUtils && AppUtils.FormHelpers && form) {
			AppUtils.FormHelpers.attachAjaxSubmit(form, {
				submitBtn, method: 'POST',
				getMode: () => (form.dataset.mode || 'create'),
				errorTargets: { customer_code: '#customer_code_error', customer_name: '#customer_name_error', email: '#email_error', mobile_no: '#mobile_no_error', gstin: '#gstin_error', pan: '#pan_error', correspondence_address: '#correspondence_address_error' },
				beforeSubmit: ({ form, mode }) => {
					if (mode !== 'edit') { try { form.querySelector('#customer_id').value = ''; } catch(_) {} }
					if (IS_SUPER) { const cid = getSelectedBusinessId(); if (!cid) { AppUtils.notify('Please select a business first.', { type: 'error' }); return false; } if (formCompanyIdEl) formCompanyIdEl.value = cid; }
				},
				onSuccess: function(json, { mode }){
					const msg = (json && json.message) ? json.message : (mode === 'edit' ? 'Customer updated' : 'Customer created');
					AppUtils.notify(msg, { type: (json && json['alert-type']) ? json['alert-type'] : 'success' });
					try { closeModal(); } catch(_) {}
					refreshGrid();
				}
			});
		}

		if (window.AppUtils && AppUtils.InputHelpers) { if (nameEl) AppUtils.InputHelpers.bindCamelCase(nameEl); }
		if (codeEl) codeEl.addEventListener('input', function(){ codeTouched = true; codeEl.value = sanitizeCodeVal(codeEl.value); });
		if (nameEl) nameEl.addEventListener('input', function(){ if ((form?.dataset?.mode || 'create') === 'create' && codeEl && !codeTouched) { const gen = generateCodeFromName(nameEl.value); if (gen) codeEl.value = gen; } });
		if (mobileEl) mobileEl.addEventListener('input', function(){ mobileEl.value = mobileEl.value.replace(/\D/g,'').slice(0,10); });
		if (gstinEl) gstinEl.addEventListener('input', function(){ gstinEl.value = gstinEl.value.toUpperCase(); });
		if (panEl) panEl.addEventListener('input', function(){ panEl.value = panEl.value.toUpperCase(); });

		modalEl && modalEl.addEventListener('shown.bs.modal', function(){ try { if (nameEl) { nameEl.focus({ preventScroll: true }); if ((form?.dataset?.mode || 'create') === 'edit' && typeof nameEl.select === 'function') nameEl.select(); } } catch(_) {} });
		modalEl && modalEl.addEventListener('hidden.bs.modal', function(){ try { ALL_INPUTS.forEach(el => { if (el) el.classList.remove('is-invalid'); }); if (form) form.classList.remove('was-validated'); } catch(_) {} });

		try { if (window.feather) feather.replace(); } catch(_) {}

		// ---- Super Admin: Business Picker ----
		if (IS_SUPER && window.AppUtils && AppUtils.BusinessPicker) {
			if (!BUSINESS_PICKER_LIST_URL) return;
			function getAddToolbarLI(){ const el = document.getElementById('toolbarAdd'); return el ? el.closest('.e-toolbar-item') : null; }
			const addButtonHandler = function(show){ const li = getAddToolbarLI(); if (li) li.style.display = show ? '' : 'none'; if (headerAddBtn) headerAddBtn.style.display = show ? '' : 'none'; };
			AppUtils.BusinessPicker.init({
				headerSelector: '.card-header.d-flex', noImageUrl: NO_IMAGE, listUrl: BUSINESS_PICKER_LIST_URL, addButtonHandler,
				onClear: function(){ grid.emptyRecordTemplate = NO_BUSINESS_TEMPLATE; grid.dataSource = []; },
				onBusinessChanged: function(company){ grid.emptyRecordTemplate = AppUtils.emptyRecordTemplate('Customer Register'); AppUtils.GridHelpers.loadDataToGrid(grid, listUrl + '?company_id=' + encodeURIComponent(String(company.id)), { dateFields: DATE_FIELDS }); }
			});
		}
	});
})();
</script>

@include('admin.master.partials.customer_modal', ['idPrefix' => ''])

@endsection
