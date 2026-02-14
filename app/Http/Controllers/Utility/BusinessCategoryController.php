<?php

namespace App\Http\Controllers\Utility;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\BusinessCategory;
use Throwable;

class BusinessCategoryController extends Controller
{
    /**
     * Render the Category List page
     */
    public function AllCategories()
    {
        $user = Auth::user();
        $role = strtolower(str_replace('_', ' ', trim($user?->role ?? 'user')));

        // Only Super Admin can access this page
        if ($role !== 'super admin') {
            abort(403, 'You do not have permission to access this page.');
        }

        return view('admin.utility.category.get_categories');
    }

    /**
     * List categories for grid (JSON)
     */
    public function ListCategories(Request $request)
    {
        $user = Auth::user();
        $role = strtolower(str_replace('_', ' ', trim($user?->role ?? 'user')));

        if ($role !== 'super admin') {
            return response()->json([]);
        }

        $rows = BusinessCategory::orderBy('id', 'desc')->get();

        return response()->json($rows->map(function ($r) {
            return [
                'id' => (int) $r->id,
                'name' => (string) $r->name,
                'description' => (string) ($r->description ?? ''),
                'is_active' => (int) ($r->status ?? 1), // Mapping status to is_active for grid
                'is_active_text' => ((int) ($r->status ?? 1) === 1 ? 'Yes' : 'No'),
                'created_by' => $r->created_by,
                'lastmodified_by' => $r->lastmodified_by,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ];
        }));
    }

    /**
     * Create a new category
     */
    public function AddCategory(Request $request)
    {
        $user = Auth::user();
        $role = strtolower(str_replace('_', ' ', trim($user?->role ?? 'user')));

        if ($role !== 'super admin') {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to add categories.',
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100', Rule::unique('utility_business_categories', 'name')],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Category name is required.',
            'name.max' => 'Category name must be at most 100 characters.',
            'name.unique' => 'Category name already exists.',
            'description.max' => 'Description must be at most 500 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please fix the error(s).',
                'errors' => $validator->errors(),
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 422);
        }

        try {
            $category = new BusinessCategory();
            $category->name = trim($request->string('name'));
            $category->description = trim($request->string('description', ''));
            $category->status = 1;
            $category->created_by = $user?->name;
            // updated_at is null by default as UPDATED_AT is null in model
            $category->save();
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category.',
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'alert-type' => 'success',
            'positionClass' => 'toast-bottom-right',
            'category' => [
                'id' => (int) $category->id,
                'name' => (string) $category->name,
                'description' => (string) $category->description,
                'is_active' => (int) $category->is_active,
            ],
        ]);
    }

    /**
     * Update an existing category
     */
    public function UpdateCategory(Request $request)
    {
        $user = Auth::user();
        $role = strtolower(str_replace('_', ' ', trim($user?->role ?? 'user')));

        if ($role !== 'super admin') {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit categories.',
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 403);
        }

        $id = (int) $request->input('id', $request->input('key'));
        if ($id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid category id.',
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 422);
        }

        $row = BusinessCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('utility_business_categories', 'name')->ignore($row->id)
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Category name is required.',
            'name.max' => 'Category name must be at most 100 characters.',
            'name.unique' => 'Category name already exists.',
            'description.max' => 'Description must be at most 500 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please fix the error(s).',
                'errors' => $validator->errors(),
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 422);
        }

        try {
            $row->name = trim($request->string('name'));
            $row->description = trim($request->string('description', ''));

            if (!$row->isDirty(['name', 'description'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No changes were made to the category!',
                    'alert-type' => 'warning',
                    'positionClass' => 'toast-bottom-right',
                ], 422);
            }

            $row->lastmodified_by = $user?->name;
            $row->updated_at = now();
            $row->save();
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category.',
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'alert-type' => 'success',
            'positionClass' => 'toast-bottom-right',
        ]);
    }

    /**
     * Delete a category
     */
    public function DeleteCategory(Request $request)
    {
        $user = Auth::user();
        $role = strtolower(str_replace('_', ' ', trim($user?->role ?? 'user')));

        if ($role !== 'super admin') {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete categories.',
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 403);
        }

        $id = (int) $request->input('id', $request->input('key'));
        if ($id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid category id.',
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 422);
        }

        $row = BusinessCategory::findOrFail($id);

        try {
            $row->delete();
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category.',
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
            'alert-type' => 'success',
            'positionClass' => 'toast-bottom-right',
        ]);
    }

    /**
     * Toggle active status
     */
    public function SetActiveCategory(Request $request)
    {
        $user = Auth::user();
        $role = strtolower(str_replace('_', ' ', trim($user?->role ?? 'user')));

        if ($role !== 'super admin') {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update status.',
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 403);
        }

        $id = (int) $request->input('id', $request->input('key'));
        $status = $request->input('is_active', null);

        if ($id <= 0 || !in_array((string) $status, ['0', '1'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request.',
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 422);
        }

        $row = BusinessCategory::findOrFail($id);
        $newVal = (int) $status;

        if ((int) ($row->status ?? 1) === $newVal) {
            return response()->json([
                'success' => true,
                'message' => 'No changes were made to the status!',
                'alert-type' => 'warning',
                'positionClass' => 'toast-bottom-right',
            ]);
        }

        try {
            $row->status = $newVal;
            $row->lastmodified_by = $user?->name;
            $row->updated_at = now();
            $row->save();
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status.',
                'alert-type' => 'error',
                'positionClass' => 'toast-bottom-right',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'alert-type' => 'success',
            'positionClass' => 'toast-bottom-right',
        ]);
    }
}
