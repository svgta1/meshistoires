(function(){
  "use strict";
  var config = null;
  var content = null;
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
      this.title = info.title;
      this.desc = info.description;
      this.social = info.social;
      this.copyRight = info.copyRight;
      this.isBot = info.isBot;
      this.isAdult = info.adult_content;
      Seo.social('__meta-twitter:site', info.social.x.id);
      Seo.social('__meta-fb:id', info.social.facebook.id);
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
        tplHtml = await Fetch.get('/components/template/consentAdult.tpl');
      else
        tplHtml = await Fetch.get('/components/template/consent.tpl');
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
          let cookie = document.getElementById('input_consent_cookie');
          if(a18.checked && cookie.checked){
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
      document.title = content + ' - ' + info.title;
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
    static img(content){
      let imgOg = document.getElementById('__meta-og:image');
      let imageApi = config.api.uri + config.api.version + '/image';
      let imgX = document.getElementById('__meta-twitter:image');
      imgOg.content = imgX.content = document.location.protocol + '//' + document.location.host + imageApi + '/' + content;
    }
    static url(url){
      let urlOg = document.getElementById('__meta-og:url');
      let canon = document.getElementById('__canonical');
      let x = document.getElementById('__meta-twitter:url');
      urlOg.content = canon.href = x.content = url;
    }
    static social(elmId, content){
      let elm = document.getElementById(elmId);
      elm.content = content;
    }
  }
  class Article {
    fetch_article = null;
    constructor(){
      this.articleUrl = config.api.uri + config.api.version + '/article';
      this.get_articles();
    }
    ariane(menuUuid, articleUuid){
      let ariane = menu.ariane(menuUuid);
      let span = document.createElement('span');
      span.innerHTML = "</span>&nbsp;&nbsp;➲&nbsp;&nbsp;";
      ariane.appendChild(span);
      let a = document.createElement('a');
      a.setAttribute('property', 'item');
      a.setAttribute('typeof', 'WebPage');
      let article = articleLoaded[articleUuid];
      a.href = '/' + article.uri;
      a.innerHTML = article.title;
      Utils.aPreventDefault(a);
      ariane.appendChild(a);
      return ariane;
    }
    async get_articles(){
      let article = window.localStorage.getItem('article');
      if(article == null){
        article = await this.get_ApiArticle();
      }
      Seo.genSeoArticles();
    }

    async get_ApiArticle(){
      let url = this.articleUrl ;
      if(this.fetch_article !== null)
        return null;
      this.fetch_article = 1;
      let articles = await Fetch.get(url);
      articles = articles.resp;
      this.fetch_article = null;
      if(window.localStorage.getItem('article_hash') !== articles.metadata.hash){
        window.localStorage.setItem('article', JSON.stringify(articles.list));
        window.localStorage.setItem('article_hash', articles.metadata.hash);
      }
      if(window.localStorage.getItem('article') == null)
        window.localStorage.setItem('article', JSON.stringify(articles.list));
      return JSON.stringify(articles.list);
    }


  }
  class Menu {
    fetch_menu = null;
    fetch_menu_top = null;

    constructor(){
      this.menuUrl = config.api.uri + config.api.version + '/menu';
      this.get_menuTop();
    }
    async get_menuTop(){
      let menuTop = window.localStorage.getItem('menu_top');
      if(menuTop == null){
        menuTop = await this.get_ApiMenuTop();
      }
      if(menuTop == null)
        return;
      menuTop = JSON.parse(menuTop);
      let ul = document.createElement('ul');
      for(let [key, value] of Object.entries(menuTop)){
        let li = document.createElement('li');
        li.setAttribute('property', 'itemListElement');
        li.setAttribute('typeof', 'ListItem');
        let a = document.createElement('a');
        a.setAttribute('property', 'item');
        a.setAttribute('typeof', 'WebPage');
        a.href = '/' + value.uri;
        a.innerHTML = value.name;
        a.setAttribute('uuid', value.uuid);
        Utils.aPreventDefault(a);
        li.appendChild(a);
        if(value.subMenu.length > 0){
          a.setAttribute('class', 'havesubmenu');
          this.get_menuTopSub(li, value.subMenu);
        }
        ul.appendChild(li);
      }
      let menu = document.getElementById('menu');
      menu.appendChild(ul);
    }
    async get_menuTopSub(elm, subMenu){
      let menu = window.localStorage.getItem('menu');
      if(menu == null){
        menu = await this.get_ApiMenu();
      }
      if(menu == null)
        return;
      menu = JSON.parse(menu);

      let ul = document.createElement('ul');
      for(let i = 0; i < subMenu.length; i++){
        let li = document.createElement('li');
        li.setAttribute('property', 'itemListElement');
        li.setAttribute('typeod', 'ListItem');
        let a = document.createElement('a');
        a.setAttribute('property', 'item');
        a.setAttribute('typeof', 'WebPage');
        a.setAttribute('uuid', subMenu[i]);
        li.appendChild(a);
        ul.appendChild(li);
        if(menu[subMenu[i]]){
          a.href = '/' + menu[subMenu[i]].uri;
          a.innerHTML = menu[subMenu[i]].name;
          Utils.aPreventDefault(a);
        }else{
          a.remove();
        }
      }
      elm.appendChild(ul);
      Seo.genSeoMenu();
    }
    async get_ApiMenu(){
      let url = this.menuUrl;
      if(this.fetch_menu !== null)
        return null;
      this.fetch_menu = 1;
      let menus = await Fetch.get(url);
      menus = menus.resp;
      this.fetch_menu = null;
      if(window.localStorage.getItem('menu_hash') !== menus.metadata.hash){
        window.localStorage.setItem('menu', JSON.stringify(menus.list));
        window.localStorage.setItem('menu_hash', menus.metadata.hash);
      }
      if(window.localStorage.getItem('menu') == null)
        window.localStorage.setItem('menu', JSON.stringify(menus.list));
      return JSON.stringify(menus.list);
    }
    async get_ApiMenuTop(){
      let url = this.menuUrl + '/top';
      if(this.fetch_menu_top !== null)
        return null;
      this.fetch_menu_top = 1;
      let menus = await Fetch.get(url);
      menus = menus.resp;
      this.fetch_menu_top = null;
      if(window.localStorage.getItem('menu_top_hash') !== menus.metadata.hash){
        window.localStorage.setItem('menu_top', JSON.stringify(menus.list));
        window.localStorage.setItem('menu_top_hash', menus.metadata.hash);
      }
      if(window.localStorage.getItem('menu_top') == null)
        window.localStorage.setItem('menu_top', JSON.stringify(menus.list));
      return JSON.stringify(menus.list);
    }
    ariane(uuid){
      let menu = JSON.parse(window.localStorage.getItem('menu'));
      let ariane = [];
      ariane.unshift(menu[uuid]);
      while(menu[uuid].parent !== false){
        uuid = menu[uuid].parent;
        ariane.unshift(menu[uuid]);
      }
      let div = document.createElement('div');
      div.setAttribute('class', 'ariane');
      if(ariane.length < 2)
        return div;
      for(let i = 0; i < ariane.length; i++){
        let span = document.createElement('span');
        span.innerHTML = "</span>&nbsp;&nbsp;➲&nbsp;&nbsp;";
        div.appendChild(span);
        let a = document.createElement('a');
        a.setAttribute('property', 'item');
        a.setAttribute('typeof', 'WebPage');
        a.href = '/' + ariane[i].uri;
        a.innerHTML = ariane[i].name;
        Utils.aPreventDefault(a);
        div.appendChild(a);
      }
      return div;
    }
    async submenu(uuid){
      let menuList = JSON.parse(window.localStorage.getItem('menu'));
      let menu = menuList[uuid];
      let subMenuDiv = document.getElementById('submenu');
      if(subMenuDiv == null)
        return;
      subMenuDiv.innerHTML = "";
      if(menu.name == "Accueil"){
        let json = await Fetch.get(config.api.uri + config.api.version + '/menu/random');
        if(json.ok)
          menu.subMenu = json.resp;
      }
      if(menu.subMenu.length == 0){
        subMenuDiv.classList.add('hide');
        document.getElementById('content').classList.add('minHeight');
        subMenuDiv.classList.remove('show');
      }else{
        let h2 = document.createElement('h2');
        if(menu.name == "Accueil"){
          h2.innerHTML = "Histoires au hasard";
        }else{
          h2.innerHTML = "A lire dans cette section";
        }
        subMenuDiv.appendChild(h2);
        let ul = document.createElement('ul');
        subMenuDiv.appendChild(ul);
        for(let i=0; i < menu.subMenu.length; i++){
          let subMenu = menuList[menu.subMenu[i]];
          let image = '/components/img/logo_mh_128.png';
          if(subMenu.articles[0] !== undefined){
            let article = subMenu.articles[0];
            if(articleLoaded[article] !== undefined){
              article = articleLoaded[article];
            }else{
              let json = await Fetch.get(config.api.uri + config.api.version + '/article/' + article);
              if(json.ok){
                article = json.resp;
              }else {
                article = null;
              }
            }
            if(article != null && article.firstImage != null){
              image = config.api.uri + config.api.version + '/imageThumb/' + article.firstImage;
            }
          }

          let li = document.createElement('li');
          ul.appendChild(li);
          li.setAttribute('property', 'itemListElement');
          li.setAttribute('typeod', 'ListItem');
          let a = document.createElement('a');
          li.appendChild(a);
          let div = document.createElement('div');
          a.appendChild(div);
          let img = document.createElement('img');
          div.appendChild(img);
          let span = document.createElement('span');
          a.appendChild(span);
          a.setAttribute('property', 'item');
          a.setAttribute('typeof', 'WebPage');
          a.setAttribute('title', subMenu.name);
          a.href = '/' + subMenu.uri;
          Utils.aPreventDefault(a);
          span.setAttribute('property', 'name');
          span.innerHTML = subMenu.name;
          img.src = image;
          img.setAttribute('alt', 'img_' + subMenu.name);
          setTimeout(() => {
            document.getElementById('content').classList.remove('minHeight');
            subMenuDiv.classList.add('show');
            subMenuDiv.classList.remove('hide');
          }, 400);
        }
      }
    }
    menuName(uuid){
      let menuList = JSON.parse(window.localStorage.getItem('menu'));
      let menu = menuList[uuid];
      return menu.name;
    }
  }
  class Content {
    constructor(){
      this.url = document.location.protocol + '//' + document.location.host + document.location.pathname;
      this.location = document.location.pathname;
      if(this.location == '/')
        this.location = '/accueil';
      this.articleApi = config.api.uri + config.api.version + '/article';
      this.menuApi = config.api.uri + config.api.version + '/menu';
      this.imageApi = config.api.uri + config.api.version + '/image';
      this.commentApi = config.api.uri + config.api.version + '/comment/article';
    }
    async getLocation(){
      if(Seo.load.menu == false || Seo.load.article == false){
        return;
      }
      if(Seo.load.article && Seo.load.menu){
        if(Seo.seoP[this.location] == undefined){
          let json = await Fetch.get('/components/template/nocontent.tpl');
          if(!json.ok)
            return;
          let wrap = document.getElementById('wrap');
          let footer = document.getElementById('footer');
          wrap.innerHTML = json.resp;
          wrap.appendChild(footer);
          console.error('Location', this.location + ' Not exist');
          return;
        }
        this.content = Seo.seoP[this.location];
        Seo.url(this.url);
        if(this.content.type == "menu")
          this.contentMenu();
        if(this.content.type == "article")
          this.contentArticle();
      }
    }
    async contentMenu(){
      Seo.title(this.content.name);
      Seo.desc(this.content.name);

      menu.submenu(this.content.uuid);
      let contents = document.getElementById('content');
      document.getElementById('wrap').insertBefore(menu.ariane(this.content.uuid), contents);

      contents.innerHTML = "";
      let h2 = document.createElement('h2');
      contents.appendChild(h2);
      h2.innerHTML = this.content.name;

      let imgsList = [];
      let firstArt = null;
      let articles = this.content.articles;
      if(articleLoaded[articles[0]] == undefined){
        let articlesList = await Fetch.get(this.articleApi + '/parent/' + this.content.uuid);
        if(articlesList.ok){
          for(let i = 0; i < articlesList.resp.length; i++){
            let article = articlesList.resp[i];
            articleLoaded[article.uuid] = article;
          }
        }
      }
      for(let i = 0; i < articles.length; i++){
        let div = document.createElement('div');
        div.setAttribute('class', 'article');
        div.id = articles[i];
        contents.appendChild(div);
        let content = null;
        if(articleLoaded[articles[i]] == undefined){
          content = await Fetch.get(this.articleApi + '/' + articles[i]);
          if(content.ok)
            articleLoaded[articles[i]] = content.resp;
        }
        content = articleLoaded[articles[i]];
        if(firstArt == null)
          firstArt = content;
        let article = document.createElement('article');
        if(content.title){
          let h2 = document.createElement('h2');
          h2.innerHTML = content.title;
          article.appendChild(h2);
        }

        if(content.resume === true){
          Html.resume(
            article,
            content.content.replaceAll('style="color: #6b5b95; font-family: \'Allura\', cursive; font-weight: bold; font-size: 1.5em;"', 'class="signature"').replaceAll('style="color: #6b5b95; font-family: Allura, cursive; font-weight: bold; font-size: 1.5em;"', 'class="signature"'),
            content.title,
            config.api.uri + config.api.version + '/imageThumb/' + content.firstImage
          );
        }else{
          article.innerHTML = content.content.replaceAll('style="color: #6b5b95; font-family: \'Allura\', cursive; font-weight: bold; font-size: 1.5em;"', 'class="signature"').replaceAll('style="color: #6b5b95; font-family: Allura, cursive; font-weight: bold; font-size: 1.5em;"', 'class="signature"');
          article.querySelectorAll("*[style]").forEach((element) => element.removeAttribute('style'));
        }
        Html.contentConv(article, this.content.name, '');
        let from = config.api.uri + config.api.version + '/image/';
        let to = config.api.uri + config.api.version + '/imageThumb/';
        let to300 = config.api.uri + config.api.version + '/imageThumb300/';
        let imgs = article.getElementsByTagName('img');
        for(let i=0; i < imgs.length; i++){
          if(!imgsList.includes(imgs[i].src))
            imgsList.push(imgs[i].src);
          imgs[i].src = imgs[i].src.replace(from, to300);
          if(content.resume === true){
            imgs[i].className = 'smallimg';
            imgs[i].src = imgs[i].src.replace(from, to);
            imgs[i].src = imgs[i].src.replace(to300, to);

            let thb300 = document.createElement('img');
            thb300.src = imgs[i].src.replace(to, to300);
          }
          let image = document.createElement('img');
          image.src = imgs[i].src.replace(to, from);
          image.src = image.src.replace(to300, from);
        }
        if(content.resume){
          let divLire = document.createElement('div');
          divLire.setAttribute('class', 'articleResume');
          let a = document.createElement('a');
          a.setAttribute("class", "otherPage");
          a.setAttribute("property", "item");
          a.setAttribute("typeof", "WebPage");
          a.href = content.uri;
          a.innerHTML = '<span><span property="name">Lire la suite </span>&nbsp;&nbsp;➲</span>';
          Utils.aPreventDefault(a);
          divLire.appendChild(a);
          article.appendChild(divLire);
        }
        div.appendChild(article);
      }
      document.getElementById('wrap').insertBefore(menu.ariane(this.content.uuid), document.getElementById('footer'));
      Seo.desc(this.content.name + ' ' + firstArt.content);
      Seo.img(firstArt.firstImage);
      for(let i = 0; i < imgsList.length; i++){
        let img = document.createElement('img');
        img.src = imgsList[i];
      }
    }
    async contentArticle(){
      let subMenuDiv = document.getElementById('submenu');

      let content = null;
      if(articleLoaded[this.content.uuid] == undefined){
        content = await Fetch.get(this.articleApi + '/' + this.content.uuid);
        if(content.ok)
          articleLoaded[this.content.uuid] = content.resp;
      }

      let menuName = menu.menuName(this.content.menu_uuid);
      let contents = document.getElementById('content');
      contents.innerHTML = "";
      let h2 = document.createElement('h2');
      contents.appendChild(h2);
      h2.innerHTML = menuName;
      let ariane = gArticle.ariane(this.content.menu_uuid, this.content.uuid);
      ariane.id = "article_ariane";
      if(document.getElementById('article_ariane') == undefined){
        ariane.id = "article_ariane";
        document.getElementById('wrap').insertBefore(ariane, contents);
      }

      let div = document.createElement('div');
      div.setAttribute('class', 'article');
      div.id = this.content.uuid;
      contents.appendChild(div);
      contents.appendChild(gArticle.ariane(this.content.menu_uuid, this.content.uuid));
      let article = document.createElement('article');
      div.appendChild(article);

      let pagination = document.createElement('div');
      pagination.setAttribute('class', 'pagination');
      let page_prev = document.createElement('span');
      let page_inter = document.createElement('span');
      page_inter.innerHTML = '<span>&nbsp;&nbsp;&nbsp;&nbsp; — oooOOooo — &nbsp;&nbsp;&nbsp;&nbsp;</span>';
      let page_next = document.createElement('span');
      pagination.appendChild(page_prev);
      pagination.appendChild(page_inter);
      pagination.appendChild(page_next);
      content = articleLoaded[this.content.uuid];

      this.prevArticle(page_prev);
      this.nextArticle(page_next);

      Seo.desc(content.menu_name + ' ' + content.title);
      Seo.img(content.firstImage);
      Seo.title(content.menu_name + ' | ' + content.title);

      h2.innerHTML += '<span class="hearts">&hearts;</span>' + content.title;
      let articleDiv = document.createElement('div');
      content.content = content.content.replaceAll('style="color: #6b5b95; font-family: \'Allura\', cursive; font-weight: bold; font-size: 1.5em;"', 'class="signature"').replaceAll('style="color: #6b5b95; font-family: Allura, cursive; font-weight: bold; font-size: 1.5em;"', 'class="signature"');
      articleDiv.innerHTML = content.content;
      article.innerHTML = '';
      article.appendChild(articleDiv);
      article.querySelectorAll("*[style]").forEach((element) => element.removeAttribute('style'));
      this.articleComment(content, article, articleDiv);

      Html.contentConv(article, content.menu_name, content.title);

      let from = config.api.uri + config.api.version + '/image/';
      let to = config.api.uri + config.api.version + '/imageThumb300/';
      let imgs = article.getElementsByTagName('img');
      let imgsList = [];
      for(let i=0; i < imgs.length; i++){
        if(!imgsList.includes(imgs[i].src))
          imgsList.push(imgs[i].src);
        imgs[i].src = imgs[i].src.replace(from, to);
      }

      div.appendChild(pagination);
      for(let i = 0; i < imgsList.length; i++){
        let img = document.createElement('img');
        img.src = imgsList[i];
      }
    }
    async articleComment(content, article, first){
      let articleComments = null;
      let commentActif = await Fetch.get(config.api.uri + '/info/commentActif');
      if(content.comment && commentActif.resp.comment_enable)
        articleComments = await Comment.articleComment(this.commentApi, this.content.uuid);
      if(content.comment && commentActif.resp.comment_enable)
        article.insertBefore(articleComments, first);
    }
    async prevArticle(elm){
      let content = await Fetch.get(this.articleApi + '/' + this.content.uuid + '/prev');
      content = content.resp;
      if((content.title !== undefined) && (content.title !== "")){
        let a = document.createElement('a');
        a.setAttribute("class", "otherPage");
        a.setAttribute("property", "item");
        a.setAttribute("typeof", "WebPage");
        a.href = '/' + content.uri;
        Utils.aPreventDefault(a);

        let span = document.createElement('span');
        span.setAttribute("property", "name");
        span.innerHTML = '⮈&nbsp;&nbsp;<span>' + content.title + '</span>';
        a.appendChild(span);
        elm.appendChild(a);
      }
    }
    async nextArticle(elm){
      let content = await Fetch.get(this.articleApi + '/' + this.content.uuid + '/next');
      content = content.resp;
      if((content.title !== undefined) && (content.title !== "")){
        let a = document.createElement('a');
        a.setAttribute("class", "otherPage");
        a.setAttribute("property", "item");
        a.setAttribute("typeof", "WebPage");
        a.href = '/' + content.uri;
        Utils.aPreventDefault(a);

        let span = document.createElement('span');
        span.setAttribute("property", "name");
        span.innerHTML = "<span>" + content.title + '</span>&nbsp;&nbsp;➲';
        a.appendChild(span);
        elm.appendChild(a);
      }
    }
  }
  class Comment{
    static async articleComment(api, artUuid){
      let content = await Fetch.get(api + '/' + artUuid);
      content = content.resp;
      let div = document.createElement('div');
      div.id = "article_comments";
      div.setAttribute('class', 'comment');
      let spanH = document.createElement('span');
      div.appendChild(spanH);
      spanH.innerHTML = content.length + ' commentaire';
      if(content.length > 1)
        spanH.innerHTML += 's';
      let spanH1 = document.createElement('span');
      div.appendChild(spanH1);
      spanH1.innerHTML = 'Laisser un commentaire';
      spanH1.addEventListener('click', function(e){
        Comment.addComment(artUuid);
      });
      let divCommentList = document.createElement('div');
      div.appendChild(divCommentList);
      let ul = document.createElement('ul');
      divCommentList.appendChild(ul);
      spanH.addEventListener('click', function(e){
        if(divCommentList.getAttribute('class') == 'show')
          divCommentList.removeAttribute('class');
        else
          divCommentList.setAttribute('class', 'show');
      });
      for(let i=0; i < content.length; i++){
        let date = new Date();
        date.setTime(content[i].date * 1000);

        let li = document.createElement('li');
        ul.appendChild(li);
        let div0 = document.createElement('div');
        div0.setAttribute('class', 'comment_d');
        li.appendChild(div0);
        let div1 = document.createElement('div');
        div1.setAttribute('class', 'comment_u');
        let span1 = document.createElement('span');
        div1.appendChild(span1);
        let div2 = document.createElement('div');
        div2.setAttribute('class', 'comment_c');
        let span2 = document.createElement('span');
        div2.appendChild(span2);
        span1.innerHTML = content[i].user + ' le ' + date.toLocaleString() + ' : ';
        span2.innerHTML = content[i].comment.replaceAll('\n','<br />');
        div0.appendChild(div1);
        div0.appendChild(div2);
      }
      return div;
    }
    static async noComment(){
      Utils.resp("Veuillez vous connecter pour laisser un commentaire.");
    }
    static async addComment(artUuid){
      let userConnected = false;
      let json;
      if(userLoad == null){
        json = await Fetch.get(config.api.uri + config.api.version + '/user/profile', await Fetch.auth());
        userLoad = json;
      }
      json = userLoad;
      if(json.ok)
        userConnected = true;
      if(!userConnected){
        this.noComment();
        return;
      }
      let tplHtml = await Fetch.get('/components/template/newComment.tpl');
      tplHtml = tplHtml.resp;
      let body = document.body;
      if(document.getElementById('new_comment') != null)
        return;
      let div = document.createElement('div');
      div.id = 'new_comment';
      div.innerHTML = tplHtml;
      div.classList.add('close');
      setTimeout(() => { div.classList.remove('close'); }, 1);
      body.appendChild(div);

      let userInfo = await Utils.getUserInfo();
      document.getElementById('comm_msg').innerHTML = userInfo.givenName;

      let close = document.getElementById('input_comment_close');
      close.addEventListener('click', function (e) {
        div.classList.add('close');
        setTimeout(() => { div.remove(); }, 100);
      });
      let send = document.getElementById('input_comment_send');
      send.addEventListener('click', function (e) {
        Comment.postComment(artUuid);
      });
    }
    static async postComment(artUuid){
      let info = await Utils.getUserInfo();
      let comment = document.getElementById('send_comment_text');
      let comment_value = comment.value.trim();
      comment_value = Seo.removeTags(comment_value);

      if((comment_value == 'false') || (comment_value == false )|| (comment_value.length < 10)){
        Utils.resp('Votre commentaire doit faire au moins 10 caractères.', 'new_comment_info');
        console.error('Comment to small');
        comment.focus();
        return;
      }
      let toPost = {
        'articleUuid': artUuid,
        'userInfo': await Ui.getUi(),
        'comment': comment_value,
      }
      let auth = await Fetch.auth();
      auth.body =  JSON.stringify(toPost);
      let {resp, ok} = await Fetch.post(config.api.uri + config.api.version + '/comment/article/' + artUuid, auth);
      if(!ok){
        let alert = document.getElementById('new_comment_alert');
        alert.classList.add('show');
      }else{
        let tplHtml = await Fetch.get('/components/template/newCommentConfirm.tpl');
        let newComm = document.getElementById('new_comment');
        newComm.innerHTML = tplHtml.resp;
        let close = document.getElementById('input_comment_close');
        close.addEventListener('click', function (e) {
          newComm.remove();
        });
      }

    }
  }
  class Utils {
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
    static aPreventDefault(a){
      a.addEventListener('click', (event) => {
        event.preventDefault();
        window.history.pushState(window.location.pathname, '', a.href);
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
          Ui.setUi();
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
  class Html {
    static resume(elm, content, title, img){
      content = Seo.removeTags(Seo.truncate(content.trim(), 800)) + ' ...';
      let sTitle = Seo.removeTags(title);
      content = content.replace(sTitle, '');
      elm.innerHTML = "";
      let resumeTitle = document.createElement('h2');
      resumeTitle.innerHTML = title;
      let resumeImg = document.createElement('img');
      resumeImg.src = img;
      resumeImg.setAttribute('alt', title);
      let resumeP = document.createElement('p');
      resumeP.innerHTML = content;
      resumeP.querySelectorAll("*[style]").forEach((element) => element.removeAttribute('style'));
      elm.appendChild(resumeTitle);
      elm.appendChild(resumeImg);
      elm.appendChild(resumeP);
    }
    static contentConv(elm, name, title){
      this.imgsConv(elm);
      this.H2(elm);
    }
    static H2(elm){
      let h2s = elm.getElementsByTagName('h2');
      for(let i=0; i < h2s.length; i++){
        h2s[i].removeAttribute('style');
      }
    }
    static imgsConv(elm, name, title){
      let imgs = elm.getElementsByTagName('img');
      for(let i=0; i < imgs.length; i++){
        this.imgConv(imgs[i], name, title, i);
      }
    }
    static imgConv(elm, name, title, i){
      elm.removeAttribute('width');
      elm.removeAttribute('height');
      if(elm.getAttribute('alt') == null){
        elm.setAttribute('alt', name + '_'+ title + i);
      }
      elm.addEventListener('click', function (e) {
        Image.open(this.src);
      });
    }
  }
  class Footer {
    constructor(info){
      let social = info.social;
      let footer = document.getElementById('footer');
      let f_social = document.getElementById('footer_social');
      f_social.innerHTML = '';
      for(let [key, value] of Object.entries(social)){
        let a = document.createElement('a');
        let img = document.createElement('img');
        a.appendChild(img);
        f_social.appendChild(a);

        a.href = value.url;
        a.setAttribute('title', value.title);
        a.setAttribute('target', "_SOCIAL");

        img.src = value.icon;
        img.setAttribute('alt', 'logo ' + value.name);
      }

      let cr = document.getElementById('div_copyright');
      let div = document.createElement('div');
      cr.appendChild(div);
      div.innerHTML = info.copyRight;
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
      if(!ok){
        Fetch.encKey = null;
        await Fetch.post(config.api.uri + config.api.version + '/ui', {
          body: JSON.stringify(ui)
        });
      }
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
          return {
            resp: resp,
            ok: response.ok
          }
        } catch (error){
          window.localStorage.clear();
          return {
            resp: null,
            ok: false
          };
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
  class Init{
    constructor(){
      this.loadConfig();
    }
    async loadConfig(){
      let json = await Fetch.get('/config/config.json?d=' + Date.now());
      document.getElementById('header').addEventListener('click', ()=>{
        window.location = "/";
      });
      await this.start(json.resp);
      this.end();
    }
    async start(json){
      config = json;
      window.mh.config = config;
      Utils.loadScript("/components/js/ident.js", "scriptIdent");
      Utils.loadScript("/components/js/vendor/simplewebauthn.js", "scriptWebauthn");
      info = new Info();
      await info.getInfo();
      content = new Content();
      menu = new Menu();

      if(document.location.pathname == '/reactive/' || document.location.pathname == '/supprimer/'){
        Utils.loadScript("/components/js/cpt.js", "scriptCpt");
      }else{
        gArticle = new Article();
      }

      prevUrl = window.location.href;
      setInterval(() => {
        let currUrl = window.location.href;
        if (currUrl != prevUrl) {
          if(window.history.state == null)
            return;
          prevUrl = currUrl;
          let footer = document.getElementById('footer');
          let div = document.createElement('div');
          div.id = "content";
          let submenu = document.getElementById('submenu');
          submenu.innerHTML = "";
          let wrap = document.getElementById('wrap');
          wrap.scrollTo(0,0);
          wrap.innerHTML = "";
          wrap.appendChild(div);
          wrap.appendChild(submenu);
          wrap.appendChild(footer);
          div.classList.add('minHeight');
          submenu.classList.add('hide');
          submenu.classList.remove('show');
          content = new Content();
          gArticle = new Article();
          Ui.setUi();
        }
      }, 60);
    }
    end(){
      let d = new Date();
      if(Seo.load.menu && Seo.load.article){
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
        }
      }else{
        window.localStorage.setItem('_updateInfos', d.getTime());
      }
    }
  }

  if(window.mh == undefined)
    window.mh = {};
  window.mh.staticJose = Jose;
  window.mh.fetch = Fetch;
  window.mh.Seo = Seo;
  window.mh.User = userLoad;
  new Init();
})()
