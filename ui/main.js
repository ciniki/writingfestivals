//
// This is the main app for the writingfestivals module
//
function ciniki_writingfestivals_main() {
    //
    // The panel to list the festival
    //
    this.menu = new M.panel('festival', 'ciniki_writingfestivals_main', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.writingfestivals.main.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
//        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
//            'cellClasses':[''],
//            'hint':'Search festival',
//            'noData':'No festival found',
//            },
        'festivals':{'label':'Festival', 'type':'simplegrid', 'num_cols':2,
            'headerValues':['Festival', 'Status'],
            'noData':'No festival',
            'menu':{
                'add':{
                    'label':'Add Festival',
                    'fn':'M.ciniki_writingfestivals_main.edit.open(\'M.ciniki_writingfestivals_main.menu.open();\',0,null);'
                    },
                },
            },
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.writingfestivals.festivalSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_writingfestivals_main.menu.liveSearchShow('search',null,M.gE(M.ciniki_writingfestivals_main.menu.panelUID + '_' + s), rsp.festivals);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_writingfestivals_main.festival.open(\'M.ciniki_writingfestivals_main.menu.open();\',\'' + d.id + '\');';
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'festivals' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.status_text;
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'festivals' ) {
            return 'M.ciniki_writingfestivals_main.festival.open(\'M.ciniki_writingfestivals_main.menu.open();\',\'' + d.id + '\',M.ciniki_writingfestivals_main.festival.nplist);';
        }
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('ciniki.writingfestivals.festivalList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_writingfestivals_main.menu;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

    //
    // The panel to display Festival
    //
    this.festival = new M.panel('Festival', 'ciniki_writingfestivals_main', 'festival', 'mc', 'large narrowaside', 'sectioned', 'ciniki.writingfestivals.main.festival');
    this.festival.data = null;
    this.festival.festival_id = 0;
    this.festival.schedulesection_id = 0;
    this.festival.scheduledivision_id = 0;
    this.festival.nplists = {};
    this.festival.nplist = [];
    this.festival.sections = {
        '_tabs':{'label':'', 'type':'menutabs', 'selected':'sections', 'tabs':{
            'sections':{'label':'Syllabus', 'fn':'M.ciniki_writingfestivals_main.festival.switchTab(\'sections\');'},
            'registrations':{'label':'Registrations', 'fn':'M.ciniki_writingfestivals_main.festival.switchTab(\'registrations\');'},
            'schedule':{'label':'Schedule', 'fn':'M.ciniki_writingfestivals_main.festival.switchTab(\'schedule\');'},
            'comments':{'label':'Comments', 'fn':'M.ciniki_writingfestivals_main.festival.switchTab(\'comments\');',
//                'visible':function() { return (M.ciniki_writingfestivals_main.festival.data.flags&0x02) == 0x02 ? 'yes' : 'no'},
                },
            'competitors':{'label':'Competitors', 'fn':'M.ciniki_writingfestivals_main.festival.switchTab(\'competitors\');'},
            'adjudicators':{'label':'Adjudicators', 'fn':'M.ciniki_writingfestivals_main.festival.switchTab(\'adjudicators\');'},
            'files':{'label':'Files', 'fn':'M.ciniki_writingfestivals_main.festival.switchTab(\'files\');'},
            'sponsors':{'label':'Sponsors', 'fn':'M.ciniki_writingfestivals_main.festival.switchTab(\'sponsors\');',
                'visible':function() { return M.modFlagSet('ciniki.writingfestivals', 0x10); },
                },
            'winners':{'label':'Winners', 
                'visible':function() { return M.modFlagSet('ciniki.writingfestivals', 0x80); },
                'fn':'M.ciniki_writingfestivals_main.festival.switchTab(\'winners\');',
                },
            }},
        'details':{'label':'Details', 'aside':'yes', 'list':{
            'name':{'label':'Name'},
            'start_date':{'label':'Start'},
            'end_date':{'label':'End'},
            'num_registrations':{'label':'# Reg'},
            }},
        '_stabs':{'label':'', 'type':'paneltabs', 'selected':'sections', 
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'sections' ? 'yes' : 'no'; },
            'tabs':{
                'sections':{'label':'Sections', 'fn':'M.ciniki_writingfestivals_main.festival.switchTab(null,\'sections\');'},
                'categories':{'label':'Categories', 'fn':'M.ciniki_writingfestivals_main.festival.switchTab(null,\'categories\');'},
                'classes':{'label':'Classes', 'fn':'M.ciniki_writingfestivals_main.festival.switchTab(null,\'classes\');'},
            }},
        'program_buttons':{'label':'', 'aside':'yes',
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'schedule' ? 'yes' : 'no'; },
            'buttons':{
                'pdf':{'label':'Download Program PDF', 'fn':'M.ciniki_writingfestivals_main.festival.downloadProgramPDF();'},
            }},
        'sections':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'sections' && M.ciniki_writingfestivals_main.festival.sections._stabs.selected == 'sections' ? 'yes' : 'no'; },
            'sortable':'yes',
            'sortTypes':['text', 'number'],
            'headerValues':['Section', 'Registrations'],
            'addTxt':'Add Section',
            'addFn':'M.ciniki_writingfestivals_main.section.open(\'M.ciniki_writingfestivals_main.festival.open();\',0,M.ciniki_writingfestivals_main.festival.festival_id,null);',
            },
        'si_buttons':{'label':'', 
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'sections' && M.ciniki_writingfestivals_main.festival.sections._stabs.selected == 'sections' && M.ciniki_writingfestivals_main.festival.data.sections.length == 0 ? 'yes' : 'no'; },
            'buttons':{
                'copy':{'label':'Copy previous syllabus', 'fn':'M.ciniki_writingfestivals_main.festival.syllabusCopy("previous");'},
            }},
        'categories':{'label':'', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'sections' && M.ciniki_writingfestivals_main.festival.sections._stabs.selected == 'categories' ? 'yes' : 'no'; },
            'sortable':'yes',
            'sortTypes':['text', 'text', 'number'],
            'headerValues':['Section', 'Category', 'Registrations'],
            'addTxt':'Add Category',
            'addFn':'M.ciniki_writingfestivals_main.category.open(\'M.ciniki_writingfestivals_main.festival.open();\',0,0,M.ciniki_writingfestivals_main.festival.festival_id,null);',
            },
        'classes':{'label':'', 'type':'simplegrid', 'num_cols':5,
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'sections' && M.ciniki_writingfestivals_main.festival.sections._stabs.selected == 'classes' ? 'yes' : 'no'; },
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text', 'number', 'number'],
            'headerValues':['Section', 'Category', 'Class', 'Fee', 'Registrations'],
            'addTxt':'Add Class',
            'addFn':'M.ciniki_writingfestivals_main.class.open(\'M.ciniki_writingfestivals_main.festival.open();\',0,0,M.ciniki_writingfestivals_main.festival.festival_id,null);',
            },
        'registration_tabs':{'label':'', 'aside':'yes', 'type':'paneltabs', 'selected':'sections',
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'registrations' ? 'yes' : 'no'; },
            'tabs':{
                'sections':{'label':'Sections', 'fn':'M.ciniki_writingfestivals_main.festival.switchRegTab("sections");'},
                'teachers':{'label':'Teachers', 'fn':'M.ciniki_writingfestivals_main.festival.switchRegTab("teachers");'},
            }}, 
        'registration_sections':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'registrations' && M.ciniki_writingfestivals_main.festival.sections.registration_tabs.selected == 'sections' ? 'yes' : 'no'; },
            },
        'registration_teachers':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'registrations' && M.ciniki_writingfestivals_main.festival.sections.registration_tabs.selected == 'teachers' ? 'yes' : 'no'; },
            },
        'registration_buttons':{'label':'', 'aside':'yes', 
            'visible':function() {return M.ciniki_writingfestivals_main.festival.sections._tabs.selected=='registrations'?'yes':'no';},
            'buttons':{
                'excel':{'label':'Export to Excel', 'fn':'M.ciniki_writingfestivals_main.festival.downloadExcel(M.ciniki_writingfestivals_main.festival.festival_id);'},
                'pdf':{'label':'Registrations PDF ', 'fn':'M.ciniki_writingfestivals_main.festival.downloadPDF(M.ciniki_writingfestivals_main.festival.festival_id);'},
            }},
        'registration_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':5,
            'visible':function() {return M.ciniki_writingfestivals_main.festival.sections._tabs.selected=='registrations'?'yes':'no';},
            'hint':'Search',
            'noData':'No registrations found',
            'headerValues':['Class', 'Registrant', 'pdf_filename', 'Fee', 'Status'],
            'cellClasses':['', '', '', '', ''],
            },
        'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':5,
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'registrations' ? 'yes' : 'no'; },
            'headerValues':['Class', 'Registrant', 'PDF', 'Fee', 'Status', ''],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text', 'altnumber', 'altnumber', ''],
            'cellClasses':['', '', '', '', '', 'alignright'],
//            'addTxt':'Add Registration',
//            'addFn':'M.ciniki_writingfestivals_main.registration.open(\'M.ciniki_writingfestivals_main.festival.open();\',0,0,0,M.ciniki_writingfestivals_main.festival.festival_id,null);',
            },
        'registrations_emailbutton':{'label':'', 
            'visible':function() {return M.ciniki_writingfestivals_main.festival.sections._tabs.selected=='registrations' && M.ciniki_writingfestivals_main.festival.teacher_customer_id > 0 ?'yes':'no';},
            'buttons':{
                'email':{'label':'Email List', 'fn':'M.ciniki_writingfestivals_main.festival.emailTeacherRegistrations();'},
            }},
        'schedule_sections':{'label':'Schedules', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'visible':function() { return ['schedule', 'comments'].indexOf(M.ciniki_writingfestivals_main.festival.sections._tabs.selected) >= 0 ? 'yes' : 'no'; },
            'cellClasses':['', 'multiline alignright'],
            'addTxt':'Add Schedule',
            'addFn':'M.ciniki_writingfestivals_main.schedulesection.open(\'M.ciniki_writingfestivals_main.festival.open();\',0,M.ciniki_writingfestivals_main.festival.festival_id,null);',
            'editFn':function(s, i, d) {
                return 'M.ciniki_writingfestivals_main.schedulesection.open(\'M.ciniki_writingfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_writingfestivals_main.festival.festival_id,null);';
                },
            },
        'schedule_divisions':{'label':'Divisions', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'visible':function() { return ['schedule', 'comments'].indexOf(M.ciniki_writingfestivals_main.festival.sections._tabs.selected) >= 0 ? 'yes' : 'no'; },
            'cellClasses':['multiline', 'multiline alignright'],
            'addTxt':'Add Division',
            'addFn':'M.ciniki_writingfestivals_main.scheduledivision.open(\'M.ciniki_writingfestivals_main.festival.open();\',0,M.ciniki_writingfestivals_main.festival.schedulesection_id,M.ciniki_writingfestivals_main.festival.festival_id,null);',
            'editFn':function(s, i, d) {
                return 'M.ciniki_writingfestivals_main.scheduledivision.open(\'M.ciniki_writingfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_writingfestivals_main.festival.schedulesection_id,M.ciniki_writingfestivals_main.festival.festival_id,null);';
                },
            },
        'schedule_download':{'label':'Schedule PDF', 'aside':'yes',
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'schedule' && M.ciniki_writingfestivals_main.festival.schedulesection_id>0? 'yes' : 'no'; },
            'fields':{
                'names':{'label':'Full Names', 'type':'toggle', 'default':'public', 'toggles':{'public':'No', 'private':'yes'}},
            }},
        'schedule_buttons':{'label':'', 'aside':'yes',
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'schedule' && M.ciniki_writingfestivals_main.festival.schedulesection_id>0? 'yes' : 'no'; },
            'buttons':{
                'pdf':{'label':'Download PDF', 'fn':'M.ciniki_writingfestivals_main.festival.downloadSchedulePDF();'},
                'certs':{'label':'Certificates PDF', 'fn':'M.ciniki_writingfestivals_main.festival.downloadCertificatesPDF();'},
                'comments':{'label':'Adjudicators Comments PDF', 'fn':'M.ciniki_writingfestivals_main.festival.downloadCommentsPDF();'},
            }},
        'schedule_timeslots':{'label':'Time Slots', 'type':'simplegrid', 'num_cols':2, 
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'schedule' && M.ciniki_writingfestivals_main.festival.schedulesection_id>0 && M.ciniki_writingfestivals_main.festival.scheduledivision_id>0 ? 'yes' : 'no'; },
            'cellClasses':['label', 'multiline'],
            'addTxt':'Add Time Slot',
            'addFn':'M.ciniki_writingfestivals_main.scheduletimeslot.open(\'M.ciniki_writingfestivals_main.festival.open();\',0,M.ciniki_writingfestivals_main.festival.scheduledivision_id,M.ciniki_writingfestivals_main.festival.festival_id,null);',
            },
        'timeslot_comments':{'label':'Time Slots', 'type':'simplegrid', 'num_cols':5, 
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'comments' && M.ciniki_writingfestivals_main.festival.schedulesection_id>0 && M.ciniki_writingfestivals_main.festival.scheduledivision_id>0 ? 'yes' : 'no'; },
            'headerValues':['Time', 'Name', '', '', ''],
            'headerClasses':['', '', 'aligncenter', 'aligncenter', 'aligncenter'],
            'cellClasses':['', '', 'aligncenter', 'aligncenter', 'aligncenter'],
            },
        'competitors':{'label':'', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'competitors' ? 'yes' : 'no'; },
            'headerValues':['Name', 'Classes', 'Waiver'],
            },
        'adjudicators':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'adjudicators' ? 'yes' : 'no'; },
            'addTxt':'Add Adjudicator',
            'addFn':'M.ciniki_writingfestivals_main.adjudicator.open(\'M.ciniki_writingfestivals_main.festival.open();\',0,0,M.ciniki_writingfestivals_main.festival.festival_id,null);',
            },
        'sponsors':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'sponsors' ? 'yes' : 'no'; },
            'headerValues':['Name', 'Level'],
            'addTxt':'Add Sponsor',
            'addFn':'M.ciniki_writingfestivals_main.sponsor.open(\'M.ciniki_writingfestivals_main.festival.open();\',0,M.ciniki_writingfestivals_main.festival.festival_id);',
        },
