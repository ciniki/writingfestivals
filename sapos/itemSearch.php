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
function ciniki_writingfestivals_sapos_itemSearch($ciniki, $tnid, $args) {

    if( $args['start_needle'] == '' ) {
        return array('stat'=>'ok', 'items'=>array());
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Load current festival
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'loadCurrentFestival');
    $rc = ciniki_writingfestivals_loadCurrentFestival($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.390', 'msg'=>'', 'err'=>$rc['err']));
    }
    if( !isset($rc['festival']) ) {
        return array('stat'=>'ok', 'items'=>array());
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
        . "AND (classes.code LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR classes.code LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR classes.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR classes.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
        . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.ags', array(
        array('container'=>'items', 'fname'=>'id',
            'fields'=>array('id', 'code', 'description'=>'name', 'earlybird_fee', 'fee')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $items = array();
    if( isset($rc['items']) ) {
        foreach($rc['items'] as $item) {
            $item['flags'] = 0x28;
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
            $items[] = array('item'=>$item);
        }
    }

    return array('stat'=>'ok', 'items'=>$items);        
}
?>
