<div id="auth_code">
  <h2>Authentification par code</h2>
  <div id="conn_info"></div>
  <div id="auth_code_prep">
    <div>
      <label for="auth_code_mail">Renseignez votre email : </label><input type="text" id="auth_code_mail" placeholder="Email" />
      <button id="auth_code_valid" class="send">Recevoir le code</button>
    </div>
    <div>
      <label for="auth_code_code">Code reçu : </label><input type="text" id="auth_code_code" placeholder="saisir le code" /><br />
      <label for="auth_code_name">Votre nom : </label><input type="text" id="auth_code_name" placeholder="votre nom" />
      <button id="auth_code_connect" class="send">Se connecter</button><br />
      <span class="required">Veuillez saisir un nom d'affichage s'il s'agit de votre premier accès.</span>
    </div>
  </div>
  <div id="conn_div_close">
    <button id="conn_close">Annuler</button>
  </div>
</div>
