<?php
dl("php_mapscript.dll");
include "./functions.php";
include "./config.inc";

$map_path=$mapfile_dir;
$map_file="katastr.map";
$map=ms_newMapObj($map_path.$map_file); // vytvoreni objektu map
$parcely_layer=$map->getLayerByName("parcely");
$budovy_layer=$map->getLayerByName("budovy");
$parcely_label_layer=$map->getLayerByName("parcely_label");
$full_extent=$map->extent;
$val_zsize=2;
$check_zin="CHECKED";
$check_zout="";
$check_pan="";
$check_query="";
$select_qparcely="SELECTED";
$select_qbudovy="";
$check_parcely="CHECKED";
$check_parcely_label="";
$check_budovy="CHECKED";
$check_budovy_label="";
// nasledujici kod se vykona jen pokud jsme klikli do mapy
//  nebo obnovili extent
if ((isset($_POST["mapa_x"]) && isset($_POST["mapa_y"])) ||
    (isset($_POST["ref_x"]) && isset($_POST["ref_y"])) || 
    (isset($_POST["full"])) || (isset($_POST["renew"]))) {
// po kazdem obnoveni je treba vyresit "pamatovani si" co bylo zaskrtnute:
    $val_zsize=$_POST["zsize"];
    if ($val_zsize<=1) $val_zsize=2;
    elseif($val_zsize>=100) $val_zsize=2;
    //$zoom_factor=$_POST["zoom"]*$val_zsize;
    if ($_POST["tool"] == "pan") {
          $zoom_factor = 1;
          $check_pan = "CHECKED";
          $check_zout = "";
          $check_zin = "";
          $check_query = "";
    } else if ($_POST["tool"] == "zout") {
          $zoom_factor=-1*$val_zsize;
          $check_pan = "";
          $check_zout = "CHECKED";
          $check_zin = "";
          $check_query = "";
    } else if ($_POST["tool"] == "zin") {
          $zoom_factor=$val_zsize;
          $check_pan = "";
          $check_zout = "";
          $check_zin = "CHECKED";
          $check_query = "";
    } else {
          $check_pan = "";
          $check_zout = "";
          $check_zin = "";
          $check_query = "CHECKED";
    }    
    if (!isset($_POST["parcely"])) {
        $parcely_layer->set("status",MS_OFF);
        $check_parcely="";
    } else {
        if (isset($_POST["parcely_label"])) {
          $parcely_label_layer=$map->getLayerByName("parcely_label");
          $parcely_label_layer->set("status",MS_ON);
          $check_parcely_label="CHECKED";
        }
    }    
    if (!isset($_POST["budovy"])) {
        $budovy_layer->set("status",MS_OFF);
        $check_budovy="";
    } else {
        if (isset($_POST["budovy_label"])) {
          $budovy_label_layer=$map->getLayerByName("budovy_label");
          $budovy_label_layer->set("status",MS_ON);
          $check_budovy_label="CHECKED";
        }
    }
    if ($_POST["qlayer"]=="budovy") {
        $select_qparcely="";
        $select_qbudovy="SELECTED";
    }
//bylo-li kliknuto do mapy, nastavime chovani dle pouziteho nastroje:    
    if (isset($_POST["mapa_x"]) && isset($_POST["mapa_y"])) { 
        $click_point=ms_newPointObj();
        $click_point->setXY($_POST["mapa_x"],$_POST["mapa_y"]);// bod kliku (souradnice v px)
        $extent_to_set=explode(" ",$_POST["last_extent"]);
        //nebyl pouzit nastroj dotazovani
        if ($_POST["tool"]!="query")  {          
          $my_extent=ms_newRectObj();
          $my_extent->setextent($extent_to_set[0],$extent_to_set[1],
                                $extent_to_set[2],$extent_to_set[3]);
          $map->zoompoint($zoom_factor,$click_point,$map->width,
                          $map->height,$my_extent);
        } elseif ($_POST["tool"]=="query") {
          $map->setExtent($extent_to_set[0],$extent_to_set[1],
                          $extent_to_set[2],$extent_to_set[3]);
          $query_point=image2geo($map,$click_point);
          $qlayer_name=$_POST["qlayer"];
          $query_layer=$map->getLayerByName($qlayer_name);
          if($query_layer->queryByPoint($query_point,MS_SINGLE,-1) == MS_SUCCESS) {
              $result=$query_layer->getResult(0);
              $query_layer->open();
              $my_shape=$query_layer->getShape($result->tileindex,$result->shapeindex);
              if ($qlayer_name=="parcely") {
                  $shape_id=$my_shape->values["id"];
                  $filter="id=".$shape_id;
              } elseif ($qlayer_name=="budovy") {
                  $shape_id=$my_shape->values["id"];
                  $cislo_domovni=$my_shape->values["cislo_domovni"];
                  $filter="id=".$shape_id;
              }
              $query_layer->close();
              $selected=$map->getLayerByName($qlayer_name."_selected");
              $selected->set("status",MS_ON);
              $selected->setFilter($filter);                                    
          }          
        }
    }
    // bylo kliknuto do referenční mapky
    elseif (isset($_POST["ref_x"]) && isset($_POST["ref_y"])){
        $click_point=ms_newPointObj();
        $click_point->setXY($_POST["ref_x"],$_POST["ref_y"]);
        $my_scale=$_POST["last_scale"];
        $map->zoomscale($my_scale,$click_point,$map->reference->width,
                        $map->reference->height,$full_extent);
    }
    // bylo kliknuto na "Obnovit"
    elseif (isset($_POST["renew"])) {
        $extent_to_set=explode(" ",$_POST["last_extent"]);
        $map->setExtent($extent_to_set[0],$extent_to_set[1],
                        $extent_to_set[2],$extent_to_set[3]);
    }
}
/* // pouziti Querymap
if (isset($_POST["tool"]) && ($_POST["tool"] == "query")) {
    $image=$map->drawQuery();
} else {
    $image=$map->draw();
}
*/
$image=$map->draw();
$image_url=$image->saveWebImage();
$last_scale=$map->scale;
$extent_to_form=$map->extent->minx." ".$map->extent->miny." "
                .$map->extent->maxx." ".$map->extent->maxy;
