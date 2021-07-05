<?php
//
// Description
// ===========
// This function will lookup an item that is being added to a shopping cart online.  This function
// has extra checks to make sure the requested item is available to the customer.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_writingfestivals_sapos_cartItemLookup($ciniki, $tnid, $customer, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.186', 'msg'=>'No registration specified', 'err'=>$rc['err']));
    }

    //
    // Check to make sure the registration exists
    //
    if( $args['object'] == 'ciniki.writingfestivals.registration' ) {
        $item = $args;

        return array('stat'=>'ok', 'item'=>$item);
    }

    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.132', 'msg'=>'No registration specified.'));
}
?>
