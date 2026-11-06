CKEDITOR.plugins.add( 'zsmanager', {
    init: function( editor ) {
        editor.config.filebrowserBrowseUrl = './ckeditor/plugins/zsmanager/index.php';
         editor.config.filebrowserImageBrowseUrl = './ckeditor/plugins/zsmanager/index.php';
    }
});
