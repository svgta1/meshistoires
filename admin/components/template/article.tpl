<div>
  <h2>Article</h2>
  <div>
    <table>
      <tr><td><label for="article_title">Titre : </label></td><td><input type="text" id="article_title" value=""/></td></tr>
      <tr><td><label for="article_position"> Position : </label></td><td><input type="number" id="article_position" value=""/></td></tr>
      <tr><td><label for="article_visible"> Visible : </label></td><td><input type="checkbox" id="article_visible" /></td></tr>
      <tr><td><label for="article_resume"> Résumé : </label></td><td><input type="checkbox" id="article_resume" /></td></tr>
      <tr><td><label for="article_comment"> Commentaires : </label></td><td><input type="checkbox" id="article_comment" /></td></tr>
      <tr>
        <td><label for="article_menulist">Menu parent: </label></td>
        <td>
          <select name="article_menulist" id="article_menulist">
          </select>
        </td>
      </tr>
    </table>
    <p>
      <h3>Action sur l'édition'</h3>
      <button id="article_clean">Nettoyer copier/coller word</button>
      <button id="article_clear" class="red">Vider le contenu</button>
    </p>
    <p>
      <h3>Action sur le serveur</h3>
      <button id="article_maj">Mettre à jour</button>
      <button id="article_delete" class="red">Supprimer</button>
    </p>
    <p>
      <h3></h3>
      <button id="article_close" class="green">Fermer</button>
    </p>
  </div>
  <div>
    <p id="article_content_div">
      <textarea id="article_content"></textarea>
    </p>
  </div>
</div>
