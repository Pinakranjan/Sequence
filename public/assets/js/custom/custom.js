"use strict";

// Manage Pages Dynamic Shop Start
$(document).ready(function () {
    $(document).on("click", ".shop-btn-add", function () {
        let wrapper = $("#shop-wrapper");
        let count = wrapper.find(".s-count").length + 1;

        let newField = `
            <div class="mb-3 s-count">
                <label class="mb-1">Shop- ${count}</label>
                <div class="row g-2 align-items-end">
                    <div class="col">
                        <input type="text" name="silder_shop_text[]" required
                            class="form-control"
                            placeholder="Enter text">
                    </div>
                    <div class="col-auto d-flex justify-content-end">
                        <button type="button" class="btn dynamic-delete-btn shop-btn-remove">
                            <svg class="mt-1 text-danger" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;
        wrapper.append(newField);
        updateLabels();
    });

    $(document).on("click", ".shop-btn-remove", function () {
        $(this).closest(".s-count").remove();
        updateLabels();
    });

    function updateLabels() {
        $("#shop-wrapper .s-count").each(function (index) {
            $(this)
                .find("label")
                .text("Shop- " + (index + 1));
        });
    }
});
// Manage Pages Dynamic Shop End

/** Party Start */
function addMoreFeature() {
    let length = parseInt($(".duplicate-feature").length) + 1; // Increment length by 1
    if (length > 3) {
        toastr.error("You can not add more than 3 Reference!");
        return;
    }
    var newFeature = $(".duplicate-feature:last")
        .clone()
        .insertAfter("div.duplicate-feature:last");

    $(".reference:last").text("Reference - " + length);
    $(".duplicate-feature:last .clear-input").val("");

    $(".duplicate-feature:last .clear-img").val(null); // Clear the file input
    $(".duplicate-feature:last .table-img").attr("src", ""); // Clear the image source
}
function removeFeature(button) {
    $(button).closest(".duplicate-feature").remove();
}
/** Party End */

var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
);
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

//Business view modal
$(document).on("click", ".business-view", function () {
    $(".business_name").text($(this).data("name"));
    $("#image").attr("src", $(this).data("image"));
    $("#name").text($(this).data("name"));
    $("#address").text($(this).data("address"));
    $("#category").text($(this).data("category"));
    $("#phone").text($(this).data("phone"));
    $("#package").text($(this).data("package"));
    $("#last_enroll").text($(this).data("last_enroll"));
    $("#expired_date").text($(this).data("expired_date"));
    $("#created_date").text($(this).data("created_date"));
});

$(document).on("change", ".file-input-change", function () {
    let prevId = $(this).data("id");
    newPreviewImage(this, prevId);
});

// image preview
function newPreviewImage(input, prevId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            $("#" + prevId).attr("src", e.target.result);
        };
        reader.readAsDataURL(input.files[0]);
    }
}

//Upgrade plan
$(document).on("click", ".business-upgrade-plan", function () {
    var url = $(this).data("url");

    $("#business_name").val($(this).data("name"));
    $("#business_id").val($(this).data("id"));
    $(".upgradePlan").attr("action", url);
});

$("#plan_id").on("change", function () {
    $(".plan-price").val($(this).find(":selected").data("price"));
});

$(".modal-reject").on("click", function () {
    var url = $(this).data("url");
    $(".modalRejectForm").attr("action", url);
});

$(".modal-approve").on("click", function () {
    var url = $(this).data("url");
    $(".modalApproveForm").attr("action", url);
});

$(".manual-payment-modal").on("click", function () {
    var url = $(this).data("url");
    $(".manualPaymentForm").attr("action", url);
});

$(".manual-payment-reject-modal").on("click", function () {
    var url = $(this).data("url");
    $(".manualPaymentRejectForm").attr("action", url);
});

// Print Window
$(document).ready(function () {
    $(".print-window").on("click", function () {
        window.print();
    });
});

// Password Show Hide Start
$(document).ready(function () {
    $(".eye-btn").on("click", function () {
        var passwordField = $(this).siblings("input"); // Selects the input field within the same parent
        var icon = $(this);

        if (passwordField.attr("type") === "password") {
            passwordField.attr("type", "text");
            icon.removeClass("fa-eye").addClass("fa-eye-slash");
        } else {
            passwordField.attr("type", "password");
            icon.removeClass("fa-eye-slash").addClass("fa-eye");
        }
    });
});
// Password Show Hide End

// Multidelete Start
function updateSelectedCount() {
    var selectedCount = $(".delete-checkbox-item:checked").length;
    $(".selected-count").text(selectedCount);

    if (selectedCount > 0) {
        $(".delete-show").removeClass("d-none");
    } else {
        $(".delete-show").addClass("d-none");
    }
}

$(".select-all-delete").on("click", function () {
    $(".delete-checkbox-item").prop("checked", this.checked);
    updateSelectedCount();
});

$(document).on("change", ".delete-checkbox-item", function () {
    updateSelectedCount();
});

$(".trigger-modal").on("click", function () {
    var dynamicUrl = $(this).data("url");

    $("#dynamic-delete-form").attr("action", dynamicUrl);

    var ids = $(".delete-checkbox-item:checked")
        .map(function () {
            return $(this).val();
        })
        .get();

    if (ids.length === 0) {
        alert("Please select at least one item.");
        return;
    }

    var form = $("#dynamic-delete-form");
    form.find("input[name='ids[]']").remove();
    ids.forEach(function (id) {
        form.append('<input type="hidden" name="ids[]" value="' + id + '">');
    });
});

$(".create-all-delete").on("click", function (event) {
    event.preventDefault();

    var form = $("#dynamic-delete-form");
    form.submit();
});
// Multidelete End

//Subscriber view modal
$(document).on("click", ".subscriber-view", function () {
    $(".business_name").text($(this).data("name"));
    $("#image").attr("src", $(this).data("image"));
    $("#category").text($(this).data("category"));
    $("#package").text($(this).data("package"));
    $("#gateway").text($(this).data("gateway"));
    $("#enroll_date").text($(this).data("enroll"));
    $("#expired_date").text($(this).data("expired"));
    $("#manul_attachment").attr("src", $(this).data("manul-attachment"));
});

// Message View
$(document).on("click", ".message-view", function () {
    $("#name").text($(this).data("name"));
    $("#email").text($(this).data("email"));
    $("#phone").text($(this).data("phone"));
    $("#business_name").text($(this).data("business-name"));
    $("#message").text($(this).data("message"));
});

// Staff view Start
$(document).on("click", ".staff-view-btn", function () {
    var staffName = $(this).data("staff-view-name");
    var staffPhone = $(this).data("staff-view-phone-number");
    var staffemail = $(this).data("staff-view-email-number");
    var staffRole = $(this).data("staff-view-role");
    var staffImage = $(this).data("staff-view-image");
    var staffStatus = $(this).data("staff-view-status");

    // Set the text for the staff view fields
    $("#staff_view_name").text(staffName);
    $("#staff_view_phone_number").text(staffPhone);
    $("#staff_view_email_number").text(staffemail);
    $("#staff_view_role").text(staffRole);
    $("#staffImage").attr("src", staffImage);
    $("#staff_view_status").text(staffStatus);

    var statusElement = $("#staff_view_status");
    statusElement.removeClass("active-status premium-bg");
    if (staffStatus === "active") {
        statusElement.addClass("active-status");
    } else if (staffStatus === "pending") {
        statusElement.addClass("premium-bg");
    }
});
// Staff view End

// Custom Date Filter Start
$(document).on("change", ".custom-filter-select", function () {
    let value = $(this).val();

    if (value === "custom_date") {
        $(".date-filters").removeClass("d-none");
    } else {
        $(".date-filters").addClass("d-none");
    }
});
// Custom Date Filter End

//Dynamic Tags Setting Start
$(document)
    .off("click", ".add-new-tag")
    .on("click", ".add-new-tag", function () {
        let html = `
    <div class="col-md-6">
        <div class="row row-items">
            <div class="col-sm-10">
                <label for="">Tags</label>
                <input type="text" name="tags[]" class="form-control" required
                    placeholder="Enter tags name">
            </div>
            <div class="col-sm-2 align-self-center mt-3">
                <button type="button" class="mt-lg-3 btn text-danger trash remove-btn-features"
                    onclick="removeDynamicField(this)"><i
                        class="fas fa-trash"></i></button>
            </div>
        </div>
    </div>
    `;
        $(".manual-rows .single-tags").append(html);
    });

$(document).on("click", ".add-new-item", function () {
    let html = `
    <div class="row row-items">
        <div class="col-sm-5">
            <label for="">Label</label>
            <input type="text" name="manual_data[label][]" value="" class="form-control" placeholder="Enter label name">
        </div>
        <div class="col-sm-5">
            <label for="">Select Required/Optionl</label>
            <select class="form-control" required name="manual_data[is_required][]">
                <option value="1">Required</option>
                <option value="0">Optional</option>
            </select>
        </div>
        <div class="col-sm-2 align-self-center mt-3">
            <button type="button" class="mt-lg-3 btn text-danger trash remove-btn-features"><i class="fas fa-trash"></i></button>
        </div>
    </div>
    `;
    $(".manual-rows").append(html);
});

$(document).on("click", ".remove-btn-features", function () {
    var $row = $(this).closest(".row-items");
    $row.remove();
});

//Dynamic tag ends

// Img preview----------->
function initImagePreview(inputSelector, imageSelector) {
    document.addEventListener("DOMContentLoaded", function () {
        const fileInputs = document.querySelectorAll(inputSelector);

        fileInputs.forEach((input) => {
            const previewImage = document.querySelector(
                input.getAttribute("data-preview")
            );

            input.addEventListener("change", function () {
                if (this.files && this.files[0] && previewImage) {
                    previewImage.src = URL.createObjectURL(this.files[0]);
                }
            });
        });
    });
}

initImagePreview("[data-preview]", "[data-preview]");

document.addEventListener("DOMContentLoaded", function () {
    const element = document.getElementById("modifier_group_id");
    if (element && element.tagName === "SELECT") {
        new Choices(element, {
            removeItemButton: true,
            placeholderValue: "Select modifier groups",
            searchPlaceholderValue: "Search...",
            shouldSort: false,
        });
    }
});

$(document).ready(function () {
    const $activeItem = $("ul li.active");
    if ($activeItem.length) {
        $activeItem[0].scrollIntoView({
            behavior: "smooth",
            block: "center",
        });
    }
});

$(document).on("click", ".preview-img", function () {
    let imgSrc = $(this).attr("src");
    $("#subscriber-view-modal").modal("hide");
    $("#modalImage").attr("src", imgSrc);
    $("#imagePreviewModal").modal("show");
});

$("#imagePreviewModal").on("hidden.bs.modal", function () {
    $("#subscriber-view-modal").modal("show");
});

$(document).ready(function () {
    const choicesMap = new Map();

    $(".choices-select").each(function () {
        const select = this;
        const choicesInstance = new Choices(select, {
            searchEnabled: true,
            itemSelectText: "",
            shouldSort: false,
            noResultsText: "No data found. Press enter to add new.",
        });
        choicesMap.set(select.id, choicesInstance);
    });

    $(document).on("keydown", ".choices__input--cloned", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();

            const activeInput = $(this);
            const searchTerm = activeInput.val().trim();
            if (!searchTerm) return;

            const choicesContainer = activeInput.closest(".choices");

            // Always get the right select in the same input group
            const selectElement = choicesContainer.parent().find("select.choices-select");
            if (!selectElement.length) return;

            // check if any option matches
            let matchFound = false;
            selectElement.find("option").each(function () {
                if ($(this).text().toLowerCase().includes(searchTerm.toLowerCase())) {
                    matchFound = true;
                    return false;
                }
            });

            if (!matchFound) {
                // get modal from select’s data-modal
                const modalId = selectElement.data("modal");
                if (!modalId) return;

                const modalNameInput = $(modalId).find('input[name="name"]');
                const modalPhoneInput = $(modalId).find('input[name="phone"]');

                // Clear select
                selectElement.val("").trigger("change");

                // Phone or name detection
                const phoneRegex = /^(\+?[0-9]{1,15}|[0-9]{3,})$/;
                const isPhoneNumber = phoneRegex.test(searchTerm);

                if (isPhoneNumber && modalPhoneInput.length) {
                    modalPhoneInput.val(searchTerm);
                } else if (modalNameInput.length) {
                    modalNameInput.val(searchTerm);
                }

                // Open modal
                new bootstrap.Modal($(modalId)[0]).show();
                activeInput.val("");
            }
        }
    });
});



