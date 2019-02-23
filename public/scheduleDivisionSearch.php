<?php
//
// Description
// -----------
// This method searchs for a Schedule Divisions for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Schedule Division for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_writingfestivals_scheduleDivisionSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'checkAccess');
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.scheduleDivisionSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of scheduledivisions
    //
    $strsql = "SELECT ciniki_writingfestival_schedule_divisions.id, "
        . "ciniki_writingfestival_schedule_divisions.festival_id, "
        . "ciniki_writingfestival_schedule_divisions.ssection_id, "
        . "ciniki_writingfestival_schedule_divisions.name, "
        . "ciniki_writingfestival_schedule_divisions.division_date, "
        . "ciniki_writingfestival_schedule_divisions.address "
        . "FROM ciniki_writingfestival_schedule_divisions "
        . "WHERE ciniki_writingfestival_schedule_divisions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'scheduledivisions', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'ssection_id', 'name', 'division_date', 'address')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['scheduledivisions']) ) {
        $scheduledivisions = $rc['scheduledivisions'];
        $scheduledivision_ids = array();
        foreach($scheduledivisions as $iid => $scheduledivision) {
            $scheduledivision_ids[] = $scheduledivision['id'];
        }
    } else {
        $scheduledivisions = array();
        $scheduledivision_ids = array();
    }

    return array('stat'=>'ok', 'scheduledivisions'=>$scheduledivisions, 'nplist'=>$scheduledivision_ids);
}
?>
