/*
 * Logout → Login: đảo ngược hiệu ứng đăng nhập. Phiên được đóng bằng fetch (cùng POST + CSRF của form), sau đó trình duyệt chuyển tới trang Login,
 * nơi login-arrival.js dựng cửa khép lại đè lên trang vừa rời. Layout admin nạp file này bằng <script src> thường (không import/export), cùng chỗ với
 * dashboard-arrival.js. Form logout khai báo data-cms-logout và data-login-url; thiếu hỗ trợ View Transition / bật giảm chuyển động / không có fetch thì gửi form như cũ.
 * Key 'cms-logout-transition' phải khớp với login-arrival.js.
 */
(function () {
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form || !form.hasAttribute || !form.hasAttribute('data-cms-logout') || !form.dataset.loginUrl) return;
    var supported = typeof window.CSSViewTransitionRule !== 'undefined' && 'onpagereveal' in window;
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    // data-auth-transition (cấu hình theme Login) = 'push-horizontal': nối đuôi, có fallback trượt trang khi thiếu View Transition.
    var push = form.dataset.authTransition === 'push-horizontal' && !!window.CmsAuthPush;
    if ((!supported && !push) || reduced || !window.fetch) return; // plain form post, as before
    event.preventDefault();
    fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', redirect: 'manual', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (response) {
        if (response.type !== 'opaqueredirect' && !response.ok) { form.submit(); return; }
        try { sessionStorage.setItem('cms-logout-transition', JSON.stringify({ mode: supported ? 'vt' : 'fb', t: Date.now(), transition: push ? 'push-horizontal' : undefined })); } catch (e) {}
        if (push && !supported) window.CmsAuthPush.leave(-1, function () { window.location.assign(form.dataset.loginUrl); });
        else window.location.assign(form.dataset.loginUrl);
      })
      .catch(function () { form.submit(); });
  });
})();
