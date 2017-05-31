<?php
include "functions.php";
include "./config.inc";
$cislo_tel=$_GET["cislo_tel"] or die ('Nebylo predano cislo listu vlastnictvi');

//vytvoreni pripojeni k databazi
$dbconn=pg_connect($pgconn)
          or die("Připojení k databázi se nezdařilo: ".pg_last_error());
          
// ********* HLAVICKA ***********
$query="SELECT t.id,ku.kod,ku.nazev,ob.kod,ob.nazev,ok.nazev
        FROM tel t, katuze ku, obce ob, okresy ok
        WHERE t.cislo_tel=$cislo_tel AND t.katuze_kod=ku.kod
            AND ku.obce_kod=ob.kod AND ob.okresy_kod=ok.kod";
$result=pg_query($query) or die ('Dotaz selhal: '.pg_last_error());
$row=pg_fetch_row($result) or die ('List vlastnictví číslo '.$cislo_tel.' nebyl nalezen!');         
$tel_id=$row[0];
$katuze_kod=$row[1];
$katuze_nazev=ucwords($row[2]);
$obec_kod=$row[3];
$obec_nazev=ucwords($row[4]);
$okres_nazev=ucwords($row[5]);

// ******** VLASTNICTVI ********
$query="SELECT v.podil_citatel, v.podil_jmenovatel, t.nazev, o.nazev_u, 
            o.opsub_type, o.ico, o.rodne_cislo, o.obec, v.tel_id, v.typrav_kod
        FROM vla v, typrav t, opsub o
        WHERE t.kod = v.typrav_kod AND o.id = v.opsub_id AND v.tel_id=$tel_id
        ORDER BY v.typrav_kod";
$result=pg_query($query) or die ('Dotaz selhal: '.pg_last_error());
$i=0;
while ($row=pg_fetch_row($result)){
    $podil_citatel=$row[0];
    $podil_jmenovatel=$row[1];
    if ($podil_jmenovatel==1) $podil[]="";
    else $podil[]=$podil_citatel."/".$podil_jmenovatel;
    $typrav_nazev[]=$row[2];
    $opsub_nazev[]=$row[3];
    $opsub_type=$row[4];
    $adresa=$row[7];
    if ($adresa!="") $opsub_adresa[]=", ".$adresa;
    else $opsub_adresa[]="";
    
    if ($opsub_type=="ofo") {
      $identifikator[]=$row[6];
      $pozn[]="";
    } elseif($opsub_type=="opo") {
      $identifikator[]=$row[5];
      $pozn[]="";
    } elseif($opsub_type=="bsm") {
      $identifikator[]="";
      $pozn[]="SJM";
    } else {
      $identifikator[]="";
      $pozn[]="";
    }
    $i++;
}
// ******** NEMOVITOSTI ***************
//nemovitosti-pozemky
$query="SELECT p.par_cislo_komplet,
            p.vymera_parcely,p.drupoz_nazev,p.zpvypo_nazev,p.id,z.nazev
            FROM par p LEFT JOIN rzo r ON (p.id=r.par_id) LEFT JOIN zpochn z ON (r.zpochr_kod=z.kod)
            WHERE tel_id=$tel_id
            ORDER BY druh_cislovani_par,kmenove_cislo_par,poddeleni_cisla_par";
$result=pg_query($query) or die ('Dotaz selhal: '.pg_last_error());
$par_count=0;
while ($row=pg_fetch_row($result)) {
  $parcelni_cislo[]=$row[0];
  $vymera_parcely[]=$row[1];
  $drupoz_nazev[]=$row[2];
  $zpvypo_nazev[]=$row[3];
  $par_id=$row[4];
  $pole_par_id[]=$par_id;
  $zpochr_nazev[]=$row[5];

  $par_count++;
}
// nemovitosti-budovy
$query="SELECT c.nazev,b.typbud_kod,b.cislo_bud_komplet,zpv.zkratka,b.id,
            zpo.nazev,p.par_cislo_komplet,t.id,t.cislo_tel
        FROM bud b LEFT JOIN rzo r ON (b.id=r.bud_id) LEFT JOIN zpochn zpo ON (r.zpochr_kod=zpo.kod)
              LEFT JOIN casobc c ON (b.caobce_kod=c.kod), zpvybu zpv, par p, tel t
        WHERE b.tel_id=$tel_id AND b.zpvybu_kod=zpv.kod AND b.id=p.bud_id AND p.tel_id=t.id
        ORDER BY typbud_kod";
