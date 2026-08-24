(function(){
  "use strict";
  var config = null;
  var contentClass = null;
  var menu = null;
  var gArticle = null;
  var info = null;
  var articleLoaded = [];
  var prevUrl = null;
  var userLoad = null;

  class Image {
    static open(src){
      let body = document.body;
      if(document.getElementById('img_div') != null)
        return;
      let div = document.createElement('div');
      if(document.getElementById('img_img') != null)
        return;
      let img = document.createElement('img');
      div.id = 'img_div';
      div.classList.add("close");
      img.id = 'img_img';
      img.setAttribute('alt', 'bigPicture');
      img.src = src.replace('imageThumb300','image').replace('imageThumb','image');
      img.classList.add("small");

      img.addEventListener('load', function(){
        img.classList.remove("small");
        div.classList.remove("close");
      });

      div.addEventListener('click', function (e) {
        img.classList.add("small");
        div.classList.add("close");
        setTimeout(() => { div.remove(); }, 100);
        //this.remove();
      });
      div.appendChild(img);
      body.appendChild(div);
    }
  }
  class Info {
    static title = '';
    static desc = '';
    constructor(){
      this.infoUrl = config.api.uri + '/info';
    }
    async getInfo(){
      let info = await this.getApiInfo();
      let title = document.getElementById('site_title');
      let desc = document.getElementById('site_desc');
      info = JSON.parse(info);
      title.innerHTML = info.title;
      desc.innerHTML = info.description;
      document.title = info.title;
      Info.title = this.title = info.title;
      Info.desc = this.desc = info.description;
      this.copyRight = info.copyRight;
      this.isBot = info.isBot;
      this.isAdult = info.adult_content;
      new Footer(this);
      this.consent();
    }
    async consent(){
      if(this.isBot)
        return;
      let consentStore = window.localStorage.getItem('_consent');
      if(consentStore != null){
        consentStore = JSON.parse(consentStore);
        if(this.isAdult && consentStore.a18 && consentStore.cookie)
          return;
        if(!this.isAdult && consentStore.cookie)
          return;
      }
      let tplHtml = null;
      if(this.isAdult)
        tplHtml = await Fetch.get(window.mh.components + 'template/consentAdult.tpl?' + window.mh.config.version);
      else
        tplHtml = await Fetch.get(window.mh.components + 'template/consent.tpl?' + window.mh.config.version);
      tplHtml = tplHtml.resp;
      let body = document.body;
      let these = this;

      if(document.getElementById('consent') != null)
        return;
      let div = document.createElement('div');
      div.id = 'consent';
      div.innerHTML = tplHtml;
      body.appendChild(div);
      div.classList.add('close');
      setTimeout(() => { div.classList.remove('close'); }, 100);
      let close = document.getElementById('input_consent_send');
      if(these.isAdult){
        close.addEventListener('click', function (e) {
          let a18 = document.getElementById('input_consent_18');
          if(a18.checked){
            window.localStorage.setItem('_consent', JSON.stringify({
              'cookie': true,
              'a18': true,
              'time': new Date().getTime()
            }));
            div.classList.add('close');
            setTimeout(() => { div.remove(); }, 100);
          }
        });
      }else{
        close.addEventListener('click', function (e) {
          let cookie = document.getElementById('input_consent_cookie');
          if(cookie.checked){
            window.localStorage.setItem('_consent', JSON.stringify({
              'cookie': true,
              'time': new Date().getTime()
            }));
            div.classList.remove('close');
            setTimeout(() => { div.remove(); }, 100);
          }
        });
      }
    }
    async getApiInfo(){
      let info = await Fetch.get(this.infoUrl);
      let res = JSON.stringify(info.resp);
      window.localStorage.setItem('siteInfo', res);
      return res;
    }
  }
  
  class Menu {
    fetch_menu_top = null;

    constructor(){
      this.apiUrl = config.api.uri + config.api.version;
      this.menuUrl = this.apiUrl + '/menu';
      this.get_menuTop();
    }
    async storeMenuTop(){
      let menuTop = await this.get_ApiMenuTop();
      window.sessionStorage.setItem('menu_top', menuTop);
      this.menuTop = JSON.parse(menuTop);
    }
    async get_menuTop(){
      let menuTop = window.sessionStorage.getItem('menu_top');
      if(menuTop == null || menuTop == 'null'){
        await this.storeMenuTop();
      }else{
        this.menuTop = JSON.parse(menuTop);
      }
      if(this.menuTop == null)
        return;
      let menu = document.getElementById('menu');
      menu.innerHTML = this.menuTop.template;
      Utils.allAPreventDefault('menu');
      this.storeMenuTop();
    }
    async get_menuContent(name){
      let ar = name.split('/');
      let method = ar[0];
      Menu.addClassMenu(method);
      ar.shift();
      let path = ar.join('/');
      let url;
      if(path == ""){
        url = this.menuUrl + '/' + method;
      }else{
        url = this.apiUrl + '/' + method + '/' + path;
      }
      if(name = 'accueil/images'){
        let token = window.localStorage.getItem('_tokenImgs');
        if(token !== null)
          url += '?token=' + token;
      }
      let res = Fetch.get(url);
      try {
        contentClass[method](res);
      }catch(error){
        console.error(error);
        window.location = '/accueil/error404';
        // Erreur 404;
      }
    }
    static addClassMenu(name){
      let menu = document.getElementById('menu');
      let ul = menu.getElementsByTagName('ul')[0];
      let list = ul.getElementsByTagName('li');
      let li = document.getElementById('menu_li_' + name);
      for(let l of list){
        if(l.id !== 'menu_li_' + name)
          l.classList.remove('highLight');
      }
      if(!li)
        return;
      if(!li.classList.contains('highLight'))
        li.classList.add('highLight');
    }
    async get_ApiMenuTop(){
      let url = this.menuUrl + '/top';
      if(this.fetch_menu_top !== null)
        return null;
      this.fetch_menu_top = 1;
      let menus = await Fetch.get(url);
      menus = menus.resp;
      this.fetch_menu_top = null;
      return JSON.stringify(menus);
    }
  }
  class Content {
    constructor(){
      this.url = document.location.protocol + '//' + document.location.host + document.location.pathname;
      this.location = document.location.pathname.replace(/\/$/, '');
      if(this.location == '/' || this.location == ''){
        document.location = '/accueil';
        return;
      }
      this.loadConfig();
      this.apiUrl = config.api.uri + config.api.version;
      this.menu = new Menu();
    }
    async accueil(ress){
      let res = await ress;
      if(!res.ok){
        if(res.responseCode == 404){
          window.location = '/accueil/error404';
          return;
        }
        if(res.responseCode == 403){
          window.location = '/accueil/error403';
          return;
        }
        return;
      }
      this.defaultContent(res.resp);
      let img_ac = document.getElementById('img_accueil');
      let l = document.location.protocol + '//' + document.location.host + config.components + 'img/inspiration.webp'
      if(img_ac)
        img_ac.src = l;
      document.getElementById("__meta-og:image").content = document.getElementById("__meta-twitter:image").content = l;
    }
    async collections(ress){
      let res = await ress;
      if(!res.ok){
        if(res.responseCode == 404){
          window.location = '/accueil/error404';
          return;
        }
        if(res.responseCode == 403){
          window.location = '/accueil/error403';
          return;
        }
        return;
      }
      this.defaultContent(res.resp);
      let uri = config.api.uri + config.api.version + '/collections/histoire/';
      if(res.resp.histoires){
        let nbr = res.resp.histoires.nbr;
        for(let i = 0; i < res.resp.histoires.nbr; i++){
          let uuid = res.resp.histoires.list[i]
          let url = uri + uuid;
          if(i < 20)
            await this.getHistoire(url, uuid);
          else
            this.getHistoire(url, uuid);
        }
      }
    }
    async images(ress){
      let res = await ress;
      if(!res.ok){
        if(res.responseCode == 404){
          window.location = '/accueil/error404';
          return;
        }
        if(res.responseCode == 403){
          window.location = '/accueil/error403';
          return;
        }
        return;
      }
      this.defaultContent(res.resp);
    }
    async getHistoire(url, uuid){
      let histoire = await Fetch.get(url);
      let liHist = document.getElementById('histoire_' + uuid);
      liHist.innerHTML = histoire.resp.html;
      Utils.allAPreventDefault('histoire_' + uuid);
      let imgs = liHist.getElementsByTagName('img');
      let imgUrl = config.api.uri + config.api.version + '/imageThumb300/';
      for(let e of imgs){
        e.src = imgUrl + e.id;
        Utils.imgPreventDefault(e);
      };
    }
    async histoires(ress){
      let res = await ress;
      if(!res.ok){
        if(res.responseCode == 404){
          window.location = '/accueil/error404';
          return;
        }
        if(res.responseCode == 403){
          window.location = '/accueil/error403';
          return;
        }
        return;
      }
      this.defaultContent(res.resp);
      let uri = config.api.uri + config.api.version + '/histoires/';
      if(res.resp.histoires){
        let nbr = res.resp.histoires.nbr;
        for(let i = 0; i < nbr; i++){
          let uuid = res.resp.histoires.list[i]
          let url = uri + 'l/' +uuid;
          if(i < 20)
            await this.getHistoire(url, uuid);
          else
            this.getHistoire(url, uuid);
        }
      }
    }
    defaultContent(resp){
      let dAriane = document.getElementById('ariane')
      dAriane.innerHTML = resp.ariane;
      Utils.allAPreventDefault('ariane');
      let content = document.getElementById('content');
      content.innerHTML = resp.template;
      Utils.allAPreventDefault('content');
      let imgs = content.getElementsByTagName('img');
      let imgUrl = config.api.uri + config.api.version + '/imageThumb300/';
      for(let e of imgs){
        e.src = imgUrl + e.id;
        Utils.imgPreventDefault(e);
      };
      document.title = document.getElementById("__meta-og:title").content = document.getElementById("__meta-twitter:title").content = resp.data.meta.title;
      document.getElementById("__meta-og:image").content = document.getElementById("__meta-twitter:image").content = resp.data.meta.image;
      document.getElementById("__meta-og:url").content = document.getElementById("__meta-twitter:url").content = resp.data.meta.url;
      document.getElementById("__meta-description").content = document.getElementById("__meta-og:description").content = document.getElementById("__meta-twitter:desc").content = resp.data.meta.description;
      document.getElementById("__meta-keywords").content = resp.data.meta.keywords;
      document.getElementById("CreativeWork").innerHTML = JSON.stringify(resp.creative);
      let delL = document.getElementsByTagName("span");
      for(let d of delL){
        if(!d.hasAttribute('action')){
          continue;
        }
        let id = null;
        let uri = null;
        let action = null;
        let reload = false;
        if(d.getAttribute('action') == "delete"){
          id = d.id.replace('span_imageId_', '');
          uri = this.apiUrl + '/image/' + id;
          action = 'delete';
        }
        if(d.getAttribute('action') == "deleteDefitive"){
          id = d.id.replace('span_imageId_del_', '');
          uri = this.apiUrl + '/image/definitive/' + id;
          action = 'delete';
          reload = true;
        }
        if(d.getAttribute('action') == "restore"){
          id = d.id.replace('span_imageId_rest_', '');
          uri = this.apiUrl + '/image/' + id;
          action = 'put';
          reload = true;
        }
        if(uri !== null && action !== null){
          d.addEventListener('click', async ()=>{
            let token = window.localStorage.getItem('_tokenImgs');
            let b = {};
            if(token !== null){
              b.body = JSON.stringify({token: token});
            }
            let res = await Fetch[action](uri, b);
            if(res.ok){
              document.getElementById('li_' + id).remove();
            }
          });
        }
      }
    }
    async loadConfig(){
      config = window.mh.config;
      info = new Info();
      info.getInfo();
    }
    async contentMenu(){
      document.getElementById('wrap').scrollTo({top: 0, left: 0, behavior: "smooth"}); 
      let searchParam = new URL(document.location.toString()).searchParams;
      let token = searchParam.get('token');
      if(token){
        window.localStorage.setItem('_tokenImgs', token);
      }
      this.menu.get_menuContent(document.location.pathname.replace('/',''));
    }
  }
  class Utils {
    static updateLocalStorage(){
      /*let d = new Date();
      let update = window.localStorage.getItem('_updateInfos');
      let dU = new Date();
      dU.setTime(update);
      let diff = config.updateH * 60 * 60 * 1000;
      if((d - dU) > diff){
        window.localStorage.setItem('_updateInfos', d.getTime());
        info.getApiInfo();
        menu.get_ApiMenuTop();
        menu.get_ApiMenu();
        gArticle.get_ApiArticle();
      }*/
    }
    static resp(msg, id){
      if(id == undefined)
        id = crypto.randomUUID();
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
    static allImgPrenventDefault(id){
      let contents = document.getElementById(id);
      let imgs = contents.getElementsByTagName('img');
      for(let img of imgs){
        Utils.imgPreventDefault(img);
      }
    }
    static allAPreventDefault(id){
      let contents = document.getElementById(id);
      let la = contents.getElementsByTagName('a');
      for(let a of la){
        Utils.aPreventDefault(a)
      }
    }
    static imgPreventDefault(img){
      if(img.parentElement.tagName == 'A' || img.parentElement.tagName == 'a'){
		    Utils.aPreventDefault(img.parentElement);
		    return;
	    }
	    img.addEventListener("click",()=>{
		    Image.open(img.src);
	    })
    }
    static aPreventDefault(a){
      if(a.target)
        return;
      a.addEventListener('click', (event) => {
        event.preventDefault();
        if(a.getAttribute('att') == 'anchor'){
          document.getElementById("histImg").scrollIntoView({
            behavior: 'smooth'
          });
        }else{
          window.history.pushState(window.location.pathname, '', a.href);
        }
      });
    }
    static email_validation(email){
      return email.match(
        /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
      );
    }
    static async getUserInfo(){
      let jwt = window.localStorage.getItem('_ua');
      let json, givenName, familyName, email;
      if(jwt !== null){
        if(userLoad == null){
          json = await Fetch.get(config.api.uri + config.api.version + '/user/profile', await Fetch.auth());
          userLoad = json;
        }
        json = userLoad;
        if(json.ok){
          givenName = json.resp.givenname;
          familyName = json.resp.sn;
          email = json.resp.mail;
        }
      }
      if(jwt == null || !json.ok){
        let info = await Ui.getUi();
        if(info.givenName != undefined)
          givenName = info.givenName;
        if(info.familyName != undefined)
          familyName = info.familyName;
        if(info.email != undefined)
          email = info.email;
      }
      return {
        'givenName': givenName,
        'familyName': familyName,
        'email': email
      }
    }
    static async userInfo(){
      let info = await this.getUserInfo();
      this.saveUserInfo(info.givenName, info.givenName, info.familyName, info.email);
      this.saveUserInfo(info.familyName, info.givenName, info.familyName, info.email);
      this.saveUserInfo(info.email, info.givenName, info.familyName, info.email);
    }
    static async saveUserInfo(elm, givenName, familyName, email){
      var info = await Ui.getUi();
      elm.addEventListener('focusout', function(e){
        let _info = {};
        Object.assign(_info, info);
        _info.givenName = givenName.value.trim();
        _info.familyName = familyName.value.trim();
        _info.email = email.value.trim();
        if(JSON.stringify(info) !== JSON.stringify(_info)){
          Ui.ui = _info;
          //Ui.setUi();
          Object.assign(info, _info);
        }
      });
    }
    static loadScript(src, id){
      let htmlHead = document.getElementsByTagName('head')[0];
      if(document.getElementById(id) == undefined){
        let script = document.createElement('script');
        script.setAttribute('rel', "preload");
        script.setAttribute('as', "script");
        script.id = id;
        script.src = src + '?v=' + config.version;
        htmlHead.appendChild(script);
      }
    }
  }
  class Footer {
    constructor(info){
      let cr = document.getElementById('div_copyright');
      let div = document.createElement('div');
      cr.appendChild(div);
      div.innerHTML = info.copyRight;
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
        let uri = config.api.uri + config.api.version + '/JWK/sign';
        let json = await Fetch.get(uri, {
          headers: new Headers({
              'Authorization': 'Bearer ' + auth,
              'Content-Type': 'application/json'
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
      if(params.credentials == undefined)
        params.credentials = "same-origin";
      params.method = method;
      let response = await fetch(uri, params);
      return await this.response(response);
    }
    static async response(response){
      let contentType = null;
      response.headers.forEach(function(val, key){
        if(key == "content-type")
          contentType = val;
      });
      let isJson = this.isJson(contentType);
      if(isJson)
        try{
          let resp = await response.json();
          let jsonOk = response.ok;
          if(resp.error !== undefined && resp.error)
            jsonOk = false;
          return {
            responseCode: response.status,
            responseError: response.statusText,
            resp: resp,
            ok: jsonOk
          }
        } catch (error){
          //window.localStorage.clear();
          return {
            responseCode: response.status,
            responseError: response.statusText,
            resp: null,
            ok: false
          };
        }
      else
        return {
          responseCode: response.status,
          responseError: response.statusText,
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
  class Init{
    constructor(){
      contentClass = new Content();
      this.loadConfig();
    }
    async loadConfig(){
      document.getElementById('header').addEventListener('click', ()=>{
        window.location = "/accueil";
      });
      this.start();
      this.end();
    }
    async start(){
      prevUrl = window.location.href;
      setInterval(() => {
        let currUrl = window.location.href;
        if (currUrl != prevUrl) {
          if(window.history.state == null)
            return;
          prevUrl = currUrl;
          contentClass.contentMenu();
        }
      }, 60);
      //contentClass.contentMenu();
      let lM = window.location.pathname.split('/');
      Menu.addClassMenu(lM[1]);
      if(lM[1] == 'images'){
        contentClass.contentMenu();
      }
      Utils.allAPreventDefault('ariane');
      Utils.allAPreventDefault('content');
      Utils.allImgPrenventDefault('content');
    }
    end(){
      let d = new Date();
      window.localStorage.setItem('_updateInfos', d.getTime());
      document.body.classList.remove('hidden');
    }
  }

  if(window.mh == undefined)
    window.mh = {};
  new Init();
})()
