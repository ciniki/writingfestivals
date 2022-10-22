<?php
//
// Description
// ===========
// This function searches the exhibit items for sale.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_writingfestivals_sapos_itemLookup($ciniki, $tnid, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.386', 'msg'=>'No item specified'));
    }

    //
    // Check a customer has been logged in
    //
    if( !isset($args['customer_id']) || $args['customer_id'] == 0 ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.writingfestivals.387', 'msg'=>'You must add a customer first'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Lookup the class 
    //
    if( $args['object'] == 'ciniki.writingfestivals.class' ) {
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
            . "AND classes.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'class');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.388', 'msg'=>'Unable to load class', 'err'=>$rc['err']));
        }
        if( !isset($rc['class']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.389', 'msg'=>'Unable to find requested class'));
        }
        $item = $rc['class'];
        
        $item['flags'] = 0x28;
        $item['description'] = $item['name'];
        $item['object'] = 'ciniki.writingfestivals.class';
        $item['object_id'] = $item['id'];
        $item['price_id'] = 0;
        $item['quantity'] = 1;
        $item['taxtype_id'] = 0;
        $item['notes'] = '';
        $item['unit_amount'] = $item['fee'];
        $item['unit_discount_amount'] = 0;
        $item['unit_discount_percentage'] = 0;
        if( $festival['earlybird'] == 'yes' && $item['earlybird_fee'] > 0 ) {
            $item['unit_amount'] = $item['earlybird_fee'];
        }

        return array('stat'=>'ok', 'item'=>$item);
    }

    return array('stat'=>'ok');        
}
?>
