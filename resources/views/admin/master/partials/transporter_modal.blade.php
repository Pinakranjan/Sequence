@php
    $idPrefix = isset($idPrefix) ? (string) $idPrefix : '';
    $buildId = function (string $suffix) use ($idPrefix) {
        return $idPrefix . $suffix;
    };
@endphp

{{-- Reusable Transporter Add/Edit/View Modal --}}
<div class="modal fade" id="{{ $buildId('transporterModal') }}" tabindex="-1"
    aria-labelledby="{{ $buildId('transporterModalLabel') }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:760px;">
        <div class="modal-content">
            <div class="modal-header py-2" style="min-height:42px;">
                <h5 class="modal-title d-flex align-items-center fs-6" id="{{ $buildId('transporterModalLabel') }}">
                    <i class="fa-solid fa-truck me-2" style="width:18px;height:18px;line-height:18px;"></i>
                    <span id="{{ $buildId('modalTitle') }}">Add Transporter</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="{{ $buildId('transporterForm') }}" method="POST" action="{{ url('/transporter/create') }}"
                enctype="multipart/form-data" data-mode="create" novalidate>
                @csrf
                <input type="hidden" id="{{ $buildId('form_company_id') }}" name="company_id" value="">
                <input type="hidden" id="{{ $buildId('transporter_id') }}" name="id" />
                <div class="modal-body">
                    <div class="col-12">
                        <div class="row g-2 align-items-start">
                            <div class="col-12 col-md-8 pe-md-2">
                                <label for="{{ $buildId('transporter_name') }}" class="form-label">Transporter Name
                                    <span class="text-danger">*</span></label>
                                <input type="text" id="{{ $buildId('transporter_name') }}" name="transporter_name"
                                    class="form-control" maxlength="100" autocomplete="off" required />
                                <div class="invalid-feedback" id="{{ $buildId('transporter_name_error') }}">Transporter
                                    name is required.</div>
                            </div>
                            <div class="col-12 col-md-4 ps-md-2">
                                <label for="{{ $buildId('transporter_code') }}" class="form-label">Transporter Code
                                    <span class="text-danger">*</span></label>
                                <input type="text" id="{{ $buildId('transporter_code') }}" name="transporter_code"
                                    class="form-control text-uppercase text-center fw-bold"
                                    style="text-transform: uppercase;" maxlength="10" autocomplete="off" required />
                                <div class="invalid-feedback" id="{{ $buildId('transporter_code_error') }}">Transporter
                                    code is required.</div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-12">
                            <label for="{{ $buildId('contact_person') }}" class="form-label">Contact Person</label>
                            <input type="text" id="{{ $buildId('contact_person') }}" name="contact_person"
                                class="form-control" maxlength="100" autocomplete="off" />
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <div class="row gx-4 gy-2 gx-2">
                            <div class="col-12 col-md-6 pe-md-2">
                                <label for="{{ $buildId('email') }}" class="form-label">Email <span
                                        class="text-danger">*</span></label>
                                <input type="email" class="form-control text-lowercase" id="{{ $buildId('email') }}"
                                    name="email" maxlength="50" autocomplete="off" required>
                                <div class="invalid-feedback" id="{{ $buildId('email_error') }}">Enter a valid email
                                    address.</div>
                            </div>
                            <div class="col-12 col-md-6 ps-md-2">
                                <label for="{{ $buildId('mobile_no') }}" class="form-label">Mobile <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control text-center" id="{{ $buildId('mobile_no') }}"
                                    name="mobile_no" inputmode="numeric" pattern="\d{10}" maxlength="10"
                                    autocomplete="off" required>
                                <div class="invalid-feedback" id="{{ $buildId('mobile_no_error') }}">Mobile must be
                                    exactly 10 digits.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <div class="row gx-4 gy-2 gx-2">
                            <div class="col-12 col-md-6 pe-md-2">
                                <label for="{{ $buildId('gstin') }}" class="form-label">GSTIN</label>
                                <input type="text" class="form-control text-center" id="{{ $buildId('gstin') }}"
                                    name="gstin" maxlength="15" autocomplete="off" placeholder="Enter GSTIN"
                                    pattern="[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]"
                                    style="text-transform: uppercase;">
                                <div class="invalid-feedback" id="{{ $buildId('gstin_error') }}">Enter a valid GSTIN.
                                </div>
                            </div>
                            <div class="col-12 col-md-6 ps-md-2">
                                <label for="{{ $buildId('pan') }}" class="form-label">PAN</label>
                                <input type="text" class="form-control text-center" id="{{ $buildId('pan') }}"
                                    name="pan" maxlength="10" autocomplete="off" placeholder="Enter PAN"
                                    pattern="[A-Z]{5}[0-9]{4}[A-Z]" style="text-transform: uppercase;">
                                <div class="invalid-feedback" id="{{ $buildId('pan_error') }}">Enter a valid PAN.</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <label for="{{ $buildId('correspondence_address') }}" class="form-label">Correspondence Address
                            <span class="text-danger">*</span></label>
                        <textarea id="{{ $buildId('correspondence_address') }}" name="correspondence_address" rows="3"
                            class="form-control" maxlength="500" autocomplete="off"
                            placeholder="Enter Correspondence Address" required></textarea>
                        <div class="invalid-feedback" id="{{ $buildId('correspondence_address_error') }}">Correspondence
                            address is required.</div>
                    </div>
                    <div class="mt-2">
                        <label for="{{ $buildId('billing_address') }}" class="form-label">Billing Address</label>
                        <textarea id="{{ $buildId('billing_address') }}" name="billing_address" rows="3"
                            class="form-control" maxlength="500" autocomplete="off"
                            placeholder="Enter Billing Address"></textarea>
                    </div>
                    <div class="mt-2">
                        <label for="{{ $buildId('notes') }}" class="form-label">Notes</label>
                        <textarea id="{{ $buildId('notes') }}" name="notes" rows="2" class="form-control"
                            placeholder="Optional notes..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <div class="w-100 d-flex align-items-center justify-content-between">
                        <div class="d-flex flex-column" id="{{ $buildId('auditInfoMount') }}"></div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal"><i
                                    data-feather="x" class="me-1"></i><span>Cancel</span></button>
                            <button type="submit" class="btn btn-primary" id="{{ $buildId('submitBtn') }}"><i
                                    data-feather="save" class="me-1"></i><span>Save</span></button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>