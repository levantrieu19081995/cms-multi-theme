const menuTypes = {
  url: 'Liên kết',
  post: 'Bài viết',
  image: 'Hình ảnh',
  page: 'Trang',
  video: 'Video',
  category: 'Chuyên mục',
  docs: 'Nhóm văn bản',
  get feature() {
    return window.featureText?.type_label ?? 'feature';
  }
};

// Cảnh báo khi menu trỏ tới chức năng đang tắt / không còn tồn tại (menu vẫn được giữ, chỉ ẩn ngoài website).
const featureWarnings = {
  get disabled() {
    return window.featureText?.warn_disabled ?? '';
  },
  get missing() {
    return window.featureText?.warn_missing ?? '';
  }
};

const typeLabelHtml = menuItem => {
  const label = menuTypes[menuItem.type];
  const warning = menuItem.type === 'feature' ? featureWarnings[menuItem.feature_status] : null;
  return warning ? `${label} <span class="badge bg-label-warning ms-1">${warning}</span>` : label;
};

const menuTargets = {
  _self: '',
  _blank: '<i class="ri-arrow-right-up-line ri-14px" title="Mở ở của sổ mới"></i>'
};

const attr = value => String(value ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');

window.renderMenuItem = menuItem => {
  var template = document.getElementById('menu-item-template').innerHTML;
  var renderHtml = template
    .replace('#id', menuItem.id)
    .replaceAll('#title', menuItem.title)
    .replace('#type', menuItem.type)
    .replace('#typeLabel', () => typeLabelHtml(menuItem))
    .replace('#url', menuItem.url)
    .replace('#target', menuItem.target)
    .replace('%icon%', () => attr(menuItem.icon))
    .replace('%fk%', () => attr(menuItem.feature_key))
    .replace('%ft%', () => attr(menuItem.feature_target))
    .replace('%fs%', () => attr(menuItem.feature_status))
    .replace('#menuIcon', attr(menuItem.icon || 'ri-magic-line'))
    .replace('#iconTarget', menuTargets[menuItem.target])
    .replace('#ref_id', menuItem.ref_id)
    .replace('%en_title%', () => attr(menuItem.title_en))
    .replace('%en_url%', () => attr(menuItem.url_en));
  return renderHtml;
};

window.renderMultiLevelMenuItem = menuItem => {
  if (!menuItem.childs || menuItem.childs.length == 0) {
    return renderMenuItem(menuItem);
  } else {
    var template = document.getElementById('multi-level-menu-item-template').innerHTML;
    var renderChilds = '';
    menuItem.childs.forEach(childMenuItem => {
      renderChilds += renderMultiLevelMenuItem(childMenuItem);
    });

    var renderHtml = template
      .replace('#id', menuItem.id)
      .replaceAll('#title', menuItem.title)
      .replace('#type', menuItem.type)
      .replace('#typeLabel', () => typeLabelHtml(menuItem))
      .replace('#url', menuItem.url)
      .replace('#target', menuItem.target)
      .replace('%icon%', () => attr(menuItem.icon))
    .replace('%fk%', () => attr(menuItem.feature_key))
    .replace('%ft%', () => attr(menuItem.feature_target))
    .replace('%fs%', () => attr(menuItem.feature_status))
      .replace('#menuIcon', attr(menuItem.icon || 'ri-magic-line'))
      .replace('#iconTarget', menuTargets[menuItem.target])
      .replace('#ref_id', menuItem.ref_id)
      .replace('%en_title%', () => attr(menuItem.title_en))
      .replace('%en_url%', () => attr(menuItem.url_en))
      .replace('#childs', renderChilds);
    return renderHtml;
  }
};

window.renderNestable = (elementId, nestList) => {
  var template = document.getElementById('menu-builder-template').innerHTML;
  var renderChilds = '';
  nestList.forEach(menuItem => {
    renderChilds += renderMultiLevelMenuItem(menuItem);
  });

  let workEl = document.getElementById(elementId);
  let renderHtml = template.replace('#childs', renderChilds);
  let parentEl = workEl.parentNode;
  let dupEl = workEl.cloneNode(true);
  workEl.remove();
  parentEl.appendChild(dupEl);
  dupEl.innerHTML = renderHtml;
};

window.serializeItem = element => {
  var el = $(element);

  return {
    id: el.data('id'),
    title: el.data('title'),
    type: el.data('type'),
    url: el.data('url'),
    target: el.data('target'),
    icon: el.data('icon') ?? '',
    ref_id: el.data('ref_id'),
    feature_key: el.data('feature_key') ?? '',
    feature_target: el.data('feature_target') ?? '',
    feature_status: el.data('feature_status') ?? '',
    title_en: el.data('title_en') ?? '',
    url_en: el.data('url_en') ?? ''
  };
};

window.serializeNestable = element => {
  var objects = [];
  $(element)
    .find('>> li')
    .each((i, li) => {
      if ($(li).has('ol').length) {
        // Nested
        let parentItem = serializeItem(li);
        parentItem.childs = serializeNestable(li);
        objects.push(parentItem);
      } else {
        // Menu item
        objects.push(serializeItem(li));
      }
    });
  return objects;
};

window.openModal = menuItem => {
  console.log(' window.openModal menuItem :>> ', menuItem);
  var elModel = null;
  if (menuItem.type == 'url') {
    elModel = $('#create_url_menu_modal');
    elModel.find('input, select, checkbox, textarea').each(function (i, ip) {
      for (var key in menuItem) {
        if ($(ip).attr('name') == key) {
          ip.value = menuItem[key];
        }
      }
    });
  } else if (
    menuItem.type == 'post' ||
    menuItem.type == 'image' ||
    menuItem.type == 'page' ||
    menuItem.type == 'video'
  ) {
    elModel = $(`#create_${menuItem.type}_menu_modal`);
    elModel.find('input, select, checkbox, textarea').each(function (i, ip) {
      for (var key in menuItem) {
        if ($(ip).attr('name') == key) {
          ip.value = menuItem[key];
        }
      }
    });

    window.actionSelectPost = res => {
      elModel.find('input[name=ref_id]').val(res.id);
      console.log('window. actionSelectPost res:>> ', res);
      if (
        !elModel.find('input[name=title]').val() ||
        elModel.find('input[name=title]').val() == elModel.find('input[name=url]').val()
      ) {
        elModel.find('input[name=title]').val(res.title);
      }
      elModel.find('input[name=url]').val(res.title);
    };
  } else if (menuItem.type == 'category') {
    elModel = $('#create_category_menu_modal');
    elModel.find('input, select, checkbox, textarea').each(function (i, ip) {
      for (var key in menuItem) {
        if ($(ip).attr('name') == key) {
          ip.value = menuItem[key];
        }
      }
    });

    elModel.find('select[name=ref_id]').on('change', function () {
      var categoryId = $(this).val();
      var categoryName = $(this)
        .find('option[value=' + categoryId + ']')
        .text()
        .trim();
      var previousTitle = elModel.find('input[name=title]').val();
      var previousUrl = elModel.find('input[name=url]').val();
      if (!previousTitle || previousTitle == previousUrl) {
        elModel.find('input[name=title]').val(categoryName);
      }
      elModel.find('input[name=url]').val(categoryName);
    });
  } else if (menuItem.type == 'docs') {
    elModel = $('#create_docs_menu_modal');
    elModel.find('input, select, checkbox, textarea').each(function (i, ip) {
      for (var key in menuItem) {
        if ($(ip).attr('name') == key) {
          ip.value = menuItem[key];
        }
      }
    });

    elModel.find('select[name=ref_id]').on('change', function () {
      var categoryId = $(this).val();
      var categoryName = $(this)
        .find('option[value=' + categoryId + ']')
        .text()
        .trim();

      var previousTitle = elModel.find('input[name=title]').val();
      var previousUrl = elModel.find('input[name=url]').val();
      if (!previousTitle || previousTitle == previousUrl) {
        elModel.find('input[name=title]').val(categoryName);
      }
      elModel.find('input[name=url]').val(categoryName);
    });
  }

  else if (menuItem.type == 'feature') {
    elModel = $('#create_feature_menu_modal');
    elModel.find('input, select, textarea').each(function (i, ip) {
      for (var key in menuItem) {
        if ($(ip).attr('name') == key && key != 'feature_target') {
          ip.value = menuItem[key];
        }
      }
    });
    const keySelect = elModel.find('select[name=feature_key]');
    // Mục cũ có thể trỏ tới chức năng không còn trong danh sách: giữ nguyên tham chiếu để người dùng thấy và sửa.
    // Chức năng đã tắt không có trong danh sách chọn; một mục cũ trỏ tới nó vẫn hiện lại (chỉ cho lần sửa này, gỡ khi mở lần sau).
    keySelect.find('option[data-edit-only]').remove();
    if (menuItem.feature_key && !keySelect.find(`option[value="${menuItem.feature_key}"]`).length) {
      const known = (window.featureChoices || []).find(c => c.key === menuItem.feature_key);
      const label = known ? `${known.name} ${window.featureText?.off_suffix ?? ''}` : `${menuItem.feature_key} ${window.featureText?.missing_option ?? ''}`;
      keySelect.append($('<option>').val(menuItem.feature_key).attr('data-edit-only', '1').text(label));
      keySelect.val(menuItem.feature_key);
    }
    window.populateFeatureTargets(elModel, menuItem.feature_target);
    keySelect.off('change.feature').on('change.feature', function () {
      window.populateFeatureTargets(elModel, null);
      const previous = elModel.find('input[name=title]').data('autoTitle');
      const current = elModel.find('input[name=title]').val();
      if (!current || current == previous) {
        const text = elModel.find('select[name=feature_target] option:selected').text();
        elModel.find('input[name=title]').val(text).data('autoTitle', text);
      }
    });
  }

  if (elModel) {
    window.syncMenuIconPickers?.(elModel[0]);
    elModel.find('h4.modal-title span.action-mode').text(menuItem.id > 0 ? 'Cập nhật' : 'Thêm');
    elModel.modal('show');
  }
};

// Điền danh sách điểm đến theo chức năng đang chọn (dữ liệu từ Feature Registry, không viết cứng).
window.populateFeatureTargets = (elModel, selected) => {
  const key = elModel.find('select[name=feature_key]').val();
  const choice = (window.featureChoices || []).find(c => c.key === key);
  const select = elModel.find('select[name=feature_target]').empty();
  (choice ? choice.targets : []).forEach(t => select.append($('<option>').val(t.key).text(t.name)));
  if (selected && !select.find(`option[value="${selected}"]`).length) {
    select.append($('<option>').val(selected).text(`${selected} ${window.featureText?.missing_option ?? ''}`));
  }
  if (selected) {
    select.val(selected);
  }
};

window.openEditModel = id => {
  let li = $('li.dd-item[data-id=' + id + ']');
  let menuItem = serializeItem(li);
  openModal(menuItem);
};

function closeModal(type) {
  $(`#create_${type}_menu_modal`).modal('hide');
}

$('.add-menu-item').each(function () {
  var el = $(this);
  var type = el.data('type');
  $(this).on('click', function () {
    openModal({
      id: 0,
      title: '',
      url: type == 'url' ? '/' : '',
      type: type,
      target: '_self',
      ref_id: 0,
      icon: '',
      feature_key: '',
      feature_target: '',
      feature_status: '',
      title_en: '',
      url_en: ''
    });
  });
});

$('.create_menu_item').each(function () {
  var thisEl = $(this);
  thisEl.on('click', function () {
    var menuItem = {};
    let formId = thisEl.data('form');
    $('#' + formId)
      .find('input, select, checkbox, textarea')
      .each(function (i, ip) {
        let el = $(ip);
        menuItem[el.attr('name')] = el.val();
      });

    if (menuItem.type == 'feature') {
      if (!menuItem.feature_key || !menuItem.feature_target) {
        toastr.error(window.featureText?.choose_required ?? '');
        return;
      }
      const choice = (window.featureChoices || []).find(c => c.key === menuItem.feature_key);
      const target = choice ? choice.targets.find(t => t.key === menuItem.feature_target) : null;
      // `url` chỉ là nhãn hiển thị trong trình quản lý; liên kết thật sinh từ route khi hiển thị.
      menuItem.url = choice && target ? `${choice.name} › ${target.name}` : `${menuItem.feature_key}:${menuItem.feature_target}`;
      menuItem.feature_status = choice && target ? (choice.enabled ? 'ok' : 'disabled') : 'missing';
    }
    if (menuItem.title.length == 0) {
      toastr.error('Văn bản hiển thị không được rỗng');
    } else if (menuItem.url.length == 0) {
      if (menuItem.type == 'url') {
        toastr.error('Đường dẫn liên kết không được rỗng');
      } else if (
        menuItem.type == 'post' ||
        menuItem.type == 'page' ||
        menuItem.type == 'image' ||
        menuItem.type == 'video' ||
        menuItem.type == 'docs' ||
        menuItem.type == 'post'
      ) {
        toastr.error(`Liên kết ${menuTypes[menuItem.type]} chưa được chọn`);
      }
    } else {
      addMenuItem(menuItem);

      closeModal(menuItem.type);
    }
  });
});

window._pageInit = () => {
  if (!window.menuData) {
    let jsonString = document.getElementById('structure').value;
    if (jsonString) {
      window.menuData = JSON.parse(jsonString);
    } else {
      window.menuData = [];
    }
  }

  // Init
  renderNestable('nestable', menuData);

  $('#nestable').nestable({
    maxDepth: 4,
    group: 1
    //   handleClass: 'drag-handle'
  });

  window.evClickEditMenuItem = function () {
    let menuItem = serializeItem($(this).closest('li'));
    openModal(menuItem);
  };

  const swalWithBootstrapButtons = Swal.mixin({
    customClass: {
      confirmButton: 'btn btn-danger',
      cancelButton: 'btn btn-default'
    },
    buttonsStyling: false
  });
  window.evClickDeleteMenuItem = function () {
    swalWithBootstrapButtons
      .fire({
        showClass: {
          popup: `
          animate__animated
          animate__fadeInUp
          animate__faster
        `
        },
        hideClass: {
          popup: `
          animate__animated
          animate__fadeOutDown
          animate__faster
        `
        },
        title: 'Bạn có chắc muốn xóa?',
        text: 'Dữ liệu đã xóa sẽ không thể phục hồi!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Đồng ý',
        cancelButtonText: 'Hủy',
        reverseButtons: false
      })
      .then(result => {
        if (result.isConfirmed) {
          $(this).closest('li').remove();
          window.menuData = serializeNestable($('#nestable'));

          _pageInit();

          setMenuItemActionEvent();

          document.getElementById('structure').value = JSON.stringify(menuData);
        }
      });
  };

  window.setMenuItemActionEvent = function () {
    $('li.dd-item i[action=edit]').on('click', evClickEditMenuItem);
    $('li.dd-item i[action=delete]').on('click', evClickDeleteMenuItem);
  };

  $('#nestable').on('change', function () {
    window.menuData = serializeNestable($(this));
    document.getElementById('structure').value = JSON.stringify(menuData);

    setMenuItemActionEvent();
  });
};

$(document).ready(() => {
  setMenuItemActionEvent();
});

window.addMenuItem = menuItem => {
  if (menuItem.id != 0) {
    var el = $('li.dd-item[data-id=' + menuItem.id + ']');
    for (var key in menuItem) {
      el.data(key, menuItem[key]);
    }
    el.find('.dd-handle .menu-item-title')[0].innerHTML = menuItem.title;
    window.menuData = serializeNestable($('#nestable'));
  } else {
    menuItem.id = -new Date().getTime();
    menuData.push(menuItem);
  }

  // Rerender
  _pageInit();

  setMenuItemActionEvent();

  document.getElementById('structure').value = JSON.stringify(menuData);
};

// Init page
(() => _pageInit())();
