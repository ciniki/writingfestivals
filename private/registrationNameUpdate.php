<?php
//
// Description
// -----------
// This function will update the registration display_name based on the rtype and competitor names.
//
// Arguments
// ---------
// ciniki:
// tnid:                 The tenant ID to check the session user against.
// method:                      The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_writingfestivals_registrationNameUpdate(&$ciniki, $tnid, $registration_id) {
    
    $strsql = "SELECT registrations.id, "
        . "registrations.festival_id, "
        . "registrations.teacher_customer_id, "
        . "registrations.billing_customer_id, "
        . "registrations.rtype, "
        . "registrations.status, "
        . "registrations.display_name, "
        . "registrations.public_name, "
        . "registrations.competitor1_id, "
        . "registrations.competitor2_id, "
        . "registrations.competitor3_id, "
        . "registrations.competitor4_id, "
        . "registrations.competitor5_id, "
        . "registrations.class_id, "
        . "registrations.title, "
        . "registrations.word_count, "
        . "FORMAT(registrations.fee, 2) AS fee, "
        . "registrations.payment_type, "
        . "registrations.notes, "
        . "competitors.id AS competitor_id, "
        . "competitors.name AS competitor_name, "
        . "competitors.public_name AS competitor_public_name "
        . "FROM ciniki_writingfestival_registrations AS registrations "
        . "LEFT JOIN ciniki_writingfestival_competitors AS competitors ON ( "
            . "(registrations.competitor1_id = competitors.id "
                . "OR registrations.competitor2_id = competitors.id "
                . "OR registrations.competitor3_id = competitors.id "
                . "OR registrations.competitor4_id = competitors.id "
                . "OR registrations.competitor5_id = competitors.id "
                . ") "
            . ") "
        . "WHERE registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND registrations.id = '" . ciniki_core_dbQuote($ciniki, $registration_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.writingfestivals', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('rtype', 'display_name', 'public_name', 'competitor1_id', 'competitor2_id', 'competitor3_id', 'competitor4_id', 'competitor5_id')),
        array('container'=>'competitors', 'fname'=>'competitor_id', 
            'fields'=>array('competitor_id', 'name'=>'competitor_name', 'public_name'=>'competitor_public_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.115', 'msg'=>'Registration not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['registrations']) || count($rc['registrations']) <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.116', 'msg'=>'Unable to find Registration'));
    }
    $registration = array_pop($rc['registrations']);

    $display_name = '';
    $public_name = '';

    //
    // Only update display name for non-ensembles
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    if( $registration['rtype'] < 90 ) {
        $names = array();
        $pnames = array();
        if( $registration['competitor1_id'] > 0 && isset($registration['competitors'][$registration['competitor1_id']]['name']) ) {
            $names[] = $registration['competitors'][$registration['competitor1_id']]['name'];
            if( $registration['competitors'][$registration['competitor1_id']]['public_name'] != '' ) {
                $pnames[] = $registration['competitors'][$registration['competitor1_id']]['public_name']; 
            } else {
                $pnames[] = preg_replace("/^(.).*\s([^\s]+)$/", '$1. $2', $registration['competitors'][$registration['competitor1_id']]['name']); 
            }
        } 
//        if( $registration['rtype'] > 30 ) {
            if( $registration['competitor2_id'] > 0 && isset($registration['competitors'][$registration['competitor2_id']]['name']) ) {
                $names[] = $registration['competitors'][$registration['competitor2_id']]['name'];
                if( $registration['competitors'][$registration['competitor2_id']]['public_name'] != '' ) {
                    $pnames[] = $registration['competitors'][$registration['competitor2_id']]['public_name']; 
                } else {
                    $pnames[] = preg_replace("/^(.).*\s([^\s]+)$/", '$1. $2', $registration['competitors'][$registration['competitor2_id']]['name']); 
                }
            } 
//        }
//        if( $registration['rtype'] > 50 ) {
            if( $registration['competitor3_id'] > 0 && isset($registration['competitors'][$registration['competitor3_id']]['name']) ) {
                $names[] = $registration['competitors'][$registration['competitor3_id']]['name'];
                if( $registration['competitors'][$registration['competitor3_id']]['public_name'] != '' ) {
                    $pnames[] = $registration['competitors'][$registration['competitor3_id']]['public_name']; 
                } else {
                    $pnames[] = preg_replace("/^(.).*\s([^\s]+)$/", '$1. $2', $registration['competitors'][$registration['competitor3_id']]['name']); 
                }
            } 
//        }
        if( count($names) == 3 ) {
            $display_name = $names[0] . ', ' . $names[1] . ' & ' . $names[2];
            $public_name = $pnames[0] . ', ' . $pnames[1] . ' & ' . $pnames[2];
        } elseif( count($names) == 2 ) {
            $display_name = $names[0] . ' & ' . $names[1];
            $public_name = $pnames[0] . ' & ' . $pnames[1];
        } elseif( count($names) == 1 ) {
            $display_name = $names[0];
            $public_name = $pnames[0];
        }
        $update_args = array();
        if( isset($display_name) && $display_name != $registration['display_name'] ) {
            $update_args['display_name'] = $display_name;
        }
        if( isset($public_name) && $public_name != $registration['public_name'] ) {
            $update_args['public_name'] = $public_name;
        }
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.writingfestivals.registration', $registration_id, $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.117', 'msg'=>'Unable to update name', 'err'=>$rc['err']));
            }
        }
    } elseif( $registration['rtype'] == 90 ) {  
        $update_args = array();
        if( $registration['competitor1_id'] > 0 
            && isset($registration['competitors'][$registration['competitor1_id']]['name']) 
            ) {
            if( $registration['display_name'] != $registration['competitors'][$registration['competitor1_id']]['name'] ) {
                $update_args['display_name'] = $registration['competitors'][$registration['competitor1_id']]['name'];
            }
            if( $registration['public_name'] != $registration['competitors'][$registration['competitor1_id']]['name'] ) {
                $update_args['public_name'] = $registration['competitors'][$registration['competitor1_id']]['name'];
            }
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.writingfestivals.registration', $registration_id, $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.152', 'msg'=>'Unable to update name', 'err'=>$rc['err']));
            }
        }
    }

    return array('stat'=>'ok', 'display_name'=>$display_name, 'public_name'=>$public_name);
}
?>
