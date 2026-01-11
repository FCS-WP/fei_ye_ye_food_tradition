
document.addEventListener("DOMContentLoaded", function () {
    if (typeof flatpickr !== "undefined") {
        flatpickr("#pickup_date", {
            dateFormat: "d/m/Y",
            minDate: new Date().fp_incr(1),
            disableMobile: true
        });
    }
});
