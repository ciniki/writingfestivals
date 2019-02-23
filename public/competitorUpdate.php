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
//
function ciniki_writingfestivals_competitorUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'competitor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Competitor'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'public_name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Public Name'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'parent'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Parent'),
        'address'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Address'),
        'city'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'City'),
        'province'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Province'),
        'postal'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Postal Code'),
        'phone_home'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Home Phone'),
        'phone_cell'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Cell Phone'),
        'email'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Email'),
        'age'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Age'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'),
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
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.competitorUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the competitor
    //
    $strsql = "SELECT ciniki_writingfestival_competitors.id, "
        . "ciniki_writingfestival_competitors.name, "
        . "ciniki_writingfestival_competitors.public_name "
        . "FROM ciniki_writingfestival_competitors "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['competitor_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'competitor');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.150', 'msg'=>'Unable to load competitor', 'err'=>$rc['err']));
    }
    if( !isset($rc['competitor']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.151', 'msg'=>'Unable to find requested competitor'));
    }
    $competitor = $rc['competitor'];

    //
    // If the public_name is same as calculated, the keep as blank, the public name is an override only field.
    //
    if( isset($args['public_name']) && $args['public_name'] != '' ) {
        if( isset($args['name']) ) {
            $public_name = preg_replace("/^(.).*\s([^\s]+)$/", '$1. $2', $args['name']); 
        } else {
            $public_name = preg_replace("/^(.).*\s([^\s]+)$/", '$1. $2', $competitor['name']); 
        }
        if( isset($args['public_name']) && $args['public_name'] == $public_name ) {
            $args['public_name'] = '';
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
    // Update the Competitor in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.writingfestivals.competitor', $args['competitor_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.writingfestivals');
        return $rc;
    }

    //
    // Update the competitor registrations
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'competitorUpdateNames');
    $rc = ciniki_writingfestivals_competitorUpdateNames($ciniki, $args['tnid'], $args['festival_id'], $args['competitor_id']);
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

    return array('stat'=>'ok');
}
?>
