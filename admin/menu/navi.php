<?php
/**
 * DZCP - deV!L`z ClanPortal 1.7.0
 * http://www.dzcp.de
 */

if(_adminMenu != 'true') exit;

    $where = $where.': '._navi_head;
      if($do == "add")
      {
        $qry = $sql->select("SELECT s2.*, s1.name AS katname, s1.placeholder FROM `{prefix_navi_kats}` AS s1 LEFT JOIN `{prefix_navi}` AS s2 ON s1.`placeholder` = s2.`kat` ORDER BY s1.name, s2.pos;");
        $thiskat = ""; $position = "";
        foreach($qry as $get) {
          if($thiskat != $get['kat']) {
            $position .= '
              <option class="dropdownKat" value="lazy">'.stringParser::decode($get['katname']).'</option>
              <option value="'.stringParser::decode($get['placeholder']).'-1">-> '._admin_first.'</option>
            ';
          }
          $thiskat = $get['kat'];

          $position .= empty($get['name']) ? '' : '<option value="'.stringParser::decode($get['placeholder']).'-'.($get['pos']+1).'">'._nach.' -> '.navi_name(stringParser::decode($get['name'])).'</option>';
        }

        $show = show($dir."/form_navi", array("do" => "addnavi",
                                              "what" => _button_value_add,
                                              "head" => _navi_add_head,
                                              "ja" => _yes,
                                              "intern" => _config_forum_intern,
                                              "nein" => _no,
                                              "n_name" => "",
                                              "n_url" => "",
                                              "atarget" => "",
                                              "target" => _target,
                                              "position" => $position,
                                              "name" => _navi_name,
                                              "url" => _navi_url_to,
                                              "wichtig" => _navi_wichtig,
                                              "pos" => _posi));
      } elseif($do == "addnavi") {
        if(empty($_POST['name']))
        {
          $show = error(_navi_no_name,1);
        } elseif(empty($_POST['url'])) {
          $show = error(_navi_no_url,1);
        } elseif($_POST['pos'] == "lazy") {
          $show = error(_navi_no_pos,1);
        } else {
          if($_POST['pos'] == "1" || "2") $sign = ">= ";
          else $sign = "> ";

          $kat = preg_replace('/-(\d+)/','',$_POST['pos']);
          $pos = preg_replace("=nav_(.*?)-=","",$_POST['pos']);

          $sql->update("UPDATE `{prefix_navi}`
                      SET `pos` = pos+1
                      WHERE pos ".$sign." '".intval($pos)."'");

          $sql->insert("INSERT INTO `{prefix_navi}`
                      SET `pos`       = '".intval($pos)."',
                          `kat`       = '".stringParser::encode($kat)."',
                          `name`      = '".stringParser::encode($_POST['name'])."',
                          `url`       = '".stringParser::encode($_POST['url'])."',
                          `shown`     = '1',
                          `target`    = '".intval($_POST['target'])."',
                          `internal`  = '".intval($_POST['internal'])."',
                          `type`      = '2',
                          `wichtig`   = '".intval($_POST['wichtig'])."'");
          $show = info(_navi_added,"?admin=navi");
        }
      } elseif($do == "delete") {
        $get = $sql->fetch("SELECT * FROM `{prefix_navi}` WHERE id = '".intval($_GET['id'])."'");
        
        $sql->delete("DELETE FROM `{prefix_sites}` WHERE id = '".intval($get['editor'])."'");
        $sql->delete("DELETE FROM `{prefix_navi}` WHERE id = '".intval($_GET['id'])."'");

        $show = info(_navi_deleted, "?admin=navi");
      } elseif($do == "edit") {
        $qry = $sql->select("SELECT s2.*, s1.name AS katname, s1.placeholder "
                . "FROM `{prefix_navi_kats}` AS s1 "
                . "LEFT JOIN `{prefix_navi}` AS s2 "
                . "ON s1.`placeholder` = s2.`kat` "
                . "ORDER BY s1.name, s2.pos");
         $i = 1;
         $thiskat = '';
         foreach($qry as $get) {
          if($thiskat != $get['kat']) {
            $position .= '
              <option class="dropdownKat" value="lazy">'.stringParser::decode($get['katname']).'</option>
              <option value="'.stringParser::decode($get['placeholder']).'-1">-> '._admin_first.'</option>
            ';
          }
          $thiskat = $get['kat'];
          $sel[$i] = ($get['id'] == $_GET['id']) ? 'selected="selected"' : '';

          $position .= empty($get['name']) ? '' : '<option value="'.stringParser::decode($get['placeholder']).'-'.($get['pos']+1).'" '.$sel[$i].'>'._nach.' -> '.navi_name(stringParser::decode($get['name'])).'</option>';

          $i++;
        }

        $get = $sql->fetch("SELECT * FROM `{prefix_navi}` WHERE id = '".intval($_GET['id'])."'");

        if($get['type'] == "1")
        {
          $name = stringParser::decode($get['name']);
          $read = "readonly";
        } else {
          $name = stringParser::decode($get['name']);
          $read = "";
        }

        if($get['wichtig'] == "1") $selw = 'selected="selected"';
        if($get['shown'] == "1") $sels = 'selected="selected"';
        if($get['internal'] == "1") $seli = 'selected="selected"';
        if($get['target'] == "1") $target = 'selected="selected"';

        $show = show($dir."/form_navi_edit", array("name" => _navi_name,
                                                   "url" => _navi_url_to,
                                                   "wichtig" => _navi_wichtig,
                                                   "pos" => _posi,
                                                   "atarget" => $target,
                                                   "target" => _target,
                                                   "n_name" => $name,
                                                   "n_url" => $get['url'],
                                                   "what" => _button_value_edit,
                                                   "do" => "editlink&amp;id=".$get['id']."",
                                                   "ja" => _yes,
                                                   "intern" => _config_forum_intern,
                                                   "seli" => $seli,
                                                   "sichtbar" => _navi_shown,
                                                   "sels" => $sels,
                                                   "position" => $position,
                                                   "selw" => $selw,
                                                   "read" => $read,
                                                   "nein" => _no,
                                                   "head" => _navi_edit_head));
      } elseif($do == "editlink") {
        if($_POST['pos'] == "1" || "2") $sign = ">= ";
        else $sign = "> ";

        $kat = preg_replace('/-(\d+)/','',$_POST['pos']);
        $pos = preg_replace("=nav_(.+)-=","",$_POST['pos']);

        $sql->update("UPDATE `{prefix_navi}`
                    SET pos = pos+1
                    WHERE pos ".$sign." '".intval($pos)."'");

        $sql->update("UPDATE `{prefix_navi}`
                    SET `pos`       = '".intval($pos)."',
                        `kat`       = '".stringParser::encode($kat)."',
                        `name`      = '".stringParser::encode($_POST['name'])."',
                        `url`       = '".stringParser::encode($_POST['url'])."',
                        `target`    = '".intval($_POST['target'])."',
                        `shown`     = '".intval($_POST['sichtbar'])."',
                        `internal`  = '".intval($_POST['internal'])."',
                        `wichtig`   = '".intval($_POST['wichtig'])."'
                    WHERE id = '".intval($_GET['id'])."'");

        $show = info(_navi_edited,"?admin=navi");
      } elseif($do == "menu") {
        $sql->update("UPDATE `{prefix_navi}`
                    SET `shown`     = '".intval($_GET['set'])."'
                    WHERE id = '".intval($_GET['id'])."'");

        header("Location: ?admin=navi");
      } else if($do == 'intern') {
        $sql->update("UPDATE `{prefix_navi_kats}`
                    SET `intern` = '".intval($_GET['set'])."'
                    WHERE id = '".intval($_GET['id'])."'");

        header("Location: ?admin=navi");
      } else if($do == 'editkat') {
        $get = $sql->fetch("SELECT * FROM `{prefix_navi_kats}` WHERE `id` = '".intval($_GET['id'])."'");

        $show = show($dir."/form_navi_kats", array("head" => _menu_edit_kat,
                                                   "name" => _sponsors_admin_name,
                                                   "placeholder" => _placeholder,
                                                   "visible" => _menu_visible,
                                                   "what" => _menu_edit_kat,
                                                   "menu_kat_info" => _menu_kat_info,
                                                   "n_name" => stringParser::decode($get['name']),
                                                   "n_placeholder" => str_replace('nav_', '', stringParser::decode($get['placeholder'])),
                                                   "sel_user" => ($get['level'] == 1 ? ' selected="selected"' : ''),
                                                   "sel_trial" => ($get['level'] == 2 ? ' selected="selected"' : ''),
                                                   "sel_member" => ($get['level'] == 3 ? ' selected="selected"' : ''),
                                                   "sel_admin" => ($get['level'] == 4 ? ' selected="selected"' : ''),
                                                   "guest" => _status_unregged,
                                                   "user" => _status_user,
                                                   "trial" => _status_trial,
                                                   "member" => _status_member,
                                                   "admin" => _status_admin,
                                                   "do" => 'updatekat&amp;id='.$get['id']
                                                   ));
      } else if($do == 'updatekat') {
        $sql->update("UPDATE `{prefix_navi_kats}`
            SET `name`        = '".stringParser::encode($_POST['name'])."',
                `placeholder` = 'nav_".stringParser::encode($_POST['placeholder'])."',
                `level`       = '".intval($_POST['level'])."'
            WHERE `id` = '".intval($_GET['id'])."'");

        $show = info(_menukat_updated, '?admin=navi');
      } else if($do == 'deletekat') {
        $sql->delete("DELETE FROM `{prefix_navi_kats}` WHERE `id` = '".intval($_GET['id'])."'");
        $show = info(_menukat_deleted, '?admin=navi');
      }  else if($do == 'addkat') {
        $get = $sql->fetch("SELECT * FROM `{prefix_navi_kats}` WHERE `id` = '".intval($_GET['id'])."'");

        $show = show($dir."/form_navi_kats", array("head" => _menu_add_kat,
                                                   "name" => _sponsors_admin_name,
                                                   "placeholder" => _placeholder,
                                                   "visible" => _menu_visible,
                                                   "menu_kat_info" => _menu_kat_info,
                                                   "what" => _menu_add_kat,
                                                   "n_name" => "",
                                                   "n_placeholder" => "",
                                                   "sel_user" => "",
                                                   "sel_trial" => "",
                                                   "sel_member" => "",
                                                   "sel_admin" => "",
                                                   "guest" => _status_unregged,
                                                   "user" => _status_user,
                                                   "trial" => _status_trial,
                                                   "member" => _status_member,
                                                   "admin" => _status_admin,
                                                   "do" => 'insertkat'
                                                   ));
      } else if($do == 'insertkat') {
        $sql->insert("INSERT INTO `{prefix_navi_kats}`
            SET `name`        = '".stringParser::encode($_POST['name'])."',
                `placeholder` = 'nav_".stringParser::encode($_POST['placeholder'])."',
                `level`       = '".intval($_POST['intern'])."'");

        $show = info(_menukat_inserted, '?admin=navi');
      } else {
	//default
	$kat = "";
	$show_ = "";
	$color = 0;

        $qry = $sql->select("SELECT s1.*, s2.name AS katname FROM `{prefix_navi}` AS s1 LEFT JOIN `{prefix_navi_kats}` AS s2 ON s1.kat = s2.placeholder ORDER BY s2.name, s1.kat,s1.pos");
        foreach($qry as $get) {
          $class = ($color % 2) ? "contentMainSecond" : "contentMainFirst"; $color++;

          if($get['type'] == "0")
          {
            $delete = show("page/button_delete_single", array("id" => $get['id'],
                                                              "action" => "admin=navi&amp;do=delete",
                                                              "title" => _button_title_del,
                                                              "del" => _confirm_del_navi));
            $edit = "&nbsp;";
            $type = _navi_space;
          } else {
            $type = stringParser::decode($get['name']);
            $edit = show("page/button_edit_single", array("id" => $get['id'],
                                                          "action" => "admin=navi&amp;do=edit",
                                                          "title" => _button_title_edit));
            $delete = show("page/button_delete_single", array("id" => $get['id'],
                                                              "action" => "admin=navi&amp;do=delete",
                                                              "title" => _button_title_del,
                                                              "del" => _confirm_del_navi));
          }

          if($get['shown'] == "1")
          {
            $shown = _yesicon;
            $set = 0;
          } else {
            $shown = _noicon;
            $set = 1;
          }
          if($get['katname'] != $kat) {
              $kat = $get['katname'];
              $show_ .= '<tr><td align="center" colspan="8" class="contentHead"><span class="fontBold">'.$get['katname'].'</span></td></tr>';
          }
          $show_ .= show($dir."/navi_show", array("class" => $class,
                                                  "name" => $type,
                                                  "id" => $get['id'],
                                                  "set" => $set,
                                                  "url" => cut($get['url'],34),
                                                  "kat" => stringParser::decode($get['katname']),
                                                  "shown" => $shown,
                                                  "edit" => $edit,
                                                  "del" => $delete));
        }
	//default
	$show_kats = "";
        $color = 0;

	$qry = $sql->select("SELECT * FROM `{prefix_navi_kats}` ORDER BY `name` ASC");
        foreach($qry as $get) {
          $class = ($color % 2) ? 'contentMainFirst' : 'contentMainSecond'; $color++;

          $type = stringParser::decode($get['name']);
          if($get['placeholder'] == 'nav_admin') {
            $edit = '';
            $delete = '';
          } else {
            $edit = show("page/button_edit_single", array("id" => $get['id'],
                                                          "action" => "admin=navi&amp;do=editkat",
                                                          "title" => _button_title_edit));
            $delete = show("page/button_delete_single", array("id" => $get['id'],
                                                              "action" => "admin=navi&amp;do=deletekat",
                                                              "title" => _button_title_del,
                                                              "del" => _confirm_del_menu));
          }
          $show_kats .= show($dir."/navi_kats", array("name" => stringParser::decode($get['name']),
                                                      "intern" => (empty($get['intern']) ? _noicon : _yesicon),
                                                      "id" => $get['id'],
                                                      "set" => (empty($get['intern']) ? 1 : 0),
                                                      "placeholder" => str_replace('nav_', '', stringParser::decode($get['placeholder'])),
                                                      "class" => $class,
                                                      "edit" => $edit,
                                                      "del" => $delete));
        }

        $show = show($dir."/navi", array("show" => $show_,
                                         "intern" => _config_forum_intern,
                                         "name" => _navi_name,
                                         "info" => _navi_info,
                                         "kat" => _config_newskats_kat,
                                         "placeholder" => _placeholder,
                                         "head_kat" => _menu_kats_head,
                                         "add_kat" => _menu_add_kat,
                                         "show_kats" => $show_kats,
                                         "url" => _navi_url,
                                         "intern" => _internal,
                                         "shown" => _navi_shown,
                                         "head" => _navi_head,
                                         "add" => _navi_add_head,
                                         "type" => _navi_type,
                                         "wichtig" => _navi_wichtig,
                                         "edit" => _editicon_blank,
                                         "del" => _deleteicon_blank));
      }
