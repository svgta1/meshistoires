(function(){
  "use strict";
  let Utils = window.mh.Utils;
  let apiUri = window.mh.apiUri;
  let Fetch = window.mh.Fetch;
  let Seo = window.mh.Seo;
  class Imgs{
    delete = {
      init: function(uuid){
        Utils.validChange('Validez la suppression de l\'image', this, {}, uuid);
      },
      valid: async function(toUpdate, uuid){
        let json = await Fetch.delete(apiUri + '/admin/image/' + uuid, await Fetch.auth());
        if(!json.ok){
          Utils.resp('La suppression n\'a pas pu se faire');
          return;
        }
        document.getElementById('image_' + uuid).remove();
        Utils.resp('Suppression réalisée');
      },
      cancel: function(toUpdate, uuid){
        Utils.resp('Suppression annulée');
      }
    }
    loadImg(json){
      let uri = mh.config.api.uri + mh.config.api.version + '/imageThumb/' + json.value;
      let ul = document.getElementById('admin_imgs_list');
      let li = document.createElement('li');
      ul.appendChild(li);
      li.id = 'image_' + json.value;
      let div = document.createElement('div');
      li.appendChild(div);
      let tpl = this.tpl_info;
      tpl = tpl.replace('##title##', json.title);
      tpl = tpl.replace('##uri##', uri);
      div.innerHTML = tpl;
      let button = document.createElement('button');
      div.appendChild(button);
      button.classList.add('red');
      button.innerHTML = "Supprimer l'image";
      button.addEventListener('click', ()=>{
        this.delete.init(json.value);
      });
      let img = div.getElementsByTagName('img')[0];
      img.addEventListener('click', ()=>{
        let div = document.createElement('div');
        div.id = "aff_img";
        document.getElementById('footer').appendChild(div);
        div.addEventListener('click', ()=>{
          div.remove();
        });
        let img = document.createElement('img');
        div.appendChild(img);
        img.src = mh.config.api.uri + mh.config.api.version + '/image/' + json.value;
      });
    }
    async getList(skip){
      let json = await Fetch.get(apiUri + '/admin/images?skip=' + skip, await Fetch.auth());
      if(!json.ok){
        Utils.resp('Erreur sur le chargement des images');
        return;
      }
      for(let i=0; i < json.resp.count; i++)
        this.loadImg(json.resp.list[i]);
      let count = skip + json.resp.count;
      if(count < json.resp.total)
        this.getList(count);
    }
    async init(){
      let tpl = await Fetch.get(mh.config.adminPath + '/components/template/img_info.tpl');
      if(!tpl.ok){
        console.error('tpl ne peut pas être chargé')
        return;
      }
      this.tpl_info = tpl.resp;
      this.getList(0);
    }
  }
  let imgs = new Imgs();
  imgs.init();
})()