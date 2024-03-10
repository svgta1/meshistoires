(function(){
  "use strict";
  var menuLoaded = [];
  var articlesLoaded = [];
  var tpl_init = null;
  var tpl_init_article = null;
  var content = document.getElementById('content');

  class Alert {
    constructor() {
      this.id = '_alert';
      let footer = document.getElementById('footer');
      let div = document.createElement('div');
      footer.appendChild(div);
      div.id = this.id;
      this.div = div;
    }
    open(dom, callback, valid, close, _class) {
      let self = this;
      this.div.appendChild(dom);
      close.addEventListener('click', () => {
        self.close();
      });
      valid.addEventListener('click', () => {
        callback(self, _class);
      });
    }
    close() {
      this.div.remove();
    }
  }

  class Article {
    constructor(obj) {
      this.obj = obj;
      this.init();
    }
    async init() {
      if(tpl_init_article == null){
        let tpl = await Fetch.get(window.mh.adminPath + '/components/template/article.tpl');
        if(!tpl.ok)
          return;
        tpl_init_article = tpl.resp;
      }
      this.loadScr();
      this.setObj();
    }
    setObj() {
      document.getElementById('article_id').innerHTML = this.obj.uuid;
      document.getElementById('article_selected_h2').innerHTML = document.getElementById('article_title').value =this.obj.title;
      document.getElementById('article_position').value = this.obj.position;
      document.getElementById('article_visible').checked = this.obj.visible;
      document.getElementById('article_resume').checked = this.obj.resume;
      document.getElementById('article_comment').checked = this.obj.comment;
      document.getElementById('article_content').innerHTML = this.obj.content;
    }
    loadScr() {
      let div = document.getElementById('article_selected');
      div.innerHTML = tpl_init_article;
      let scriptTiny = document.createElement('script');
      div.appendChild(scriptTiny);
      scriptTiny.src = "/admin/components/js/vendor/tinymce/js/tinymce/tinymce.min.js";
      scriptTiny.async = false;
      scriptTiny.type = "text/javascript";
      scriptTiny.addEventListener('load', function(e){
        let script = document.createElement('script');
        div.appendChild(script);
        script.src = "/admin/components/js/article.js";
        script.async = true;
        script.type = "text/javascript";
        script.addEventListener('load',function(e){
          window.mh.articleEditor();
        });
      });
    }
  }
  class Menu {
    constructor(obj) {
      this.obj = obj;
      window.location.hash = obj.uuid;
      let ul = document.getElementById('menu_ul');
      let spanL = ul.getElementsByClassName('selected');
      for(let i=0; i < spanL.length; i++)
        spanL[i].classList.remove('selected');
      let li = document.getElementById(obj.uuid);
      if(li !== null)
        li.getElementsByTagName('span')[0].classList.add('selected');
      this.loadTpl();
      this.loadH2();
      this.loadInfo();
      this.loadSubMs();
      this.loadArticles();
    }
    async loadArticles() {
      let self = this;
      let tableA = document.getElementById('menu_select_articles');
      for(let i = 0; i < self.obj.articles.length; i++){
        let tr = document.createElement('tr');
        tableA.appendChild(tr);
        let td = document.createElement('td');
        tr.appendChild(td);
        if(articlesLoaded[self.obj.articles[i]] == undefined){
          let api = await Fetch.get(apiUri + '/admin/article/' + self.obj.articles[i], await Fetch.auth());
          if(!api.ok)
            return;
          if(api.resp.title == undefined)
            api.resp.title = '';
          articlesLoaded[self.obj.articles[i]] = api.resp;
        }
        let json = articlesLoaded[self.obj.articles[i]];
        let title = null;
        if(json.title.length < 2)
          title = 'No title';
        else
          title = json.title;
        td.innerHTML = title;
        td.addEventListener('click', function(e){
          new Article(json);
        });
      }
    }
    async loadSubMs() {
      let self = this;
      let tableM = document.getElementById('menu_select_sDomain');
      for(let i = 0; i < self.obj.subMenu.length; i++){
        let tr = document.createElement('tr');
        tableM.appendChild(tr);
        let td = document.createElement('td');
        tr.appendChild(td);
        if(menuLoaded[self.obj.subMenu[i]] == undefined){
          let json = await Fetch.get(apiUri + '/admin/menu/' + self.obj.subMenu[i], await Fetch.auth());
          menuLoaded[self.obj.subMenu[i]] = json.resp;
        }
        td.innerHTML = menuLoaded[self.obj.subMenu[i]].name;
        td.addEventListener('click', function(e){
          new Menu(menuLoaded[self.obj.subMenu[i]]);
        });
      }
    }
    async loadInfo() {
      let self = this;
      let span = document.getElementById('menu_select_parent');
      if(self.obj.parent == null || !self.obj.parent){
        span.innerHTML += "Aucun";
      }else{
        let a = document.createElement('a');
        span.appendChild(a);
        a.href = '#' + self.obj.parentObj.uuid;
        a.innerHTML = self.obj.parentObj.name;
        a.addEventListener('click', async function(e){
          if(menuLoaded[self.obj.parentObj.uuid] == undefined){
            let json = await Fetch.get(apiUri + '/admin/menu/' + self.obj.parentObj.uuid, await Fetch.auth());
            menuLoaded[self.obj.parentObj.uuid] = json.resp;
          }
          new Menu(menuLoaded[self.obj.parentObj.uuid]);
        });
      }
      document.getElementById('menu_select_id').innerHTML = self.obj.uuid;
      document.getElementById('menu_select_name').value = self.obj.name;
      document.getElementById('menu_select_position').value = self.obj.position;
      document.getElementById('menu_select_visible').checked = self.obj.visible;

      document.getElementById('menu_select_maj').addEventListener('click', () => {
          self.update(self);
      });
      document.getElementById('menu_select_del').addEventListener('click', () => {
        let div = document.createElement('div');
        let h2 = document.createElement('h2');
        div.appendChild(h2);
        h2.innerHTML = "Valider la suppression";
        let p = document.createElement('p');
        p.innerHTML = "<b>Attention</b> : il ne sera pas possible de faire machine arrière.<br />";
        p.innerHTML += "Les articles seront également supprimés.";
        div.appendChild(p);
        let ok = document.createElement('button');
        div.appendChild(ok);
        ok.innerHTML = "Valider";
        let ko = document.createElement('button');
        div.appendChild(ko);
        ko.innerHTML = "Annuler";
        ok.classList.add('red');
        let a = new Alert();
        a.open(div, self.validSup, ok, ko, self);
      });
    }
    async validSup(callbackClass, self) {
      console.log(callbackClass);
      let json = await Fetch.delete(apiUri + '/admin/menu/' + self.obj.uuid, await Fetch.auth());
      if(json.ok){
        delete menuLoaded[self.obj.uuid];
        document.getElementById(self.obj.uuid).remove();
        let menu = menuLoaded[self.obj.parent];
        if(menu !== undefined){
          let ind = menu.subMenu.indexOf(self.obj.uuid);
          menu.subMenu.splice(ind, 1);
          new Menu(menuLoaded[self.obj.parent]);
        }else{
          let key = Object.keys(menuLoaded)[0];
          console.log('key', menuLoaded[key]);
          new Menu(menuLoaded[key]);
        }
        callbackClass.close();
      }else{
        let div = callbackClass.div;
        if(document.getElementById('alerte_error') == null){
          let p = document.createElement('p');
          p.id = 'alerte_error';
          div.getElementsByTagName('div')[0].appendChild(p);
        }
        let pA = document.getElementById('alerte_error');
        pA.innerHTML = '<b>Erreur : ' + json.resp + '</b>';
      }
    }
    async update() {
      let json = {
        'name': document.getElementById('menu_select_name').value,
        'position': document.getElementById('menu_select_position').value,
        'visible': document.getElementById('menu_select_visible').checked
      };
      let auth = await Fetch.auth();
      auth.body = JSON.stringify(json);
      let ret = await Fetch.put(apiUri + '/admin/menu/' + this.obj.uuid, auth);
      if(ret.ok){
        this.obj.name = json.name;
        this.obj.position = json.position;
        this.obj.visible = json.visible;
      }
      let li = document.getElementById(this.obj.uuid);
      li.innerHTML = '';
      let span = document.createElement('span');
      li.appendChild(span);
      span.innerHTML = this.obj.name;
      let self = this;
      span.addEventListener('click', function(e){
        new Menu(self.obj);
      });
      new Menu(this.obj);
    }
    loadH2() {
      let h2 = document.getElementById('menu_select_h2');
      h2.innerHTML = this.obj.name;
    }
    loadTpl() {
      this.dom = document.getElementById('menu_select');
      if(tpl_init == null)
        tpl_init = this.dom.outerHTML;
      content.innerHTML = tpl_init;
    }
  }
  class MenuLeft {
    constructor(obj, dom) {
      this.obj = obj;
      this.dom = dom;
      let ul = document.createElement('ul');
      dom.appendChild(ul);
      this.genLi();
      ul.appendChild(this.li);
      this.loadSub();
    }
    async loadSub() {
      if(this.obj.subMenu == undefined)
        return;
      if(this.obj.subMenu.length == 0)
        return;
      for(let i = 0; i < this.obj.subMenu.length; i++){
        if(menuLoaded[this.obj.subMenu[i]] == undefined){
          let json = await Fetch.get(apiUri + '/admin/menu/' + this.obj.subMenu[i], await Fetch.auth());
          if(!json.ok)
            return;
          menuLoaded[this.obj.subMenu[i]] = json.resp;
        }
        new MenuLeft(menuLoaded[this.obj.subMenu[i]], this.li);
      }
    }
    genLi() {
      let li = document.createElement('li');
      li.id = this.obj.uuid;
      let span = document.createElement('span');
      li.appendChild(span);
      span.innerHTML = this.obj.name;
      let self = this;
      span.addEventListener('click', function(e){
        new Menu(self.obj);
      });
      this.li = li;
      if(window.location.hash.replace('#', '') == self.obj.uuid)
        this.li.getElementsByTagName('span')[0].classList.add('selected');
    }
  }
  async function loadTopMenu(){
    let json = await Fetch.get(apiUri + '/admin/menu/top', await Fetch.auth());
    let left_m = document.getElementById('menu_menus');
    let listTop = json.resp.list;
    for(let i = 0; i < json.resp.metadata.count; i++){
      if(menuLoaded[listTop[i].uuid] == undefined)
        menuLoaded[listTop[i].uuid] = listTop[i];
      new MenuLeft(listTop[i], left_m);
    }
    if(window.location.hash.length == 37){
      json = await Fetch.get(apiUri + '/admin/menu/' + window.location.hash.replace('#', ''), await Fetch.auth());
      new Menu(json.resp);
    }else{
      new Menu(json.resp.list[0]);
    }
  }
  let apiUri = window.mh.apiUri;
  let Fetch = window.mh.Fetch;
  loadTopMenu();
})()
