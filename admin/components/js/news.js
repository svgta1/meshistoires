(function(){
  "use strict";
  let Utils = window.mh.Utils;
  let apiUri = window.mh.apiUri;
  let Fetch = window.mh.Fetch;
  let Seo = window.mh.Seo;

  class Publish{
    init(json){
      let toUpdate = {};
      let text = 'Publier la news letter <b>' + json.title + '</b>';
      text += '<br><br><b>Attention : </b> Veuillez enregistrer les dernières modifications avant publication. Dans le cas contraire, elles ne seront pas prises en compte.';
      Utils.validChange(text, this, toUpdate, json);
    }
    async valid(toUpdate, json){
      let _json = await Fetch.put(apiUri + '/admin/news/publish/' + json.uuid, await Fetch.auth());
      if(!_json.ok){
        Utils.resp('Erreur sur la publication de la news');
        return;
      }
      Utils.resp('Publication réalisée. La page va se recharger dans 4 sec.');
      setTimeout(() => {document.location.reload();}, 4000);
    }
    cancel(toUpdate, json){
      Utils.resp('Publication annulée');
    }
  }
  class EnregNews{
    init(json){
      if(json.title.length < 3){
        Utils.resp('Titre trop court');
        return;
      }
      if(json.msg.length < 10){
        Utils.resp('Contenu trop court');
        return;
      } 
      let toUpdate = {
        msg: json.msg,
        title: Seo.removeAll(json.title)
      }
      Utils.validChange('Enregistrement de la news letter <b>' + json.title + '</b>', this, toUpdate, json);
    }
    async valid(toUpdate, json){
      let auth = await Fetch.auth();
      auth.body = JSON.stringify(toUpdate);
      let _json;
      if(json.uuid == null)
        _json = await Fetch.post(apiUri + '/admin/news', auth);
      else
        _json = await Fetch.put(apiUri + '/admin/news/' + json.uuid, auth);
      if(!_json.ok){
        Utils.resp('Erreur sur l\'enregistrement de la news');
        return;
      }
      Utils.resp('Enregistrement réalisé. La page va se recharger dans 4 sec.');
      setTimeout(() => {document.location.reload();}, 4000);
    }
    cancel(toUpdate, json){
      Utils.resp('Enregistrement annulé');
    }
  }
  class News{
    list = [];
    new_template = null;
    disableButton(elm){
      elm.classList.add('grey');
      elm.disabled = true;
    }
    affNews(json){
      let div = document.getElementById('news_setnews');
      document.getElementById('news_setnews_title').value = json.title;
      document.getElementById('news_setnews_published').checked = json.published;
      if(json.datePublished !== 0)
        document.getElementById('news_setnews_published_date').innerHTML = Utils.formatDateHM(json.datePublished);
      tinyMCE.get('admin_news_textarea').setContent(json.msg);

      document.getElementById('clean_editor').addEventListener('click', () => {Utils.tinyMce.clean('admin_news_textarea');});
      document.getElementById('news_setnews_annuler').addEventListener('click', () => {
        tinymce.remove();
        div.remove();
      });
      document.getElementById('news_setnews_clean').addEventListener('click', () => {
        tinyMCE.get('admin_news_textarea').setContent('');
      });
      document.getElementById('news_setnews_enregistrer').addEventListener('click', () => {
        let eng = new EnregNews();
        eng.init({
          title: document.getElementById('news_setnews_title').value,
          msg: tinyMCE.get('admin_news_textarea').getContent(),
          uuid: json.uuid
        });
      });
      document.getElementById('news_setnews_publish').addEventListener('click', () => {
        let pub = new Publish();
        pub.init(json);
      });
      if(json.published){
        document.getElementById('news_setnews_title').disabled = true;
        this.disableButton(document.getElementById('clean_editor'));
        this.disableButton(document.getElementById('news_setnews_enregistrer'));
        this.disableButton(document.getElementById('news_setnews_publish'));
        this.disableButton(document.getElementById('news_setnews_clean'));
      }
      document.getElementById('admin_tmce_div_textarea').classList.remove('hidde');
    }
    async setNews(json){
      if(this.new_template == null){
        let tpl = await Fetch.get(mh.config.adminPath + '/components/template/news_new.tpl');
        if(!tpl.ok){
          Utils.resp('Erreur sur le chargement du template');
          return;
        }
        this.new_template = tpl.resp;
      }
      let footer = document.getElementById('footer');
      if(document.getElementById('news_setnews') == null){
        let div = document.createElement('div');
        footer.appendChild(div);
        div.id = 'news_setnews';
      }
      let div = document.getElementById('news_setnews');
      div.innerHTML = this.new_template;
      document.getElementById('admin_tmce_div_textarea').classList.add('hidde');
      this.iniTMce();
      setTimeout(() => { news.affNews(json); }, 500);
    }
    iniTMce(){
      let initParam = Utils.tinyMce.general_init;
      initParam.selector = 'textarea#admin_news_textarea';
      initParam.images_upload_handler = Utils.tinyMce.upload_img;
      initParam.setup = (editor) => {
        editor.on('paste', (e) => {
          setTimeout(() => { Utils.tinyMce.clean('admin_news_textarea'); }, 1000);
        });
      }
      tinymce.init(initParam);  
    }
    async init(){
      let head = document.getElementsByTagName('head')[0];
      let tmce = document.createElement('script');
      tmce.src = '/admin/components/js/vendor/tinymce/js/tinymce/tinymce.min.js';
      tmce.setAttribute('referrerpolicy', 'origin');
      tmce.setAttribute('async', false);
      head.appendChild(tmce);
      this.tinyMce_loaded = false;
      tmce.addEventListener("load", () => {
        this.tinyMce_loaded = true;
      });
      let json = await Fetch.get(apiUri + '/admin/news/list', await Fetch.auth());
      if(!json.ok){
        Utils.resp('Erreur sur le chargement des messages');
        return;
      }
      if(this.tpl_list == null){
        let tpl = await Fetch.get(mh.config.adminPath + '/components/template/news_list.tpl');
        if(!tpl.ok){
          console.error('tpl ne peut pas être chargé')
          return;
        }
        this.tpl_list = tpl.resp;
      }
      
      let ul = document.getElementById('admin_news_list');
      for(let i = 0; i < json.resp.length; i++){
        let li = document.createElement('li');
        ul.appendChild(li);
        let div = document.createElement('div');
        li.appendChild(div);
        let tpl = this.tpl_list;
        tpl = tpl.replace('##title##', json.resp[i].title);
        tpl = tpl.replace('##dateCreate##', Utils.formatDateHM(json.resp[i].dateCreate));
        tpl = tpl.replace('##dateUpdate##', Utils.formatDateHM(json.resp[i].dateUpdate));
        tpl = tpl.replace('##published##', json.resp[i].published ? 'Oui' : 'Non');
        tpl = tpl.replace('##datePublished##', (json.resp[i].datePublished == 0) ? '' : Utils.formatDateHM(json.resp[i].datePublished));
        div.innerHTML = tpl;
        let button = document.createElement('button');
        div.appendChild(button);
        if(json.resp[i].published){
          button.innerHTML = 'Visualiser la news';
          button.classList.add('orange');
        }else{
          button.innerHTML = 'Editer la news';
          button.classList.add('green');
        }
          
        button.addEventListener('click', ()=>{
          news.setNews(json.resp[i]);
        });
        let br = document.createElement('br');
        div.appendChild(br);
        let newB = document.createElement('button');
        div.appendChild(newB);
        newB.innerHTML = "Créer nouveaux à partir du contenu";
        newB.addEventListener('click', ()=>{
          let _json = {
            title: null,
            msg: json.resp[i].msg,
            published: false,
            datePublished: 0,
            uuid: null
          };
          news.setNews(_json);
        });
      }
      document.getElementById('admin_news_create').addEventListener('click', () => {news.setNews({
        title: null,
        msg: '',
        published: false,
        datePublished: 0,
        uuid: null
      });});
    }
  }

  let news = new News();
  news.init();
})()