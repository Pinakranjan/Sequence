<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\HasFormPermissions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Master\Customer;
use App\Models\Utility\Business;
use Throwable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class CustomerController extends Controller
{
    use HasFormPermissions;

    private const FORM_ID = 4;

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

            if (!$allowed) {
                abort(403, 'You do not have permission to access this page.');
            }
        }

        return view('admin.master.get_customers');
    }

    public function ListCustomers(Request $request)
    {
        $user = Auth::user();
        $role = $user?->role ?? 'user';
        $normRole = strtolower(str_replace('_', ' ', trim($role)));
        $isSuper = ($normRole === 'super admin');

        $q = Customer::query();
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

        $rows = $q->orderBy('id', 'desc')->get();

        return response()->json($rows->map(function ($r) {
            return [
                'id' => (int) $r->id,
                'company_id' => (int) $r->company_id,
                'customer_code' => (string) ($r->customer_code ?? ''),
                'customer_name' => (string) ($r->customer_name ?? ''),
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
            ];
        }));
    }

    public function AddCustomer(Request $request)
    {
        $user = Auth::user();
        $role = $user?->role ?? 'user';
        $normRole = strtolower(str_replace('_', ' ', trim($role)));
        $isSuper = ($normRole === 'super admin');
        $companyId = $isSuper ? (int) $request->input('company_id', 0) : (int) ($user?->company_id ?? 0);

        if (!$this->canDo($user, 'add', self::FORM_ID)) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to add customers.', 'alert-type' => 'error'], 403);
        }

        if ($companyId <= 0) {
            return response()->json(['success' => false, 'message' => 'Please select a business before adding a customer.', 'alert-type' => 'warning'], 422);
        }

        $business = Business::find($companyId);
        if (!$business) {
            return response()->json(['success' => false, 'message' => 'Invalid business selection.', 'alert-type' => 'error'], 422);
        }
        if ((int) ($business->is_active ?? 0) !== 1) {
            return response()->json(['success' => false, 'message' => 'This business is inactive.', 'alert-type' => 'error'], 403);
        }
        if ((int) ($business->is_locked ?? 0) === 1) {
            return response()->json(['success' => false, 'message' => 'This business is locked.', 'alert-type' => 'error'], 403);
        }

        $validator = Validator::make($request->all(), [
            'customer_code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('master_customers', 'customer_code')->where(fn($q) => $q->where('company_id', $companyId))
            ],
            'customer_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('master_customers', 'customer_name')->where(fn($q) => $q->where('company_id', $companyId))
            ],
            'mobile_no' => ['required', 'regex:/^\d{10}$/'],
            'email' => 'required|email|max:100',
            'correspondence_address' => ['required', 'string', 'max:250'],
            'billing_address' => ['nullable', 'string', 'max:250'],
            'gstin' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'pan' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ], [
            'customer_code.required' => 'Customer code is required.',
            'customer_code.unique' => 'Customer code already exists.',
            'customer_name.required' => 'Customer name is required.',
            'customer_name.unique' => 'Customer name already exists.',
            'correspondence_address.required' => 'Correspondence address is required.',
            'gstin.regex' => 'The GSTIN must be a valid format.',
            'pan.regex' => 'The PAN must be a valid format.',
            'mobile_no.regex' => 'The mobile number must be a valid 10-digit number.',
            'email.required' => 'Please provide an email address.',
            'email.email' => 'Please provide a valid email address.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Please fix the error(s).', 'errors' => $validator->errors(), 'alert-type' => 'error'], 422);
        }

        $data = $validator->validated();
        $data['gstin'] = isset($data['gstin']) ? strtoupper(trim($data['gstin'])) : null;
        $data['pan'] = isset($data['pan']) ? strtoupper(trim($data['pan'])) : null;
        $data['email'] = isset($data['email']) ? strtolower(trim($data['email'])) : null;
        if (isset($data['mobile_no'])) {
            $data['mobile_no'] = preg_replace('/\D+/', '', $data['mobile_no']);
        }
        foreach (['correspondence_address', 'billing_address', 'notes', 'contact_person', 'customer_name'] as $k) {
            if (isset($data[$k])) {
                $data[$k] = trim($data[$k]);
            }
        }

        try {
            $row = new Customer();
            $row->company_id = $companyId;
            $row->customer_code = $data['customer_code'];
            $row->customer_name = $data['customer_name'];
            $row->correspondence_address = $data['correspondence_address'] ?? null;
            $row->billing_address = $data['billing_address'] ?? null;
            $row->gstin = $data['gstin'] ?? null;
            $row->pan = $data['pan'] ?? null;
            $row->contact_person = $data['contact_person'] ?? null;
            $row->mobile_no = $data['mobile_no'] ?? null;
            $row->email = $data['email'] ?? null;
            $row->notes = $data['notes'] ?? null;
            $row->is_active = 1;
            $row->created_by = $user?->name;
            $row->save();
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create customer.', 'error' => $e->getMessage(), 'alert-type' => 'error'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Customer created successfully.', 'alert-type' => 'success']);
    }

    public function UpdateCustomer(Request $request)
    {
        $user = Auth::user();
        $role = $user?->role ?? 'user';
        $normRole = strtolower(str_replace('_', ' ', trim($role)));
        $isSuper = ($normRole === 'super admin');
        $companyId = (int) ($user?->company_id ?? 0);

        if (!$this->canDo($user, 'edit', self::FORM_ID)) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to edit customers.', 'alert-type' => 'error'], 403);
        }

        $id = (int) $request->input('id', $request->input('key'));
        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid customer id.', 'alert-type' => 'error'], 422);
        }

        $row = Customer::findOrFail($id);

        if (!$isSuper && (int) $row->company_id !== $companyId) {
            return response()->json(['success' => false, 'message' => 'You are not allowed to modify this customer.', 'alert-type' => 'error'], 403);
        }

        $business = Business::find((int) $row->company_id);
        if (!$business) {
            return response()->json(['success' => false, 'message' => 'Parent business not found.', 'alert-type' => 'error'], 422);
        }
        if ((int) ($business->is_active ?? 0) !== 1) {
            return response()->json(['success' => false, 'message' => 'This business is inactive.', 'alert-type' => 'error'], 403);
        }
        if ((int) ($business->is_locked ?? 0) === 1) {
            return response()->json(['success' => false, 'message' => 'This business is locked.', 'alert-type' => 'error'], 403);
        }

        $validator = Validator::make($request->all(), [
            'customer_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('master_customers', 'customer_name')->where(fn($q) => $q->where('company_id', $row->company_id))->ignore($row->id)
            ],
            'customer_code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('master_customers', 'customer_code')->where(fn($q) => $q->where('company_id', $row->company_id))->ignore($row->id)
            ],
            'mobile_no' => ['required', 'regex:/^\d{10}$/'],
            'email' => 'required|email|max:100',
            'correspondence_address' => ['required', 'string', 'max:250'],
            'billing_address' => ['nullable', 'string', 'max:250'],
            'gstin' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'pan' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ], [
            'customer_code.unique' => 'Customer code already exists.',
            'customer_name.unique' => 'Customer name already exists.',
            'gstin.regex' => 'The GSTIN must be a valid format.',
            'pan.regex' => 'The PAN must be a valid format.',
            'mobile_no.regex' => 'The mobile number must be a valid 10-digit number.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Please fix the error(s).', 'errors' => $validator->errors(), 'alert-type' => 'error'], 422);
        }

        $data = $validator->validated();
        $data['gstin'] = isset($data['gstin']) ? strtoupper(trim($data['gstin'])) : null;
        $data['pan'] = isset($data['pan']) ? strtoupper(trim($data['pan'])) : null;
        $data['email'] = isset($data['email']) ? strtolower(trim($data['email'])) : null;
        if (isset($data['mobile_no'])) {
            $data['mobile_no'] = preg_replace('/\D+/', '', $data['mobile_no']);
        }
        foreach (['correspondence_address', 'billing_address', 'notes', 'contact_person', 'customer_name'] as $k) {
            if (isset($data[$k])) {
                $data[$k] = trim($data[$k]);
            }
        }

        $row->fill([
            'customer_name' => $data['customer_name'],
            'customer_code' => $data['customer_code'],
            'correspondence_address' => $data['correspondence_address'] ?? null,
            'billing_address' => $data['billing_address'] ?? null,
            'gstin' => $data['gstin'] ?? null,
            'pan' => $data['pan'] ?? null,
            'contact_person' => $data['contact_person'] ?? null,
            'mobile_no' => $data['mobile_no'] ?? null,
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        if (!$row->isDirty()) {
            return response()->json(['success' => false, 'message' => 'No changes were made to the customer!', 'alert-type' => 'warning'], 422);
        }

        try {
            $row->lastmodified_by = $user?->name;
            $row->updated_at = now();
            $row->save();
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update customer.', 'alert-type' => 'error'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Customer updated successfully.', 'alert-type' => 'success']);
    }

    public function DeleteCustomer(Request $request)
    {
        $user = Auth::user();
        $role = $user?->role ?? 'user';
        $normRole = strtolower(str_replace('_', ' ', trim($role)));
        $isSuper = ($normRole === 'super admin');

        if (!$this->canDo($user, 'delete', self::FORM_ID)) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to delete customers.', 'alert-type' => 'error'], 403);
        }

        $id = (int) $request->input('id', $request->input('key'));
        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid customer id.', 'alert-type' => 'error'], 422);
        }

        $row = Customer::findOrFail($id);
        if (!$isSuper) {
            $companyId = (int) ($user?->company_id ?? 0);
            if ($companyId <= 0 || (int) $row->company_id !== $companyId) {
                return response()->json(['success' => false, 'message' => 'You are not allowed to delete this customer.', 'alert-type' => 'error'], 403);
            }
        }

        $business = Business::find((int) $row->company_id);
        if (!$business) {
            return response()->json(['success' => false, 'message' => 'Parent business not found.', 'alert-type' => 'error'], 422);
        }
        if ((int) ($business->is_active ?? 0) !== 1) {
            return response()->json(['success' => false, 'message' => 'This business is inactive.', 'alert-type' => 'error'], 403);
        }
        if ((int) ($business->is_locked ?? 0) === 1) {
            return response()->json(['success' => false, 'message' => 'This business is locked.', 'alert-type' => 'error'], 403);
        }

        try {
            $row->delete();
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete customer.', 'error' => $e->getMessage(), 'alert-type' => 'error'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Customer deleted successfully.', 'alert-type' => 'success']);
    }

    public function SetActiveCustomer(Request $request)
    {
        $user = Auth::user();
        $role = $user?->role ?? 'user';
        $normRole = strtolower(str_replace('_', ' ', trim($role)));
        $isSuper = ($normRole === 'super admin');

        if (!$this->canDo($user, 'edit', self::FORM_ID)) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to update status.', 'alert-type' => 'error'], 403);
        }

        $id = (int) $request->input('id', $request->input('key'));
        $isActive = $request->input('is_active', null);
        if ($id <= 0 || !in_array((string) $isActive, ['0', '1'], true)) {
            return response()->json(['success' => false, 'message' => 'Invalid request.', 'alert-type' => 'error'], 422);
        }

        $row = Customer::findOrFail($id);
        if (!$isSuper) {
            $companyId = (int) ($user?->company_id ?? 0);
            if ($companyId <= 0 || (int) $row->company_id !== $companyId) {
                return response()->json(['success' => false, 'message' => 'You are not allowed to modify this customer.', 'alert-type' => 'error'], 403);
            }
        }

        $business = Business::find((int) $row->company_id);
        if (!$business) {
            return response()->json(['success' => false, 'message' => 'Parent business not found.', 'alert-type' => 'error'], 422);
        }
        if ((int) ($business->is_active ?? 0) !== 1) {
            return response()->json(['success' => false, 'message' => 'This business is inactive.', 'alert-type' => 'error'], 403);
        }
        if ((int) ($business->is_locked ?? 0) === 1) {
            return response()->json(['success' => false, 'message' => 'This business is locked.', 'alert-type' => 'error'], 403);
        }

        $newVal = (int) $isActive;
        if ((int) $row->is_active === $newVal) {
            return response()->json(['success' => false, 'message' => 'No changes were made to the status!', 'alert-type' => 'warning']);
        }

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