/*        'sponsors':{'label':'Sponsors', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'sponsors' ? 'yes' : 'no'; },
            'addTxt':'Manage Sponsors',
            'addFn':'M.startApp(\'ciniki.sponsors.ref\',null,\'M.ciniki_writingfestivals_main.festival.open();\',\'mc\',{\'object\':\'ciniki.writingfestivals.festival\',\'object_id\':M.ciniki_writingfestivals_main.festival.festival_id});',
        }, */
        'files':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'files' ? 'yes' : 'no'; },
            'addTxt':'Add File',
            'addFn':'M.ciniki_writingfestivals_main.addfile.open(\'M.ciniki_writingfestivals_main.festival.open();\',M.ciniki_writingfestivals_main.festival.festival_id);',
            },
        'winners':{'label':'', 'type':'simplegrid', 'num_cols':4,
            'headerValues':['Category', 'Award', 'Title', 'Author'],
            'visible':function() { return M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'winners' ? 'yes' : 'no'; },
            'addTxt':'Add Winner',
            'addFn':'M.ciniki_writingfestivals_main.winner.open(\'M.ciniki_writingfestivals_main.festival.open();\',0,M.ciniki_writingfestivals_main.festival.festival_id);',
            },
    }
    this.festival.downloadProgramPDF = function() {
        var args = {'tnid':M.curTenantID, 'festival_id':this.festival_id};
        M.api.openPDF('ciniki.writingfestivals.programPDF',args);
    }
    this.festival.downloadSchedulePDF = function() {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'schedulesection_id':this.schedulesection_id,
            'names':this.formValue('names'),
            };
        M.api.openPDF('ciniki.writingfestivals.schedulePDF',args);
    }
    this.festival.downloadCertificatesPDF = function() {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'schedulesection_id':this.schedulesection_id,
            };
        M.api.openFile('ciniki.writingfestivals.certificatesPDF',args);
    }
    this.festival.downloadCommentsPDF = function() {
        var args = {'tnid':M.curTenantID,
            'festival_id':this.festival_id,
            'schedulesection_id':this.schedulesection_id,
            };
        M.api.openPDF('ciniki.writingfestivals.commentsPDF',args);
    }
    this.festival.listLabel = function(s, i, d) { return d.label; }
    this.festival.listValue = function(s, i, d) { return this.data[i]; }
    this.festival.fieldValue = function(s, i, d) { 
        if( this.data[i] == null ) { return ''; }
        return this.data[i]; 
    }
    this.festival.liveSearchCb = function(s, i, v) {
        if( s == 'registration_search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.writingfestivals.registrationSearch', {'tnid':M.curTenantID, 'start_needle':v, 'festival_id':this.festival_id, 'limit':'50'}, function(rsp) {
                    M.ciniki_writingfestivals_main.festival.liveSearchShow('registration_search',null,M.gE(M.ciniki_writingfestivals_main.festival.panelUID + '_' + s), rsp.registrations);
                });
        }
    }
    this.festival.liveSearchResultValue = function(s, f, i, j, d) {
        if( s == 'registration_search' ) { 
            switch(j) {
                case 0: return d.class_code;
                case 1: return d.display_name;
                case 2: return d.pdf_filename;
                case 3: return '$' + d.fee;
                case 4: return d.status_text;
            }
        }
    }
    this.festival.liveSearchResultRowFn = function(s, f, i, j, d) {
        if( s == 'registration_search' ) { 
            return 'M.ciniki_writingfestivals_main.registration.open(\'M.ciniki_writingfestivals_main.festival.open();\',\'' + d.id + '\',0,0,M.ciniki_writingfestivals_main.festival.festival_id, M.ciniki_writingfestivals_main.festival.nplists.registrations,\'festival\');';
        }
    }
    this.festival.cellValue = function(s, i, j, d) {
        if( s == 'sections' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return (d.num_registrations!=0 ? d.num_registrations : '');
            }
        }
        if( s == 'categories' ) {
            switch(j) {
                case 0: return d.section_name;
                case 1: return d.name;
                case 2: return (d.num_registrations!=0 ? d.num_registrations : '');
            }
        }
        if( s == 'classes' ) {
            switch(j) {
                case 0: return d.section_name;
                case 1: return d.category_name;
                case 2: return d.code + ' - ' + d.name;
                case 3: return d.earlybird_fee + '/' + d.fee;
                case 4: return (d.num_registrations!=0 ? d.num_registrations : '');
            }
        }
        if( s == 'registrations' ) {
            switch (j) {
                case 0: return d.class_code;
                case 1: return d.display_name;
                case 2: return d.pdf_filename;
                case 3: return '$' + d.fee;
                case 4: return d.status_text;
            }
        }
        if( s == 'registration_sections' ) {
            return d.name + (d.num_registrations > 0 ? ' <span class="count">' + d.num_registrations + '</span>' : '');
        }
        if( s == 'registration_teachers' ) {
            return d.display_name + (d.num_registrations > 0 ? ' <span class="count">' + d.num_registrations + '</span>' : '');
        }
        if( s == 'schedule_sections' ) {
            switch(j) {
                case 0: return d.name;
            }
        }
        if( s == 'schedule_divisions' && M.ciniki_writingfestivals_main.festival.sections._tabs.selected == 'comments' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.name + ' <span class="subtext">' + d.division_date_text + '</span>';
            }
        }
        if( s == 'schedule_divisions' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.name + ' <span class="subdue">' + d.division_date_text + '</span><span class="subtext">' + d.address + '</span>';
            }
        }
        if( s == 'schedule_timeslots' ) {
            switch(j) {
                case 0: return d.slot_time_text;
                case 1: return '<span class="maintext">' + d.name + '</span><span class="subtext">' + d.description.replace(/\n/g, '<br/>') + '</span>';
            }
        }
        if( s == 'timeslot_comments' ) {
            switch(j) {
                case 0: return d.time;
                case 1: return '<span class="maintext">' + d.name + '</span><span class="subtext">' + d.description.replace(/\n/g, '<br/>') + '</span>';
                case 2: return d.status1;
                case 3: return d.status2;
                case 4: return d.status3;
            }
        }
        if( s == 'competitors' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.classcodes;
                case 2: return d.waiver_signed;
            }
        }
        if( s == 'adjudicators' ) {
            return d.name;
        }
        if( s == 'files' ) {
            return d.name;
        }
        if( s == 'sponsors' ) {
            switch(j) { 
                case 0: return d.name;
                case 1: return d.level;
            }
        }
        if( s == 'winners' ) {
            switch(j) {
                case 0: return d.category;
                case 1: return d.award;
                case 2: return d.title;
                case 3: return d.author;
            }
        }
