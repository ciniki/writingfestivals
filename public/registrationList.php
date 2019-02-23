<?php
//
// Description
// -----------
// This method will return the list of Registrations for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Registration for.
//
// Returns
// -------
//
function ciniki_writingfestivals_registrationList($ciniki) {
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
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.registrationList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of registrations
    //
    $strsql = "SELECT ciniki_writingfestival_registrations.id, "
        . "ciniki_writingfestival_registrations.festival_id, "
        . "ciniki_writingfestival_registrations.teacher_customer_id, "
        . "ciniki_writingfestival_registrations.billing_customer_id, "
        . "ciniki_writingfestival_registrations.rtype, "
        . "ciniki_writingfestival_registrations.status, "
        . "ciniki_writingfestival_registrations.invoice_id, "
        . "ciniki_writingfestival_registrations.display_name, "
        . "ciniki_writingfestival_registrations.competitor1_id, "
        . "ciniki_writingfestival_registrations.competitor2_id, "
        . "ciniki_writingfestival_registrations.competitor3_id, "
        . "ciniki_writingfestival_registrations.competitor4_id, "
        . "ciniki_writingfestival_registrations.competitor5_id, "
        . "ciniki_writingfestival_registrations.class_id, "
        . "ciniki_writingfestival_registrations.title, "
        . "ciniki_writingfestival_registrations.word_count, "
        . "ciniki_writingfestival_registrations.fee, "
        . "ciniki_writingfestival_registrations.payment_type "
        . "FROM ciniki_writingfestival_registrations "
        . "WHERE ciniki_writingfestival_registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'festival_id', 'teacher_customer_id', 'billing_customer_id', 'rtype', 'status', 'invoice_id',
                'display_name', 'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id', 
                'class_id', 'title', 'word_count', 'fee', 'payment_type')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['registrations']) ) {
        $registrations = $rc['registrations'];
        $registration_ids = array();
        foreach($registrations as $iid => $registration) {
            $registration_ids[] = $registration['id'];
        }
    } else {
        $registrations = array();
        $registration_ids = array();
    }

    return array('stat'=>'ok', 'registrations'=>$registrations, 'nplist'=>$registration_ids);
}
?>
