(function(){
  "use strict";
  let Utils = window.mh.Utils;
  let apiUri = window.mh.apiUri;
  let Fetch = window.mh.Fetch;

  class Comm{
    tplList = null;
    valid = {
      init: function(uuid){
        Utils.validChange('Confirmez la validation du commentaire', this, '', uuid);
      },
      valid: async function(change, uuid){
        let fetch = await Fetch.put(apiUri + '/admin/com/' + uuid, await Fetch.auth());
        if(!fetch.ok){
          Utils.resp('Un problème est survenu. La validation est annulée');
          return;
        }
        Utils.resp('Validation du commentaire réalisée. La page va se recharger dans 4 sec.');
        setTimeout(() => {document.location.reload();}, 4000);
      },
      cancel: function(change, uuid){
        Utils.resp('Validation annulée.');
      }
    }
    deValid = {
      init: function(uuid){
        Utils.validChange('Confirmez la dévalidation du commentaire', this, '', uuid);
      },
      valid: async function(change, uuid){
        let fetch = await Fetch.put(apiUri + '/admin/com/' + uuid, await Fetch.auth());
        if(!fetch.ok){
          Utils.resp('Un problème est survenu. La dévalidation est annulée');
          return;
        }
        Utils.resp('Dévalidation du commentaire réalisée. La page va se recharger dans 4 sec.');
        setTimeout(() => {document.location.reload();}, 4000);
      },
      cancel: function(change, uuid){
        Utils.resp('Dévalidation annulée.');
      }
    }
    sup = {
      init: function(uuid){
        Utils.validChange('Confirmez la suppression du commentaire', this, '', uuid);
      },
      valid: async function(change, uuid){
        let fetch = await Fetch.delete(apiUri + '/admin/com/' + uuid, await Fetch.auth());
        if(!fetch.ok){
          Utils.resp('Un problème est survenu. La suppression est annulée');
          return;
        }
        Utils.resp('Suppression du commentaire réalisée. La page va se recharger dans 4 sec.');
        setTimeout(() => {document.location.reload();}, 4000);
      },
      cancel: function(change, uuid){
        Utils.resp('Suppression annulée.');
      }
    }
    button = {
      valid: function(elm, uuid){
        let btn = document.createElement('button');
        elm.appendChild(btn);
        btn.innerHTML = "Valider";
        btn.addEventListener('click', () => {
          com.valid.init(uuid);
        });
      },
      deValid: function(elm, uuid){
        let btn = document.createElement('button');
        elm.appendChild(btn);
        btn.innerHTML = "Dévalider";
        btn.classList.add('green');
        btn.addEventListener('click', () => {
          com.deValid.init(uuid);
        });
      },
      sup: function(elm, uuid){
        let btn = document.createElement('button');
        elm.appendChild(btn);
        btn.innerHTML = "Supprimer";
        btn.classList.add('red');
        btn.addEventListener('click', () => {
          com.sup.init(uuid);
        });
      },
      defSup: function(elm, uuid){
        let btn = document.createElement('button');
        elm.appendChild(btn);
        btn.innerHTML = "Supprimer définitivement";
        btn.classList.add('red');
        btn.addEventListener('click', () => {
          com.sup.init(uuid);
        });
      }
    }
    loadPerType(id, list){
      let pDiv = document.getElementById(id);
      for(let i = 0; i < list.length; i++){
        let div = document.createElement('div');
        pDiv.appendChild(div);
        let table = document.createElement('table');
        div.appendChild(table);
        let tpl = this.tplList;
        list[i].msg = list[i].msg.trim();
        list[i].msg = list[i].msg.replaceAll('\n','<br>');
        list[i].dateCreate = Utils.formatDateHM(list[i].dateCreate);
        list[i].dateUpdate = Utils.formatDateHM(list[i].dateUpdate);
        for(let [key, value] of Object.entries(list[i])){
          tpl = tpl.replaceAll('##' + key + '##', value);
        }
        table.innerHTML = tpl;
        if(!list[i].valide && !list[i].deleted){
          this.button.valid(div, list[i].uuid);
          this.button.sup(div, list[i].uuid);
          continue;
        }
        if(list[i].valide && !list[i].deleted){
          this.button.deValid(div, list[i].uuid);
          this.button.sup(div, list[i].uuid);
          continue;
        }
        if(list[i].deleted){
          this.button.defSup(div, list[i].uuid);
        }
      }
    }
    async init(){
      let _tpl = await Fetch.get(mh.config.adminPath + '/components/template/com_tplList.tpl');
      if(_tpl.ko){
        console.error('template inexistant');
        return
      }
      this.tplList = _tpl.resp;
      let json = await Fetch.get(apiUri + '/admin/com/list', await Fetch.auth());
      if(!json.ok){
        Utils.resp('Erreur sur le chargement des commentaires');
        return;
      }
      this.loadPerType('admin_com_tab_nonvalid', json.resp.nonValide);
      this.loadPerType('admin_com_tab_valid', json.resp.valide);
      this.loadPerType('admin_com_tab_sup', json.resp.sup);
    }
  }
  let com = new Comm();
  com.init();
})()
