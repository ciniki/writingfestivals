<?php
//
// Description
// -----------
// This function will return the blocks for the website.
//
// Arguments
// ---------
// ciniki:
// tnid:            The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_writingfestivals_wng_process(&$ciniki, $tnid, &$request, $section) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.writingfestivals']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.206', 'msg'=>"I'm sorry, the section you requested does not exist."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.writingfestivals.207', 'msg'=>"No section specified."));
    }

    if( $section['ref'] == 'ciniki.writingfestivals.syllabus' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'syllabusProcess');
        return ciniki_writingfestivals_wng_syllabusProcess($ciniki, $tnid, $request, $section);
//    } elseif( $section['ref'] == 'ciniki.writingfestivals.registrations' ) {
//        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'registrationsProcess');
//        return ciniki_writingfestivals_wng_registrationsProcess($ciniki, $tnid, $request, $section);
//    } elseif( $section['ref'] == 'ciniki.writingfestivals.files' ) {
//        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'filesProcess');
//        return ciniki_writingfestivals_wng_filesProcess($ciniki, $tnid, $request, $section);
    } elseif( $section['ref'] == 'ciniki.writingfestivals.adjudicators' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'adjudicatorsProcess');
        return ciniki_writingfestivals_wng_adjudicatorsProcess($ciniki, $tnid, $request, $section);
//    } elseif( $section['ref'] == 'ciniki.writingfestivals.timeslotphotos' ) {
//        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'timeslotPhotosProcess');
//        return ciniki_writingfestivals_wng_timeslotPhotosProcess($ciniki, $tnid, $request, $section);
//    } elseif( $section['ref'] == 'ciniki.writingfestivals.categorylists' ) {
//        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'categoryListsProcess');
//        return ciniki_writingfestivals_wng_categoryListsProcess($ciniki, $tnid, $request, $section);
    } elseif( $section['ref'] == 'ciniki.writingfestivals.sponsors' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'sponsorsProcess');
        return ciniki_writingfestivals_wng_sponsorsProcess($ciniki, $tnid, $request, $section);
    } elseif( $section['ref'] == 'ciniki.writingfestivals.winners' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'winnersProcess');
        return ciniki_writingfestivals_wng_winnersProcess($ciniki, $tnid, $request, $section);
//    } elseif( $section['ref'] == 'ciniki.writingfestivals.trophies' ) {
//        ciniki_core_loadMethod($ciniki, 'ciniki', 'writingfestivals', 'wng', 'trophiesProcess');
//        return ciniki_writingfestivals_wng_trophiesProcess($ciniki, $tnid, $request, $section);
    }

    return array('stat'=>'ok');
}
?>
