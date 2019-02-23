<?php
//
// Description
// ===========
// This method will return all the information about an schedule section.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the schedule section is attached to.
// schedulesection_id:          The ID of the schedule section to get the details for.
//
// Returns
// -------
//
function ciniki_writingfestivals_scheduleSectionGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'schedulesection_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Schedule Section'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'checkAccess');
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.scheduleSectionGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Schedule Section
    //
    if( $args['schedulesection_id'] == 0 ) {
        $schedulesection = array('id'=>0,
            'festival_id'=>'',
            'name'=>'',
        );
    }

    //
    // Get the details for an existing Schedule Section
    //
    else {
        $strsql = "SELECT ciniki_writingfestival_schedule_sections.id, "
            . "ciniki_writingfestival_schedule_sections.festival_id, "
            . "ciniki_writingfestival_schedule_sections.name, "
            . "ciniki_writingfestival_schedule_sections.adjudicator1_id, "
            . "ciniki_writingfestival_schedule_sections.adjudicator2_id, "
            . "ciniki_writingfestival_schedule_sections.adjudicator3_id "
            . "FROM ciniki_writingfestival_schedule_sections "
            . "WHERE ciniki_writingfestival_schedule_sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_writingfestival_schedule_sections.id = '" . ciniki_core_dbQuote($ciniki, $args['schedulesection_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
            array('container'=>'schedulesections', 'fname'=>'id', 
                'fields'=>array('festival_id', 'name', 'adjudicator1_id', 'adjudicator2_id', 'adjudicator3_id'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.88', 'msg'=>'Schedule Section not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['schedulesections'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.89', 'msg'=>'Unable to find Schedule Section'));
        }
        $schedulesection = $rc['schedulesections'][0];
    }

    $rsp = array('stat'=>'ok', 'schedulesection'=>$schedulesection);

    //
    // Get the list of adjudicators
    //
    $strsql = "SELECT ciniki_writingfestival_adjudicators.id, "
        . "ciniki_writingfestival_adjudicators.customer_id, "
        . "ciniki_customers.display_name "
        . "FROM ciniki_writingfestival_adjudicators "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_writingfestival_adjudicators.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_writingfestival_adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_writingfestival_adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $schedulesection['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'adjudicators', 'fname'=>'id', 'fields'=>array('id', 'customer_id', 'name'=>'display_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['adjudicators']) ) {
        $rsp['adjudicators'] = $rc['adjudicators'];
    } else {
        $rsp['adjudicators'] = array();
    }

    return $rsp;
}
?>
