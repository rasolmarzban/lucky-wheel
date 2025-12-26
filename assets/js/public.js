jQuery(document).ready(function ($) {
  var $wrapper = $("#rwl-wheel-wrapper");
  if ($wrapper.length === 0) {
    // Even if shortcode is not here, we might need floating bar
    checkFloatingBar();
    return;
  }

  var items = $wrapper.data("items");

  // Safety check: ensure items is an array
  if (items && typeof items === "object" && !Array.isArray(items)) {
    items = Object.values(items);
  }

  var sliceCount = items ? items.length : 0;
  var sliceAngle = sliceCount > 0 ? 360 / sliceCount : 0;

  // 0. Notification Helper
  function showNotification(message, type) {
    // type: 'success', 'error', 'info'
    var icon = "ℹ️";
    if (type === "success") icon = "✅";
    if (type === "error") icon = "❌";

    var toastId = "toast-" + Date.now();
    var html = `
            <div id="${toastId}" class="rwl-toast rwl-toast-${type}">
                <div class="rwl-toast-icon">${icon}</div>
                <div class="rwl-toast-message">${message}</div>
                <div class="rwl-toast-close">&times;</div>
            </div>
        `;

    // Create container if not exists (though it should exist from HTML)
    var $container = $("#rwl-toast-container");
    if ($container.length === 0) {
      $("body").append('<div id="rwl-toast-container" class="rwl-toast-container"></div>');
      $container = $("#rwl-toast-container");
    }

    $container.append(html);
    var $toast = $("#" + toastId);

    // Auto remove after 5 seconds
    setTimeout(function () {
      removeToast($toast);
    }, 5000);

    // Click to remove
    $toast.find(".rwl-toast-close").on("click", function () {
      removeToast($toast);
    });
  }

  function removeToast($toast) {
    $toast.addClass("rwl-fade-out");
    $toast.on("animationend", function () {
      $(this).remove();
    });
  }

  // 1. Render Wheel
  function renderWheel() {
    if (sliceCount === 0) {
      console.error("RSD Lucky Wheel: No items found.");
      // showNotification('هیچ آیتمی برای گردونه یافت نشد.', 'error'); // Optional to show user
      return;
    }

    var gradientParts = [];
    var currentAngle = 0;

    // We iterate items to build conic-gradient
    for (var i = 0; i < sliceCount; i++) {
      var color = items[i].color || "#ccc";
      gradientParts.push(color + " " + currentAngle + "deg " + (currentAngle + sliceAngle) + "deg");
      currentAngle += sliceAngle;

      // Add text label (optional, complex with pure CSS gradient, usually requires absolute positioned spans)
      // For this version, we stick to colors to keep it simple, or we can add spans.
      // Let's add spans for text.
      addSliceText(i, items[i].title);
    }

    $("#rwl-wheel-element").css("background", "conic-gradient(" + gradientParts.join(", ") + ")");
  }

  function addSliceText(index, text) {
    // Calculate position
    // This is tricky without SVG. Let's try to rotate text elements.
    // We place them at center, rotate them to the slice angle, then translate outwards.
    var angle = sliceAngle * index + sliceAngle / 2;
    var $span = $('<span class="rwl-slice-text">' + text + "</span>");

    $span.css({
      position: "absolute",
      top: "50%",
      left: "50%",
      "transform-origin": "0 0",
      transform: "rotate(" + angle + "deg) translateY(-120px) translateX(-50%)", // Adjust radius (120px)
      width: "100px",
      "text-align": "center",
      "font-size": "12px",
      color: "#fff",
      "text-shadow": "1px 1px 2px #000",
    });

    $("#rwl-wheel-element").append($span);
  }

  renderWheel();

  // Test Mode Logic
  if (rwl_obj.is_test_mode == "1") {
    $("#rwl-step-login").hide();
    $("#rwl-step-wheel").fadeIn();

    // Override spin button for test mode
    $("#rwl-spin-btn")
      .off("click")
      .on("click", function () {
        var $btn = $(this);
        $btn.prop("disabled", true).text("در حال چرخش...");

        $.post(
          rwl_obj.ajax_url,
          {
            action: "rwl_test_spin",
            nonce: rwl_obj.nonce,
          },
          function (response) {
            if (response.success) {
              spinWheel(response.data.result_index, response.data.item, response);
            } else {
              $btn.prop("disabled", false).text("چرخش");
              showNotification(response.data.message, "error");
            }
          }
        );
      });
  }

  // 2. OTP Logic
  $("#rwl-send-otp-btn").on("click", function () {
    var mobile = $("#rwl-mobile-input").val();
    if (!mobile || mobile.length < 10) {
      showNotification("لطفا شماره موبایل معتبر وارد کنید", "error");
      return;
    }

    var $btn = $(this);
    $btn.prop("disabled", true).text("در حال ارسال...");

    $.post(
      rwl_obj.ajax_url,
      {
        action: "rwl_send_otp",
        nonce: rwl_obj.nonce,
        mobile: mobile,
      },
      function (response) {
        $btn.prop("disabled", false).text("ارسال کد تایید");
        if (response.success) {
          showNotification(response.data.message, "success");
          $("#rwl-otp-section").slideDown();
          $("#rwl-send-otp-btn").hide();
          $("#rwl-mobile-input").prop("disabled", true);
        } else {
          showNotification(response.data.message, "error");
        }
      }
    );
  });

  // 3. Verify & Spin Logic
  $("#rwl-verify-btn").on("click", function () {
    var mobile = $("#rwl-mobile-input").val();
    var otp = $("#rwl-otp-input").val();

    if (!otp) {
      showNotification("لطفا کد تایید را وارد کنید", "error");
      return;
    }

    var $btn = $(this);
    $btn.prop("disabled", true).text("در حال بررسی...");

    $.post(
      rwl_obj.ajax_url,
      {
        action: "rwl_verify_spin",
        nonce: rwl_obj.nonce,
        mobile: mobile,
        otp: otp,
      },
      function (response) {
        if (response.success) {
          showNotification("کد تایید شد! در حال آماده‌سازی گردونه...", "success");
          // Switch to wheel view
          $("#rwl-step-login").hide();
          $("#rwl-step-wheel").fadeIn();

          // Start Spin
          spinWheel(response.data.result_index, response.data.item, response);
        } else {
          $btn.prop("disabled", false).text("تایید و شروع");
          showNotification(response.data.message, "error");
        }
      }
    );
  });

  function spinWheel(winningIndex, item, response) {
    // Calculate rotation
    // We want the winning slice to be at the TOP (Arrow).
    // Arrow is at 0deg (top).
    // Center of winning slice is at: (index * sliceAngle) + (sliceAngle / 2)
    // We need to rotate the WHEEL so that this angle moves to 0 (or 360).
    // Target Rotation = 360 - (CenterAngle)

    var centerAngle = winningIndex * sliceAngle + sliceAngle / 2;
    var targetRotation = 360 - centerAngle;

    // Add extra spins (e.g., 3 full spins for slower speed)
    var totalRotation = targetRotation + 360 * 3;

    // Apply CSS
    $("#rwl-wheel-element").css("transform", "rotate(" + totalRotation + "deg)");

    // Wait for animation
    setTimeout(function () {
      showResult(item, response.data.is_win);
    }, 13000); // 12s (CSS) + 1s delay
  }

  function showResult(item, is_win) {
    $("#rwl-won-item-title").text(item.title);

    if (is_win) {
      $("#rwl-popup-title").text("تبریک!");
      $("#rwl-popup-desc").text("شما برنده شدید:");
      $("#rwl-won-code").text(item.code);
      $("#rwl-code-container").show();

      // Save to LocalStorage for floating bar
      var data = {
        code: item.code,
        expiry: new Date().getTime() + 24 * 60 * 60 * 1000, // 24 hours
      };
      localStorage.setItem("rwl_won_data", JSON.stringify(data));
      checkFloatingBar();
    } else {
      $("#rwl-popup-title").text("متاسفیم!");
      $("#rwl-popup-desc").text("شانس خود را دوباره امتحان کنید:");
      $("#rwl-code-container").hide();
    }

    $("#rwl-result-popup").fadeIn();
  }

  // Popup interactions
  $(".rwl-close-popup").on("click", function () {
    $("#rwl-result-popup").fadeOut();
  });

  $("#rwl-copy-btn, #rwl-bar-copy").on("click", function () {
    var code = $(this).siblings("span, #rwl-bar-code").text();
    if (!code) {
      // Handle dynamic button in floating bar where sibling selection might fail
      code = $("#rwl-bar-code").text();
    }

    // Fallback for copy
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val(code).select();
    document.execCommand("copy");
    $temp.remove();
    showNotification("کد کپی شد!", "success");
  });

  // Floating Bar Logic
  function checkFloatingBar() {
    var data = localStorage.getItem("rwl_won_data");
    if (!data) return;

    data = JSON.parse(data);
    var now = new Date().getTime();

    if (now < data.expiry) {
      // Show bar
      var $bar = $("#rwl-floating-bar");

      if ($("#rwl-floating-bar").length === 0) {
        $("body").append(`
                    <div id="rwl-floating-bar" class="rwl-floating-bar">
                        <div class="rwl-bar-content">
                            <span>کد تخفیف شما: <strong id="rwl-bar-code"></strong></span>
                            <span id="rwl-bar-timer"></span>
                            <button id="rwl-bar-copy" class="rwl-btn-small">کپی</button>
                        </div>
                        <span class="rwl-close-bar">&times;</span>
                    </div>
                `);

        // Rebind events for dynamically added element
        $(".rwl-close-bar").on("click", function () {
          $("#rwl-floating-bar").fadeOut();
        });

        $("#rwl-bar-copy").on("click", function () {
          var code = $("#rwl-bar-code").text();
          var $temp = $("<input>");
          $("body").append($temp);
          $temp.val(code).select();
          document.execCommand("copy");
          $temp.remove();
          showNotification("کد کپی شد!", "success");
        });
      }

      $("#rwl-bar-code").text(data.code);
      $("#rwl-floating-bar").fadeIn();

      // Timer
      setInterval(function () {
        var remaining = data.expiry - new Date().getTime();
        if (remaining <= 0) {
          $("#rwl-floating-bar").fadeOut();
          localStorage.removeItem("rwl_won_data");
        } else {
          var hours = Math.floor((remaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
          var minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
          $("#rwl-bar-timer").text(hours + " ساعت و " + minutes + " دقیقه مانده");
        }
      }, 60000);
    } else {
      localStorage.removeItem("rwl_won_data");
    }
  }

  // Run check on load
  checkFloatingBar();

  $(".rwl-close-bar").on("click", function () {
    $("#rwl-floating-bar").fadeOut();
  });
});
