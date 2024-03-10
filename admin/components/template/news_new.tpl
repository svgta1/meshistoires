<div class="admin_tmce">
  <h2>Edition de la news letter</h2>
  <div>
    <table>
      <tr>
        <td>Nom de la news</td>
        <td><input type="text" placeholder="Titre" id="news_setnews_title" /></td>
      </tr>
      <tr>
        <td>Déjà publiée</td>
        <td><input type="checkbox" placeholder="Titre" id="news_setnews_published" disabled/></td>
      </tr>
      <tr>
        <td>Date de publication</td>
        <td id="news_setnews_published_date"></td>
      </tr>
    </table>
    <div>
      <h3>Actions sur l'édition</h3>
      <button id="clean_editor">Nettoyer copier/coller de word</button>
      <button id="news_setnews_clean" class="red">Vider le contenu</button>
    </div>
    <div>
      <h3>Actions serveur</h3>
      <button id="news_setnews_enregistrer">Enregistrer</button>
      <button id="news_setnews_annuler" class="red">Annuler l'édition</button>
    </div>
    <button id="news_setnews_publish" class="green">Publier la news</button>
  </div>
  <div id="admin_tmce_div_textarea">
    <textarea id="admin_news_textarea"></textarea>
  </div>
</div>