/*        if( s == 'sponsors' && j == 0 ) {
            return '<span class="maintext">' + d.sponsor.title + '</span>';
        } */
    }
    this.festival.cellSortValue = function(s, i , j, d) {
        if( s == 'registrations' ) {
            switch(j) {
                case 3: return d.fee;
                case 4: return d.status;
            }
        }
        return '';
    }
    this.festival.rowFn = function(s, i, d) {
        switch(s) {
            case 'sections': return 'M.ciniki_writingfestivals_main.section.open(\'M.ciniki_writingfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_writingfestivals_main.festival.festival_id, M.ciniki_writingfestivals_main.festival.nplists.sections);';
            case 'categories': return 'M.ciniki_writingfestivals_main.category.open(\'M.ciniki_writingfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_writingfestivals_main.festival.festival_id, M.ciniki_writingfestivals_main.festival.nplists.categories);';
            case 'classes': return 'M.ciniki_writingfestivals_main.class.open(\'M.ciniki_writingfestivals_main.festival.open();\',\'' + d.id + '\',0,M.ciniki_writingfestivals_main.festival.festival_id, M.ciniki_writingfestivals_main.festival.nplists.classes);';
            case 'registrations': return 'M.ciniki_writingfestivals_main.registration.open(\'M.ciniki_writingfestivals_main.festival.open();\',\'' + d.id + '\',0,0,M.ciniki_writingfestivals_main.festival.festival_id, M.ciniki_writingfestivals_main.festival.nplists.registrations,\'festival\');';
            case 'registration_sections': return 'M.ciniki_writingfestivals_main.festival.openSection(\'' + d.id + '\',"' + M.eU(d.name) + '");';
            case 'registration_teachers': return 'M.ciniki_writingfestivals_main.festival.openTeacher(\'' + d.id + '\',"' + M.eU(d.display_name) + '");';
            case 'schedule_sections': return 'M.ciniki_writingfestivals_main.festival.openScheduleSection(\'' + d.id + '\',"' + M.eU(d.name) + '");';
            case 'schedule_divisions': return 'M.ciniki_writingfestivals_main.festival.openScheduleDivision(\'' + d.id + '\',"' + M.eU(d.name) + '");';
//            case 'schedule_sections': return 'M.ciniki_writingfestivals_main.schedulesection.open(\'M.ciniki_writingfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_writingfestivals_main.festival.festival_id,null);';
//            case 'schedule_divisions': return 'M.ciniki_writingfestivals_main.scheduledivision.open(\'M.ciniki_writingfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_writingfestivals_main.festival.schedulesection_id,M.ciniki_writingfestivals_main.festival.festival_id,null);';
            case 'schedule_timeslots': return 'M.ciniki_writingfestivals_main.scheduletimeslot.open(\'M.ciniki_writingfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_writingfestivals_main.festival.scheduledivision_id,M.ciniki_writingfestivals_main.festival.festival_id,null);';
            case 'timeslot_comments': return 'M.ciniki_writingfestivals_main.timeslotcomments.open(\'M.ciniki_writingfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_writingfestivals_main.festival.scheduledivision_id,M.ciniki_writingfestivals_main.festival.festival_id,null);';
            case 'competitors': return 'M.ciniki_writingfestivals_main.competitor.open(\'M.ciniki_writingfestivals_main.festival.open();\',\'' + d.id + '\',M.ciniki_writingfestivals_main.festival.festival_id);';
            case 'adjudicators': return 'M.ciniki_writingfestivals_main.adjudicator.open(\'M.ciniki_writingfestivals_main.festival.open();\',\'' + d.id + '\',0,M.ciniki_writingfestivals_main.festival.festival_id, M.ciniki_writingfestivals_main.festival.nplists.adjudicators);';
            case 'files': return 'M.ciniki_writingfestivals_main.editfile.open(\'M.ciniki_writingfestivals_main.festival.open();\',\'' + d.id + '\');';
            case 'sponsors': return 'M.ciniki_writingfestivals_main.sponsor.open(\'M.ciniki_writingfestivals_main.festival.open();\',\'' + d.id + '\');';
            case 'winners': return 'M.ciniki_writingfestivals_main.winner.open(\'M.ciniki_writingfestivals_main.festival.open();\',\'' + d.id + '\');';
            //case 'sponsors': return 'M.startApp(\'ciniki.sponsors.ref\',null,\'M.ciniki_writingfestivals_main.festival.open();\',\'mc\',{\'ref_id\':\'' + d.sponsor.ref_id + '\'});';
        }
        return '';
    }
    this.festival.switchTab = function(tab, stab) {
        if( tab != null ) { this.sections._tabs.selected = tab; }
        if( stab != null ) { this.sections._stabs.selected = stab; }
        this.open();
    }
    this.festival.switchRegTab = function(t) {
        this.sections.registration_tabs.selected = t;
        this.open();
    }
    this.festival.emailTeacherRegistrations = function() {
        M.ciniki_writingfestivals_main.emailregistrations.open('M.ciniki_writingfestivals_main.festival.show();');
    }
    this.festival.openSection = function(id,n) {
        this.section_id = id;
        this.teacher_customer_id = 0;
        if( id > 0 ) {
            this.sections.registrations.label = 'Registrations - ' + decodeURIComponent(n);
        } else {
            this.sections.registrations.label = 'Registrations';
        }
        this.open();
    }
    this.festival.openTeacher = function(id,n) {
        this.teacher_customer_id = id;
        this.section_id = 0;
        if( id > 0 ) {
            this.sections.registrations.label = 'Registrations - ' + M.dU(n);
        } else {
            this.sections.registrations.label = 'Registrations';
        }
        this.open();
    }
    this.festival.openScheduleSection = function(i, n) {
        this.schedulesection_id = i;
        this.sections.schedule_divisions.label = M.dU(n);
        this.scheduledivision_id = 0;
        this.open();
    }
    this.festival.openScheduleDivision = function(i, n) {
        this.scheduledivision_id = i;
        this.sections.schedule_timeslots.label = M.dU(n);
        this.open();
    }
    this.festival.downloadExcel = function(fid) {
        M.api.openFile('ciniki.writingfestivals.registrationsExcel', {'tnid':M.curTenantID, 'festival_id':fid});
    }
    this.festival.downloadPDF = function(fid) {
        M.api.openFile('ciniki.writingfestivals.registrationsPDF', {'tnid':M.curTenantID, 'festival_id':fid});
    }
    this.festival.open = function(cb, fid, list) {
        if( fid != null ) { this.festival_id = fid; }
        var args = {'tnid':M.curTenantID, 'festival_id':this.festival_id};
        this.size = 'large narrowaside';
        if( this.sections._tabs.selected == 'sections' ) {
            args['sections'] = 'yes';
            args['categories'] = 'yes';
            args['classes'] = 'yes';
        } else if( this.sections._tabs.selected == 'registrations' ) {
            args['sections'] = 'yes';
            args['registrations'] = 'yes';
        } else if( this.sections._tabs.selected == 'schedule' ) {
            this.size = 'medium mediumaside';
            args['schedule'] = 'yes';
            args['ssection_id'] = this.schedulesection_id;
            args['sdivision_id'] = this.scheduledivision_id;
            this.sections.schedule_sections.changeTxt = 'Add Schedule';
            this.sections.schedule_sections.addTxt = 'Unscheduled';
            this.sections.schedule_divisions.addTxt = 'Add Division';
        } else if( this.sections._tabs.selected == 'comments' ) {
            this.size = 'large narrowaside';
            args['schedule'] = 'yes';
            args['comments'] = 'yes';
            args['ssection_id'] = this.schedulesection_id;
            args['sdivision_id'] = this.scheduledivision_id;
            args['adjudicators'] = 'yes';
            this.sections.schedule_sections.addTxt = '';
            this.sections.schedule_sections.changeTxt = '';
            this.sections.schedule_divisions.addTxt = '';
        } else if( this.sections._tabs.selected == 'competitors' ) {
            args['competitors'] = 'yes';
        } else if( this.sections._tabs.selected == 'adjudicators' ) {
            args['adjudicators'] = 'yes';
        } else if( this.sections._tabs.selected == 'files' ) {
            args['files'] = 'yes';
        } else if( this.sections._tabs.selected == 'winners' ) {
            args['winners'] = 'yes';
        } else if( this.sections._tabs.selected == 'sponsors' ) {
            args['sponsors'] = 'yes';
        }
        if( this.section_id > 0 ) {
            args['section_id'] = this.section_id;
        }
        if( this.teacher_customer_id > 0 ) {
            args['teacher_customer_id'] = this.teacher_customer_id;
        }
        M.api.getJSONCb('ciniki.writingfestivals.festivalGet', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_writingfestivals_main.festival;
            p.data = rsp.festival;
            p.sections.timeslot_comments.headerValues[2] = '';
            p.sections.timeslot_comments.headerValues[3] = '';
            p.sections.timeslot_comments.headerValues[4] = '';
            if( rsp.festival.sections != null ) {
                p.data.registration_sections = [];
                for(var i in rsp.festival.sections) {
                    p.data.registration_sections.push({'id':rsp.festival.sections[i].id, 'name':rsp.festival.sections[i].name});
                }
//                p.data.registration_sections = rsp.festival.sections;
                p.data.registration_sections.push({'id':0, 'name':'All'});
            }
            if( rsp.festival.schedule_sections != null ) {
                for(var i in rsp.festival.schedule_sections) {
                    if( p.schedulesection_id > 0 && rsp.festival.schedule_sections[i].id == p.schedulesection_id ) {
                        if( rsp.festival.schedule_sections[i].adjudicator1_id > 0 && rsp.festival.adjudicators != null && rsp.festival.adjudicators[rsp.festival.schedule_sections[i].adjudicator1_id] != null ) {
                            p.sections.timeslot_comments.headerValues[2] = rsp.festival.adjudicators[rsp.festival.schedule_sections[i].adjudicator1_id].name;
                        }
                        if( rsp.festival.schedule_sections[i].adjudicator2_id > 0 && rsp.festival.adjudicators != null && rsp.festival.adjudicators[rsp.festival.schedule_sections[i].adjudicator2_id] != null ) {
                            p.sections.timeslot_comments.headerValues[2] = rsp.festival.adjudicators[rsp.festival.schedule_sections[i].adjudicator2_id].name;
                        }
                        if( rsp.festival.schedule_sections[i].adjudicator3_id > 0 && rsp.festival.adjudicators != null && rsp.festival.adjudicators[rsp.festival.schedule_sections[i].adjudicator3_id] != null ) {
                            p.sections.timeslot_comments.headerValues[2] = rsp.festival.adjudicators[rsp.festival.schedule_sections[i].adjudicator3_id].name;
                        }
                    }
                }
            }
            p.nplists = {};
            if( rsp.nplists != null ) {
                p.nplists = rsp.nplists;
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.festival.syllabusCopy = function(old_fid) {
        M.api.getJSONCb('ciniki.writingfestivals.festivalSyllabusCopy', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'old_festival_id':old_fid}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_writingfestivals_main.festival.open();
        });
    }
    this.festival.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.festival_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_writingfestivals_main.festival.open(null,' + this.nplist[this.nplist.indexOf('' + this.festival_id) + 1] + ');';
        }
        return null;
    }
    this.festival.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.festival_id) > 0 ) {
            return 'M.ciniki_writingfestivals_main.festival_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.festival_id) - 1] + ');';
        }
        return null;
    }
    this.festival.addButton('edit', 'Edit', 'M.ciniki_writingfestivals_main.edit.open(\'M.ciniki_writingfestivals_main.festival.open();\',M.ciniki_writingfestivals_main.festival.festival_id);');
    this.festival.addClose('Back');
    this.festival.addButton('next', 'Next');
    this.festival.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Festival
    //
    this.edit = new M.panel('Festival', 'ciniki_writingfestivals_main', 'edit', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.writingfestivals.main.edit');
    this.edit.data = null;
    this.edit.festival_id = 0;
    this.edit.nplist = [];
    this.edit.sections = {
/*        '_document_logo_id':{'label':'Document Header Logo', 'type':'imageform', 'aside':'yes', 'fields':{
            'header_logo_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_writingfestivals_main.edit.setFieldValue('header_logo_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
                'addDropImage':function(fid) {
                    M.ciniki_writingfestivals_main.edit.setFieldValue(fid,0);
                    return true;
                 },
             },
        }}, */
        'general':{'label':'', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'start_date':{'label':'Start', 'type':'date'},
            'end_date':{'label':'End', 'type':'date'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Active', '30':'Current', '60':'Archived'}},
            'flags1':{'label':'Online Registrations', 'type':'flagtoggle', 'default':'off', 'bit':0x01, 'field':'flags'},
            //'flags2':{'label':'Virtual Event', 'type':'flagtoggle', 'default':'off', 'bit':0x02, 'field':'flags'},
            'flags2':{'label':'Virtual Option', 'type':'flagtoggle', 'default':'off', 'bit':0x02, 'field':'flags',
                'on_fields':['flags3','virtual_date'],
                },
            'flags3':{'label':'Virtual Pricing', 'type':'flagtoggle', 'default':'off', 'bit':0x04, 'field':'flags', 'visible':'no'},
            'flags4':{'label':'Section End Dates', 'type':'flagtoggle', 'default':'off', 'bit':0x08, 'field':'flags', 'visible':'yes'},
            //'earlybird_date':{'label':'Earlybird End', 'type':'date'},
            'earlybird_date':{'label':'Earlybird Deadline', 'type':'datetime'},
            'live_date':{'label':'Live Deadline', 'type':'datetime'},
            'virtual_date':{'label':'Virtual Deadline', 'type':'datetime', 'visible':'no'},
            }},
        '_settings':{'label':'', 'aside':'yes', 'fields':{
            'age-restriction-msg':{'label':'Age Restriction Message', 'type':'text'},
            }},
        '_waiver':{'label':'Waiver Message', 'aside':'yes', 'fields':{
            'waiver-title':{'label':'Title', 'type':'text'},
            'waiver-msg':{'label':'Message', 'type':'textarea', 'size':'medium'},
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'website', 'tabs':{
            'website':{'label':'Website', 'fn':'M.ciniki_writingfestivals_main.edit.switchTab(\'website\');'},
            'documents':{'label':'Documents', 'fn':'M.ciniki_writingfestivals_main.edit.switchTab(\'documents\');'},
            }},
        '_primary_image_id':{'label':'Primary Image', 'type':'imageform', 
            'visible':function() { return M.ciniki_writingfestivals_main.edit.sections._tabs.selected == 'website' ? 'yes' : 'hidden'; },
            'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                    'addDropImage':function(iid) {
                        M.ciniki_writingfestivals_main.edit.setFieldValue('primary_image_id', iid, null, null);
                        return true;
                        },
                    'addDropImageRefresh':'',
                    'deleteImage':function(fid) {
                        M.ciniki_writingfestivals_main.edit.setFieldValue(fid,0);
                        return true;
                     },
                 },
        }},
        '_description':{'label':'Description', 
            'visible':function() { return M.ciniki_writingfestivals_main.edit.sections._tabs.selected == 'website' ? 'yes' : 'hidden'; },
            'fields':{
                'description':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_document_logo_id':{'label':'Document Image', 'type':'imageform',
            'visible':function() { return M.ciniki_writingfestivals_main.edit.sections._tabs.selected == 'documents' ? 'yes' : 'hidden'; },
            'fields':{
                'document_logo_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                    'addDropImage':function(iid) {
                        M.ciniki_writingfestivals_main.edit.setFieldValue('document_logo_id', iid, null, null);
                        return true;
                        },
                    'addDropImageRefresh':'',
                    'deleteImage':function(fid) {
                        M.ciniki_writingfestivals_main.edit.setFieldValue(fid,0);
                        return true;
                     },
                 },
        }},
        '_document_header_msg':{'label':'Header Message', 
            'visible':function() { return M.ciniki_writingfestivals_main.edit.sections._tabs.selected == 'documents' ? 'yes' : 'hidden'; },
            'fields':{
                'document_header_msg':{'label':'', 'hidelabel':'yes', 'type':'text'},
            }},
        '_document_footer_msg':{'label':'Footer Message', 
            'visible':function() { return M.ciniki_writingfestivals_main.edit.sections._tabs.selected == 'documents' ? 'yes' : 'hidden'; },
            'fields':{
                'document_footer_msg':{'label':'', 'hidelabel':'yes', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_writingfestivals_main.edit.save();'},
            'updatename':{'label':'Update Public Names', 
                'visible':function() {return M.ciniki_writingfestivals_main.edit.festival_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_writingfestivals_main.edit.updateNames();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_writingfestivals_main.edit.festival_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_writingfestivals_main.edit.remove();'},
            }},
        };
    this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.writingfestivals.festivalHistory', 'args':{'tnid':M.curTenantID, 'festival_id':this.festival_id, 'field':i}};
    }
    this.edit.switchTab = function(tab) {
        this.sections._tabs.selected = tab;
        this.showHideSection('_primary_image_id');
        this.showHideSection('_description');
        this.showHideSection('_document_logo_id');
        this.showHideSection('_document_header_msg');
        this.showHideSection('_document_footer_msg');
        this.refreshSection('_tabs');
    }
    this.edit.updateNames = function() {
        M.api.getJSONCb('ciniki.writingfestivals.registrationNamesUpdate', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.alert("Done");
        });
    }
    this.edit.open = function(cb, fid, list) {
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.writingfestivals.festivalGet', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_writingfestivals_main.edit;
            p.data = rsp.festival;
            if( (rsp.festival.flags&0x02) == 0x02 ) {
                p.sections.general.fields.flags3.visible = 'yes';
                p.sections.general.fields.virtual_date.visible = 'yes';
            } else {
                p.sections.general.fields.virtual_date.visible = 'no';
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.edit.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_writingfestivals_main.edit.close();'; }
        if( this.festival_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.writingfestivals.festivalUpdate', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.writingfestivals.festivalAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.edit.festival_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.edit.remove = function() {
        M.confirm('Are you sure you want to remove festival?',null,function() {
            M.api.getJSONCb('ciniki.writingfestivals.festivalDelete', {'tnid':M.curTenantID, 'festival_id':M.ciniki_writingfestivals_main.edit.festival_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.edit.close();
            });
        });
    }
    this.edit.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.festival_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_writingfestivals_main.edit.save(\'M.ciniki_writingfestivals_main.edit.open(null,' + this.nplist[this.nplist.indexOf('' + this.festival_id) + 1] + ');\');';
        }
        return null;
    }
    this.edit.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.festival_id) > 0 ) {
            return 'M.ciniki_writingfestivals_main.edit.save(\'M.ciniki_writingfestivals_main.festival_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.festival_id) - 1] + ');\');';
        }
        return null;
    }
    this.edit.addButton('save', 'Save', 'M.ciniki_writingfestivals_main.edit.save();');
    this.edit.addClose('Cancel');
    this.edit.addButton('next', 'Next');
    this.edit.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Section
    //
    this.section = new M.panel('Section', 'ciniki_writingfestivals_main', 'section', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.writingfestivals.main.section');
    this.section.data = null;
    this.section.festival_id = 0;
    this.section.section_id = 0;
    this.section.nplists = {};
    this.section.nplist = [];
    this.section.sections = {
        '_primary_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_writingfestivals_main.section.setFieldValue('primary_image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
                'deleteImage':function(fid) {
                    M.ciniki_writingfestivals_main.section.setFieldValue('primary_image_id',0);
                    return true;
                 },
             },
        }},
        'general':{'label':'Section', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'type':'text', 'required':'yes'},
            'sequence':{'label':'Order', 'type':'text', 'required':'yes', 'size':'small'},
            'live_end_dt':{'label':'Live End Date', 'type':'datetime',
                'visible':function() {return (M.ciniki_writingfestivals_main.festival.data.flags&0x08) == 0x08 ? 'yes' : 'no';},
                },
            'virtual_end_dt':{'label':'Virtual End Date', 'type':'datetime',
                'visible':function() {return (M.ciniki_writingfestivals_main.festival.data.flags&0x0a) == 0x0a ? 'yes' : 'no';},
                },
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'categories', 'tabs':{
            'categories':{'label':'Categories', 'fn':'M.ciniki_writingfestivals_main.section.switchTab(\'categories\');'},
            'synopsis':{'label':'Description', 'fn':'M.ciniki_writingfestivals_main.section.switchTab(\'synopsis\');'},
            }},
        '_synopsis':{'label':'Synopsis', 
            'visible':function() { return M.ciniki_writingfestivals_main.section.sections._tabs.selected == 'synopsis' ? 'yes' : 'hidden'; },
            'fields':{'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'}},
            },
        '_description':{'label':'Description', 
            'visible':function() { return M.ciniki_writingfestivals_main.section.sections._tabs.selected == 'synopsis' ? 'yes' : 'hidden'; },
            'fields':{'description':{'label':'', 'hidelabel':'yes', 'type':'textarea'}},
            },
        'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_writingfestivals_main.section.sections._tabs.selected == 'categories' ? 'yes' : 'hidden'; },
            'addTxt':'Add Category',
            'addFn':'M.ciniki_writingfestivals_main.section.openCategory(0);',
            },
        '_buttons':{'label':'', 'buttons':{
            'syllabuspdf':{'label':'Download Syllabus (PDF)', 'fn':'M.ciniki_writingfestivals_main.section.downloadSyllabusPDF();'},
            'save':{'label':'Save', 'fn':'M.ciniki_writingfestivals_main.section.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_writingfestivals_main.section.section_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_writingfestivals_main.section.remove();'},
            }},
        };
    this.section.fieldValue = function(s, i, d) { return this.data[i]; }
    this.section.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.writingfestivals.sectionHistory', 'args':{'tnid':M.curTenantID, 'section_id':this.section_id, 'field':i}};
    }
    this.section.cellValue = function(s, i, j, d) {
        switch (j) {
            case 0: return d.name;
        }
    }
    this.section.rowFn = function(s, i, d) {
        return 'M.ciniki_writingfestivals_main.section.openCategory(\'' + d.id + '\');';
    }
    this.section.openCategory = function(cid) {
        this.save("M.ciniki_writingfestivals_main.category.open('M.ciniki_writingfestivals_main.section.open();', '" + cid + "', this.section_id, this.festival_id, this.nplists.categories);");
    }
    this.section.switchTab = function(tab) {
        this.sections._tabs.selected = tab;
        this.refresh();
        this.show();
    }
    this.section.downloadSyllabusPDF = function() {
        M.api.openPDF('ciniki.writingfestivals.festivalSyllabusPDF', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'section_id':this.section_id});
    }
    this.section.open = function(cb, sid, fid, list) {
        if( sid != null ) { this.section_id = sid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.writingfestivals.sectionGet', {'tnid':M.curTenantID, 'section_id':this.section_id, 'festival_id':this.festival_id, 'categories':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_writingfestivals_main.section;
            p.data = rsp.section;
            p.festival_id = rsp.section.festival_id;
            p.nplists = {};
            if( rsp.nplists != null ) {
                p.nplists = rsp.nplists;
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.section.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_writingfestivals_main.section.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.section_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.writingfestivals.sectionUpdate', {'tnid':M.curTenantID, 'section_id':this.section_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.writingfestivals.sectionAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.section.section_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.section.remove = function() {
        M.confirm('Are you sure you want to remove section?',null,function() {
            M.api.getJSONCb('ciniki.writingfestivals.sectionDelete', {'tnid':M.curTenantID, 'section_id':M.ciniki_writingfestivals_main.section.section_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.section.close();
            });
        });
    }
    this.section.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.section_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_writingfestivals_main.section.save(\'M.ciniki_writingfestivals_main.section.open(null,' + this.nplist[this.nplist.indexOf('' + this.section_id) + 1] + ');\');';
        }
        return null;
    }
    this.section.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.section_id) > 0 ) {
            return 'M.ciniki_writingfestivals_main.section.save(\'M.ciniki_writingfestivals_main.section.open(null,' + this.nplist[this.nplist.indexOf('' + this.section_id) - 1] + ');\');';
        }
        return null;
    }
    this.section.addButton('save', 'Save', 'M.ciniki_writingfestivals_main.section.save();');
    this.section.addClose('Cancel');
    this.section.addButton('next', 'Next');
    this.section.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Category
    //
    this.category = new M.panel('Category', 'ciniki_writingfestivals_main', 'category', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.writingfestivals.main.category');
    this.category.data = null;
    this.category.category_id = 0;
    this.category.nplists = {};
    this.category.nplist = [];
    this.category.sections = {
        '_primary_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_writingfestivals_main.category.setFieldValue('primary_image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
                'addDropImage':function(fid) {
                    M.ciniki_writingfestivals_main.category.setFieldValue(fid,0);
                    return true;
                 },
             },
        }},
        'general':{'label':'', 'aside':'yes', 'fields':{
            'section_id':{'label':'Section', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}},
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'sequence':{'label':'Order', 'required':'yes', 'type':'text'},
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'classes', 'tabs':{
            'classes':{'label':'Classes', 'fn':'M.ciniki_writingfestivals_main.category.switchTab(\'classes\');'},
            'synopsis':{'label':'Description', 'fn':'M.ciniki_writingfestivals_main.category.switchTab(\'synopsis\');'},
            }},
        '_synopsis':{'label':'Synopsis', 
            'visible':function() { return M.ciniki_writingfestivals_main.category.sections._tabs.selected == 'synopsis' ? 'yes' : 'hidden'; },
            'fields':{'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'}},
            },
        '_description':{'label':'Description', 
            'visible':function() { return M.ciniki_writingfestivals_main.category.sections._tabs.selected == 'synopsis' ? 'yes' : 'hidden'; },
            'fields':{'description':{'label':'', 'hidelabel':'yes', 'type':'textarea'}},
            },
        'classes':{'label':'Classes', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_writingfestivals_main.category.sections._tabs.selected == 'classes' ? 'yes' : 'hidden'; },
            'addTxt':'Add Class',
            'addFn':'M.ciniki_writingfestivals_main.category.openClass(0);',
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_writingfestivals_main.category.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_writingfestivals_main.category.category_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_writingfestivals_main.category.remove();'},
            }},
        };
    this.category.fieldValue = function(s, i, d) { return this.data[i]; }
    this.category.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.writingfestivals.categoryHistory', 'args':{'tnid':M.curTenantID, 'category_id':this.category_id, 'field':i}};
    }
    this.category.cellValue = function(s, i, j, d) {
        switch (j) {
            case 0: return d.code + ' - ' + d.name;
            case 1: return d.earlybird_fee + '/' + d.fee;
        }
    }
    this.category.rowFn = function(s, i, d) {
        return 'M.ciniki_writingfestivals_main.category.openClass(\'' + d.id + '\');';
    }
    this.category.openClass = function(cid) {
        this.save("M.ciniki_writingfestivals_main.class.open('M.ciniki_writingfestivals_main.category.open();','" + cid + "', this.category_id, this.festival_id, this.nplists.classes);");
    }
    this.category.switchTab = function(tab) {
        this.sections._tabs.selected = tab;
        this.refresh();
        this.show();
    }
    this.category.open = function(cb, cid, sid,fid,list) {
        if( cid != null ) { this.category_id = cid; }
        if( sid != null ) { this.section_id = sid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.writingfestivals.categoryGet', {'tnid':M.curTenantID, 
            'category_id':this.category_id, 'festival_id':this.festival_id, 'section_id':this.section_id,
            'sections':'yes', 'classes':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_writingfestivals_main.category;
            p.data = rsp.category;
            p.nplists = {};
            if( rsp.nplists != null ) {
                p.nplists = rsp.nplists;
            }
            p.sections.general.fields.section_id.options = rsp.sections;
            p.refresh();
            p.show(cb);
        });
    }
    this.category.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_writingfestivals_main.category.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.category_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.writingfestivals.categoryUpdate', {'tnid':M.curTenantID, 'category_id':this.category_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.writingfestivals.categoryAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.category.category_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.category.remove = function() {
        M.confirm('Are you sure you want to remove category?',null,function() {
            M.api.getJSONCb('ciniki.writingfestivals.categoryDelete', {'tnid':M.curTenantID, 'category_id':M.ciniki_writingfestivals_main.category.category_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.category.close();
            });
        });
    }
    this.category.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.category_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_writingfestivals_main.category.save(\'M.ciniki_writingfestivals_main.category.open(null,' + this.nplist[this.nplist.indexOf('' + this.category_id) + 1] + ');\');';
        }
        return null;
    }
    this.category.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.category_id) > 0 ) {
            return 'M.ciniki_writingfestivals_main.category.save(\'M.ciniki_writingfestivals_main.category.open(null,' + this.nplist[this.nplist.indexOf('' + this.category_id) - 1] + ');\');';
        }
        return null;
    }
    this.category.addButton('save', 'Save', 'M.ciniki_writingfestivals_main.category.save();');
    this.category.addClose('Cancel');
    this.category.addButton('next', 'Next');
    this.category.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Class
    //
    this.class = new M.panel('Class', 'ciniki_writingfestivals_main', 'class', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.writingfestivals.main.class');
    this.class.data = null;
    this.class.festival_id = 0;
    this.class.class_id = 0;
    this.class.nplists = {};
    this.class.nplist = [];
    this.class.sections = {
        'general':{'label':'', 'aside':'yes', 'fields':{
            'category_id':{'label':'Category', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}},
            'code':{'label':'Code', 'type':'text', 'size':'small'},
            'name':{'label':'Name', 'type':'text'},
            'sequence':{'label':'Order', 'type':'text'},
            'earlybird_fee':{'label':'Earlybird Fee', 'type':'text', 'size':'small'},
            'fee':{'label':'Fee', 'type':'text', 'size':'small'},
            }},
        'registration':{'label':'Registration Options', 'aside':'yes', 'fields':{
            'flags1':{'label':'Online Registrations', 'type':'flagtoggle', 'default':'on', 'bit':0x01, 'field':'flags'},
            'flags2':{'label':'Multiple/Registrant', 'type':'flagtoggle', 'default':'on', 'bit':0x02, 'field':'flags'},
            'flags5':{'label':'2nd Competitor', 'type':'flagtoggle', 'default':'off', 'bit':0x10, 'field':'flags'},
            'flags6':{'label':'3nd Competitor', 'type':'flagtoggle', 'default':'off', 'bit':0x20, 'field':'flags'},
            }},
        'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':3, 
            'headerValues':['Competitor', 'Teacher', 'Status'],
//            'addTxt':'Add Registration',
//            'addFn':'M.ciniki_writingfestivals_main.registration.open(\'M.ciniki_writingfestivals_main.festival.open();\',0,0,M.ciniki_writingfestivals_main.class.class_id,M.ciniki_writingfestivals_main.festival.festival_id,null,\'festival\');',
            },
        '_buttons':{'label':'', 'aside':'yes', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_writingfestivals_main.class.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_writingfestivals_main.class.class_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_writingfestivals_main.class.remove();'},
            }},
        };
    this.class.fieldValue = function(s, i, d) { return this.data[i]; }
    this.class.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.writingfestivals.classHistory', 'args':{'tnid':M.curTenantID, 'class_id':this.class_id, 'field':i}};
    }
    this.class.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.display_name;
            case 1: return d.pdf_filename;
            case 2: return d.status_text;
        }
    }
    this.class.rowFn = function(s, i, d) {
        return 'M.ciniki_writingfestivals_main.registration.open(\'M.ciniki_writingfestivals_main.class.open();\',\'' + d.id + '\',0,0,M.ciniki_writingfestivals_main.class.festival_id, null,\'festival\');';
    }
    this.class.open = function(cb, iid, cid, fid, list) {
        if( iid != null ) { this.class_id = iid; }
        if( cid != null ) { this.category_id = cid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.writingfestivals.classGet', {'tnid':M.curTenantID, 'class_id':this.class_id, 'festival_id':this.festival_id, 'category_id':this.category_id, 
            'registrations':'yes', 'categories':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_writingfestivals_main.class;
            p.data = rsp.class;
            p.nplists = {};
            if( rsp.nplists != null ) {
                p.nplists = rsp.nplists;
            }
            p.sections.general.fields.category_id.options = rsp.categories;
            p.refresh();
            p.show(cb);
        });
    }
    this.class.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_writingfestivals_main.class.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.class_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.writingfestivals.classUpdate', {'tnid':M.curTenantID, 'class_id':this.class_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.writingfestivals.classAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.class.class_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.class.remove = function() {
        M.confirm('Are you sure you want to remove class?',null,function() {
            M.api.getJSONCb('ciniki.writingfestivals.classDelete', {'tnid':M.curTenantID, 'class_id':M.ciniki_writingfestivals_main.class.class_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.class.close();
            });
        });
    }
    this.class.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.class_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_writingfestivals_main.class.save(\'M.ciniki_writingfestivals_main.class.open(null,' + this.nplist[this.nplist.indexOf('' + this.class_id) + 1] + ');\');';
        }
        return null;
    }
    this.class.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.class_id) > 0 ) {
            return 'M.ciniki_writingfestivals_main.class.save(\'M.ciniki_writingfestivals_main.classd.open(null,' + this.nplist[this.nplist.indexOf('' + this.class_id) - 1] + ');\');';
        }
        return null;
    }
    this.class.addButton('save', 'Save', 'M.ciniki_writingfestivals_main.class.save();');
    this.class.addClose('Cancel');
    this.class.addButton('next', 'Next');
    this.class.addLeftButton('prev', 'Prev');

    //
    // Registrations
    //
    this.registration = new M.panel('Registration', 'ciniki_writingfestivals_main', 'registration', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.writingfestivals.main.registration');
    this.registration.data = null;
    this.registration.festival_id = 0;
    this.registration.teacher_customer_id = 0;
    this.registration.competitor1_id = 0;
    this.registration.competitor2_id = 0;
    this.registration.competitor3_id = 0;
//    this.registration.competitor4_id = 0;
//    this.registration.competitor5_id = 0;
    this.registration.registration_id = 0;
    this.registration.nplist = [];
    this.registration._source = '';
    this.registration.sections = {
//        '_tabs':{'label':'', 'type':'paneltabs', 'field_id':'rtype', 'selected':'30', 'tabs':{
//            '30':{'label':'Individual', 'fn':'M.ciniki_writingfestivals_main.registration.switchTab("30");'},
//            '50':{'label':'Duet', 'fn':'M.ciniki_writingfestivals_main.registration.switchTab("50");'},
//            '60':{'label':'Trio', 'fn':'M.ciniki_writingfestivals_main.registration.switchTab("60");'},
//            '90':{'label':'Ensemble', 'fn':'M.ciniki_writingfestivals_main.registration.switchTab("90");'},
//            }},
        '_display_name':{'label':'Duet/Trio/Ensemble Name', 'aside':'yes',
            'visible':'hidden',
//            'visible':function(){return (parseInt(M.ciniki_writingfestivals_main.registration.sections._tabs.selected)>60?'yes':'hidden');},
            'fields':{ 
                'display_name':{'label':'', 'hidelabel':'yes', 'type':'text'},
            }},
        'competitor1_details':{'label':'Competitor 1', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label', ''],
            'addTxt':'',
            'addFn':'M.ciniki_writingfestivals_main.registration.addCompetitor(M.ciniki_writingfestivals_main.registration.competitor1_id, 1);',
            'changeTxt':'Add',
            'changeFn':'M.ciniki_writingfestivals_main.registration.addCompetitor(0, 1);',
            },
        'competitor2_details':{'label':'Competitor 2', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':'hidden',
//            'visible':function(){return (parseInt(M.ciniki_writingfestivals_main.registration.sections._tabs.selected)>30?'yes':'hidden');},
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.ciniki_writingfestivals_main.registration.addCompetitor(M.ciniki_writingfestivals_main.registration.competitor1_id, 2);',
            'changeTxt':'Change',
            'changeFn':'M.ciniki_writingfestivals_main.registration.addCompetitor(0, 2);',
            },
        'competitor3_details':{'label':'Competitor 3', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':'hidden',
//            'visible':function(){return (parseInt(M.ciniki_writingfestivals_main.registration.sections._tabs.selected)>50?'yes':'hidden');},
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.ciniki_writingfestivals_main.registration.addCompetitor(M.ciniki_writingfestivals_main.registration.competitor1_id, 3);',
            'changeTxt':'Change',
            'changeFn':'M.ciniki_writingfestivals_main.registration.addCompetitor(0, 3);',
            },
/*        'competitor4_details':{'label':'Competitor 4', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':function(){return (parseInt(M.ciniki_writingfestivals_main.registration.sections._tabs.selected)>60?'yes':'hidden');},
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.ciniki_writingfestivals_main.registration.addCompetitor(M.ciniki_writingfestivals_main.registration.competitor1_id, 4);',
            'changeTxt':'Change',
            'changeFn':'M.ciniki_writingfestivals_main.registration.addCompetitor(0, 4);',
            },
        'competitor5_details':{'label':'Competitor 5', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':function(){return (parseInt(M.ciniki_writingfestivals_main.registration.sections._tabs.selected)>60?'yes':'hidden');},
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.ciniki_writingfestivals_main.registration.addCompetitor(M.ciniki_writingfestivals_main.registration.competitor1_id, 5);',
            'changeTxt':'Change',
            'changeFn':'M.ciniki_writingfestivals_main.registration.addCompetitor(0, 5);',
            }, */
        'invoice_details':{'label':'Invoice', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label', ''],
            },
        '_class':{'label':'', 'fields':{
//            'status':{'label':'Status', 'required':'yes', 'type':'toggle', 'toggles':{'5':'Draft', '10':'Applied', '50':'Paid'}},
//            'payment_type':{'label':'Payment', 'type':'toggle', 'toggles':{'20':'Square', '50':'Visa', '55':'Mastercard', '100':'Cash', '105':'Cheque', '110':'Email', '120':'Other', '121':'Online'}},
            'class_id':{'label':'Class', 'required':'yes', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}, 
                'onchangeFn':'M.ciniki_writingfestivals_main.registration.updateForm',
                },
            'title':{'label':'Title', 'type':'text'},
            'word_count':{'label':'Word Count', 'type':'text', 'size':'small'},
//            'earlybird_fee':{'label':'Earlybird Fee', 'type':'text', 'size':'small'},
            'fee':{'label':'Fee', 'type':'text', 'size':'small'},
            'pdf_filename':{'label':'PDF', 'type':'text', 'editable':'no',
//                'visible':function() { return (M.ciniki_writingfestivals_main.registration.data.festival.flags&0x02) == 0x02 ? 'yes' : 'no'},
                },
            }},
        'pdf_buttons':{'label':'', 
//            'visible':function() { return (M.ciniki_writingfestivals_main.registration.data.festival.flags&0x02) == 0x02 ? 'yes' : 'no'},
            'buttons':{
                'add':{'label':'Upload Writing PDF', 'fn':'M.ciniki_writingfestivals_main.registration.uploadPDF();',
                    'visible':function() { return M.ciniki_writingfestivals_main.registration.data.pdf_filename == '' ? 'yes' : 'no'},
                    },
                'upload':{'label':'Replace Writing PDF', 'fn':'M.ciniki_writingfestivals_main.registration.uploadPDF();',
                    'visible':function() { return M.ciniki_writingfestivals_main.registration.data.pdf_filename != '' ? 'yes' : 'no'},
                    },
                'download':{'label':'Download Writing PDF', 'fn':'M.ciniki_writingfestivals_main.registration.downloadPDF();',
                    'visible':function() { return M.ciniki_writingfestivals_main.registration.data.pdf_filename != '' ? 'yes' : 'no'},
                    },
            }},
        '_notes':{'label':'Notes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_writingfestivals_main.registration.save();'},
            'printcert':{'label':'Download Certificate PDF', 
                'visible':function() {return M.ciniki_writingfestivals_main.registration.registration_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_writingfestivals_main.registration.printCert();'},
            'printcomments':{'label':'Download Comments PDF', 
                'visible':function() {return M.ciniki_writingfestivals_main.registration.registration_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_writingfestivals_main.registration.printComments();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_writingfestivals_main.registration.registration_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_writingfestivals_main.registration.remove();'},
            }},
        };
    this.registration.fieldValue = function(s, i, d) { return this.data[i]; }
    this.registration.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.writingfestivals.registrationHistory', 'args':{'tnid':M.curTenantID, 'registration_id':this.registration_id, 'field':i}};
    }
    this.registration.cellValue = function(s, i, j, d) {
        if( s == 'competitor1_details' || s == 'competitor2_details' || s == 'competitor3_details' ) {
            switch(j) {
                case 0 : return d.label;
                case 1 : 
                    if( d.label == 'Email' ) {
                        return M.linkEmail(d.value);
                    } else if( d.label == 'Address' ) {
                        return d.value.replace(/\n/g, '<br/>');
                    }
                    return d.value;
            }
        }
        if( s == 'teacher_details' ) {
            switch(j) {
                case 0: return d.detail.label;
                case 1:
                    if( d.detail.label == 'Email' ) {
                        return M.linkEmail(d.detail.value);
                    } else if( d.detail.label == 'Address' ) {
                        return d.detail.value.replace(/\n/g, '<br/>');
                    }
                    return d.detail.value;
            }
        }
        if( s == 'invoice_details' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return d.value.replace(/\n/, '<br/>');
            }
        }
    }
    this.registration.rowFn = function(s, i, d) {
        if( s == 'invoice_details' && this._source != 'invoice' && this._source != 'pos' ) {
            return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_writingfestivals_main.registration.open();\',\'mc\',{\'invoice_id\':\'' + this.data.invoice_id + '\'});';
        }
    }
    this.registration.switchTab = function(t) {
        this.sections._tabs.selected = t;
        this.refreshSection('_tabs');
        this.showHideSection('_display_name');
        this.showHideSection('competitor2_details');
        this.showHideSection('competitor3_details');
//        this.showHideSection('competitor4_details');
//        this.showHideSection('competitor5_details');
    }
    this.registration.updateForm = function(s, i) {
        var festival = this.data.festival;
        var cid = this.formValue('class_id');
        for(var i in this.classes) {
            if( this.classes[i].id == cid ) {
                var c = this.classes[i];
    
                if( festival.earlybird == 'yes' && c.earlybird_fee > 0 ) {
                    this.setFieldValue('fee', c.earlybird_fee);
                } else {
                    this.setFieldValue('fee', c.fee);
                }

                this.sections._display_name.visible = (c.flags&0x70) > 0 ? 'yes' : 'hidden';
                this.sections.competitor2_details.visible = (c.flags&0x10) == 0x10 ? 'yes' : 'hidden';
                this.sections.competitor3_details.visible = (c.flags&0x20) == 0x20 ? 'yes' : 'hidden';
                this.showHideSection('competitor2_details');
                this.showHideSection('competitor3_details');
                this.showHideSection('_display_name');
            }
        }
    }
