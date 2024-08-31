<?php
//
// Description
// ===========
// This method will return all the information about an registration.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the registration is attached to.
// registration_id:          The ID of the registration to get the details for.
//
// Returns
// -------
//
function ciniki_writingfestivals_registrationGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'festival_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Festival'),
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'),
        'class_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Class'),
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
    $rc = ciniki_writingfestivals_checkAccess($ciniki, $args['tnid'], 'ciniki.writingfestivals.registrationGet');
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
    // Return default for new Registration
    //
    if( $args['registration_id'] == 0 ) {
        $registration = array('id'=>0,
            'festival_id'=>$args['festival_id'],
            'teacher_customer_id'=>'0',
            'billing_customer_id'=>'0',
            'rtype'=>30,
            'status'=>'',
            'invoice_id'=>'0',
            'display_name'=>'',
            'competitor1_id'=>'0',
            'competitor2_id'=>'0',
            'competitor3_id'=>'0',
            'competitor4_id'=>'0',
            'competitor5_id'=>'0',
            'class_id'=>(isset($args['class_id']) ? $args['class_id'] : 0),
            'title'=>'',
            'word_count'=>'',
            'fee'=>'0',
            'payment_type'=>'0',
            'pdf_filename'=>'',
            'notes'=>'',
        );
    }

    //
    // Get the details for an existing Registration
    //
    else {
        $strsql = "SELECT ciniki_writingfestival_registrations.id, "
            . "ciniki_writingfestival_registrations.festival_id, "
            . "ciniki_writingfestival_registrations.teacher_customer_id, "
            . "ciniki_writingfestival_registrations.billing_customer_id, "
            . "ciniki_writingfestival_registrations.rtype, "
            . "ciniki_writingfestival_registrations.status, "
            . "ciniki_writingfestival_registrations.invoice_id, "
            . "ciniki_writingfestival_registrations.display_name, "
            . "ciniki_writingfestival_registrations.competitor1_id, "
            . "ciniki_writingfestival_registrations.competitor2_id, "
            . "ciniki_writingfestival_registrations.competitor3_id, "
            . "ciniki_writingfestival_registrations.competitor4_id, "
            . "ciniki_writingfestival_registrations.competitor5_id, "
            . "ciniki_writingfestival_registrations.class_id, "
            . "ciniki_writingfestival_registrations.title, "
            . "ciniki_writingfestival_registrations.word_count, "
            . "FORMAT(ciniki_writingfestival_registrations.fee, 2) AS fee, "
            . "ciniki_writingfestival_registrations.payment_type, "
            . "ciniki_writingfestival_registrations.pdf_filename, "
            . "ciniki_writingfestival_registrations.notes "
            . "FROM ciniki_writingfestival_registrations "
            . "WHERE ciniki_writingfestival_registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_writingfestival_registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'festival_id', 'teacher_customer_id', 'billing_customer_id', 'rtype', 'status', 'invoice_id',
                    'display_name', 'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id', 
                    'class_id', 'title', 'word_count', 'fee', 'payment_type', 'pdf_filename', 'notes'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.73', 'msg'=>'Registration not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['registrations'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.74', 'msg'=>'Unable to find Registration'));
        }
        $registration = $rc['registrations'][0];

        //
        // Get the teacher details
        //
        if( isset($registration['teacher_customer_id']) && $registration['teacher_customer_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
            $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['tnid'], 
                array('customer_id'=>$registration['teacher_customer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $registration['teacher_details'] = $rc['details'];
        } else {
            $registration['teacher_details'] = array();
        }
       
        //
        // Get the competitor details
        //
        for($i = 1; $i <= 5; $i++) {
            if( $registration['competitor' . $i . '_id'] > 0 ) {
                $strsql = "SELECT ciniki_writingfestival_competitors.id, "
                    . "ciniki_writingfestival_competitors.festival_id, "
                    . "ciniki_writingfestival_competitors.name, "
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
                    . "AND ciniki_writingfestival_competitors.id = '" . ciniki_core_dbQuote($ciniki, $registration['competitor' . $i . '_id']) . "' "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
                $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
                    array('container'=>'competitors', 'fname'=>'id', 
                        'fields'=>array('festival_id', 'name', 'parent', 'address', 'city', 'province', 'postal', 'phone_home', 'phone_cell', 'email', '_age', 'notes'),
                        ),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.68', 'msg'=>'Competitor not found', 'err'=>$rc['err']));
                }
                if( !isset($rc['competitors'][0]) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.69', 'msg'=>'Unable to find Competitor'));
                }
                $competitor = $rc['competitors'][0];
                $competitor['age'] = $competitor['_age'];
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
                if( $competitor['age'] != '' ) { $details[] = array('label'=>'Age', 'value'=>$competitor['_age']); }
                $registration['competitor' . $i . '_details'] = $details;
            }
        }

        //
        // Load the invoice details
        //
        if( $registration['invoice_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceObjectItem');
            $rc = ciniki_sapos_hooks_invoiceObjectItem($ciniki, $args['tnid'], $registration['invoice_id'], 
                'ciniki.musicfestivals.registration', $registration['id']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['invoice']) ) {
                $registration['invoice_details'][] = array(
                    'label'=>'Invoice', 
                    'value'=>'#' . $rc['invoice']['invoice_number'] . ' - ' . $rc['invoice']['status_text'],
                    );
                if( $rc['invoice']['customer']['display_name'] != '' ) {
                    $registration['invoice_details'][] = array(
                        'label'=>'Customer', 
                        'value'=>$rc['invoice']['customer']['display_name'],
                        );
                }
                $registration['invoice_details'][] = array(
                    'label'=>'Type', 
                    'value'=>$rc['invoice']['invoice_type_text'],
                    );
                $registration['invoice_details'][] = array(
                    'label'=>'Date', 
                    'value'=>$rc['invoice']['invoice_date'],
                    );
                $registration['invoice_status'] = $rc['invoice']['status'];
            }
            if( isset($rc['item']) ) {
                $registration['item_id'] = $rc['item']['id'];
                $registration['unit_amount'] = $rc['item']['unit_amount_display'];
                $registration['unit_discount_amount'] = $rc['item']['unit_discount_amount_display'];
                $registration['unit_discount_percentage'] = $rc['item']['unit_discount_percentage'];
                $registration['taxtype_id'] = $rc['item']['taxtype_id'];
            } else {
                $registration['item_id'] = 0;
                $registration['unit_amount'] = '';
                $registration['unit_discount_amount'] = '';
                $registration['unit_discount_percentage'] = '';
                $registration['taxtype_id'] = 0;
            }
        }
    }

    $rsp = array('stat'=>'ok', 'registration'=>$registration, 'competitors'=>array(), 'classes'=>array());

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');


    //
    // Get the list of competitors
    //
/*    $strsql = "SELECT id, name "
        . "FROM ciniki_writingfestival_competitors "
        . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $args['festival_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY name "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'competitors', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( isset($rc['competitors']) ) {
        $rsp['competitors'] = $rc['competitors'];
    } */

    //
    // Get the list of classes
    //
    $strsql = "SELECT classes.id, "
        . "CONCAT_WS(' - ', classes.code, classes.name) AS name, "
        . "classes.flags, "
        . "FORMAT(classes.earlybird_fee, 2) AS earlybird_fee, "
        . "FORMAT(classes.fee, 2) AS fee "
        . "FROM ciniki_writingfestival_sections AS sections "
        . "INNER JOIN ciniki_writingfestival_categories AS categories ON ("
            . "sections.id = categories.section_id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_writingfestival_classes AS classes ON ("
            . "categories.id = classes.category_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE sections.festival_id = '" . ciniki_core_dbQuote($ciniki, $registration['festival_id']) . "' "
        . "AND sections.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY sections.sequence, sections.name, categories.sequence, categories.name, classes.sequence, classes.name "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'classes', 'fname'=>'id', 'fields'=>array('id', 'name', 'fee')),
        ));
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( isset($rc['classes']) ) {
        $rsp['classes'] = $rc['classes'];
    }

    //
    // Get the festival details
    //
    $strsql = "SELECT ciniki_writingfestivals.id, "
        . "ciniki_writingfestivals.name, "
        . "ciniki_writingfestivals.permalink, "
        . "ciniki_writingfestivals.start_date, "
        . "ciniki_writingfestivals.end_date, "
        . "ciniki_writingfestivals.status, "
        . "ciniki_writingfestivals.flags, "
        . "ciniki_writingfestivals.earlybird_date, "
        . "ciniki_writingfestivals.live_date, "
        . "ciniki_writingfestivals.primary_image_id, "
        . "ciniki_writingfestivals.description, "
        . "ciniki_writingfestivals.document_logo_id, "
        . "ciniki_writingfestivals.document_header_msg, "
        . "ciniki_writingfestivals.document_footer_msg "
        . "FROM ciniki_writingfestivals "
        . "WHERE ciniki_writingfestivals.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_writingfestivals.id = '" . ciniki_core_dbQuote($ciniki, $registration['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'festivals', 'fname'=>'id', 
            'fields'=>array('name', 'permalink', 'start_date', 'end_date', 'status', 'flags', 'earlybird_date', 'live_date',
                'primary_image_id', 'description', 
                'document_logo_id', 'document_header_msg', 'document_footer_msg'),
            'utctotz'=>array('start_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                'end_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                'earlybird_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.174', 'msg'=>'Festival not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['festivals'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.175', 'msg'=>'Unable to find Festival'));
    }
    $rsp['registration']['festival'] = $rc['festivals'][0];

    //
    // Determine which dates are still open for the festival
    //
    $now = new DateTime('now', new DateTimezone('UTC'));
    $earlybird_dt = new DateTime($rsp['registration']['festival']['earlybird_date'], new DateTimezone('UTC'));
    $live_dt = new DateTime($rsp['registration']['festival']['live_date'], new DateTimezone('UTC'));
    $rsp['registration']['festival']['earlybird'] = (($rsp['registration']['festival']['flags']&0x01) == 0x01 && $earlybird_dt > $now ? 'yes' : 'no');
    $rsp['registration']['festival']['live'] = (($rsp['registration']['festival']['flags']&0x01) == 0x01 && $live_dt > $now ? 'yes' : 'no');

    //
    // Get the festival settings
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_writingfestival_settings "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $registration['festival_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.writingfestivals', 'settings');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.205', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    foreach($rc['settings'] as $k => $v) {
        $rsp['registration']['festival'][$k] = $v;
    }

    return $rsp;
}
?>
