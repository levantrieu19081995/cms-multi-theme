CKEDITOR.plugins.add( 'replaceTagNameByBsquochoai', {
    init: function( editor ) {
       editor.addCommand( 'replaceTagName', new CKEDITOR.dialogCommand( 'replaceTagNameDialog' ) );

       editor.ui.addButton( 'replaceTagName', {
		    label: 'Change this Html Tag Name',
		    command: 'replaceTagName',
		    toolbar: 'insert',
			icon: this.path + 'browser.png'
		});
       CKEDITOR.dialog.add( 'replaceTagNameDialog', this.path + 'mydialog.js' );
    }
});