/*    this.registration.setFee = function(s, i) {
//        if( this.registration_id == 0 ) {
            var cid = this.formValue('class_id');
            for(var i in this.classes) {
                if( this.classes[i].id == cid ) {
                    this.setFieldValue('fee', this.classes[i].fee);
                }
            }
//        }
    } */
    this.registration.addCompetitor = function(cid,c) {
        this.save("M.ciniki_writingfestivals_main.competitor.open('M.ciniki_writingfestivals_main.registration.updateCompetitor(" + c + ");'," + cid + "," + this.festival_id + ",null);");
    }
    this.registration.updateCompetitor = function(c) {
        var p = M.ciniki_writingfestivals_main.competitor;
        if( this['competitor' + c + '_id'] != p.competitor_id ) {
            this['competitor' + c + '_id'] = p.competitor_id;
            this.save("M.ciniki_writingfestivals_main.registration.open();");
        } else {    
            this.open();
        }
/*        M.api.getJSONCb('ciniki.writingfestivals.competitorGet', {'tnid':M.curTenantID, 'competitor_id':this['competitor'+c+'_id']}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_writingfestivals_main.registration;
            p.data['competitor'+c+'_details'] = rsp.details;
            if( p['competitor' + c + '_id'] == 0 ) {
                p.sections['competitor'+c+'_details'].addTxt = '';
                p.sections['competitor'+c+'_details'].changeTxt = 'Add';
            } else {
                p.sections['competitor'+c+'_details'].addTxt = 'Edit';
                p.sections['competitor'+c+'_details'].changeTxt = 'Change';
            }
            p.refreshSection('competitor'+c+'_details');
            p.show();
        }); */
    }
