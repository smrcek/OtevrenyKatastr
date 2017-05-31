from Tkinter import *
from FileDialog import *
import nvf2sql

class consts:
    db="nvf"
    o="PostgreSQL Unicode"
    u="root"
    p="830101"
    f="Exporvse.vfk"
    
class App:
    def __init__(self,master):
        frame=Frame(master, bd=1, relief='raised')
        frame.grid(padx=5, pady=5, row=0, column=0)

        popis=Label(frame, text='Database options')
        popis.grid(pady=10, row=0, column=0)
        to=Label(frame, text='ODBC source name:')
        to.grid(row=1, column=0)
        self.o=Entry(frame, width=30)
        self.o.grid(pady=5, padx=10, row=1, column=1)
        self.o.insert(0,const.o)
        tdb=Label(frame, text='Database name:')
        tdb.grid(row=2, column=0)
        self.db=Entry(frame, width=30)
        self.db.grid(pady=5, padx=10, row=2, column=1)
        self.db.insert(0,const.db)
#        th=Label(frame, text='Host:')
#        th.grid(row=2, column=0)
#        self.h=Entry(frame, width=30)
#        self.h.grid(pady=5, padx=10, row=2, column=1)
#        self.h.insert(0,const.h)
        tu=Label(frame, text='User name:')
        tu.grid(row=3, column=0)
        self.u=Entry(frame, width=30)
        self.u.grid(pady=5, padx=10, row=3, column=1)
        self.u.insert(0,const.u)
        tp=Label(frame, text='Password:')
        tp.grid(row=4, column=0)
        self.p=Entry(frame, width=30, show="*")
        self.p.grid(pady=5, padx=10, row=4, column=1)
        self.p.insert(0,const.p)

        frame2=Frame(master)
        frame2.grid(padx=5, pady=5, row=1, column=0)

        te=Label(frame2, text='File name:')
        te.grid(row=1, column=0)
        self.f=Entry(frame2, width=30)
        self.f.grid(pady=5, padx=5, row=1, column=1)
        self.f.insert(0,const.f)
        button=Button(frame2, text="Convert", command=self.conv)
        button.grid(pady=10, padx=5, row=2, column=0)
    def conv(self):
        nvf2sql.MainProcess(self.o.get(), self.db.get(), self.u.get(), self.p.get(), self.f.get())

const=consts()
mainw=Tk()
mainw.title('NVF to PostGIS convertor')
Gui=App(mainw)
mainw.mainloop()

