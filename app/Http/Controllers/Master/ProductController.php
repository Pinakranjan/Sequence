<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\HasFormPermissions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Master\Product;
use App\Models\Utility\Business;
use Throwable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ProductController extends Controller
{
    use HasFormPermissions;

    private const FORM_ID = 1;

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

        return view('admin.master.get_products');
    }

    public function ListProducts(Request $request)
    {
        $user = Auth::user();
        $role = $user?->role ?? 'user';
        $normRole = strtolower(str_replace('_', ' ', trim($role)));
        $isSuper = ($normRole === 'super admin');

        $q = Product::query();
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
                'product_code' => (string) ($r->product_code ?? ''),
                'product_name' => (string) ($r->product_name ?? ''),
                'product_type' => (int) ($r->product_type ?? 0),
                'product_type_text' => Product::productTypeLabel((int) ($r->product_type ?? 0)),
                'is_active' => (int) ($r->is_active ?? 0) === 1 ? 1 : 0,
                'is_active_text' => ((int) $r->is_active === 1 ? 'Yes' : 'No'),
                'created_by' => $r->created_by,
                'lastmodified_by' => $r->lastmodified_by,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ];
        }));
    }

    public function AddProduct(Request $request)
    {
        $user = Auth::user();
        $role = $user?->role ?? 'user';
        $normRole = strtolower(str_replace('_', ' ', trim($role)));
        $isSuper = ($normRole === 'super admin');
        $companyId = $isSuper ? (int) $request->input('company_id', 0) : (int) ($user?->company_id ?? 0);

        if (!$this->canDo($user, 'add', self::FORM_ID)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add products.',
                'alert-type' => 'error',
            ], 403);
        }

        if ($companyId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a business before adding a product.',
                'alert-type' => 'warning',
            ], 422);
        }

        $business = Business::find($companyId);
        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid business selection.',
                'alert-type' => 'error',
            ], 422);
        }

        if ((int) ($business->is_active ?? 0) !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'This business is inactive.',
                'alert-type' => 'error',
            ], 403);
        }

        if ((int) ($business->is_locked ?? 0) === 1) {
            return response()->json([
                'success' => false,
                'message' => 'This business is locked.',
                'alert-type' => 'error',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'product_code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('master_products', 'product_code')->where(function ($q) use ($companyId) {
                    return $q->where('company_id', $companyId);
                })
            ],
            'product_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('master_products', 'product_name')->where(function ($q) use ($companyId) {
                    return $q->where('company_id', $companyId);
                })
            ],
            'product_type' => ['required', 'integer', 'in:0,1,2'],
        ], [
            'product_code.required' => 'Product code is required.',
            'product_code.max' => 'Product code must be at most 10 characters.',
            'product_code.unique' => 'Product code already exists for this business.',
            'product_name.required' => 'Product name is required.',
            'product_name.max' => 'Product name must be at most 100 characters.',
            'product_name.unique' => 'Product name already exists for this business.',
            'product_type.required' => 'Please select a product type.',
            'product_type.in' => 'Invalid product type.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please fix the error(s).',
                'errors' => $validator->errors(),
                'alert-type' => 'error',
            ], 422);
        }

        $data = $validator->validated();

        try {
            $row = new Product();
            $row->company_id = $companyId;
            $row->product_code = $data['product_code'];
            $row->product_name = trim($data['product_name']);
            $row->product_type = (int) $data['product_type'];
            $row->is_active = 1;
            $row->created_by = $user?->name;
            $row->save();
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product.',
                'error' => $e->getMessage(),
                'alert-type' => 'error',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'alert-type' => 'success',
        ]);
    }

    public function UpdateProduct(Request $request)
    {
        $user = Auth::user();
        $role = $user?->role ?? 'user';
        $normRole = strtolower(str_replace('_', ' ', trim($role)));
        $isSuper = ($normRole === 'super admin');
        $companyId = (int) ($user?->company_id ?? 0);

        if (!$this->canDo($user, 'edit', self::FORM_ID)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit products.',
                'alert-type' => 'error',
            ], 403);
        }

        $id = (int) $request->input('id', $request->input('key'));
        if ($id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid product id.',
                'alert-type' => 'error',
            ], 422);
        }

        $row = Product::findOrFail($id);

        if (!$isSuper) {
            if ((int) $row->company_id !== $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to modify this product.',
                    'alert-type' => 'error',
                ], 403);
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

        $validator = Validator::make($request->all(), [
            'product_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('master_products', 'product_name')->where(function ($q) use ($row) {
                    return $q->where('company_id', $row->company_id);
                })->ignore($row->id)
            ],
            'product_code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('master_products', 'product_code')->where(function ($q) use ($row) {
                    return $q->where('company_id', $row->company_id);
                })->ignore($row->id)
            ],
            'product_type' => ['required', 'integer', 'in:0,1,2'],
        ], [
            'product_code.required' => 'Product code is required.',
            'product_code.unique' => 'Product code already exists for this business.',
            'product_name.required' => 'Product name is required.',
            'product_name.unique' => 'Product name already exists for this business.',
            'product_type.required' => 'Please select a product type.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please fix the error(s).',
                'errors' => $validator->errors(),
                'alert-type' => 'error',
            ], 422);
        }

        $data = $validator->validated();

        $row->fill([
            'product_name' => trim($data['product_name']),
            'product_code' => $data['product_code'],
            'product_type' => (int) $data['product_type'],
        ]);

        if (!$row->isDirty()) {
            return response()->json([
                'success' => false,
                'message' => 'No changes were made to the product!',
                'alert-type' => 'warning',
            ], 422);
        }

        try {
            $row->lastmodified_by = $user?->name;
            $row->updated_at = now();
            $row->save();
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product.',
                'alert-type' => 'error',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'alert-type' => 'success',
        ]);
    }

    public function DeleteProduct(Request $request)
    {
        $user = Auth::user();
        $role = $user?->role ?? 'user';
        $normRole = strtolower(str_replace('_', ' ', trim($role)));
        $isSuper = ($normRole === 'super admin');

        if (!$this->canDo($user, 'delete', self::FORM_ID)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete products.',
                'alert-type' => 'error',
            ], 403);
        }

        $id = (int) $request->input('id', $request->input('key'));
        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid product id.', 'alert-type' => 'error'], 422);
        }

        $row = Product::findOrFail($id);
        if (!$isSuper) {
            $companyId = (int) ($user?->company_id ?? 0);
            if ($companyId <= 0 || (int) $row->company_id !== $companyId) {
                return response()->json(['success' => false, 'message' => 'You are not allowed to delete this product.', 'alert-type' => 'error'], 403);
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product.',
                'error' => $e->getMessage(),
                'alert-type' => 'error',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
            'alert-type' => 'success',
        ]);
    }

    public function SetActiveProduct(Request $request)
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

        $row = Product::findOrFail($id);
        if (!$isSuper) {
            $companyId = (int) ($user?->company_id ?? 0);
            if ($companyId <= 0 || (int) $row->company_id !== $companyId) {
                return response()->json(['success' => false, 'message' => 'You are not allowed to modify this product.', 'alert-type' => 'error'], 403);
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

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'alert-type' => 'success',
        ]);
    }
}
