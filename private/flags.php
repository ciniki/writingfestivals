<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_writingfestivals_flags(&$ciniki) {
    //
    // The flags for the object
    //
    $flags = array(
        // 0x01
//        array('flag'=>array('bit'=>'1', 'name'=>'')), // Registrations **flag not yet implemented**
        array('flag'=>array('bit'=>'2', 'name'=>'Online Registrations')),
//        array('flag'=>array('bit'=>'3', 'name'=>'Timeslot Photos')),
//        array('flag'=>array('bit'=>'4', 'name'=>'')),
        // 0x10
        array('flag'=>array('bit'=>'5', 'name'=>'Sponsors')),
//        array('flag'=>array('bit'=>'6', 'name'=>'Lists')),
//        array('flag'=>array('bit'=>'7', 'name'=>'Trophies')),
        array('flag'=>array('bit'=>'8', 'name'=>'Winners')),
        // 0x0100
//        array('flag'=>array('bit'=>'9', 'name'=>'Main Menu Festivals')),
//        array('flag'=>array('bit'=>'10', 'name'=>'')),
//        array('flag'=>array('bit'=>'11', 'name'=>'')),
//        array('flag'=>array('bit'=>'12', 'name'=>'')),
        // 0x1000
//        array('flag'=>array('bit'=>'13', 'name'=>'')),
//        array('flag'=>array('bit'=>'14', 'name'=>'')),
//        array('flag'=>array('bit'=>'15', 'name'=>'')),
//        array('flag'=>array('bit'=>'16', 'name'=>'')),
        );
    //
    return array('stat'=>'ok', 'flags'=>$flags);
}
?>
