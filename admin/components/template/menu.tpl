<div>
  <h2>Détails du menu</h2>
  <div>
    <h3>Informations</h3>
    <table>
      <tr>
        <td>Nom </td><td><input id="menu_menuname" type="text" placeholder="Nom du menu"/></td>
      </tr>
      <tr>
        <td>Visible </td><td><input id="menu_menuvisible" type="checkbox"/></td>
      </tr>
      <tr>
        <td>Position </td><td><input id="menu_menuposition" type="number"/>
          sur <span id="menu_menuslength">0</span>
        </td>
      </tr>
      <tr>
        <td>Menu parent</td>
        <td>
          <select name="menu_menulist" id="menu_menulist">
            <option value="false">Menu principal</option>
          </select>
        </td>
      </tr>
    </table>
    <button id="menu_menuupdate">Mettre à jour</button>
    <button id="menu_menudelete" class="red">Supprimer</button>
  </div>
  <div>
    <h3>Sous-Menu</h3>
    <button id="menu_newsubmenu">Créer un sous-menu</button>
    <table id="menu_submenutable">
      <tr><th>Position</th><th>Menu</th></tr>
    </table>
  </div>
  <div>
    <h3>Articles</h3>
    <button id="menu_newarticle">Créer un article</button>
    <table id="menu_articletable">
      <tr><th>Position</th><th>Article</th><th>Visible</th></tr>
    </table>
  </div>
</div>