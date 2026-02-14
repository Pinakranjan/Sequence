"use strict";
(function () {
    try {
        // Only initialize Quill if the container exists on the current page
        var el = document.getElementById("quill-editor");
        if (window.Quill && el && !el.__quill) {
            // Store the instance on the element to avoid double-inits when scripts reload
            el.__quill = new Quill(el, { theme: "snow" });
        }
    } catch (e) {
        // Silently ignore on pages without Quill or if assets not loaded here
    }
})();
