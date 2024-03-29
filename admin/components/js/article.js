tinymce.init({
		selector: 'textarea',
		language: 'fr_FR',
		height : 500,
		plugins: [
			"textcolor autosave advlist autolink lists link image charmap print preview anchor",
			"searchreplace visualblocks code fullscreen",
			"insertdatetime media table contextmenu powerpaste image imagetools wordcount"
		],
		toolbar: "restoredraft | insertfile undo redo | styleselect | bold italic underline | fontselect fontsizeselect forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image insert", schema: 'html5',
		autosave_ask_before_unload: true,
		autosave_interval: "5s",
		autosave_prefix: "igblog-autosave-{path}{query}-{id}-",
		autosave_restore_when_empty: true,
		autosave_retention: "1440m",
		paste_data_images: true,
		powerpaste_word_import: 'clean',
		image_list: 'listImage.php',
		image_advtab: true,
		images_upload_handler: function (blobInfo, success, failure) {
		},
		content_css: "/components/css/default.css?" + new Date().getTime(),
	});
