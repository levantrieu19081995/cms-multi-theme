CKEDITOR.dialog.add( 'replaceTagNameDialog', function( editor ) {
    return {
        title: 'Change current elements tagName properties',
        minWidth: 400,
        minHeight: 200,

        contents: [
		    {
		        id: 'tab-basic',
		        label: 'Basic Settings',
		        elements: [
		            {
		                type: 'text',
		                id: 'newTagName',
		                label: 'To this tagName',
		                validate: CKEDITOR.dialog.validate.notEmpty( "To this tagName cannot be empty." )
		            }
		        ]
		    },
		    {
		        id: 'tab-adv',
		        label: 'Advanced Settings',
		        elements: [
		            {
		                type: 'text',
		                id: 'id',
		                label: 'Id'
		            }, {
		                type: 'text',
		                id: 'class',
		                label: 'Classes (seperate class name by space)'
		            }
		        ]
		    }
		],

		onOk: function() {
		    var dialog = this;
		    newTagName = dialog.getValueOf( 'tab-basic', 'newTagName' )
		    id = dialog.getValueOf( 'tab-adv', 'id' )
		    className = dialog.getValueOf( 'tab-adv', 'class' )

		    var abbr = editor.document.createElement( newTagName );
		    if ( id )
		        abbr.setAttribute( 'id', id );
		    if ( className )
		        abbr.setAttribute( 'class', className );

		    active = editor.getSelection().getStartElement()
		    activeHtml = editor.getSelection().getStartElement().getHtml()

		    abbr.setHtml( activeHtml );

		    range = editor.createRange()
			range.setStartAt( active, CKEDITOR.POSITION_BEFORE_START );
			range.collapse( true );

			editor.editable().insertElement( abbr, range );
			active.remove()
		},
		onShow: function(){
			active = editor.getSelection().getStartElement()
			console.log(active.$.tagName)
			this.setValueOf( 'tab-basic', 'newTagName', active.$.tagName );
		}
    };
});