$result=pg_query($query) or die ('Dotaz selhal: '.pg_last_error());
$bud_count=0;
$bud_id_prev=0;
while ($row=pg_fetch_row($result)){
  $par_c=$row[6];
  if ($row[7]!=$tel_id) $par_c=$par_c.", LV:".$row[8];
   
  $bud_id=$row[4];
  // odchyceni pripadu, kdy budova lezi na vice parcelach
  if ($bud_id==$bud_id_prev){
      $bud_count--;
      $par_cisla[$bud_count]=$par_cisla[$bud_count].",".$par_c;
  } 
  else {
    $par_cisla[]=$par_c;
    $castobce_nazev=ucwords($row[0]);
    $typbud_kod=$row[1];
    $cislo_domovni=$row[2];
    $zpvybu_nazev[]=$row[3];  
    $zpochr_nazev[]=$row[5];
    // slozeni jedoznacne identifikace budovy - cast obce a cislo
    if (($typbud_kod==1) || ($typbud_kod==2)) {
      $budova[]=$castobce_nazev.", ".$cislo_domovni;
    } else {
      $budova[]=$cislo_domovni;
    }
  }  
  $bud_id_prev=$bud_id;
  $bud_count++;  
}

// ************* JINE PRAVNI VZTAHY ********************
/*SELECT t.nazev,jpv.popis_pravniho_vztahu,par.druh_cislovani_par,
              par.kmenove_cislo_par,par.poddeleni_cisla_par,
              r.typriz_kod,r.poradove_cislo
        FROM jpv LEFT JOIN par ON (jpv.par_id_k=par.id)
              LEFT JOIN bud ON (jpv.bud_id_k=bud.id)
              LEFT JOIN opsub ON (opsub.id=jpv.opsub_id_k)
              LEFT JOIN vla ON (vla.opsub_id=opsub.id)
              JOIN typrav t ON (jpv.typrav_kod=t.kod)
              JOIN rizeni r ON (jpv.rizeni_id_vzniku=r.id)
        WHERE jpv.tel_id=$tel_id OR (jpv.tel_id IS NULL AND 
              (par.tel_id=$tel_id OR bud.tel_id=$tel_id OR
              vla.tel_id=$tel_id))";
*/  
$query="SELECT t.nazev,jpv.popis_pravniho_vztahu, r.typriz_kod,r.poradove_cislo,
            tel.cislo_tel, jpv.opsub_id_k, jpv.bud_id_k, jpv.par_id_k, 
            jpv.opsub_id_pro, jpv.bud_id_pro, jpv.par_id_pro,
            opsub.nazev, tb.zkratka, bud.cislo_domovni,
            par.par_cislo_komplet, opsub.obec, typlis.nazev,
            t.sekce, r.rok, listin.poradove_cislo_zhotovitele, 
            listin.doplneni_zhotovitele, listin.rok_zhotovitele
        FROM jpv LEFT JOIN par ON (jpv.par_id_k=par.id OR jpv.par_id_pro=par.id)
            LEFT JOIN bud ON (jpv.bud_id_k=bud.id)
	          LEFT JOIN typbud tb ON (tb.kod=bud.typbud_kod)
            LEFT JOIN opsub ON (opsub.id=jpv.opsub_id_k OR opsub.id=jpv.opsub_id_pro)
            LEFT JOIN vla ON (vla.opsub_id=opsub.id)
	          LEFT JOIN tel ON (tel.id=jpv.tel_id)
            JOIN typrav t ON (jpv.typrav_kod=t.kod)
            JOIN rizeni r ON (jpv.rizeni_id_vzniku=r.id)
            LEFT JOIN rl ON (rl.jpv_id=jpv.id)
		        LEFT JOIN listin ON (listin.id=rl.listin_id)
		        LEFT JOIN typlis ON (typlis.kod=listin.typlist_kod)
        WHERE jpv.tel_id=$tel_id 
		        OR (jpv.tel_id IS NULL AND(par.tel_id=$tel_id 
		        OR bud.tel_id=$tel_id OR vla.tel_id=$tel_id))
        ORDER BY t.kod";
            
