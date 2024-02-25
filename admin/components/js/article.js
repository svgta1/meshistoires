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
		/*	var xhr, formData;

			xhr = new XMLHttpRequest();
			xhr.withCredentials = false;
			xhr.open('POST', 'uploadImage.php');

			xhr.onload = function() {
				var json;

				if (xhr.status != 200) {
					failure('HTTP Error: ' + xhr.status);
					return;
				}

				json = JSON.parse(xhr.responseText);

				if (!json || typeof json.location != 'string') {
				failure('Invalid JSON: ' + xhr.responseText);
					return;
				}
				try {
					success(json.location);
				}catch(error){
					console.error(error);
				}
			};

			var bInfo=blobInfo.blob();

			if(bInfo.name){
				var fname=bInfo.name;
			}else{

				var tname=blobInfo.filename();
				var ext=tname.split('.').pop();
				var d=new Date();
				var a=d.getFullYear();
				var m=d.getMonth() + 1;
				if(m < 10)
					m= "0"+m;
				var j=d.getDate();
				if(j < 10)
					j="0"+j;
				var h=d.getHours();
				if(h < 10)
					h="0"+h;
				var i=d.getMinutes();
				if(i < 10)
					i="0"+i;
				var s=d.getSeconds();
				if(s < 10)
					s="0"+s;
				var ms=d.getMilliseconds();
				if(ms < 10)
					ms ="00"+ms;
				else if(ms < 100)
					ms ="0"+ms;

				var fname = 'img.'+ a.toString() + m.toString() + j.toString() + h.toString() + i.toString() + s.toString() + '.' + ms.toString() + '.' + ext;
			}

			formData = new FormData();
			formData.append('file', blobInfo.blob(),fname);

			xhr.send(formData);*/
		},
		content_css: "/components/css/default.css?" + new Date().getTime(),
	});
