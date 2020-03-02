<?php
//
// Description
// -----------
// This function will process a web request for the Writing Festival module.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get writing festival request for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_writingfestivals_web_processRequest(&$ciniki, $settings, $tnid, $args) {

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.writingfestivals']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.11', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }
    $page = array(
        'title'=>$args['page_title'],
        'breadcrumbs'=>$args['breadcrumbs'],
        'blocks'=>array(),
        'submenu'=>array(),
        );
    $uri_split = $args['uri_split'];

    //
    // Check for writing festival permalink, for archived festivals
    //
    $festival_id = 0;
/*    $festival = array(
        'flags' => 0,
        'settings' => array(
            'age-restriction-msg' => '',
            ),
        ); */
    $dt = new DateTime('now', new DateTimezone($intl_timezone));
    if( isset($uri_split[0]) && $uri_split[0] != '' ) {
        //
        // Check if a writingfestival
        //
        $strsql = "SELECT id, name, flags, "
            . "IFNULL(DATEDIFF(earlybird_date, '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "'), -1) AS earlybird "
            . "FROM ciniki_writingfestivals "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND status = 30 "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $uri_split[0]) . "' "
            . "ORDER BY start_date DESC "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'festival');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['festival']) ) {
            $festival_id = $rc['festival']['id'];
            $festival = $rc['festival'];
            $festival['settings'] = array(
                'age-restriction-msg' => '',
                );
            $uri_split = shift($uri_split);
            $page['breadcrumbs'][] = array('name'=>$rc['festival']['name'], 'url'=>$args['base_url'] . '/' . $uri_split[0]);
        }
    }

    //
    // No festival specified on the url, load the specified one in the settings, or find the more recent.
    //
    if( $festival_id == 0 ) {
        //
        // Load the festival name
        //
        $strsql = "SELECT id, name, flags, "
            . "IFNULL(DATEDIFF(earlybird_date, '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "'), -1) AS earlybird "
            . "FROM ciniki_writingfestivals "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND status = 30 "
            . "ORDER BY start_date DESC "
            . "LIMIT 1 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'festival');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['festival']) ) {
            $festival_id = $rc['festival']['id'];
            $festival = $rc['festival'];

            $page['breadcrumbs'][] = array('name'=>$rc['festival']['name'], 'url'=>$args['base_url']);
        }
    }

    //
    // Check if no festival found
    //
    if( $festival_id == 0 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.39', 'msg'=>'We could not find the requested Writing Festival. Please try again or contact us for more information.'));
    }

    //
    // Load the settings for the festival
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_writingfestival_settings "
        . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.writingfestivals', 'settings');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.143', 'msg'=>'We could not find the requested Writing Festival. Please try again or contact us for more information.'));
    }
    foreach($rc['settings'] as $k => $v) {
        $festival['settings'][$k] = $v;
    }

    //
    // Get the sponsors for the festival
    //
    if( isset($ciniki['tenant']['modules']['ciniki.sponsors']) 
        && ($ciniki['tenant']['modules']['ciniki.sponsors']['flags']&0x02) == 0x02
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'web', 'sponsorRefList');
        $rc = ciniki_sponsors_web_sponsorRefList($ciniki, $settings, $tnid, 
            'ciniki.writingfestivals.festival', $festival_id);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['sponsors']) ) {
            $sponsors = $rc['sponsors'];
        }
    }

    //
    // Check if file to download
    //
    if( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'download' && isset($args['uri_split'][1]) && $args['uri_split'][1] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'web', 'fileDownload');
        $rc = ciniki_writingfestivals_web_fileDownload($ciniki, $ciniki['request']['tnid'], $festival_id, $ciniki['request']['uri_split'][1]);
        if( $rc['stat'] == 'ok' ) {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            $file = $rc['file'];
            if( $file['extension'] == 'pdf' ) {
                header('Content-Type: application/pdf');
            }
//          header('Content-Disposition: attachment;filename="' . $file['filename'] . '"');
            header('Content-Length: ' . strlen($file['binary_content']));
            header('Cache-Control: max-age=0');

            print $file['binary_content'];
            exit;
        }
        
        //
        // If there was an error locating the files, display generic error
        //
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.63', 'msg'=>'The file you requested does not exist.'));
    }

    //
    // Decide what should be displayed, default to about page
    //
    $display = 'about';
    if( isset($uri_split[0]) ) {
        if( $uri_split[0] == 'about' ) {
            $display = 'about';
        } elseif( $uri_split[0] == 'adjudicators' ) {
            $display = 'adjudicators';
            $adjudicator_permalink = $uri_split[0];
        } elseif( $uri_split[0] == 'registrations' ) {
            $display = 'registrations';
            array_shift($uri_split); 
        } else {
            $strsql = "SELECT id, name, permalink, primary_image_id AS image_id, synopsis, description "
                . "FROM ciniki_writingfestival_sections "
                . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
                . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $uri_split[0]) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'section');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['section']) ) {
                $section = $rc['section'];
                $display = 'section';
            } else {
                return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.48', 'msg'=>'We could not find the request page.'));
            }
        }
    }

    //
    // Load the details for the festival, and display the main page.
    //
    if( $display == 'about' ) {
        $strsql = "SELECT id, name, start_date, end_date, status, flags, primary_image_id, description "
            . "FROM ciniki_writingfestivals "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND status = 30 "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
            . "ORDER BY start_date DESC "
            . "LIMIT 1 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'festival');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['festival']) ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.writingfestivals.49', 'msg'=>'We could not find the request page.'));
        }
        $festival = $rc['festival'];
       
        if( isset($festival['primary_image_id']) && $festival['primary_image_id'] > 0 ) {
            $page['blocks'][] = array('type'=>'asideimage', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$festival['primary_image_id']);
        }

        $content = $festival['description'];
        $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>$content);

        //
        // Get any files
        //
        $strsql = "SELECT id, name, permalink, extension, description "
            . "FROM ciniki_writingfestival_files "
            . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (ciniki_writingfestival_files.webflags&0x01) > 0 "       // Make sure file is to be visible
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
            array('container'=>'files', 'fname'=>'id', 'fields'=>array('id', 'name', 'permalink', 'extension', 'description')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['files']) ) {
            $page['blocks'][] = array('type'=>'files', 'base_url'=>$args['base_url'] . '/download', 'files'=>$rc['files']);
        }
    }

    //
    // Process the registrations page
    //
    elseif( $display == 'registrations' ) {
        $page['breadcrumbs'][] = array('name'=>'Registrations', 'url'=>$args['base_url'] . '/registrations');

        $args['uri_split'] = $uri_split;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'web', 'processRequestRegistrations');
        $rc = ciniki_writingfestivals_web_processRequestRegistrations($ciniki, $settings, $tnid, array(
            'uri_split' => $uri_split,
            'festival_id' => $festival_id,
            'festival_flags' => $festival['flags'],
            'earlybird' => $festival['earlybird'],
            'settings' => $festival['settings'],
            'base_url' => $args['base_url'] . '/registrations',
            'ssl_domain_base_url' => $args['ssl_domain_base_url'] . '/registrations',
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['blocks']) ) {
            foreach($rc['blocks'] as $block) {
                $page['blocks'][] = $block;
            }
        }
    }

    //
    // Display the section information
    //
    elseif( $display == 'section' ) {
        $page['breadcrumbs'][] = array('name'=>$section['name'], 'url'=>$args['base_url'] . '/' . $section['permalink']);
        //
        // Display the section information
        //
        $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>$section['name'], 
            'aside_image_id'=>(isset($section['image_id']) && $section['image_id'] > 0  ? $section['image_id'] : 0),
            'wide'=>(isset($section['image_id']) && $section['image_id'] > 0  ? 'no' : 'yes'),
            'content'=>($section['description'] != '' ? $section['description'] : $section['synopsis'])
            );

        //
        // Get the categories and classes
        //
        $strsql = "SELECT ciniki_writingfestival_classes.id, "
            . "ciniki_writingfestival_classes.uuid, "
            . "ciniki_writingfestival_classes.festival_id, "
            . "ciniki_writingfestival_classes.category_id, "
            . "ciniki_writingfestival_categories.id AS category_id, "
            . "ciniki_writingfestival_categories.name AS category_name, "
            . "ciniki_writingfestival_categories.primary_image_id AS category_image_id, "
            . "ciniki_writingfestival_categories.synopsis AS category_synopsis, "
            . "ciniki_writingfestival_categories.description AS category_description, "
            . "ciniki_writingfestival_classes.code, "
            . "ciniki_writingfestival_classes.name, "
            . "ciniki_writingfestival_classes.permalink, "
            . "ciniki_writingfestival_classes.sequence, "
            . "ciniki_writingfestival_classes.flags, ";
        if( $festival['earlybird'] >= 0 ) {
            $strsql .= "CONCAT('$', FORMAT(ciniki_writingfestival_classes.earlybird_fee, 2)) AS fee ";
        } else {
            $strsql .= "CONCAT('$', FORMAT(ciniki_writingfestival_classes.fee, 2)) AS fee ";
        }
        $strsql .= "FROM ciniki_writingfestival_categories, ciniki_writingfestival_classes "
            . "WHERE ciniki_writingfestival_categories.section_id = '" . ciniki_core_dbQuote($ciniki, $section['id']) . "' "
            . "AND ciniki_writingfestival_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_writingfestival_categories.id = ciniki_writingfestival_classes.category_id "
            . "AND ciniki_writingfestival_classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY ciniki_writingfestival_categories.sequence, ciniki_writingfestival_categories.name, "
                . "ciniki_writingfestival_classes.sequence, ciniki_writingfestival_classes.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
            array('container'=>'categories', 'fname'=>'category_id', 
                'fields'=>array('name'=>'category_name', 'image_id'=>'category_image_id', 'synopsis'=>'category_synopsis', 'description'=>'category_description')),
            array('container'=>'classes', 'fname'=>'id', 
                'fields'=>array('id', 'uuid', 'festival_id', 'category_id', 'code', 'name', 'permalink', 'sequence', 'flags', 'fee')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['categories']) ) {
            $categories = $rc['categories'];
            foreach($categories as $category) {
                $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>$category['name'], 
                    'aside_image_id'=>(isset($category['image_id']) && $category['image_id'] > 0  ? $category['image_id'] : 0),
                    'wide'=>(isset($category['image_id']) && $category['image_id'] > 0  ? 'no' : 'yes'),
                    'content'=>($category['description'] != '' ? $category['description'] : $category['synopsis'])
                    );
                if( isset($category['classes']) && count($category['classes']) > 0 ) {
                    //
                    // FIXME: Check if online registrations enabled, and online registrations enabled for this class
                    //
                    if( ($festival['flags']&0x01) == 0x01 ) {
                        foreach($category['classes'] as $cid => $class) {
                            $category['classes'][$cid]['register'] = "<a href='" . $args['base_url'] . "/registrations?r=new&cl=" . $class['uuid'] . "'>Register</a>";
                        }
                        $page['blocks'][] = array('type'=>'table', 'section'=>'classes', 
                            'columns'=>array(
                                array('label'=>'Code', 'field'=>'code', 'class'=>''),
                                array('label'=>'Course', 'field'=>'name', 'class'=>''),
                                array('label'=>'Fee', 'field'=>'fee', 'class'=>'aligncenter'),
                                array('label'=>'', 'field'=>'register', 'class'=>'alignright'),
                                ),
                            'rows'=>$category['classes'],
                            );
                    } else {
                        $page['blocks'][] = array('type'=>'table', 'section'=>'classes', 
                            'columns'=>array(
                                array('label'=>'', 'field'=>'code', 'class'=>''),
                                array('label'=>'', 'field'=>'name', 'class'=>''),
                                array('label'=>'Fee', 'field'=>'fee', 'class'=>'aligncenter'),
                                ),
                            'rows'=>$category['classes'],
                            );
                    }
                }
            }
        }
    }

    //
    // Display the adjudicators
    //
    elseif( $display == 'adjudicators' ) {
        $page['breadcrumbs'][] = array('name'=>'Adjudicators', 'url'=>$args['base_url'] . '/adjudicators');
        $strsql = "SELECT ciniki_writingfestival_adjudicators.id, "
            . "ciniki_writingfestival_adjudicators.customer_id, "
            . "ciniki_customers.sort_name "
            . "FROM ciniki_writingfestival_adjudicators, ciniki_customers "
            . "WHERE ciniki_writingfestival_adjudicators.festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
            . "AND ciniki_writingfestival_adjudicators.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_writingfestival_adjudicators.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY ciniki_customers.sort_name "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'a');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'customerPublicDetails');
            foreach($rc['rows'] as $row) {
                $rc = ciniki_customers_web_customerPublicDetails($ciniki, $settings, $tnid, array('customer_id'=>$row['customer_id']));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $customer = $rc['customer'];
                $page['blocks'][] = array('type'=>'content', 'section'=>'section', 'title'=>$customer['display_name'], 
                    'aside_image_id'=>(isset($customer['image_id']) && $customer['image_id'] > 0  ? $customer['image_id'] : 0),
                    'html'=>$customer['processed_description']);
//                if( isset($customer['image_id']) && $customer['image_id'] > 0 ) {
//                    $page['blocks'][] = array('type'=>'asideimage', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$customer['image_id']);
//                }
//                $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>$customer['display_name'], 'html'=>$customer['processed_description']);
            } 
        } else {
            $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>"We don't currently have any adjudicators.");
        } 
    }

    if( isset($sponsors) && count($sponsors) > 0 ) {
        $page['blocks'][] = array('type'=>'sponsors', 'section'=>'sponsors', 'title'=>'', 'sponsors'=>$sponsors);
    }

    //
    // Add the submenu
    //
    $page['submenu'] = array();
    $page['submenu']['about'] = array('name'=>'About', 'url'=>$args['base_url'] . '/about');

    //
    // Get the sections
    //
    $strsql = "SELECT name, permalink "
        . "FROM ciniki_writingfestival_sections "
        . "WHERE festival_id = '" . ciniki_core_dbQuote($ciniki, $festival_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY sequence "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.writingfestivals', 'section');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        foreach($rc['rows'] as $row) {
            $page['submenu'][$row['permalink']] = array('name'=>$row['name'], 'url'=>$args['base_url'] . '/' . $row['permalink']);
        }
    }
    $page['submenu']['adjudicators'] = array('name'=>'Adjudicators', 'url'=>$args['base_url'] . '/adjudicators');
    if( isset($ciniki['session']['customer']['id']) ) {
        $page['submenu']['registrations'] = array('name'=>'Registrations', 'url'=>$args['base_url'] . '/registrations');
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