$result=pg_query($query) or die ('Dotaz selhal: '.pg_last_error());
while ($row=pg_fetch_row($result)) {
    if ($row[17]=="b")
    // patri do casti B1
    {
        $B_typjpv_nazev[]=$row[0];
        $popis_prav=explode(" / ",$row[1]);
        $B_popis_prav[]=$popis_prav[0];
        $rok=explode("-",$row[18]);
        $rok=$rok[0];
        $B_rizeni[]=strtoupper($row[2])."-".$row[3]."/".$rok;
        if ($row[8]!=NULL){
            $B_opravneni_pro[]=$row[11].", ".$row[15];
        } else $B_opravneni_pro[]="&nbsp;";     
        if ($row[4]!=NULL){
            $B_povinnost_k[]="LV: ".$row[4];
        }
        $rok_zhotovitele=explode("-",$row[21]);
        $rok_zhotovitele=$rok_zhotovitele[0];
        $B_listina[]=$row[16]." ".$row[19]."/".$rok;
        $B_polvz[]=ConcatPolvz($row[20]);
    } else if ($row[17]=="c")
    // patri do casti C
    {
        $C_typjpv_nazev[]=$row[0];
        $popis_prav=explode(" / ",$row[1]);
        $C_popis_prav[]=$popis_prav[0];
        $rok=explode("-",$row[18]);
        $rok=$rok[0];
        $C_rizeni[]=strtoupper($row[2])."-".$row[3]."/".$rok;
        if ($row[8]!=NULL){
            $C_opravneni_pro[]=$row[11].", ".$row[15];
        } else $C_opravneni_pro[]="&nbsp;";
        
        if ($row[4]!=NULL){
            $C_povinnost_k[]="LV: ".$row[4];
        } else if ($row[6]!=NULL){
            $C_povinnost_k[]="Budova: ".$row[12]." ".$row[13];
        } else if ($row[7]!=NULL){
            $C_povinnost_k[]="Parcela: ".$row[14];
        } else $C_povinnost_k[]="&nbsp;";
        $rok_zhotovitele=explode("-",$row[21]);
        $rok_zhotovitele=$rok_zhotovitele[0];
        $C_listina[]=$row[16]." ".$row[19]."/".$rok;
        $C_polvz[]=ConcatPolvz($row[20]);
    } else if ($row[17]=="d") 
    // patri do casti D
    {
        $D_typjpv_nazev[]=$row[0];
        $popis_prav=explode(" / ",$row[1]);
        $D_popis_prav[]=$popis_prav[0];
        $rok=explode("-",$row[18]);
        $rok=$rok[0];
        $D_rizeni[]=strtoupper($row[2])."-".$row[3]."/".$rok;
        if ($row[7]!=NULL){
            $D_vztah_k[]="Parcela: ".$row[14];
        } else if ($row[4]!=NULL){
            $D_vztah_k[]="LV: ".$cislo_tel;
        } else if ($row[5]!=NULL){
            $D_vztah_k[]=$row[11].", ".$row[15];
        }
    }     
}

// *************** LISTINY *******************
$query="SELECT DISTINCT list.popis, typlis.nazev, 
	       os.nazev, os.obec, os.rodne_cislo, os.ico,
	       riz.typriz_kod, riz.poradove_cislo,
	       riz.rok, list.poradove_cislo_zhotovitele, list.doplneni_zhotovitele,
	       list.rok_zhotovitele
        FROM listin list JOIN typlis ON (typlis.kod=list.typlist_kod)
	           JOIN rl ON (rl.listin_id=list.id)
	           LEFT JOIN par ON (par.id=rl.par_id)
	           LEFT JOIN bud ON (bud.id=rl.bud_id)
	           LEFT JOIN jpv ON (jpv.id=rl.jpv_id)
	           LEFT JOIN opsub os ON (os.id=rl.opsub_id)
	           LEFT JOIN vla ON (vla.opsub_id=os.id)
	           JOIN rizeni riz ON (riz.id=list.rizeni_id)
        WHERE par.tel_id=$tel_id OR bud.tel_id=$tel_id
            OR vla.tel_id=$tel_id
        ORDER BY riz.rok";
