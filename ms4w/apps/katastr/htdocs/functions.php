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
