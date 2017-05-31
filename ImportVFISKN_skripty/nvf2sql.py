from geom import *
from funkce import *
from init import *
import dbi, odbc
import sys

####      DB
def MainProcess(od,db,user,pas,file_name):
    try:
        s=od+'/'+user+'/'+pas
        conn = odbc.odbc(s)
    except:
        print "I am unable to connect to the database"
    cur = conn.cursor()
    cur.execute("select tablename from pg_tables where schemaname='public'")
    tables=cur.fetchall()
    print "Deleting tables..."
    for table in tables:
        if not ((table[0]=='spatial_ref_sys') or (table[0]=='geometry_columns')):
            cur.execute("DROP TABLE "+table[0]+" cascade;")


    f=file(file_name,'r')
    fp=file('Pkeys.sql','w+')
    ff=file('Fkeys.sql','w+')

    tabs=[]
    print "Filling tables..."
    for line in f:                      #prochazime vstupni soubor
        line=line.replace('"','\'')         #nahradi " -> '
        if line[1]=='H':                    #if radek je HLAVICKA
            h=line.split(';')[0][2:]            #ulozi nazev tabulky do 'h'
        elif line[1]=='B':                  #if uvozujici radek BLOKU
            b=line.split(';')[0][2:]            #ulozi nazev tabulky do 'b'
            sql=ProcessB(line)                  #vytvori SQL dotaz
            try:
                cur.execute(sql)                #SQL dotaz vytvori prazdnou tabulku
            except:
                print 'create table '+b
            tabs.append(b)                      #prida nazev tabulky do 'tabs'
        elif line[1]=='D':                  #if radka obsahuje DATA
            d=line.split(';')[0][2:]
            try:
                cur.execute(ProcessD(line))     #SQL dotaz naplni tabulku
            except:
                pass

    for i in tabs:
        try:
            fp.write(set_Pkeys(i))
        except:
            print "chyba Primary " + i, sys.exc_type, sys.exc_value
    for i in tabs:
        try:
            ff.write(set_Fkeys(i))
        except:
            print "chyba Foreign " + i, sys.exc_type, sys.exc_value
        
    fp.close()
    ff.close()
    f.close()
    fp=file('Pkeys.sql','r')
    ff=file('Fkeys.sql','r')
    print "Primary Keys..."
    for line in fp:
        try:
            cur.execute(line)
        except:
            print line
    foo=0
    fline=''
    print "Foreign Keys..."
    for line in ff:
        fline=fline+line
        foo=foo+1
        if foo%2==0:
            try:
                cur.execute(fline)
            except:
                print line
            fline=''
    fp.close()
    ff.close()

    print "Geometry HP..."
    AddGeometryL(db,'hp',cur)
    print "Geometry OB..."
    AddGeometryL(db,'ob',cur)
    print "Geometry DPM..."
    AddGeometryL(db,'dpm',cur)
    print "Geometry PAR..."
    AddGeometryP(db,'par','hp',cur)
    print "Geometry BUD..."
    AddGeometryP(db,'bud','ob',cur)
    AddColumn(cur,'par','drupoz_nazev','varchar(60)','drupoz','nazev','kod','drupoz_kod')
    AddColumn(cur,'par','zpvypo_nazev','varchar(60)','zpvypo','nazev','kod','zpvypa_kod')
    SetUpParcelNumber(cur)
    SetUpBuildingNumber(cur)
    print "Complete."    
