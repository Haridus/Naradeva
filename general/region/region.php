<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once './LocalSettings.php';

    function normalizeDl($dl)
    {
        if($dl > 180){
            $f = intval( ($dl+180) /360.0);
            $dl = $dl - $f*360;
        }		
        elseif($dl < -180){
            $f = intval( ($dl-180) /(-360.0) );
            $dl = $dl+$f*360;
        }
        return $dl;
    }
	
    function normalizeSh($sh)
    {
        if($sh > 90){
            $f = intval( $sh/90 );
            $sh = 90 - abs($sh - $f*90);
        }		
        elseif($sh < -90){
            $f = intval( $sh/(-90.0) );
            $sh = -90 + abs($sh+$f*90);
        }
        return $sh;
    }
    
    function coordinates_is_valid($sh, $dl)
    {
        return ( abs($sh)<90 and abs($dl)<180 ); 
    }
    
//-----------------------------------------------------------------------------   
//set 0-------------------------------------------------------------------------   
    function calculateRegionHandlers_0($sh,$dl)
    {
        $result = [];
        
        $sh = normalizeSh($sh);
        $dl = normalizeDl($dl);
        $shmod = $sh + 90.0;		
        $dlmod = $dl + 180.0;
        
        $shpart = intval($shmod/AppSettings::sh_min_segment_value/*min value part*/);
        $dlpart = intval($dlmod/AppSettings::dl_min_segment_value/*min value part*/);
        
        $shstr = sprintf("%'.05d",$shpart);
        $dlstr = sprintf("%'.05d",$dlpart);
        
        for( $i=0; $i<5; $i++ ){
            $shf = $shstr[$i];
            $dlf = $dlstr[$i];
            $result[] = "$shf$dlf";
        }
        
        return $result;
    }
    
    function regionFromHandlers_0($handlers)
    {
        return implode("",$handlers);
    }
    
    function calculateRegion_0($sh, $dl)
    {
        $result = "";
        $handlers = calculateRegionHandlers_0($sh,$dl);
        $result = regionFromHandlers_0($handlers);
        return $result; 				
    }
	
    function calculateNearbyRegionsHandlers_0($sh,$dl)
    {
        $result = [];
        $shf = AppSettings::sh_min_segment_value;
        $dlf = AppSettings::dl_min_segment_value;
        $result[] = calculateRegionHandlers_0(normalizeSh($sh+$shf),normalizeDl($dl+$dlf) );
        $result[] = calculateRegionHandlers_0(normalizeSh($sh+$shf),normalizeDl($dl) );
        $result[] = calculateRegionHandlers_0(normalizeSh($sh+$shf),normalizeDl($dl-$dlf) );
        $result[] = calculateRegionHandlers_0(normalizeSh($sh-$shf),normalizeDl($dl-$dlf) );
        $result[] = calculateRegionHandlers_0(normalizeSh($sh-$shf),normalizeDl($dl) );
        $result[] = calculateRegionHandlers_0(normalizeSh($sh-$shf),normalizeDl($dl+$dlf) );
        $result[] = calculateRegionHandlers_0(normalizeSh($sh),normalizeDl($dl-$dlf) );
        $result[] = calculateRegionHandlers_0(normalizeSh($sh),normalizeDl($dl+$dlf) );
        return $result;		
    }
        
    function calculateNearbyRegions_0($sh,$dl)
    {
        $result = [];
        $set = calculateNearbyRegionsHandlers_0($sh, $dl);
        foreach ($set as $value) {
            $result[] = regionFromHandlers_0($value);            
        }
        return $result;		
    }
    
//set 1---------------------------------------------------------------------------------------------------
// obsolete: need revising
//    //    function calculateRegionHandlers_1($sh,$dl)
//    {
//        $result = [];
//        $sh = normalizeSh($sh);
//        $dl = normalizeDl($dl);
//	
//        $shmod = $sh+180;		
//        $dlmod = $dl+90;
//			
//        $sh_cell = intval( $shmod );
//        $dl_cell = intval( $dlmod );
//        $sh_rem = intval( ($shmod - $sh_cell)*100 );
//        $dl_rem = intval( ($dlmod - $dl_cell)*100 );
//			
//        $spart = intval($dl_cell*360+$sh_cell);
//        $fpart = intval($dl_rem*100+$sh_rem);
//		
//        $result = array($sh_cell,$dl_cell,$sh_rem,$dl_rem);
//        return $result; 	
//    }
//
//    function regionFromHandlers_1($handlers)
//    {
//        $result = NULL;
//        if( is_array($handlers) and count($handlers) > 3 ){
//            $spart = intval($handlers[1]*360+$handlers[0]);
//            $fpart = intval($handlers[3]*100+$handlers[2]);
//            $result = sprintf("%'.05d%'.04d",$spart,$fpart);
//        }
//        return implode("",$handlers);
//    }
//    
//    function calculateRegion($sh, $dl)
//    {
//        $result = "";
//        $handlers = calculateRegionHandlers_1($sh,$dl);
//        $result = regionFromHandlers_1($handlers);
//        return $result; 				
//    }
//	
//    function calculateNearbyRegionsHandlers_1($sh,$dl)
//    {
//        $result = [];
//        $shf = 0.012;
//        $dlf = 0.012;
//        $result[] = calculateRegionHandlers_1(normalizeSh($sh+$shf),normalizeDl($dl+$dlf) );
//        $result[] = calculateRegionHandlers_1(normalizeSh($sh+$shf),normalizeDl($dl) );
//        $result[] = calculateRegionHandlers_1(normalizeSh($sh+$shf),normalizeDl($dl-$dlf) );
//        $result[] = calculateRegionHandlers_1(normalizeSh($sh-$shf),normalizeDl($dl-$dlf) );
//        $result[] = calculateRegionHandlers_1(normalizeSh($sh-$shf),normalizeDl($dl) );
//        $result[] = calculateRegionHandlers_1(normalizeSh($sh-$shf),normalizeDl($dl+$dlf) );
//        $result[] = calculateRegionHandlers_1(normalizeSh($sh),normalizeDl($dl-$dlf) );
//        $result[] = calculateRegionHandlers_1(normalizeSh($sh),normalizeDl($dl+$dlf) );
//        return $result;		
//    }
//        
//    function calculateNearbyRegions_1($sh,$dl)
//    {
//        $result = [];
//        $set = calculateNearbyRegionsHandlers_1($sh, $dl);
//        foreach ($set as $value) {
//            $result[] = regionFromHandlers_1($value);            
//        }
//        return $result;		
//    }
//    
?>