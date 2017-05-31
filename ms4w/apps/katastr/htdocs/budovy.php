<?php
include "./config.inc";
//vytvoreni pripojeni k databazi
$dbconn=pg_connect($pgconn)
          or die("Připojení k databázi se nezdařilo: ".pg_last_error());
          
$query="SELECT c.nazev, bud.cislo_bud_komplet, zvb.zkratka, 
            par.par_cislo_komplet, tel.cislo_tel
        FROM bud JOIN zpvybu zvb ON (zvb.kod=bud.zpvybu_kod)
                JOIN par ON (par.bud_id=bud.id)
                LEFT JOIN tel ON (tel.id=bud.tel_id)
                LEFT JOIN casobc c ON (c.kod=bud.caobce_kod)
        WHERE bud.id=".$my_shape->values["id"];
$result=pg_query($query) or die ('Dotaz selhal: '.pg_last_error());
$row=pg_fetch_row($result);

$cast_obce_nazev=ucwords($row[0]);
$budova=$row[1];
$zpvybu_nazev=$row[2];
$par_c=$row[3];
$lv_cislo=$row[4];
pg_close($dbconn);

//vypis
echo "<hr>";
if (isset($cast_obce_nazev) && $cast_obce_nazev!="") echo "Část obce: ".$cast_obce_nazev."<br>";
echo "Budova: ".$budova."<br>";
echo "Způsob využití budovy: ".$zpvybu_nazev."<br>";
echo "Na parcele číslo: ".$par_c."<br>";
if (isset($lv_cislo)) echo "LV: <a href='lv.php?cislo_tel=$lv_cislo' target='_blank'>$lv_cislo</a>";
else echo "LV: není";
?>
