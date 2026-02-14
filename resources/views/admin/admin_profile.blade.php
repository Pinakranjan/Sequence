@extends('admin.admin_master')
@section('admin')

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <style>
        /* smaller card header for this page only */
        .card-header.sm-header {
            padding: .45rem 1rem;
        }

        .card-header.sm-header .card-title {
            font-size: 1rem;
            margin-bottom: 0;
        }

        /* make the preview image clearly clickable */
        #showImage {
            cursor: pointer;
        }
    </style>
    <div class="content">
        <!-- Start Content-->
        <div class="container-xxl">
            <div class="py-3 d-flex align-items-sm-center flex-sm-row flex-column">
                <div class="flex-grow-1">
                    <h4 class="fs-18 fw-semibold m-0">Profile</h4>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="align-items-center">
                                <div class="d-flex align-items-center">
                                    <img src="{{ $profileData->photo_url }}"
                                        class="rounded-circle avatar-xl img-thumbnail float-start" alt="image profile">
                                    <div class="overflow-hidden ms-4">
                                        @php
                                            $rawRole = trim((string) ($profileData->role ?? 'User'));
                                            $roleLabel = match (strtolower($rawRole)) {
                                                'super admin' => 'Super Admin',
                                                'admin' => 'Admin',
                                                default => 'User',
                                            };
                                            $roleBadgeClass = match ($roleLabel) {
                                                'Super Admin' => 'bg-danger',
                                                'Admin' => 'bg-success',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <style>.custom-orange-badge { background-color: #fc8019 !important; color: #fff !important; }</style>
                                        <div class="d-flex align-items-center gap-2">
                                            <h4 class="m-0 text-dark fs-20">{{ $profileData->name }}</h4>
                                            <span class="badge {{$roleBadgeClass}} text-uppercase align-middle"
                                                style="font-size: 0.55rem; line-height: 1;">{{ $roleLabel }}</span>
                                        </div>
                                        <p class="my-1 text-muted fs-16">{{ $profileData->email }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane pt-1" id="profile_setting" role="tabpanel" aria-labelledby="setting_tab">
                                <div class="row g-3 g-md-4">
                                    <div class="col-12 col-md-6 mb-3 mb-md-0 pe-md-2">
                                        <div class="card border mb-0 h-100">

                                            <div class="card-header sm-header">
                                                <div class="row align-items-center">
                                                    <div class="col">
                                                        <h4 class="card-title mb-0"><i
                                                                class="mdi mdi-account me-2"></i>Personal Information</h4>
                                                    </div><!--end col-->
                                                </div>
                                            </div>

                                            <form action="{{ route('profile.store') }}" method="POST"
                                                enctype="multipart/form-data" class="d-flex flex-column" novalidate>
                                                @csrf

                                                <div class="card-body flex-grow-1">
                                                    <div class="form-group mb-3 row">
                                                        <label class="form-label">Name</label>
                                                        <div class="col-lg-12 col-xl-12">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="mdi mdi-account"></i></span>
                                                                <input id="name"
                                                                    class="form-control @error('name') is-invalid @enderror"
                                                                    type="text" name="name"
                                                                    value="{{ old('name', $profileData->name) }}">
                                                                @error('name')
                                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                                @enderror
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group mb-3 row">
                                                        <label class="form-label">Email</label>
                                                        <div class="col-lg-12 col-xl-12">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="mdi mdi-email"></i></span>
                                                                <input type="email" name="email" class="form-control"
                                                                    value="{{ $profileData->email }}" placeholder="Email"
                                                                    aria-describedby="basic-addon1" readonly
                                                                    style="background: #f0f0f0; filter: grayscale(100%);">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group mb-3 row">
                                                        <label class="form-label">Phone</label>
                                                        <div class="col-lg-12 col-xl-12">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="mdi mdi-phone-outline"></i></span>
                                                                <input id="phone"
                                                                    class="form-control @error('phone') is-invalid @enderror"
                                                                    type="text" name="phone" placeholder="Phone"
                                                                    aria-describedby="basic-addon1"
                                                                    value="{{ old('phone', $profileData->phone) }}"
                                                                    inputmode="numeric" pattern="[0-9]*" maxlength="10"
                                                                    autocomplete="tel">
                                                                @error('phone')
                                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                                @enderror
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group mb-3 row">
                                                        <label class="form-label">Address</label>
                                                        <div class="col-lg-12 col-xl-12">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i
                                                                        class="mdi mdi-map-marker"></i></span>
                                                                <textarea id="address" name="address"
                                                                    class="form-control @error('address') is-invalid @enderror">{{ old('address', $profileData->address) }}</textarea>
                                                                @error('address')
                                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                                @enderror
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group mb-3 row">
                                                        <label class="form-label">Profile Photo</label>
                                                        <div class="col-lg-12 col-xl-12">
                                                            <div class="input-group">
                                                                <input
                                                                    class="form-control @error('photo') is-invalid @enderror"
                                                                    type="file" name="photo" id="image"
                                                                    aria-describedby="basic-addon1" accept="image/*">
                                                                @error('photo')
                                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                                @enderror
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div><!--end card-body-->

                                                <div class="card-footer bg-transparent border-0 text-center">
                                                    <img id="showImage" src="{{ $profileData->photo_url }}"
                                                        class="rounded-circle avatar-xl img-thumbnail mb-3"
                                                        alt="image profile" role="button" tabindex="0"
                                                        aria-label="Change profile photo">
                                                    <br>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="mdi mdi-content-save"></i> Save Changes
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6 ps-md-2">
                                        <!-- Change Password Card -->
                                        <div class="card border mb-3 w-100">
                                            <div class="card-header sm-header">
                                                <div class="row align-items-center">
                                                    <div class="col">
                                                        <h4 class="card-title mb-0"><i
                                                                class="mdi mdi-lock-outline me-2"></i>Change Password</h4>
                                                    </div><!--end col-->
                                                </div>
                                            </div>

                                            <form action="{{ route('admin.password.update') }}" method="post"
                                                class="d-flex flex-column">
                                                @csrf

                                                <div class="card-body mb-0 flex-grow-1">
                                                    <div class="form-group mb-3 row">
                                                        <label class="form-label">Old Password</label>
                                                        <div class="col-lg-12 col-xl-12">
                                                            <input
                                                                class="form-control @error('old_password') is-invalid @enderror"
                                                                type="password" name="old_password" id="old_password"
                                                                placeholder="Old Password">
                                                            @error('old_password')
                                                                <span class="text-danger text-sm">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="form-group mb-3 row">
                                                        <label class="form-label">New Password</label>
                                                        <div class="col-lg-12 col-xl-12">
                                                            <input
                                                                class="form-control @error('new_password') is-invalid @enderror"
                                                                type="password" name="new_password" id="new_password"
                                                                placeholder="New Password">
                                                            @error('new_password')
                                                                <span class="text-danger text-sm">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="form-group mb-3 row">
                                                        <label class="form-label">Confirm Password</label>
                                                        <div class="col-lg-12 col-xl-12">
                                                            <input
                                                                class="form-control @error('confirm_password') is-invalid @enderror"
                                                                type="password" name="confirm_password"
                                                                id="confirm_password" placeholder="Confirm Password">
                                                            @error('confirm_password')
                                                                <span class="text-danger text-sm">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div><!--end card-body-->

                                                <div class="card-footer bg-transparent border-0 mt-auto">
                                                    <div class="d-flex justify-content-center">
                                                        <button type="submit" class="btn btn-primary px-4">
                                                            <i class="mdi mdi-lock-reset me-1"></i> Change Password
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="mt-3"></div>
                                        <!-- PIN Setup Card -->
                                        <div class="card border mb-0 w-100">
                                            <div class="card-header sm-header">
                                                <div class="row align-items-center">
                                                    <div class="col">
                                                        <h4 class="card-title mb-0">
                                                            <i class="mdi mdi-dialpad me-2"></i>Setup PIN
                                                            @if ($profileData->pin_enabled)
                                                                <span class="badge bg-success ms-2">Active</span>
                                                            @elseif($profileData->pin)
                                                                <span class="badge bg-warning ms-2">Inactive</span>
                                                            @else
                                                                <span class="badge bg-secondary ms-2">Not Set</span>
                                                            @endif
                                                        </h4>
                                                    </div>
                                                </div>
                                            </div>

                                            <form action="{{ route('profile.pin.save') }}" method="post" id="pinSetupForm">
                                                @csrf
                                                <div class="card-body">
                                                    <p class="text-muted small mb-3">Set a 4-digit PIN as an alternative to
                                                        your password for quick login.</p>

                                                    <div class="form-group mb-3">
                                                        <label class="form-label d-block text-center">Enter PIN</label>
                                                        <div
                                                            class="pin-input-container d-flex gap-2 justify-content-center">
                                                            <input type="text" maxlength="1"
                                                                class="form-control pin-digit text-center" data-index="0"
                                                                inputmode="numeric"
                                                                style="width: 50px; height: 55px; font-size: 20px; font-weight: bold;">
                                                            <input type="text" maxlength="1"
                                                                class="form-control pin-digit text-center" data-index="1"
                                                                inputmode="numeric"
                                                                style="width: 50px; height: 55px; font-size: 20px; font-weight: bold;">
                                                            <input type="text" maxlength="1"
                                                                class="form-control pin-digit text-center" data-index="2"
                                                                inputmode="numeric"
                                                                style="width: 50px; height: 55px; font-size: 20px; font-weight: bold;">
                                                            <input type="text" maxlength="1"
                                                                class="form-control pin-digit text-center" data-index="3"
                                                                inputmode="numeric"
                                                                style="width: 50px; height: 55px; font-size: 20px; font-weight: bold;">
                                                        </div>
                                                        <input type="hidden" name="pin" id="pinHidden" value="">
                                                        @error('pin')
                                                            <div class="text-danger text-sm text-center mt-2">{{ $message }}
                                                            </div>
                                                        @enderror
                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <label class="form-label d-block text-center">Confirm PIN</label>
                                                        <div
                                                            class="pin-input-container d-flex gap-2 justify-content-center">
                                                            <input type="text" maxlength="1"
                                                                class="form-control pin-confirm-digit text-center"
                                                                data-index="0" inputmode="numeric"
                                                                style="width: 50px; height: 55px; font-size: 20px; font-weight: bold;">
                                                            <input type="text" maxlength="1"
                                                                class="form-control pin-confirm-digit text-center"
                                                                data-index="1" inputmode="numeric"
                                                                style="width: 50px; height: 55px; font-size: 20px; font-weight: bold;">
                                                            <input type="text" maxlength="1"
                                                                class="form-control pin-confirm-digit text-center"
                                                                data-index="2" inputmode="numeric"
                                                                style="width: 50px; height: 55px; font-size: 20px; font-weight: bold;">
                                                            <input type="text" maxlength="1"
                                                                class="form-control pin-confirm-digit text-center"
                                                                data-index="3" inputmode="numeric"
                                                                style="width: 50px; height: 55px; font-size: 20px; font-weight: bold;">
                                                        </div>
                                                        <input type="hidden" name="pin_confirmation" id="pinConfirmHidden"
                                                            value="">
                                                        @error('pin_confirmation')
                                                            <div class="text-danger text-sm text-center mt-2">{{ $message }}
                                                            </div>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="card-footer bg-transparent border-0 mt-auto">
                                                    <div class="d-flex justify-content-center gap-2">
                                                        <button type="submit" class="btn btn-primary px-4">
                                                            <i class="mdi mdi-content-save me-1"></i> Save PIN
                                                        </button>
                                                        @if ($profileData->pin)
                                                            <button type="button"
                                                                class="btn {{ $profileData->pin_enabled ? 'btn-warning' : 'btn-success' }} px-3"
                                                                id="togglePinBtn">
                                                                <i
                                                                    class="mdi {{ $profileData->pin_enabled ? 'mdi-toggle-switch-off' : 'mdi-toggle-switch' }} me-1"></i>
                                                                {{ $profileData->pin_enabled ? 'Deactivate' : 'Activate' }}
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- end education -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        $(document).ready(function () {
            // Restrict phone to digits only and max 10 characters
            const phoneEl = document.getElementById('phone');
            if (phoneEl) {
                const sanitizePhone = (el) => {
                    const clean = el.value.replace(/\D/g, '').slice(0, 10);
                    if (el.value !== clean) el.value = clean;
                };
                phoneEl.addEventListener('input', () => sanitizePhone(phoneEl));
                phoneEl.addEventListener('paste', () => setTimeout(() => sanitizePhone(phoneEl), 0));
            }
            $('#image').change(function (e) {
                const input = e.target;
                if (input && input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function (ev) {
                        $('#showImage').attr('src', ev.target.result);
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            });

            // clicking the visible preview image should open the file chooser
            $('#showImage').on('click keydown', function (e) {
                // open file chooser on click or Enter key
                if (e.type === 'click' || e.key === 'Enter' || e.keyCode === 13) {
                    $('#image').trigger('click');
                }
            });

            // Focus the first error in Personal Information form, else password form
            @if($errors->has('name') || $errors->has('phone') || $errors->has('address') || $errors->has('photo'))
                @if($errors->has('name'))
                    const firstErrorEl = document.getElementById('name');
                @elseif($errors->has('phone'))
                    const firstErrorEl = document.getElementById('phone');
                @elseif($errors->has('address'))
                    const firstErrorEl = document.getElementById('address');
                @elseif($errors->has('photo'))
                    const firstErrorEl = document.getElementById('image');
                @endif
                                                                                            if (firstErrorEl) {
                    firstErrorEl.focus();
                    // If it's a text-editable control, select the text to allow quick overwrite
                    const isSelectable = firstErrorEl instanceof HTMLInputElement || firstErrorEl instanceof HTMLTextAreaElement;
                    if (isSelectable && typeof firstErrorEl.select === 'function') {
                        // Delay select slightly to ensure focus applied first on all browsers
                        setTimeout(() => {
                            try { firstErrorEl.select(); } catch (e) { }
                        }, 0);
                    }
                    // scroll into view for better UX
                    firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            @elseif($errors->has('old_password') || $errors->has('new_password') || $errors->has('confirm_password'))
                const oldPasswordInput = document.getElementById('old_password');
                if (oldPasswordInput instanceof HTMLInputElement) {
                    oldPasswordInput.focus();
                    // Select any existing text in the password field for quick correction
                    if (typeof oldPasswordInput.select === 'function') {
                        setTimeout(() => {
                            try { oldPasswordInput.select(); } catch (e) { }
                        }, 0);
                    }
                    oldPasswordInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            @endif

            // PIN digit input handling
            function setupPinInputs(selector, hiddenId) {
                const inputs = document.querySelectorAll(selector);
                const hidden = document.getElementById(hiddenId);

                if (!inputs.length || !hidden) return;

                function updateHidden() {
                    let pin = '';
                    inputs.forEach(i => pin += i.value);
                    hidden.value = pin;
                }

                inputs.forEach((input, index) => {
                    input.addEventListener('input', function () {
                        if (!/^\d*$/.test(this.value)) {
                            this.value = '';
                            return;
                        }
                        updateHidden();
                        if (this.value.length === 1 && index < inputs.length - 1) {
                            inputs[index + 1].focus();
                        }
                    });

                    input.addEventListener('keydown', function (e) {
                        if (e.key === 'Backspace' && !this.value && index > 0) {
                            inputs[index - 1].focus();
                            inputs[index - 1].value = '';
                            updateHidden();
                        }
                    });

                    input.addEventListener('paste', function (e) {
                        e.preventDefault();
                        const pasted = e.clipboardData.getData('text').replace(/\D/g, '');
                        if (pasted) {
                            for (let i = 0; i < pasted.length && index + i < inputs.length; i++) {
                                inputs[index + i].value = pasted[i];
                            }
                            updateHidden();
                            const nextIdx = Math.min(index + pasted.length, inputs.length - 1);
                            inputs[nextIdx].focus();
                        }
                    });

                    input.addEventListener('focus', function () {
                        this.select();
                    });
                });
            }

            setupPinInputs('.pin-digit', 'pinHidden');
            setupPinInputs('.pin-confirm-digit', 'pinConfirmHidden');

            // Toggle PIN button
            const togglePinBtn = document.getElementById('togglePinBtn');
            if (togglePinBtn) {
                togglePinBtn.addEventListener('click', function () {
                    const isCurrentlyEnabled = this.classList.contains('btn-warning');
                    const newState = !isCurrentlyEnabled;

                    fetch('{{ route("profile.pin.toggle") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ enable: newState })
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                try { Notify('success', null, data.message); } catch (e) { }
                                setTimeout(() => window.location.reload(), 1000);
                            } else {
                                try { Notify('error', null, data.message); } catch (e) { }
                            }
                        })
                        .catch(err => {
                            try { Notify('error', null, 'Failed to toggle PIN status'); } catch (e) { }
                        });
                });
            }
        });
    </script>

@endsection