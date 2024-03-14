(function(){
  "use strict";
  let Utils = window.mh.Utils;
  let apiUri = window.mh.apiUri;
  let Fetch = window.mh.Fetch;
  let Seo = window.mh.Seo;
  class Static{
    static tpl_article = null;
    static tpl_menu = null;
    static tpl_newmenu = null;
    static menuList = [];
    static orderMenuList(parent, obj){
      for (let [key, value] of Object.entries(obj)){
        if(value.parent !== parent)
          continue;
        if(parent == false)
          obj[key].indice = value.position + '.';
        else
          obj[key].indice = obj[value.parent].indice + value.position + '.';
        this.orderMenuList(key, obj);
      }
    }
    static getMenuList(){
      this.orderMenuList(false, this.menuList);
      let list = [];
      for (let [key, value] of Object.entries(this.menuList)){
        list.push(value);
      }
      list.sort((a, b) =>{
        let iA = a.indice;
        let iB = b.indice;
        if(iA < iB)
          return -1;
        if(iA > iB)
          return 1;
        return 0;
      });
      return list;
    }
    static async getTplArticle(){
      if(this.tpl_article == null){
        let _tpl = await Fetch.get(mh.config.adminPath + '/components/template/article.tpl');
        if(_tpl.ko){
          console.error('template inexistant');
          return
        }
        this.tpl_article = _tpl.resp;
      }
      return this.tpl_article;
    }
    static async getTplMenu(){
      if(this.tpl_menu == null){
        let _tpl = await Fetch.get(mh.config.adminPath + '/components/template/menu.tpl');
        if(_tpl.ko){
          console.error('template inexistant');
          return
        }
        this.tpl_menu = _tpl.resp;
      }
      return this.tpl_menu;
    }
    static async getTplNewMenu(){
      if(this.tpl_newmenu == null){
        let _tpl = await Fetch.get(mh.config.adminPath + '/components/template/menunew.tpl');
        if(_tpl.ko){
          console.error('template inexistant');
          return
        }
        this.tpl_newmenu = _tpl.resp;
      }
      return this.tpl_newmenu;
    }
  }
  class NewArticle{
    constructor(parent){
      if(parent == null || parent == undefined){
        Utils.resp('Erreur sur l\'origine de l\'article.');
          return;
      }
      this.create.init(parent);
    }
    create = {
      init: function(parent){
        let toCreate = {
          parent: parent
        }
        Utils.validChange('Validez la création de l\'article.', this, toCreate, {});
      },
      valid: async function(toCreate, json){
        let uri = apiUri + '/admin/article';
        let auth = await Fetch.auth();
        auth.body = JSON.stringify(toCreate);
        let resp = await Fetch.post(uri, auth);
        if(!resp.ok){
          Utils.resp('Erreur sur la création de l\'article.');
          return;
        }
        let article = new Article(resp.resp.uuid);
        article.affArticle();
      },
      cancel: function(toCreate, json){
        Utils.resp('Annulation de la création de l\'article.');
      }
    }
  }
  class Article{
    updated = false;
    constructor(uuid){
      this.uuid = uuid;
      this.article = null;
    }
    clean = {
      init: function(){
        Utils.validChange('Vider le contenu de l\'article ?', this, {}, {});
      },
      valid: function(v1, v2){
        tinyMCE.get('article_content').setContent('');
      },
      cancel: function(v1, v2){}
    }
    maj = {
      init: function(self){
        let toUpdate = {};
        let content = tinyMCE.get('article_content').getContent();
        if(content != self.article.content)
          toUpdate['content'] = content;
        let title = document.getElementById('article_title').value;
        if(title != self.article.title){
          self.updated = true;
          toUpdate['title'] = title;
        }       
        let position = document.getElementById('article_position').value;
        if(position != self.article.position){
          self.updated = true;
          toUpdate['position'] = position;
        }        
        let visible = document.getElementById('article_visible').checked;
        if(visible != self.article.visible)
          toUpdate['visible'] = visible;
        let resume = document.getElementById('article_resume').checked;
        if(resume != self.article.resume)
          toUpdate['resume'] = resume;
        let comment = document.getElementById('article_comment').checked;
        if(comment != self.article.comment)
          toUpdate['comment'] = comment;
        let parent = document.getElementById('article_menulist').value;
        if(parent != self.article.parent){
          self.updated = true;
          toUpdate['parent'] = parent;
        }
        Utils.validChange('Valider la mise à jour.', this, toUpdate, self);
      },
      valid: async function(toUpdate, self){
        let uri = apiUri + '/admin/article/' + self.article.uuid;
        let auth = await Fetch.auth();
        auth.body = JSON.stringify(toUpdate);
        let resp = await Fetch.put(uri, auth);
        if(!resp.ok){
          Utils.resp('Erreur lors de la mise à jour. Action annulée');
          return;
        }

        Utils.resp('Mise à jour réalisée. Rechargement de l\'article dans 3 sec.');
        setTimeout(() => { 
          Utils.tinyMce.clean('article_content');
          let uuid = self.uuid;
          self.article = null;
          self.uuid = null;
          tinymce.remove();
          document.getElementById('article_aff').remove();
          let article = new Article(uuid);
          article.updated = self.updated;
          article.affArticle();
        }, 3000);
      },
      cancel: function(v1, v2){
        Utils.resp('Mise à jour annulée');
      }
    }
    delete = {
      init: function(self){
        Utils.validChange('Valider la suppression.', this, {}, self);
      },
      valid: async function(v1, self){
        let uri = apiUri + '/admin/article/' + self.article.uuid;
        let resp = await Fetch.delete(uri, await Fetch.auth());
        if(!resp.ok){
          Utils.resp('Erreur serveur sur la suppression de l\'article.');
          return;
        }
        Utils.resp('Suppression réalisée. La page va se recharger dans 3 sec.');
        setTimeout(() => {
          document.location.hash = self.article.parent;
          document.location.reload();
        }, 3000);
      },
      cancel: function(v1, v2){
        Utils.resp('Suppression annulée.');
      }
    }
    async init(){
      let json = await Fetch.get(apiUri + '/admin/article/' + this.uuid, await Fetch.auth());
      if(!json.ok){
        Utils.resp('Erreur sur le chargement de l\'article');
        return;
      }
      this.article = json.resp;
    }
    async getArticle(){
      if(this.article == null)
        await this.init();
      return this.article;
    }
    async getTitle(){
      let title = await this.getArticle().title;
      if(title.length == 0)
        title = "Sans titre";
      return title;
    }
    async affArticle(){
      let article = await this.getArticle();
      let tpl = await Static.getTplArticle();
      let div = document.getElementById('article_aff');
      if( div == null){
        div = document.createElement('div');
        div.id = "article_aff";
        document.getElementById('footer').appendChild(div);
      }
      div.innerHTML = tpl;
      this.iniTMce();

      document.getElementById('article_title').value = article.title;
      document.getElementById('article_position').value = article.position;
      document.getElementById('article_visible').checked = article.visible;
      document.getElementById('article_resume').checked = article.resume;
      document.getElementById('article_comment').checked = article.comment;
      document.getElementById('article_content').value = article.content;
      try{
        tinyMCE.get('article_content').setContent(article.content);
      }catch{
      }
      let menuList = document.getElementById('article_menulist');
      for (let [key, value] of Object.entries(Static.getMenuList())){
        let option = document.createElement('option');
        option.value = value.uuid;
        option.innerHTML = value.indice + ' - ' + value.name;
        menuList.appendChild(option);
        if(value.uuid == article.parent)
          option.selected = true;
      }

      document.getElementById('article_close').addEventListener('click',()=>{
        let updated = this.updated;
        if(!updated){
          this.article = null;
          this.uuid = null;
          tinymce.remove();
          div.remove();
        }else{
          document.location.hash = this.article.parent;
          document.location.reload();
        }
      });
      document.getElementById('article_clean').addEventListener('click',()=>{
        Utils.tinyMce.clean('article_content');
      });
      document.getElementById('article_clear').addEventListener('click',()=>{
        this.clean.init();
      });
      document.getElementById('article_maj').addEventListener('click',()=>{
        this.maj.init(this);
      });
      document.getElementById('article_delete').addEventListener('click',()=>{
        this.delete.init(this);
      });
    }
    iniTMce(){
      let initParam = Utils.tinyMce.general_init;
      initParam.selector = 'textarea#article_content';
      initParam.images_upload_handler = Utils.tinyMce.upload_img;
      initParam.setup = (editor) => {
        editor.on('paste', (e) => {
          setTimeout(() => { Utils.tinyMce.clean('article_content'); }, 1000);
        });
      }
      tinymce.init(initParam);  
    }
  }
  class NewMenu{
    create = {
      init: function(parent){
        let name = Seo.removeAll(document.getElementById('menu_menuname').value.trim());
        if(!name || name.length < 3){
          Utils.resp('Nom de menu trop court');
          return;
        }
        let toCreate = {
          parent: parent,
          name: name
        };
        Utils.validChange('Validez la création du menu <b>'+name+'</b>.', this, toCreate, {});
      },
      valid: async function(toCreate, json){
        let uri = apiUri + '/admin/menu';
        let auth = await Fetch.auth();
        auth.body = JSON.stringify(toCreate);
        let resp = await Fetch.post(uri, auth);
        if(!resp.ok){
          Utils.resp('Erreur sur la création du menu');
          return;
        }
        Utils.resp('Création du menu réalisée. La page va se recharger dans 4 sec.');
        setTimeout(() => {
          document.location.hash = resp.resp.uuid;
          document.location.reload();
        }, 4000);
      },
      cancel: function(toCreate, json){
        Utils.resp('Annulation de la création du menu.');
      },

    }
    constructor(parent){
      this.parent = parent;
    }
    async loadMenu(){
      let spanL = document.getElementsByClassName('selected');
      for(let i = 0; i < spanL.length; i++)
        spanL[i].classList.remove('selected');
      document.getElementById('admin_menus_details').innerHTML = await Static.getTplNewMenu();
      let menuList = document.getElementById('menu_menulist');
      for (let [key, value] of Object.entries(Static.getMenuList())){
        let option = document.createElement('option');
        option.value = value.uuid;
        option.innerHTML = value.indice + ' - ' + value.name;
        menuList.appendChild(option);
        if(value.uuid == this.parent)
          option.selected = true;
      }
      document.getElementById('menu_createmenu').addEventListener('click', ()=>{
        this.create.init(this.parent);
      });
    }
  }
  class Menu{
    menu = null;
    articles = null;
    subMenus = null;
    retried_load = 0;
    constructor(){
      this.uuid = document.location.hash.replace('#', "");
    }
    deleteMenu = {
      init: function(menu){
        this.menu = menu;
        let toUpdate = {};
        let text = 'Validez la suppression du menu <b>'+this.menu.name+'</b>.<br><br>';
        text += '<span class="red">Attention : </span>Les sous-menus et articles ne seront pas supprimés.';

        Utils.validChange(text, this, toUpdate, this.menu);
      },
      valid: async function(toUpdate, json){
        let uri = apiUri + '/admin/menu/' + this.menu.uuid;
        let resp = await Fetch.delete(uri, await Fetch.auth());
        if(!resp.ok){
          Utils.resp('Erreur sur la suppression du menu');
          return;
        }
        Utils.resp('Suppression réalisée. La page va se recharger dans 4 sec.');
        setTimeout(() => {
          if(this.menu.parent){
            document.location.hash = this.menu.parent;
            document.location.reload();
          }else{
            document.location = document.location.origin + document.location.pathname;
          }
        }, 4000);
      },
      cancel: function(toUpdate, json){
        Utils.resp('Suppression annulée.');
      }
    }
    updateMenu = {
      init: function(menu){
        this.menu = menu;
        let toUpdate = {};
        let name = Seo.removeAll(document.getElementById('menu_menuname').value.trim());
        if(name !== this.menu.name)
          toUpdate.name = name;
        let visible = document.getElementById('menu_menuvisible').checked;
        if(visible !== this.menu.visible)
          toUpdate.visible = visible;
        let position = parseInt(document.getElementById('menu_menuposition').value);
        if(position !== this.menu.position)
          toUpdate.position = position;
        let parent = document.getElementById('menu_menulist').value;
        if(parent == 'false')
          parent = false;
        if(parent !== this.menu.parent)
          toUpdate.parent = parent;
        if(Object.keys(toUpdate).length < 1){
          Utils.resp('Aucune mise à jour détectée.');
          return;
        }
        Utils.validChange('Validez la mise à jour du menu <b>'+this.menu.name+'</b>.', this, toUpdate, this.menu);
      },
      valid: async function(toUpdate, json){
        let uri = apiUri + '/admin/menu/' + this.menu.uuid;
        let auth = await Fetch.auth();
        auth.body = JSON.stringify(toUpdate);
        let resp = await Fetch.put(uri, auth);
        if(!resp.ok){
          Utils.resp('Erreur sur la mise à jour du menu');
          return;
        }
        Utils.resp('Mise à jour réalisée. La page va se recharger dans 4 sec.');
        setTimeout(() => {document.location.reload();}, 4000);
      },
      cancel: function(toUpdate, json){
        Utils.resp('Mise à jour annulée.');
      }
    }
    async loadMenu(){
      if(this.menu == null){
        let json = await Fetch.get(apiUri + '/admin/menu/' + this.uuid, await Fetch.auth());
        if(!json.ok){
          Utils.resp('Erreur sur le chargement du menu');
          return;
        }
        this.menu = json.resp;
      }
      document.getElementById('menu_menuname').value = this.menu.name;
      document.getElementById('menu_menuvisible').checked = this.menu.visible;
      document.getElementById('menu_menuposition').value = this.menu.position;
      document.getElementById('menu_menuslength').innerHTML = this.menu.parentChildLength;
      let menuList = document.getElementById('menu_menulist');
      for (let [key, value] of Object.entries(Static.getMenuList())){
        let option = document.createElement('option');
        option.value = value.uuid;
        option.innerHTML = value.indice + ' - ' + value.name;
        menuList.appendChild(option);
        if(value.uuid == this.menu.parent)
          option.selected = true;
      }


      document.getElementById('menu_menuupdate').addEventListener('click', ()=>{
        this.updateMenu.init(this.menu);
      });
      document.getElementById('menu_menudelete').addEventListener('click', ()=>{
        this.deleteMenu.init(this.menu);
      });
      document.getElementById('menu_newsubmenu').addEventListener('click', ()=>{
        let newMenu = new NewMenu(this.menu.uuid);
        newMenu.loadMenu();
      });
      document.getElementById('menu_newarticle').addEventListener('click', ()=>{
        new NewArticle(this.menu.uuid);
      });
    }
    async loadSubMenu(){
      if(this.subMenus == null){
        let subMenuL = await Fetch.get(apiUri + '/admin/menus/' + this.uuid, await Fetch.auth());
        if(!subMenuL.ok){
          Utils.resp('Erreur sur le chargement des sous-menus');
          return;
        }
        this.subMenus = subMenuL.resp.list;
      }
      let subTab = document.getElementById('menu_submenutable');
      for(let i = 0; i < this.subMenus.length; i++){
        let tr = document.createElement('tr');
        subTab.appendChild(tr);
        let position = document.createElement('td');
        tr.appendChild(position);
        position.innerHTML = this.subMenus[i].position;
        let td = document.createElement('td');
        tr.appendChild(td);
        td.innerHTML = this.subMenus[i].name;
        td.addEventListener('click', ()=>{
          document.location.hash = this.subMenus[i].uuid;
          let menu = new Menu();
          menu.affMenu();
        });
      }
    }
    async loadArticles(){
      if(this.articles == null){
        let articlesL = await Fetch.get(apiUri + '/admin/articles/parent/' + this.uuid, await Fetch.auth());
        if(!articlesL.ok){
          Utils.resp('Erreur sur le chargement des articles');
          return;
        }
        this.articles = articlesL.resp;
      }
      let artTab = document.getElementById('menu_articletable');
      for(let i = 0; i < this.articles.length; i++){
        let tr = document.createElement('tr');
        artTab.appendChild(tr);
        let position = document.createElement('td');
        tr.appendChild(position);
        position.innerHTML = this.articles[i].position;
        let td = document.createElement('td');
        tr.appendChild(td);
        td.innerHTML = this.articles[i].title.length > 0 ? this.articles[i].title : 'Sans titre';
        let visible = document.createElement('td');
        tr.appendChild(visible);
        visible.innerHTML = this.articles[i].visible ? 'Oui' : 'Non';
        tr.addEventListener('click', ()=>{
          let art = new Article(this.articles[i].uuid);
          art.affArticle();
        });
      }
    }
    async affMenu(){
      if(this.retried_load > 5){
        Utils.resp('Erreur : menu inexistant');
        return;
      }
      if(Static.menuList[this.uuid] == undefined){
        this.retried_load += 1;
        setTimeout(() => {this.affMenu();}, 500);
        return;
      }
      this.retried_load = 0;
      let spanL = document.getElementsByClassName('selected');
      for(let i = 0; i < spanL.length; i++)
        spanL[i].classList.remove('selected');
      document.getElementById('menu_span_' + this.uuid).classList.add('selected');
      document.getElementById('admin_menus_details').innerHTML = await Static.getTplMenu();
      this.loadMenu();
      this.loadSubMenu();
      this.loadArticles();
    }
  }
  class Menus{
    async getMenus(uuid, elm){
      let uri;
      if(uuid == false)
        uri = apiUri + '/admin/menus/top';
      else
        uri = apiUri + '/admin/menus/' + uuid;
      let json = await Fetch.get(uri, await Fetch.auth());
      if(!json.ok){
        Utils.resp('Erreur sur le chargement des menus');
        return;
      }
      let ul = document.createElement('ul');
      elm.appendChild(ul);
      for(let i = 0; i < json.resp.metadata.count; i++){
        let li = document.createElement('li');
        ul.appendChild(li);
        let span = document.createElement('span');
        li.appendChild(span);
        span.innerHTML = json.resp.list[i].name;
        span.id = "menu_span_" + json.resp.list[i].uuid;
        this.getMenus(json.resp.list[i].uuid, li);
        span.addEventListener('click', () =>{
          document.location.hash = json.resp.list[i].uuid;
          let menu = new Menu();
          menu.affMenu();
        });
        Static.menuList[json.resp.list[i].uuid] = json.resp.list[i];
      }
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

      this.getMenus(false, document.getElementById('admin_menus_list'));
      let hash = document.location.hash.replace('#', "");
      if(hash.length > 0){
        let menu = new Menu();
        menu.affMenu();
      }
      document.getElementById('admin_menus_new_parent').addEventListener('click', ()=>{
        let newMenu = new NewMenu(false);
        newMenu.loadMenu();
      });
    }
  }
  let menus = new Menus;
  menus.init();
})()