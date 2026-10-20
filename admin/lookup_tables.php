<?php
/**
 * admin/lookup_tables.php
 *
 * Registry of reference tables an administrator may maintain from
 * admin/system_settings.php. Add a line here to expose another table - the
 * generic editor (lookup_manager.php) reads its columns from the schema.
 *
 * Deliberately NOT listed: patients, pharmacy, stock/dispensing records and
 * anything else that is transactional data.
 */
function lookup_tables(): array
{
    return [
        // ── Drugs & pharmacy setup ──
        'drug'              => ['group' => 'Drugs & Pharmacy', 'label' => 'Drugs',                 'table' => 'drug',              'help' => 'Drug names used in stock, prescriptions and dispensing (e.g. Buprenorphine 2mg).'],
        'drugcategory'      => ['group' => 'Drugs & Pharmacy', 'label' => 'Drug Categories',       'table' => 'drugcategory',      'help' => 'Categories a drug can belong to.'],
        'formulation'       => ['group' => 'Drugs & Pharmacy', 'label' => 'Formulations',          'table' => 'formulation',       'help' => 'Tablet, syrup, injection and other dosage forms.'],
        'dosing'            => ['group' => 'Drugs & Pharmacy', 'label' => 'Dosing Options',        'table' => 'dosing',            'help' => 'Dosing instructions available when prescribing.'],
        'administrationroutes' => ['group' => 'Drugs & Pharmacy', 'label' => 'Administration Routes', 'table' => 'administrationroutes', 'help' => 'Oral, IM, IV...'],
        'durationunits'     => ['group' => 'Drugs & Pharmacy', 'label' => 'Duration Units',        'table' => 'durationunits',     'help' => 'Days, weeks, months...'],
        'drug_sources'      => ['group' => 'Drugs & Pharmacy', 'label' => 'Drug Sources',          'table' => 'drug_sources',      'help' => 'Where stock is received from.'],
        'suppliers'         => ['group' => 'Drugs & Pharmacy', 'label' => 'Suppliers',             'table' => 'suppliers',         'help' => 'Supplier contacts.'],
        'transaction_types' => ['group' => 'Drugs & Pharmacy', 'label' => 'Stock Transaction Types','table' => 'transaction_types', 'help' => 'Receiving, adjustments, etc.'],
        'categories'        => ['group' => 'Drugs & Pharmacy', 'label' => 'Product Categories',    'table' => 'categories',        'help' => 'Categories for non-drug products.'],

        // ── Regimens ──
        'regimens'          => ['group' => 'Regimens', 'label' => 'ART Regimens',          'table' => 'regimens',      'help' => 'Antiretroviral regimens.'],
        'regimen_type'      => ['group' => 'Regimens', 'label' => 'Regimen Types',         'table' => 'regimen_type',  'help' => 'Line / type of regimen.'],
        'tb_regimens'       => ['group' => 'Regimens', 'label' => 'TB Regimens',           'table' => 'tb_regimens',   'help' => 'Anti-TB treatment regimens (e.g. 2 RHZE, 3 RHZE).'],
        'tpt_regimens'      => ['group' => 'Regimens', 'label' => 'TPT Regimens',          'table' => 'tpt_regimens',  'help' => 'TB preventive therapy regimens.'],

        // ── Clinical statuses ──
        'tbl_hiv_status'    => ['group' => 'Clinical Statuses', 'label' => 'HIV Status',         'table' => 'tbl_hiv_status',  'help' => ''],
        'tb_status'         => ['group' => 'Clinical Statuses', 'label' => 'TB Status',          'table' => 'tb_status',       'help' => ''],
        'hepb_status'       => ['group' => 'Clinical Statuses', 'label' => 'Hepatitis B Status', 'table' => 'hepb_status',     'help' => ''],
        'hepc_status'       => ['group' => 'Clinical Statuses', 'label' => 'Hepatitis C Status', 'table' => 'hepc_status',     'help' => ''],
        'other_status'      => ['group' => 'Clinical Statuses', 'label' => 'Other Statuses',     'table' => 'other_status',    'help' => ''],
        'status'            => ['group' => 'Clinical Statuses', 'label' => 'Client Status Types','table' => 'status',          'help' => 'Active, LTFU, Weaned off...'],
        'treatment_stage'   => ['group' => 'Clinical Statuses', 'label' => 'Treatment Stages',   'table' => 'treatment_stage', 'help' => ''],
        'enrolment_status'  => ['group' => 'Clinical Statuses', 'label' => 'Enrolment Status',   'table' => 'enrolment_status','help' => ''],
        'reintegration_status' => ['group' => 'Clinical Statuses', 'label' => 'Reintegration Status', 'table' => 'reintegration_status', 'help' => ''],

        // ── Client demographics ──
        'tblgender'         => ['group' => 'Client Demographics', 'label' => 'Gender',             'table' => 'tblgender',          'help' => ''],
        'marital_status'    => ['group' => 'Client Demographics', 'label' => 'Marital Status',     'table' => 'marital_status',     'help' => ''],
        'education_level'   => ['group' => 'Client Demographics', 'label' => 'Education Levels',   'table' => 'education_level',    'help' => ''],
        'employment_status' => ['group' => 'Client Demographics', 'label' => 'Employment Status',  'table' => 'employment_status',  'help' => ''],
        'religion'          => ['group' => 'Client Demographics', 'label' => 'Religion',           'table' => 'religion',           'help' => ''],
        'living_conditions' => ['group' => 'Client Demographics', 'label' => 'Living Conditions',  'table' => 'living_conditions',  'help' => ''],
        'offences'          => ['group' => 'Client Demographics', 'label' => 'Offences',           'table' => 'offences',           'help' => ''],
        'tblcso'            => ['group' => 'Client Demographics', 'label' => 'CSOs',               'table' => 'tblcso',             'help' => 'Community organisations clients are linked to.'],
        'tblaccompanment'   => ['group' => 'Client Demographics', 'label' => 'Accompaniment Types','table' => 'tblaccompanment',    'help' => ''],
        'tblreferral'       => ['group' => 'Client Demographics', 'label' => 'Referral Types',     'table' => 'tblreferral',        'help' => ''],

        // ── Geography ──
        'countries'         => ['group' => 'Geography', 'label' => 'Countries',    'table' => 'countries',    'help' => ''],
        'counties'          => ['group' => 'Geography', 'label' => 'Counties',     'table' => 'counties',     'help' => ''],
        'sub_counties'      => ['group' => 'Geography', 'label' => 'Sub-counties', 'table' => 'sub_counties', 'help' => ''],

        // ── Users ──
        'userroles'         => ['group' => 'Users', 'label' => 'User Roles', 'table' => 'userroles', 'help' => 'Roles that can be assigned to system users.'],
    ];
}
