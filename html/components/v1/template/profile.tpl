<div>
  <div id="profile">
    <h2>Votre Profil</h2>
    <div id="profile_info">
    </div>
    <div id="profile_ui">
      <h3>Vos informations</h3>
      <table>
        <tr>
          <td><label for="profile_gn">Nom d'affichage</label></td>
          <td><input type="text" id="profile_gn" value="" placeholder="Nom affichage"/></td>
        </tr>
        <tr>
          <td><label for="profile_sn">Nom réel</label></td>
          <td><input type="text" id="profile_sn" value="" placeholder="Nom"/></td>
        </tr>
        <tr>
          <td>Email</td>
          <td><span id="profile_mail"></span></td>
        </tr>
        <tr>
          <td>Date de création</td>
          <td><span id="profile_dc"></span></td>
        </tr>
        <tr>
          <td>Date de mise à jour</td>
          <td><span id="profile_dm"></span></td>
        </tr>
        <tr>
          <td><label for="profile_abo">Abonnement news</label></td>
          <td><input type="checkbox" id="profile_abo" /></td>
        </tr>
      </table>
        <button id="profile_update" class="send">Mettre à jour vos données</button>
        <button id="profile_contact" class="green">Me contacter</button>
        <button id="profile_comment" class="green">Vos commentaires</button>
        <button id="profile_close">Fermer</button>

    </div>
    <div>
      <h3>Vos clés de sécurité</h3>
      <table id="profile_sec">
      </table>
      <input type="text" id="profile_addK_label" placeholder="Nom de la clé" value="" />
      <button id="profile_addK" class="send">Ajouter une clé de sécurité</button>
    </div>
    <div>
      <button id="profile_logout">Se déconnecter</button>
    </div>
  </div>
  <div id="Historique">
    <h2>Votre Historique</h2>
    <div>
      <p>Voici l'historique de vos 100 dernières navigations sur le site.</p>
    </div>
    <div>
      <table id="profile_history">
        <tr>
          <th>Dates d'accès</th>
          <th>Liens consultés</th>
        </tr>
      </table>
    </div>
  </div>
</div>
