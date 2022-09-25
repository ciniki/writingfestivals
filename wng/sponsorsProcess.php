<?php
//
// Description
// -----------
// This function will process a wng request for the blog module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_writingfestivals_wng_sponsorsProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.writingfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.194', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.195', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.196', 'msg'=>"No festival specified"));
    }

    //
    // Load the sponsors
    //
    $strsql = "SELECT sponsors.id, "
        . "sponsors.name, "
        . "sponsors.image_id, "
        . "sponsors.url "
        . "FROM ciniki_writingfestival_sponsors AS sponsors "
        . "WHERE sponsors.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "AND sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( isset($s['level']) && $s['level'] == 1 ) {
        $strsql .= "AND (sponsors.flags&0x01) = 0x01 ";
    } elseif( isset($s['level']) && $s['level'] == 2 ) {
        $strsql .= "AND (sponsors.flags&0x02) = 0x02 ";
    } elseif( isset($s['level']) && $s['level'] == 3 ) {
        $strsql .= "AND (sponsors.flags&0x03) = 0x03 ";
    } 
    $strsql .= "ORDER BY sponsors.sequence, sponsors.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'sponsors', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'image-id'=>'image_id', 'url'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.197', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
    }
    $sponsors = isset($rc['sponsors']) ? $rc['sponsors'] : array();

    if( count($sponsors) > 0 ) {
        //
        // Add the title block
        //
        if( isset($s['title']) && $s['title'] != '' ) {
            $blocks[] = array(
                'type' => 'title', 
                'level' => 2,
                'class' => 'sponsors',
                'title' => $s['title'],
                );
        }

        //
        // Add the sponsors
        //
        $blocks[] = array(
//            'type' => 'imagescroll', 
            'type' => 'sponsors', 
//            'padding' => '#ffffff',
            //'speed' => isset($s['speed']) ? $s['speed'] : 'medium',
            'class' => 'sponsors image-size-' . (isset($s['image-size']) ? $s['image-size'] : 'medium'),
            'items' => $sponsors,
            );
    } 

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
