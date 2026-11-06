/**
 * Form Picker
 */

'use strict';

import 'flatpickr/dist/l10n/vn.js';

(function () {
  // Flat Picker
  // --------------------------------------------------------------------
  const flatpickrDateTimeFrom = document.querySelector('#flatpickr-datetime-from'),
    flatpickrDateTimeTo = document.querySelector('#flatpickr-datetime-to');

  let fromPicker, toPicker;

  // Datetime
  if (flatpickrDateTimeFrom) {
    fromPicker = flatpickrDateTimeFrom.flatpickr({
      enableTime: true,
      dateFormat: 'd-m-Y H:i', // Custom format
      time_24hr: true, // 24-hour format
      minDate: 'today', // Only allow today and future
      locale: 'vn', // Vietnamese (if loaded)
      altInput: true, // Show a user-friendly input
      altFormat: 'l, d/m/Y H:i', // Friendly format
      allowInput: true, // Allow manual typing
      onChange: function (selectedDates, dateStr, instance) {
        if (toPicker) {
          // console.log('[]', selectedDates[0]);
          toPicker.set('minDate', selectedDates[0] || 'today');
        }
      }
    });
  }

  if (flatpickrDateTimeTo) {
    toPicker = flatpickrDateTimeTo.flatpickr({
      enableTime: true,
      dateFormat: 'd-m-Y H:i',
      time_24hr: true,
      minDate: flatpickrDateTimeFrom ? flatpickrDateTimeFrom.value || 'today' : 'today',
      locale: 'vn',
      altInput: true,
      altFormat: 'l, d/m/Y H:i',
      allowInput: true
    });

    // function validateDateRange() {
    //   const fromVal = flatpickrDateTimeFrom.value;
    //   const toVal = flatpickrDateTimeTo.value;
    //   const fromDate = fromVal ? fromPicker.parseDate(fromVal, 'd-m-Y H:i') : null;
    //   const toDate = toVal ? toPicker.parseDate(toVal, 'd-m-Y H:i') : null;

    //   // Trường hợp người dùng nhập sai, flatpickr đã xóa giá trị
    //   if (toVal === '' && toPicker.selectedDates.length === 0) {
    //     toastr.error('Thời gian Đến ngày không thể nhỏ hơn hoặc bằng thời gian Từ ngày', 'Lỗi');
    //     return;
    //   }

    //   if ((toVal && !toDate) || (fromDate && toDate && toDate < fromDate)) {
    //     toastr.error('Thời gian đến ngày không hợp lệ');
    //     flatpickrDateTimeTo.value = '';
    //     if (flatpickrDateTimeTo._flatpickr && flatpickrDateTimeTo._flatpickr.altInput) {
    //       flatpickrDateTimeTo._flatpickr.altInput.value = '';
    //     }
    //   }
    // }

    function validateDateRange() {
      const fromDate = fromPicker?.selectedDates[0];
      const toDate = toPicker?.selectedDates[0];

      // Không kiểm tra nếu chưa chọn đủ 2 mốc thời gian
      if (!fromDate || !toDate) return;

      if (toDate < fromDate) {
        toastr.error(window.cmsAdminTranslations.date_range_invalid, window.cmsAdminTranslations.error);

        // Xóa dữ liệu ở input và altInput
        toPicker.clear();
      }
    }

    flatpickrDateTimeTo.addEventListener('blur', validateDateRange);
    flatpickrDateTimeTo.addEventListener('change', validateDateRange);

    // Nếu dùng altInput, cũng cần bắt sự kiện trên altInput
    if (flatpickrDateTimeTo._flatpickr && flatpickrDateTimeTo._flatpickr.altInput) {
      flatpickrDateTimeTo._flatpickr.altInput.addEventListener('blur', validateDateRange);
      flatpickrDateTimeTo._flatpickr.altInput.addEventListener('change', validateDateRange);
    }
  }
})();
