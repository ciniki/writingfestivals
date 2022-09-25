<?php
//
// Description
// -----------
// This method will return the list of Festivals for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Festival for.
//
// Returns
// -------
//
function ciniki_writingfestivals_festivalList($ciniki) {
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
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.festivalList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of festivals
    //
    $strsql = "SELECT ciniki_writingfestivals.id, "
        . "ciniki_writingfestivals.name, "
        . "ciniki_writingfestivals.permalink, "
        . "ciniki_writingfestivals.start_date, "
        . "ciniki_writingfestivals.end_date, "
        . "ciniki_writingfestivals.status, "
        . "ciniki_writingfestivals.flags, "
        . "ciniki_writingfestivals.earlybird_date "
        . "FROM ciniki_writingfestivals "
        . "WHERE ciniki_writingfestivals.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY start_date DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'start_date', 'end_date', 'status', 'flags', 'earlybird_date')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['festivals']) ) {
        $festivals = $rc['festivals'];
        $festival_ids = array();
        foreach($festivals as $iid => $festival) {
            $festival_ids[] = $festival['id'];
        }
    } else {
        $festivals = array();
        $festival_ids = array();
    }

    return array('stat'=>'ok', 'festivals'=>$festivals, 'nplist'=>$festival_ids);
}
?>
