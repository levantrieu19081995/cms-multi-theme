'use strict';

$(function () {
  const $tree = $('#permission-groups');
  const $form = $('#role-form');

  if (!$tree.length || !$form.length) return;

  const $search = $('#permission-search');
  const $groupFilter = $('#permission-group-filter');
  const $summary = $('#permission-selection-summary');
  const $status = $('#permission-tree-status');
  const $name = $('#name');
  const $nameError = $('#name-error');
  const $permissionError = $('#permission-error');
  const rawRoleId = $tree.attr('data-user-id');
  const roleId = rawRoleId === undefined || rawRoleId === null || rawRoleId === '' ? -1 : rawRoleId;
  const urls = window.cmsUrls || {};
  let selectedPermissionIds = [];
  let permissionGroups = [];
  let searchTimer = null;

  function setStatus(message, type = 'muted') {
    $status.attr('data-state', type).text(message).toggle(Boolean(message));
  }

  function normalizeSelected(data) {
    return Array.isArray(data) ? data.map(item => String(item.id)) : [];
  }

  async function requestJson(url) {
    const response = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return response.json();
  }

  async function loadSelectedPermissions() {
    if (String(roleId) === '-1') return;

    const baseUrl = urls.roleByRole || '/cms/api/vai-tro/getDsVaiTroByUserId';
    const payload = await requestJson(`${baseUrl}/${roleId}`);
    if (payload.code === 200) selectedPermissionIds = normalizeSelected(payload.data);
  }

  function buildTree(items, parentId = null) {
    return items
      .filter(item => {
        if (parentId === null) return item.parent_id === null || Number(item.parent_id) === -1;
        return String(item.parent_id) === String(parentId);
      })
      .map(item => {
        const children = buildTree(items, item.id);
        return {
          id: item.id_encrypt,
          text: item.name,
          originalPermissionId: item.id,
          state: {
            opened: false,
            selected: selectedPermissionIds.includes(String(item.id))
          },
          children
        };
      });
  }

  function treeInstance() {
    return $tree.jstree(true);
  }

  function updateSummary() {
    const tree = treeInstance();
    if (!tree) return;

    const total = tree.get_json('#', { flat: true }).length;
    const selected = tree.get_checked().length;
    $summary.text(`Đã chọn ${selected}/${total} quyền`);
    $permissionError.text('');
  }

  function populateGroupFilter() {
    const tree = treeInstance();
    if (!tree) return;

    const root = tree.get_node('#');
    root.children.forEach(nodeId => {
      const node = tree.get_node(nodeId);
      $('<option>', { value: nodeId, text: node.text }).appendTo($groupFilter);
    });
  }

  function applyGroupFilter() {
    const tree = treeInstance();
    if (!tree) return;

    const selectedGroup = $groupFilter.val();
    tree.get_node('#').children.forEach(nodeId => {
      const nodeElement = tree.get_node(nodeId, true);
      nodeElement.toggleClass('cms-permission-group-hidden', Boolean(selectedGroup) && nodeId !== selectedGroup);
    });

    if (selectedGroup) tree.open_node(selectedGroup);
  }

  function applySearch() {
    const tree = treeInstance();
    if (!tree) return;

    const query = $search.val().trim();
    tree.clear_search();
    if (query) tree.search(query, true, true);
    applyGroupFilter();

    window.requestAnimationFrame(() => {
      const visibleMatches = $tree.find('.jstree-search:visible').length;
      if (query && visibleMatches === 0) {
        setStatus(`Không tìm thấy quyền phù hợp với “${query}”.`, 'empty');
      } else {
        setStatus('');
      }
    });
  }

  function initializeTree(treeData) {
    const theme = $('html').hasClass('light-style') ? 'default' : 'default-dark';

    $tree.jstree({
      core: {
        themes: { name: theme, dots: true, icons: false, responsive: true },
        data: treeData
      },
      checkbox: {
        keep_selected_style: false,
        three_state: true,
        whole_node: false
      },
      search: {
        case_sensitive: false,
        show_only_matches: true,
        show_only_matches_children: true
      },
      plugins: ['types', 'checkbox', 'search'],
      types: { default: { icon: '' } }
    });

    $tree.on('ready.jstree', function () {
      populateGroupFilter();
      updateSummary();
      setStatus('');
    });

    $tree.on('changed.jstree', function (_event, data) {
      selectedPermissionIds = data.selected;
      updateSummary();
    });
  }

  function escapeHtml(value) {
    return $('<div>').text(value ?? '').html();
  }

  function descendantsOf(parent, allItems) {
    const children = allItems.filter(item => String(item.parent_id) === String(parent.id));
    return children.flatMap(child => [child, ...descendantsOf(child, allItems)]);
  }

  function makePermissionGroups(items) {
    const roots = items.filter(item => item.parent_id === null || Number(item.parent_id) === -1);
    const groupedIds = new Set();
    const groups = roots.map(root => {
      const rows = [root, ...descendantsOf(root, items)];
      rows.forEach(row => groupedIds.add(String(row.id)));
      return { id: String(root.id), title: root.name, rows };
    });
    const ungrouped = items.filter(item => !groupedIds.has(String(item.id)));
    if (ungrouped.length) groups.push({ id: 'ungrouped', title: 'Quyền khác', rows: ungrouped });
    return groups;
  }

  function permissionCode(item) {
    return item.slug || `permission-${item.id}`;
  }

  function renderPermissionRow(item, rootId) {
    const id = String(item.id);
    const checked = selectedPermissionIds.includes(id) ? ' checked' : '';
    const parentBadge = id === String(rootId) ? '<span class="cms-permission-parent-badge">Quyền cha</span>' : '';
    const searchText = `${permissionCode(item)} ${item.name}`.toLocaleLowerCase('vi');

    return `
      <tr class="cms-role-permission-row" data-permission-row data-search="${escapeHtml(searchText)}">
        <td class="cms-permission-toggle-cell">
          <div class="form-check form-switch m-0">
            <input class="form-check-input cms-permission-switch" type="checkbox" value="${escapeHtml(item.id_encrypt)}" data-permission-id="${escapeHtml(id)}" aria-label="Bật quyền ${escapeHtml(item.name)}"${checked}>
          </div>
        </td>
        <td class="cms-permission-code"><code>${escapeHtml(permissionCode(item))}</code>${parentBadge}</td>
        <td class="cms-permission-name">${escapeHtml(item.name)}</td>
        <td class="cms-permission-description">—</td>
      </tr>`;
  }

  function updateTableSummary() {
    const total = $tree.find('.cms-permission-switch').length;
    const selected = $tree.find('.cms-permission-switch:checked').length;
    $summary.text(`Đã chọn ${selected}/${total} quyền`);
    $permissionError.text('');
  }

  function initializePermissionGroups(items) {
    permissionGroups = makePermissionGroups(items);
    permissionGroups.forEach(group => {
      $('<option>', { value: group.id, text: `${group.title} (${group.rows.length})` }).appendTo($groupFilter);
    });

    $tree.html(permissionGroups.map(group => `
      <section class="cms-role-permission-group" data-permission-group="${escapeHtml(group.id)}">
        <header class="cms-role-permission-group-header">
          <button type="button" class="cms-permission-group-collapse" data-collapse-group aria-expanded="true" aria-label="Thu gọn cụm ${escapeHtml(group.title)}">
            <i class="ri-arrow-down-s-line" aria-hidden="true"></i><span>${escapeHtml(group.title)}</span>
          </button>
          <div class="cms-role-permission-group-tools">
            <span class="cms-permission-group-count">${group.rows.length}</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle-group>Bật/Tắt cả nhóm</button>
          </div>
        </header>
        <div class="table-responsive cms-role-permission-table-wrap">
          <table class="table cms-role-permission-table mb-0">
            <thead><tr><th>Bật</th><th>Mã quyền</th><th>Tên quyền</th><th>Mô tả</th></tr></thead>
            <tbody>${group.rows.map(row => renderPermissionRow(row, group.rows[0].id)).join('')}</tbody>
          </table>
        </div>
      </section>`).join(''));

    updateTableSummary();
    setStatus('');
  }

  function visibleRows($group) {
    return $group.find('[data-permission-row]').filter(function () {
      return !$(this).hasClass('cms-permission-row-filtered');
    });
  }

  function applyTableFilters() {
    const query = $search.val().trim().toLocaleLowerCase('vi');
    const groupId = String($groupFilter.val() || '');
    let visibleGroupCount = 0;

    $tree.find('[data-permission-group]').each(function () {
      const $group = $(this);
      const groupMatches = !groupId || String($group.data('permission-group')) === groupId;
      $group.find('[data-permission-row]').each(function () {
        const $row = $(this);
        $row.toggleClass('cms-permission-row-filtered', Boolean(query) && !String($row.data('search')).includes(query));
      });
      const hasRows = visibleRows($group).length > 0;
      $group.toggleClass('cms-permission-group-filtered', !groupMatches || !hasRows);
      if (groupMatches && hasRows) visibleGroupCount += 1;
    });

    setStatus(visibleGroupCount ? '' : `Không tìm thấy quyền phù hợp với “${$search.val().trim()}”.`, 'empty');
  }

  async function loadTree() {
    try {
      setStatus('Đang tải danh sách quyền...');
      await loadSelectedPermissions();

      const payload = await requestJson(urls.roleList || '/cms/api/vai-tro/getDsVaiTro');
      if (payload.code !== 200 || !Array.isArray(payload.data)) throw new Error('Invalid permission payload');

      initializePermissionGroups(payload.data);
    } catch (_error) {
      setStatus('Không thể tải danh sách quyền. Vui lòng tải lại trang.', 'error');
    }
  }

  $search.on('input', function () {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(applyTableFilters, 150);
  });

  $groupFilter.on('change', applyTableFilters);

  $tree.on('change', '.cms-permission-switch', updateTableSummary);

  $tree.on('click', '[data-toggle-group]', function () {
    const $switches = visibleRows($(this).closest('[data-permission-group]')).find('.cms-permission-switch');
    const shouldCheck = $switches.filter(':not(:checked)').length > 0;
    $switches.prop('checked', shouldCheck).trigger('change');
  });

  $tree.on('click', '[data-collapse-group]', function () {
    const $group = $(this).closest('[data-permission-group]');
    const collapsed = $group.toggleClass('is-collapsed').hasClass('is-collapsed');
    $(this).attr('aria-expanded', String(!collapsed));
  });

  $('#permission-expand-all').on('click', function () {
    $tree.find('[data-permission-group]').removeClass('is-collapsed').find('[data-collapse-group]').attr('aria-expanded', 'true');
  });
  $('#permission-collapse-all').on('click', function () {
    $tree.find('[data-permission-group]:not(.cms-permission-group-filtered)').addClass('is-collapsed').find('[data-collapse-group]').attr('aria-expanded', 'false');
  });
  $('#permission-check-all').on('click', function () {
    $tree.find('.cms-permission-switch').prop('checked', true).trigger('change');
  });
  $('#permission-uncheck-all').on('click', function () {
    $tree.find('.cms-permission-switch').prop('checked', false).trigger('change');
  });

  $form.on('submit', function (event) {
    event.preventDefault();

    const nameValue = $name.val().trim();
    const encryptedIds = $tree.find('.cms-permission-switch:checked').map(function () { return this.value; }).get();
    let valid = true;

    $nameError.text('');
    $permissionError.text('');
    $name.removeClass('is-invalid');

    if (!nameValue) {
      $nameError.text('Tên vai trò không được để trống.');
      $name.addClass('is-invalid');
      valid = false;
    } else if (nameValue.length > 255) {
      $nameError.text('Tên vai trò không được vượt quá 255 ký tự.');
      $name.addClass('is-invalid');
      valid = false;
    }

    if (!encryptedIds.length) {
      $permissionError.text('Vui lòng chọn ít nhất 1 quyền.');
      valid = false;
    }

    if (!valid) return;

    const formData = new FormData(this);
    formData.append('permissions', JSON.stringify(encryptedIds));
    const $submitButton = $form.find('button[type="submit"]');
    $submitButton.prop('disabled', true);

    $.ajax({
      url: `${urls.roleSave || '/cms/api/vai-tro/save'}/${roleId}`,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function () {
        toastr.success(String(roleId) === '-1' ? 'Thêm mới vai trò thành công!' : 'Cập nhật vai trò thành công!');
        window.location.href = $form.data('index-url');
      },
      error: function () {
        toastr.error('Không thể lưu vai trò. Vui lòng kiểm tra lại dữ liệu.');
        $submitButton.prop('disabled', false);
      }
    });
  });

  loadTree();
});
