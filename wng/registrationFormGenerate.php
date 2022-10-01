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
function ciniki_writingfestivals_wng_registrationFormGenerate(&$ciniki, $tnid, &$request, $args) {

    if( !isset($ciniki['tenant']['modules']['ciniki.writingfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.341', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a festival was specified
    //
    if( !isset($args['festival']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.342', 'msg'=>"No festival specified"));
    }
    $festival = $args['festival'];

    //
    // Make sure competitors where passed in arguments
    //
    if( !isset($args['competitors']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.344', 'msg'=>"No competitors specified"));
    }
    $competitors = $args['competitors'];

    //
    // Make sure competitors where passed in arguments
    //
    if( !isset($args['teachers']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.345', 'msg'=>"No teachers specified"));
    }
    $teachers = $args['teachers'];

    //
    // Make sure competitors where passed in arguments
    //
    if( !isset($args['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.349', 'msg'=>"No registration specified"));
    }
    $registration = $args['registration'];

    //
    // Make sure customer type is passed
    //
    if( !isset($args['customer_type']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.350', 'msg'=>"No customer type specified"));
    }
    $customer_type = $args['customer_type'];

    //
    // Make sure customer specified
    //
    if( !isset($args['customer_id']) || $args['customer_id'] == '' || $args['customer_id'] < 1 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.347', 'msg'=>"No customer specified"));
    }

    //
    // Load the sections and classes
    //
    $strsql = "SELECT s.id AS section_id, "
        . "s.name AS section_name, "
        . "s.live_end_dt AS live_end_dt, "
        . "s.virtual_end_dt AS virtual_end_dt, "
        . "ca.name AS category_name, "
        . "cl.id AS class_id, "
        . "cl.uuid AS class_uuid, "
        . "cl.code AS class_code, "
        . "cl.name AS class_name, "
        . "CONCAT_WS(' - ', s.name, cl.code, cl.name) AS sectionclassname, "
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
            'fields'=>array('id'=>'section_id', 'name'=>'section_name', 'live_end_dt', 'virtual_end_dt'),
            ),
        array('container'=>'classes', 'fname'=>'class_id', 
            'fields'=>array('id'=>'class_id', 'uuid'=>'class_uuid', 'category_name', 'code'=>'class_code', 
                'name'=>'class_name', 'sectionclassname', 'flags'=>'class_flags', 'earlybird_fee', 'fee', 'vfee' => 'virtual_fee'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.299', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
    }
    $sections = isset($rc['sections']) ? $rc['sections'] : array();

    $dt = new DateTime('now', new DateTimezone('UTC'));

    //
    // Build the list of classes and find selected class
    //
    $classes_2c = array();  // Class id's with 2 competitors
    $classes_3c = array();  // Class id's with 3 competitors
    $classes_2t = array();  // Class id's with 2 title & times
    $classes_3t = array();  // Class id's with 3 title & times
    $live_prices = array();
    $virtual_prices = array();
    foreach($sections as $sid => $section) {
        $section_live = 'yes';
        $section_virtual = 'yes';
        if( ($festival['flags']&0x08) == 0x08 ) {
            if( $section['live_end_dt'] != '0000-00-00 00:00:00' ) {
                $section_live_dt = new DateTime($section['live_end_dt'], new DateTimezone('UTC'));
                if( $section_live_dt < $dt ) {
                    $section_live = 'no';
                }
            }
            if( $section['virtual_end_dt'] != '0000-00-00 00:00:00' ) {
                $section_virtual_dt = new DateTime($section['virtual_end_dt'], new DateTimezone('UTC'));
                if( $section_virtual_dt < $dt ) {
                    $section_virtual = 'no';
                }
            }
        }
        if( isset($section['classes']) ) {
            foreach($section['classes'] as $cid => $section_class) {
                if( ($section_class['flags']&0x10) == 0x10 ) {
                    $classes_2c[] = $cid;
                }
                if( ($section_class['flags']&0x20) == 0x20 ) {
                    $classes_3c[] = $cid;
                }
                if( ($section_class['flags']&0x1000) == 0x1000 ) {
                    $classes_2t[] = $cid;
                }
                if( ($section_class['flags']&0x2000) == 0x2000 ) {
                    $classes_3t[] = $cid;
                }
                if( $section_class['code'] != '' ) {
                    $sections[$sid]['classes'][$cid]['codename'] = $section_class['code'] . ' - ' . $section_class['name'];
                }
                if( isset($_GET['cl']) && $_GET['cl'] == $section_class['uuid'] ) {
                    $selected_sid = $sid;
                    $selected_cid = $cid;
                }
                //
                // Virtual option(0x02) and virtual pricing(0x04) set for festival
                //
                if( ($festival['flags']&0x06) == 0x06 ) {
                    if( $festival['earlybird'] == 'yes' && $section_live == 'yes' && $section_class['earlybird_fee'] > 0 ) {
                        $live_prices[$cid] = '$' . number_format($section_class['earlybird_fee'], 2);
                        $sections[$sid]['classes'][$cid]['live_fee'] = $section_class['earlybird_fee'];
                    } elseif( $festival['live'] == 'yes' && $section_live == 'yes' && $section_class['fee'] > 0 ) {
                        $live_prices[$cid] = '$' . number_format($section_class['fee'], 2);
                        $sections[$sid]['classes'][$cid]['live_fee'] = $section_class['fee'];
                    }
                    if( $festival['virtual'] == 'yes' && $section_virtual == 'yes' && $section_class['vfee'] > 0 ) {
                        $virtual_prices[$cid] = '$' . number_format($section_class['vfee'], 2);
                        $sections[$sid]['classes'][$cid]['virtual_fee'] = $section_class['vfee'];
                    }
                    //
                    // Check to see if class is still available for registration
                    //
                    if( !isset($sections[$sid]['classes'][$cid]['live_fee'])
                        && !isset($sections[$sid]['classes'][$cid]['virtual_fee'])
                        ) {
                        unset($sections[$sid]['classes'][$cid]);
                    }
                }
                //
                // Only virtual option set, with same pricing
                //
                elseif( ($festival['flags']&0x06) == 0x02 ) {
/*                    if( $festival['earlybird'] == 'yes' && $section_class['earlybird_fee'] > 0 ) {
                        $sections[$sid]['classes'][$cid]['live_fee'] = $section_class['earlybird_fee'];
                    } else {
                        $sections[$sid]['classes'][$cid]['live_fee'] = $section_class['fee'];
                    }
                    if( $festival['virtual'] == 'yes' && $section_class['vfee'] > 0 ) {
                        $sections[$sid]['classes'][$cid]['virtual_fee'] = $section_class['vfee'];
                    } */
                    //
                    // Check to see if class is still available for registration
                    //
/*                    if( !isset($sections[$sid]['classes'][$cid]['live_fee'])
                        && !isset($sections[$sid]['classes'][$cid]['virtual_fee'])
                        ) {
                        unset($sections[$sid]['classes'][$cid]);
                    } */
                    if( ($festival['flags']&0x08) == 0x08 && $section_live == 'no' && $section_virtual == 'no' ) {
                        unset($sections[$sid]['classes'][$cid]);
                    }
                }
                //
                // Section end dates and no virtual option or pricing
                //
                elseif( ($festival['flags']&0x08) == 0x08 ) {
                    if( $section_live == 'no' ) {
                        unset($sections[$sid]['classes'][$cid]);
                    }
                }
            }
        }
    }
    foreach($sections as $sid => $section) {
        if( count($section['classes']) == 0 ) {
            unset($sections[$sid]);
        }
    }
    if( isset($selected_sid) ) {
        $selected_section = $sections[$selected_sid];
        if( isset($selected_cid) ) {
            $selected_class = $sections[$selected_sid]['classes'][$selected_cid];
        }
    }

    //
    // Check for different class submitted in form
    //
    if( isset($_POST['f-section']) && $_POST['f-section'] > 0 ) {
        $selected_section = $sections[$_POST['f-section']];
        if( isset($_POST["f-section-{$_POST['f-section']}-class"]) ) {
            $selected_class = $sections[$_POST['f-section']]['classes'][$_POST["f-section-{$_POST['f-section']}-class"]];
            $comp_required = 1;
            $titles_required = 1;
/*            if( ($selected_class['flags']&0x10) == 0x10 ) {
                $comp_required = 2;
            }
            if( ($selected_class['flags']&0x20) == 0x20 ) {
                $comp_required = 3;
            }
            if( ($selected_class['flags']&0x1000) == 0x1000 ) {
                $titles_required = 2;
            }
            if( ($selected_class['flags']&0x2000) == 0x2000 ) {
                $titles_required = 3;
            } */
        }
    }
    elseif( isset($registration['class_id']) ) {
        foreach($sections as $sid => $section) {
            if( isset($section['classes'][$registration['class_id']]) ) {
                $selected_section = $section;
                $selected_class = $section['classes'][$registration['class_id']];
                break;
            }
        }
    }

    //
    // Select the first section and class if nothing selected
    //
    if( !isset($selected_section) ) {
        foreach($sections as $section) {
            $selected_section = $section;
            foreach($section['classes'] as $class) {
                $selected_class = $class;
                break;
            }
            break;
        }
    }


    //
    // Setup the fields for the form
    //
    $fields = array(
        'registration_id' => array(
            'id' => 'registration_id',
            'label' => '',
            'ftype' => 'hidden',
            'value' => (isset($_POST['f-registration_id']) ? $_POST['f-registration_id'] : (isset($registration['registration_id']) ? $registration['registration_id'] : 0)),
            ),
        'action' => array(
            'id' => 'action',
            'label' => '',
            'ftype' => 'hidden',
            'value' => 'update',
            ),
        'section' => array(
            'id' => 'section',
            'ftype' => 'select',
            'label' => 'Section',
            'blank' => 'no',
            'size' => 'small',
            'required' => 'yes',
            'flex-basis' => '10em',
            'onchange' => 'sectionSelected()',
            'options' => $sections,
            'value' => (isset($selected_section) ? $selected_section['id'] : (isset($registration['section']) ? $registration['section'] : '')),
            ),
        );

    //
    // Add the classes for each section
    //
    foreach($sections as $sid => $section) {
        if( isset($section['classes']) ) {
            $fields["section-{$sid}-classes"] = array(
                'id' => "section-{$sid}-class",
                'ftype' => 'select',
                'label' => 'Class',
                'required' => 'yes',
                'class' => isset($selected_section['id']) && $selected_section['id'] == $sid ? '' : 'hidden',
                'blank' => 'no',
                'size' => 'medium',
                'flex-basis' => '32em',
                'options' => $section['classes'],
                'option-id-field' => 'id',
                'option-value-field' => 'codename',
                'onchange' => "classSelected({$sid})",
                'value' => isset($selected_class['id']) ? $selected_class['id'] : '',
                );
        }
    }

    //
    // Add child information 
    //
    for($i = 1; $i <= 1; $i++) {
/*        $class = ($i > 1 ? 'hidden' : '');
        if( isset($selected_class) && $i == 2 && (($selected_class['flags']&0x10) == 0x10 || ($selected_class['flags']&0x20) == 0x20) ) {
            $class = '';
        }
        elseif( isset($selected_class) && $i == 3 && (($selected_class['flags']&0x20) == 0x20) ) {
            $class = '';
        } */
        $fields["competitor{$i}_id"] = array(
            'id' => "competitor{$i}_id",
            'ftype' => 'select',
            'size' => 'large',
            'class' => '',
            'required' => ($i < 3 ? 'yes' : 'no'),
            'label' => "Competitor",
            'onchange' => "competitorSelected({$i})",
            'options' => $competitors,
            'value' => isset($_POST["f-competitor{$i}_id"]) && $_POST["f-competitor{$i}_id"] > -1 ? $_POST["f-competitor{$i}_id"] : (isset($registration["competitor{$i}_id"]) ? $registration["competitor{$i}_id"] : 0),
            );
        $fields["competitor{$i}_id"]['options']['add'] = array(
            'id' => '-1',
            'name' => 'Add Competitor',
            );
    }

    //
    // Add teacher
    //
    $fields["title"] = array(
        'id' => "title",
        'ftype' => 'text',
        'flex-basis' => '28em',
        'class' => '',
        'required' => 'yes',
        'size' => 'medium',
        'label' => 'Title',
        'value' => isset($_POST["f-title"]) ? $_POST["f-title"] : (isset($registration["title"]) ? $registration["title"] : ''),
        );
    $fields["word_count"] = array(
        'id' => "word_count",
        'flex-basis' => '8em',
        'required' => 'yes',
        'class' => '',
        'ftype' => 'text',
        'size' => 'small',
        'label' => 'Word Count',
        'value' => isset($_POST["f-word_count"]) ? $_POST["f-word_count"] : (isset($registration["word_count"]) ? $registration["word_count"] : ''),
        );

    //
    // Check if virtual performance option is available
    //
    if( ($festival['flags']&0x02) == 0x02 ) {
        $fields['virtual'] = array(
            'id' => 'virtual',
            'label' => 'I would like to participate',
            'ftype' => 'select',
            'blank' => 'no',
            'required' => 'yes',
            'size' => 'large',
            'options' => array(
                '-1' => 'Please choose how you will participate',
                '0' => 'in person on a date to be scheduled',
                '1' => 'virtually and submit a video',
                ),
            'value' => isset($_POST['f-virtual']) ? $_POST['f-virtual'] : (isset($registration['virtual']) ? $registration['virtual'] : -1),
            );
        //
        // Setup pricing for virtual option with separate virtual pricing
        //
        if( ($festival['flags']&0x06) == 0x06 ) {
            if( isset($festival['live']) && $festival['live'] == 'yes' 
                && isset($selected_class['live_fee']) && $selected_class['live_fee'] > 0 
                ) {
                $fields['virtual']['options'][0] .= ' - $' . number_format($selected_class['live_fee'], 2);
            } else {
                unset($fields['virtual']['options'][-1]);
                unset($fields['virtual']['options'][0]);
            }
            if( isset($festival['virtual']) && $festival['virtual'] == 'yes' 
                && isset($selected_class['virtual_fee']) && $selected_class['virtual_fee'] > 0 
                ) {
                $fields['virtual']['options'][1] .= ' - $' . number_format($selected_class['virtual_fee'], 2);
            } else {
                if( isset($fields['virtual']['options'][-1]) ) {
                    unset($fields['virtual']['options'][-1]);
                }
                unset($fields['virtual']['options'][1]);
            }
        }
        // 
        // Check if both options still available
        //
        elseif( ($festival['flags']&0x06) == 0x02 ) {
            if( $festival['live'] == 'no' && $festival['earlybird'] == 'no' ) {
                unset($fields['virtual']['options'][-1]);
                unset($fields['virtual']['options'][0]);
            }
        }
    }

    //
    // Setup the Javascript for updating the form as fields change
    //
/*    $js_prices = '';
    $js_set_prices = '';
    if( ($festival['flags']&0x06) == 0x06 ) {
        $js_prices .=  "var clslp=" . json_encode($live_prices) . ";" // live prices
            . "var clsvp=" . json_encode($virtual_prices) . ";" // virtual prices
            . "";
        $js_set_prices .= ""
            . "var s=C.gE('f-virtual');"
            . "var v=s.value;"
            . "s.options.length=0;"
            . "s.appendChild(new Option('Please choose how you will participate',-1));"
            . "if(clslp[c]!=null){"
                . "s.appendChild(new Option('in person on a date to be scheduled - '+clslp[c], 0,0,(v==0?1:0)));"
            . "}"
            . "if(clsvp[c]!=null){"
                . "s.appendChild(new Option('virtually and submit a video - '+clsvp[c], 1,0,(v==1?1:0)));"
            . "}"
            . "";
    } */
    $js = ""
        . "var sids=[" . implode(',', array_keys($sections)) . "];"
//        . "var cls2c=[" . implode(',', $classes_2c) . "];" // 2 competitor classes (duets)
//        . "var cls3c=[" . implode(',', $classes_3c) . "];" // 3 competitor classes (trios)
//        . "var cls2t=[" . implode(',', $classes_2t) . "];" // 2 title & times
//        . "var cls3t=[" . implode(',', $classes_3t) . "];" // 3 title & times
//        . $js_prices
        . "function sectionSelected(){"
            . "var s=C.gE('f-section').value;"
            . "for(var i in sids){"
                . "var e=C.gE('f-section-'+sids[i]+'-class');"
                . "if(s==sids[i]){"
                    . "C.rC(e.parentNode,'hidden');"
//                    . "classSelected(sids[i]);"
                . "}else{"
                    . "C.aC(e.parentNode,'hidden');"
                . "}"
            . "}"
        . "};"
//        . "function classSelected(sid){"
//            . "var c=C.gE('f-section-'+sid+'-class').value;"
//            . "if(c!=null){"
//                . $js_set_prices
//            . "}"
//        . "};"
        . "function competitorSelected(c) {"
            . "var t=C.gE('f-competitor'+c+'_id').value;"
            . "if(t==-1){"
                . "C.gE('f-action').value='addcompetitor';"
                . "var f=C.gE('addregform');"
                . "f.action='{$request['ssl_domain_base_url']}/account/writingfestivalcompetitors?add=yes';"
                . "f.submit();"
            . "}"
        . "};"
        . "function formCancel(){"
            . "var f=C.gE('addregform');"
            . "C.gE('f-action').value='cancel';"
            . "f.submit();"
        . "};"
        . "function formSubmit(){"
            . "var f=C.gE('addregform');"
            . "C.gE('f-action').value='update';"
            . "f.submit();"
        . "};"
//        . "function teacherSelected(){"
//            . "var t=C.gE('f-teacher_customer_id').value;"
//            . "if(t==-1){"
//                . "C.rC(C.gE('f-teacher_name').parentNode,'hidden');"
//                . "C.rC(C.gE('f-teacher_phone').parentNode,'hidden');"
//                . "C.rC(C.gE('f-teacher_email').parentNode,'hidden');"
//            . "}else{"
//                . "C.aC(C.gE('f-teacher_name').parentNode,'hidden');"
//                . "C.aC(C.gE('f-teacher_phone').parentNode,'hidden');"
//                . "C.aC(C.gE('f-teacher_email').parentNode,'hidden');"
//            . "}"
//        . "}; ";
        . "";

    $rsp = array('stat'=>'ok', 'fields'=>$fields, 'js'=>$js, 'sections'=>$sections);
    if( isset($selected_section) ) {
        $rsp['selected_section'] = $selected_section;
    }
    if( isset($selected_class) ) {
        $rsp['selected_class'] = $selected_class;
    }
    return $rsp;
}
?>