/*    this.registration.updateTeacher = function(cid) {
        if( cid != null ) { 
            this.teacher_customer_id = cid;
            if( this.teacher_customer_id > 0 ) {
                M.api.getJSONCb('ciniki.customers.customerDetails', {'tnid':M.curTenantID, 'customer_id':this.teacher_customer_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_writingfestivals_main.registration;
                    p.data.teacher_details = rsp.details;
                    if( p.customer_id == 0 ) {
                        p.sections.teacher_details.addTxt = '';
                        p.sections.teacher_details.changeTxt = 'Add';
                    } else {
                        p.sections.teacher_details.addTxt = 'Edit';
                        p.sections.teacher_details.changeTxt = 'Change';
                    }
                    p.refreshSection('teacher_details');
                    p.show();
                });
            } else {
                this.data.teacher_details = [];
                this.sections.teacher_details.addTxt = '';
                this.sections.teacher_details.changeTxt = 'Add';
                this.refreshSection('teacher_details');
                this.show();
            }
        } else {
            this.show();
        }
    } */
    this.registration.printCert = function() {
        M.api.openFile('ciniki.writingfestivals.registrationCertificatesPDF', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'registration_id':this.registration_id});
    }
    this.registration.printComments = function() {
        M.api.openFile('ciniki.writingfestivals.registrationCommentsPDF', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'registration_id':this.registration_id});
    }
    this.registration.uploadPDF = function() {
        if( this.upload == null ) {
            this.upload = M.aE('input', this.panelUID + '_pdf_filename_upload', 'image_uploader');
            this.upload.setAttribute('name', 'pdf_filename');
            this.upload.setAttribute('type', 'file');
            this.upload.setAttribute('onchange', this.panelRef + '.uploadFile();');
        }
        this.upload.value = '';
        this.upload.click();
    }
    this.registration.uploadFile = function() {
        var f = this.upload;
        M.api.postJSONFile('ciniki.writingfestivals.registrationPDFAdd', 
            {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'registration_id':this.registration_id}, 
            f.files[0], 
            function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_writingfestivals_main.registration;
                p.data.pdf_filename = rsp.registration.pdf_filename;
                p.refreshSection('pdf_buttons');
                p.setFieldValue('pdf_filename', rsp.registration.pdf_filename);
            });
    }
    this.registration.downloadPDF = function() {
        M.api.openFile('ciniki.writingfestivals.registrationPDFDownload',{'tnid':M.curTenantID, 'registration_id':this.registration_id});
    }
    this.registration.open = function(cb, rid, tid, cid, fid, list, source) {
        if( rid != null ) { this.registration_id = rid; }
        if( tid != null ) { this.teacher_customer_id = tid; }
        if( fid != null ) { this.festival_id = fid; }
        if( cid != null ) { this.class_id = cid; }
        if( list != null ) { this.nplist = list; }
        if( source != null ) { this._source = source; }
        M.api.getJSONCb('ciniki.writingfestivals.registrationGet', {'tnid':M.curTenantID, 'registration_id':this.registration_id, 
            'teacher_customer_id':this.teacher_customer_id, 'festival_id':this.festival_id, 'class_id':this.class_id, 
            }, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_writingfestivals_main.registration;
            p.data = rsp.registration;
            p.classes = rsp.classes;
            if( p.festival_id == 0 ) {
                p.festival_id = rsp.registration.festival_id;
            }
//            p.sections._tabs.selected = rsp.registration.rtype;
            p.sections._class.fields.class_id.options = rsp.classes;
            p.sections._class.fields.class_id.options.unshift({'id':0, 'name':''});
//            p.teacher_customer_id = parseInt(rsp.registration.teacher_customer_id);
/*            if( p.teacher_customer_id == 0 ) {
                p.sections.teacher_details.addTxt = '';
                p.sections.teacher_details.changeTxt = 'Add';
            } else {
                p.sections.teacher_details.addTxt = 'Edit';
                p.sections.teacher_details.changeTxt = 'Change';
            } */
            for(var i = 1; i<= 3; i++) {
                p['competitor' + i + '_id'] = parseInt(rsp.registration['competitor' + i + '_id']);
                if( p['competitor' + i + '_id'] == 0 ) {
                    p.sections['competitor' + i + '_details'].addTxt = '';
                    p.sections['competitor' + i + '_details'].changeTxt = 'Add';
                } else {
                    p.sections['competitor' + i + '_details'].addTxt = 'Edit';
                    p.sections['competitor' + i + '_details'].changeTxt = 'Change';
                }
            }
            p.refresh();
            p.show(cb);
            p.updateForm();
        });
    }
    this.registration.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_writingfestivals_main.registration.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.formValue('class_id') == 0 ) {
            M.alert("You must select a class.");
            return false;
        }
