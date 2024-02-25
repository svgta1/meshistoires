<div id="menu_select">
  <div>
    <h2 id="menu_select_h2"></h2>
    <div>
      <h3>Informations</h3>

        <table>
          <tr><td><span>Id : </span></td><td><span id="menu_select_id"></span></td></tr>
          <tr><td><label for="menu_select_name">Nom : </label></td><td><input type="text" id="menu_select_name" value=""/></td></tr>
          <tr><td><span>Menu parent : </span></td><td><span id="menu_select_parent"></span></td></tr>
          <tr><td><label for="menu_select_position"> Position : </label></td><td><input type="number" id="menu_select_position" value=""/></td></tr>
          <tr><td><label for="menu_select_visible"> Visible : </label></td><td><input type="checkbox" id="menu_select_visible" /></td></tr>
        </table>
      <p>
        <button id="menu_select_maj">Mettre à jour</button>
        <button id="menu_select_pdf">Générer PDF</button></br>
      </p>
      <p class="del">
        <button id="menu_select_del">Supprimer le menu</button>
      </p>
    </div>
    <div>
      <h3>Sous Menu</h3>
      <table id="menu_select_sDomain">
      </table>
      <button id="menu_select_new">Créer un sous-menu</button>
    </div>
    <div>
      <h3>Articles</h3>
      <table id="menu_select_articles">
      </table>
      <button id="menu_select_new">Créer un article</button>
    </div>
  </div>
  <div id="article_selected"></div>
</div>
