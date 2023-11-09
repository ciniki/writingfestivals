<?php
//
// Description
// -----------
// This function will check for registrations in the writing festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_writingfestivals_wng_accountMenuItems($ciniki, $tnid, $request, $args) {

    $items = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = isset($args['base_url']) ? $args['base_url'] : '';

    //
    // Get the writing festival with the most recent date and status published
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_writingfestivals "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND status = 30 "        // Published
        . "ORDER BY start_date DESC "
        . "LIMIT 1 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'festival');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.202', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
    }
    if( !isset($rc['festival']) ) {
        // No festivals published, no items to return
        return array('stat'=>'ok');
    }
    $festival = $rc['festival'];

    //
    // Check if the customer is an adjudicator
    //
    $adjudicator = 'no';
    $strsql = "SELECT id "
        . "FROM ciniki_writingfestival_adjudicators "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'adjudicator');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.393', 'msg'=>'Unable to load adjudicator', 'err'=>$rc['err']));
    }
    if( isset($rc['adjudicator']) ) {
        $items[] = array(
            'title' => 'Adjudications', 
            'priority' => 750, 
            'selected' => isset($args['selected']) && $args['selected'] == 'writingfestivaladjudications' ? 'yes' : 'no',
            'ref' => 'ciniki.writingfestivals.adjudications',
            'url' => $base_url . '/writingfestivaladjudications',
            );
    }

    $items[] = array(
        'title' => 'Registrations', 
        'priority' => 739, 
        'selected' => isset($args['selected']) && $args['selected'] == 'writingfestivalregistrations' ? 'yes' : 'no',
        'ref' => 'ciniki.writingfestivals.registrations',
        'url' => $base_url . '/writingfestivalregistrations',
        );
    $items[] = array(
        'title' => 'Competitors', 
        'priority' => 738, 
        'selected' => isset($args['selected']) && $args['selected'] == 'writingfestivalcompetitors' ? 'yes' : 'no',
        'ref' => 'ciniki.writingfestivals.competitors',
        'url' => $base_url . '/writingfestivalcompetitors',
        );

    return array('stat'=>'ok', 'items'=>$items);
}
?>
