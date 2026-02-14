document.addEventListener("DOMContentLoaded", function () {
    const toggle = document.getElementById("customNotificationToggle");
    const dropdown = document.getElementById("customNotificationDropdown");

    toggle.addEventListener("click", function (e) {
        e.preventDefault();
        dropdown.classList.toggle("show");
    });

    document.addEventListener("click", function (e) {
        if (!toggle.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove("show");
        }
    });
});
