"use strict";

function Notify(type, res, msg = null) {
  var type;
  var message;

  // Ensure consistent toastr placement across the app
  try {
    if (window.toastr) {
      // Remove existing container so new positionClass will recreate it in correct place
      try {
        var _tc = document.getElementById("toast-container");
        if (_tc && _tc.parentNode) _tc.parentNode.removeChild(_tc);
      } catch (_) { }
      window.toastr.options = Object.assign({}, window.toastr.options || {}, {
        positionClass: "toast-bottom-right",
      });
    }
  } catch (e) { }

  switch (type) {
    case "error":
      message =
        msg ??
        res.responseJSON.message ??
        res.responseText ??
        "Oops! Something went wrong";
      toastr.error(message);
      break;
    case "success":
      message = msg ?? res.message ?? "Congratulate! Operation Successful.";
      toastr.success(message);
      break;
    case "warning":
      message =
        msg ??
        res.message ??
        res.responseJSON.message ??
        "Warning! Operation Failed.";
      toastr.warning(message);
      break;
    default:
  }
}
