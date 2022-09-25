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
function ciniki_writingfestivals_wng_adjudicatorsProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.writingfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.248', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.249', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a festival was specified
    //
    if( !isset($s['festival-id']) || $s['festival-id'] == '' || $s['festival-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.211', 'msg'=>"No festival specified"));
    }

    //
    // Load the adjudicators
    //
    $strsql = "SELECT adjudicators.id, "
        . "adjudicators.customer_id, "
        . "customers.display_name, "
        . "customers.sort_name, "
        . "adjudicators.image_id, "
        . "adjudicators.description, "
        . "sections.name AS section "
        . "FROM ciniki_writingfestival_adjudicators AS adjudicators "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "adjudicators.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_writingfestival_schedule_sections AS sections ON ("
            . "("
                . "adjudicators.id = sections.adjudicator1_id "
                . "OR adjudicators.id = sections.adjudicator2_id "
                . "OR adjudicators.id = sections.adjudicator3_id "
                . ") "
            . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $s['festival-id']) . "' "
        . "AND adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY customers.sort_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'adjudicators', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'display_name', 'section', 'image_id', 'description', 'sort_name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.221', 'msg'=>'Unable to load adjudicators', 'err'=>$rc['err']));
    }
    $adjudicators = isset($rc['adjudicators']) ? $rc['adjudicators'] : array();

    if( count($adjudicators) > 0 ) {
        //
        // Add the title block
        //
        $blocks[] = array(
            'type' => 'title', 
            'title' => isset($s['title']) ? $s['title'] : 'Adjudicators',
            );
        //
        // Add the adjudicators
        //
        $side = 'right';
        foreach($adjudicators as $adjudicator) {
            $blocks[] = array(
                'type' => 'contentphoto', 
                'image-position' => 'top-' . $side,
                'title' => $adjudicator['display_name']
                    . (isset($adjudicator['section']) && $adjudicator['section'] != '' ? ' - ' . $adjudicator['section'] : ''), 
                'image-id' => (isset($adjudicator['image_id']) && $adjudicator['image_id'] > 0  ? $adjudicator['image_id'] : 0),
                'content' => $adjudicator['description'],
                );
            $side = $side == 'right' ? 'left' : 'right';
        } 
    } else {
        $blocks[] = array(
            'type' => 'text', 
            'title' => isset($s['title']) ? $s['title'] : 'Adjudicators',
            'content' => "We don't currently have any adjudicators.",
            );
    } 

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
