document.addEventListener("DOMContentLoaded", function () {
  if (typeof flatpickr !== "undefined") {
    flatpickr("#pickup_date", {
      disable: ["2026-01-16"],
      dateFormat: "d/m/Y",
      minDate: new Date().fp_incr(1),
      disableMobile: true,
    });
  }
});
