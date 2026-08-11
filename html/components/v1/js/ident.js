(function(){
  "use strict";
  class Utils {
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
        id = 'profile_info';
      let respInfo = document.getElementById(id);
      if(respInfo == undefined)
        return;
      respInfo.innerHTML = msg;
      respInfo.classList.add('show');
      setTimeout(() => { respInfo.innerHTML = ""; respInfo.classList.remove('show');}, 5000);
    }
    static email_validation(email){
      return email.match(
        /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
      );
    }
  }
  class Tpl {
    static async load(tpl, divId, closeId){
      if(document.getElementById(divId) != null)
        return;
      let tplHtml = await Fetch.get(tpl);
      if(!tplHtml.ok)
        return;
      let body = document.body;
      let div = document.createElement('div');
      div.id = divId;
      div.classList.add('close');
      setTimeout(() => { div.classList.remove('close'); }, 1);
      div.innerHTML = tplHtml.resp;
      body.appendChild(div);
      let close = document.getElementById(closeId);
      close.addEventListener('click', () => {
        div.classList.add('close');
        setTimeout(() => { div.remove(); }, 100);
      })
    }
    static async close(divId){
      let div = document.getElementById(divId);
      if(div !== undefined){
        div.classList.add('close');
        setTimeout(() => { div.remove(); }, 100);
      }
    }
  }
  class Ui {
    connected = false;
    switchConn(json) {
      let ident = document.getElementById("ident");
      if(json.ok){
        ident.innerHTML = json.resp.givenname.slice(0, 2).toUpperCase();
        ident.classList.remove('red');
        ident.classList.add('logged');
        ident.setAttribute('data-title', json.resp.givenname);
        this.connected = true;
        this.user = json.resp;
        mh.User = json;
      }else{
        ident.innerHTML = "⚤";
        ident.classList.add('red');
        ident.classList.remove('logged');
        mh.User = false;
      }
    }
    async getUI(){
      let json = await Fetch.get(config.api.uri + config.api.version + '/user/profile', await Fetch.auth());
      this.switchConn(json);
      let ident = document.getElementById("ident");
      ident.addEventListener('click', async () => {
        let json = await Fetch.get(config.api.uri + config.api.version + '/user/profile', await Fetch.auth());
        if(json.ok !== ui.connected){
          window.location.reload();
        }
        if(ui.connected){
          this.user = json.resp;
        }
        ui.getTpl();
      });
      ident.addEventListener('mouseover', () => {
        setTimeout(() => { ident.classList.add('hover'); }, 1000);
      });
      ident.addEventListener('mouseout', () => {
        ident.classList.remove('hover');
      });
    }
    async updateProfile(){
      let input = document.getElementsByTagName('input');
      let update = false;
      let newInfo = {};
      for(let i = 0; i < input.length; i++){
        let value = input[i].value.trim();
        value = mh.Seo.removeTags(value);
        value = mh.Seo.removeCharriot(value);
        if((input[i].id == 'profile_gn') && (value !== ui.user.givenname) && (value != false) && (value != 'false')){
          newInfo.givenname = value;
          update = true;
        }
        if((input[i].id == 'profile_sn') && (value !== ui.user.sn)){
          newInfo.sn = value;
          if((newInfo.sn == false) || (newInfo.sn == 'false'))
            newInfo.sn = "";
          update = true;
        }
        if((input[i].id == 'profile_abo') && (input[i].checked !== ui.user.abo_news)){
            newInfo.abo_news = input[i].checked;
            update = true;
        }
      }
      if(!update){
        Utils.resp("Aucune mise à jour à réaliser");
      }
      if(update){
        let authParam = await Fetch.auth();
        authParam.body = JSON.stringify(newInfo);
        let url = config.api.uri + config.api.version + '/user/profile';
        let json = await Fetch.put(url, authParam);
        if(json.ok){
          Utils.resp("Mise à jour réalisée");
          for(let [key, value] of Object.entries(json.resp)){
            ui.user[key] = value;
          }
        }else{
          Utils.resp("La mise à jour n'a pas pu être réalisée");
        }
      }
    }
    async contact_getResp(id, ul) {
      let url =  config.api.uri + config.api.version + '/contact/' + id;
      let json = await Fetch.get(url, await Fetch.auth());
      if(!json.ok)
        return;
      for(let i=0; i < json.resp.length; i++){
        let disc = json.resp[i];
        let li = document.createElement('li');
        ul.appendChild(li);
        li.classList.add('response');
        let divM = document.createElement('div');
        li.appendChild(divM);
        divM.classList.add('response');
        let spanM = document.createElement('span');
        divM.appendChild(spanM);
        spanM.innerHTML = Utils.formatDateHM(disc.msgTs);
        let pM = document.createElement('p');
        divM.appendChild(pM);
        pM.innerHTML = disc.msg.replaceAll('\n','<br />');
      }
    }
    async contact_list() {
      let url =  config.api.uri + config.api.version + '/contact';
      let json = await Fetch.get(url, await Fetch.auth());
      if(!json.ok){
        document.getElementById('contact_history').innerHTML = "Problème sur la récupération des discussions";
        return;
      }
      if(json.resp.length == 0){
        document.getElementById('contact_history').innerHTML = "Aucune discussion à afficher";
        return;
      }
      document.getElementById('contact_history').innerHTML = "";
      let ul = document.createElement('ul');
      document.getElementById('contact_history').appendChild(ul);
      for(let i=0; i < json.resp.length; i++){
        let disc = json.resp[i];
        let li = document.createElement('li');
        ul.appendChild(li);
        li.classList.add('contact');
        if(disc.hasResponse){
          let ulR = document.createElement('ul');
          li.appendChild(ulR);
          this.contact_getResp(disc.id, ulR);
        }
        let divM = document.createElement('div');
        li.appendChild(divM);
        divM.classList.add('contact');
        let spanM = document.createElement('span');
        divM.appendChild(spanM);
        spanM.innerHTML = Utils.formatDateHM(disc.msgTs);
        let pM = document.createElement('p');
        divM.appendChild(pM);
        pM.innerHTML = disc.msg.replaceAll('\n','<br />');
      }
    }
    async contact() {
      this.contact_list();
      let divId = 'user_contact';
      if(!this.connected)
        return;
      await Tpl.load('/components/template/user_contact.tpl', divId, 'close_contact');
      let send = document.getElementById('send_contact');
      send.addEventListener('click', async () => {
        let msg = document.getElementById('textarea_contact').value.trim();
        msg = mh.Seo.removeTags(msg);
        let auth = await Fetch.auth();
        let body = {
          contact: msg
        };
        auth.body = JSON.stringify(body);
        let json = await Fetch.post(config.api.uri + config.api.version + '/contact', auth);
        console.log(json);
        if(json.ok){
          document.getElementById('textarea_contact').value = "";
          this.contact_list();
          Utils.resp('Votre message a été envoyé', 'contact_info');
        }
      });
    }
    async comment() {
      let divId = 'user_contact';
      if(!this.connected)
        return;
      await Tpl.load('/components/template/user_comment.tpl', 'user_comment', 'close_comm');
      let json = await Fetch.get(config.api.uri + config.api.version + '/user/profile/comment', await Fetch.auth());
      if(!json.ok){
        document.getElementById('comm_new_version').remove();
        let div = document.createElement('div');
        div.innerHTML = "Une erreur est survenue. Veuillez réessayer dans quelques instants";
        document.getElementById('div_com_list').appendChild(div);
        return;
      }
      if(json.resp.length == 0){
        document.getElementById('comm_new_version').remove();
        let div = document.createElement('div');
        div.innerHTML = "Aucun commentaire posté";
        document.getElementById('div_com_list').appendChild(div);
        return;
      }
      let ul1 = document.createElement('ul');
      document.getElementById('comm_new_version').appendChild(ul1);
      for(let i = 0; i < json.resp.length; i++){
        this.createElementComment(json.resp[i], ul1);
      }
    }
    createElementComment(json, ul){
      let li = document.createElement('li');
      ul.appendChild(li);
      let table = document.createElement('table');
      li.appendChild(table);
      let article = document.createElement('tr');
      table.appendChild(article);
      let articleL1 = document.createElement('td');
      let articleL2 = document.createElement('td');
      article.appendChild(articleL1);
      article.appendChild(articleL2);
      articleL1.innerHTML = 'Article';
      articleL2.innerHTML = '<a href="/' + json.art.uri + '">' + json.art.menuName + '<span class="hearts">♥</span>' + json.art.artTitle + '</a>';
      let create = document.createElement('tr');
      table.appendChild(create);
      let create1 = document.createElement('td');
      let create2 = document.createElement('td');
      create.appendChild(create1);
      create.appendChild(create2);
      create1.innerHTML = "Date du commentaire"
      let date = new Date();
      date.setTime(json.dateCreate * 1000);
      create2.innerHTML = date.toLocaleString();
      let valide = document.createElement('tr');
      table.appendChild(valide);
      let valide1 = document.createElement('td');
      let valide2 = document.createElement('td');
      valide.appendChild(valide1);
      valide.appendChild(valide2);
      valide1.innerHTML = "Validé administration";
      let valideL = "Pas encore";
      if(json.valide)
        valideL = "Oui";
      valide2.innerHTML = valideL;
      let comm = document.createElement('tr');
      table.appendChild(comm);
      let comm1 = document.createElement('td');
      let comm2 = document.createElement('td');
      comm.appendChild(comm1);
      comm.appendChild(comm2);
      comm2.classList.add('commentaire')
      comm1.innerHTML = "Votre commentaire";
      comm2.innerHTML = json.msg.replaceAll('\n','<br />');
    }
    async createKey() {
      let value = document.getElementById('profile_addK_label').value;
      value = mh.Seo.removeTags(value);
      value = mh.Seo.removeCharriot(value);
      if(value == 'false' || value == false || value.length < 3){
        Utils.resp("Le nom de la clé doit faire au moins 3 caractères");
        return;
      }

      let enregUrl = config.api.uri + config.api.version + '/auth/webauthn/enreg';
      let { startRegistration } = SimpleWebAuthnBrowser;
      let json = await Fetch.get(enregUrl, await Fetch.auth());
      if(!json.ok){
        Utils.resp("Une erreur est survenue");
        return;
      }
      let attResp;
      try {
        attResp = await startRegistration(json.resp);
      } catch (error) {
        Utils.resp("La clé n'a pas pu être enregistrée");
        throw error;
      }
      let authParam = await Fetch.auth();
      attResp.keyName = value;
      authParam.body = JSON.stringify(attResp);
      let verif = await Fetch.post(enregUrl, authParam);
      if(verif.ok){
        Utils.resp("Votre clé à été créée. Le profile va se fermer pour prise en compte.");
        setTimeout(() => { Tpl.close('user_info'); }, 3000);
      }else{
        Utils.resp("La clé n'a pas pu être enregistrée");
      }
    }
    async logout() {
      let json = await Fetch.delete(config.api.uri + config.api.version + '/auth', await Fetch.auth());
      if(json.ok){
        window.localStorage.removeItem('_ua');
        window.location.reload();
      }
    }
    async history() {
      let url = config.api.uri + config.api.version + '/user/history';
      let json = await Fetch.get(url, await Fetch.auth());
      if(!json.ok)
        return;
      let table = document.getElementById('profile_history');
      for(let i = 0; i < json.resp.length; i++){
        let tr = document.createElement('tr');
        table.appendChild(tr);
        let td1 = document.createElement('td');
        let td2 = document.createElement('td');
        tr.appendChild(td1);
        tr.appendChild(td2);
        let path = json.resp[i].path;
        let ts = json.resp[i].timestamp;
        td2.innerHTML = '<a href="'+path+'">'+path+'</a>';
        td1.innerHTML = Utils.formatDateHM(ts);
      }
    }
    async profile() {
      this.history();
      await mh.staticJose.getJWS();
      let scopes = mh.staticJose.JWS_payload.scope.split(' ');
      let isAdmin = false;
      for(let i = 0; i < scopes.length; i++){
        let s = scopes[i].split(':');
        if(s[0] == 'admin'){
          isAdmin = true;
          break;
        }
      }
      if(isAdmin){
        let adminAccess = document.createElement('button');
        document.getElementById('profile_ui').appendChild(adminAccess);
        adminAccess.classList.add('orange');
        adminAccess.innerHTML = "Accéder à l'administration";
        adminAccess.addEventListener('click', () => {
          window.open(config.default.admin, "_blank").focus();
        });
      }
      document.getElementById('profile_gn').value = this.user.givenname;
      document.getElementById('profile_sn').value = this.user.sn;
      document.getElementById('profile_mail').innerHTML = this.user.mail;
      document.getElementById('profile_dc').innerHTML = Utils.formatDate(this.user.dateCreate);
      document.getElementById('profile_dm').innerHTML = Utils.formatDate(this.user.dateUpdate);
      document.getElementById('profile_abo').checked = this.user.abo_news;

      let keySec = this.user.sec_keys;
      let table = document.getElementById('profile_sec');
      for(let i = 0; i < keySec.length; i++){
        if(keySec[i] == undefined)
          continue;
        if(keySec[i].hasOwnProperty('name') == false)
          continue;
        let tr = document.createElement('tr');
        table.appendChild(tr);
        let td1 = document.createElement('td');
        tr.appendChild(td1);
        let td2 = document.createElement('td');
        tr.appendChild(td2);
        td1.innerHTML = keySec[i].name;

        let btn = document.createElement('button');
        btn.innerHTML = 'Supprimer la clé';
        btn.classList.add("del");
        td2.appendChild(btn);

        btn.addEventListener('click', async function(e){
          let authParam = await Fetch.auth();
          authParam.body = JSON.stringify({key: keySec[i].credentialId});
          let url = config.api.uri + config.api.version + '/user/key';
          let json = await Fetch.delete(url, authParam);
          if(json.ok){
            tr.remove();
            let respInfo = document.getElementById('profile_info');
            respInfo.classList.add('show');
            respInfo.innerHTML = 'Clé "' + keySec[i].name + '" supprimée';
            setTimeout(() => { respInfo.innerHTML = ""; respInfo.classList.remove('show');}, 5000);
            delete keySec[i];
          }
        });
      }
      document.getElementById('profile_update').addEventListener('click', this.updateProfile.bind(this));
      document.getElementById('profile_contact').addEventListener('click', this.contact.bind(this));
      document.getElementById('profile_comment').addEventListener('click', this.comment.bind(this));
      document.getElementById('profile_addK').addEventListener('click', this.createKey.bind(this));
      document.getElementById('profile_logout').addEventListener('click', this.logout.bind(this));
    }
    async setUi(resp){
      window.localStorage.setItem('_ua', resp);
    }
    async authOidc() {
      let url = config.api.uri + config.api.version + '/auth/oidc/' + this.id;
      let json = await Fetch.get(url);
      if(!json.ok){
        Utils.resp("Erreur sur la récupération des informations fournisseur", "conn_info");
        return;
      }
      window.localStorage.setItem('lastPath', window.location.href);
      document.location = json.resp;
    }
    async authWebauthn() {
      let { startAuthentication } = SimpleWebAuthnBrowser;
      let uri = config.api.uri + config.api.version + '/auth/webauthn';

      let json = await Fetch.get(uri);
      if(!json.ok){
        Utils.resp("L'action a échoué", "conn_info");
        return;
      }
      let asseResp;
      try {
        asseResp = await startAuthentication(json.resp);
      } catch (error) {
        Utils.resp("La récupération des informations a échoué", "conn_info");
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
        Utils.resp("Vous êtes authentifié.<br /> La page va se recharger.", "conn_info");
        setTimeout(() => { window.location.reload(); }, 3000);
      }else{
        Utils.resp("L'authentification a échoué", "conn_info");
      }
    }
    async authCode(){
      let div = document.getElementById('conn_authList')
      let parent = div.parentElement;
      let json = await Fetch.get('/components/template/authCode.tpl');
      if(!json.ok)
        return;
      div.hidden = true;
      parent.innerHTML = json.resp;
      document.getElementById('auth_code_valid').addEventListener('click', async ()=>{
        let mail = document.getElementById('auth_code_mail').value.trim();
        if(!Utils.email_validation(mail)){
          Utils.resp("Email non valide", "conn_info");
          return;
        }
        let url = config.api.uri + config.api.version + '/auth/code';
        let json = await Fetch.post(url, {
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            email: mail
          })
        });
        if(!json.ok){
          Utils.resp("Le mail n'a pas pu être envoyé", "conn_info");
          return;
        }else{
          Utils.resp("Le mail a été envoyé", "conn_info");
          document.getElementById('auth_code_valid').remove();
          document.getElementById('auth_code_mail').disabled = true;
        }
      });

      document.getElementById('auth_code_connect').addEventListener('click', async ()=>{
        let mail = document.getElementById('auth_code_mail').value.trim();
        if(!Utils.email_validation(mail)){
          Utils.resp("Email non valide", "conn_info");
          return;
        }
        let code = document.getElementById('auth_code_code').value.trim();
        code = mh.Seo.removeTags(code);
        code = mh.Seo.removeCharriot(code);
        let nom = document.getElementById('auth_code_name').value.trim();
        nom = mh.Seo.removeTags(nom);
        nom = mh.Seo.removeCharriot(nom);

        let url = config.api.uri + config.api.version + '/auth/code';
        let json = await Fetch.put(url, {
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            email: mail,
            name: nom,
            code: code
          })
        });
        if(!json.ok){
          Utils.resp("Une erreur est survenue : " + JSON.stringify(json.resp), "conn_info");
          return;
        }else{
          await this.setUi(json.resp);
          Utils.resp("Vous êtes authentifié.<br /> La page va se recharger.", "conn_info");
          setTimeout(() => { window.location.reload(); }, 3000);
        }
      });
      document.getElementById('conn_close').addEventListener('click', async ()=>{
        Tpl.close('user_conn');
      });
    }
    async conn() {
      let url = config.api.uri + config.api.version + '/auth/list';
      let json = await Fetch.get(url);
      if(!json.ok)
        return;
      let list = json.resp;
      let oidc = [];
      let webauthn = null;
      let code = null;
      for(let i = 0; i < list.length; i++){
        if(list[i].type == 'oidc')
          oidc.push(list[i]);
        if(list[i].type == 'code')
          code = list[i];
        if(list[i].type == 'webauthn')
          webauthn = list[i];
      }
      let divList = document.getElementById('conn_authList');
      if(webauthn !== null){
        let div = document.createElement('div');
        divList.appendChild(div);
        let h3 = document.createElement('h3');
        div.appendChild(h3);
        h3.innerHTML = "Sécurisation forte";
        //h3.classList.add('green');
        let btn = document.createElement('button');
        div.appendChild(btn);
        btn.innerHTML = webauthn.desc;
        btn.classList.add('send');
        btn.addEventListener('click', this.authWebauthn.bind(this));
      }
      if(oidc.length > 0 ){
        let div = document.createElement('div');
        divList.appendChild(div);
        let h3 = document.createElement('h3');
        div.appendChild(h3);
        h3.innerHTML = "Via fournisseur d'identité";
        //h3.classList.add('blue');
        for(let i = 0; i < oidc.length; i++){
          let fi = document.createElement('button');
          div.appendChild(fi);
          fi.innerHTML = oidc[i].desc;
          fi.classList.add('send');
          fi.addEventListener('click', this.authOidc.bind(oidc[i]));
        }
      }
      if(code !== null){
        let div = document.createElement('div');
        divList.appendChild(div);
        let h3 = document.createElement('h3');
        div.appendChild(h3);
        h3.innerHTML = "Via email";
        //h3.classList.add('orange');
        let btn = document.createElement('button');
        div.appendChild(btn);
        btn.innerHTML = code.desc;
        btn.classList.add('send');
        btn.addEventListener('click', this.authCode.bind(this));
      }
    }
    async getTpl(){
      if(this.connected){
        await Tpl.load('/components/template/profile.tpl', 'user_info', 'profile_close');
        this.profile();
      }else{
        await Tpl.load('/components/template/connect.tpl', 'user_conn', 'conn_close');
        this.conn();
      }
    }
  }
  var Fetch = mh.fetch;
  var config = mh.config;
  var ui = new Ui();
  ui.getUI();
})();
