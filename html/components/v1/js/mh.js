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
  async function init(){
    let config = '/config/config.json?d=' + Date.now();
    let resp = await fetch(config);
    if(!resp.ok){
      console.error('config file not found');
      return;
    }
    gConfig = window.mh.config = await resp.json();
    loadPreloadScript('/components/js/main.js');
    loadModuleScript('/components/js/jose.js');
  }

  if(window.mh == undefined)
    window.mh = {};
  init();
})()