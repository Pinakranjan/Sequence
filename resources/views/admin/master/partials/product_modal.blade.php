@php
    $idPrefix = isset($idPrefix) ? (string) $idPrefix : '';
    $buildId = function (string $suffix) use ($idPrefix) {
        return $idPrefix . $suffix;
    };
@endphp

{{-- Reusable Product Add/Edit/View Modal --}}
<div class="modal fade" id="{{ $buildId('productModal') }}" tabindex="-1"
    aria-labelledby="{{ $buildId('productModalLabel') }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:660px;">
        <div class="modal-content">
            <div class="modal-header py-2" style="min-height:42px;">
                <h5 class="modal-title d-flex align-items-center fs-6" id="{{ $buildId('productModalLabel') }}">
                    <i class="fa-solid fa-box-open me-2" style="width:18px;height:18px;line-height:18px;"></i>
                    <span id="{{ $buildId('modalTitle') }}">Add Product</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="{{ $buildId('productForm') }}" method="POST" action="{{ url('/product/create') }}"
                enctype="multipart/form-data" data-mode="create" novalidate>
                @csrf

                <input type="hidden" id="{{ $buildId('form_company_id') }}" name="company_id" value="">
                <input type="hidden" id="{{ $buildId('product_id') }}" name="id" />
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="{{ $buildId('product_name') }}" class="form-label">Product Name <span
                                class="text-danger">*</span></label>
                        <input type="text" id="{{ $buildId('product_name') }}" name="product_name" class="form-control"
                            maxlength="100" autocomplete="off" required />
                        <div class="invalid-feedback" id="{{ $buildId('product_name_error') }}">Product name is
                            required.</div>
                    </div>

                    <div class="mb-3">
                        <label for="{{ $buildId('product_code') }}" class="form-label">Product Code <span
                                class="text-danger">*</span></label>
                        <input type="text" id="{{ $buildId('product_code') }}" name="product_code"
                            class="form-control text-uppercase text-center fw-bold" style="text-transform: uppercase;"
                            maxlength="10" autocomplete="off" required />
                        <div class="invalid-feedback" id="{{ $buildId('product_code_error') }}">Product code is
                            required.</div>
                    </div>

                    <div class="mb-3">
                        <label for="{{ $buildId('product_type') }}" class="form-label">Product Type <span
                                class="text-danger">*</span></label>
                        <select id="{{ $buildId('product_type') }}" name="product_type" class="form-select" required>
                            <option value="0">OUTBOUND</option>
                            <option value="1">INBOUND</option>
                            <option value="2">INTERNAL</option>
                        </select>
                        <div class="invalid-feedback" id="{{ $buildId('product_type_error') }}">Please select a product
                            type.</div>
                    </div>
                </div>

                <div class="modal-footer">
                    <div class="w-100 d-flex align-items-center justify-content-between">
                        <div class="d-flex flex-column" id="{{ $buildId('auditInfoMount') }}"></div>

                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
                                <i data-feather="x" class="me-1"></i>
                                <span>Cancel</span>
                            </button>

                            <button type="submit" class="btn btn-primary" id="{{ $buildId('submitBtn') }}">
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