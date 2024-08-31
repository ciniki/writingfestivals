<?php
//
// Description
// ===========
// This method will return all the information about an sponsor.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the sponsor is attached to.
// sponsor_id:          The ID of the sponsor to get the details for.
//
// Returns
// -------
//
function ciniki_writingfestivals_sponsorGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'sponsor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Sponsor'),
        'festival_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Festival'),
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
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.sponsorGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Sponsor
    //
    if( $args['sponsor_id'] == 0 ) {
        $seq = 1;
        if( $args['festival_id'] && $args['festival_id'] > 0 ) {
            $strsql = "SELECT MAX(sequence) AS max_sequence "
                . "FROM ciniki_writingfestival_sponsors "
                . "WHERE ciniki_writingfestival_sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_writingfestival_sponsors.festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'max');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['max']['max_sequence']) ) {
                $seq = $rc['max']['max_sequence'] + 1;
            }
        }
        $sponsor = array('id'=>0,
            'festival_id'=>'',
            'name'=>'',
            'url'=>'',
            'sequence'=>$seq,
            'flags'=>'0',
            'image_id'=>'0',
        );
    }

    //
    // Get the details for an existing Sponsor
    //
    else {
        $strsql = "SELECT ciniki_writingfestival_sponsors.id, "
            . "ciniki_writingfestival_sponsors.festival_id, "
            . "ciniki_writingfestival_sponsors.name, "
            . "ciniki_writingfestival_sponsors.url, "
            . "ciniki_writingfestival_sponsors.sequence, "
            . "ciniki_writingfestival_sponsors.flags, "
            . "ciniki_writingfestival_sponsors.image_id "
            . "FROM ciniki_writingfestival_sponsors "
            . "WHERE ciniki_writingfestival_sponsors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_writingfestival_sponsors.id = '" . ciniki_core_dbQuote($ciniki, $args['sponsor_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
            array('container'=>'sponsors', 'fname'=>'id', 
                'fields'=>array('festival_id', 'name', 'url', 'sequence', 'flags', 'image_id'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.225', 'msg'=>'Sponsor not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['sponsors'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.226', 'msg'=>'Unable to find Sponsor'));
        }
        $sponsor = $rc['sponsors'][0];
    }

    return array('stat'=>'ok', 'sponsor'=>$sponsor);
}
?>
