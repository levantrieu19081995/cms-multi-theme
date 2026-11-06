/*
 * Trang Login nhận hiệu ứng Logout → Login (cửa khép lại đè lên trang vừa rời; kiểu dáng: effects/auth/style.css, html.cms-login-arriving).
 * Login nạp file này bằng <script src> THƯỜNG (không phải module, không defer) trong <head>: pagereveal chỉ bắn một lần trước lần render đầu,
 * nên listener phải đăng ký sớm hơn mọi module. Không có import/export; key 'cms-logout-transition' phải khớp với logout-login-transition.js.
 * Mọi lần tới khác giữ hành vi tức thì. Cờ debug: window.cmsAuthDebug (Blade khai báo).
 */
(function () {
  var KEY = 'cms-logout-transition', data = null, root = document.documentElement, debug = !!window.cmsAuthDebug;
  try { data = JSON.parse(sessionStorage.getItem(KEY) || 'null'); sessionStorage.removeItem(KEY); } catch (e) {}
  // payload.transition = 'push-horizontal' (do theme Login khai báo): nối đuôi, xử lý bởi push-horizontal-transition.js; mode 'fb' = trình duyệt không có View Transition.
  var push = !!(data && data.transition === 'push-horizontal' && window.CmsAuthPush);
  var fresh = data && (data.mode === 'vt' || (push && data.mode === 'fb')) && Date.now() - Number(data.t) < 15000;
  if (fresh && push && data.mode === 'fb') window.CmsAuthPush.enter(-1);
  window.addEventListener('pagereveal', function (event) {
    if (!event.viewTransition) return;
    if (!fresh) { event.viewTransition.skipTransition(); return; }
    if (push) { window.CmsAuthPush.arrive(event, -1); return; }
    // Centre of the gap = centre of the real login frame (it is capped at 1480px, so it is not the viewport centre).
    var vw = window.innerWidth, frame = document.querySelector('.cms-login-frame'), c = vw / 2;
    if (frame) { var rect = frame.getBoundingClientRect(); c = rect.left + rect.width / 2; }
    var vars = { '--cms-vt-vw': vw + 'px', '--cms-vt-c': c + 'px', '--cms-vt-t': (Math.max(c, vw - c) + 2) + 'px' };
    for (var k in vars) root.style.setProperty(k, vars[k]);
    root.classList.add('cms-login-arriving');
    var done = function () { root.classList.remove('cms-login-arriving'); for (var k in vars) root.style.removeProperty(k); };
    event.viewTransition.finished.then(done, done);
    if (debug) console.info('[cms-login-transition] logout arrival, branch = view-transition', { centerX: c, innerWidth: vw });
  });
})();
