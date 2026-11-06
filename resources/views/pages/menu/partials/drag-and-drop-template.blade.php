<div style="display: none">
    <template id="menu-item-template">
        <li class="dd-item" data-id="#id" data-title="#title" data-url="#url" data-type="#type" data-ref_id="#ref_id"
            data-target="#target" data-icon="%icon%" data-feature_key="%fk%" data-feature_target="%ft%" data-feature_status="%fs%" data-title_en="%en_title%" data-url_en="%en_url%">
            <i class="ri-delete-bin-2-fill" style="cursor: pointer" title="Xóa" action="delete"></i>
            <i class="ri-edit-2-fill" style="cursor: pointer;" title="Thay đổi" action="edit"></i>
            <div class="dd-handle">
                <div class="menu-item-title drag-handle" style="white-space: nowrap; text-overflow: ellipsis; overflow: hidden;">
                    <i class="#menuIcon me-1" aria-hidden="true"></i>#title
                </div>
                <div style="font-weight: normal">
                    <small>
                        Loại: #typeLabel #iconTarget

                    </small>
                </div>
            </div>
        </li>
    </template>
    <template id="multi-level-menu-item-template">
        <li class="dd-item" data-id="#id" data-title="#title" data-url="#url" data-type="#type" data-ref_id="#ref_id" data-target="#target" data-icon="%icon%" data-feature_key="%fk%" data-feature_target="%ft%" data-feature_status="%fs%" data-title_en="%en_title%" data-url_en="%en_url%">
            <i class="ri-edit-2-fill" style="cursor: pointer" title="Thay đổi" action="edit"></i>
            <div class="dd-handle">
                <div class="menu-item-title drag-handle" style="white-space: nowrap; text-overflow: ellipsis; overflow: hidden;">
                    <i class="#menuIcon me-1" aria-hidden="true"></i>#title
                </div>
                <div style="font-weight: normal">
                    <small>
                        Loại: #typeLabel #iconTarget
                    </small>
                </div>
            </div>
            <ol class="dd-list">#childs</ol>
        </li>
    </template>
    <template id="menu-builder-template">
        <ol class="dd-list">#childs</ol>
    </template>
</div>
