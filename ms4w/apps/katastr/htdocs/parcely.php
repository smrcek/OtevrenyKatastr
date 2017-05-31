<?php
include "./config.inc";
// vytvoreni pripojeni k databazi
$dbconn=pg_connect($pgconn)
          or die("Připojení k databázi se nezdařilo: ".pg_last_error());

// *** ziskani udaju o parcele ***
// SQL dotaz
$query='SELECT ku.nazev, tel.cislo_tel, bud.cislo_bud_komplet
        FROM par JOIN katuze ku ON (ku.kod=par.katuze_kod)
              JOIN tel ON (tel.id=par.tel_id)
              LEFT JOIN bud ON (bud.id=par.bud_id)
        WHERE par.id='.$shape_id;
$result=pg_query($query) or die('Dotaz selhal: '.pg_last_error());
$row=pg_fetch_row($result); // ziskani vysledku
$katuze_nazev=ucwords($row[0]);
$lv_cislo=$row[1];
$cislo_domovni=$row[2];
pg_free_result($result);    // uvolneni vysledku   
pg_close($dbconn);          // uzavreni pripojeni

// vypis atributu
echo "<hr>";
echo "<div>";
echo "Katastrální území: ".$katuze_nazev."<br>";
echo "Parcelní číslo: ".$my_shape->values["par_cislo_komplet"]."<br>";
echo "Výměra [m2]: ".$my_shape->values["vymera_parcely"]."<br>";
echo "Druh pozemku: ".$my_shape->values["drupoz_nazev"]."<br>";
if($my_shape->values["zpvypo_nazev"]!="") {
    echo "Způsob využití pozemku: ".$my_shape->values["zpvypo_nazev"]."<br>";
}
if(isset($cislo_domovni)) echo "Budova: ".$cislo_domovni."<br>";
echo "LV: <a href='lv.php?cislo_tel=$lv_cislo' target='_blank'>$lv_cislo</a>";
echo "</div>";
?>