//        if( this.competitor1_id == 0 ) {
//            M.alert("You must have a competitor.");
//            return false;
//        }
        if( this.registration_id > 0 ) {
            var c = this.serializeForm('no');
            if( this.teacher_customer_id != this.data.teacher_customer_id ) { c += '&teacher_customer_id=' + this.teacher_customer_id; }
            if( this.competitor1_id != this.data.competitor1_id ) { c += '&competitor1_id=' + this.competitor1_id; }
            if( this.competitor2_id != this.data.competitor2_id ) { c += '&competitor2_id=' + this.competitor2_id; }
            if( this.competitor3_id != this.data.competitor3_id ) { c += '&competitor3_id=' + this.competitor3_id; }
//            if( this.competitor4_id != this.data.competitor4_id ) { c += '&competitor4_id=' + this.competitor4_id; }
//            if( this.competitor5_id != this.data.competitor5_id ) { c += '&competitor5_id=' + this.competitor5_id; }
            if( c != '' ) {
                M.api.postJSONCb('ciniki.writingfestivals.registrationUpdate', {'tnid':M.curTenantID, 'registration_id':this.registration_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            c += '&teacher_customer_id=' + this.teacher_customer_id;
            c += '&competitor1_id=' + this.competitor1_id;
            c += '&competitor2_id=' + this.competitor2_id;
            c += '&competitor3_id=' + this.competitor3_id;
//            c += '&competitor4_id=' + this.competitor4_id;
//            c += '&competitor5_id=' + this.competitor5_id;
            M.api.postJSONCb('ciniki.writingfestivals.registrationAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.registration.registration_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.registration.remove = function() {
        M.confirm('Are you sure you want to remove registration?',null,function() {
            M.api.getJSONCb('ciniki.writingfestivals.registrationDelete', {'tnid':M.curTenantID, 'registration_id':M.ciniki_writingfestivals_main.registration.registration_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.registration.close();
            });
        });
    }
    this.registration.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.registration_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_writingfestivals_main.registration.save(\'M.ciniki_writingfestivals_main.registration.open(null,' + this.nplist[this.nplist.indexOf('' + this.registration_id) + 1] + ');\');';
        }
        return null;
    }
    this.registration.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.registration_id) > 0 ) {
            return 'M.ciniki_writingfestivals_main.registration.save(\'M.ciniki_writingfestivals_main.registration_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.registration_id) - 1] + ');\');';
        }
        return null;
    }
    this.registration.addButton('save', 'Save', 'M.ciniki_writingfestivals_main.registration.save();');
    this.registration.addClose('Cancel');
    this.registration.addButton('next', 'Next');
    this.registration.addLeftButton('prev', 'Prev');


    //
    // The panel to add/edit a competitor
    //
    this.competitor = new M.panel('Competitor', 'ciniki_writingfestivals_main', 'competitor', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.writingfestivals.main.competitor');
    this.competitor.data = null;
    this.competitor.festival_id = 0;
    this.competitor.competitor_id = 0;
    this.competitor.nplist = [];
    this.competitor.sections = {
        'general':{'label':'Competitor', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text', 'livesearch':'yes'},
            'public_name':{'label':'Public Name', 'type':'text'},
            'parent':{'label':'Parent', 'type':'text'},
            }},
        '_other':{'label':'', 'aside':'yes', 'fields':{
            'age':{'label':'Age', 'type':'text'},
            'flags1':{'label':'Waiver', 'type':'flagtoggle', 'bit':0x01, 'field':'flags', 'toggles':{'':'Unsigned', 'signed':'Signed'}},
            }},
        '_notes':{'label':'Notes', 'aside':'yes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_address':{'label':'', 'fields':{
            'address':{'label':'Address', 'type':'text'},
            'city':{'label':'City', 'type':'text', 'size':'small'},
            'province':{'label':'Province', 'type':'text', 'size':'small'},
            'postal':{'label':'Postal Code', 'type':'text', 'size':'small'},
            'phone_home':{'label':'Home Phone', 'type':'text', 'size':'small'},
            'phone_cell':{'label':'Cell Phone', 'type':'text', 'size':'small'},
            'email':{'label':'Email', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_writingfestivals_main.competitor.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_writingfestivals_main.competitor.competitor_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_writingfestivals_main.competitor.remove();'},
            }},
        };
    this.competitor.fieldValue = function(s, i, d) { return this.data[i]; }
    this.competitor.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.writingfestivals.competitorHistory', 'args':{'tnid':M.curTenantID, 'competitor_id':this.competitor_id, 'field':i}};
    }
    this.competitor.liveSearchCb = function(s, i, value) {
        if( i == 'name' ) {
            M.api.getJSONBgCb('ciniki.writingfestivals.competitorSearch', 
                {'tnid':M.curTenantID, 'start_needle':value, 'limit':25}, function(rsp) { 
                    M.ciniki_writingfestivals_main.competitor.liveSearchShow(s, i, M.gE(M.ciniki_writingfestivals_main.competitor.panelUID + '_' + i), rsp.competitors); 
                });
        }
    }
    this.competitor.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.competitor.liveSearchResultRowFn = function(s, f, i, j, d) { 
        return 'M.ciniki_writingfestivals_main.competitor.open(null,\'' + d.id + '\');';
    }
    this.competitor.open = function(cb, cid, fid, list) {
        if( cid != null ) { this.competitor_id = cid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.writingfestivals.competitorGet', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'competitor_id':this.competitor_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_writingfestivals_main.competitor;
            p.data = rsp.competitor;
            p.refresh();
            p.show(cb);
        });
    }
    this.competitor.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_writingfestivals_main.competitor.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.competitor_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.writingfestivals.competitorUpdate', {'tnid':M.curTenantID, 'festival_id':this.festival_id, 'competitor_id':this.competitor_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.writingfestivals.competitorAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.competitor.competitor_name = rsp.name;
                M.ciniki_writingfestivals_main.competitor.competitor_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.competitor.remove = function() {
        M.confirm('Are you sure you want to remove competitor?',null,function() {
            M.api.getJSONCb('ciniki.writingfestivals.competitorDelete', {'tnid':M.curTenantID, 'competitor_id':M.ciniki_writingfestivals_main.competitor.competitor_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.competitor.close();
            });
        });
    }
    this.competitor.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.competitor_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_writingfestivals_main.competitor.save(\'M.ciniki_writingfestivals_main.competitor.open(null,' + this.nplist[this.nplist.indexOf('' + this.competitor_id) + 1] + ');\');';
        }
        return null;
    }
    this.competitor.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.competitor_id) > 0 ) {
            return 'M.ciniki_writingfestivals_main.competitor.save(\'M.ciniki_writingfestivals_main.competitor_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.competitor_id) - 1] + ');\');';
        }
        return null;
    }
    this.competitor.addButton('save', 'Save', 'M.ciniki_writingfestivals_main.competitor.save();');
    this.competitor.addClose('Cancel');
    this.competitor.addButton('next', 'Next');
    this.competitor.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Schedule Section
    //
    this.schedulesection = new M.panel('Schedule Section', 'ciniki_writingfestivals_main', 'schedulesection', 'mc', 'medium', 'sectioned', 'ciniki.writingfestivals.main.schedulesection');
    this.schedulesection.data = null;
    this.schedulesection.festival_id = 0;
    this.schedulesection.schedulesection_id = 0;
    this.schedulesection.nplist = [];
    this.schedulesection.sections = {
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            }},
        'adjudicators':{'label':'Adjudicators', 'fields':{
            'adjudicator1_id':{'label':'First', 'type':'select', 'complex_options':{'name':'name', 'value':'id'}, 'options':{}},
            'adjudicator2_id':{'label':'Second', 'type':'select', 'complex_options':{'name':'name', 'value':'id'}, 'options':{}},
            'adjudicator3_id':{'label':'Third', 'type':'select', 'complex_options':{'name':'name', 'value':'id'}, 'options':{}},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_writingfestivals_main.schedulesection.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_writingfestivals_main.schedulesection.schedulesection_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_writingfestivals_main.schedulesection.remove();'},
            }},
        };
    this.schedulesection.fieldValue = function(s, i, d) { return this.data[i]; }
    this.schedulesection.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.writingfestivals.scheduleSectionHistory', 'args':{'tnid':M.curTenantID, 'schedulesection_id':this.schedulesection_id, 'field':i}};
    }
    this.schedulesection.downloadPDF = function(f,i,n) {
        M.api.openFile('ciniki.writingfestivals.schedulePDF',{'tnid':M.curTenantID, 'festival_id':f, 'schedulesection_id':i, 'names':n});
    }
    this.schedulesection.open = function(cb, sid, fid, list) {
        if( sid != null ) { this.schedulesection_id = sid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.writingfestivals.scheduleSectionGet', 
            {'tnid':M.curTenantID, 'schedulesection_id':this.schedulesection_id, 'festival_id':this.festival_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_writingfestivals_main.schedulesection;
                p.data = rsp.schedulesection;
                rsp.adjudicators.unshift({'id':'0', 'name':'None'});
                p.sections.adjudicators.fields.adjudicator1_id.options = rsp.adjudicators;
                p.sections.adjudicators.fields.adjudicator2_id.options = rsp.adjudicators;
                p.sections.adjudicators.fields.adjudicator3_id.options = rsp.adjudicators;
                p.refresh();
                p.show(cb);
            });
    }
    this.schedulesection.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_writingfestivals_main.schedulesection.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.schedulesection_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.writingfestivals.scheduleSectionUpdate', 
                    {'tnid':M.curTenantID, 'schedulesection_id':this.schedulesection_id, 'festival_id':this.festival_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        eval(cb);
                    });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.writingfestivals.scheduleSectionAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.schedulesection.schedulesection_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.schedulesection.remove = function() {
        M.confirm('Are you sure you want to remove scheduleSection?',null,function() {
            M.api.getJSONCb('ciniki.writingfestivals.scheduleSectionDelete', {'tnid':M.curTenantID, 'schedulesection_id':M.ciniki_writingfestivals_main.schedulesection.schedulesection_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.schedulesection.close();
            });
        });
    }
    this.schedulesection.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.schedulesection_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_writingfestivals_main.schedulesection.save(\'M.ciniki_writingfestivals_main.schedulesection.open(null,' + this.nplist[this.nplist.indexOf('' + this.schedulesection_id) + 1] + ');\');';
        }
        return null;
    }
    this.schedulesection.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.schedulesection_id) > 0 ) {
            return 'M.ciniki_writingfestivals_main.schedulesection.save(\'M.ciniki_writingfestivals_main.schedulesection_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.schedulesection_id) - 1] + ');\');';
        }
        return null;
    }
    this.schedulesection.addButton('save', 'Save', 'M.ciniki_writingfestivals_main.schedulesection.save();');
    this.schedulesection.addClose('Cancel');
    this.schedulesection.addButton('next', 'Next');
    this.schedulesection.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Schedule Division
    //
    this.scheduledivision = new M.panel('Schedule Division', 'ciniki_writingfestivals_main', 'scheduledivision', 'mc', 'medium', 'sectioned', 'ciniki.writingfestivals.main.scheduledivision');
    this.scheduledivision.data = null;
    this.scheduledivision.festival_id = 0;
    this.scheduledivision.ssection_id = 0;
    this.scheduledivision.scheduledivision_id = 0;
    this.scheduledivision.nplist = [];
    this.scheduledivision.sections = {
        'general':{'label':'', 'fields':{
            'ssection_id':{'label':'Section', 'required':'yes', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}},
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'division_date':{'label':'Date', 'required':'yes', 'type':'date'},
            'address':{'label':'Address', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_writingfestivals_main.scheduledivision.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_writingfestivals_main.scheduledivision.scheduledivision_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_writingfestivals_main.scheduledivision.remove();'},
            }},
        };
    this.scheduledivision.fieldValue = function(s, i, d) { return this.data[i]; }
    this.scheduledivision.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.writingfestivals.scheduleDivisionHistory', 'args':{'tnid':M.curTenantID, 'scheduledivision_id':this.scheduledivision_id, 'field':i}};
    }
    this.scheduledivision.open = function(cb, sid, ssid, fid, list) {
        if( sid != null ) { this.scheduledivision_id = sid; }
        if( ssid != null ) { this.ssection_id = ssid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.writingfestivals.scheduleDivisionGet', 
            {'tnid':M.curTenantID, 'scheduledivision_id':this.scheduledivision_id, 'festival_id':this.festival_id, 'ssection_id':this.ssection_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_writingfestivals_main.scheduledivision;
                p.data = rsp.scheduledivision;
                p.sections.general.fields.ssection_id.options = rsp.schedulesections;
                p.refresh();
                p.show(cb);
            });
    }
    this.scheduledivision.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_writingfestivals_main.scheduledivision.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.scheduledivision_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.writingfestivals.scheduleDivisionUpdate', 
                    {'tnid':M.curTenantID, 'scheduledivision_id':this.scheduledivision_id, 'festival_id':this.festival_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        eval(cb);
                    });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.writingfestivals.scheduleDivisionAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.scheduledivision.scheduledivision_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.scheduledivision.remove = function() {
        M.confirm('Are you sure you want to remove scheduleDivision?',null,function() {
            M.api.getJSONCb('ciniki.writingfestivals.scheduleDivisionDelete', {'tnid':M.curTenantID, 'scheduledivision_id':M.ciniki_writingfestivals_main.scheduledivision.scheduledivision_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.scheduledivision.close();
            });
        });
    }
    this.scheduledivision.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.scheduledivision_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_writingfestivals_main.scheduledivision.save(\'M.ciniki_writingfestivals_main.scheduledivision.open(null,' + this.nplist[this.nplist.indexOf('' + this.scheduledivision_id) + 1] + ');\');';
        }
        return null;
    }
    this.scheduledivision.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.scheduledivision_id) > 0 ) {
            return 'M.ciniki_writingfestivals_main.scheduledivision.save(\'M.ciniki_writingfestivals_main.scheduledivision_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.scheduledivision_id) - 1] + ');\');';
        }
        return null;
    }
    this.scheduledivision.addButton('save', 'Save', 'M.ciniki_writingfestivals_main.scheduledivision.save();');
    this.scheduledivision.addClose('Cancel');
    this.scheduledivision.addButton('next', 'Next');
    this.scheduledivision.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Schedule Time Slot
    //
    this.scheduletimeslot = new M.panel('Schedule Time Slot', 'ciniki_writingfestivals_main', 'scheduletimeslot', 'mc', 'medium', 'sectioned', 'ciniki.writingfestivals.main.scheduletimeslot');
    this.scheduletimeslot.data = null;
    this.scheduletimeslot.festival_id = 0;
    this.scheduletimeslot.scheduletimeslot_id = 0;
    this.scheduletimeslot.sdivision_id = 0;
    this.scheduletimeslot.nplist = [];
    this.scheduletimeslot.sections = {
        'general':{'label':'', 'fields':{
            'sdivision_id':{'label':'Division', 'required':'yes', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}},
            'slot_time':{'label':'Time', 'required':'yes', 'type':'text', 'size':'small'},
            'class1_id':{'label':'Class 1', 'required':'yes', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}, 
                'onchangeFn':'M.ciniki_writingfestivals_main.scheduletimeslot.updateRegistrations'},
            'class2_id':{'label':'Class 2', 'required':'yes', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}, 
                'onchangeFn':'M.ciniki_writingfestivals_main.scheduletimeslot.updateRegistrations'},
            'class3_id':{'label':'Class 3', 'required':'yes', 'type':'select', 'complex_options':{'value':'id', 'name':'name'}, 'options':{}, 
                'onchangeFn':'M.ciniki_writingfestivals_main.scheduletimeslot.updateRegistrations'},
            'name':{'label':'Name', 'type':'text'},
            }},
        '_options':{'label':'',
            'visible':function() {
                var p = M.ciniki_writingfestivals_main.scheduletimeslot;
                var c1 = p.formValue('class1_id');
                var c2 = p.formValue('class2_id');
                var c3 = p.formValue('class3_id');
                if( c1 == null && p.data.class1_id > 0 && p.data.class2_id == 0 && p.data.class3_id == 0 ) { return 'yes'; }
                return (c1 != null && c1 > 0 && (c2 == null || c2 == 0) && (c3 == null || c3 == 0) ? 'yes' : 'hidden');
                },
            'fields':{
                'flags1':{'label':'Split Class', 'type':'flagtoggle', 'default':'off', 'bit':0x01, 'field':'flags', 
                    'onchange':'M.ciniki_writingfestivals_main.scheduletimeslot.updateRegistrations'},
            }},
        '_registrations':{'label':'Registrations', 
            'visible':'hidden',
            'fields':{
                'registrations':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'list':[]},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'Description', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_writingfestivals_main.scheduletimeslot.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_writingfestivals_main.scheduletimeslot.scheduletimeslot_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_writingfestivals_main.scheduletimeslot.remove();'},
            }},
        };
    this.scheduletimeslot.fieldValue = function(s, i, d) { return this.data[i]; }
    this.scheduletimeslot.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.writingfestivals.scheduleTimeslotHistory', 'args':{'tnid':M.curTenantID, 'scheduletimeslot_id':this.scheduletimeslot_id, 'field':i}};
    }
    this.scheduletimeslot.updateRegistrations = function() {
        this.sections._registrations.visible = 'hidden';
        if( this.formValue('flags1') == 'on' && this.formValue('class1_id') > 0 && this.formValue('class2_id') == 0 && this.formValue('class3_id') == 0 && this.data.classes != null ) {
            for(var i in this.data.classes) {
                if( this.data.classes[i].id == this.formValue('class1_id') ) {
                    if( this.data.classes[i].registrations != null ) {
                        this.sections._registrations.visible = 'yes';
                        this.sections._registrations.fields.registrations.list = this.data.classes[i].registrations;
                    }
                }
            }
        }
        this.showHideSection('_options');
        this.showHideSection('_registrations');
        if( this.sections._registrations.visible == 'yes' ) {
            this.refreshSection('_registrations');
        }
    }
    this.scheduletimeslot.open = function(cb, sid, did, fid, list) {
        if( sid != null ) { this.scheduletimeslot_id = sid; }
        if( did != null ) { this.sdivision_id = did; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.writingfestivals.scheduleTimeslotGet', 
            {'tnid':M.curTenantID, 'scheduletimeslot_id':this.scheduletimeslot_id, 'festival_id':this.festival_id, 'sdivision_id':this.sdivision_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_writingfestivals_main.scheduletimeslot;
                p.data = rsp.scheduletimeslot;
                p.data.classes = rsp.classes;
                p.sections.general.fields.sdivision_id.options = rsp.scheduledivisions;
                rsp.classes.unshift({'id':0, 'name':'No Class'});
                p.sections.general.fields.class1_id.options = rsp.classes;
                p.sections.general.fields.class2_id.options = rsp.classes;
                p.sections.general.fields.class3_id.options = rsp.classes;
                p.sections._registrations.visible = 'hidden';
                if( rsp.scheduletimeslot.class1_id > 0 && rsp.classes != null ) {
                    for(var i in rsp.classes) {
                        if( rsp.classes[i].id == rsp.scheduletimeslot.class1_id ) {
                            if( rsp.classes[i].registrations != null ) {
                                if( (rsp.scheduletimeslot.flags&0x01) > 0 ) {
                                    p.sections._registrations.visible = 'yes';
                                }
                                p.sections._registrations.fields.registrations.list = rsp.classes[i].registrations;
                            }
                        }
                    }
                }
                p.refresh();
                p.show(cb);
            });
    }
    this.scheduletimeslot.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_writingfestivals_main.scheduletimeslot.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.scheduletimeslot_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.writingfestivals.scheduleTimeslotUpdate', 
                    {'tnid':M.curTenantID, 'scheduletimeslot_id':this.scheduletimeslot_id, 'festival_id':this.festival_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        eval(cb);
                    });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.writingfestivals.scheduleTimeslotAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.scheduletimeslot.scheduletimeslot_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.scheduletimeslot.remove = function() {
        M.confirm('Are you sure you want to remove scheduleTimeslot?',null,function() {
            M.api.getJSONCb('ciniki.writingfestivals.scheduleTimeslotDelete', {'tnid':M.curTenantID, 'scheduletimeslot_id':M.ciniki_writingfestivals_main.scheduletimeslot.scheduletimeslot_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.scheduletimeslot.close();
            });
        });
    }
    this.scheduletimeslot.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.scheduletimeslot_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_writingfestivals_main.scheduletimeslot.save(\'M.ciniki_writingfestivals_main.scheduletimeslot.open(null,' + this.nplist[this.nplist.indexOf('' + this.scheduletimeslot_id) + 1] + ');\');';
        }
        return null;
    }
    this.scheduletimeslot.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.scheduletimeslot_id) > 0 ) {
            return 'M.ciniki_writingfestivals_main.scheduletimeslot.save(\'M.ciniki_writingfestivals_main.scheduletimeslot_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.scheduletimeslot_id) - 1] + ');\');';
        }
        return null;
    }
    this.scheduletimeslot.addButton('save', 'Save', 'M.ciniki_writingfestivals_main.scheduletimeslot.save();');
    this.scheduletimeslot.addClose('Cancel');
    this.scheduletimeslot.addButton('next', 'Next');
    this.scheduletimeslot.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Schedule Time Slot Comments
    //
    this.timeslotcomments = new M.panel('Comments', 'ciniki_writingfestivals_main', 'timeslotcomments', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.writingfestivals.main.timeslotcomments');
    this.timeslotcomments.data = null;
    this.timeslotcomments.festival_id = 0;
    this.timeslotcomments.timeslot_id = 0;
    this.timeslotcomments.nplist = [];
    this.timeslotcomments.sections = {};
    this.timeslotcomments.fieldValue = function(s, i, d) { return this.data[i]; }
    this.timeslotcomments.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.writingfestivals.scheduleTimeslotHistory', 'args':{'tnid':M.curTenantID, 'scheduletimeslot_id':this.timeslot_id, 'field':i}};
    }
    this.timeslotcomments.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0 : return d.label;
            case 1 : return d.value;
            }
    }
    this.timeslotcomments.open = function(cb, tid, fid, list) {
        if( tid != null ) { this.timeslot_id = tid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.writingfestivals.scheduleTimeslotCommentsGet', 
            {'tnid':M.curTenantID, 'timeslot_id':this.timeslot_id, 'festival_id':this.festival_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_writingfestivals_main.timeslotcomments;
                p.data = rsp.timeslot;
                p.sections = {};
                for(var i in rsp.timeslot.registrations) {
                    var registration = rsp.timeslot.registrations[i];
                    p.sections['details_' + i] = {'label':'Registration', 'type':'simplegrid', 'num_cols':2, 'aside':'yes'};
                    p.data['details_' + i] = [
                        {'label':'Class', 'value':registration.reg_class_name},
                        {'label':'Participant', 'value':registration.name},
                        {'label':'Title', 'value':registration.title},
//                        {'label':'Video', 'value':M.hyperlink(registration.videolink)},
                        {'label':'PDF', 'value':registration.pdf_filename},
                        ];
                    // 
                    // Setup the comment, grade & score fields, could be for multiple adjudicators
                    //
                    for(var j in rsp.adjudicators) {
                        p.sections['comments_' + i] = {'label':rsp.adjudicators[j].display_name, 'fields':{}};
                        p.sections['comments_' + i].fields['comments_' + rsp.timeslot.registrations[i].id + '_' + rsp.adjudicators[j].id] = {
                            'label':'Comments', 
                            'type':'textarea', 
                            'size':'medium',
                            };
                        p.sections['comments_' + i].fields['grade_' + rsp.timeslot.registrations[i].id + '_' + rsp.adjudicators[j].id] = {
                            'label':'Grade', 
                            'type':'text', 
                            'size':'small',
                            };
                        p.sections['comments_' + i].fields['score_' + rsp.timeslot.registrations[i].id + '_' + rsp.adjudicators[j].id] = {
                            'label':'Score', 
                            'type':'text', 
                            'size':'small',
                            };
                    }
                }
                p.refresh();
                p.show(cb);
            });
    }
    this.timeslotcomments.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_writingfestivals_main.timeslotcomments.close();'; }
        if( !this.checkForm() ) { return false; }
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.writingfestivals.scheduleTimeslotCommentsUpdate', 
                {'tnid':M.curTenantID, 'timeslot_id':this.timeslot_id, 'festival_id':this.festival_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
        } else {
            eval(cb);
        }
    }
    this.timeslotcomments.addButton('save', 'Save', 'M.ciniki_writingfestivals_main.timeslotcomments.save();');
    this.timeslotcomments.addClose('Cancel');

    //
    // Adjudicators
    //
    this.adjudicator = new M.panel('Adjudicator', 'ciniki_writingfestivals_main', 'adjudicator', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.writingfestivals.main.adjudicator');
    this.adjudicator.data = null;
    this.adjudicator.festival_id = 0;
    this.adjudicator.adjudicator_id = 0;
    this.adjudicator.customer_id = 0;
    this.adjudicator.nplist = [];
    this.adjudicator.sections = {
        '_image_id':{'label':'Adjudicator Photo', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_writingfestivals_main.adjudicator.setFieldValue('image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
                'deleteImage':function(fid) {
                    M.ciniki_writingfestivals_main.adjudicator.setFieldValue(fid,0);
                    return true;
                 },
             },
        }}, 
        'customer_details':{'label':'Adjudicator', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_writingfestivals_main.adjudicator.updateCustomer();\',\'mc\',{\'next\':\'M.ciniki_writingfestivals_main.adjudicator.updateCustomer\',\'customer_id\':M.ciniki_writingfestivals_main.adjudicator.data.customer_id});',
            'changeTxt':'Change customer',
            'changeFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_writingfestivals_main.adjudicator.updateCustomer();\',\'mc\',{\'next\':\'M.ciniki_writingfestivals_main.adjudicator.updateCustomer\',\'customer_id\':0});',
            },
        '_description':{'label':'Full Bio', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'xlarge'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_writingfestivals_main.adjudicator.save();'},
            'delete':{'label':'Remove Adjudicator', 
                'visible':function() {return M.ciniki_writingfestivals_main.adjudicator.adjudicator_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_writingfestivals_main.adjudicator.remove();'},
            }},
        };
    this.adjudicator.fieldValue = function(s, i, d) { return this.data[i]; }
    this.adjudicator.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.writingfestivals.adjudicatorHistory', 'args':{'tnid':M.curTenantID, 'adjudicator_id':this.adjudicator_id, 'field':i}};
    }
    this.adjudicator.cellValue = function(s, i, j, d) {
        if( s == 'customer_details' && j == 0 ) { return d.detail.label; }
        if( s == 'customer_details' && j == 1 ) {
            if( d.detail.label == 'Email' ) {
                return M.linkEmail(d.detail.value);
            } else if( d.detail.label == 'Address' ) {
                return d.detail.value.replace(/\n/g, '<br/>');
            }
            return d.detail.value;
        }
    };
    this.adjudicator.open = function(cb, aid, cid, fid, list) {
        if( cb != null ) { this.cb = cb; }
        if( aid != null ) { this.adjudicator_id = aid; }
        if( cid != null ) { this.customer_id = cid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        if( aid != null && aid == 0 && cid != null && cid == 0 ) {
            M.startApp('ciniki.customers.edit',null,this.cb,'mc',{'next':'M.ciniki_writingfestivals_main.adjudicator.openCustomer', 'customer_id':0});
            return true;
        }
        M.api.getJSONCb('ciniki.writingfestivals.adjudicatorGet', {'tnid':M.curTenantID, 'customer_id':this.customer_id, 'adjudicator_id':this.adjudicator_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_writingfestivals_main.adjudicator;
            p.data = rsp.adjudicator;
            if( rsp.adjudicator.id > 0 ) {
                p.festival_id = rsp.adjudicator.festival_id;
            }
            p.customer_id = rsp.adjudicator.customer_id;
            if( p.customer_id == 0 ) {
                p.sections.customer_details.addTxt = '';
                p.sections.customer_details.changeTxt = 'Add';
            } else {
                p.sections.customer_details.addTxt = 'Edit';
                p.sections.customer_details.changeTxt = 'Change';
            }
            p.refresh();
            p.show();
        });
    }
    this.adjudicator.openCustomer = function(cid) {
        this.open(null,null,cid);
    }
    this.adjudicator.updateCustomer = function(cid) {
        if( cid != null ) { this.customer_id = cid; }
        M.api.getJSONCb('ciniki.customers.customerDetails', {'tnid':M.curTenantID, 'customer_id':this.customer_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_writingfestivals_main.adjudicator;
            p.data.customer_details = rsp.details;
            if( p.customer_id == 0 ) {
                p.sections.customer_details.addTxt = '';
                p.sections.customer_details.changeTxt = 'Add';
            } else {
                p.sections.customer_details.addTxt = 'Edit';
                p.sections.customer_details.changeTxt = 'Change';
            }
            p.refreshSection('customer_details');
            p.show();
        });
    }
    this.adjudicator.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_writingfestivals_main.adjudicator.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.adjudicator_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.writingfestivals.adjudicatorUpdate', {'tnid':M.curTenantID, 'adjudicator_id':this.adjudicator_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.writingfestivals.adjudicatorAdd', {'tnid':M.curTenantID, 'customer_id':this.customer_id, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.adjudicator.adjudicator_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.adjudicator.remove = function() {
        M.confirm('Are you sure you want to remove adjudicator?',null,function() {
            M.api.getJSONCb('ciniki.writingfestivals.adjudicatorDelete', {'tnid':M.curTenantID, 'adjudicator_id':M.ciniki_writingfestivals_main.adjudicator.adjudicator_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.adjudicator.close();
            });
        });
    }
    this.adjudicator.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.adjudicator_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_writingfestivals_main.adjudicator.save(\'M.ciniki_writingfestivals_main.adjudicator.open(null,' + this.nplist[this.nplist.indexOf('' + this.adjudicator_id) + 1] + ');\');';
        }
        return null;
    }
    this.adjudicator.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.adjudicator_id) > 0 ) {
            return 'M.ciniki_writingfestivals_main.adjudicator.save(\'M.ciniki_writingfestivals_main.adjudicator_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.adjudicator_id) - 1] + ');\');';
        }
        return null;
    }
    this.adjudicator.addButton('save', 'Save', 'M.ciniki_writingfestivals_main.adjudicator.save();');
    this.adjudicator.addClose('Cancel');
    this.adjudicator.addButton('next', 'Next');
    this.adjudicator.addLeftButton('prev', 'Prev');

    //
    // The panel to display the add form
    //
    this.addfile = new M.panel('Add File', 'ciniki_writingfestivals_main', 'addfile', 'mc', 'medium', 'sectioned', 'ciniki.writingfestivals.main.addfile');
    this.addfile.default_data = {'type':'20'};
    this.addfile.festival_id = 0;
    this.addfile.data = {}; 
    this.addfile.sections = {
        '_file':{'label':'File', 'fields':{
            'uploadfile':{'label':'', 'type':'file', 'hidelabel':'yes'},
        }},
        'info':{'label':'Information', 'type':'simpleform', 'fields':{
            'name':{'label':'Title', 'type':'text'},
            'webflags':{'label':'Website', 'type':'flags', 'default':'1', 'flags':{'1':{'name':'Visible'}}},
        }},
        '_save':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_writingfestivals_main.addfile.save();'},
        }},
    };
    this.addfile.fieldValue = function(s, i, d) { 
        if( this.data[i] != null ) { return this.data[i]; } 
        return ''; 
    };
    this.addfile.open = function(cb, eid) {
        this.reset();
        this.data = {'name':''};
        this.file_id = 0;
        this.festival_id = eid;
        this.refresh();
        this.show(cb);
    };
    this.addfile.save = function() {
        var c = this.serializeFormData('yes');
        if( c != '' ) {
            M.api.postJSONFormData('ciniki.writingfestivals.fileAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c,
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_writingfestivals_main.addfile.file_id = rsp.id;
                    M.ciniki_writingfestivals_main.addfile.close();
                });
        } else {
            this.close();
        }
    };
    this.addfile.addButton('save', 'Save', 'M.ciniki_writingfestivals_main.addfile.save();');
    this.addfile.addClose('Cancel');

    //
    // The panel to display the edit form
    //
    this.editfile = new M.panel('File', 'ciniki_writingfestivals_main', 'editfile', 'mc', 'medium', 'sectioned', 'ciniki.writingfestivals.info.editfile');
    this.editfile.file_id = 0;
    this.editfile.data = null;
    this.editfile.sections = {
        'info':{'label':'Details', 'type':'simpleform', 'fields':{
            'name':{'label':'Title', 'type':'text'},
            'webflags':{'label':'Website', 'type':'flags', 'default':'1', 'flags':{'1':{'name':'Visible'}}},
        }},
        '_save':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_writingfestivals_main.editfile.save();'},
            'download':{'label':'Download', 'fn':'M.ciniki_writingfestivals_main.editfile.download(M.ciniki_writingfestivals_main.editfile.file_id);'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_writingfestivals_main.editfile.remove();'},
        }},
    };
    this.editfile.fieldValue = function(s, i, d) { 
        return this.data[i]; 
    }
    this.editfile.sectionData = function(s) {
        return this.data[s];
    };
    this.editfile.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.writingfestivals.fileHistory', 'args':{'tnid':M.curTenantID, 'file_id':this.file_id, 'field':i}};
    };
    this.editfile.open = function(cb, fid) {
        if( fid != null ) { this.file_id = fid; }
        M.api.getJSONCb('ciniki.writingfestivals.fileGet', {'tnid':M.curTenantID, 'file_id':this.file_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_writingfestivals_main.editfile;
            p.data = rsp.file;
            p.refresh();
            p.show(cb);
        });
    };
    this.editfile.save = function() {
        var c = this.serializeFormData('no');
        if( c != '' ) {
            M.api.postJSONFormData('ciniki.writingfestivals.fileUpdate', {'tnid':M.curTenantID, 'file_id':this.file_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_writingfestivals_main.editfile.close();
            });
        }
    };
    this.editfile.remove = function() {
        M.confirm('Are you sure you want to delete \'' + this.data.name + '\'?  All information about it will be removed and unrecoverable.',null,function() {
            M.api.getJSONCb('ciniki.writingfestivals.fileDelete', {'tnid':M.curTenantID, 'file_id':M.ciniki_writingfestivals_main.editfile.file_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_writingfestivals_main.editfile.close();
            });
        });
    };
    this.editfile.download = function(fid) {
        M.api.openFile('ciniki.writingfestivals.fileDownload', {'tnid':M.curTenantID, 'file_id':fid});
    };
    this.editfile.addButton('save', 'Save', 'M.ciniki_writingfestivals_main.editfile.save();');
    this.editfile.addClose('Cancel');

    //
    // The panel to email a teacher their list of registrations
    //
    this.emailregistrations = new M.panel('Email Registrations', 'ciniki_writingfestivals_main', 'emailregistrations', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.writingfestivals.main.editfile');
    this.emailregistrations.data = {};
    this.emailregistrations.sections = {
        '_subject':{'label':'', 'type':'simpleform', 'aside':'yes', 'fields':{
            'subject':{'label':'Subject', 'type':'text'},
        }},
        '_message':{'label':'Message', 'type':'simpleform', 'aside':'yes', 'fields':{
            'message':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
        }},
        '_save':{'label':'', 'aside':'yes', 'buttons':{
            'send':{'label':'Send', 'fn':'M.ciniki_writingfestivals_main.emailregistrations.send();'},
        }},
        'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':4,
            'headerValues':['Class', 'Registrant', 'Title', 'Time'],
            },
    };
    this.emailregistrations.fieldValue = function(s, i, d) { return ''; }
    this.emailregistrations.cellValue = function(s, i, j, d) {
        if( s == 'registrations' ) {
            switch (j) {
                case 0: return d.class_code;
                case 1: return d.display_name;
                case 2: return d.title;
                case 3: return d.perf_time;
            }
        }
    }
    this.emailregistrations.open = function(cb, reg) {
        this.sections.registrations.label = M.ciniki_writingfestivals_main.festival.sections.registrations.label;
        this.data.registrations = M.ciniki_writingfestivals_main.festival.data.registrations;
        this.refresh();
        this.show(cb);
    };
    this.emailregistrations.send = function() {
        var c = this.serializeForm('yes');
        M.api.postJSONCb('ciniki.writingfestivals.registrationsEmailSend', 
            {'tnid':M.curTenantID, 'teacher_id':M.ciniki_writingfestivals_main.festival.teacher_customer_id, 'festival_id':M.ciniki_writingfestivals_main.festival.festival_id}, c, 
            function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_writingfestivals_main.emailregistrations.close();
            });
    }
    this.emailregistrations.addButton('send', 'Send', 'M.ciniki_writingfestivals_main.emailregistrations.send();');
    this.emailregistrations.addClose('Cancel');

    //
    // The panel to edit Winner
    //
    this.winner = new M.panel('Winner', 'ciniki_writingfestivals_main', 'winner', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.writingfestivals.main.winner');
    this.winner.data = null;
    this.winner.winner_id = 0;
    this.winner.nplist = [];
    this.winner.sections = {
        '_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_writingfestivals_main.winner.setFieldValue('image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
             },
        }},
        'general':{'label':'', 'aside':'yes', 'fields':{
            'category':{'label':'Category', 'required':'yes', 'type':'text'},
            'award':{'label':'Award', 'required':'yes', 'type':'text'},
            'title':{'label':'Title', 'type':'text'},
            'author':{'label':'Author', 'type':'text'},
            'sequence':{'label':'Order', 'type':'text', 'size':'small'},
            }},
        '_synopsis':{'label':'Synopsis', 'aside':'yes', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_intro':{'label':'Introduction', 'fields':{
            'intro':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_content':{'label':'Content', 'fields':{
            'content':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_writingfestivals_main.winner.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_writingfestivals_main.winner.winner_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_writingfestivals_main.winner.remove();'},
            }},
        };
    this.winner.fieldValue = function(s, i, d) { return this.data[i]; }
    this.winner.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.writingfestivals.winnerHistory', 'args':{'tnid':M.curTenantID, 'winner_id':this.winner_id, 'field':i}};
    }
    this.winner.open = function(cb, wid, fid, list) {
        if( wid != null ) { this.winner_id = wid; }
        if( fid != null ) { this.festival_id = fid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.writingfestivals.winnerGet', {'tnid':M.curTenantID, 'winner_id':this.winner_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_writingfestivals_main.winner;
            p.data = rsp.winner;
            p.refresh();
            p.show(cb);
        });
    }
    this.winner.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_writingfestivals_main.winner.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.winner_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.writingfestivals.winnerUpdate', {'tnid':M.curTenantID, 'winner_id':this.winner_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.writingfestivals.winnerAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.winner.winner_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.winner.remove = function() {
        if( confirm('Are you sure you want to remove winner?') ) {
            M.api.getJSONCb('ciniki.writingfestivals.winnerDelete', {'tnid':M.curTenantID, 'winner_id':this.winner_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.winner.close();
            });
        }
    }
    this.winner.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.winner_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_writingfestivals_main.winner.save(\'M.ciniki_writingfestivals_main.winner.open(null,' + this.nplist[this.nplist.indexOf('' + this.winner_id) + 1] + ');\');';
        }
        return null;
    }
    this.winner.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.winner_id) > 0 ) {
            return 'M.ciniki_writingfestivals_main.winner.save(\'M.ciniki_writingfestivals_main.winner.open(null,' + this.nplist[this.nplist.indexOf('' + this.winner_id) - 1] + ');\');';
        }
        return null;
    }
    this.winner.addButton('save', 'Save', 'M.ciniki_writingfestivals_main.winner.save();');
    this.winner.addClose('Cancel');
    this.winner.addButton('next', 'Next');
    this.winner.addLeftButton('prev', 'Prev');

    //
    // The panel to edit Sponsor
    //
    this.sponsor = new M.panel('Sponsor', 'ciniki_writingfestivals_main', 'sponsor', 'mc', 'medium', 'sectioned', 'ciniki.writingfestivals.main.sponsor');
    this.sponsor.data = null;
    this.sponsor.festival_id = 0;
    this.sponsor.sponsor_id = 0;
    this.sponsor.nplist = [];
    this.sponsor.sections = {
        '_image_id':{'label':'Logo', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_writingfestivals_main.sponsor.setFieldValue('image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
                'deleteImage':function(fid) {
                    M.ciniki_writingfestivals_main.sponsor.setFieldValue(fid, 0);
                    return true;
                    },
             },
        }},
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'url':{'label':'Website', 'type':'text'},
            'sequence':{'label':'Order', 'type':'text', 'size':'small'},
            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Level 1'}, '2':{'name':'Level 2'}}},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_writingfestivals_main.sponsor.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_writingfestivals_main.sponsor.sponsor_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_writingfestivals_main.sponsor.remove();'},
            }},
        };
    this.sponsor.fieldValue = function(s, i, d) { return this.data[i]; }
    this.sponsor.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.writingfestivals.sponsorHistory', 'args':{'tnid':M.curTenantID, 'sponsor_id':this.sponsor_id, 'field':i}};
    }
    this.sponsor.open = function(cb, sid, fid) {
        if( sid != null ) { this.sponsor_id = sid; }
        if( fid != null ) { this.festival_id = fid; }
        M.api.getJSONCb('ciniki.writingfestivals.sponsorGet', {'tnid':M.curTenantID, 'sponsor_id':this.sponsor_id, 'festival_id':this.festival_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_writingfestivals_main.sponsor;
            p.data = rsp.sponsor;
            p.refresh();
            p.show(cb);
        });
    }
    this.sponsor.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_writingfestivals_main.sponsor.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.sponsor_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.writingfestivals.sponsorUpdate', {'tnid':M.curTenantID, 'sponsor_id':this.sponsor_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.writingfestivals.sponsorAdd', {'tnid':M.curTenantID, 'festival_id':this.festival_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.sponsor.sponsor_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.sponsor.remove = function() {
        if( confirm('Are you sure you want to remove sponsor?') ) {
            M.api.getJSONCb('ciniki.writingfestivals.sponsorDelete', {'tnid':M.curTenantID, 'sponsor_id':this.sponsor_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_writingfestivals_main.sponsor.close();
            });
        }
    }
    this.sponsor.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.sponsor_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_writingfestivals_main.sponsor.save(\'M.ciniki_writingfestivals_main.sponsor.open(null,' + this.nplist[this.nplist.indexOf('' + this.sponsor_id) + 1] + ');\');';
        }
        return null;
    }
    this.sponsor.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.sponsor_id) > 0 ) {
            return 'M.ciniki_writingfestivals_main.sponsor.save(\'M.ciniki_writingfestivals_main.sponsor.open(null,' + this.nplist[this.nplist.indexOf('' + this.sponsor_id) - 1] + ');\');';
        }
        return null;
    }
    this.sponsor.addButton('save', 'Save', 'M.ciniki_writingfestivals_main.sponsor.save();');
    this.sponsor.addClose('Cancel');
    this.sponsor.addButton('next', 'Next');
    this.sponsor.addLeftButton('prev', 'Prev');

    //
    // Start the app
    // cb - The callback to run when the user leaves the main panel in the app.
    // ap - The application prefix.
    // ag - The app arguments.
    //
    this.start = function(cb, ap, ag) {
        args = {};
        if( ag != null ) {
            args = eval(ag);
        }
        
        //
        // Create the app container
        //
        var ac = M.createContainer(ap, 'ciniki_writingfestivals_main', 'yes');
        if( ac == null ) {
            M.alert('App Error');
            return false;
        }

        if( args.item_object != null && args.item_object == 'ciniki.writingfestivals.registration' && args.item_object_id != null ) {
            this.registration.open(cb, args.item_object_id, 0, 0, 0, null, args.source);
        } else if( args.registration_id != null && args.registration_id != '' ) {
            this.registration.open(cb, args.registration_id, 0, 0);
        } else {
            this.menu.open(cb);
        }
    }
}

