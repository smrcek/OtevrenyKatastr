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