$result=pg_query($query) or die ('Dotaz selhal: '.pg_last_error());
while ($row=pg_fetch_row($result)){
  
  $rok_zhotovitele=explode("-",$row[11]);
  $rok_zhotovitele=$rok_zhotovitele[0];
  $E_listina_nazev[]=$row[1]." ".$row[9]."/".$rok_zhotovitele." ".$row[0];
  $E_polvz[]=ConcatPolvz($row[10]);
  $E_op_subjekt[]=$row[2].", ".$row[3];
  if ($row[4]!=NULL || $row[4]!="") $E_ident[]=$row[4];
  else if ($row[5]!=NULL || $row[5]!="") $E_ident[]=$row[5];
  else $E_ident[]="&nbsp;";
  $rok=explode("-",$row[8]);
  $rok=$rok[0];
  $E_rizeni[]=strtoupper($row[6])."-".$row[7]."/".$rok;
}

// ********** BPEJ ****************
$query="SELECT bdp.vymera, bdp.bpej_kod, par.par_cislo_komplet	         
        FROM par, bdp
        WHERE par.tel_id=$tel_id AND bdp.par_id=par.id 
        ORDER BY par.druh_cislovani_par, par.kmenove_cislo_par, par.poddeleni_cisla_par";
$result=pg_query($query) or die ('Dotaz selhal: '.pg_last_error());
while ($row=pg_fetch_row($result)){
    $F_parcela[]=$row[2];
    $F_vymera[]=$row[0];
    $F_bpej_kod[]=$row[1];
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html lang="cs">
<head>
<meta http-equiv="Content-language" content="cs">
<meta http-equiv="Content-type" CONTENT="text/html; charset=UTF-8"><!--iso-8859-2"-->
<meta name="resource-type" content="document">
<link rel="stylesheet" type="text/css" href="styl.css">
<title>LV <?php echo $cislo_tel ?></title>
</head>

<body>

<div align="center">
<div class="stranka">
<span class="bold">VÝPIS Z KATASTRU NEMOVITOSTÍ</span><br>
<!--span class="bold">prokazující stav evidovaný k datu</span> <span class="italic"></span-->
<table>
  <tr>
    <th class="hlavickaleva">Okres:&nbsp;</th> <td class="bold"><?php echo $okres_nazev ?> </td> 
    <th class="hlavickaprava">Obec:&nbsp;</th> <td class="bold"><?php echo $obec_kod." ".$obec_nazev ?> </td>
  </tr>
  <tr>
    <th class="hlavickaleva">Kat. území:&nbsp;</th> <td class="bold"><?php echo $katuze_kod." ".$katuze_nazev ?> </td> 
    <th class="hlavickaprava">List vlastnictví:&nbsp;</th> <td class="bold"><?php echo $cislo_tel?> </td>
  </tr>
</table>
<!-- sekce A -->
<table class="sekce">
  <tr>
    <th class="sekce_pismeno">A</th> <th>Vlastník, jiný oprávněný</th> <th>Identifikátor</th> <th>Podíl</th>
  </tr>
<?php
$i=0;
foreach ($typrav_nazev as $typrav_current) {
  //$typrav_prev=$typrav_nazev[$i-1];
  if (($i==0) || ($typrav_current!=$typrav_nazev[$i-1])){
    echo "<tr class='borderbottom'>
            <th colspan=4>$typrav_current</th>
          </tr>";
  }
  echo    "<tr>
            <td>".$pozn[$i]."</td> <td>".$opsub_nazev[$i].$opsub_adresa[$i]."</td> <td>".$identifikator[$i]."</td>
            <td class='alignright'>".$podil[$i]."</td>
          </tr>";
  $i++;
}
?>    
</table>
<!-- sekce B -->
<table class="sekce">
  <tr>
    <th class="sekce_pismeno">B</th> <th class="sekce_nazev">Nemovitosti</th>
  </tr>
</table>
<?php
if ($par_count!=0) {
// vypis parcel
  echo "<table>
        <tr>
          <th colspan=5>Pozemky</th>
        </tr>
        <tr class='borderbottom'>
          <th>&nbsp;</th> <th>Parcela</th> <th>Výměra[m2]</th> 
          <th>Druh pozemku</th> <th>Způsob využití</th> <th>Způsob ochrany</th>
        </tr>";
  $i=0;
  foreach ($parcelni_cislo as $par_c_current) {
    echo "<tr>
            <td>&nbsp;</td>
            <td>".$par_c_current."</td>
            <td class='alignright'>".$vymera_parcely[$i]."</td>
            <td>".$drupoz_nazev[$i]."</td>
            <td>".$zpvypo_nazev[$i]."</td>
            <td>".$zpochr_nazev[$i]."</td>
          </tr>";
    $i++;
  }
  echo "</table>";
}

if ($bud_count!=0) {
// vypis budov
  echo "<table>
          <tr>
            <th colspan=5>Budovy</th>
          </tr><tr>
            <th>&nbsp;</th><th>Typ budovy</th>
          </tr>
          <tr class='borderbottom'>
            <th>&nbsp;</th><th>Část obce, č. budovy</th><th>Způsob využití</th>
            <th>Způsob ochrany</th><th>Na parcele</th>
          </tr>";
  //$i=0;
  for ($i=0; $i<$bud_count; $i++) {
    echo "<tr>
            <td>&nbsp;</td>
            <td>".$budova[$i]."</td>
            <td>".$zpvybu_nazev[$i]."</td>
            <td>".$zpochr_nazev[$i]."</td>
            <td>".$par_cisla[$i]."</td>
          </tr>";
    //$i++;
  }
  echo "</table>";
}
?>  

<!-- sekce B1 -->
<table class="sekce">
  <tr>
    <th class="sekce_pismeno">B1</th> <th class="sekce_nazev">Jiná práva</th>
<?php
if (isset($B_typjpv_nazev[0])){
  echo "</tr>
        </table>
        <table>
          <tr>
            <th>&nbsp;</th> <th colspan=4>Typ vztahu</th>
          </tr><tr class='borderbottom'>
            <th>&nbsp;</th> <th>Oprávnění pro</th> <th colspan=3>Povinnost k</th>
          </tr>";
  $i=0;
  foreach ($B_typjpv_nazev as $typjpv_curr){
    echo "<tr>
            <td>1</td> <td colspan=3>$typjpv_curr</td> <td>&nbsp;</td>
          </tr><tr>
            <td>&nbsp;</td> <td colspan=3>".$B_popis_prav[$i]."</td> <td>&nbsp;</td>
          </tr><tr>
            <td>&nbsp;</td> <td>".$B_opravneni_pro[$i]."</td>
            <td>".$B_povinnost_k[$i]."</td> <td>&nbsp;</td>
            <td>".$B_rizeni[$i]."</td>
          </tr><tr>
            <td>&nbsp;</td> <td colspan=4>Listina: ".$B_listina[$i]."</td>
          </tr><tr>
            <td colspan=3>&nbsp;</td> <td>".$B_polvz[$i]."</td> <td>".$B_rizeni[$i]."</td>
          </tr>";
    $i++;        
  }
  echo "</table>";
} else echo "<th class='bezzapisu'>- Bez zápisu</th>
            </tr>
          </table>";
?>
<!-- sekce C -->
<table class="sekce">
  <tr>
    <th class="sekce_pismeno">C</th> 
    <th class="sekce_nazev">Omezení vlastnického práva</th>
<?php
if (isset($C_typjpv_nazev[0])){
  echo "</tr>
      </table>
      <table>
          <tr>
            <th>&nbsp;</th> <th colspan=4>Typ vztahu</th>
          </tr><tr class='borderbottom'>
            <th>&nbsp;</th> <th>Oprávnění pro</th> <th colspan=3>Povinnost k</th>
          </tr>";
  $i=0;
  foreach ($C_typjpv_nazev as $typjpv_curr){
    echo "<tr>
            <td>1</td> <td colspan=3>$typjpv_curr</td> <td>&nbsp;</td>
          </tr><tr>
            <td>&nbsp;</td> <td colspan=3>".$C_popis_prav[$i]."</td> <td>&nbsp;</td>
          </tr><tr>
            <td>&nbsp;</td> <td>".$C_opravneni_pro[$i]."</td>
            <td>".$C_povinnost_k[$i]."</td> <td>&nbsp;</td>
            <td>".$C_rizeni[$i]."</td>
          </tr><tr>
            <td>&nbsp;</td> <td colspan=4>Listina: ".$C_listina[$i]."</td>
          </tr><tr>
            <td colspan=3>&nbsp;</td> <td>".$C_polvz[$i]."</td> <td>".$C_rizeni[$i]."</td>
          </tr>";
    $i++;
  }
  echo "</table>";
} else echo "<th class='bezzapisu'>- Bez zápisu</th>
            </tr>
          </table>";
?>
<!-- sekce D -->
<table class="sekce">
  <tr>
    <th class="sekce_pismeno">D</th>
    <th class="sekce_nazev">Jiné zápisy</th>
<?php
if (isset($D_typjpv_nazev[0])){
  echo "</tr>
      </table>
      <table>
          <tr>
            <th>&nbsp;</th> <th colspan=3>Typ vztahu</th>
          </tr><tr class='borderbottom'>
            <th>&nbsp;</th> <th>Vztah pro</th> <th colspan=2>Vztah k</th>
          </tr>";
  $i=0;
  foreach ($D_typjpv_nazev as $typjpv_curr){
    echo "<tr>
            <td>1</td> <td colspan=2>$typjpv_curr</td>
          </tr><tr>
            <td>&nbsp;</td> <td colspan=2>".$D_popis_prav[$i]."</td>
          </tr><tr>
            <td colspan=2>&nbsp;</td> <td>".$D_vztah_k[$i]."</td>
            <td>".$D_rizeni[$i]."</td>
          </tr>";          
    $i++;
  }
  echo "</table>";
} else echo "<th class='bezzapisu'>- Bez zápisu</th>
            </tr>
          </table>";
?>
<!-- sekce E -->
<table class="sekce">
  <tr>
    <th class="sekce_pismeno">E</th>
    <th class="sekce_nazev">Nabývací tituly a jiné podklady zápisu</th>
<?php
if (isset($E_listina_nazev[0])){
  echo "</tr>
      </table>
      <table>
          <tr>
            <th>&nbsp;</th> <th colspan=3>Listina</th>
          </tr>";
  $i=0;
  foreach ($E_listina_nazev as $listina_curr){
    echo "<tr>
            <td>1</td> <td colspan=3>".$listina_curr."</td>
          </tr><tr>
            <td colspan=2>&nbsp;</td> <td>".$E_polvz[$i]."</td> <td>".$E_rizeni[$i]."</td>
          </tr><tr>
            <td>&nbsp;</td> <td>Pro: ".$E_op_subjekt[$i]."</td>
            <td>&nbsp;</td> <td>RČ/IČO: ".$E_ident[$i]."</td>
          </tr>";
    $i++;
  }
  echo "</table>";
} else echo "<th class='bezzapisu'>- Bez zápisu</th>
            </tr>
          </table>";
?>
<!-- sekce F -->
<table class="sekce">
  <tr>
    <th class="sekce_pismeno">F</th>
    <th colspan=3 class="sekce_nazev">Vztah bonitovaných půdně ekologických jednotek (BPEJ) k parcelám</th>
<?php
if (isset($F_parcela[0])){
  echo "</tr>
      </table>
      <table>
          <tr class='borderbottom'>
            <th>&nbsp;</th> <th>Parcela</th> <th>BPEJ</th> <th>Výměra[m2]</th>
          </tr>";
  $i=0;
  foreach ($F_parcela as $parcela_curr){
    echo "<tr>
            <td>&nbsp;</td> <td>".$F_parcela[$i]."</td>
            <td>".$F_bpej_kod[$i]."</td> <td>".$F_vymera[$i]."</td>
          </tr>";
    $i++;
  }
  echo "</table>";
  echo "<span class='italic'>Pokud je výměra bonitních dílů parcel menší než výměra parcely,
       zbytek parcely není bonitován</span>";
} else echo "<th class='bezzapisu'>- Bez zápisu</th>
            </tr>
          </table>";
?>
<table class="sekce">
  <tr>
    <th>Jiří Petrák, ZČU v Plzni</th>
    <th class="zapatiprave">Vyhotoveno:&nbsp;</th>
    <th><?php echo date("d.m.Y H:i:s");?></th>
  </tr><tr>
    <th class="upozorneni">Autor neručí za správnost a úplnost údajů!</th>
    <th class="zapatiprave">Vyhotovil:&nbsp;</th>
    <th>Vyhotoveno KN_mapserverem</th>
  </tr><tr>
    <th>Řízení PÚ: .................</th>
    <th class="zapatiprave">Podpis, razítko:&nbsp;</th>
    <th>&nbsp;</th>
  </tr>
</table>
</div>
</div>

</body>
</html>
