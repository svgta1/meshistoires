(function(){
  "use strict";
  var config = null;
  var apiUri = null;

  var menuList = {
    menu_menus: {scope: 'auth admin:read', desc: 'Menus', path: '/menus'},
    menu_cpt: {scope: 'auth admin:read', desc: 'Comptes', path: '/cpt'},
    menu_com: {scope: 'auth admin:read', desc: 'Commentaires', path: '/com'},
    menu_msg: {scope: 'auth admin:read', desc: 'Messages', path: '/msg'},
    menu_news: {scope: 'auth admin:read', desc: 'News letter', path: '/news'},
    menu_img: {scope: 'auth admin:read', desc: 'Images', path: '/img'},
    menu_ana: {scope: 'auth admin:read', desc: 'Analytics', path: '/ana'},
    menu_site: {scope: 'auth', desc: 'Retour au site', path: '/'},
    menu_out: {scope: 'auth', desc: 'Déconnexion', path: '/'},
  }

  class Tpl {
    static async loadTpl(template){
      let tpl = await Fetch.get(config.adminPath + '/components/template/' + template + '.tpl');
      if(!tpl.ok)
        return;
      let content = document.getElementById('content');
      content.innerHTML = tpl.resp;
      let scr = document.createElement('script');
      scr.src = config.adminPath + '/components/js/' + template + '.js';
      content.appendChild(scr);
    }
    static async menus(){
      this.loadTpl('menus');
    }
    static async img(){
      this.loadTpl('img');
    }
    static async cpt(){
      this.loadTpl('cpt');
    }
    static async com(){
      this.loadTpl('com');
    }
    static async msg(){
      this.loadTpl('msg');
    }
    static async news(){
      this.loadTpl('news');
    }
    static async ana(){
      this.loadTpl('ana');
    }
  }
  class Utils {
    static tinyMce = {
      general_init: {
        height: 500,
        width: 800,
        plugins: "autosave advlist autolink lists link image preview charmap searchreplace visualblocks code fullscreen insertdatetime media table image wordcount",
        toolbar: "restoredraft | insertfile undo redo | styleselect | bold italic underline | fontselect fontsizeselect forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image insert", schema: 'html5',
        paste_data_images: true,
        autosave_ask_before_unload: true,
        autosave_interval: "30s",
        autosave_prefix: "autosave-{path}{query}-{id}-",
        autosave_restore_when_empty: true,
        autosave_retention: "1440m",
        image_uploadtab: true,
        image_advtab: true,
        image_caption: true,
        relative_urls: false,
        remove_script_host: false,
        convert_urls: true,
        content_css: "/components/css/default.css?" + new Date().getTime(),
        image_list: async (success) => {
          let json = await Fetch.get(apiUri + '/admin/images', await Fetch.auth());
          if(!json.ok){
            console.error('List not accessible');
            return;
          }
          let list = [];
          for(let i = 0; i < json.resp.count; i++){
            let img = json.resp.list[i];
            img.value = mh.config.api.uri + mh.config.api.version + '/imageThumb300/' + img.value;
            list.push(img);
          }
          success(list);
        }
      },
      upload_img: (blobInfo, progress) => new Promise( async (resolve, reject) => {
        let bInfo=blobInfo.blob();
        let fname = null;
        if(bInfo.name){
          fname=bInfo.name;
        }else{
          let tname=blobInfo.filename();
          let ext=tname.split('.').pop();
          fname = 'img_' + crypto.randomUUID() + '.' + ext;
        }

        let reader = new FileReader();
        let base64String;
        reader.readAsDataURL(bInfo);
        reader.onloadend = function () {
          base64String = reader.result;
        }

        tinymce.activeEditor.setProgressState(true);
        let auth = await Fetch.auth();
        auth.body = JSON.stringify({
          file_name: fname,
          content: base64String
        });
        let json = await Fetch.post(apiUri + '/admin/image', auth);
        tinymce.activeEditor.setProgressState(false);
        if(!json.ok){
          console.error(json.resp);
          reject(json.resp);
          return;
        }
        let img = mh.config.api.uri + mh.config.api.version + '/imageThumb300/' + json.resp.filename;
        resolve(img);
      }),
      clean_text: function(text){
        return text.replace('\x3C!-- [if !supportLists]--><span><span>-<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span>\x3C!--[endif]-->', '');
      },
      setSib: function(elm){
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
      },
      clean_MsoList: function(poubelle){
        let l = poubelle.getElementsByClassName('MsoListParagraphCxSpFirst');
        if(l.length > 0){
          this.list = [];
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
      },
      clean_MsoNormal: function(poubelle){
        let l = poubelle.getElementsByClassName('MsoNormal');
        if(l.length > 0){
          l[0].removeAttribute('class');
          this.clean_MsoNormal(poubelle);
        }
      },
      clean_style: function(poubelle){
        let l = poubelle.querySelectorAll("*[style]");
        if(l.length > 0){
          l[0].removeAttribute('style');
          this.clean_style(poubelle);
        }
      },
      clean: function(id){
        let poubelle = document.createElement('div');
        dispatchEvent.id = 'poubelle';
        poubelle.innerHTML = tinyMCE.get(id).getContent();
        this.clean_MsoNormal(poubelle);
        this.clean_style(poubelle);
        this.clean_MsoList(poubelle);
        tinyMCE.get(id).setContent(poubelle.innerHTML);
        poubelle.remove();
      }
    }
    static strUcFirst(a) {
      return (a+'').charAt(0).toUpperCase() + (a+'').substr(1);
    }
    static toUpper(str) {
      let ar = str.split(' ');
      for(let i = 0; i < ar.length; i++ )
        ar[i] = this.strUcFirst(ar[i]);
      return ar.join(' ');
    }
    static formatDateHM(ts){
      let d = new Date();
      d.setTime(ts * 1000);
      return this.toUpper(d.toLocaleString());
    }
    static formatDate(ts){
      let d = new Date();
      d.setTime(ts * 1000);
      let options = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      };
      return this.toUpper(d.toLocaleDateString(undefined, options));
    }
    static resp(msg, id){
      if(id == undefined)
        id = crypto.randomUUID();;
      let respInfo = document.getElementById(id);
      let create = false;
      if(respInfo == undefined){
        create = true;
        respInfo = document.createElement('div');
        respInfo.id = id;
        document.getElementById('footer').appendChild(respInfo);
      }
      respInfo.classList.add('generalInfo');
      respInfo.innerHTML = msg;
      respInfo.classList.add('show');
      setTimeout(() => { respInfo.innerHTML = ""; }, 3000);
      if(create){
        setTimeout(() => { respInfo.remove();}, 3100);
      }else{
        setTimeout(() => { respInfo.classList.remove('generalInfo');}, 3000);
      }
    }
    static email_validation(email){
      return email.match(
        /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
      );
    }
    static async validChange(text, callback, toChange, json){
      let tpl = await Fetch.get(mh.config.adminPath + '/components/template/validation.tpl');
      if(tpl.ko){
        console.error('template inexistant');
        return
      }
      tpl = tpl.resp.replace("##TEXT##", text);
      let div = document.createElement('div');
      div.id = 'validation_change';
      div.innerHTML = tpl;
      document.getElementById('footer').appendChild(div);
      document.getElementById('validation_change_go').addEventListener('click', () => {
        callback.valid(toChange, json);
        document.getElementById('validation_change').remove();
      });
      document.getElementById('validation_change_cancel').addEventListener('click', () => {
        callback.cancel(toChange, json);
        document.getElementById('validation_change').remove();
      });
    }
  }
  class Main {
    api = null;
    template = 'main';
    constructor(){
      this.api = apiUri;
      this.loadTpl();
    }
    verifyScopes(scope){
      let scopeAr = scope.split(' ');
      let scopeL = [];
      for(let i = 0; i < scopeAr.length; i++)
        scopeL[scopeAr[i]] = false;
      let scopeArP = Jose.JWS_payload.scope.split(' ');
      for(let i=0; i < scopeArP.length; i++){
        if(scopeL[scopeArP[i]] !== undefined)
          scopeL[scopeArP[i]] = true;
      }
      for (let [key, value] of Object.entries(scopeL)){
        if(value === false)
          return false;
      }
      return true;
    }
    async loadTpl(){
      let uri = apiUri + '/auth/renew';
      let auth = await Fetch.auth();
      auth.headers.set('Content-Type', 'application/json')
      auth.body = JSON.stringify({jwk: await Jose.getJWS()});
      let jwt = await Fetch.put(uri, auth);
      if(jwt.ok){
        window.localStorage.setItem('_ua', jwt.resp);
      }
      document.body.innerHTML = '';
      let tpl = await Fetch.get(config.adminPath + '/components/template/' + this.template + '.tpl');
      if(!tpl.ok)
        return;
      document.body.innerHTML = tpl.resp;
      let menuL = [];
      let ul = document.getElementById('menu_ul');
      for (let [key, value] of Object.entries(menuList)){
        if(this.verifyScopes(value.scope)){
          menuL[key] = true;
          let li = document.createElement('li');
          ul.appendChild(li);
          li.id = key;
          let span = document.createElement('span');
          span.innerHTML = value.desc;
          li.appendChild(span);
          if(key !== 'menu_out' && key !== 'menu_site'){
            span.addEventListener('click', function(e){
              window.location = config.adminPath + value.path;
            });
          }else{
            span.addEventListener('click', function(e){
              if(key == 'menu_out'){
                window.localStorage.removeItem('_ua');
                document.location = config.adminPath;
              }
              if(key == 'menu_site'){
                document.location = value.path;
              }
            });
          }
        }
      }
      switch(document.location.pathname){
        case config.adminPath + '/menus':
          if(menuL['menu_menus']){
            Tpl.menus();
            break;
          }
        case config.adminPath + '/img':
          if(menuL['menu_img']){
            Tpl.img();
            break;
          }
        case config.adminPath + '/cpt':
          if(menuL['menu_cpt']){
            Tpl.cpt();
            break;
          }
        case config.adminPath + '/com':
          if(menuL['menu_com']){
            Tpl.com();
            break;
          }
        case config.adminPath + '/msg':
          if(menuL['menu_msg']){
            Tpl.msg();
            break;
          }
        case config.adminPath + '/ana':
          if(menuL['menu_ana']){
            Tpl.ana();
            break;
          }
        case config.adminPath + '/news':
          if(menuL['menu_news']){
            Tpl.news();
            break;
          }
        default:
          if(this.verifyScopes(menuList['menu_menus'].scope)){
            document.location.pathname = config.adminPath + '/menus';
          }else{
            document.location.pathname = '/';
          }
      }
    }
  }
  class Auth {
    api = '/auth';
    template = 'auth';
    constructor(){
      let path = document.location.pathname.replace(config.adminPath + '/auth/','').split('/');
      try{
        this[path[0]](path[1]);
      }catch{
        this.getList();
      }
    }
    async getList(){
      let json = await Fetch.get(apiUri + '/auth/list');
      if(!json.ok)
        return;
      let tpl = await Fetch.get(config.adminPath + '/components/template/' + this.template + '.tpl');
      if(!tpl.ok)
        return;
      document.body.innerHTML = '';
      document.body.innerHTML = tpl.resp;
      let ul = document.getElementById('auth_list');
      let these = this;
      for(let i= 0 ; i < json.resp.length; i++){
        let li = document.createElement('li');
        let btn = document.createElement('button');
        li.appendChild(btn);
        btn.innerHTML = json.resp[i].desc;
        btn.addEventListener('click', async function(e){
          let uri = config.api.uri + these.api + '/';
          let _json = null;
          if(json.resp[i].type == "webauthn"){
            these.webauthn();
          }
          if(json.resp[i].type == "oidc"){
            _json = await Fetch.get(apiUri + '/auth/' + json.resp[i].type + '/' +json.resp[i].id);
            document.location = _json.resp;
          }
        });
        ul.appendChild(li);
      }
    }
    async oidc(uuid){
      let uri = apiUri + '/auth/oidc/callback/' + uuid + document.location.search;
      let json = await Fetch.get(uri);
      if(json.ok){
        await this.setUi(json.resp);
        let lastPath = window.localStorage.getItem('lastPath');
        if(lastPath !== null){
          window.localStorage.removeItem('lastPath');
          window.location = lastPath;
          return;
        }
      }else{
        window.location = '/';
        return;
      }
      window.location = config.adminPath;
    }
    async webauthn(){
      let { startAuthentication } = SimpleWebAuthnBrowser;
      let uri = apiUri + '/auth/webauthn'
      let json = await Fetch.get(uri);
      if(!json.ok)
        return;
      let asseResp;
      try {
        asseResp = await startAuthentication(json.resp);
      } catch (error) {
        throw error;
      }
      let verif = await Fetch.post(uri, {
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(asseResp)
      })
      if(verif.ok){
        await this.setUi(verif.resp);
      }
      window.location = config.adminPath;
    }
    async setUi(resp){
      window.localStorage.setItem('_ua', resp);
      await Jose.getJWS();
      let payload = Jose.JWS_payload;
      let ui = await Ui.getUi();
      ui.givenName = payload.givenName;
      ui.email = payload.email;
      Ui.ui = ui;
      Ui.setUi();
    }
  }
  class Fetch {
    static encKey = null;
    static doEnc = {};
    static async enc_content(content, type){
      if(this.doEnc[type] === undefined){
        let siteInfo = JSON.parse(window.localStorage.getItem('siteInfo'));
        this.doEnc.enc_POST = siteInfo.enc_POST;
        this.doEnc.enc_PUT = siteInfo.enc_PUT;
        this.doEnc.enc_DELETE = siteInfo.enc_DELETE;
      }
      if(!this.doEnc[type])
        return content;
      if(this.encKey == null){
        let {resp, ok} = await this.get(config.api.uri + config.api.version + '/JWK/enc');
        if(!ok)
          return;
        this.encKey = resp;
      }
      let enc= await Jose.encExt(content, this.encKey);
      let o = {
        type: 'enc',
        kid : this.encKey.kid,
        cypher: enc,
      };
      return JSON.stringify(o);
    }
    static async auth(){
      return {
        credentials: "include",
        headers: new Headers({
            'Authorization': 'Bearer ' + await Jose.getJWS(),
            'Content-Type': 'application/json'
        }),
      }
    }
    static async get(uri, params){
      return await this._fetch('GET', uri, params);
    }
    static async post(uri, params){
      params.body = await this.enc_content(params.body, 'enc_POST');
      if(params.headers == undefined)
        params.headers = new Headers({
            'Content-Type': 'application/json'
        });
      return await this._fetch('POST', uri, params);;
    }
    static async put(uri, params){
      params.body = await this.enc_content(params.body, 'enc_PUT');
      if(params.headers == undefined)
        params.headers = new Headers({
            'Content-Type': 'application/json'
        });
      return await this._fetch('PUT', uri, params);;
    }
    static async delete(uri, params){
      params.body = await this.enc_content(params.body, 'enc_DELETE');
      if(params.headers == undefined)
        params.headers = new Headers({
            'Content-Type': 'application/json'
        });
      return await this._fetch('DELETE', uri, params);;
    }
    static async _fetch(method, uri, params){
      if(params === undefined)
        params = {};
      params.method = method;
      try{
        let response = await fetch(uri, params);
        return await this.response(response);
      }catch{
        Utils.resp('Une erreur serveur a été détectée. L\'action est annulée.');
      }
    }
    static async response(response){
      let contentType = null;
      response.headers.forEach(function(val, key){
        if(key == "content-type")
          contentType = val;
      });
      let isJson = this.isJson(contentType);
      if(isJson)
        return {
          resp: await response.json(),
          ok: response.ok
        }
      else
        return {
          resp: await response.text(),
          ok: response.ok
        }
    }
    static isJson(str){
      if(str == null)
        return false;
      return (str.indexOf('application/json') !== -1)
    }

  }
  class Ui {
    static ui = null;
    static tz(){
      return Intl.DateTimeFormat().resolvedOptions().timeZone;
    }
    static ua(){
      return navigator.userAgent;
    }
    static lang(){
      return navigator.language;
    }
    static async getUi(){
      if(this.ui == null){
        let ui = window.localStorage.getItem('_ui');
        if(ui == null){
          ui = {};
        }else{
          try{
            ui = JSON.parse(await Jose.decInt(ui));
          }catch(error){
            ui = {}
          }
        }
        this.ui = ui;
      }
      this.ui.path = document.location.pathname;
      return this.ui;
    }
    static async setUi(){
      if(Fetch.encKey == null){
        let {resp, ok} = await Fetch.get(config.api.uri + config.api.version + '/JWK/enc');
        if(ok)
          Fetch.encKey = resp;
      }
      let ui = await this.getUi();
      ui.tz = this.tz();
      ui.ua = this.ua();
      ui.lang = this.lang();
      if(ui.uuid == undefined)
        ui.uuid = crypto.randomUUID();
      window.localStorage.setItem('_ui', await Jose.encInt(JSON.stringify(ui)));
      let {resp, ok} = await Fetch.post(config.api.uri + config.api.version + '/ui', {
        body: JSON.stringify(ui)
      });
    }
  }
  class Jose {
    static key = null;
    static api = null;
    static JWS_payload = null;
    static JWKset = null;
    static async getJWS(){
      let auth = window.localStorage.getItem('_ua');
      if(auth == null)
        return null;
      if(this.JWKset == null){
        let uri = apiUri + '/JWK/sign';
        let json = await Fetch.get(uri, {
          credentials: "include",
          headers: new Headers({
              'Authorization': 'Bearer ' + auth,
          }),
        });
        if(!json.ok)
          return null;
        this.JWKset = json.resp;
      }
      let JWKset = this.api.createLocalJWKSet(this.JWKset);
      let siteInfo = JSON.parse(window.localStorage.getItem('siteInfo'));

      try{
        var {payload, protectedHeader} = await this.api.jwtVerify(auth, JWKset, {
          issuer: siteInfo.title
        });
      }catch{
        return null;
      }
      this.JWS_payload = payload;
      return auth;
    }
    static async encExt(str, keyObj){
      let key = await this.api.importJWK(keyObj);
      let enc = await new this.api.CompactEncrypt(
        new TextEncoder().encode(str),
      )
        .setProtectedHeader({ alg: keyObj.alg, enc: 'A256CBC-HS512' })
        .encrypt(key);
      return enc;
    }
    static async decInt(jwe){
      let key = await this.getKey();
      let { plaintext, protectedHeader } = await this.api.compactDecrypt(jwe, key);
      return new TextDecoder().decode(plaintext);
    }
    static async encInt(str){
      let key = await this.getKey();
      let enc = await new this.api.CompactEncrypt(
        new TextEncoder().encode(str),
      )
        .setProtectedHeader({ alg: 'A256KW', enc: 'A256CBC-HS512' })
        .encrypt(key);
      return enc;
    }
    static async ctrlKey(){
      if(window.localStorage.getItem('_k') == null){
        await this.genKey();
      }
    }
    static async getKey(){
      if(this.key == null){
        let key = JSON.parse(window.localStorage.getItem('_k'));
        this.key = await this.api.importJWK(key);
      }
      return this.key;
    }
    static async genKey(){
      let key = await this.api.generateSecret("HS256", {extractable: true});
      let exp = await this.api.exportJWK(key);
      window.localStorage.setItem('_k', JSON.stringify(exp));
    }
  }
  class Seo {
    static seoP = {};
    static load = {
      'menu': false,
      'article': false,
    };
    static genSeoMenu(){
      let menu = window.localStorage.getItem('menu');
      if(menu == null)
        return;
      menu = JSON.parse(menu);
      for(let [key, value] of Object.entries(menu)){
        this.seoP['/' + value.uri] = value;
      }
      this.load.menu = true;
      content.getLocation();
    }
    static genSeoArticles(){
      let article = window.localStorage.getItem('article');
      if(article == null)
        return;
      article = JSON.parse(article);
      for(let [key, value] of Object.entries(article)){
        this.seoP['/' + value.uri] = value;
      }
      this.load.article = true;
      content.getLocation();
    }
    static removeAll(str) {
      if ((str === null) || (str === ''))
          return false;
      else
          str = str.toString();
      str = this.removeTags(str);
      str = this.removeSpec(str);
      str = this.removeCharriot(str);
      return str;
    }
    static removeTags(str) {
      if ((str === null) || (str === ''))
          return false;
      else
          str = str.toString();
      return str.replace(/<\/?[^>]+(>|$)/g, '');
    }
    static removeSpec(str){
      if ((str === null) || (str === ''))
          return false;
      else
          str = str.toString();

      return str.replace(/&eacute;/g,'é')
              .replace(/&nbsp;/g,'')
              .replace(/&ccedil;/g,'ç')
              .replace(/&agrave;/g,'à')
              .replace(/&egrave;/g,'è')
              .replace(/&ocirc;/g,'ô');
    }
    static removeCharriot(str) {
      if ((str === null) || (str === ''))
          return false;
      else
          str = str.toString();
      return str.replace(/\n|\r/g,' ');
    }
    static truncate(content, len){
      return content.substring(0, len);
    }
    static title(content){
      let title = document.title;
      document.title = content + ' - ' + title;
      let titleOg = document.getElementById('__meta-og:title');
      let titleX = document.getElementById('__meta-twitter:title');
      titleOg.content = titleX.content = content;
    }
    static desc(content){
      let desc = document.getElementById('__meta-description');
      let descOg = document.getElementById('__meta-og:description');
      let descX = document.getElementById('__meta-twitter:desc');
      desc.content = descOg.content = descX.content = info.title + ' - ' + info.desc + ' : ' +  this.truncate(this.removeAll(content.trim()), 150);;
    }
  }
  class Init{
    constructor(){
      this.loadConfig();
      this.path = document.location.pathname;
      this.adm_conf = JSON.parse(document.getElementById('adm_conf').innerText);
    }
    async loadConfig(){
      let json = await Fetch.get('/config/config.json?d=' + Date.now());
      await this.start(json.resp);
    }
    async start(json){
      config = json;
      window.mh.config = config;
      config.adminPath = this.adm_conf.path;
      let info = await Fetch.get(config.api.uri + '/info');
      window.localStorage.setItem('siteInfo', JSON.stringify(info.resp));
      window.mh.apiUri = apiUri = config.api.uri + config.api.version;
      window.mh.Fetch = Fetch;
      window.mh.adminPath = config.adminPath;
      let authU = await Jose.getJWS();
      if(authU == null){
        if(!this.path.match(/\/auth\//g))
          document.location.pathname = config.adminPath + '/auth/';
        new Auth();
      }else{
        new Main();
      }
    }
  }
  if(window.mh == undefined)
    window.mh = {};
  window.mh.staticJose = Jose;
  window.mh.Seo = Seo;
  window.mh.Utils = Utils;
  new Init();
})()
