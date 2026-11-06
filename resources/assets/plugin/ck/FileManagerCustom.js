import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
// import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';

export default class FileManagerCustom extends Plugin {
    init() {
        const editor = this.editor;

        editor.ui.componentFactory.add('fileManager', locale => {
            const view = new ButtonView(locale); // Sửa lỗi ButtonView tại đây

            view.set({
                label: 'File Manager',
                icon: 'insert', // Thay icon phù hợp hoặc có thể không dùng icon
                tooltip: true
            });

            // Xử lý khi nút được nhấn
            view.on('execute', () => {
                const lfmUrl = '/laravel-filemanager?type=Images';
                const windowManager = window.open(lfmUrl, 'FileManager', 'width=800,height=600');
                
                window.SetUrl = function (items) {
                    const filePath = items.map(item => item.url).join(',');

                    editor.model.change(writer => {
                        const imageElement = writer.createElement('imageBlock', {
                            src: filePath
                        });

                        editor.model.insertContent(imageElement, editor.model.document.selection);
                    });
                };
            });

            return view;
        });
    }
}
