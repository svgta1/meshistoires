(function(){
  "use strict";
  class Utils{
    static parse(){
      let params = new URLSearchParams(document.location.search);
      return {
        code: params.get('code'),
        uuid: params.get('id')
      }
    }
    static async noContent(){
      let json = await Fetch.get('/components/template/nocontent.tpl');
      if(!json.ok)
        return;
      let wrap = document.getElementById('wrap');
      let footer = document.getElementById('footer');
      wrap.innerHTML = json.resp;
      wrap.appendChild(footer);
      console.error('Location', this.location + ' Not exist');
    }
  }
  class Init{
    parse(){
      let parse = Utils.parse();
      console.log(parse);
      if(parse.code == null || parse.uuid == null){
        Utils.noContent();
        return false;
      }
      this.url = config.api.uri + config.api.version + '/user/action/' + parse.uuid;
      this.body = {
        code: parse.code,
        action: null
      };
      return true;
    }
    react(){
      this.body.action = 'restore';
      this.aff('user_restore.tpl');
    }
    sup(){
      this.body.action = 'delete';
      this.aff('user_delete.tpl');
    }
    async aff(toLoad){
      let url = this.url + '?code=' + this.body.code + '&action=' + this.body.action;
      let json = await Fetch.get(url);
      if(!json.ok){
        Utils.noContent();
        return;
      }
      let tpl = await Fetch.get('/components/template/' + toLoad);
      if(!tpl.ok)
        return;
      let wrap = document.getElementById('wrap');
      let footer = document.getElementById('footer');
      wrap.innerHTML = tpl.resp;
      wrap.appendChild(footer);
    }
  }
  class Reac{
    async init(){
      console.log(Utils.parse());
    }
  }
  class Sup{
    async init(){

    }
  }
  var config = mh.config;
  var Fetch = mh.fetch;
  let path = document.location.pathname;
  let r = null;
  let init = new Init();
  let resp = init.parse();
  if(resp){
    switch(path){
      case "/reactive/":
        init.react();
        break;
      case "/supprimer/":
        init.sup();
        break;
      default:
        window.location = "/";
    }
  }

})();
