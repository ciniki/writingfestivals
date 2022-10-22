<?php
//
// Description
// ===========
// This function will be a callback when an item is added to ciniki.sapos.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_writingfestivals_sapos_itemAdd($ciniki, $tnid, $invoice_id, $item) {

    //
    // An course was added to an invoice item, get the details and see if we need to 
    // create a registration for this course offering
    //
    if( isset($item['object']) && $item['object'] == 'ciniki.writingfestivals.class' && isset($item['object_id']) ) {
        //
        // Load current festival
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'loadCurrentFestival');
        $rc = ciniki_writingfestivals_loadCurrentFestival($ciniki, $tnid);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.390', 'msg'=>'', 'err'=>$rc['err']));
        }
        $festival = $rc['festival'];
       
        //
        // Search classes by code or name
        //
        $strsql = "SELECT classes.id, "
            . "classes.code, "
            . "classes.name, "
            . "classes.earlybird_fee, "
            . "classes.fee "
            . "FROM ciniki_writingfestival_classes AS classes "
            . "WHERE classes.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND classes.id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'class');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.388', 'msg'=>'Unable to load class', 'err'=>$rc['err']));
        }
        if( !isset($rc['class']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.389', 'msg'=>'Unable to find requested class'));
        }
        $class = $rc['class'];

        $fee = 0;
        $virtual = 0;
        if( isset($festival['earlybird']) && $festival['earlybird'] == 'yes' ) {
            $fee = $class['earlybird_fee'];
        } elseif( isset($festival['live']) && $festival['live'] == 'yes' ) {
            $fee = $class['fee'];
        } else {
            $fee = $class['fee'];
        }

        //
        // Load the customer for the invoice
        //
        $strsql = "SELECT id, customer_id "
            . "FROM ciniki_sapos_invoices "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['invoice']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.62', 'msg'=>'Unable to find invoice'));
        }
        $invoice = $rc['invoice'];
        

        //
        // Create the registration
        //
        $registration = array(
            'festival_id' => $festival['id'],
            'billing_customer_id' => $invoice['customer_id'],
            'teacher_customer_id' => 0,
            'rtype' => 30,
            'status' => 6,
            'invoice_id' => $invoice_id,
            'display_name' => '',
            'public_name' => '',
            'competitor1_id' => 0,
            'competitor2_id' => 0,
            'competitor3_id' => 0,
            'competitor4_id' => 0,
            'competitor5_id' => 0,
            'class_id' => $class['id'],
            'timeslot_id' => 0,
            'title' => '',
            'word_count' => '',
            'payment_type' => 0,
            'fee' => $fee,
            );

        //
        // Add the registration
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.writingfestivals.registration', $registration, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.351', 'msg'=>'Unable to add the registration', 'err'=>$rc['err']));
        }
        $reg_id = $rc['id'];
        
        return array('stat'=>'ok', 'object'=>'ciniki.writingfestivals.registration', 'object_id'=>$reg_id);
    }

    return array('stat'=>'ok');
}
?>
