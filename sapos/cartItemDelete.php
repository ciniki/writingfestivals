<?php
//
// Description
// ===========
// This function 
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_writingfestivals_sapos_cartItemDelete($ciniki, $tnid, $invoice_id, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.131', 'msg'=>'No registration specified', 'err'=>$rc['err']));
    }

    //
    // Check to make sure the registration exists
    //
    if( $args['object'] == 'ciniki.writingfestivals.registration' ) {
        //
        // Get the current details for the registration
        //
        $strsql = "SELECT status "
            . "FROM ciniki_writingfestival_registrations "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.135', 'msg'=>'Unable to find registrations', 'err'=>$rc['err']));
        }
        if( !isset($rc['item']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.136', 'msg'=>'Unable to find registration'));
        }
        $item = $rc['item'];

        if( $item['status'] != 6 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.137', 'msg'=>'This registration cannot be removed.'));
        }

        //
        // Change the status back to Draft
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.writingfestivals.registration', $args['object_id'], array('status'=>5));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.138', 'msg'=>'Error trying to remove registration.', 'err'=>$rc['err']));
        }

        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
