# OtevrenyKatastr
Skripty pro převod vybraných částí výměnného formátu ISKN do PostGIS

 * data- vzorová data výměnného formátu ISKN, stažená
      z webu ČÚZK. Nachází se zde dva soubory, VFK verze 2.8 a 3.0.
      
 * importVFISKN_skripty- skripty pro převod dat VF ISKN
      do databáze PostGIS, napsané v jazyce Python J. Orálkem. Nově
      doplněny funkce pro sestavení parcelních a domovních čísel
      (pro podrobnosti viz text DP kap. 4.2).
      
 *mapserver_skripty- zdrojové soubory mapového serveru.
      Rozděleno do dvou podsložek:
          *htdocs- soubory, které je nutno umístit
              do adresáře přístupného z internetu (nastaví se v konfiguračním
              souboru webového serveru)
          *mapservdata- mapsoubor, šablona pro legendu,
              obrázek referenční mapy a adresář s true type fonty. Tyto
              soubory je vhodné umístit do adresáře, kam není přístup
              z internetu.
              
      Pro spuštění mapového serveru je potřeba aktualizovat údaje pro
      připojení k databázi a cestu k adresáři, kde se nachází mapsoubor.
      Oboje se nastaví v "konfiguračním" souboru config.inc, kde
      je třeba změnit hodnoty PHP proměnných:
      
      $pg_conn="host=jméno_serveru dbname=název_databáze user=uživatelské_jméno
                password=uživatelské_heslo".
                
      $mapfile_dir="C:/mapserver/mapservdata/"(např.)
      
      Dále je nutné pozměnit cesty v mapsouboru katastr.map.
      Jde o následující řádky:
          FONTSET:cesta k souboru se seznamem fontu (fonts.list)
          WEB/IMAGEPATH:cesta k adresáři, kam se budou ukládat obrázky
                  vygenerované mapserverem
          WEB/IMAGEURL:URL adesáře s vygenerovanými obrázky
          REFERENCE/IMAGE:cesta k referenční mapě
          v každé vrstvě: LAYER/CONNECTION:údaje pro připojení k databázi
      
 *ms4w- zdrojové soubory mapového serveru předpřipravené
      pro balík MS4W. Stačí nakopírovat do odpovídajících složek instalace MS4W a změnit údaje
      pro připojení k databázi v souboru config.inc.
