<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\HasFormPermissions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Master\Supplier;
use App\Models\Utility\Business;
use Throwable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    use HasFormPermissions;

    private const FORM_ID = 2;

    public function index()
    {
        $user = Auth::user();
        $role = $user?->role ?? 'user';
        $normRole = strtolower(str_replace('_', ' ', trim($role)));
        $isPrivileged = in_array($normRole, ['admin', 'super admin'], true);

        if (!$isPrivileged) {
            $allowed = $this->canDo($user, 'add', self::FORM_ID)
                || $this->canDo($user, 'edit', self::FORM_ID)
                || $this->canDo($user, 'delete', self::FORM_ID)
                || $this->canDo($user, 'view', self::FORM_ID)
                || $this->canDo($user, 'viewatt', self::FORM_ID);
            if (!$allowed)
                abort(403, 'You do not have permission to access this page.');
        }

        return view('admin.master.get_suppliers');
    }

    public function ListSuppliers(Request $request)
    {
        $user = Auth::user();
        $normRole = strtolower(str_replace('_', ' ', trim($user?->role ?? 'user')));
        $isSuper = ($normRole === 'super admin');

        $q = Supplier::query();
        if ($isSuper) {
            $cid = (int) $request->query('company_id', 0);
            if ($cid <= 0)
                return response()->json([]);
            $q->where('company_id', $cid);
        } else {
            $companyId = (int) ($user?->company_id ?? 0);
            if ($companyId <= 0)
                return response()->json([]);
            $q->where('company_id', $companyId);
        }

        return response()->json($q->orderBy('id', 'desc')->get()->map(fn($r) => [
            'id' => (int) $r->id,
            'company_id' => (int) $r->company_id,
            'supplier_code' => (string) ($r->supplier_code ?? ''),
            'supplier_name' => (string) ($r->supplier_name ?? ''),
            'correspondence_address' => (string) ($r->correspondence_address ?? ''),
            'billing_address' => (string) ($r->billing_address ?? ''),
            'gstin' => (string) ($r->gstin ?? ''),
            'pan' => (string) ($r->pan ?? ''),
            'contact_person' => (string) ($r->contact_person ?? ''),
            'mobile_no' => (string) ($r->mobile_no ?? ''),
            'email' => (string) ($r->email ?? ''),
            'notes' => (string) ($r->notes ?? ''),
            'is_active' => (int) ($r->is_active ?? 0) === 1 ? 1 : 0,
            'is_active_text' => ((int) $r->is_active === 1 ? 'Yes' : 'No'),
            'created_by' => $r->created_by,
            'lastmodified_by' => $r->lastmodified_by,
            'created_at' => $r->created_at,
            'updated_at' => $r->updated_at,
        ]));
    }

    public function AddSupplier(Request $request)
    {
        $user = Auth::user();
        $normRole = strtolower(str_replace('_', ' ', trim($user?->role ?? 'user')));
        $isSuper = ($normRole === 'super admin');
        $companyId = $isSuper ? (int) $request->input('company_id', 0) : (int) ($user?->company_id ?? 0);

        if (!$this->canDo($user, 'add', self::FORM_ID))
            return response()->json(['success' => false, 'message' => 'You do not have permission to add suppliers.', 'alert-type' => 'error'], 403);

        if ($companyId <= 0)
            return response()->json(['success' => false, 'message' => 'Please select a business before adding a supplier.', 'alert-type' => 'warning'], 422);

        $business = Business::find($companyId);
        if (!$business)
            return response()->json(['success' => false, 'message' => 'Invalid business selection.', 'alert-type' => 'error'], 422);
        if ((int) ($business->is_active ?? 0) !== 1)
            return response()->json(['success' => false, 'message' => 'This business is inactive.', 'alert-type' => 'error'], 403);
        if ((int) ($business->is_locked ?? 0) === 1)
            return response()->json(['success' => false, 'message' => 'This business is locked.', 'alert-type' => 'error'], 403);

        $validator = Validator::make($request->all(), [
            'supplier_code' => ['required', 'string', 'max:10', Rule::unique('master_suppliers', 'supplier_code')->where(fn($q) => $q->where('company_id', $companyId))],
            'supplier_name' => ['required', 'string', 'max:100', Rule::unique('master_suppliers', 'supplier_name')->where(fn($q) => $q->where('company_id', $companyId))],
            'mobile_no' => ['required', 'regex:/^\d{10}$/'],
            'email' => 'required|email|max:100',
            'correspondence_address' => ['required', 'string', 'max:250'],
            'billing_address' => ['nullable', 'string', 'max:250'],
            'gstin' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'pan' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ], [
            'supplier_code.required' => 'Supplier code is required.',
            'supplier_code.unique' => 'Supplier code already exists.',
            'supplier_name.required' => 'Supplier name is required.',
            'supplier_name.unique' => 'Supplier name already exists.',
            'correspondence_address.required' => 'Correspondence address is required.',
            'gstin.regex' => 'The GSTIN must be a valid format.',
            'pan.regex' => 'The PAN must be a valid format.',
            'mobile_no.regex' => 'The mobile number must be a valid 10-digit number.',
            'email.required' => 'Please provide an email address.',
            'email.email' => 'Please provide a valid email address.',
        ]);

        if ($validator->fails())
            return response()->json(['success' => false, 'message' => 'Please fix the error(s).', 'errors' => $validator->errors(), 'alert-type' => 'error'], 422);

        $data = $validator->validated();
        $data['gstin'] = isset($data['gstin']) ? strtoupper(trim($data['gstin'])) : null;
        $data['pan'] = isset($data['pan']) ? strtoupper(trim($data['pan'])) : null;
        $data['email'] = isset($data['email']) ? strtolower(trim($data['email'])) : null;
        if (isset($data['mobile_no']))
            $data['mobile_no'] = preg_replace('/\D+/', '', $data['mobile_no']);
        foreach (['correspondence_address', 'billing_address', 'notes', 'contact_person', 'supplier_name'] as $k) {
            if (isset($data[$k]))
                $data[$k] = trim($data[$k]);
        }

        try {
            $row = new Supplier();
            $row->company_id = $companyId;
            foreach (['supplier_code', 'supplier_name', 'correspondence_address', 'billing_address', 'gstin', 'pan', 'contact_person', 'mobile_no', 'email', 'notes'] as $f)
                $row->$f = $data[$f] ?? null;
            $row->is_active = 1;
            $row->created_by = $user?->name;
            $row->save();
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create supplier.', 'error' => $e->getMessage(), 'alert-type' => 'error'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Supplier created successfully.', 'alert-type' => 'success']);
    }

    public function UpdateSupplier(Request $request)
    {
        $user = Auth::user();
        $normRole = strtolower(str_replace('_', ' ', trim($user?->role ?? 'user')));
        $isSuper = ($normRole === 'super admin');

        if (!$this->canDo($user, 'edit', self::FORM_ID))
            return response()->json(['success' => false, 'message' => 'You do not have permission to edit suppliers.', 'alert-type' => 'error'], 403);

        $id = (int) $request->input('id', $request->input('key'));
        if ($id <= 0)
            return response()->json(['success' => false, 'message' => 'Invalid supplier id.', 'alert-type' => 'error'], 422);

        $row = Supplier::findOrFail($id);
        if (!$isSuper && (int) $row->company_id !== (int) ($user?->company_id ?? 0))
            return response()->json(['success' => false, 'message' => 'You are not allowed to modify this supplier.', 'alert-type' => 'error'], 403);

        $business = Business::find((int) $row->company_id);
        if (!$business)
            return response()->json(['success' => false, 'message' => 'Parent business not found.', 'alert-type' => 'error'], 422);
        if ((int) ($business->is_active ?? 0) !== 1)
            return response()->json(['success' => false, 'message' => 'This business is inactive.', 'alert-type' => 'error'], 403);
        if ((int) ($business->is_locked ?? 0) === 1)
            return response()->json(['success' => false, 'message' => 'This business is locked.', 'alert-type' => 'error'], 403);

        $validator = Validator::make($request->all(), [
            'supplier_name' => ['required', 'string', 'max:100', Rule::unique('master_suppliers', 'supplier_name')->where(fn($q) => $q->where('company_id', $row->company_id))->ignore($row->id)],
            'supplier_code' => ['required', 'string', 'max:10', Rule::unique('master_suppliers', 'supplier_code')->where(fn($q) => $q->where('company_id', $row->company_id))->ignore($row->id)],
            'mobile_no' => ['required', 'regex:/^\d{10}$/'],
            'email' => 'required|email|max:100',
            'correspondence_address' => ['required', 'string', 'max:250'],
            'billing_address' => ['nullable', 'string', 'max:250'],
            'gstin' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'pan' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails())
            return response()->json(['success' => false, 'message' => 'Please fix the error(s).', 'errors' => $validator->errors(), 'alert-type' => 'error'], 422);

        $data = $validator->validated();
        $data['gstin'] = isset($data['gstin']) ? strtoupper(trim($data['gstin'])) : null;
        $data['pan'] = isset($data['pan']) ? strtoupper(trim($data['pan'])) : null;
        $data['email'] = isset($data['email']) ? strtolower(trim($data['email'])) : null;
        if (isset($data['mobile_no']))
            $data['mobile_no'] = preg_replace('/\D+/', '', $data['mobile_no']);
        foreach (['correspondence_address', 'billing_address', 'notes', 'contact_person', 'supplier_name'] as $k) {
            if (isset($data[$k]))
                $data[$k] = trim($data[$k]);
        }

        $row->fill([
            'supplier_name' => $data['supplier_name'],
            'supplier_code' => $data['supplier_code'],
            'correspondence_address' => $data['correspondence_address'] ?? null,
            'billing_address' => $data['billing_address'] ?? null,
            'gstin' => $data['gstin'] ?? null,
            'pan' => $data['pan'] ?? null,
            'contact_person' => $data['contact_person'] ?? null,
            'mobile_no' => $data['mobile_no'] ?? null,
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        if (!$row->isDirty())
            return response()->json(['success' => false, 'message' => 'No changes were made to the supplier!', 'alert-type' => 'warning'], 422);

        try {
            $row->lastmodified_by = $user?->name;
            $row->updated_at = now();
            $row->save();
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update supplier.', 'alert-type' => 'error'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Supplier updated successfully.', 'alert-type' => 'success']);
    }

    public function DeleteSupplier(Request $request)
    {
        $user = Auth::user();
        $normRole = strtolower(str_replace('_', ' ', trim($user?->role ?? 'user')));
        $isSuper = ($normRole === 'super admin');

        if (!$this->canDo($user, 'delete', self::FORM_ID))
            return response()->json(['success' => false, 'message' => 'You do not have permission to delete suppliers.', 'alert-type' => 'error'], 403);

        $id = (int) $request->input('id', $request->input('key'));
        if ($id <= 0)
            return response()->json(['success' => false, 'message' => 'Invalid supplier id.', 'alert-type' => 'error'], 422);

        $row = Supplier::findOrFail($id);
        if (!$isSuper) {
            $companyId = (int) ($user?->company_id ?? 0);
            if ($companyId <= 0 || (int) $row->company_id !== $companyId)
                return response()->json(['success' => false, 'message' => 'You are not allowed to delete this supplier.', 'alert-type' => 'error'], 403);
        }

        $business = Business::find((int) $row->company_id);
        if (!$business)
            return response()->json(['success' => false, 'message' => 'Parent business not found.', 'alert-type' => 'error'], 422);
        if ((int) ($business->is_active ?? 0) !== 1)
            return response()->json(['success' => false, 'message' => 'This business is inactive.', 'alert-type' => 'error'], 403);
        if ((int) ($business->is_locked ?? 0) === 1)
            return response()->json(['success' => false, 'message' => 'This business is locked.', 'alert-type' => 'error'], 403);

        try {
            $row->delete();
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete supplier.', 'alert-type' => 'error'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Supplier deleted successfully.', 'alert-type' => 'success']);
    }

    public function SetActiveSupplier(Request $request)
    {
        $user = Auth::user();
        $normRole = strtolower(str_replace('_', ' ', trim($user?->role ?? 'user')));
        $isSuper = ($normRole === 'super admin');

        if (!$this->canDo($user, 'edit', self::FORM_ID))
            return response()->json(['success' => false, 'message' => 'You do not have permission to update status.', 'alert-type' => 'error'], 403);

        $id = (int) $request->input('id', $request->input('key'));
        $isActive = $request->input('is_active', null);
        if ($id <= 0 || !in_array((string) $isActive, ['0', '1'], true))
            return response()->json(['success' => false, 'message' => 'Invalid request.', 'alert-type' => 'error'], 422);

        $row = Supplier::findOrFail($id);
        if (!$isSuper) {
            $companyId = (int) ($user?->company_id ?? 0);
            if ($companyId <= 0 || (int) $row->company_id !== $companyId)
                return response()->json(['success' => false, 'message' => 'You are not allowed to modify this supplier.', 'alert-type' => 'error'], 403);
        }

        $business = Business::find((int) $row->company_id);
        if (!$business)
            return response()->json(['success' => false, 'message' => 'Parent business not found.', 'alert-type' => 'error'], 422);
        if ((int) ($business->is_active ?? 0) !== 1)
            return response()->json(['success' => false, 'message' => 'This business is inactive.', 'alert-type' => 'error'], 403);
        if ((int) ($business->is_locked ?? 0) === 1)
            return response()->json(['success' => false, 'message' => 'This business is locked.', 'alert-type' => 'error'], 403);

        $newVal = (int) $isActive;
        if ((int) $row->is_active === $newVal)
            return response()->json(['success' => false, 'message' => 'No changes were made to the status!', 'alert-type' => 'warning']);

        try {
            $row->is_active = $newVal;
            $row->lastmodified_by = $user?->name;
            $row->updated_at = now();
            $row->save();
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update status.', 'alert-type' => 'error'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Status updated successfully.', 'alert-type' => 'success']);
    }
}
