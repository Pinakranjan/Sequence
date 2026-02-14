document.addEventListener("DOMContentLoaded", function () {
    function handleResize2() {
        const salesContent = document.getElementById("salesContent2");
        const salesRight = document.getElementById("salesRight2");
        const salesLeft = document.getElementById("salesLeft2");

        const searchContainer = document.querySelector(".search-container");
        const categoryBtnContainer = document.querySelector(
            ".category-btn-container"
        );

        const isSmallScreen = window.matchMedia("(max-width: 550px)").matches;

        if (isSmallScreen) {
            salesContent?.classList.remove("sales-content");
            salesContent?.classList.add("row");

            salesRight?.classList.remove("sales-right-content");
            salesRight?.classList.add("col-lg-7");

            salesLeft?.classList.remove("left-content-sales");
            salesLeft?.classList.add("col-lg-5");

            searchContainer?.classList.add("custom-padding");
            categoryBtnContainer?.classList.add("custom-padding");
        } else {
            salesContent?.classList.add("sales-content");
            salesContent?.classList.remove("row");

            salesRight?.classList.add("sales-right-content");
            salesRight?.classList.remove("col-lg-7");

            salesLeft?.classList.add("left-content-sales");
            salesLeft?.classList.remove("col-lg-5");

            searchContainer?.classList.remove("custom-padding");
            categoryBtnContainer?.classList.remove("custom-padding");
        }
    }

    handleResize2();

    window.addEventListener("resize", handleResize2);
});
