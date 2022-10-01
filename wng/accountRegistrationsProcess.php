<?php
//
// Description
// -----------
// This function will check for registrations in the writing festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_writingfestivals_wng_accountRegistrationsProcess(&$ciniki, $tnid, &$request, $args) {

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = $request['ssl_domain_base_url'] . '/account/writingfestivalregistrations';
    $display = 'list';
    $form_errors = '';
    $errors = array();

    //
    // Check for a cancel
    //
    if( (isset($_POST['cancel']) && $_POST['cancel'] == 'Cancel')
        || (isset($_POST['f-action']) && $_POST['f-action'] == 'cancel')
        ) {
        if( isset($request['session']['account-writingfestivals-registration-return-url']) ) {
            header("Location: {$request['session']['account-writingfestivals-registration-return-url']}");
            unset($request['session']['account-writingfestivals-registration-return-url']);
            return array('stat'=>'exit');
        }
        header("Location: {$base_url}");
        return array('stat'=>'exit');
    }

    if( isset($request['session']['account-writingfestivals-registration-saved']) ) {
        $_POST = $request['session']['account-writingfestivals-registration-saved'];
        if( isset($request['session']['account-writingfestivals-registration-saved']['new-id']) ) {
            for($i=1;$i<5;$i++) {
                if( isset($_POST["f-competitor{$i}_id"]) && $_POST["f-competitor{$i}_id"] == -1 ) {
                    $_POST["f-competitor{$i}_id"] = $request['session']['account-writingfestivals-registration-saved']['new-id'];
                }
            }
        }
        unset($request['session']['account-writingfestivals-registration-saved']);
    }
    if( isset($request['session']['account-writingfestivals-competitor-form-return']) ) {
        unset($request['session']['account-writingfestivals-competitor-form-return']);
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
        . "AND status = 30 "        // Current
        . "ORDER BY start_date DESC "
        . "LIMIT 1 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'festival');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.259', 'msg'=>'Unable to load festival', 'err'=>$rc['err']));
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
    // Load the customer type, or ask for customer type
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'accountCustomerTypeProcess');
    $rc = ciniki_writingfestivals_wng_accountCustomerTypeProcess($ciniki, $tnid, $request, array(
        'festival' => $festival,
        'base_url' => $base_url,
        ));
    if( $rc['stat'] == 'exit' ) {
        return $rc;
    }
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.362', 'msg'=>'Unable to ', 'err'=>$rc['err']));
    }
    if( isset($rc['stop']) && $rc['stop'] == 'yes' ) {
        // 
        // Return form with select customer type
        //
        return $rc;
    }
    $customer_type = $rc['customer_type'];
    if( isset($rc['switch_block']) ) {
        $customer_switch_type_block = $rc['switch_block'];
    }

    //
    // Get the list of competitors
    // Search only current festival, as ages will have changed from previous festivals
    //
    $strsql = "SELECT competitors.id, "
        . "competitors.name "
        . "FROM ciniki_writingfestival_competitors AS competitors "
        . "WHERE billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "ORDER BY competitors.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'competitors', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.325', 'msg'=>'Unable to load competitors', 'err'=>$rc['err']));
    }
    $competitors = isset($rc['competitors']) ? $rc['competitors'] : array();

    //
    // Load the class list
    //
    $strsql = "SELECT s.id AS section_id, "
        . "s.name AS section_name, "
        . "ca.name AS category_name, "
        . "cl.id AS class_id, "
        . "cl.uuid AS class_uuid, "
        . "cl.code AS class_code, "
        . "cl.name AS class_name, "
        . "cl.flags AS class_flags, "
        . "cl.earlybird_fee, "
        . "cl.fee, "
        . "cl.virtual_fee "
        . "FROM ciniki_writingfestival_sections AS s "
        . "INNER JOIN ciniki_writingfestival_categories AS ca ON ("
            . "s.id = ca.section_id "
            . "AND ca.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_writingfestival_classes AS cl ON ("
            . "ca.id = cl.category_id "
            . "AND cl.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE s.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
        . "AND s.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (s.flags&0x01) = 0 "
        . "ORDER BY s.sequence, s.name, ca.sequence, ca.name, cl.sequence, cl.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'sections', 'fname'=>'section_id', 
            'fields'=>array('id'=>'section_id', 'name'=>'section_name'),
            ),
        array('container'=>'classes', 'fname'=>'class_id', 
            'fields'=>array('id'=>'class_id', 'uuid'=>'class_uuid', 'category_name', 'code'=>'class_code', 
                'name'=>'class_name', 'flags'=>'class_flags', 'earlybird_fee', 'fee', 'virtual_fee'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.298', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    //
    // Load the teachers
    //
    $teachers = array();
    if( $customer_type != 20 ) {
        $strsql = "SELECT customers.id, "
            . "customers.display_name "
            . "FROM ciniki_writingfestival_registrations AS registrations "
            . "INNER JOIN ciniki_customers AS customers ON ("
                . "registrations.teacher_customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            // Search teachers from all previous festivals
//            . "AND registrations.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
            array('container'=>'teachers', 'fname'=>'id', 'fields'=>array('id', 'name'=>'display_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.297', 'msg'=>'Unable to load teachers', 'err'=>$rc['err']));
        }
        $teachers = isset($rc['teachers']) ? $rc['teachers'] : array();
    }

    //
    // Load the registration specified
    //
    if( isset($_POST['f-registration_id']) && $_POST['f-registration_id'] > 0 ) {
        $registration_id = $_POST['f-registration_id'];
        $strsql = "SELECT id AS registration_id, "
            . "uuid, "
            . "teacher_customer_id, "
            . "billing_customer_id, "
            . "rtype, "
            . "status, "
            . "invoice_id, "
            . "display_name, "
            . "public_name, "
            . "competitor1_id, "
            . "competitor2_id, "
            . "competitor3_id, "
            . "competitor4_id, "
            . "competitor5_id, "
            . "class_id, "
            . "title, "
            . "word_count, "
            . "fee, "
            . "notes "
            . "FROM ciniki_writingfestival_registrations "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $registration_id) . "' "
            . "AND billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'registration');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.368', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
        }
        if( !isset($rc['registration']) ) {
            $errors[] = array(
                'msg' => 'Unable to find the registration',
                );
            $display = 'list';
        } else {
            $registration = $rc['registration'];
            $display = 'form';
        }
    }
    elseif( isset($_GET['r']) && $_GET['r'] != '' ) {
        $registration_uuid = $_GET['r'];
        $strsql = "SELECT id AS registration_id, "
            . "uuid, "
            . "teacher_customer_id, "
            . "billing_customer_id, "
            . "rtype, "
            . "status, "
            . "invoice_id, "
            . "display_name, "
            . "public_name, "
            . "competitor1_id, "
//            . "competitor2_id, "
//            . "competitor3_id, "
//            . "competitor4_id, "
//            . "competitor5_id, "
            . "class_id, "
            . "title, "
            . "word_count, "
            . "fee, "
            . "notes "
            . "FROM ciniki_writingfestival_registrations "
            . "WHERE uuid = '" . ciniki_core_dbQuote($ciniki, $registration_uuid) . "' "
            . "AND billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "AND festival_id = '" . ciniki_core_dbQuote($ciniki, $festival['id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'registration');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.368', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
        }
        if( !isset($rc['registration']) ) {
            $errors[] = array(
                'msg' => 'Unable to find the registration',
                );
            $display = 'list';
        } else {
            $registration = $rc['registration'];
            $registration_id = $registration['registration_id'];
            $display = 'form';
            if( isset($_GET['ru']) && $_GET['ru'] != '' ) {
                $return_url = $request['ssl_domain_base_url'] . $_GET['ru'];
                $request['session']['account-writingfestivals-registration-return-url'] = $return_url;
            }
        }
    }


    //
    // Setup the fields for the form
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'registrationFormGenerate');
    $rc = ciniki_writingfestivals_wng_registrationFormGenerate($ciniki, $tnid, $request, array(
        'festival' => $festival,
        'competitors' => $competitors,
        'teachers' => $teachers,
        'registration' => isset($registration) ? $registration : array(),
        'customer_type' => $customer_type,
        'customer_id' => $request['session']['customer']['id'],
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.348', 'msg'=>'', 'err'=>$rc['err']));
    }
    $fields = $rc['fields'];
    $js = $rc['js'];
    $sections = $rc['sections'];
    if( isset($rc['selected_section']) ) {
        $selected_section = $rc['selected_section'];
    }
    if( isset($rc['selected_class']) ) {
        $selected_class = $rc['selected_class'];
    }

    //
    // Check if form submitted
    //
    if( isset($_POST['f-registration_id']) && isset($_POST['f-action']) && $_POST['f-action'] == 'addcompetitor' && count($errors) == 0 ) {
        //
        // Returning from add competitor form
        //
        $registration_id = $_POST['f-registration_id'];
        $display = 'form';
    }
    elseif( isset($_POST['f-registration_id']) && isset($_POST['f-action']) && $_POST['f-action'] == 'update' && count($errors) == 0 ) {
        $registration_id = $_POST['f-registration_id'];
        $display = 'form';
        foreach($fields as $field) {
            if( $field['ftype'] == 'line'
                || ($field['id'] == 'parent' && $customer_type == 30)
                ) {
                continue;
            }
            if( isset($field['required']) && $field['required'] == 'yes' && $field['value'] == '' && $field['id'] != 'termstitle' ) {
                $errors[] = array(
                    'msg' => 'You must specify the registration ' . $field['label'],
                    );
            }
        }

        //
        // Check if teacher needs to be setup
        //
/*        if( $customer_type == 20 ) {
            $registration['teacher_customer_id'] = $request['session']['customer']['id'];
        }
        elseif( $fields['teacher_customer_id']['value'] == -1 ) {
            if( $fields['teacher_email']['value'] == '' ) {
                $errors[] = array(
                    'msg' => 'You must specify your teachers email',
                    );
            } else {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'teacherCreate');
                $rc = ciniki_writingfestivals_wng_teacherCreate($ciniki, $tnid, $request, array());
                if( $rc['stat'] == 'ok' ) {
                    $fields['teacher_customer_id']['value'] = $rc['teacher_customer_id'];
                }
                elseif( $rc['stat'] == '404' ) {
                    return $rc;
                }
                elseif( $rc['stat'] == 'error' ) {
                    $errors[] = array(
                        'msg' => $rc['err']['msg'],
                        );
                } else {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.370', 'msg'=>'Unable to create teacher', 'err'=>$rc['err']));
                }
            }
        } */

        //
        // If the cart doesn't exist, create one now
        //
        if( count($errors) == 0 
            && (!isset($request['session']['cart']['id']) || $request['session']['cart']['id'] == 0)
            ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartCreate');
            $rc = ciniki_sapos_wng_cartCreate($ciniki, $tnid, $request, array());
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.310', 'msg'=>'Error opening cart', 'err'=>$rc['err']));
            }
        }
        //
        // If no errors, add/update the registration
        //
        if( $fields['registration_id']['value'] == 0 && count($errors) == 0 ) {
            //
            // Create the registration
            //
            $registration = array(
                'festival_id' => $festival['id'],
                'billing_customer_id' => $request['session']['customer']['id'],
                //'teacher_customer_id' => $fields['teacher_customer_id']['value'],
                'rtype' => 30,
                'status' => 6,
                'invoice_id' => $request['session']['cart']['id'],
                'display_name' => '',
                'public_name' => '',
                'competitor1_id' => $fields['competitor1_id']['value'],
                //'competitor2_id' => $fields['competitor2_id']['value'],
                //'competitor3_id' => $fields['competitor3_id']['value'],
                'class_id' => $selected_class['id'],
                'timeslot_id' => 0,
                'title' => $fields['title']['value'],
                'word_count' => $fields['word_count']['value'],
                'payment_type' => 0,
//                'virtual' => $fields['virtual']['value'],
//                'notes' => $fields['notes']['value'],
                );
            if( ($selected_class['flags']&0x20) == 0x20 ) {
                $registration['rtype'] = 60;
            } elseif( ($selected_class['flags']&0x10) == 0x10 ) {
                $registration['rtype'] = 50;
            }
            // Virtual pricing
            if( $festival['earlybird'] == 'yes' && $festival['earlybird_fee'] > 0 ) {
                $registration['fee'] = $selected_class['earlybird_fee'];
            } else {
                $registration['fee'] = $selected_class['fee'];
            }
            if( ($festival['flags']&0x04) == 0x04 && $fields['virtual']['value'] == 1 && $selected_class['virtual_fee'] > 0 ) {
                $registration['fee'] = $selected_class['virtual_fee'];
            }

            //
            // Add the registration
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.writingfestivals.registration', $registration, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.371', 'msg'=>'Unable to add the competitor', 'err'=>$rc['err']));
            }
            $registration_id = $rc['id'];

            //
            // Update the names
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'registrationNameUpdate');
            $rc = ciniki_writingfestivals_registrationNameUpdate($ciniki, $tnid, $registration_id);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.311', 'msg'=>'Unable to updated registration name', 'err'=>$rc['err']));
            }
            $registration['display_name'] = $rc['display_name'];
            $registration['public_name'] = $rc['public_name'];

            //
            // Add to the cart
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartItemAdd');
            $rc = ciniki_sapos_wng_cartItemAdd($ciniki, $tnid, $request, array(
                'object' => 'ciniki.writingfestivals.registration',
                'object_id' => $registration_id,
                'price_id' => 0,
                'quantity' => 1,
                'flags' => 0x08,
                'code' => $selected_class['code'],
                'description' => $selected_class['name'],
                'unit_amount' => $registration['fee'],
                'unit_discount_amount' => 0,
                'unit_discount_percentage' => 0,
                'taxtype_id' => 0,
                'notes' => $registration['display_name'] . ($registration['title'] != '' ? ' - ' . $registration['title'] : ''),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.309', 'msg'=>'Unable to add to cart', 'err'=>$rc['err']));
            }

            //
            // Reload cart
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartLoad');
            $rc = ciniki_sapos_wng_cartLoad($ciniki, $tnid, $request);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.332', 'msg'=>'Unable to load cart', 'err'=>$rc['err']));
            }
           
            if( isset($request['session']['account-writingfestivals-registration-return-url']) ) {
                header("Location: {$request['session']['account-writingfestivals-registration-return-url']}");
                unset($request['session']['account-writingfestivals-registration-return-url']);
                return array('stat'=>'exit');
            }

            header("Location: {$request['ssl_domain_base_url']}/account/writingfestivalregistrations");
            return array('stat'=>'exit');
        }
        elseif( count($errors) == 0 ) {
            //
            // Check for updates to selected class
            //
            if( ($selected_class['flags']&0x20) == 0x20 ) {
                $registration['rtype'] = 60;
            } elseif( ($selected_class['flags']&0x10) == 0x10 ) {
                $registration['rtype'] = 50;
            } else {
                $registration['rtype'] = 30;
            }
            // Virtual pricing
            if( $festival['earlybird'] == 'yes' && $festival['earlybird_fee'] > 0 ) {
                $new_fee = $selected_class['earlybird_fee'];
            } else {
                $new_fee = $selected_class['fee'];
            }
//            if( ($festival['flags']&0x04) == 0x04 && $fields['virtual']['value'] == 1 && $festival['virtual_fee'] > 0 ) {
//                $new_fee = $selected_class['virtual_fee'];
//            }

            $update_args = array();
            foreach($fields as $field) {
                if( $field['ftype'] == 'content' || $field['ftype'] == 'hidden' || $field['ftype'] == 'line' 
                    || strncmp($field['id'], 'section', 7) == 0 
                    || $field['id'] == 'teacher_name'
                    || $field['id'] == 'teacher_email'
                    || $field['id'] == 'teacher_phone'
                    ) {
                    continue;
                }
                if( strncmp($field['id'], 'section', 7) == 0 ) {
                    continue;
                }
                if( !isset($registration[$field['id']]) || $field['value'] != $registration[$field['id']] ) {
                    $update_args[$field['id']] = $field['value'];
                }
            }
            if( $selected_class['id'] != $registration['class_id'] ) {
                $update_args['class_id'] = $selected_class['id'];
            }
            if( isset($new_fee) && $new_fee != $registration['fee'] ) {
                $update_args['fee'] = $new_fee;
                $registration['fee'] = $new_fee;
            }
            //
            // Update the registration
            //
            if( count($update_args) > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.writingfestivals.registration', $registration_id, $update_args, 0x07);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.372', 'msg'=>'Unable to update the registration', 'err'=>$rc['err']));
                }

                //
                // Check if any names need changing
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'private', 'registrationNameUpdate');
                $rc = ciniki_writingfestivals_registrationNameUpdate($ciniki, $tnid, $registration_id);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.311', 'msg'=>'Unable to updated registration name', 'err'=>$rc['err']));
                }
                $registration['display_name'] = $rc['display_name'];
                $registration['public_name'] = $rc['public_name'];

                //
                // Load the cart item
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceObjectItem');
                $rc = ciniki_sapos_hooks_invoiceObjectItem($ciniki, $tnid, $request['session']['cart']['id'], 'ciniki.writingfestivals.registration', $registration_id);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.312', 'msg'=>'Unable to get invoice item', 'err'=>$rc['err']));
                }
                $item = $rc['item'];

                //
                // Check if anything changed in the cart
                //
                $update_item_args = array();
                $notes = $registration['display_name'] . ($registration['title'] != '' ? ' - ' . $registration['title'] : '');

                if( $item['code'] != $selected_class['code'] ) {
                    $update_item_args['code'] = $selected_class['code'];
                }
                if( $item['description'] != $selected_class['name'] ) {
                    $update_item_args['description'] = $selected_class['name'];
                }
                if( $item['unit_amount'] != $registration['fee'] ) {
                    $update_item_args['unit_amount'] = $registration['fee'];
                }
                if( $item['notes'] != $notes ) {
                    $update_item_args['notes'] = $notes;
                }
                if( count($update_item_args) > 0 ) {
                    $update_item_args['item_id'] = $item['id'];
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartItemUpdate');
                    $rc = ciniki_sapos_wng_cartItemUpdate($ciniki, $tnid, $request, $update_item_args);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.313', 'msg'=>'Unable to update invoice', 'err'=>$rc['err']));
                    }
                }
            }

            if( isset($request['session']['account-writingfestivals-registration-return-url']) ) {
                header("Location: {$request['session']['account-writingfestivals-registration-return-url']}");
                unset($request['session']['account-writingfestivals-registration-return-url']);
                return array('stat'=>'exit');
            }

            header("Location: {$request['ssl_domain_base_url']}/account/writingfestivalregistrations");
            return array('stat'=>'exit');
        }
    }
    elseif( isset($_POST['f-delete']) && $_POST['f-delete'] == 'Remove' && isset($registration) ) {
        //
        // Check if paid registration
        //
        if( $registration['status'] >= 10 ) {
            $blocks[] = array(
                'type' => 'msg',
                'class' => 'limit-width limit-width-60',
                'level' => 'error',
                'content' => "This registration has been paid, please contact us to cancel.",
                );
            $display = 'list';
        }
        elseif( isset($_POST['submit']) && $_POST['submit'] == 'Remove Registration'
            && isset($_POST['f-action']) && $_POST['f-action'] == 'confirmdelete'
            ) {
            //
            // Check for a defined cart
            //
            if( isset($request['session']['cart']['id']) ) {
                //
                // Load the cart item
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceObjectItem');
                $rc = ciniki_sapos_hooks_invoiceObjectItem($ciniki, $tnid, $request['session']['cart']['id'], 'ciniki.writingfestivals.registration', $registration['registration_id']);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.312', 'msg'=>'Unable to get invoice item', 'err'=>$rc['err']));
                }
                $item = $rc['item'];
                
                //
                // Remove the cart item
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartItemDelete');
                $rc = ciniki_sapos_wng_cartItemDelete($ciniki, $tnid, $request, array(
                    'item_id' => $item['id'],
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.329', 'msg'=>'Unable to remove registration', 'err'=>$rc['err']));
                }

                //
                // Reload cart
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartLoad');
                $rc = ciniki_sapos_wng_cartLoad($ciniki, $tnid, $request);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.332', 'msg'=>'Unable to load cart', 'err'=>$rc['err']));
                }

            } else {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
                $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.writingfestivals.registration', $registration['registration_id'], $registration['uuid'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.331', 'msg'=>'Unable to remove registration', 'err'=>$rc['err']));
                }
            }
           
            header("Location: {$request['ssl_domain_base_url']}/account/writingfestivalregistrations");
            return array('stat'=>'exit');
        }
        else {
            $display = 'delete';
        }
    }
    elseif( isset($_GET['r']) && $_GET['r'] == 'yes' ) {
        $display = 'form';
        if( $festival['live'] == 'no' && (($festival['flags']&0x02) == 0 || $festival['virtual'] == 'no') ) {
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error',
                'content' => 'Registrations are closed for ' . $festival['name'],
                );
            return array('stat' => 'ok', 'blocks'=>$blocks);
        }
    }
    elseif( isset($_GET['add']) && $_GET['add'] == 'yes' ) {
        //
        // Check if registrations are still open
        //
        if( $festival['live'] == 'no' && (($festival['flags']&0x02) == 0 || $festival['virtual'] == 'no') ) {
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error',
                'content' => 'Registrations are closed for ' . $festival['name'],
                );
            return array('stat' => 'ok', 'blocks'=>$blocks);
        }
        $registration_id = 0;
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
    // Show the registration edit/add form
    //
    if( $display == 'form' ) {
        $blocks[] = array(
            'type' => 'form',
            'form-id' => 'addregform',
            'title' => ($registration_id > 0 ? 'Update Registration' : 'Add Registration'),
            'class' => 'limit-width limit-width-60',
            'problem-list' => $form_errors,
            'cancel-label' => 'Cancel',
            'js-submit' => 'formSubmit();',
            'js-cancel' => 'formCancel();',
            'submit-label' => ($registration_id > 0 ? 'Update Registration' : 'Add Registration'),
            'fields' => $fields,
            'js' => $js,
            );
    }
    //
    // Show the delete form
    //
    elseif( $display == 'delete' ) {
        
        $blocks[] = array(
            'type' => 'form',
            'title' => 'Remove Registration',
            'class' => 'limit-width limit-width-50',
            'cancel-label' => 'Cancel',
            'submit-label' => 'Remove Registration',
            'fields' => array(
                'registration_id' => array(
                    'id' => 'registration_id',
                    'ftype' => 'hidden',
                    'value' => $registration['registration_id'],
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
                    'label' => 'Are you sure you want to remove ' . $registration['display_name'] . ' in ' . $selected_class['codename'] . '?',
                    ),
                ),
            );
    }
    //
    // Show the list of registrations
    //
    else {
        //
        // Get the list of registrations
        //
        $strsql = "SELECT registrations.id, "
            . "registrations.status, "
            . "registrations.invoice_id, "
            . "registrations.display_name, "
            . "registrations.title, "
            . "registrations.word_count, "
            . "registrations.fee, "
            . "classes.code AS class_code, "
            . "classes.name AS class_name, "
            . "CONCAT_WS(' - ', classes.code, classes.name) AS codename "
            . "FROM ciniki_writingfestival_registrations AS registrations "
            . "LEFT JOIN ciniki_writingfestival_classes AS classes ON ("
                . "registrations.class_id = classes.id "
                . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE registrations.billing_customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'status', 'invoice_id', 'display_name', 'class_code', 'class_name', 'codename', 'fee', 'title', 'word_count'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.300', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();
        $cart_registrations = array();
        $etransfer_registrations = array();
        $paid_registrations = array();
        $cancelled_registrations = array();
        foreach($registrations as $reg) {
            if( $reg['status'] == 6 ) {
                $cart_registrations[] = $reg;
            } elseif( $reg['status'] == 7 ) {
                $etransfer_registrations[] = $reg;
            } elseif( $reg['status'] == 50 ) {
                $paid_registrations[] = $reg;
            } elseif( $reg['status'] == 60 ) {
                $cancelled_registrations[] = $reg;
            }
        }

    
        if( $form_errors != '' ) { 
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error',
                'content' => $form_errors,
                );
        }
        if( ($festival['flags']&0x01) == 0x01 && ($festival['live'] == 'yes' || $festival['virtual'] == 'yes') ) {
            if( count($cart_registrations) > 0 ) {
                $add_button = "<a class='button' href='{$request['ssl_domain_base_url']}/account/writingfestivalregistrations?add=yes'>Add</a>";
                $total = 0;
                foreach($cart_registrations as $rid => $registration) {
                    $cart_registrations[$rid]['editbutton'] = "<form action='{$base_url}' method='POST'>"
                        . "<input type='hidden' name='f-registration_id' value='{$registration['id']}' />"
                        . "<input type='hidden' name='action' value='edit' />"
                        . "<input class='button' type='submit' name='submit' value='Edit'>"
                        . "<input class='button' type='submit' name='f-delete' value='Remove'>"
                        . "</form>";
                    $cart_registrations[$rid]['fee'] = '$' . number_format($registration['fee'], 2);
                    $total += $registration['fee'];
                }
                $blocks[] = array(
                    'type' => 'table',
                    'title' => $festival['name'] . ' Cart',
                    'class' => 'limit-width limit-width-60',
                    'headers' => 'yes',
                    'columns' => array(
                        array('label' => 'Competitor', 'field' => 'display_name', 'class' => 'alignleft'),
                        array('label' => 'Class', 'field' => 'codename', 'class' => 'alignleft'),
                        array('label' => 'Title', 'field' => 'title', 'class' => 'alignleft'),
                        array('label' => 'Fee', 'field' => 'fee', 'class' => 'alignright'),
                        array('label' => $add_button, 'field' => 'editbutton', 'class' => 'buttons alignright'),
                        ),
                    'footer' => array(
                        array('value' => '<b>Total</b>', 'colspan' => 3, 'class' => 'alignright'),
                        array('value' => '$' . number_format($total, 2), 'class' => 'alignright'),
                        array('value' => '', 'class' => 'alignright'),
                        ),
                    'rows' => $cart_registrations,
                    );
            } else {
                $blocks[] = array(
                    'type' => 'text',
                    'class' => 'limit-width limit-width-60',
                    'title' => $festival['name'] . ' Registrations',
                    'content' => 'No registrations',
                    );
            }
            $buttons = array(
                'type' => 'buttons',
                'class' => 'limit-width limit-width-60 aligncenter',
                'list' => array(array(
                    'text' => 'Add Registration',
                    'url' => "{$request['ssl_domain_base_url']}/account/writingfestivalregistrations?add=yes",
                    )),
                );
            if( count($cart_registrations) > 0 ) {
                $buttons['list'][] = array(
                    'text' => 'Checkout',
                    'url' => "/cart",
                    );
            }
            $blocks[] = $buttons;
        } else {
            $blocks[] = array(
                'type' => 'text',
                'class' => 'limit-width limit-width-60',
                'title' => $festival['name'] . ' Registrations',
                'content' => 'Registrations closed',
                );
        } 
        if( count($etransfer_registrations) > 0 ) {
            //
            // Format fee
            //
            $total = 0;
            foreach($etransfer_registrations as $rid => $registration) {
                $etransfer_registrations[$rid]['fee'] = '$' . number_format($registration['fee'], 2);
                $total += $registration['fee'];
            }
            $blocks[] = array(
                'type' => 'table',
                'title' => $festival['name'] . ' E-transfers Required',
                'class' => 'limit-width limit-width-60',
                'headers' => 'yes',
                'columns' => array(
                    array('label' => 'Competitor', 'field' => 'display_name', 'class' => 'alignleft'),
                    array('label' => 'Class', 'field' => 'codename', 'class' => 'alignleft'),
                    array('label' => 'Title', 'field' => 'title', 'class' => 'alignleft'),
                    array('label' => 'Fee', 'field' => 'fee', 'class' => 'alignright'),
                    ),
                'rows' => $etransfer_registrations,
                'footer' => array(
                    array('value' => '<b>Total</b>', 'colspan' => 3, 'class' => 'alignright'),
                    array('value' => '$' . number_format($total, 2), 'class' => 'alignright'),
                    ),
                );
            //
            // FIXME: add check to see if timeslot assigned and show
            //
           
            //
            // FIXME: Add button to download PDF list of registrations
            //
        }
        if( count($paid_registrations) > 0 ) {
            $blocks[] = array(
                'type' => 'table',
                'title' => $festival['name'] . ' Paid Registrations',
                'class' => 'limit-width limit-width-60',
                'headers' => 'yes',
                'columns' => array(
                    array('label' => 'Competitor', 'field' => 'display_name', 'class' => 'alignleft'),
                    array('label' => 'Class', 'field' => 'codename', 'class' => 'alignleft'),
                    array('label' => 'Title', 'field' => 'title', 'class' => 'alignleft'),
                    ),
                'rows' => $paid_registrations,
                );
            //
            // FIXME: add check to see if timeslot assigned and show
            //
           
            //
            // FIXME: Add button to download PDF list of registrations
            //
        }
        if( count($cancelled_registrations) > 0 ) {
            $blocks[] = array(
                'type' => 'table',
                'title' => $festival['name'] . ' Cancelled Registrations',
                'class' => 'limit-width limit-width-60',
                'headers' => 'yes',
                'columns' => array(
                    array('label' => 'Competitor', 'field' => 'display_name', 'class' => 'alignleft'),
                    array('label' => 'Class', 'field' => 'codename', 'class' => 'alignleft'),
                    array('label' => 'Title', 'field' => 'title', 'class' => 'alignleft'),
                    ),
                'rows' => $cancelled_registrations,
                );
            //
            // FIXME: add check to see if timeslot assigned and show
            //
           
            //
            // FIXME: Add button to download PDF list of registrations
            //
        }

        if( ($festival['flags']&0x01) == 0x01 && ($festival['live'] == 'yes' || $festival['virtual'] == 'yes') 
            && isset($customer_switch_type_block)
            ) {
            $blocks[] = $customer_switch_type_block;
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
