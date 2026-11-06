<style>
.cms-editorial-admin{--cms-ink:#10264d;--cms-muted:#6e83ae;--cms-line:#d4e0f3;--cms-blue:#0879ff;--cms-sidebar:244px;min-height:100vh;background:linear-gradient(130deg,#f1f7ff,#f5f9ff 65%,#eaf3ff);color:var(--cms-ink);font:14px/1.5 Inter,Arial,sans-serif}
.cms-editorial-admin *{box-sizing:border-box}.cms-editorial-admin [hidden]{display:none!important}
.cms-editorial-admin .cms-admin-topbar{height:64px;display:flex;align-items:center;gap:18px;padding:0 30px 0 20px;position:fixed;inset:0 0 auto;z-index:1050;border-bottom:1px solid #e3ecf9;background:linear-gradient(145deg,#fff 25%,#edf5ff 75%,#fff);box-shadow:0 2px 10px #173a6610}
.cms-editorial-admin .cms-admin-brand{width:calc(var(--cms-sidebar) - 32px);flex-shrink:0;display:flex;align-items:center;height:64px}.cms-admin-brand img{width:190px;height:auto;max-height:64px;object-fit:contain}
.cms-editorial-admin button{font:inherit}.cms-editorial-admin .cms-menu-button{border:0;background:none;color:#294a82;font-size:23px;padding:8px;line-height:1;cursor:pointer}
.cms-editorial-admin .cms-admin-search{border:1px solid #d8e5f6;border-radius:10px;display:flex;align-items:center;gap:10px;max-width:580px;flex:1;height:40px;padding:0 15px;color:#96acce;background:#ffffff65}.cms-admin-search input{border:0;outline:0;background:none;width:100%;color:var(--cms-ink)}.cms-admin-search input::placeholder{color:#8398be}
.cms-editorial-admin .cms-admin-account{margin-left:auto}.cms-editorial-admin .cms-account-button{display:flex;align-items:center;gap:10px;border:0;background:none;color:var(--cms-ink);font-weight:600;cursor:pointer}.cms-account-avatar{width:36px;height:36px;display:grid;place-items:center;border-radius:50%;background:#382a26;color:white;font-size:12px}
.cms-editorial-admin .cms-admin-sidebar{position:fixed;top:64px;left:0;bottom:0;width:var(--cms-sidebar);overflow-y:auto;border-right:1px solid #e5edf9;background:#f9fcff;z-index:1040;padding:16px 10px;transition:transform .2s,width .2s,box-shadow .2s;overflow-x:hidden}.cms-sidebar-home{display:flex;align-items:center;gap:16px;padding:9px 18px 16px;color:var(--cms-ink);font-weight:600}.cms-sidebar-home i:first-child{font-size:21px}
.cms-editorial-admin .cms-admin-links{list-style:none;padding:0;margin:0}.cms-editorial-admin .cms-nav-heading{padding:14px 20px 6px;text-transform:uppercase;font-size:11px;color:#899bbf}.cms-editorial-admin .cms-nav-link{display:flex;align-items:center;gap:10px;padding:10px 16px;border-radius:8px;color:#294777;min-height:42px}.cms-editorial-admin .cms-nav-link>i{font-size:21px;width:20px;flex-shrink:0;margin:0}.cms-editorial-admin .cms-nav-link:hover,.cms-editorial-admin .cms-nav-item.active>.cms-nav-link{background:#e3f0ff;color:#0879ff}.cms-editorial-admin .menu-sub{display:none;list-style:none;padding:0 0 0 20px}.cms-editorial-admin .open>.menu-sub{display:block}.cms-editorial-admin .menu-sub .menu-link{color:#294777;padding:8px;display:block}.cms-menu-empty{color:var(--cms-muted);padding:16px}
.cms-editorial-admin .cms-admin-main{margin-left:var(--cms-sidebar);padding:84px 32px 28px;min-width:0}.cms-menu-backdrop{display:none}
.cms-editorial-admin .cms-breadcrumb{display:flex;align-items:center;gap:7px;font-size:13px;color:var(--cms-muted);margin-bottom:8px;flex-wrap:wrap}.cms-breadcrumb a{color:#657ca7}.cms-breadcrumb i:first-child{color:#0879ff;font-size:18px}
.cms-editorial-admin .cms-page-heading{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:12px}.cms-editorial-admin h1{font:700 27px/1.2 Inter,Arial,sans-serif;color:#0f244d;margin:0 0 5px}.cms-heading-note{color:#6d81ae;margin:0;font-size:13px}.cms-editorial-admin .cms-status{margin-left:10px;vertical-align:middle;font-size:11px;padding:4px 7px;border-radius:4px;display:inline-block;background:#e4efff;color:#375f93;font-weight:500}
.cms-editorial-admin .cms-form-grid{display:grid;grid-template-columns:minmax(0,1.86fr) minmax(310px,1fr);gap:22px;align-items:start}.cms-editorial-admin .cms-form-column{min-width:0;display:flex;flex-direction:column;gap:12px}.cms-editorial-admin .cms-field-card{border:0;border-radius:11px;background:#fff;padding:10px 20px;box-shadow:0 4px 18px #35609907;min-width:0}.cms-editorial-admin .cms-field-card>label,.cms-editorial-admin .cms-card-label{display:block;font-weight:600;color:#12254d;margin:0 0 6px;line-height:1.3}.cms-editorial-admin .cms-required{color:#f01d30}.cms-editorial-admin .form-control,.cms-editorial-admin .form-select{border:1px solid var(--cms-line);border-radius:8px;background:#fff;box-shadow:inset 0 1px 2px #153e7010;padding:8px 12px;min-height:36px;font-size:13px;color:#23416c}.cms-editorial-admin .form-control:focus,.cms-editorial-admin .form-select:focus{border-color:#58a1ff;box-shadow:0 0 0 3px #0879ff12}.cms-editorial-admin .form-control::placeholder{color:#7b91b8}.cms-editorial-admin textarea.form-control{resize:vertical}.cms-editorial-admin .cms-counter{display:block;text-align:right;font-size:12px;color:var(--cms-muted);line-height:1.2;margin:4px 3px -5px}.cms-editorial-admin .cms-help{color:var(--cms-muted);font-size:12px;margin:6px 0 0}
.cms-editorial-admin .cms-slug-wrap,.cms-editorial-admin .cms-category-search{position:relative}.cms-slug-wrap input{padding-right:36px!important}.cms-slug-wrap i{position:absolute;right:12px;top:8px;color:#315284}.cms-category-search i{position:absolute;left:12px;top:9px;color:#7e97bf}.cms-category-search input{padding-left:35px!important;margin-bottom:9px}
.cms-editorial-admin .cms-category-list{border:1px solid var(--cms-line);border-radius:8px;max-height:246px;overflow-y:auto;padding:9px 12px}.cms-editorial-admin .cms-category-row{display:flex;gap:10px;align-items:flex-start;margin:0 0 5px}.cms-editorial-admin .cms-category-row:last-child{margin-bottom:0}.cms-editorial-admin .cms-category-row input{margin:2px 0 0;flex-shrink:0;width:16px;height:16px;accent-color:#0879ff}.cms-editorial-admin .cms-category-row label{font-weight:400;font-size:13px;margin:0;cursor:pointer;line-height:1.45}.cms-editorial-admin .cms-tags{display:flex;gap:7px;flex-wrap:wrap;margin-top:9px}.cms-editorial-admin .cms-tag{display:inline-flex;align-items:center;gap:6px;border-radius:6px;padding:5px 8px;background:#f0f6ff;border:1px solid #deebff;font-size:12px}.cms-editorial-admin .cms-tag label{margin:0;font-weight:400}
.cms-editorial-admin .cms-image-picker{border:1px dashed #c5dcfa;border-radius:9px;padding:13px;background:#f8fbff;display:flex;gap:12px;align-items:center}.cms-image-symbol{width:46px;height:48px;display:grid;place-items:center;border-radius:10px;background:#e5f1ff;color:#0879ff;font-size:26px;flex-shrink:0}.cms-image-text{flex:1;min-width:0;font-size:13px;font-weight:600}.cms-image-text small{display:block;color:var(--cms-muted);font-weight:400;font-size:11px;margin-top:4px}.cms-editorial-admin #holder img{max-width:100%;max-height:160px;object-fit:contain;border-radius:6px;margin:0 0 12px;height:auto!important}.cms-editorial-admin .cms-options label{display:flex;align-items:flex-start;gap:12px;margin:0 0 12px;font-weight:400}.cms-editorial-admin .cms-options label:last-child{margin:0}.cms-options input{width:18px;height:18px;accent-color:#0879ff;margin-top:2px;flex-shrink:0}.cms-options small{display:block;color:var(--cms-muted);font-size:12px;margin-top:3px}
.cms-editorial-admin .cms-form-actions{display:flex;align-items:center;gap:10px;padding-top:12px;flex-wrap:wrap}.cms-editorial-admin .cms-form-actions .cms-cancel{margin-left:auto}.cms-editorial-admin .btn{border-radius:7px;min-height:39px;font-weight:600;font-size:13px;text-transform:none;padding:8px 17px;box-shadow:none;display:inline-flex;align-items:center;justify-content:center;gap:8px}.cms-editorial-admin .btn-primary{background:#0879ff;border-color:#0879ff;color:#fff}.cms-editorial-admin .cms-save{background:linear-gradient(#2d5592,#12336d);border-color:#244780;color:#fff}.cms-editorial-admin .btn-outline-secondary{border-color:#94b3e0;color:#214679;background:#fff}.cms-editorial-admin .btn-outline-primary{border-color:#a4ceff;background:#f5faff;color:#0879ff}.cms-editorial-admin .cms-image-picker .btn{min-height:35px;padding:7px 10px;white-space:nowrap;font-weight:500}.cms-editorial-admin fieldset{min-width:0;border:0;margin:0;padding:0}.cms-editorial-admin fieldset:disabled .form-control{background:#f9fbff}.cms-editorial-admin .cms-extra-panel{margin-top:16px}.cms-editorial-admin .cms-history-table{overflow:auto}.cms-editorial-admin .table{font-size:13px;margin:0}.cms-editorial-admin .table th{font-size:11px;color:#7185aa;white-space:nowrap}.cms-editorial-admin .table td{min-width:110px}
.cms-editorial-admin .ck.ck-editor{--ck-border-radius:6px;--ck-color-base-border:#d4e0f3;--ck-font-size-base:13px}.cms-editorial-admin .ck-editor__editable_inline{min-height:75px!important}.cms-editorial-admin .ck.ck-toolbar{background:#fff}.cms-editorial-admin .ck.ck-editor__main>.ck-editor__editable{color:#294777}.cms-editorial-admin .ck.ck-editor__editable.ck-blurred .ck-widget.ck-widget_selected{outline:none}
@media(min-width:1500px){.cms-editorial-admin .cms-field-card{padding:10px 20px}.cms-editorial-admin .cms-admin-main{padding:84px 36px 28px}}
@media(max-width:1099px){.cms-editorial-admin .cms-admin-sidebar{transform:translateX(-100%)}.cms-editorial-admin .cms-admin-main{margin-left:0;padding:84px 20px 24px}.cms-editorial-admin.cms-menu-open .cms-admin-sidebar{transform:none}.cms-editorial-admin.cms-menu-open .cms-menu-backdrop{display:block;position:fixed;inset:64px 0 0;border:0;background:#0d24464d;z-index:1030}.cms-editorial-admin .cms-admin-brand{width:180px}.cms-editorial-admin .cms-admin-brand img{width:175px}.cms-editorial-admin .cms-admin-topbar{padding:0 16px;gap:10px}}
@media(max-width:767px){.cms-editorial-admin .cms-form-grid{grid-template-columns:minmax(0,1fr);gap:15px}.cms-editorial-admin .cms-admin-search{display:none}.cms-editorial-admin .cms-admin-brand{width:145px}.cms-editorial-admin .cms-admin-brand img{width:145px}.cms-editorial-admin .cms-account-button>span:not(.cms-account-avatar){display:none}.cms-editorial-admin .cms-admin-main{padding:80px 14px 20px}.cms-editorial-admin .cms-page-heading{flex-wrap:wrap}.cms-editorial-admin h1{font-size:24px}.cms-editorial-admin .cms-field-card{padding:14px}.cms-editorial-admin .cms-image-picker{flex-wrap:wrap}.cms-editorial-admin .cms-form-actions .cms-cancel{margin-left:0}.cms-editorial-admin .cms-form-actions .btn{flex:1}.cms-editorial-admin .cms-account-button{gap:5px}.cms-editorial-admin .cms-category-list{max-height:246px}}
@media(prefers-reduced-motion:reduce){.cms-editorial-admin .cms-admin-sidebar{transition:none}}
.cms-editorial-admin .cms-sidebar-heading{display:flex;align-items:center;gap:4px;min-height:52px}
.cms-editorial-admin .cms-sidebar-heading .cms-sidebar-home{flex:1;min-width:0;padding:9px 12px 16px;overflow:hidden}
.cms-editorial-admin .cms-sidebar-toggle{border:0;background:none;color:#294777;display:grid;place-items:center;width:30px;height:32px;padding:4px;flex-shrink:0;border-radius:6px;cursor:pointer}
.cms-editorial-admin .cms-sidebar-toggle:hover{background:#e3f0ff}.cms-editorial-admin .cms-sidebar-toggle i{font-size:20px;transition:transform .2s}
.cms-editorial-admin .cms-admin-main{transition:margin-left .2s}
@media(min-width:1100px){
    .cms-editorial-admin.cms-menu-collapsed .cms-admin-main{margin-left:76px}
    .cms-editorial-admin.cms-menu-collapsed .cms-admin-sidebar{width:76px;transform:none}
    .cms-editorial-admin.cms-menu-collapsed .cms-sidebar-heading{flex-direction:row-reverse;justify-content:flex-end;gap:14px}
    .cms-editorial-admin.cms-menu-collapsed .cms-sidebar-toggle{margin-left:13px}
    .cms-editorial-admin.cms-menu-collapsed .cms-sidebar-toggle i{transform:rotate(180deg)}
    .cms-editorial-admin.cms-menu-collapsed:not(.cms-menu-peek) .cms-nav-link{justify-content:center;gap:0;padding:10px 12px}
    .cms-editorial-admin.cms-menu-collapsed:not(.cms-menu-peek) .cms-nav-link>span,.cms-editorial-admin.cms-menu-collapsed:not(.cms-menu-peek) .cms-sidebar-home>span{display:none}
    .cms-editorial-admin.cms-menu-collapsed:not(.cms-menu-peek) .cms-sidebar-home{display:none}
    .cms-editorial-admin.cms-menu-collapsed:not(.cms-menu-peek) .cms-nav-heading{font-size:0;padding:0;height:12px;margin:8px;border-top:1px solid #e5edf9}
    .cms-editorial-admin.cms-menu-collapsed:not(.cms-menu-peek) .menu-sub{display:none!important}
    .cms-editorial-admin.cms-menu-collapsed.cms-menu-peek .cms-admin-sidebar{width:var(--cms-sidebar);box-shadow:8px 0 24px #173a6620}
}
@media(prefers-reduced-motion:reduce){.cms-editorial-admin .cms-admin-main,.cms-editorial-admin .cms-sidebar-toggle i{transition:none}}
.cms-editorial-admin .btn.btn-primary{background:#0879ff!important;border-color:#0879ff!important}.cms-editorial-admin .btn.cms-save{background:linear-gradient(#2d5592,#12336d)!important;border-color:#244780!important}
@media(max-width:1099px){.cms-editorial-admin.cms-menu-open .cms-menu-backdrop{left:var(--cms-sidebar)}}
.cms-editorial-admin .cms-news-heading{align-items:center;flex-wrap:wrap}
/* Filled controls are local to the news create/edit form and its action row. */
.cms-editorial-admin .cms-news-form .form-control,.cms-editorial-admin .cms-news-form .form-select,.cms-editorial-admin .cms-news-form .input-group-text{border:0;background:#edeff6;box-shadow:none}
.cms-editorial-admin .cms-news-form fieldset:disabled .form-control,.cms-editorial-admin .cms-news-form .form-control:disabled,.cms-editorial-admin .cms-news-form .form-select:disabled{background:#f0f2f7;color:#63799d}
.cms-editorial-admin .cms-news-form .form-control:focus,.cms-editorial-admin .cms-news-form .form-select:focus{border:0;background:#e6ebf6;box-shadow:0 0 0 2px #0879ff26}
.cms-editorial-admin .cms-news-form .form-control.is-invalid,.cms-editorial-admin .cms-news-form .form-select.is-invalid{box-shadow:0 0 0 2px #e34e652b;background-color:#fff0f2}
.cms-editorial-admin .cms-news-form .cms-category-list{border:0;background:#edeff6}
.cms-editorial-admin .cms-news-form .cms-image-picker{border:0;background:#edf3fc}
.cms-editorial-admin .cms-news-form .ck.ck-toolbar,.cms-editorial-admin .cms-news-form .ck-editor__main>.ck-editor__editable,.cms-editorial-admin .cms-news-form .ck-source-editing-area textarea{border:0!important;background:#edeff6}
.cms-editorial-admin .cms-news-form .ck-editor__main>.ck-editor__editable.ck-focused{background:#e6ebf6;box-shadow:0 0 0 2px #0879ff26!important}
.cms-editorial-admin .cms-news-form .btn,.cms-editorial-admin .cms-news-heading .btn{border:0!important}
.cms-editorial-admin .cms-news-form .btn-outline-secondary,.cms-editorial-admin .cms-news-heading .btn-outline-secondary{background:#e6e9f2;color:#214679}
.cms-editorial-admin .cms-news-form .btn-outline-primary,.cms-editorial-admin .cms-news-heading .btn-outline-primary{background:#e1edff;color:#0864d0}
.cms-editorial-admin .cms-news-form .btn-outline-danger,.cms-editorial-admin .cms-news-heading .btn-outline-danger{background:#fde6e9;color:#c72c43}
.cms-editorial-admin .cms-news-form .btn-outline-secondary:hover,.cms-editorial-admin .cms-news-heading .btn-outline-secondary:hover{background:#d9dfeb}
.cms-editorial-admin .cms-news-form .btn-outline-primary:hover,.cms-editorial-admin .cms-news-heading .btn-outline-primary:hover{background:#cfE2ff}
.cms-editorial-admin .cms-news-form .btn-outline-danger:hover,.cms-editorial-admin .cms-news-heading .btn-outline-danger:hover{background:#f9d5db}
.cms-editorial-admin .cms-news-form .btn:focus-visible,.cms-editorial-admin .cms-news-heading .btn:focus-visible{outline:2px solid #7a9bd1;outline-offset:3px}
.cms-editorial-admin .cms-history h2{margin:0 0 10px;font-size:13px;font-weight:600;color:#12254d}
.cms-editorial-admin .cms-history-timeline{list-style:none;margin:0;padding:0}
.cms-editorial-admin .cms-history-entry{--cms-history-color:#7b8fae;--cms-history-bg:#edf2f8;position:relative;padding:0 0 6px 58px;min-width:0}
.cms-editorial-admin .cms-history-entry:not(:last-child)::before{content:"";position:absolute;left:28px;top:22px;bottom:-20px;width:1px;background:#c5cbd4}
.cms-editorial-admin .cms-history-entry:last-child{padding-bottom:0}
.cms-editorial-admin .cms-history-marker{position:absolute;left:16px;top:12px;width:25px;height:25px;border-radius:50%;display:grid;place-items:center;color:#fff;background:linear-gradient(145deg,color-mix(in srgb,var(--cms-history-color),white 20%),var(--cms-history-color));box-shadow:0 1px 3px #19325414;font-size:17px;line-height:1}
.cms-editorial-admin .cms-history-detail{border:1px solid #e8edff;border-radius:7px;background:linear-gradient(110deg,#f7f8ff,#fafcff);padding:7px 14px;min-width:0}
.cms-editorial-admin .cms-history-detail time{display:block;font-size:13px;line-height:1.35;font-weight:700;color:#182750;margin-bottom:3px}
.cms-editorial-admin .cms-history-detail dl{display:grid;grid-template-columns:78px minmax(0,1fr);margin:0;font-size:12px;line-height:1.3;align-items:baseline}
.cms-editorial-admin .cms-history-detail dt{font-weight:400;color:#8595b5}
.cms-editorial-admin .cms-history-detail dd{margin:0;color:#203653;overflow-wrap:anywhere}
.cms-editorial-admin .cms-history-badge{display:inline-flex;align-items:center;gap:5px;border-radius:12px;padding:1px 9px;color:var(--cms-history-color);background:var(--cms-history-bg);font-size:11px;font-weight:500}
.cms-editorial-admin .cms-history-badge::before{content:"";width:7px;height:7px;border-radius:50%;background:currentColor;flex-shrink:0}
.cms-editorial-admin .cms-history-feedback{white-space:pre-wrap}
.cms-editorial-admin .cms-history-feedback em{color:#8b98b3}
.cms-editorial-admin .cms-history-published{--cms-history-color:#55a665;--cms-history-bg:#e1f2e5}
.cms-editorial-admin .cms-history-approved{--cms-history-color:#565eff;--cms-history-bg:#eceeff}
.cms-editorial-admin .cms-history-pending{--cms-history-color:#df9c22;--cms-history-bg:#fff4dd}
.cms-editorial-admin .cms-history-rejected{--cms-history-color:#da5764;--cms-history-bg:#ffedf0}
.cms-editorial-admin .cms-history-stopped{--cms-history-color:#a7793f;--cms-history-bg:#fbefdf}
@media(max-width:767px){.cms-editorial-admin .cms-history-entry{padding-left:40px}.cms-editorial-admin .cms-history-marker{left:4px}.cms-editorial-admin .cms-history-entry:not(:last-child)::before{left:16px}.cms-editorial-admin .cms-history-detail{padding:7px 10px}.cms-editorial-admin .cms-history-detail dl{grid-template-columns:72px minmax(0,1fr)}}
.cms-editorial-admin .cms-news-heading>div:first-child{flex:1 1 300px;min-width:0}
.cms-editorial-admin .cms-news-heading .cms-form-actions{padding-top:0;margin-left:auto;justify-content:flex-end}
.cms-editorial-admin .cms-news-heading .cms-form-actions .cms-cancel{margin-left:0}
.cms-editorial-admin .cms-news-heading .cms-form-actions .btn{white-space:nowrap}
@media(max-width:767px){.cms-editorial-admin .cms-news-heading .cms-form-actions{width:100%;margin-left:0}.cms-editorial-admin .cms-news-heading .cms-form-actions .btn{flex:1 1 auto}}
</style>
<style>
/* Shared administration content inherits the approved frame only inside CMS. */
.cms-editorial-admin .cms-admin-main>.card,.cms-editorial-admin .cms-admin-main .row>.col>.card{border:0;border-radius:11px;box-shadow:0 4px 18px #35609907;background:#fff;overflow:visible}
.cms-editorial-admin .card-header{background:#fff;border-bottom:1px solid #edf2fa;padding:18px 20px;color:#10264d;font-weight:600;text-transform:none!important}
.cms-editorial-admin .card-header.bg-primary,.cms-editorial-admin .card-header.text-white{background:#fff!important;color:#10264d!important}
.cms-editorial-admin .card-header h1,.cms-editorial-admin .card-header h2,.cms-editorial-admin .card-header h5{color:#10264d;font-size:21px;font-weight:600;margin:0}
.cms-editorial-admin .card-body{padding:20px}.cms-editorial-admin .form-label,.cms-editorial-admin .col-form-label{color:#183864;font-weight:500}.cms-editorial-admin .input-group-text{border-color:#d4e0f3;background:#f8fbff;color:#6987b6}
.cms-editorial-admin .form-floating-outline .form-control,.cms-editorial-admin .form-floating-outline .form-select{min-height:44px}.cms-editorial-admin .form-floating-outline>label{color:#6e83ae;background:transparent}.cms-editorial-admin .form-floating-outline>.form-control:focus~label,.cms-editorial-admin .form-floating-outline>.form-control:not(:placeholder-shown)~label,.cms-editorial-admin .form-floating-outline>.form-select~label{background:#fff}.cms-editorial-admin .form-floating-outline>.form-control:not(:focus)::placeholder{color:transparent}
.cms-editorial-admin .breadcrumb{padding:0;margin:0 0 14px;background:none;font-size:13px}.cms-editorial-admin .breadcrumb a{color:#6685b5}.cms-editorial-admin .breadcrumb-item.active{color:#183864}
.cms-editorial-admin .table-responsive{min-width:0;max-width:100%;overflow:auto}.cms-editorial-admin .table{color:#284674;--bs-table-bg:#fff;--bs-table-color:#284674;--bs-table-border-color:#e1eaf7;font-size:13px}
.cms-editorial-admin .table thead th{background:#f3f7fd;color:#647da6;border-color:#e1eaf7;font-size:11px;text-transform:uppercase;font-weight:600;padding:14px 16px;vertical-align:middle}
.cms-editorial-admin .table tbody td{padding:14px 16px;vertical-align:middle;border-color:#e6edf8}.cms-editorial-admin .table tbody tr:hover{--bs-table-bg:#f9fbff}.cms-editorial-admin .table td a:not(.btn){color:#205ba0}.cms-editorial-admin .table .btn-sm{padding:6px 10px;min-height:32px;font-size:12px;white-space:nowrap}
.cms-editorial-admin .cms-filter-panel{padding:16px 20px;display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap;border-bottom:1px solid #edf2fa}.cms-editorial-admin .cms-filter-panel label{font-size:13px;color:#6981aa;display:block;margin:0}.cms-editorial-admin .cms-filter-panel input{min-width:240px}.cms-editorial-admin .cms-filter-panel select{min-width:190px}
.cms-editorial-admin .cms-filter-panel label:first-child{flex:1;min-width:0}.cms-editorial-admin .cms-filter-panel label:first-child input{width:100%;min-width:0}
.cms-editorial-admin .cms-list-page .cms-index-card{border:0;border-radius:11px;box-shadow:0 4px 18px #35609907;overflow:hidden}
.cms-editorial-admin .cms-list-page .cms-index-card>.card-body{margin-top:0!important;padding:0}
.cms-editorial-admin .cms-list-page .cms-index-card .custom-card{border:0;box-shadow:none;border-radius:0}
.cms-editorial-admin .cms-list-search{width:100%;margin-bottom:16px}
.cms-editorial-admin .cms-list-search>.d-grid{display:flex!important;gap:8px!important;width:100%;margin-bottom:12px!important}
.cms-editorial-admin .cms-list-search .input-group{flex:1;min-width:0;max-width:none;width:auto}
.cms-editorial-admin .cms-list-search .input-group .form-control{flex:1;min-width:0;width:1%}
.cms-editorial-admin .cms-list-search>.d-grid>.btn{flex:0 0 auto;white-space:nowrap}
.cms-editorial-admin .cms-list-page .table{border:0!important}
/* Legacy table-bordered also draws borders on row groups and rows, not only cells. */
.cms-editorial-admin .cms-list-page .table>:not(caption),.cms-editorial-admin .cms-list-page .table>:not(caption)>tr{border:0!important}
.cms-editorial-admin .cms-list-page .table th,.cms-editorial-admin .cms-list-page .table td{border-left:0!important;border-right:0!important;border-color:#e6edf8!important;text-align:left}
.cms-editorial-admin .cms-list-page .table thead th{background:#f3f7fd!important;color:#647da6!important;white-space:nowrap}
.cms-editorial-admin .cms-list-page .table tbody td{border-bottom:1px solid #e6edf8!important}
.cms-editorial-admin .cms-list-page .table tbody th{padding:14px 16px;font-size:13px;font-weight:400;vertical-align:middle;border-bottom:1px solid #e6edf8!important}
.cms-editorial-admin .cms-list-page .table-responsive.text-nowrap td{white-space:normal;overflow-wrap:anywhere}
.cms-editorial-admin .cms-list-page .table-responsive .table:not(.cms-list-table){min-width:680px}
.cms-editorial-admin .cms-list-page .form-layout-footer{padding:16px 20px;margin:0!important}
.cms-editorial-admin .cms-list-page .cms-page-heading .btn{align-self:flex-start;white-space:nowrap}
.cms-editorial-admin .cms-list-table{width:100%;min-width:1120px;table-layout:fixed}.cms-editorial-admin .cms-list-table th:nth-child(1){width:33%}.cms-editorial-admin .cms-list-table th:nth-child(2){width:10%}.cms-editorial-admin .cms-list-table th:nth-child(3){width:10%}.cms-editorial-admin .cms-list-table th:nth-child(4){width:11%}.cms-editorial-admin .cms-list-table th:nth-child(5){width:15%}.cms-editorial-admin .cms-list-table th:nth-child(6){width:11%}.cms-editorial-admin .cms-list-table th:nth-child(7){width:10%}/* Bảng sự kiện có thêm cột "hiển thị trang chủ" (8 cột): chia lại độ rộng để cột thao tác không bị mất. */.cms-editorial-admin .cms-list-table.cms-list-table--home th:nth-child(1){width:24%}.cms-editorial-admin .cms-list-table.cms-list-table--home th:nth-child(2){width:9%}.cms-editorial-admin .cms-list-table.cms-list-table--home th:nth-child(3){width:9%}.cms-editorial-admin .cms-list-table.cms-list-table--home th:nth-child(4){width:9%}.cms-editorial-admin .cms-list-table.cms-list-table--home th:nth-child(5){width:10%}.cms-editorial-admin .cms-list-table.cms-list-table--home th:nth-child(6){width:13%}.cms-editorial-admin .cms-list-table.cms-list-table--home th:nth-child(7){width:11%}.cms-editorial-admin .cms-list-table.cms-list-table--home th:nth-child(8){width:15%}.cms-editorial-admin .cms-list-table td{overflow-wrap:anywhere}.cms-editorial-admin .cms-list-table td:last-child .btn{max-width:100%;white-space:normal;line-height:1.25}.cms-editorial-admin .cms-list-table .badge{white-space:normal;text-align:left;line-height:1.4}
.cms-editorial-admin .cms-state{display:inline-block;font-size:11px;font-weight:500;padding:5px 8px;border-radius:5px;background:#edf2f8;color:#607799;white-space:nowrap}.cms-editorial-admin .cms-state-published{background:#e6f7ed;color:#188458}.cms-editorial-admin .cms-state-pending_approval{background:#fff4db;color:#996617}.cms-editorial-admin .cms-state-approved{background:#e8f1ff;color:#226fc8}.cms-editorial-admin .cms-state-not_approved{background:#fff0ee;color:#b64e44}
.cms-editorial-admin .pagination .page-link{border-radius:6px;color:#326294;background:#f1f6fd}.cms-editorial-admin .pagination .active>.page-link{background:#0879ff!important;color:#fff!important}.cms-editorial-admin .nav-pills .nav-link.active{background:#e6f1ff!important;color:#0879ff!important;box-shadow:none}.cms-editorial-admin .select2-container--default .select2-selection{border-color:#d4e0f3;border-radius:8px}
.cms-editorial-admin .menu-sub .menu-item.active>.menu-link{color:#0879ff;background:#e6f1ff;border-radius:6px}.cms-editorial-admin .menu-sub .menu-item.open>.menu-sub{display:block}.cms-editorial-admin .cms-list-bottom{padding:16px 20px}
@media(max-width:767px){.cms-editorial-admin .card-body,.cms-editorial-admin .card-header,.cms-editorial-admin .cms-list-bottom{padding:14px}.cms-editorial-admin .cms-filter-panel{padding:14px}.cms-editorial-admin .cms-filter-panel label{width:100%}.cms-editorial-admin .cms-filter-panel input,.cms-editorial-admin .cms-filter-panel select{width:100%;min-width:0}.cms-editorial-admin .card-header h1,.cms-editorial-admin .card-header h2,.cms-editorial-admin .card-header h5{font-size:19px}}
@media(max-width:767px){.cms-editorial-admin .cms-filter-panel label:first-child{flex-basis:100%}.cms-editorial-admin .cms-list-page .cms-index-card>.card-body{padding:0}.cms-editorial-admin .cms-list-search>.d-grid{flex-wrap:wrap}.cms-editorial-admin .cms-list-search .input-group{flex-basis:100%}.cms-editorial-admin .cms-list-search>.d-grid>.btn{margin-left:auto}}
/* List components share the same spacing and table treatment as editorial/index. */
.cms-editorial-admin .cms-legacy-filter{display:block;margin:0;width:100%}
.cms-editorial-admin .cms-search-row{display:flex;align-items:flex-end;gap:14px;width:100%}
.cms-editorial-admin .cms-search-field{flex:1;min-width:0}
.cms-editorial-admin .cms-search-label{display:block;color:#6981aa;font-size:13px;margin-bottom:0;font-weight:400}
.cms-editorial-admin .cms-search-field .input-group{margin:0!important;width:100%;min-width:0}
.cms-editorial-admin .cms-search-field .input-group input{width:1%;flex:1;min-width:0}
.cms-editorial-admin .cms-search-row>.btn{flex-shrink:0;white-space:nowrap}
.cms-editorial-admin .cms-search-row>.cms-filter-field{flex:0 0 240px;min-width:0}
.cms-editorial-admin .cms-filter-field .form-floating{min-width:0!important}
.cms-editorial-admin .cms-filter-field .select2-container{width:100%!important}
.cms-editorial-admin .cms-filter-field .form-control,.cms-editorial-admin .cms-filter-field .form-select{width:100%;min-height:44px}
.cms-editorial-admin .cms-search-row{flex-wrap:wrap}
.cms-editorial-admin .cms-legacy-filter>.collapse{margin:14px 0 0!important}
.cms-editorial-admin .cms-legacy-filter .row .form-control,.cms-editorial-admin .cms-legacy-filter .row .form-select{min-width:0;width:100%}
.cms-editorial-admin .cms-index-card .custom-card{margin:0!important}
.cms-editorial-admin .cms-index-card .table-responsive{margin-top:0!important}
.cms-editorial-admin .cms-list-page .table{--bs-table-striped-bg:transparent;--bs-table-accent-bg:transparent}
.cms-editorial-admin .cms-list-page .table tbody td,.cms-editorial-admin .cms-list-page .table tbody th{box-shadow:none!important;background:#fff}
.cms-editorial-admin .cms-list-page .table td .h6{font:inherit;margin:0;color:inherit}
.cms-editorial-admin .cms-list-page .table tbody td[colspan]{text-align:center!important;color:#6e83ae;padding:24px 16px}
.cms-editorial-admin .cms-list-page .table td .d-flex>.dropdown-item{display:inline-flex;align-items:center;justify-content:center;width:32px;min-height:32px;padding:5px;border:1px solid #d4e0f3;border-radius:7px;background:#f5faff}
.cms-editorial-admin .cms-list-page .table td .d-flex>.dropdown-item i{margin:0!important;font-size:18px}
.cms-editorial-admin .cms-pagination{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;width:100%}
.cms-editorial-admin .cms-pagination-summary{margin:0;color:#6e83ae;font-size:12px}
.cms-editorial-admin .cms-pagination .pagination{gap:5px;max-width:100%;overflow:auto;padding:2px 0}
.cms-editorial-admin .cms-pagination .page-link{display:grid;place-items:center;min-width:32px;height:32px;border:0;box-shadow:none!important;font-size:12px}
@media(max-width:767px){.cms-editorial-admin .cms-search-row{flex-wrap:wrap}.cms-editorial-admin .cms-search-field{flex-basis:100%}.cms-editorial-admin .cms-search-row>.btn{margin-left:auto}.cms-editorial-admin .cms-list-page .form-layout-footer{padding:14px}.cms-editorial-admin .cms-legacy-filter{padding:14px}}
/* Sidebar: hover/active highlights are inset (shorter than the row) so neighbouring items, and an active parent
   with its active child, never touch. Row rhythm is unchanged: 36px highlight + 3px padding above and below = 42px,
   so two neighbouring highlights are 6px apart (padding, not margins, so the gaps do not collapse). */
.cms-editorial-admin .cms-admin-links>li{padding:3px 0}
.cms-editorial-admin .cms-admin-links .cms-nav-link{min-height:36px;margin:0;padding-block:7px}
.cms-editorial-admin .menu-sub{margin-top:3px}
.cms-editorial-admin .menu-sub .menu-item{padding:2px 0}
.cms-editorial-admin .menu-sub .menu-link{margin:0;padding:6px 10px;border-radius:6px}
.cms-editorial-admin .menu-sub .menu-link:hover{background:#eef5ff;color:#0879ff}
/* List heading actions (e.g. news: Xuất danh sách · Xem trước · Tạo tin tức) on one row, right-aligned. */
.cms-editorial-admin .cms-page-actions{display:flex;flex-wrap:wrap;justify-content:flex-end;align-items:center;gap:10px;flex-shrink:0}
.cms-editorial-admin .cms-page-actions .btn{display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
.cms-editorial-admin .cms-page-actions .btn>i{font-size:17px;line-height:1}
@media(max-width:767px){.cms-editorial-admin .cms-page-heading:has(.cms-page-actions){flex-direction:column}.cms-editorial-admin .cms-page-actions{justify-content:flex-start}}
/* Phase 16: English labels are longer than Vietnamese ones - let headings, action rows, card headers and filters wrap instead of overflowing. */
.cms-editorial-admin .cms-page-heading{flex-wrap:wrap}
.cms-editorial-admin .cms-page-heading>div:first-child{flex:1 1 300px;min-width:0}
.cms-editorial-admin .cms-page-actions{flex-shrink:1;min-width:0;max-width:100%}
.cms-editorial-admin .card-header{flex-wrap:wrap;gap:8px}
.cms-editorial-admin .card-header>*{min-width:0}
.cms-editorial-admin .cms-form-actions .btn,.cms-editorial-admin .card-header .btn{max-width:100%}
.cms-editorial-admin .cms-filter-panel>*,.cms-editorial-admin .cms-search-row>*{min-width:0}
.cms-editorial-admin .table-responsive{overflow-x:auto}
.cms-editorial-admin .table th{overflow-wrap:break-word}
.cms-editorial-admin .table td .dropdown-menu,.cms-editorial-admin .table td .btn{max-width:100%}
</style>
