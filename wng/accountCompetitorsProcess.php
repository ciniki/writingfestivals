<?php
//
// Description
// -----------
// This function will check for competitors in the writing festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_writingfestivals_wng_accountCompetitorsProcess(&$ciniki, $tnid, &$request, $args) {

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/account/writingfestivalcompetitors';
    $display = 'list';

    //
    // Check for a cancel
    //
    if( isset($_POST['cancel']) && $_POST['cancel'] == 'Cancel' ) {
        if( isset($request['session']['account-writingfestivals-competitor-form-return']) ) {
            header("Location: {$request['session']['account-writingfestivals-competitor-form-return']}");
            exit;
        }
        header("Location: {$base_url}");
        exit;
    }

    //
    // Check for a request to add competitor from registration form
    //
    if( isset($_POST['f-action']) && $_POST['f-action'] == 'addcompetitor' ) {
        $request['session']['account-writingfestivals-registration-saved'] = $_POST;
        $return_url = $request['ssl_domain_base_url'] . '/account/writingfestivalregistrations';
        $request['session']['account-writingfestivals-competitor-form-return'] = $return_url;
    }

    //
    // Get the writing festival with the most recent date and status published
    //
    $strsql = "SELECT id, "
        . "name, "
        . "flags, "
        . "earlybird_date, "
        . "live_date, "
        . "virtual_date "
        . "FROM ciniki_writingfestivals "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND status = 30 "        // Published
        . "ORDER BY start_date DESC "
        . "LIMIT 1 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'festival');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.351', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
    }
    if( !isset($rc['festival']) ) {
        // No festivals published, no items to return
        return array('stat'=>'ok');
    }
    $festival = $rc['festival'];
    $now = new DateTime('now', new DateTimezone('UTC'));
    $earlybird_dt = new DateTime($festival['earlybird_date'], new DateTimezone('UTC'));
    $live_dt = new DateTime($festival['live_date'], new DateTimezone('UTC'));
    $virtual_dt = new DateTime($festival['virtual_date'], new DateTimezone('UTC'));
    $festival['earlybird'] = (($festival['flags']&0x01) == 0x01 && $earlybird_dt > $now ? 'yes' : 'no');
    $festival['live'] = (($festival['flags']&0x01) == 0x01 && $live_dt > $now ? 'yes' : 'no');
    $festival['virtual'] = (($festival['flags']&0x03) == 0x03 && $virtual_dt > $now ? 'yes' : 'no');

    //
    // Load the festival details
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_writingfestival_settings "
        . "WHERE ciniki_writingfestival_settings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_writingfestival_settings.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.writingfestivals', 'settings');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.352', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    if( isset($rc['settings']) ) {
        foreach($rc['settings'] as $k => $v) {
            $festival[$k] = $v;
        }
    }

