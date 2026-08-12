(function(){
  function loadPreloadScript(src){
    let htmlHead = document.getElementsByTagName('head')[0];
    let script = document.createElement('script');
    script.setAttribute('rel', "preload");
    script.setAttribute('as', "script");
    script.src = src + '?v=' + gConfig.version;
    htmlHead.appendChild(script);
  }
  function loadModuleScript(src){
    let htmlHead = document.getElementsByTagName('head')[0];
    let script = document.createElement('script');
    script.setAttribute('type', "module");
    script.setAttribute('async', '');
    script.src = src + '?v=' + gConfig.version;
    htmlHead.appendChild(script);
  }
  async function getConf(ress, version){
    console.log(version);
    let resp = await ress;
    if(!resp.ok){
      console.error('config file not found');
      return;
    }
    gConfig = await resp.json();
    if(gConfig.modeDev)
      gConfig.version = Date.now();
    else
      gConfig.version = version;
    window.mh.config = gConfig;
    window.mh.components = gConfig.components;
    loadPreloadScript(window.mh.components + 'js/main.js');
    //loadModuleScript(window.mh.components + 'js/jose.js');
  }
  async function init(){
    let config = '/config/config.json?d=' + Date.now();
    let version = '/config/version.json?d=' + Date.now();
    let respConfig = fetch(config);
    let respVersion = await fetch(version);
    if(!respVersion.ok){
      console.error('config file not found');
      return;
    }
    gVersion = await respVersion.json();
    getConf(respConfig, gVersion.version);
  }

  if(window.mh == undefined)
    window.mh = {};
  init();
})()