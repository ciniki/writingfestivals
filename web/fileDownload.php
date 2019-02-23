<?php
//
// Description
// ===========
// This function will return the file details and content so it can be downloaded on the website.
//
// Returns
// -------
//
function ciniki_writingfestivals_web_fileDownload($ciniki, $tnid, $festival_id, $file_permalink) {

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_storage_dir = $rc['storage_dir'];

    //
    // Get the file details
    //
    $strsql = "SELECT ciniki_writingfestival_files.id, "
        . "ciniki_writingfestival_files.uuid, "
        . "ciniki_writingfestival_files.name, "
        . "ciniki_writingfestival_files.permalink, "
        . "ciniki_writingfestival_files.extension "
        . "FROM ciniki_writingfestival_files "
        . "WHERE ciniki_writingfestival_files.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_writingfestival_files.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "AND CONCAT_WS('.', ciniki_writingfestival_files.permalink, ciniki_writingfestival_files.extension) = '" . ciniki_core_dbQuote($ciniki, $file_permalink) . "' "
        . "AND (ciniki_writingfestival_files.webflags&0x01) > 0 "       // Make sure file is to be visible
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.writingfestivals.64', 'msg'=>'Unable to find requested file'));
    }
    $rc['file']['filename'] = $rc['file']['name'] . '.' . $rc['file']['extension'];

    //
    // Get the storage filename
    //
    $storage_filename = $tenant_storage_dir . '/ciniki.writingfestivals/files/' . $rc['file']['uuid'][0] . '/' . $rc['file']['uuid'];
    if( file_exists($storage_filename) ) {
        $rc['file']['binary_content'] = file_get_contents($storage_filename);    
    }

    return array('stat'=>'ok', 'file'=>$rc['file']);
}
?>
