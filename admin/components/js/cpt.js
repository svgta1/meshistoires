(function(){
  "use strict";
  let Utils = window.mh.Utils;
  Utils.clean = function(value){
    value = value.trim();
    value = mh.Seo.removeTags(value);
    value = mh.Seo.removeCharriot(value);
    return value;
  }
  Utils.compare = function(v1, v2){
    let dif = false;
    if( v1 !== v2){
      dif = true;
    }
    return dif;
  }
  class Cpt {
    cptTemplate = null;
    delete = {
      init: function(json){
        let text = "";
        switch(json.delType){
          case 'desactivation' :
            text = "<span class='red'>Désactivation utilisateur : </span>";
            text += '<br> L\'utilisateur sera alerté par mail de la désactivation de son compte.';
            text += '<br> Le compte pourra être restoré par l\'utilisateur.';
            break;
          case 'delete' :
            text = "<span class='red'>Suppression utilisateur : </span>";
            text += '<br> Le compte sera définitivement supprimé sans possibilité de restauration.';
            break;
          case 'ban' :
            text = "<span class='red'>Bannissement utilisateur : </span>";
            text += '<br> Le compte sera banni.';
            text += 'Le bannissement empêche un utilisateur de recréer un compte avec la même adresse email';
            break;
          default:
            return;
        }
        Utils.validChange(text, this, '', json);
      },
      valid: async function(change, json){
        let auth = await Fetch.auth();
        auth.body = JSON.stringify({
          delType: json.delType
        });
        let fetch = await Fetch.delete(apiUri + '/admin/contact/' + json.uuid, auth);
        if(!fetch.ok){
          Utils.resp('Un problème est survenu. l\'opération est annulée');
          return;
        }
        Utils.resp('Opération réalisée. La page va se recharger dans 4 sec.');
        setTimeout(() => {document.location.reload();}, 4000);
      },
      cancel: function(change, json){
        Utils.resp('Opération annulée.');
      }
    }
    restore = {
      init: function(json){
        Utils.validChange('Restauration du compte <b>' + json.givenname + '</>', this, '', json);
      },
      valid: async function(change, json){
        let auth = await Fetch.auth();
        let fetch = await Fetch.put(apiUri + '/admin/contact/restore/' + json.uuid, auth);
        if(!fetch.ok){
          Utils.resp('Un problème est survenu. l\'opération est annulée');
          return;
        }
        Utils.resp('Restauration réalisée. La page va se recharger dans 4 sec.');
        setTimeout(() => {document.location.reload();}, 4000);
      },
      cancel: function(change, json){
        Utils.resp('Restauration annulée.');
      }
    }
    update = {
      init: function(json){
        let doUpdate = false;
        let givenname = Utils.clean(document.getElementById('cpt_givenname_' + json.uuid).value);
        let sn = Utils.clean(document.getElementById('cpt_sn_' + json.uuid).value);
        let mail = Utils.clean(document.getElementById('cpt_mail_' + json.uuid).value);
        if(!Utils.email_validation(mail)){
          console.error('bad email');
          return;
        }
        let abo_news = document.getElementById('cpt_abo_news_' + json.uuid).checked;
        if(typeof abo_news !== "boolean"){
          console.error("Bad type");
          return;
        }
        let toUpdate = {};
        if(Utils.compare(givenname, json.givenname)){
          doUpdate = true;
          toUpdate['givenname'] = givenname;
        }
        if(Utils.compare(sn, json.sn)){
          doUpdate = true;
          toUpdate['sn'] = sn;
        }
        if(Utils.compare(mail, json.mail)){
          doUpdate = true;
          toUpdate['mail'] = mail;
        }
        if(Utils.compare(abo_news, json.abo_news)){
          doUpdate = true;
          toUpdate['abo_news'] = abo_news;
        }
        if(!doUpdate){
          Utils.resp('Aucune information à mettre à jour');
          return;
        }
        Utils.validChange('Mise à jour du compte <b>' + json.givenname + '</b>', this, toUpdate, json);
      },
      valid: async function(toUpdate, json){
        let auth = await Fetch.auth();
        auth.body = JSON.stringify(toUpdate);
        let fetch = await Fetch.put(apiUri + '/admin/contact/' + json.uuid, auth);
        if(!fetch.ok){
          Utils.resp('Un problème est survenu. Mise à jour annulée');
          return;
        }
        Utils.resp('Mise à jour réalisée. La page va se recharger dans 4 sec.');
        setTimeout(() => {document.location.reload();}, 4000);
      },
      cancel: function(toUpdate, json){
        Utils.resp('Mise à jour annulée');
        for (let [key, value] of Object.entries(toUpdate)){
          if(typeof value === "boolean"){
            document.getElementById('cpt_'+ key +'_' + json.uuid).checked = json[key];
            continue;
          }
          document.getElementById('cpt_'+ key +'_' + json.uuid).value = json[key];
        }
      }
    }
    async load(json){
      let tpl = this.cptTemplate;
      tpl = tpl.replaceAll('##UUID##', json.uuid);
      let div = document.createElement('div');
      div.id = 'admin_cpt_' + json.uuid;
      document.getElementById('admin_cpt').appendChild(div);
      div.innerHTML = tpl;
      document.getElementById('cpt_givenname_' + json.uuid).value = json.givenname;
      document.getElementById('cpt_sn_' + json.uuid).value = json.sn;
      document.getElementById('cpt_mail_' + json.uuid).value = json.mail;
      document.getElementById('cpt_abo_news_' + json.uuid).checked = json.abo_news;
      document.getElementById('cpt_dateCreate_' + json.uuid).innerHTML = Utils.formatDateHM(json.dateCreate);
      document.getElementById('cpt_dateUpdate_' + json.uuid).innerHTML = Utils.formatDateHM(json.dateUpdate);
      document.getElementById('cpt_nbrSec_' + json.uuid).innerHTML = json.nbr_keySec;
      document.getElementById('cpt_deleted_' + json.uuid).innerHTML = json.deleted ? '<span class="red">Oui</span>' : '<span class="blue">Non</blue>';
      document.getElementById('cpt_ban_' + json.uuid).innerHTML = json.ban ? '<span class="red">Oui</span>' : '<span class="blue">Non</blue>';

      let change = document.createElement('button');
      change.innerHTML = 'Mettre à jour';
      div.appendChild(change);
      change.addEventListener('click', () => {
        this.update.init(json);
      });
      if(!json.deleted){
        let supprimerMail = document.createElement('button');
        supprimerMail.innerHTML = 'Désactivation';
        supprimerMail.classList.add('orange');
        div.appendChild(supprimerMail);
        supprimerMail.addEventListener('click', () => {
          json.delType = 'desactivation';
          this.delete.init(json);
        });
      }else{
        let rest = document.createElement('button');
        rest.innerHTML = 'Restaurer le compte';
        rest.classList.add('green');
        div.appendChild(rest);
        rest.addEventListener('click', () => {
          this.restore.init(json);
        });
      }

      let br = document.createElement('br');
      div.appendChild(br);
      if(!json.ban){
        let ban = document.createElement('button');
        ban.innerHTML = 'Bannissement';
        ban.classList.add('red');
        div.appendChild(ban);
        ban.addEventListener('click', () => {
          json.delType = 'ban';
          this.delete.init(json);
        });
      }
      let supprimer = document.createElement('button');
      supprimer.innerHTML = 'Sup définitive';
      supprimer.classList.add('red');
      div.appendChild(supprimer);
      supprimer.addEventListener('click', () => {
        json.delType = 'delete';
        this.delete.init(json);
      });
    }
    async init(){
      let _tpl = await Fetch.get(mh.config.adminPath + '/components/template/cpt_template.tpl');
      if(_tpl.ko){
        console.error('template inexistant');
        return
      }
      this.cptTemplate = _tpl.resp;
      let json = await Fetch.get(apiUri + '/admin/contacts', await Fetch.auth());
      if(json.ok){
        for (let i = 0; i < json.resp.length; i++)
          this.load(json.resp[i]);
      }
    }
  }
  let apiUri = window.mh.apiUri;
  let Fetch = window.mh.Fetch;
  let cpt = new Cpt();
  cpt.init();
})()
