<?php
//
// Description
// ===========
// This method returns the list of objects that can be returned
// as invoice items.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_writingfestivals_sapos_objectList($ciniki, $tnid) {

    $objects = array(
        //
        // this object should only be added to carts
        //
        'ciniki.writingfestivals.registration' => array(
            'name' => 'Writing Festival Registration',
            ),
        );

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
