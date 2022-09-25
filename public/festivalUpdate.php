<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_writingfestivals_festivalUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'),
        'start_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Start'),
        'end_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'End'),
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'),
        'flags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Options'),
        'earlybird_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Earlybird End Date'),
        'live_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Live End Date'),
        'virtual_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Virtual End Date'),
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Primary Image'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        'document_logo_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Document Header Logo'),
        'document_header_msg'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Document Header Message'),
        'document_footer_msg'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Document Footer Message'),
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
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.festivalUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($args['name']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, name, permalink "
            . "FROM ciniki_writingfestivals "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.10', 'msg'=>'You already have an festival with this name, please choose another.'));
        }
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.writingfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Festival in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.writingfestivals.festival', $args['festival_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.writingfestivals');
        return $rc;
    }

    //
    // Update the Festival settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'settingsUpdate');
    $rc = ciniki_writingfestivals_settingsUpdate($ciniki, $args['tnid'], $args['festival_id'], $ciniki['request']['args']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.writingfestivals');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.writingfestivals');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'writingfestivals');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.writingfestivals.festival', 'object_id'=>$args['festival_id']));

    return array('stat'=>'ok');
}
?>
