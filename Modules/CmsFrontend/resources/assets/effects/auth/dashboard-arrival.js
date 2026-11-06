/*
 * Phía Dashboard nhận hiệu ứng Login → Dashboard (cửa mở). Layout admin nạp file này bằng <script src> THƯỜNG (không phải module, không defer),
 * đặt trong <head>: pagereveal chỉ bắn một lần trước lần render đầu, module/defer sẽ tới muộn và để hiệu ứng chạy sai hoặc không bị bỏ qua.
 * Vì vậy file không có import/export; hằng số dưới đây phải khớp với login-dashboard-transition.js.
 * CSS tương ứng: effects/auth/dashboard-arrival.css (cửa tách đôi) và push-horizontal.css (nối đuôi). Cờ debug: window.cmsAuthDebug (Blade khai báo).
 */
(function () {
  var KEY = 'cms-login-transition', root = document.documentElement, data = null, debug = !!window.cmsAuthDebug;
  try { data = JSON.parse(sessionStorage.getItem(KEY) || 'null'); sessionStorage.removeItem(KEY); } catch (e) {}
  var fresh = data && Date.now() - Number(data.t) < 15000;
  // Kiểu chuyển do theme Login khai báo (payload.transition); 'push-horizontal' = nối đuôi, xử lý bởi push-horizontal-transition.js.
  var push = fresh && data.transition === 'push-horizontal' && window.CmsAuthPush;
  if (debug && fresh) console.info('[cms-login-transition] branch =', data.mode === 'vt' ? 'view-transition' : 'fallback (fade only: no slit reveal)', data);

  if (push) {
    if (data.mode === 'vt') window.addEventListener('pagereveal', function (event) { window.CmsAuthPush.arrive(event, 1); });
    else window.CmsAuthPush.enter(1);
  } else if (fresh && data.mode === 'vt') {
    // Geometry measured on the login page: centre of the gap between the doors, and the viewport it was measured in.
    var vw = window.innerWidth, c = Number(data.centerX), t = Math.max(c, vw - c) + 2;
    var vars = { '--cms-vt-vw': vw + 'px', '--cms-vt-c': c + 'px', '--cms-vt-t': t + 'px' };
    var clear = function () { for (var k in vars) root.style.removeProperty(k); };
    for (var k in vars) root.style.setProperty(k, vars[k]);
    setTimeout(clear, 2500);
    window.addEventListener('pagereveal', function (event) {
      if (event.viewTransition) event.viewTransition.finished.then(clear, clear);
      else if (debug) console.info('[cms-login-transition] pagereveal without viewTransition (no native transition ran)');
    });
  } else {
    // Every other navigation inside the admin must stay instant.
    window.addEventListener('pagereveal', function (event) { if (event.viewTransition) event.viewTransition.skipTransition(); });
  }

  if (fresh && data.mode === 'fb' && !push) {
    root.classList.add('cms-login-fade');
    setTimeout(function () { root.classList.remove('cms-login-fade'); }, 900);
  }
})();
