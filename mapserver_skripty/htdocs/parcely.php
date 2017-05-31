/*
****************************************************************************
**                       ***  KN mapserver  ***                           **
** Copyright (C) 2007  Jiri Petrak                                        **
** University of West Bohemia, Pilsen, Czech Republic                     **
** e-mail: jiripetrak@seznam.cz                                           **
**                                                                        **
** - knihovna pro ziskavani a vizualizaci atributovych dat informacniho   **
** systemu katastru nemovitosti CR ulozenych v databazi PostGIS           **
** pomoci programu UMN MapServer                                          **
**                                                                        **
** - library for acquisition and display of Czech Republic land registry  **
** information system attribute data stored in PostGIS database           **
** using UMN MapServer software                                           **
**                                                                        **
**  This library is free software; you can redistribute it and/or         **
** modify it under the terms of the GNU Lesser General Public             **
** License as published by the Free Software Foundation; either           **
** version 2.1 of the License, or (at your option) any later version.     **
**                                                                        **
** This library is distributed in the hope that it will be useful,        **
** but WITHOUT ANY WARRANTY; without even the implied warranty of         **
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU      **
** Lesser General Public License for more details.                        **
**                                                                        **
** You should have received a copy of the GNU Lesser General Public       **
** License along with this library; if not, write to the Free Software    **
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,             **
** MA  02110-1301  USA                                                    **
****************************************************************************
*/

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

