jQuery( document ).ready( function($)
{
	// default editor
	var ket_def_editor = $( '#postdivrich' );
	// cf input field
	var ket_cf_editor_type = $( 'input[name="ket_editor_type"]' );

	// if cf value none, set now editor type
	if ( ket_cf_editor_type.val() === '' )
	{
		ket_cf_editor_type.val( ket_get_def_editor_type ( ket_def_editor ) );
	}

	// when tab switch
	ket_def_editor.find( '.wp-switch-editor' ).on( 'click', function()
	{
		ket_cf_editor_type.val( ket_get_cr_editor_type ( $(this) ) );
	});


	// function
	function ket_get_def_editor_type ( editor )
	{
		var editor_type = '';
		if ( editor.find( 'div' ).hasClass( 'html-active' ) )
		{// text editor
			editor_type = 'html';
		} else {// visual editor
			editor_type = 'tinymce';
		}
		return editor_type;
	}

	function ket_get_cr_editor_type ( tab )
	{
		var editor_type = '';
		if( tab.hasClass( 'switch-html' ) )
		{// text editor
			editor_type = 'html';

		} else {// visual editor
			editor_type = 'tinymce';
		}
		return editor_type;
	}
});