//    if( !isset($festival['waiver-msg']) || $festival['waiver-msg'] == '' ) {
//        $festival['waiver-msg'] = 'Terms and Conditions';
//    }
    if( !isset($festival['waiver-title']) || $festival['waiver-title'] == '' ) {
        $festival['waiver-title'] = 'Terms and Conditions';
    }

    //
    // Load the customer type, or ask for customer type
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'accountCustomerTypeProcess');
    $rc = ciniki_writingfestivals_wng_accountCustomerTypeProcess($ciniki, $tnid, $request, array(
        'festival' => $festival,
        'base_url' => $base_url,
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['stop']) && $rc['stop'] == 'yes' ) {
        // 
        // Return form with select customer type
        //
        return $rc;
    }
    $customer_type = $rc['customer_type'];
    if( isset($rc['switch_block']) ) {
//        $customer_switch_type_block = $rc['switch_block'];
    }

    //
    // Get the list of competitors
    //
    $strsql = "SELECT competitors.id, "
        . "competitors.name, "
        . "competitors.parent, "
        . "competitors.age "
        . "FROM ciniki_writingfestival_competitors AS competitors "
        . "WHERE competitors.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND competitors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND competitors.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "ORDER BY competitors.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'competitors', 'fname'=>'id', 'fields'=>array('id', 'name', 'parent', 'age')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.257', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
    }
    $competitors = isset($rc['competitors']) ? $rc['competitors'] : array();

    //
    // Keep track of any errors that have occured
    //
    $errors = array();

    //
    // Check if competitor specified and load
    //
    if( isset($_POST['f-competitor_id']) && $_POST['f-competitor_id'] > 0 ) {
        $competitor_id = $_POST['f-competitor_id'];
        $strsql = "SELECT id AS competitor_id, "
            . "uuid, "
            . "billing_customer_id, "
            . "name, "
            . "flags, "
            . "public_name, "
            . "parent, "
            . "address, "
            . "city, "
            . "province, "
            . "postal, "
            . "phone_home, "
            . "phone_cell, "
            . "email, "
            . "age, "
            . "notes "
            . "FROM ciniki_writingfestival_competitors "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $competitor_id) . "' "
            . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'competitor');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.355', 'msg'=>'Unable to load competitor', 'err'=>$rc['err']));
        }
        if( !isset($rc['competitor']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.356', 'msg'=>'Unable to find requested competitor'));
            $errors[] = array(
                'msg' => 'Unable to find the specified customer',
                );
            $display = 'list';
        } else {
            $competitor = $rc['competitor'];
            $display = 'form';
        }
    }


    //
    // Setup the fields for the competitor
    //
    $fields = array(
        'competitor_id' => array(
            'id' => 'competitor_id',
            'label' => '',
            'ftype' => 'hidden',
            'value' => (isset($_POST['f-competitor_id']) ? trim($_POST['f-competitor_id']) : (isset($competitor) ? $competitor['id'] : 0)),
            ),
        'action' => array(
            'id' => 'action',
            'label' => '',
            'ftype' => 'hidden',
            'value' => 'update',
            ),
        'name' => array(
            'id' => 'name',
            'label' => 'First & Last Name',
            'ftype' => 'text',
            'required' => 'yes',
            'size' => ($customer_type == 30 ? 'large' : 'medium'),
            'class' => '',
            'value' => (isset($_POST['f-name']) ? trim($_POST['f-name']) : (isset($competitor['name']) ? $competitor['name'] : '')),
            ),
        'parent' => array(
            'id' => 'parent',
            'label' => 'Parent',
            'ftype' => ($customer_type == 30 ? 'hidden' : 'text'),
            'required' => ($customer_type == 30 ? 'no' : 'yes'),
            'size' => 'medium',
            'class' => '',
            'value' => (isset($_POST['f-parent']) ? trim($_POST['f-parent']) : (isset($competitor['parent']) ? $competitor['parent'] :'')),
            ),
        'address' => array(
            'id' => 'address',
            'label' => 'Address',
            'ftype' => 'text',
            'required' => 'yes',
            'size' => 'large',
            'class' => '',
            'value' => (isset($_POST['f-address']) ? trim($_POST['f-address']) : (isset($competitor['address']) ? $competitor['address'] :'')),
            ),
        'city' => array(
            'id' => 'city',
            'label' => 'City',
            'ftype' => 'text',
            'required' => 'yes',
            'size' => 'small-medium',
            'class' => '',
            'value' => (isset($_POST['f-city']) ? trim($_POST['f-city']) : (isset($competitor['city']) ? $competitor['city'] :'')),
            ),
        'province' => array(
            'id' => 'province',
            'label' => 'Province',
            'ftype' => 'text',
            'required' => 'yes',
            'size' => 'small',
            'class' => '',
            'value' => (isset($_POST['f-province']) ? trim($_POST['f-province']) : (isset($competitor['province']) ? $competitor['province'] :'')),
            ),
        'postal' => array(
            'id' => 'postal',
            'label' => 'Postal',
            'ftype' => 'text',
            'required' => 'yes',
            'size' => 'small',
            'class' => '',
            'value' => (isset($_POST['f-postal']) ? trim($_POST['f-postal']) : (isset($competitor['postal']) ? $competitor['postal'] :'')),
            ),
        'phone_cell' => array(
            'id' => 'phone_cell',
            'label' => 'Cell Phone',
            'ftype' => 'text',
            'required' => 'yes',
            'size' => 'small',
            'class' => '',
            'value' => (isset($_POST['f-phone_cell']) ? trim($_POST['f-phone_cell']) : (isset($competitor['phone_cell']) ? $competitor['phone_cell'] :'')),
            ),
        'phone_home' => array(
            'id' => 'phone_home',
            'label' => 'Home Phone',
            'ftype' => 'text',
            'size' => 'small',
            'class' => '',
            'value' => (isset($_POST['f-phone_home']) ? trim($_POST['f-phone_home']) : (isset($competitor['phone_home']) ? $competitor['phone_home'] :'')),
            ),
        'age' => array(
            'id' => 'age',
            'label' => 'Age' . (isset($festival['age-restriction-msg']) ? ' ' . $festival['age-restriction-msg'] : ''),
            'ftype' => 'text',
            'required' => 'yes',
            'size' => 'small',
            'class' => '',
            'value' => (isset($_POST['f-age']) ? trim($_POST['f-age']) : (isset($competitor['age']) ? $competitor['age'] :'')),
            ),
        'email' => array(
            'id' => 'email',
            'label' => 'Email',
            'ftype' => 'text',
            'required' => 'yes',
            'size' => 'small-medium',
            'class' => '',
            'value' => (isset($_POST['f-email']) ? trim($_POST['f-email']) : (isset($competitor['email']) ? $competitor['email'] :'')),
            ),
        'break;' => array(
            'id' => 'break',
            'ftype' => 'break',
            ),
        'notes' => array(
            'id' => 'notes',
            'label' => 'Notes',
            'ftype' => 'textarea',
            'size' => 'tiny',
            'class' => '',
            'value' => (isset($_POST['f-notes']) ? trim($_POST['f-notes']) : (isset($competitor['notes']) ? $competitor['notes'] :'')),
            ),
        );
            error_log(print_r($festival,true));
    if( isset($festival['waiver-msg']) && $festival['waiver-msg'] != '' ) {
        $fields['termstitle'] = array(
            'id' => "termstitle",
            'label' => $festival['waiver-title'],
            'ftype' => 'content',
            'required' => 'yes',
            'size' => 'large',
//            'class' => 'hidden',
            'value' => '',
            );
        $fields['terms'] = array(
            'id' => "terms",
            'label' => $festival['waiver-msg'],
            'ftype' => 'checkbox',
            'size' => 'large',
//            'class' => 'hidden',
            'value' => (isset($competitor['flags']) && ($competitor['flags']&0x01) == 0x01 ? 'on' : ''),
            );
        if( isset($_POST['f-action']) && $_POST['f-action'] == 'update' ) {
            if( isset($_POST['f-terms']) && $_POST['f-terms'] == 'on' ) {
                $fields['terms']['value'] = 'on';
            } else {
                $fields['terms']['value'] = '';
            }
        }
    }

    //
    // Check if the form is submitted
    //
    if( isset($_POST['f-competitor_id']) && isset($_POST['f-action']) && $_POST['f-action'] == 'update' && count($errors) == 0 ) {
        $competitor_id = $_POST['f-competitor_id'];
        $fields['competitor_id']['value'] = $_POST['f-competitor_id'];
        $display = 'form';
        foreach($fields as $field) {
            if( isset($field['required']) && $field['required'] == 'yes' && $field['value'] == '' && $field['id'] != 'termstitle' ) {
                $errors[] = array(
                    'msg' => 'You must specify the competitor ' . $field['label'],
                    );
            }
        }
        if( isset($festival['waiver-msg']) && $festival['waiver-msg'] != '' 
            && (!isset($fields['terms']['value']) || $fields['terms']['value'] != 'on') 
            ) {
            $errors[] = array(
                'msg' => "You must accept the {$festival['waiver-title']} for the competitor.",
                );
        }
        //
        // Check for duplicate child
        //
        if( $fields['competitor_id']['value'] == 0 
            || (isset($_POST['f-name']) && isset($competitor['name']) && $_POST['f-name'] != $competitor['name']) 
            ) {
            //
            // Check for a duplicate name
            //
            $strsql = "SELECT COUNT(*) AS num "
                . "FROM ciniki_writingfestival_competitors AS competitors "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
                . "AND name = '" . ciniki_core_dbQuote($ciniki, $fields['name']['value']) . "' "
                . "AND parent = '" . ciniki_core_dbQuote($ciniki, $fields['parent']['value']) . "' "
                . "AND billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
            $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.writingfestivals', 'num');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.354', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
            }
            if( $rc['num'] > 0 ) {
                $errors[] = array(
                    'msg' => "You already have a competitor with this name.",
                    );
            }
        }
        //
        // If no errors add/update the competitor
        //
        if( count($errors) == 0 ) {
            if( $fields['competitor_id']['value'] == 0 ) {
                //
                // Create the competitor
                //
                $competitor = array(
                    'festival_id' => $festival['id'],
                    'billing_customer_id' => $request['session']['customer']['id'],
                    'name' => $fields['name']['value'],
                    'public_name' => preg_replace("/^(.).*\s([^\s]+)$/", '$1. $2', $fields['name']['value']),
                    'flags' => ($fields['terms']['value'] == 'on' ? 0x01 : 0),
                    'parent' => $fields['parent']['value'],
                    'address' => $fields['address']['value'],
                    'city' => $fields['city']['value'],
                    'province' => $fields['province']['value'],
                    'postal' => $fields['postal']['value'],
                    'phone_home' => $fields['phone_home']['value'],
                    'phone_cell' => $fields['phone_cell']['value'],
                    'email' => $fields['email']['value'],
                    'age' => $fields['age']['value'],
                    'notes' => $fields['notes']['value'],
                    );
                //
                // Add the competitor
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.writingfestivals.competitor', $competitor, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.353', 'msg'=>'Unable to add the competitor', 'err'=>$rc['err']));
                }
                if( isset($request['session']['account-writingfestivals-competitor-form-return']) ) {
                    $request['session']['account-writingfestivals-registration-saved']['new-id'] = $rc['id'];
                    header("Location: {$request['session']['account-writingfestivals-competitor-form-return']}");
                    return array('stat'=>'exit');
                }
                header("Location: {$request['ssl_domain_base_url']}/account/writingfestivalcompetitors");
                exit;
            } 
            else {
                $update_args = array();
                foreach($fields as $field) {
                    if( $field['ftype'] == 'content' || $field['ftype'] == 'hidden' || $field['id'] == 'terms' ) {
                        continue;
                    }
                    if( !isset($competitor[$field['id']]) || (isset($field['value']) && $field['value'] != $competitor[$field['id']]) ) {
                        $update_args[$field['id']] = $field['value'];
                    }
                }
                //
                // Update the competitor
                //
                if( count($update_args) > 0 ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.writingfestivals.competitor', $competitor_id, $update_args, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.357', 'msg'=>'Unable to update the competitor', 'err'=>$rc['err']));
                    }

                    //
                    // Update any registration this competitor is a part of
                    //
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'competitorUpdateNames');
                    $rc = ciniki_writingfestivals_competitorUpdateNames($ciniki, $tnid, $festival['id'], $competitor_id);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.328', 'msg'=>'Unable to update registrations', 'err'=>$rc['err']));
                    }

                }

                if( isset($request['session']['account-writingfestivals-competitor-form-return']) ) {
                    header("Location: {$request['session']['account-writingfestivals-competitor-form-return']}");
                    exit;
                }
                header("Location: {$request['ssl_domain_base_url']}/account/writingfestivalcompetitors");
                exit;
            }
        }
    }
    elseif( isset($_POST['f-delete']) && $_POST['f-delete'] == 'Remove' && isset($competitor) ) {
        //
        // Load the number of registrations for the competitor
        //
        $strsql = "SELECT COUNT(*) AS num "
            . "FROM ciniki_writingfestival_registrations "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ("
                . "competitor1_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                . "OR competitor2_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                . "OR competitor3_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                . "OR competitor4_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                . "OR competitor5_id = '" . ciniki_core_dbQuote($ciniki, $competitor['competitor_id']) . "' "
                . ") "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.writingfestivals', 'num');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.358', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
        }
        $num_items = isset($rc['num']) ? $rc['num'] : '';

        if( $num_items > 0 ) {
            $blocks[] = array(
                'type' => 'msg',
                'class' => 'limit-width limit-width-60',
                'level' => 'error',
                'content' => "There are still {$num_items} registration" . ($num_items > 1 ? 's' : '') . " for {$competitor['name']}, they cannot be removed.",
                );
            $display = 'list';
        } elseif( isset($_POST['submit']) && $_POST['submit'] == 'Remove Competitor'
            && isset($_POST['f-action']) && $_POST['f-action'] == 'confirmdelete'
            ) {
            $display = 'list';
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.writingfestivals.competitor', $competitor['competitor_id'], $competitor['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.359', 'msg'=>'Unable to remove competitor', 'err'=>$rc['err']));
            }
            header("Location: {$request['ssl_domain_base_url']}/account/writingfestivalcompetitors");
            exit;
        } else {
            $display = 'delete';
        }

    }
    elseif( isset($_GET['add']) && $_GET['add'] == 'yes' ) {
        $competitor_id = 0;
        if( $customer_type == 10 ) {
            $fields['parent']['value'] = $request['session']['customer']['display_name'];
        } elseif( $customer_type == 30 ) {
            $fields['name']['value'] = $request['session']['customer']['display_name'];
        }
        if( $customer_type == 10 || $customer_type == 30 ) {
            $fields['email']['value'] = $request['session']['customer']['email'];
            //
            // Lookup address
            //
            $strsql = "SELECT address1, "
                . "address2, "
                . "city, "
                . "province, "
                . "postal, "
                . "country "
                . "FROM ciniki_customer_addresses "
                . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "AND ciniki_customer_addresses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "ORDER BY flags DESC "
                . "LIMIT 1 "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'address');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.260', 'msg'=>'Unable to load address', 'err'=>$rc['err']));
            }
            if( isset($rc['address']) ) {
                $address = $rc['address']['address1'];
                if( $rc['address']['address2'] != '' ) {
                    $address .= ($address != '' ? ', ' : '') . $rc['address']['address2'];
                }
                $fields['address']['value'] = $address;
                $fields['city']['value'] = $rc['address']['city'];
                $fields['province']['value'] = $rc['address']['province'];
                $fields['postal']['value'] = $rc['address']['postal'];
            }
            //
            // Lookup phones
            //
            $strsql = "SELECT phone_label, "
                . "phone_number "
                . "FROM ciniki_customer_phones "
                . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "AND ciniki_customer_phones.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "ORDER BY flags DESC "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'address');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.360', 'msg'=>'Unable to load address', 'err'=>$rc['err']));
            }
            if( isset($rc['rows']) ) {
                foreach($rc['rows'] as $phone) {
                    if( $phone['phone_label'] == 'Cell' ) {
                        $fields['phone_cell']['value'] = $phone['phone_number'];
                    }
                    if( $phone['phone_label'] == 'Home' ) {
                        $fields['phone_home']['value'] = $phone['phone_number'];
                    }
                }
            }
        }
        $display = 'form';
    }

    
    //
    // Prepare any errors
    //
    $form_errors = '';
    if( isset($errors) && count($errors) > 0 ) {
        foreach($errors as $err) {
            $form_errors .= ($form_errors != '' ? '<br/>' : '') . $err['msg'];
        }
    }
   
    //
    // Show the competitor edit/add form
    //
    if( $display == 'form' ) {

        $blocks[] = array(
            'type' => 'form',
            'title' => ($competitor_id > 0 ? 'Update Competitor' : 'Add Competitor'),
            'class' => 'limit-width limit-width-60',
            'problem-list' => $form_errors,
            'cancel-label' => 'Cancel',
            'submit-label' => ($competitor_id > 0 ? 'Update Competitor' : 'Add Competitor'),
            'fields' => $fields,
            );
    }
    //
    // Show the delete form
    //
    elseif( $display == 'delete' ) {
        $blocks[] = array(
            'type' => 'form',
            'title' => 'Remove Competitor',
            'class' => 'limit-width limit-width-50',
            'cancel-label' => 'Cancel',
            'submit-label' => 'Remove Competitor',
            'fields' => array(
                'competitor_id' => array(
                    'id' => 'competitor_id',
                    'ftype' => 'hidden',
                    'value' => $competitor['competitor_id'],
                    ),
                'delete' => array(
                    'id' => 'delete',
                    'ftype' => 'hidden',
                    'value' => 'Remove',
                    ),
                'action' => array(
                    'id' => 'action',
                    'ftype' => 'hidden',
                    'value' => 'confirmdelete',
                    ),
                'msg' => array(
                    'id' => 'content',
                    'ftype' => 'content',
                    'label' => 'Are you sure you want to remove ' . $competitor['name'] . '?',
                    ),
                ),
            );
    }
    //
    // Show the list of competitors
    //
    else {
        if( $form_errors != '' ) { 
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error',
                'content' => $form_errors,
                );
        }
        if( count($competitors) > 0 ) {
            $add_button = '';
            //if( ($festival['flags']&0x01) == 0x01 ) {
            if( ($festival['flags']&0x01) == 0x01 && ($festival['live'] == 'yes' || $festival['virtual'] == 'yes') ) {
                foreach($competitors as $cid => $competitor) {
                    $competitors[$cid]['editbutton'] = "<form action='{$base_url}' method='POST'>"
                        . "<input type='hidden' name='f-competitor_id' value='{$cid}' />"
                        . "<input type='hidden' name='action' value='edit' />"
                        . "<input class='button' type='submit' name='submit' value='Edit'>"
                        . "<input class='button' type='submit' name='f-delete' value='Remove'>"
                        . "</form>";
                }
                $add_button = "<a class='button' href='/account/writingfestivalcompetitors?add=yes'>Add</a>";
            }
            if( $customer_type == 10 ) {
                $blocks[] = array(
                    'type' => 'table',
                    'title' => $festival['name'] . ' Competitors',
                    'class' => 'limit-width limit-width-60',
                    'headers' => 'yes',
                    'columns' => array(
                        array('label' => 'Name', 'field' => 'name', 'class' => 'alignleft'),
                        array('label' => 'Age', 'field' => 'age', 'class' => 'alignleft'),
                        array('label' => $add_button, 'field' => 'editbutton', 'class' => 'buttons alignright'),
                        ),
                    'rows' => $competitors,
                    );
            } else {
                if( $customer_type == 30 ) {
                    $blocks[] = array(
                        'type' => 'table',
                        'title' => $festival['name'] . ' Competitors',
                        'class' => 'limit-width limit-width-60',
                        'headers' => 'yes',
                        'columns' => array(
                            array('label' => 'Name', 'field' => 'name', 'class' => 'alignleft'),
                            array('label' => 'Age', 'field' => 'age', 'class' => 'alignleft'),
                            array('label' => $add_button, 'field' => 'editbutton', 'class' => 'buttons alignright'),
                            ),
                        'rows' => $competitors,
                        );
                } else {
                    $blocks[] = array(
                        'type' => 'table',
                        'title' => $festival['name'] . ' Competitors',
                        'class' => 'limit-width limit-width-60',
                        'headers' => 'yes',
                        'columns' => array(
                            array('label' => 'Name', 'field' => 'name', 'class' => 'alignleft'),
                            array('label' => 'Parent', 'field' => 'parent', 'class' => 'alignleft'),
                            array('label' => 'Age', 'field' => 'age', 'class' => 'alignleft'),
                            array('label' => $add_button, 'field' => 'editbutton', 'class' => 'buttons alignright'),
                            ),
                        'rows' => $competitors,
                        );
                }
            }
        } elseif( ($festival['flags']&0x01) == 0 ) {
            $blocks[] = array(
                'type' => 'text',
                'class' => 'limit-width limit-width-40',
                'title' => $festival['name'] . ' Competitors',
                'content' => 'Registrations closed',
                );
        } else {
            $blocks[] = array(
                'type' => 'text',
                'class' => 'limit-width limit-width-40',
                'title' => $festival['name'] . ' Competitors',
                'content' => 'No competitors',
                );
        }

        if( ($festival['flags']&0x01) == 0x01 && ($festival['live'] == 'yes' || $festival['virtual'] == 'yes') ) {
            $blocks[] = array(
                'type' => 'buttons',
                'class' => 'limit-width limit-width-40 aligncenter',
                'list' => array(array(
                    'text' => 'Add Competitor',
                    'url' => "/account/writingfestivalcompetitors?add=yes",
                    )),
                );
            if( isset($customer_switch_type_block) ) {
                $blocks[] = $customer_switch_type_block;
            }
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
