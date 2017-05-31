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
function image2geo($map,$click_point)
{
    $cellsize=$map->cellsize;
    $query_x = $map->extent->minx + $cellsize*$click_point->x;
    $query_y = $map->extent->maxy - $cellsize*$click_point->y;
    $query_point=ms_newPointObj();
    $query_point->setXY($query_x,$query_y);
    return $query_point;
}

function query($columns,$table,$condition)
{
    $query="SELECT $columns FROM $table WHERE $condition";
    $result=pg_query($query) or die ('Dotaz selhal: '.pg_last_error());
    return $row=pg_fetch_row($result);
}

function ConcatPolvz($input)
{
    $splitted=explode(" ",$input);
    $polvz[0]=strtoupper(substr($splitted[0],0,-4));
    $polvz[1]=substr($splitted[0],-4);
    return $polvz=implode("/",$polvz);
}
?>
