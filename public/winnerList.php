<?php
//
// Description
// -----------
// This method will return the list of Winners for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Winner for.
//
// Returns
// -------
//
function ciniki_writingfestivals_winnerList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'checkAccess');
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.winnerList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of winners
    //
    $strsql = "SELECT ciniki_writingfestival_winners.id, "
        . "ciniki_writingfestival_winners.festival_id, "
        . "ciniki_writingfestival_winners.category, "
        . "ciniki_writingfestival_winners.award, "
        . "ciniki_writingfestival_winners.sequence, "
        . "ciniki_writingfestival_winners.title, "
        . "ciniki_writingfestival_winners.author "
        . "FROM ciniki_writingfestival_winners "
        . "WHERE ciniki_writingfestival_winners.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'winners', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'category', 'award', 'sequence', 'title', 'author')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $winners = isset($rc['winners']) ? $rc['winners'] : array();
    $winner_ids = array();
    foreach($winners as $iid => $winner) {
        $winner_ids[] = $winner['id'];
    }

    return array('stat'=>'ok', 'winners'=>$winners, 'nplist'=>$winner_ids);
}
?>
