<?php
//
// Description
// ===========
// This method will return all the information about an competitor.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the competitor is attached to.
// competitor_id:          The ID of the competitor to get the details for.
//
// Returns
// -------
//
function ciniki_writingfestivals_competitorGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'competitor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Competitor'),
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
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.competitorGet');
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
    // Return default for new Competitor
    //
    if( $args['competitor_id'] == 0 ) {
        $competitor = array('id'=>0,
            'festival_id'=>'',
            'name'=>'',
            'public_name'=>'',
            'flags'=>'0',
            'parent'=>'',
            'address'=>'',
            'city'=>'',
            'province'=>'',
            'postal'=>'',
            'phone_home'=>'',
            'phone_cell'=>'',
            'email'=>'',
            'age'=>'',
            'notes'=>'',
        );
        $details = array();
    }

    //
    // Get the details for an existing Competitor
    //
    else {
        $strsql = "SELECT ciniki_writingfestival_competitors.id, "
            . "ciniki_writingfestival_competitors.festival_id, "
            . "ciniki_writingfestival_competitors.name, "
            . "ciniki_writingfestival_competitors.public_name, "
            . "ciniki_writingfestival_competitors.flags, "
            . "ciniki_writingfestival_competitors.parent, "
            . "ciniki_writingfestival_competitors.address, "
            . "ciniki_writingfestival_competitors.city, "
            . "ciniki_writingfestival_competitors.province, "
            . "ciniki_writingfestival_competitors.postal, "
            . "ciniki_writingfestival_competitors.phone_home, "
            . "ciniki_writingfestival_competitors.phone_cell, "
            . "ciniki_writingfestival_competitors.email, "
            . "ciniki_writingfestival_competitors.age AS _age, "
            . "ciniki_writingfestival_competitors.notes "
            . "FROM ciniki_writingfestival_competitors "
            . "WHERE ciniki_writingfestival_competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_writingfestival_competitors.id = '" . ciniki_core_dbQuote($ciniki, $args['competitor_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
            array('container'=>'competitors', 'fname'=>'id', 
                'fields'=>array('festival_id', 'name', 'public_name', 'flags',
                    'parent', 'address', 'city', 'province', 'postal', 'phone_home', 'phone_cell', 
                    'email', '_age', 'notes'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.76', 'msg'=>'Competitor not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['competitors'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.77', 'msg'=>'Unable to find Competitor'));
        }
        $competitor = $rc['competitors'][0];
        $competitor['age'] = $competitor['_age'];
        if( $competitor['public_name'] == '' ) {
            $competitor['public_name'] = preg_replace("/^(.).*\s([^\s]+)$/", '$1. $2', $competitor['name']); 
        }
        $details = array();
        $details[] = array('label'=>'Name', 'value'=>$competitor['name']);
        if( $competitor['parent'] != '' ) { $details[] = array('label'=>'Parent', 'value'=>$competitor['parent']); }
        $address = '';
        if( $competitor['address'] != '' ) { $address .= $competitor['address']; }
        $city = $competitor['city'];
        if( $competitor['province'] != '' ) { $city .= ($city != '' ? ", " : '') . $competitor['province']; }
        if( $competitor['postal'] != '' ) { $city .= ($city != '' ? "  " : '') . $competitor['postal']; }
        if( $city != '' ) { $address .= ($address != '' ? "\n" : '' ) . $city; }
        if( $address != '' ) {
            $details[] = array('label'=>'Address', 'value'=>$address);
        }
        if( $competitor['phone_home'] != '' ) { $details[] = array('label'=>'Home', 'value'=>$competitor['phone_home']); }
        if( $competitor['phone_cell'] != '' ) { $details[] = array('label'=>'Cell', 'value'=>$competitor['phone_cell']); }
        if( $competitor['email'] != '' ) { $details[] = array('label'=>'Email', 'value'=>$competitor['email']); }
        if( $competitor['age'] != '' ) { $details[] = array('label'=>'Age', 'value'=>$competitor['age']); }
        if( ($competitor['flags']&0x01) == 0x01 ) { $details[] = array('label'=>'Waiver', 'value'=>'Signed'); }
    }

    return array('stat'=>'ok', 'competitor'=>$competitor, 'details'=>$details);
}
?>
