<?php
//
// Description
// -----------
// This method will return the list of Competitors for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Competitor for.
//
// Returns
// -------
//
function ciniki_writingfestivals_competitorList($ciniki) {
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
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.competitorList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of competitors
    //
    $strsql = "SELECT ciniki_writingfestival_competitors.id, "
        . "ciniki_writingfestival_competitors.festival_id, "
        . "ciniki_writingfestival_competitors.name, "
        . "ciniki_writingfestival_competitors.flags, "
        . "ciniki_writingfestival_competitors.parent, "
        . "ciniki_writingfestival_competitors.address, "
        . "ciniki_writingfestival_competitors.city, "
        . "ciniki_writingfestival_competitors.province, "
        . "ciniki_writingfestival_competitors.postal, "
        . "ciniki_writingfestival_competitors.phone_home, "
        . "ciniki_writingfestival_competitors.phone_cell, "
        . "ciniki_writingfestival_competitors.email, "
        . "ciniki_writingfestival_competitors.age, "
        . "ciniki_writingfestival_competitors.notes "
        . "FROM ciniki_writingfestival_competitors "
        . "WHERE ciniki_writingfestival_competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'competitors', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'name', 'flags', 
                'parent', 'address', 'city', 'province', 'postal', 'phone_home', 'phone_cell', 
                'email', 'age', 'notes')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['competitors']) ) {
        $competitors = $rc['competitors'];
        $competitor_ids = array();
        foreach($competitors as $iid => $competitor) {
            $competitor_ids[] = $competitor['id'];
        }
    } else {
        $competitors = array();
        $competitor_ids = array();
    }

    return array('stat'=>'ok', 'competitors'=>$competitors, 'nplist'=>$competitor_ids);
}
?>
