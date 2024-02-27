(function(){
    "use strict";
    let Utils = window.mh.Utils;
    let apiUri = window.mh.apiUri;
    let Fetch = window.mh.Fetch;

    class Msg{
        send = {
            init: function(json, div){
                let textarea = div.getElementsByTagName('textarea')[0];
                let msg = div.getElementsByTagName('textarea')[0].value;
                textarea.disabled = true;
                if(msg.length < 3){
                    console.error('message trop court');
                    Utils.resp('Le message doit faire plus de 3 caractères.');
                    return;
                }
                json.msg = mh.Seo.removeTags(msg.trim());
                Utils.validChange('Confirmez l\'envoi du commentaire', this, textarea, json);
            },
            valid: async function(change, json){
                let auth = await Fetch.auth();
                auth.body = JSON.stringify({
                    msg: json.msg
                });
                let fetch = await Fetch.post(apiUri + '/admin/msg/' + json.art, auth);
                if(!fetch.ok){
                    Utils.resp('Une erreur s\'est produite. Le message n\'a pu être envoyé');
                    change.disabled = false;
                    return;
                }
                Utils.resp('Le message a été envoyé. La page va se recharger dans 4 secondes.');
                change.disabled = false;
                change.value = "";
                setTimeout(() => {document.location.reload();}, 4000);
            },
            cancel: function(change, json){
                Utils.resp('L\'envoi est annulé');
                change.disabled = false;
            }
        }
        setDiv(li, type, json){
            let div = document.createElement('div');
            div.classList.add(type);
            li.appendChild(div);
            let span = document.createElement('span');
            div.appendChild(span);
            span.innerHTML = Utils.formatDateHM(json.createTs);
            if(type == 'response')
                span.innerHTML += ' par ' + json.user.name;
            let p = document.createElement('p');
            div.appendChild(p);
            p.innerHTML = json.msg.replaceAll('\n','<br>');
        }
        loadResp(li, json){
            let ul = document.createElement('ul');
            li.appendChild(ul);
            for(let i = 0; i < json.length; i++){
                let li = document.createElement('li');
                li.classList.add('response');
                ul.appendChild(li);
                this.setDiv(li, 'response', json[i]);
            }
        }
        loadMsg(list){
            document.getElementById('contact_info_name_' + list.user.uuid).innerHTML = list.user.name;
            document.getElementById('contact_info_mail_' + list.user.uuid).innerHTML = list.user.mail;
            let ul = document.getElementById('contact_history_' + list.user.uuid);
            for(let i = 0; i < list.msg.length; i++){
                let li = document.createElement('li');
                li.classList.add('contact');
                ul.appendChild(li);
                if(list.msg[i].hasResponse)
                    this.loadResp(li, list.msg[i].responses);
                this.setDiv(li, 'contact', list.msg[i]);
            }
        }
        loadUser(json){
            let sort = json.sort;
            let div = document.getElementById("admin_msg");
            for(let i=0; i < sort.length; i++){
                let tpl = this.tplList;
                let _div = document.createElement('div');
                div.appendChild(_div);
                tpl = tpl.replaceAll('##uuid##', sort[i]);
                _div.innerHTML = tpl;
                this.loadMsg(json.list[sort[i]]);
                let button = _div.getElementsByTagName('button')[0];
                button.addEventListener('click', ()=>{
                    let _json = {
                        art: json.list[sort[i]].msg[0].uuid,
                    }
                    msg.send.init(_json, _div);
                });
            }
        }
        async init(){
            let _tpl = await Fetch.get(mh.config.adminPath + '/components/template/msg_list.tpl');
            if(_tpl.ko){
                console.error('template inexistant');
                return
            }
            this.tplList = _tpl.resp;
            let json = await Fetch.get(apiUri + '/admin/msg/list', await Fetch.auth());
            if(!json.ok){
                Utils.resp('Erreur sur le chargement des messages');
                return;
            }
            this.loadUser(json.resp);
        }
    }
    let msg = new Msg();
    msg.init();
})()