$ref_image=$map->drawReferenceMap();
$ref_url=$ref_image->saveWebImage();
$parcely_icon=$parcely_layer->getClass(0)->createLegendIcon(20,12);
$parcely_icon_url=$parcely_icon->saveWebImage();
$budovy_icon=$budovy_layer->getClass(0)->createLegendIcon(20,12);
$budovy_icon_url=$budovy_icon->saveWebImage();
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html lang="cs">
<head>
<meta http-equiv="Content-language" content="cs">
<meta http-equiv="Content-type" content="text/html; charset=UTF-8"><!--iso-8859-2"-->
<meta name="resource-type" content="document">
  <title>Katastr PHP</title>
</head>
<body>

<form method=post action="<?php echo $_SERVER["PHP_SELF"] ?>" >
<table border="1">
<tr><td colspan="2">
    <input type="radio" name="tool" value="pan" <?php echo $check_pan ?>> Posun |
    <input type=radio name="tool" value="zin" <?php echo $check_zin ?>> Přiblížit |
    <input type=radio name="tool" value="zout" <?php echo $check_zout ?>> Oddálit |
    Zoom faktor 
    <input type=text name=zsize value=<?php echo $val_zsize ?> size=2>
    <input type=submit name="full" value="Full extent" size=5>
    <input type=radio name="tool" value="query" <?php echo $check_query ?>> Dotaz
    <select name="qlayer" size=1>
      <option value="parcely" <?php echo $select_qparcely ?>>Parcely
      <option value="budovy" <?php echo $select_qbudovy ?>>Budovy
    </select>
</td></tr>
<tr>
  <td width=200 valign="top">
    <input type=image name="ref" alt="Referenční mapka" src="<?php echo $ref_url; ?>" ><br>
    <table>
      <?php include $map_path.'/legend.php';?>
    </table>
    <input type=submit name="renew" value="Obnovit" size=5>
<?php 
    // vypis atributu
    if (isset($result) && ($qlayer_name=="parcely")) {
      include './parcely.php';
    } elseif (isset($result) && ($qlayer_name=="budovy")) {
      include './budovy.php';
    } elseif (!isset($result) && isset($qlayer_name)) {
      include './nenalezeno.php';
    }

?>    
  </td><td>
    <input type=image name="mapa" alt="Mapa" src="<?php echo $image_url ?>" >
  </td>
</tr>
</table>
<input type=hidden name="last_extent" value="<?php echo $extent_to_form; ?>">
<input type=hidden name="last_scale" value="<?php echo $last_scale; ?>">
</form>

</body>
</html>

