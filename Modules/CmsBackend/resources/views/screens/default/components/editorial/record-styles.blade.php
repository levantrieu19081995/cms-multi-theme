<style>
.cms-editorial-admin .cms-record-page{--cms-control-bg:#edeff6}
.cms-editorial-admin .cms-record-heading{align-items:center;flex-wrap:wrap;margin-bottom:16px}
.cms-editorial-admin .cms-record-heading>div:first-child{flex:1 1 300px;min-width:0}
.cms-editorial-admin .cms-record-heading .cms-form-actions{padding:0;margin-left:auto;justify-content:flex-end}
.cms-editorial-admin .cms-record-heading .cms-form-actions .cms-cancel{margin-left:0}
.cms-editorial-admin .cms-record-heading .btn{white-space:nowrap}
.cms-editorial-admin .cms-record-page>.card:has(form){background:none;border:0;box-shadow:none}
.cms-editorial-admin .cms-record-page>.card:has(form)>.card-body{padding:0}
.cms-editorial-admin .cms-record-page .card{border:0;border-radius:11px;box-shadow:none;background:#fff}
.cms-editorial-admin .cms-record-page .card-body{padding:14px 20px}
.cms-editorial-admin .cms-record-page .card-header{border:0;padding:12px 20px;background:#fff}
.cms-editorial-admin .cms-record-page .card-header h4,.cms-editorial-admin .cms-record-page .card-header h5{color:#12254d!important;font-size:14px!important;text-transform:none!important}
.cms-editorial-admin .cms-record-page .card:has(>.card-body>.cms-record-field-card){background:none}
.cms-editorial-admin .cms-record-page .card:has(>.card-body>.cms-record-field-card)>.card-body{padding:0}
.cms-editorial-admin .cms-record-page>.row>.col-md-6:only-child{width:100%}
.cms-editorial-admin .cms-record-page .cms-record-field-card{background:#fff;border-radius:11px;padding:12px 20px;margin:0 0 12px!important;min-width:0;flex-wrap:wrap}
.cms-editorial-admin .cms-record-page .cms-record-field{position:relative;margin:0!important;display:flex;flex-direction:column;gap:7px;width:100%;min-width:0}
.cms-editorial-admin .cms-record-page .cms-record-field-card.cms-record-field{margin:0 0 12px!important}
.cms-editorial-admin .cms-record-page .card-body>hr{display:none}
.cms-editorial-admin .cms-record-page .cms-record-field-card .cms-record-field{padding:0;background:none}
.cms-editorial-admin .cms-record-page .cms-record-field>label{position:static!important;inset:auto!important;transform:none!important;max-width:100%;height:auto;width:auto;pointer-events:auto;padding:0!important;background:none!important;margin:0;color:#12254d;font-size:13px;font-weight:600;line-height:1.4}
.cms-editorial-admin .cms-record-page .cms-record-field>label::after{display:none}
.cms-editorial-admin .cms-record-page .cms-record-field>.form-control,.cms-editorial-admin .cms-record-page .cms-record-field>.form-select{padding:9px 12px!important;min-height:36px;height:auto}
.cms-editorial-admin .cms-record-page .cms-record-field>textarea.form-control{min-height:80px}
.cms-editorial-admin .cms-record-page .form-control,.cms-editorial-admin .cms-record-page .form-select,.cms-editorial-admin .cms-record-page .input-group-text,.cms-editorial-admin .cms-record-page .select2-selection{border:0!important;background:#edeff6;box-shadow:none;border-radius:7px}
.cms-editorial-admin .cms-record-page .form-control:disabled,.cms-editorial-admin .cms-record-page fieldset:disabled .form-control{background:#f0f2f7;color:#63799d}
.cms-editorial-admin .cms-record-page .form-control:focus,.cms-editorial-admin .cms-record-page .form-select:focus{background:#e6ebf6;box-shadow:0 0 0 2px #0879ff26!important}
.cms-editorial-admin .cms-record-page .form-control.is-invalid{background:#fff0f2;box-shadow:0 0 0 2px #e34e652b!important}
.cms-editorial-admin .cms-record-page input[type="file"]::file-selector-button{border:0;border-radius:5px;background:#e1edff;color:#0864d0;padding:5px 10px;margin-right:8px}
.cms-editorial-admin .cms-record-page .select2-container{width:100%!important;max-width:100%}
.cms-editorial-admin .cms-record-page .select2-selection__rendered{padding:6px 12px!important;color:#23416c}
.cms-editorial-admin .cms-record-page .ck.ck-toolbar,.cms-editorial-admin .cms-record-page .ck-editor__main>.ck-editor__editable,.cms-editorial-admin .cms-record-page .ck-source-editing-area textarea{border:0!important;background:#edeff6!important}
.cms-editorial-admin .cms-record-page .ck-editor__main>.ck-editor__editable.ck-focused{box-shadow:0 0 0 2px #0879ff26!important}
.cms-editorial-admin .cms-record-page .btn{border:0!important}
.cms-editorial-admin .cms-record-page .btn-outline-secondary,.cms-editorial-admin .cms-record-page .btn-light{background:#e6e9f2;color:#214679}
.cms-editorial-admin .cms-record-page .btn-outline-primary{background:#e1edff;color:#0864d0}
.cms-editorial-admin .cms-record-page .btn-outline-danger{background:#fde6e9;color:#c72c43}
.cms-editorial-admin .cms-record-page .btn:focus-visible{outline:2px solid #7a9bd1;outline-offset:3px}
.cms-editorial-admin .cms-record-page .accordion-item{border:0;border-radius:11px!important;background:#fff;margin-bottom:12px;overflow:hidden}
.cms-editorial-admin .cms-record-page .accordion-button{background:#fff!important;color:#12254d!important;box-shadow:none;font-size:13px;font-weight:600;padding:14px 20px}
.cms-editorial-admin .cms-record-page .accordion-body{padding:0 20px 14px}
.cms-editorial-admin .cms-record-page .cms-record-grid{display:grid;grid-template-columns:minmax(0,1.86fr) minmax(0,1fr);gap:22px;margin:0}
.cms-editorial-admin .cms-record-page .cms-record-grid>.col-lg-8,.cms-editorial-admin .cms-record-page .cms-record-grid>.col-lg-4{width:auto;padding:0;min-width:0}
.cms-editorial-admin .cms-record-page .cms-record-grid>.col-12:not(.col-lg-8):not(.col-lg-4){grid-column:1/-1}
.cms-editorial-admin .cms-record-page .cms-record-editor-card>h5{font-size:13px;font-weight:600;margin:0 0 7px}
.cms-editorial-admin .cms-record-page .cms-record-field-card .input-group-text{margin-top:25px}
.cms-editorial-admin .cms-record-page .img-avt img{border:0;max-width:100%;height:auto;object-fit:contain}
.cms-editorial-admin .cms-detail-value{margin:0;background:#edeff6;border-radius:7px;padding:9px 12px;overflow-wrap:anywhere}
.cms-editorial-admin .cms-survey-answers{padding-left:20px;margin:0}
.cms-editorial-admin .cms-survey-answers li{padding:10px 0;color:#203653}
.cms-editorial-admin .cms-survey-answers li+li{border-top:1px solid #edf2fa}
.cms-editorial-admin .cms-comments-page .modal-content{border:0;border-radius:11px;background:#f5f9ff}
.cms-editorial-admin .cms-comments-page .modal-header,.cms-editorial-admin .cms-comments-page .modal-footer{border:0}
.cms-editorial-admin .cms-comments-page .modal .form-control,.cms-editorial-admin .cms-comments-page .modal .form-select{border:0;background:#edeff6;box-shadow:none;border-radius:7px}
.cms-editorial-admin .cms-comments-page .modal .btn{border:0}
.cms-editorial-admin .cms-comments-page .modal .btn-secondary{background:#e6e9f2;color:#214679}
@media(max-width:767px){.cms-editorial-admin .cms-record-heading .cms-form-actions{width:100%;margin-left:0}.cms-editorial-admin .cms-record-heading .btn{flex:1 1 auto}.cms-editorial-admin .cms-record-page .cms-record-grid{grid-template-columns:minmax(0,1fr);gap:14px}.cms-editorial-admin .cms-record-page .cms-record-field-card,.cms-editorial-admin .cms-record-page .card-body{padding:12px 14px}}
</style>
