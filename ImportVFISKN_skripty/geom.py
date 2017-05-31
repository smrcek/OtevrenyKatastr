    
def Update_geom(data):
    sqlt=''
    for i in range(len(data)):
        sqlt=sqlt+'-'+str(data[i][0])+' -'+str(data[i][1])+','
    sqlt=sqlt[:len(sqlt)-1]
    return sqlt

def AddGeometryL(db,tab,cur):
    cur.execute("select AddGeometryColumn('"+db+"','"+tab+"','geom"+tab+"',2065,'LINESTRING',2);")

    cur.execute('select id from '+tab)
    tab_ids=cur.fetchall()

    for i in tab_ids:
        sql="""select souradnice_y,souradnice_x from sobr where id in 
        (select bp_id from sbp where """+tab+"_id="+str(i[0])+")"
        cur.execute(sql)
        rows=cur.fetchall()
        sqlt="UPDATE "+tab+"\nSET geom"+tab+"=GeomFromText('LINESTRING("+Update_geom(rows)+")',2065)\n"
        sqlt=sqlt+"WHERE id="+str(i[0])+";"
        try:
            cur.execute(sqlt)
        except:
            pass

def AddGeometryP(db,tab,tab_L,cur):
    cur.execute("select AddGeometryColumn('"+db+"','"+tab+"','geom"+tab+"',2065,'POLYGON',2);")

    cur.execute('select id from '+tab)
    tab_ids=cur.fetchall()
    for i in tab_ids:
        hp_points=[]
        if tab=='par':
            sql="select id from "+tab_L+" where (par_id_1="+str(i[0])+") or (par_id_2="+str(i[0])+");"
        elif tab=='bud':
            sql="select id from "+tab_L+" where (bud_id="+str(i[0])+") and (obrbud_type='ob');"
        else:
            pass
            
        cur.execute(sql)
        hp_ids=cur.fetchall() #seznam hp k dane parcele
        for j in hp_ids: #naplni hp_points body
            cur.execute("select PointN(geom"+tab_L+",1) from "+tab_L+" where id="+str(j[0]))
            p1=cur.fetchone()[0]
            cur.execute("select PointN(geom"+tab_L+",2) from "+tab_L+" where id="+str(j[0]))
            p2=cur.fetchone()[0]
            hp_points.append([p1,p2])

        PolyCoords=[]

        sp=-1
        while 1:
            if len(hp_ids)==0:
                break
            sp=sp+1
            PolyCoords.append([])
            # print hp_points[0][0]
            cur.execute("select x('"+hp_points[0][0]+"')")
            x=cur.fetchone()[0]
            cur.execute("select y('"+hp_points[0][0]+"')")
            y=cur.fetchone()[0]
            PolyCoords[sp].append([-x,-y])
            point=hp_points[0][0]
            hp_ids.pop(0)
            hp_points.pop(0)

            for k in range(len(hp_ids)):
                for ind,h in enumerate(hp_ids): #najde hp, obsahujici bod point
                    sql="select distance((select geom"+tab_L+" from "+tab_L+" where id="+str(h[0])+"),'"+point+"')"
                    cur.execute(sql)
                    if  cur.fetchone()[0]==0:
                        dalsi=1
                        break
                if dalsi==0:
                    break
                dalsi=0
                for foo in range(2):
                    cur.execute("select x('"+hp_points[ind][foo]+"')")
                    x=cur.fetchone()[0]
                    cur.execute("select y('"+hp_points[ind][foo]+"')")
                    y=cur.fetchone()[0]
                    try:
                        PolyCoords[sp].index([-x,-y])
                    except:
                        PolyCoords[sp].append([-x,-y])
                        point=hp_points[ind][foo]
                        hp_ids.pop(ind)
                        hp_points.pop(ind)
                        break
            PolyCoords[sp].append(PolyCoords[sp][0])

        poly=[]
        for fooo in range(len(PolyCoords)):
            sqlt="select GeomFromText('POLYGON(("+Update_geom(PolyCoords[fooo])+"))',2065)"
            cur.execute(sqlt)
            poly.append(cur.fetchone()[0])
        if len(poly)>1:
            first=0
            for ii in range(len(poly)):
                for jj in range(ii+1,len(poly)):
                    cur.execute("select contains('"+poly[ii]+"','"+poly[jj]+"')")
                    if cur.fetchone()[0]==1:
                        first=1
                        pom=PolyCoords[0]
                        PolyCoords[0]=PolyCoords[ii]
                        PolyCoords[ii]=pom
                        break
                if first==1:
                    break
            
        sqlt="UPDATE "+tab+"\nSET geom"+tab+"=GeomFromText('POLYGON("
        for fooo in range(len(PolyCoords)):
            sqlt=sqlt+'('+Update_geom(PolyCoords[fooo])+"),"
        sqlt=sqlt[:len(sqlt)-1]+")',2065)\n"
        sqlt=sqlt+"WHERE id="+str(i[0])+";"
        try:
            cur.execute(sqlt)
        except:
            print "error bud_id: "+str(i[0])




