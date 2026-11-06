/*
 * Chuyển trang ngang "nối đuôi" giữa Login và Dashboard: phần JS dùng chung cho cả hai chiều và cả hai trang. CSS: effects/auth/push-horizontal.css.
 * Kiểu chuyển được khai báo ở cấu hình theme (screens.themes.<theme>.login.transition = 'push-horizontal') và mang qua data-auth-transition
 * (khung Login, form logout) rồi vào payload sessionStorage hiện có ('cms-login-transition' / 'cms-logout-transition', trường `transition`).
 * File này không biết tên theme nào. Nạp bằng <script src> thường trong <head> (trước login-arrival.js / dashboard-arrival.js) vì pagereveal
 * chỉ bắn một lần trước lần render đầu; không có import/export. Hướng: +1 trang mới vào từ phải (đăng nhập), -1 từ trái (đăng xuất).
 */
(function () {
  var root = document.documentElement;
  var CLASSES = ['cms-push', 'cms-push-leave', 'cms-push-enter'];
  var duration = function () { return parseFloat(getComputedStyle(root).getPropertyValue('--cms-push-duration')) || 950; };
  var clear = function () {
    CLASSES.forEach(function (name) { root.classList.remove(name); });
    root.style.removeProperty('--cms-push-dir');
  };

  window.CmsAuthPush = {
    // View Transition (trang mới): bật class trong pagereveal; hai ảnh chụp trượt theo cùng một tiến độ; dọn khi kết thúc.
    arrive: function (event, direction) {
      if (!event.viewTransition) return;
      root.style.setProperty('--cms-push-dir', String(direction));
      root.classList.add('cms-push');
      event.viewTransition.finished.then(clear, clear);
    },
    // Fallback, trang đang rời: trượt cả trang theo hướng rồi gọi `then` (điều hướng).
    leave: function (direction, then) {
      root.style.setProperty('--cms-push-dir', String(direction));
      root.classList.add('cms-push-leave');
      window.addEventListener('pageshow', function (event) { if (event.persisted) clear(); }, { once: true });
      setTimeout(then, duration());
    },
    // Fallback, trang đích: trượt vào từ hướng tương ứng ngay khi tải, rồi dọn.
    enter: function (direction) {
      root.style.setProperty('--cms-push-dir', String(direction));
      root.classList.add('cms-push-enter');
      setTimeout(clear, duration() + 100);
    }
  };
})();
