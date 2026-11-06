/*
 * Trình dựng khảo sát (quản trị). Không phụ thuộc bản build Vite: nạp trực tiếp, dùng SortableJS có sẵn
 * (/assets/plugin/sortable/sortable.min.js). Cấu hình (nhãn, kiểu câu hỏi, dữ liệu ban đầu, chế độ chỉ đọc) lấy từ
 * <script type="application/json" id="survey-builder-config"> do Blade sinh ra; không có URL hay ID nào viết cứng ở đây.
 * Mọi phần tử có ID thật (id) khi đã lưu; phần tử mới có id = null và chỉ được định danh tạm bằng _uid phía trình duyệt.
 */
(function () {
    'use strict';

    var cfgEl = document.getElementById('survey-builder-config');
    var root = document.getElementById('survey-builder');
    var form = document.getElementById('survey-structure-form');
    if (!cfgEl || !root || !form) return;

    var cfg = JSON.parse(cfgEl.textContent);
    var t = cfg.i18n || {};
    var types = cfg.types || [];
    var readonly = !!cfg.readonly;
    var uidSeq = 0;
    var registry = {}; // _uid -> đối tượng trạng thái

    function uid() { uidSeq += 1; return 'u' + uidSeq; }
    function reg(obj) { obj._uid = uid(); registry[obj._uid] = obj; return obj; }

    function el(tag, attrs, children) {
        var node = document.createElement(tag);
        Object.keys(attrs || {}).forEach(function (k) {
            var v = attrs[k];
            if (v === false || v === null || v === undefined) return;
            if (k === 'class') node.className = v;
            else if (k === 'text') node.textContent = v;
            else if (k.indexOf('on') === 0) node.addEventListener(k.slice(2), v);
            else node.setAttribute(k, v === true ? '' : v);
        });
        (children || []).forEach(function (c) { if (c) node.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); });
        return node;
    }

    function typeInfo(value) {
        for (var i = 0; i < types.length; i++) if (types[i].value === value) return types[i];
        return types[0];
    }

    function newOption(text) { return reg({ id: null, text: text || '', enabled: true }); }
    function newQuestion(type) {
        var q = reg({ id: null, type: type || 'single', text: '', description: '', required: false, enabled: true, config: {}, options: [] });
        normalizeQuestion(q);
        return q;
    }
    function newGroup(name) { return reg({ id: null, name: name || '', description: '', questions: [] }); }

    function normalizeQuestion(q) {
        var info = typeInfo(q.type);
        if (info.options) {
            if (!q.options.length) { q.options = [newOption(''), newOption('')]; }
        } else {
            q.options = [];
        }
        if (q.type === 'scale') {
            q.config = Object.assign({ min: 1, max: 5, min_label: '', max_label: '' }, Array.isArray(q.config) ? {} : q.config);
        } else {
            q.config = {};
        }
    }

    function hydrate(groups) {
        return (groups || []).map(function (g) {
            var group = reg({ id: g.id, name: g.name || '', description: g.description || '', questions: [] });
            (g.questions || []).forEach(function (q) {
                var question = reg({
                    id: q.id, type: q.type, text: q.text || '', description: q.description || '',
                    required: !!q.required, enabled: q.enabled !== false,
                    config: (q.config && !Array.isArray(q.config)) ? q.config : {}, options: []
                });
                (q.options || []).forEach(function (o) {
                    question.options.push(reg({ id: o.id, text: o.text || '', enabled: o.enabled !== false }));
                });
                if (typeInfo(question.type).options && !question.options.length) normalizeQuestion(question);
                group.questions.push(question);
            });
            return group;
        });
    }

    var state = hydrate(cfg.initial);
    if (!state.length && !readonly) state.push(newGroup(t.default_group || ''));

    // ---------------------------------------------------------------- render

    function input(obj, field, attrs) {
        var a = Object.assign({ type: 'text', class: 'form-control', value: obj[field] || '' }, attrs || {});
        if (readonly) a.disabled = true;
        a.oninput = function (e) { obj[field] = e.target.value; };
        return el('input', a);
    }

    function check(obj, field, label) {
        var id = 'c' + obj._uid + field;
        var box = el('input', { type: 'checkbox', class: 'form-check-input', id: id, disabled: readonly, checked: !!obj[field] });
        box.onchange = function (e) { obj[field] = e.target.checked; };
        return el('div', { class: 'form-check form-check-inline mb-0' }, [box, el('label', { class: 'form-check-label', for: id, text: label })]);
    }

    function iconBtn(icon, title, handler, cls) {
        if (readonly) return null;
        return el('button', { type: 'button', class: 'btn btn-sm btn-text-secondary ' + (cls || ''), title: title, 'aria-label': title, onclick: handler }, [el('i', { class: icon })]);
    }

    function renderOptions(q) {
        var list = el('div', { class: 'sv-options', 'data-role': 'options', 'data-q': q._uid });
        q.options.forEach(function (o) {
            var row = el('div', { class: 'sv-option d-flex align-items-center gap-2 mb-2', 'data-uid': o._uid }, [
                readonly ? null : el('span', { class: 'sv-handle sv-handle-option text-muted', title: t.drag }, [el('i', { class: 'ri-draggable' })]),
                el('div', { class: 'flex-grow-1' }, [input(o, 'text', { placeholder: t.option_placeholder, maxlength: 500, 'aria-label': t.option_placeholder })]),
                check(o, 'enabled', t.enabled),
                iconBtn('ri-delete-bin-line', t.remove, function () {
                    if (q.options.length <= 2) { window.alert(t.options_min); return; }
                    q.options = q.options.filter(function (x) { return x !== o; });
                    render();
                }, 'text-danger')
            ]);
            list.appendChild(row);
        });
        var wrap = el('div', {}, [list]);
        if (!readonly) wrap.appendChild(el('button', { type: 'button', class: 'btn btn-sm btn-outline-secondary', onclick: function () { q.options.push(newOption('')); render(); } }, [el('i', { class: 'ri-add-line me-1' }), t.add_option]));
        return wrap;
    }

    function renderScale(q) {
        var c = q.config;
        function num(field, label) {
            var i = el('input', { type: 'number', class: 'form-control', value: c[field], min: 0, max: 10, disabled: readonly, 'aria-label': label });
            i.oninput = function (e) { c[field] = parseInt(e.target.value, 10); };
            return el('div', { class: 'col-6 col-md-2' }, [el('label', { class: 'form-label small', text: label }), i]);
        }
        function txt(field, label) {
            var i = el('input', { type: 'text', class: 'form-control', value: c[field] || '', maxlength: 100, disabled: readonly, 'aria-label': label });
            i.oninput = function (e) { c[field] = e.target.value; };
            return el('div', { class: 'col-12 col-md-4' }, [el('label', { class: 'form-label small', text: label }), i]);
        }
        return el('div', { class: 'row g-2' }, [num('min', t.scale_min), num('max', t.scale_max), txt('min_label', t.scale_min_label), txt('max_label', t.scale_max_label)]);
    }

    function renderQuestion(g, q, index) {
        var select = el('select', { class: 'form-select', disabled: readonly, 'aria-label': t.type }, types.map(function (ty) {
            return el('option', { value: ty.value, text: ty.label, selected: ty.value === q.type });
        }));
        select.onchange = function (e) {
            if (q.id && !window.confirm(t.confirm_type_change)) { e.target.value = q.type; return; }
            q.type = e.target.value; normalizeQuestion(q); render();
        };
        var body = [];
        if (typeInfo(q.type).options) body.push(renderOptions(q));
        else if (q.type === 'scale') body.push(renderScale(q));
        else if (q.type === 'yesno') body.push(el('p', { class: 'text-muted small mb-0', text: t.yesno_hint }));
        else body.push(el('p', { class: 'text-muted small mb-0', text: t.text_hint }));

        return el('div', { class: 'sv-question card mb-3' + (q.enabled ? '' : ' sv-disabled'), 'data-uid': q._uid }, [
            el('div', { class: 'card-body' }, [
                el('div', { class: 'd-flex align-items-start gap-2 mb-2' }, [
                    readonly ? null : el('span', { class: 'sv-handle sv-handle-question text-muted pt-2', title: t.drag }, [el('i', { class: 'ri-draggable ri-lg' })]),
                    el('div', { class: 'flex-grow-1' }, [input(q, 'text', { placeholder: t.question_placeholder, maxlength: 2000, 'aria-label': t.question_placeholder })]),
                    el('div', { style: 'min-width:170px' }, [select])
                ]),
                el('div', { class: 'mb-2' }, [input(q, 'description', { placeholder: t.question_hint_placeholder, maxlength: 1000, 'aria-label': t.question_hint_placeholder })]),
                el('div', { class: 'mb-3' }, body),
                el('div', { class: 'd-flex flex-wrap align-items-center gap-3 border-top pt-2' }, [
                    check(q, 'required', t.required),
                    check(q, 'enabled', t.enabled),
                    el('span', { class: 'ms-auto' }),
                    iconBtn('ri-file-copy-line', t.duplicate, function () { duplicateQuestion(g, q); }),
                    iconBtn('ri-delete-bin-line', t.remove, function () {
                        if (!window.confirm(t.confirm_remove_question)) return;
                        g.questions = g.questions.filter(function (x) { return x !== q; });
                        render();
                    }, 'text-danger')
                ])
            ])
        ]);
    }

    function renderGroup(g, gi) {
        var qList = el('div', { class: 'sv-questions', 'data-role': 'questions', 'data-g': g._uid });
        g.questions.forEach(function (q, qi) { qList.appendChild(renderQuestion(g, q, qi)); });
        var addBar = readonly ? null : el('div', { class: 'd-flex flex-wrap gap-2' }, [
            el('button', { type: 'button', class: 'btn btn-sm btn-primary', onclick: function () { g.questions.push(newQuestion('single')); render(); } }, [el('i', { class: 'ri-add-line me-1' }), t.add_question])
        ]);
        return el('section', { class: 'sv-group card mb-4 border-primary', 'data-uid': g._uid }, [
            el('div', { class: 'card-header d-flex align-items-center gap-2 bg-label-primary' }, [
                readonly ? null : el('span', { class: 'sv-handle sv-handle-group', title: t.drag }, [el('i', { class: 'ri-draggable ri-lg' })]),
                el('span', { class: 'fw-bold text-nowrap', text: t.group + ' ' + (gi + 1) }),
                el('div', { class: 'flex-grow-1' }, [input(g, 'name', { placeholder: t.group_placeholder, maxlength: 255, 'aria-label': t.group_placeholder })]),
                iconBtn('ri-delete-bin-line', t.remove, function () {
                    if (!window.confirm(t.confirm_remove_group)) return;
                    state = state.filter(function (x) { return x !== g; }); render();
                }, 'text-danger')
            ]),
            el('div', { class: 'card-body' }, [
                el('div', { class: 'mb-3' }, [input(g, 'description', { placeholder: t.group_desc_placeholder, 'aria-label': t.group_desc_placeholder })]),
                qList,
                addBar
            ])
        ]);
    }

    function render() {
        root.innerHTML = '';
        var list = el('div', { 'data-role': 'groups' });
        state.forEach(function (g, i) { list.appendChild(renderGroup(g, i)); });
        root.appendChild(list);
        if (!readonly) {
            root.appendChild(el('button', { type: 'button', class: 'btn btn-outline-primary', onclick: function () { state.push(newGroup('')); render(); } }, [el('i', { class: 'ri-add-line me-1' }), t.add_group]));
        }
        bindSortable(list);
        updateCount();
    }

    function updateCount() {
        var out = document.getElementById('survey-builder-count');
        if (!out) return;
        var n = state.reduce(function (s, g) { return s + g.questions.length; }, 0);
        out.textContent = n + ' ' + t.questions_word;
    }

    function duplicateQuestion(g, q) {
        var copy = newQuestion(q.type);
        copy.text = q.text; copy.description = q.description; copy.required = q.required; copy.enabled = q.enabled;
        copy.config = JSON.parse(JSON.stringify(q.config || {}));
        copy.options = (q.options || []).map(function (o) { return newOption(o.text); });
        g.questions.splice(g.questions.indexOf(q) + 1, 0, copy);
        render();
    }

    // ---------------------------------------------------------------- kéo thả

    function bindSortable(list) {
        if (readonly || typeof window.Sortable === 'undefined') return;
        window.Sortable.create(list, { handle: '.sv-handle-group', animation: 150, draggable: '.sv-group', onEnd: readOrder });
        list.querySelectorAll('[data-role="questions"]').forEach(function (c) {
            window.Sortable.create(c, { group: 'sv-questions', handle: '.sv-handle-question', animation: 150, draggable: '.sv-question', onEnd: readOrder });
        });
        list.querySelectorAll('[data-role="options"]').forEach(function (c) {
            window.Sortable.create(c, { handle: '.sv-handle-option', animation: 150, draggable: '.sv-option', onEnd: readOrder });
        });
    }

    // Đọc lại thứ tự từ DOM sau khi kéo thả (DOM đã đúng nên không cần dựng lại).
    function readOrder() {
        var groups = [];
        root.querySelectorAll('.sv-group').forEach(function (gNode) {
            var g = registry[gNode.getAttribute('data-uid')];
            g.questions = [];
            gNode.querySelectorAll('.sv-question').forEach(function (qNode) {
                var q = registry[qNode.getAttribute('data-uid')];
                q.options = [];
                qNode.querySelectorAll('.sv-option').forEach(function (oNode) { q.options.push(registry[oNode.getAttribute('data-uid')]); });
                g.questions.push(q);
            });
            groups.push(g);
        });
        state = groups;
        root.querySelectorAll('.sv-group > .card-header .fw-bold').forEach(function (n, i) { n.textContent = t.group + ' ' + (i + 1); });
        updateCount();
    }

    // ---------------------------------------------------------------- lưu

    function serialize() {
        return state.map(function (g) {
            return {
                id: g.id, name: g.name, description: g.description,
                questions: g.questions.map(function (q) {
                    return {
                        id: q.id, type: q.type, text: q.text, description: q.description, required: q.required, enabled: q.enabled,
                        config: q.config, options: q.options.map(function (o) { return { id: o.id, text: o.text, enabled: o.enabled }; })
                    };
                })
            };
        });
    }

    function validate() {
        for (var i = 0; i < state.length; i++) {
            var g = state[i];
            if (!g.name.trim()) return t.err_group_name.replace(':n', i + 1);
            for (var j = 0; j < g.questions.length; j++) {
                var q = g.questions[j];
                var at = (i + 1) + '.' + (j + 1);
                if (!q.text.trim()) return t.err_question_text.replace(':at', at);
                if (typeInfo(q.type).options) {
                    var filled = q.options.filter(function (o) { return o.text.trim(); }).length;
                    if (filled < 2) return t.err_options.replace(':at', at);
                }
            }
        }
        return null;
    }

    if (!readonly) {
        form.addEventListener('submit', function (e) {
            var problem = validate();
            if (problem) { e.preventDefault(); window.alert(problem); return; }
            document.getElementById('survey-structure-input').value = JSON.stringify(serialize());
        });
    }

    render();
})();
