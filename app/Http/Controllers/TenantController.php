<?php

namespace App\Http\Controllers;

use App\Enums\TenantType;
use App\Http\Traits\ApiResponse;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $size = $request->query('size', 10);
        $page = $request->query('page', 1);

        $tenants = Tenant::query();
        if ($request->has('search')) {
            $tenants->where('tenant_name', 'like', '%'.$request->search.'%');
        }
        $tenants = $tenants->paginate($size, ['*'], 'page', $page);

        return $this->paginatedResponse($tenants);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant_name' => 'required|string|max:255',
            'tenant_type' => ['required', Rule::enum(TenantType::class)],
            'tenant_phone' => 'nullable|string|max:255',
            'tenant_address' => 'nullable|string|max:255',
        ]);

        if ($request->tenant_type === TenantType::BOOTH->value) {
            $validator->addRules(['booth_num' => 'required|string|max:255']);
        }
        if ($request->tenant_type === TenantType::SPACE_ONLY->value) {
            $validator->addRules(['area_sm' => 'required|numeric']);
        }

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $tenant = Tenant::create($request->all());

        return $this->successResponse($tenant, 'Tenant created successfully');
    }

    public function show(string $id)
    {
        $tenant = Tenant::find($id);
        if (! $tenant) {
            return $this->errorResponse('Tenant not found', 404);
        }

        return $this->successResponse($tenant, 'Tenant fetched successfully');
    }

    public function update(Request $request, string $id)
    {
        $tenant = Tenant::find($id);
        if (! $tenant) {
            return $this->errorResponse('Tenant not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'tenant_name' => 'required|string|max:255',
            'tenant_type' => ['required', Rule::enum(TenantType::class)],
            'tenant_phone' => 'nullable|string|max:255',
            'tenant_address' => 'nullable|string|max:255',
        ]);

        if ($request->tenant_type === TenantType::BOOTH->value) {
            $validator->addRules(['booth_num' => 'required|string|max:255']);
        }
        if ($request->tenant_type === TenantType::SPACE_ONLY->value) {
            $validator->addRules(['area_sm' => 'required|numeric']);
        }

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $tenant->update($request->all());

        return $this->successResponse($tenant, 'Tenant updated successfully');
    }

    public function destroy(string $id)
    {
        $tenant = Tenant::find($id);
        if (! $tenant) {
            return $this->errorResponse('Tenant not found', 404);
        }
        $tenant->delete();

        return $this->successResponse(null, 'Tenant deleted successfully', 204);
    }
}
