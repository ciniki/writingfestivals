<?php
//
// Description
// ===========
// This method will be called whenever a item is updated in an invoice.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_writingfestivals_sapos_itemDelete($ciniki, $tnid, $invoice_id, $item) {

    //
    // An writing festival was added to an invoice item, get the details and see if we need to 
    // create a registration for this writing festival
    //
    if( isset($item['object']) && $item['object'] == 'ciniki.writingfestivals.registration' && isset($item['object_id']) ) {
        //
        // Check the writing festival registration exists
        //
        $strsql = "SELECT id, uuid, festival_id, invoice_id, billing_customer_id "
            . "FROM ciniki_writingfestival_registrations "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'registration');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['registration']) ) {
            // Don't worry if can't find existing reg, probably database error
            return array('stat'=>'ok');
        }
        $registration = $rc['registration'];

        //
        // Remove the registration
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
        $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.writingfestivals.registration',
            $registration['id'], $registration['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
