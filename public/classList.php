<?php
//
// Description
// -----------
// This method will return the list of Classs for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Class for.
//
// Returns
// -------
//
function ciniki_writingfestivals_classList($ciniki) {
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
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.classList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of classes
    //
    $strsql = "SELECT ciniki_writingfestival_classes.id, "
        . "ciniki_writingfestival_classes.festival_id, "
        . "ciniki_writingfestival_classes.category_id, "
        . "ciniki_writingfestival_classes.code, "
        . "ciniki_writingfestival_classes.name, "
        . "ciniki_writingfestival_classes.permalink, "
        . "ciniki_writingfestival_classes.sequence, "
        . "ciniki_writingfestival_classes.flags, "
        . "ciniki_writingfestival_classes.earlybird_fee, "
        . "ciniki_writingfestival_classes.fee "
        . "FROM ciniki_writingfestival_classes "
        . "WHERE ciniki_writingfestival_classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'classes', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'category_id', 'code', 'name', 'permalink', 'sequence', 'flags', 'earlybird_fee', 'fee')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['classes']) ) {
        $classes = $rc['classes'];
        $class_ids = array();
        foreach($classes as $iid => $class) {
            $class_ids[] = $class['id'];
        }
    } else {
        $classes = array();
        $class_ids = array();
    }

    return array('stat'=>'ok', 'classes'=>$classes, 'nplist'=>$class_ids);
}
?>
