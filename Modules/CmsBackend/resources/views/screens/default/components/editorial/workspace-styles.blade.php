<style>
/* Wide user/role/settings workspaces. Scoped to authenticated CMS requests. */
.cms-editorial-admin .cms-workspace-page{min-width:0}
.cms-editorial-admin .cms-workspace-page>.row{margin:0;min-width:0}
.cms-editorial-admin .cms-workspace-page .cms-workspace-column{flex:0 0 100%;width:100%;max-width:none;padding:0;min-width:0}
.cms-editorial-admin .cms-workspace-page .cms-workspace-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin:0 0 14px}
.cms-editorial-admin .cms-workspace-page .cms-workspace-heading h1{margin:0;font-size:27px;line-height:1.15;color:#10264d;font-weight:700;text-transform:none}
.cms-editorial-admin .cms-workspace-page .cms-workspace-heading p{margin:5px 0 0;color:#6e83ae;font-size:13px}
.cms-editorial-admin .cms-workspace-page .cms-workspace-actions{display:flex;align-items:center;justify-content:flex-end;gap:9px;flex-wrap:wrap}
.cms-editorial-admin .cms-workspace-page .cms-workspace-actions .btn,.cms-editorial-admin .cms-workspace-page .cms-setting-actions .btn{border:0!important;white-space:nowrap}
.cms-editorial-admin .cms-workspace-page .cms-workspace-actions .btn-outline-secondary,.cms-editorial-admin .cms-workspace-page .cms-setting-actions .btn-outline-secondary{background:#e6e9f2;color:#214679}
.cms-editorial-admin .cms-workspace-page .cms-workspace-form>.card{border:0;background:transparent;box-shadow:none;margin:0!important;overflow:visible}
.cms-editorial-admin .cms-workspace-page .cms-workspace-form>.card>.card-body{padding:0;background:transparent}
.cms-editorial-admin .cms-workspace-page .cms-workspace-sections{display:grid;gap:16px;align-items:start}
.cms-editorial-admin .cms-workspace-page .cms-workspace-section{min-width:0;background:#fff;border-radius:11px;box-shadow:0 4px 18px #35609907;padding:18px 20px}
.cms-editorial-admin .cms-workspace-page .cms-workspace-section-title{margin:0 0 16px!important;font-size:16px;color:#10264d;font-weight:700}
.cms-editorial-admin .cms-workspace-page .form-control,.cms-editorial-admin .cms-workspace-page .form-select,.cms-editorial-admin .cms-workspace-page .input-group-text,.cms-editorial-admin .cms-workspace-page .select2-container--default .select2-selection{border:0!important;background-color:#edeff6!important;background-image:linear-gradient(#edeff6,#edeff6)!important;box-shadow:inset 0 0 0 1000px #edeff6!important}
.cms-editorial-admin .cms-workspace-page .form-control:focus,.cms-editorial-admin .cms-workspace-page .form-select:focus{background-color:#e6ebf6!important;background-image:linear-gradient(#e6ebf6,#e6ebf6)!important;box-shadow:inset 0 0 0 1000px #e6ebf6,0 0 0 2px #0879ff26!important}
.cms-editorial-admin .cms-workspace-page .form-control.is-invalid,.cms-editorial-admin .cms-workspace-page .form-select.is-invalid{background-color:#fff0f2!important;background-image:linear-gradient(#fff0f2,#fff0f2)!important;box-shadow:inset 0 0 0 1000px #fff0f2,0 0 0 2px #e34e652b!important}
.cms-editorial-admin .cms-workspace-user .form-floating-outline>label,.cms-editorial-admin .cms-workspace-role .form-floating-outline>label{background:transparent!important}
.cms-editorial-admin .cms-workspace-user .form-floating-outline .form-control::placeholder,.cms-editorial-admin .cms-workspace-role .form-floating-outline .form-control::placeholder{color:transparent!important;opacity:0}
.cms-editorial-admin .cms-workspace-user .form-floating-outline .form-control:focus::placeholder,.cms-editorial-admin .cms-workspace-role .form-floating-outline .form-control:focus::placeholder{color:#7b91b8!important;opacity:1}
.cms-editorial-admin .cms-workspace-user .cms-workspace-section-1>.form-floating:not(.form-password-toggle),.cms-editorial-admin .cms-workspace-role .cms-workspace-section-1>.form-floating{background:#edeff6;border-radius:8px}
.cms-editorial-admin .cms-workspace-user .form-password-toggle>.input-group{display:flex;min-height:44px;border-radius:8px;background:#edeff6;box-shadow:inset 0 0 0 1000px #edeff6;overflow:hidden}
.cms-editorial-admin .cms-workspace-user .form-password-toggle>.input-group>.form-floating{flex:1 1 auto;width:1%;min-width:0;background:#edeff6}
.cms-editorial-admin .cms-workspace-user .form-password-toggle>.input-group .form-control{height:44px;min-height:44px}
.cms-editorial-admin .cms-workspace-user .form-password-toggle>.input-group .input-group-text{min-width:44px;justify-content:center}

.cms-editorial-admin .cms-workspace-role .cms-workspace-sections{grid-template-columns:minmax(0,1fr)}
.cms-editorial-admin .cms-workspace-role .cms-permission-toolbar{display:grid;grid-template-columns:minmax(240px,1fr) minmax(210px,.65fr) auto;align-items:center;gap:10px;margin-bottom:12px;padding:12px;border:1px solid #e0e7f2;border-radius:10px;background:#f8faff}
.cms-editorial-admin .cms-workspace-role .cms-permission-search{position:relative;min-width:0}
.cms-editorial-admin .cms-workspace-role .cms-permission-search>i{position:absolute;z-index:2;top:50%;left:13px;transform:translateY(-50%);color:#7184a6;font-size:18px;pointer-events:none}
.cms-editorial-admin .cms-workspace-role .cms-permission-search>.form-control{height:42px;padding-left:40px!important}
.cms-editorial-admin .cms-workspace-role #permission-group-filter{height:42px}
.cms-editorial-admin .cms-workspace-role .cms-permission-summary{justify-self:end;white-space:nowrap;padding:7px 11px;border-radius:999px;background:#e7f0ff;color:#145ea8;font-size:12px;font-weight:700}
.cms-editorial-admin .cms-workspace-role .cms-permission-actions{grid-column:1/-1;display:flex;align-items:center;gap:7px;flex-wrap:wrap;padding-top:2px}
.cms-editorial-admin .cms-workspace-role .cms-permission-actions .btn{min-height:34px;border-radius:7px;box-shadow:none}
.cms-editorial-admin .cms-workspace-role .cms-permission-tree-status{display:none;margin:0 0 10px;padding:10px 12px;border-radius:8px;background:#edf3fb;color:#607494;font-size:13px}
.cms-editorial-admin .cms-workspace-role .cms-permission-tree-status[data-state="error"]{background:#fff0f2;color:#bd2d45}
.cms-editorial-admin .cms-workspace-role .cms-permission-tree-status[data-state="empty"]{display:block!important;background:#fff8e7;color:#80601a}
.cms-editorial-admin .cms-workspace-role #permission-groups{display:grid;gap:12px;min-width:0}
.cms-editorial-admin .cms-workspace-role .cms-role-permission-group{min-width:0;overflow:hidden;border:1px solid #e0e6ef;border-radius:10px;background:#fff;box-shadow:0 5px 16px #244f7d0d}
.cms-editorial-admin .cms-workspace-role .cms-role-permission-group-header{display:flex;align-items:center;justify-content:space-between;gap:14px;min-height:48px;padding:8px 16px;background:#fff}
.cms-editorial-admin .cms-workspace-role .cms-permission-group-collapse{display:flex;align-items:center;gap:7px;min-width:0;padding:6px 0;border:0;background:transparent;color:#142b50;font-size:14px;font-weight:700;text-align:left;cursor:pointer}
.cms-editorial-admin .cms-workspace-role .cms-permission-group-collapse>i{font-size:19px;transition:transform .18s ease}
.cms-editorial-admin .cms-workspace-role .cms-permission-group-collapse>span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cms-editorial-admin .cms-workspace-role .cms-role-permission-group-tools{display:flex;align-items:center;gap:8px;flex:0 0 auto}
.cms-editorial-admin .cms-workspace-role .cms-permission-group-count{display:inline-grid;place-items:center;min-width:22px;height:22px;padding:0 6px;border-radius:4px;background:#eef1f6;color:#62728e;font-size:11px;font-weight:700}
.cms-editorial-admin .cms-workspace-role .cms-role-permission-group-tools .btn{min-height:32px;border-radius:6px;font-size:12px}
.cms-editorial-admin .cms-workspace-role .cms-role-permission-table-wrap{border-top:1px solid #e8edf4}
.cms-editorial-admin .cms-workspace-role .cms-role-permission-table{min-width:720px;table-layout:fixed;color:#526787;font-size:13px}
.cms-editorial-admin .cms-workspace-role .cms-role-permission-table th{padding:11px 16px;background:#f3f4f7;color:#243858;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.02em;border:0}
.cms-editorial-admin .cms-workspace-role .cms-role-permission-table th:nth-child(1){width:70px}
.cms-editorial-admin .cms-workspace-role .cms-role-permission-table th:nth-child(2){width:42%}
.cms-editorial-admin .cms-workspace-role .cms-role-permission-table th:nth-child(3){width:36%}
.cms-editorial-admin .cms-workspace-role .cms-role-permission-table td{padding:9px 16px;vertical-align:middle;border-color:#e2e7ee;background:#fff}
.cms-editorial-admin .cms-workspace-role .cms-role-permission-row:hover td{background:#f9fbfe}
.cms-editorial-admin .cms-workspace-role .cms-permission-toggle-cell .form-check-input{float:none;width:27px;height:16px;margin:0;cursor:pointer}
.cms-editorial-admin .cms-workspace-role .cms-permission-code code{color:#ef3863;font-size:11px;white-space:normal;overflow-wrap:anywhere}
.cms-editorial-admin .cms-workspace-role .cms-permission-parent-badge{display:inline-block;margin-left:8px;padding:3px 7px;border-radius:4px;background:#dcebf8;color:#25618f;font-size:10px;font-weight:700;white-space:nowrap}
.cms-editorial-admin .cms-workspace-role .cms-permission-name{color:#526787}
.cms-editorial-admin .cms-workspace-role .cms-permission-description{color:#98a5b9}
.cms-editorial-admin .cms-workspace-role .cms-role-permission-group.is-collapsed .cms-role-permission-table-wrap{display:none}
.cms-editorial-admin .cms-workspace-role .cms-role-permission-group.is-collapsed .cms-permission-group-collapse>i{transform:rotate(-90deg)}
.cms-editorial-admin .cms-workspace-role .cms-permission-group-filtered,.cms-editorial-admin .cms-workspace-role .cms-permission-row-filtered{display:none!important}

.cms-editorial-admin .cms-workspace-user .cms-workspace-sections{grid-template-columns:minmax(300px,.9fr) minmax(270px,.65fr) minmax(340px,1.15fr)}
.cms-editorial-admin .cms-workspace-user .cms-workspace-section-2>.form-floating:last-of-type{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 14px}
.cms-editorial-admin .cms-workspace-user .cms-workspace-section-2>.form-floating:last-of-type>small{grid-column:1/-1}
.cms-editorial-admin .cms-workspace-user .cms-workspace-section-2 .form-check{margin-top:11px!important;min-width:0}
.cms-editorial-admin .cms-workspace-user .parent-checkbox{max-height:560px!important;overflow:auto!important;border-radius:9px;background:#edeff6;padding:12px 14px}
.cms-editorial-admin .cms-workspace-user #checkAllBtn{border:0;background:#e1edff;color:#0864d0}

.cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"]>.row>.cms-workspace-column>.card{margin:0;border:0;border-radius:11px;box-shadow:0 4px 18px #35609907;overflow:hidden}
.cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"] .card-body{margin-top:0!important;padding:18px 20px}
.cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"] .cms-settings-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 16px}
.cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"] .cms-setting-field{display:block;margin:0!important;padding:0;border-radius:0;background:transparent;min-width:0}
.cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"] .cms-setting-field>label{display:block;width:100%;max-width:none;padding:0 0 7px;font-size:13px;line-height:1.35}
.cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"] .cms-setting-field>div[class*="col-"]{width:100%;max-width:none;padding:0}
.cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"] .cms-setting-wide,.cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"] .cms-setting-actions{grid-column:1/-1}
.cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"] .cms-setting-actions{background:transparent;padding:3px 0 0}
.cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"] .cms-setting-actions>div{margin:0!important;padding:0;width:100%;max-width:none}
.cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"] textarea.form-control{min-height:160px}
/* Section headings and wide blocks inside the two-column settings grid span both columns. */
.cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"] .cms-settings-form>:not(.row){grid-column:1/-1}
.cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"] .cms-setting-heading{margin:10px 0 -6px;padding-top:14px;border-top:1px solid #e4ebf3}
/* A heading that opens the form (first block after the hidden _token/_method inputs) needs no separator line. */
.cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"] .cms-settings-form>div.cms-setting-heading:first-of-type{margin-top:0;padding-top:0;border-top:0}
.cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"] .cms-setting-heading h6{margin:0 0 4px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.03em;color:#253858}
.cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"] .cms-setting-heading p{margin:0;font-size:12px;line-height:1.5;color:#7a8699}
.cms-editorial-admin .cms-featured-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:8px 16px}
.cms-editorial-admin .cms-featured-item{display:grid;grid-template-columns:64px minmax(0,1fr);align-items:center;gap:10px;margin:0;font-size:13px;font-weight:500;cursor:pointer}
.cms-editorial-admin .cms-featured-item>span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cms-editorial-admin .cms-featured-columns{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}
.cms-editorial-admin .cms-featured-column>h6{margin:0 0 8px;font-size:13px;font-weight:700}
.cms-editorial-admin .cms-featured-list{list-style:none;margin:0;padding:8px;min-height:64px;border:1px dashed #cfd9e8;border-radius:10px;background:#fafcff;display:grid;gap:6px;align-content:start}
.cms-editorial-admin .cms-featured-list[data-featured-list="visible"]{counter-reset:featured-pos}
.cms-editorial-admin .cms-featured-row{display:grid;grid-template-columns:auto 28px minmax(0,1fr) auto;align-items:center;gap:8px;padding:6px 8px;border:1px solid #e1e8f3;border-radius:8px;background:#fff;font-size:13px;font-weight:500}
.cms-editorial-admin .cms-featured-row.sortable-ghost{opacity:.45}
.cms-editorial-admin .cms-featured-handle{cursor:grab;color:#8a97ab;display:grid;place-items:center;min-width:24px;min-height:32px}
.cms-editorial-admin .cms-featured-pos{font-weight:700;color:#4b5a73;text-align:center}
.cms-editorial-admin [data-featured-list="visible"] .cms-featured-pos::before{counter-increment:featured-pos;content:counter(featured-pos)}
.cms-editorial-admin [data-featured-list="hidden"] .cms-featured-pos::before{content:"–"}
.cms-editorial-admin .cms-featured-name{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.cms-editorial-admin .cms-featured-actions{display:flex;gap:4px}
.cms-editorial-admin .cms-featured-actions button{min-width:32px;min-height:32px;padding:0 6px;border:1px solid #d6dfec;border-radius:6px;background:#fff;color:#33415c;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:4px;font-size:12px}
.cms-editorial-admin .cms-featured-actions button:hover{background:#eef3fb}
.cms-editorial-admin [data-featured-list="hidden"] [data-act="up"],.cms-editorial-admin [data-featured-list="hidden"] [data-act="down"]{display:none}
.cms-editorial-admin .cms-featured-empty{margin:0;padding:6px 4px;font-size:12px;color:#7b879a}
.cms-editorial-admin .cms-featured-item>input{text-align:center}
/* "Cài đặt chung" (CMS view only): settings card full width, "Trung tâm điều khiển" below it. */
.cms-editorial-admin .cms-workspace-settings-general>.row{display:grid;grid-template-columns:minmax(0,1fr);gap:16px;align-items:start}
.cms-editorial-admin .cms-workspace-settings-general>.row>.cms-workspace-column{width:auto}
.cms-editorial-admin .cms-workspace-settings-general .cms-control-center .card-body{display:flex;align-items:center;justify-content:space-between;gap:24px}
.cms-editorial-admin .cms-workspace-settings-general .cms-control-note{flex:1;max-width:760px;margin:0;font-size:13px;line-height:1.55;color:#6b778c}
.cms-editorial-admin .cms-workspace-settings-general .cms-control-center .btn{flex:0 0 auto}
@media (max-width:767px){.cms-editorial-admin .cms-workspace-settings-general .cms-control-center .card-body{flex-direction:column;align-items:stretch}}
.cms-editorial-admin .cms-workspace-settings-general .cms-logo-preview{display:grid;place-items:center;flex:0 0 auto;width:96px;height:96px;padding:6px;border-radius:10px;background:#f3f5fb;overflow:hidden}
.cms-editorial-admin .cms-workspace-settings-general .cms-logo-preview:empty{display:none}
.cms-editorial-admin .cms-workspace-settings-general .cms-logo-preview img{display:block;max-width:100%;max-height:100%;width:auto;height:auto!important;object-fit:contain}

@media(max-width:1399px){
    .cms-editorial-admin .cms-workspace-user .cms-workspace-sections{grid-template-columns:repeat(2,minmax(0,1fr))}
    .cms-editorial-admin .cms-workspace-user .cms-workspace-section-3{grid-column:1/-1}
}
@media(max-width:991px){
    .cms-editorial-admin .cms-workspace-role .cms-workspace-sections,.cms-editorial-admin .cms-workspace-user .cms-workspace-sections{grid-template-columns:minmax(0,1fr)}
    .cms-editorial-admin .cms-workspace-role .cms-permission-toolbar{grid-template-columns:minmax(0,1fr) minmax(210px,.7fr)}
    .cms-editorial-admin .cms-workspace-role .cms-permission-summary{grid-column:1/-1;grid-row:2;justify-self:start}
    .cms-editorial-admin .cms-workspace-role .cms-permission-actions{grid-row:3}
    .cms-editorial-admin .cms-workspace-user .cms-workspace-section-3{grid-column:auto}
    .cms-editorial-admin .cms-workspace-settings-general>.row{grid-template-columns:minmax(0,1fr)}
    .cms-editorial-admin .cms-workspace-settings-general>.row>.cms-workspace-column:nth-child(2) .card{position:static}
}
@media(max-width:767px){
    .cms-editorial-admin .cms-workspace-page .cms-workspace-heading{display:block}
    .cms-editorial-admin .cms-workspace-page .cms-workspace-actions{justify-content:flex-start;margin-top:12px}
    .cms-editorial-admin .cms-workspace-page .cms-workspace-actions .btn{flex:1 1 auto}
    .cms-editorial-admin .cms-workspace-page .cms-workspace-section{padding:14px}
    .cms-editorial-admin .cms-workspace-role .cms-permission-toolbar{grid-template-columns:minmax(0,1fr)}
    .cms-editorial-admin .cms-workspace-role .cms-permission-summary,.cms-editorial-admin .cms-workspace-role .cms-permission-actions{grid-column:1;grid-row:auto}
    .cms-editorial-admin .cms-workspace-role .cms-permission-actions .btn{flex:1 1 calc(50% - 7px)}
    .cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"] .cms-settings-form{grid-template-columns:minmax(0,1fr)}
    .cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"] .cms-setting-wide,.cms-editorial-admin .cms-workspace-page[class*="cms-workspace-settings-"] .cms-setting-actions{grid-column:auto}
    .cms-editorial-admin .cms-workspace-role .cms-role-permission-group-header{align-items:flex-start;padding:9px 12px}
    .cms-editorial-admin .cms-workspace-role .cms-role-permission-group-tools{gap:5px}
    .cms-editorial-admin .cms-workspace-user .cms-workspace-section-2>.form-floating:last-of-type{grid-template-columns:minmax(0,1fr)}
}
</style>
