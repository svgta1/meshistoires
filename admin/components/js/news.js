(function(){
  "use strict";
  let Utils = window.mh.Utils;
  let apiUri = window.mh.apiUri;
  let Fetch = window.mh.Fetch;

  class News{
    list = [];
    clean_text(text){
      return text.replace('\x3C!-- [if !supportLists]--><span><span>-<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span>\x3C!--[endif]-->', '');
    }
    setSib(elm){
      let sib = elm.nextSibling.nextSibling;
      if(sib.classList.contains('MsoListParagraphCxSpMiddle') || sib.classList.contains('MsoListParagraphCxSpLast'))
        this.list.push(this.clean_text(sib.innerHTML));
      if(sib.classList.contains('MsoListParagraphCxSpMiddle')){
        this.setSib(sib);
      }
      if(sib.classList.contains('MsoListParagraphCxSpLast')){
        sib.remove();
      }
      elm.remove();
    }
    clean_MsoList(poubelle){
      let l = poubelle.getElementsByClassName('MsoListParagraphCxSpFirst');
      if(l.length > 0){
        this.list = [];
        let _l = l[0];
        this.list.push(this.clean_text(l[0].innerHTML));
        let ul = document.createElement('ul');
        poubelle.insertBefore(ul, l[0]);
        this.setSib(l[0]);
        for(let i = 0; i < this.list.length; i++){
          let li = document.createElement('li');
          ul.appendChild(li);
          li.innerHTML = this.list[i];
        }
      }
      if(poubelle.getElementsByClassName('MsoListParagraphCxSpFirst').length > 0)
        this.clean_MsoList(poubelle);
    }
    clean_MsoNormal(poubelle){
      let l = poubelle.getElementsByClassName('MsoNormal');
      if(l.length > 0){
        l[0].removeAttribute('class');
        this.clean_MsoNormal(poubelle);
      }
        
    }
    clean_style(poubelle){
      let l = poubelle.querySelectorAll("*[style]");
      if(l.length > 0){
        l[0].removeAttribute('style');
        this.clean_style(poubelle);
      }
    }
    async clean(){
      let poubelle = document.createElement('div');
      dispatchEvent.id = 'poubelle';
      poubelle.innerHTML = tinyMCE.get('admin_news_textarea').getContent();
      this.clean_MsoNormal(poubelle);
      this.clean_style(poubelle);
      this.clean_MsoList(poubelle);
      tinyMCE.get('admin_news_textarea').setContent(poubelle.innerHTML);
      poubelle.remove();
    }
    iniTMce(){
      tinymce.init({
        selector: 'textarea#admin_news_textarea',
        height: 500,
        width: 800,
        plugins: "autosave advlist autolink lists link image preview charmap searchreplace visualblocks code fullscreen insertdatetime media table image wordcount",
        toolbar: "restoredraft | insertfile undo redo | styleselect | bold italic underline | fontselect fontsizeselect forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image insert", schema: 'html5',
        autosave_ask_before_unload: true,
        autosave_interval: "5s",
        autosave_prefix: "autosave-{path}{query}-{id}-",
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
        setup: (editor) => {
          editor.on('paste', (e) => {
            setTimeout(() => { news.clean(); }, 1000);
          });
        }
      });  
    }
    async init(){
      let head = document.getElementsByTagName('head')[0];
      let ma = document.createElement('script');
      ma.src = '/admin/components/js/vendor/mammoth.min.js';
      ma.setAttribute('referrerpolicy', 'origin');
      ma.setAttribute('async', false);
      head.appendChild(ma);

      let tmce = document.createElement('script');
      tmce.src = '/admin/components/js/vendor/tinymce/js/tinymce/tinymce.min.js';
      tmce.setAttribute('referrerpolicy', 'origin');
      tmce.setAttribute('async', false);
      head.appendChild(tmce);
      tmce.addEventListener("load", () => {
        this.iniTMce();
      });
      let json = await Fetch.get(apiUri + '/admin/news/list', await Fetch.auth());
      if(!json.ok){
        Utils.resp('Erreur sur le chargement des messages');
        return;
      }
      document.getElementById('clean_editor').addEventListener('click', () => {news.clean();});     
    }
  }

  let news = new News();
  news.init();
})()